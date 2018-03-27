<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Closure;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;

class ConsumerHandler extends \Consistence\ObjectPrototype
{

	/** @var \OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface */
	private $dequeuer;

	public function __construct(
		DequeuerInterface $dequeuer
	)
	{
		$this->dequeuer = $dequeuer;
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
			$this->dequeuer->forceStopConsumer();

			return ConsumerInterface::MSG_REJECT_REQUEUE;

		}
	}

}
