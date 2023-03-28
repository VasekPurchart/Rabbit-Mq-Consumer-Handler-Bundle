<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler;

use Doctrine\ORM\EntityManager;
use Generator;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface;
use PHPUnit\Framework\Assert;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper;

class ConsumerHandlerTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * @return int[][]|\Generator
	 */
	public function resultCodeDataProvider(): Generator
	{
		yield 'MSG_ACK' => [
			'code' => ConsumerInterface::MSG_ACK,
		];
		yield 'MSG_REJECT' => [
			'code' => ConsumerInterface::MSG_REJECT,
		];
		yield 'MSG_REJECT_REQUEUE' => [
			'code' => ConsumerInterface::MSG_REJECT_REQUEUE,
		];
		yield 'MSG_SINGLE_NACK_REQUEUE' => [
			'code' => ConsumerInterface::MSG_SINGLE_NACK_REQUEUE,
		];
	}

	/**
	 * @dataProvider resultCodeDataProvider
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

		$dequeuer = $this->createMock(DequeuerInterface::class);
		$dequeuer
			->expects(self::once())
			->method('forceStopConsumer');

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(true));

		$logger = $this->createMock(LoggerInterface::class);
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

		$sleeper = $this->createMock(Sleeper::class);
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

		$dequeuer = $this->createMock(DequeuerInterface::class);
		$dequeuer
			->expects(self::once())
			->method('forceStopConsumer');

		$logger = $this->createMock(LoggerInterface::class);
		$logger
			->expects(self::once())
			->method('log')
			->with(LogLevel::WARNING, Assert::stringContains('EntityManager was closed'));

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(false));

		$sleeper = $this->createMock(Sleeper::class);
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

		$dequeuer = $this->createMock(DequeuerInterface::class);
		$dequeuer
			->expects(self::once())
			->method('forceStopConsumer');

		$logger = $this->createMock(LoggerInterface::class);
		$logger
			->expects(self::once())
			->method('log')
			->with(LogLevel::WARNING, Assert::stringContains('uncaught exception'));
		$logger
			->expects(self::any())
			->method('error');

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(false));

		$sleeper = $this->createMock(Sleeper::class);
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

		$dequeuer = $this->createMock(DequeuerInterface::class);
		$dequeuer
			->expects(self::never())
			->method(Assert::anything());

		$logger = $this->createMock(LoggerInterface::class);
		$logger
			->expects(self::never())
			->method(Assert::anything());

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(true));

		$sleeper = $this->createMock(Sleeper::class);
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

		$dequeuer = $this->createMock(DequeuerInterface::class);

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(true));

		$logger = $this->createMock(LoggerInterface::class);

		$sleeper = $this->createMock(Sleeper::class);
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

		$dequeuer = $this->createMock(DequeuerInterface::class);
		$dequeuer
			->expects(self::never())
			->method(Assert::anything());

		$logger = $this->createMock(LoggerInterface::class);
		$logger
			->expects(self::never())
			->method(Assert::anything());

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(true));
		$entityManager
			->expects(self::once())
			->method('clear');

		$sleeper = $this->createMock(Sleeper::class);
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

		$dequeuer = $this->createMock(DequeuerInterface::class);
		$dequeuer
			->expects(self::never())
			->method(Assert::anything());

		$logger = $this->createMock(LoggerInterface::class);
		$logger
			->expects(self::never())
			->method(Assert::anything());

		$entityManager = $this->createMock(EntityManager::class);
		$entityManager
			->expects(self::any())
			->method('isOpen')
			->will(self::returnValue(true));
		$entityManager
			->expects(self::never())
			->method('clear');

		$sleeper = $this->createMock(Sleeper::class);
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

}
