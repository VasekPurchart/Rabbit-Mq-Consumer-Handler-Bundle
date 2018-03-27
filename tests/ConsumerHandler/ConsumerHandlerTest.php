<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;
use Psr\Log\LoggerInterface;
use VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper;

class ConsumerHandlerTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * @return int[][]
	 */
	public function resultCodesProvider(): array
	{
		return [
			[ConsumerInterface::MSG_ACK],
			[ConsumerInterface::MSG_REJECT],
			[ConsumerInterface::MSG_REJECT_REQUEUE],
			[ConsumerInterface::MSG_SINGLE_NACK_REQUEUE],
		];
	}

	/**
	 * @dataProvider resultCodesProvider
	 *
	 * @param int $code
	 */
	public function testProcessWithCallback(int $code): void
	{
		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects($this->never())
			->method($this->anything());

		$logger = $this->getLoggerMock();
		$logger
			->expects($this->never())
			->method($this->anything());

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects($this->never())
			->method($this->anything());

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$sleeper
		);

		$this->assertSame($code, $consumerHandler->processMessage(function () use ($code): int {
			return $code;
		}));
	}

	public function testProcessUncaughtException(): void
	{
		$exception = new \Exception('Test');

		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects($this->once())
			->method('forceStopConsumer');

		$logger = $this->getLoggerMock();
		$logger
			->expects($this->once())
			->method('error')
			->with(
				$this->stringContains('Test'),
				$this->equalTo([
					'exception' => $exception,
				])
			);

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects($this->once())
			->method('sleep')
			->with($this->equalTo($stopConsumerSleepSeconds));

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$sleeper
		);

		$this->assertSame(ConsumerInterface::MSG_REJECT_REQUEUE, $consumerHandler->processMessage(
			function () use ($exception): int {
				throw $exception;
			}
		));
	}

	/**
	 * @return \OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getDequeuerMock()
	{
		return $this->createMock(DequeuerInterface::class);
	}

	/**
	 * @return \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getLoggerMock()
	{
		return $this->createMock(LoggerInterface::class);
	}

	/**
	 * @return \VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getSleeperMock()
	{
		return $this->createMock(Sleeper::class);
	}

}
