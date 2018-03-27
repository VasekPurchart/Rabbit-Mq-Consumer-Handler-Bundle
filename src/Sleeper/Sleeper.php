<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper;

class Sleeper extends \Consistence\ObjectPrototype
{

	/**
	 * @codeCoverageIgnore calls sleep
	 *
	 * @param int $seconds
	 */
	public function sleep(int $seconds): void
	{
		sleep($seconds);
	}

}
