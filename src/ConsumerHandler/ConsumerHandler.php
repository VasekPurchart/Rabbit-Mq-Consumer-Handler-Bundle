<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Closure;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class ConsumerHandler extends \Consistence\ObjectPrototype
{

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
			return ConsumerInterface::MSG_REJECT_REQUEUE;

		}
	}

}
