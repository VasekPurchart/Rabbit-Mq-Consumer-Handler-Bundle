<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Closure;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;
use Psr\Log\LoggerInterface;
use VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper;

class ConsumerHandler extends \Consistence\ObjectPrototype
{

	/** @var int */
	private $stopConsumerSleepSeconds;

	/** @var \OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface */
	private $dequeuer;

	/** @var \Psr\Log\LoggerInterface */
	private $logger;

	/** @var \Doctrine\ORM\EntityManager */
	private $entityManager;

	/** @var \VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper */
	private $sleeper;

	/** @var bool */
	private $stopAlreadyRequested;

	public function __construct(
		int $stopConsumerSleepSeconds,
		DequeuerInterface $dequeuer,
		LoggerInterface $logger,
		EntityManager $entityManager,
		Sleeper $sleeper
	)
	{
		$this->stopConsumerSleepSeconds = $stopConsumerSleepSeconds;
		$this->dequeuer = $dequeuer;
		$this->logger = $logger;
		$this->entityManager = $entityManager;
		$this->sleeper = $sleeper;
		$this->stopAlreadyRequested = false;
	}

	/**
	 * @param \Closure $processMessageCallback
	 * @return int \OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface::MSG_* value
	 */
	public function processMessage(
		Closure $processMessageCallback
	): int
	{
		try {
			$this->entityManager->clear();

			return $processMessageCallback($this);

		} catch (\Throwable $e) {
			$this->logException($e);
			$this->stopConsumer();

			return ConsumerInterface::MSG_REJECT_REQUEUE;

		} finally {
			if (!$this->entityManager->isOpen()) {
				$this->stopConsumer();
			}
		}
	}

	public function stopConsumer(): void
	{
		if ($this->stopAlreadyRequested) {
			return;
		}

		$this->dequeuer->forceStopConsumer();
		$this->stopAlreadyRequested = true;
		$this->sleeper->sleep($this->stopConsumerSleepSeconds);
	}

	public function logException(\Throwable $exception): void
	{
		$this->logger->error($exception->getMessage(), [
			'exception' => $exception,
		]);
	}

}
