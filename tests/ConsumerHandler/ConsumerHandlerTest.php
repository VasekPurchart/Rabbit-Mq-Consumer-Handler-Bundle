<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;

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
		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects($this->never())
			->method($this->anything());

		$consumerHandler = new ConsumerHandler(
			$dequeuer
		);

		$this->assertSame($code, $consumerHandler->processMessage(function () use ($code): int {
			return $code;
		}));
	}

	public function testProcessUncaughtException(): void
	{
		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects($this->once())
			->method('forceStopConsumer');

		$consumerHandler = new ConsumerHandler(
			$dequeuer
		);

		$this->assertSame(ConsumerInterface::MSG_REJECT_REQUEUE, $consumerHandler->processMessage(
			function (): int {
				throw new \Exception();
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

}
