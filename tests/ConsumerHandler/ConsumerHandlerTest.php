<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;
use PHPUnit\Framework\Assert;
use Psr\Log\LogLevel;
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
		$consumerHandler = $this->getConsumerHandlerForNoExpectedHandling();

		Assert::assertSame($code, $consumerHandler->processMessage(function () use ($code): int {
			return $code;
		}));
	}

	public function testProcessUncaughtException(): void
	{
		$exception = new \Exception('Test');

		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects(self::once())
			->method('forceStopConsumer');

		$entityManager = $this->getOpenEntityManagerMock();

		$logger = $this->getLoggerMock();
		$logger
			->expects(self::once())
			->method('error')
			->with(
				Assert::stringContains('Test'),
				Assert::equalTo([
					'exception' => $exception,
				])
			);
		$logger
			->expects(self::once())
			->method('log')
			->with(LogLevel::WARNING, Assert::stringContains('uncaught exception'));

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::once())
			->method('sleep')
			->with(Assert::equalTo($stopConsumerSleepSeconds));

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			true,
			$sleeper
		);

		Assert::assertSame(ConsumerInterface::MSG_REJECT_REQUEUE, $consumerHandler->processMessage(
			function () use ($exception): void {
				throw $exception;
			}
		));
	}

	public function testPassConsumerHandlerToCallback(): void
	{
		$consumerHandler = $this->getConsumerHandlerForNoExpectedHandling();

		$consumerHandler->processMessage(
			function ($consumerHandler): int {
				Assert::assertInstanceOf(ConsumerHandler::class, $consumerHandler);

				return ConsumerInterface::MSG_ACK;
			}
		);
	}

	public function testStopConsumerWhenEntityManagerClosesForSuccessScenario(): void
	{
		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects(self::once())
			->method('forceStopConsumer');

		$logger = $this->getLoggerMock();
		$logger
			->expects(self::once())
			->method('log')
			->with(LogLevel::WARNING, Assert::stringContains('EntityManager was closed'));

		$entityManager = $this->getEntityManagerMock();
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(false));

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::once())
			->method('sleep')
			->with(Assert::equalTo($stopConsumerSleepSeconds));

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			true,
			$sleeper
		);

		$consumerHandler->processMessage(function (): int {
			return ConsumerInterface::MSG_ACK;
		});
	}

	public function testStopConsumerWhenEntityManagerClosesForUnhandledException(): void
	{
		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects(self::once())
			->method('forceStopConsumer');

		$logger = $this->getLoggerMock();
		$logger
			->expects(self::once())
			->method('log')
			->with(LogLevel::WARNING, Assert::stringContains('uncaught exception'));
		$logger
			->expects(self::any())
			->method('error');

		$entityManager = $this->getEntityManagerMock();
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(false));

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::once())
			->method('sleep')
			->with(Assert::equalTo($stopConsumerSleepSeconds));

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			true,
			$sleeper
		);

		$consumerHandler->processMessage(function (): void {
			throw new \Exception('Test');
		});
	}

	private function getConsumerHandlerForNoExpectedHandling(): ConsumerHandler
	{
		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects(self::never())
			->method(Assert::anything());

		$logger = $this->getLoggerMock();
		$logger
			->expects(self::never())
			->method(Assert::anything());

		$entityManager = $this->getOpenEntityManagerMock();

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::never())
			->method(Assert::anything());

		return new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			false,
			$sleeper
		);
	}

	public function testDoNotSleepWhenSleepingIsDisabled(): void
	{
		$stopConsumerSleepSeconds = 0;

		$dequeuer = $this->getDequeuerMock();

		$entityManager = $this->getOpenEntityManagerMock();

		$logger = $this->getLoggerMock();

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::never())
			->method(Assert::anything());

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			true,
			$sleeper
		);

		Assert::assertSame(ConsumerInterface::MSG_REJECT_REQUEUE, $consumerHandler->processMessage(
			function (): void {
				throw new \Exception('Test');
			}
		));
	}

	public function testClearEntityManagerBeforeMessage(): void
	{
		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects(self::never())
			->method(Assert::anything());

		$logger = $this->getLoggerMock();
		$logger
			->expects(self::never())
			->method(Assert::anything());

		$entityManager = $this->getOpenEntityManagerMock();
		$entityManager
			->expects(self::once())
			->method('clear');

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::never())
			->method(Assert::anything());

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			true,
			$sleeper
		);

		$consumerHandler->processMessage(
			function (): int {
				return ConsumerInterface::MSG_ACK;
			}
		);
	}

	public function tesDisableClearingEntityManagerBeforeMessage(): void
	{
		$stopConsumerSleepSeconds = 2;

		$dequeuer = $this->getDequeuerMock();
		$dequeuer
			->expects(self::never())
			->method(Assert::anything());

		$logger = $this->getLoggerMock();
		$logger
			->expects(self::never())
			->method(Assert::anything());

		$entityManager = $this->getOpenEntityManagerMock();
		$entityManager
			->expects(self::never())
			->method('clear');

		$sleeper = $this->getSleeperMock();
		$sleeper
			->expects(self::never())
			->method(Assert::anything());

		$consumerHandler = new ConsumerHandler(
			$stopConsumerSleepSeconds,
			$dequeuer,
			$logger,
			$entityManager,
			false,
			$sleeper
		);

		$consumerHandler->processMessage(
			function (): int {
				return ConsumerInterface::MSG_ACK;
			}
		);
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

	/**
	 * @return \Doctrine\ORM\EntityManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getEntityManagerMock()
	{
		return $this->createMock(EntityManager::class);
	}

	/**
	 * @return \Doctrine\ORM\EntityManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getOpenEntityManagerMock()
	{
		$entityManager = $this->getEntityManagerMock();
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(true));

		return $entityManager;
	}

}
