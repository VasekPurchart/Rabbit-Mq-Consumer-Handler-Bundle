<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Closure;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;
use VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper;

class ConsumerHandler extends \Consistence\ObjectPrototype
{

	/** @var int */
	private $stopConsumerSleepSeconds;

	/** @var \OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface */
	private $dequeuer;

	/** @var \VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper */
	private $sleeper;

	public function __construct(
		int $stopConsumerSleepSeconds,
		DequeuerInterface $dequeuer,
		Sleeper $sleeper
	)
	{
		$this->stopConsumerSleepSeconds = $stopConsumerSleepSeconds;
		$this->dequeuer = $dequeuer;
		$this->sleeper = $sleeper;
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
			return $processMessageCallback();

		} catch (\Throwable $e) {
			$this->stopConsumer();

			return ConsumerInterface::MSG_REJECT_REQUEUE;

		}
	}

	public function stopConsumer(): void
	{
		$this->dequeuer->forceStopConsumer();
		$this->sleeper->sleep($this->stopConsumerSleepSeconds);
	}

}
