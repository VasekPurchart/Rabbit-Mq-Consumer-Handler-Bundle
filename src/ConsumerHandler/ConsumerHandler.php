<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Closure;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;
use Psr\Log\LogLevel;
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

	/** @var bool */
	private $clearEntityManager;

	/** @var \VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper */
	private $sleeper;

	/** @var bool */
	private $stopAlreadyRequested;

	public function __construct(
		int $stopConsumerSleepSeconds,
		DequeuerInterface $dequeuer,
		LoggerInterface $logger,
		EntityManager $entityManager,
		bool $clearEntityManager,
		Sleeper $sleeper
	)
	{
		$this->stopConsumerSleepSeconds = $stopConsumerSleepSeconds;
		$this->dequeuer = $dequeuer;
		$this->logger = $logger;
		$this->entityManager = $entityManager;
		$this->clearEntityManager = $clearEntityManager;
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
			if ($this->clearEntityManager) {
				$this->entityManager->clear();
			}

			return $processMessageCallback($this);

		} catch (\Throwable $e) {
			$this->logException($e);
			$this->stopConsumer('uncaught exception');

			return ConsumerInterface::MSG_REJECT_REQUEUE;

		} finally {
			if (!$this->entityManager->isOpen()) {
				$this->stopConsumer('EntityManager was closed');
			}
		}
	}

	public function stopConsumer(string $reason): void
	{
		if ($this->stopAlreadyRequested) {
			return;
		}

		$this->log(LogLevel::WARNING, 'Consumer will be stopped, reason: ' . $reason);
		$this->dequeuer->forceStopConsumer();
		$this->stopAlreadyRequested = true;
		if ($this->stopConsumerSleepSeconds > 0) {
			$this->sleeper->sleep($this->stopConsumerSleepSeconds);
		}
	}

	/**
	 * @param mixed $level
	 * @param string $message
	 * @param mixed[] $context
	 */
	public function log($level, string $message, array $context = []): void
	{
		$this->logger->log($level, $message, $context);
	}

	public function logException(\Throwable $exception): void
	{
		$this->logger->error($exception->getMessage(), [
			'exception' => $exception,
		]);
	}

}
