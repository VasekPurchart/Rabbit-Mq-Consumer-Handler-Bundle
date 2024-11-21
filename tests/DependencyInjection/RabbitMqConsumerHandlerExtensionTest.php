<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Generator;
use PHPUnit\Framework\Assert;

class RabbitMqConsumerHandlerExtensionTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase
{

	/**
	 * @return \Symfony\Component\DependencyInjection\Extension\ExtensionInterface[]
	 */
	protected function getContainerExtensions(): array
	{
		return [
			new RabbitMqConsumerHandlerExtension(),
		];
	}

	/**
	 * @return mixed[][]|\Generator
	 */
	public function configureContainerParameterDataProvider(): Generator
	{
		yield 'default stop_consumer_sleep_seconds value' => [
			'configuration' => [],
			'parameterName' => 'vasek_purchart.rabbit_mq_consumer_handler.stop_consumer_sleep_seconds',
			'expectedParameterValue' => 1,
		];

		yield 'default entity_manager.clear value' => [
			'configuration' => [],
			'parameterName' => 'vasek_purchart.rabbit_mq_consumer_handler.entity_manager.clear',
			'expectedParameterValue' => true,
		];

		yield 'set stop_consumer_sleep_seconds' => [
			'configuration' => [
				'stop_consumer_sleep_seconds' => 2,
			],
			'parameterName' => 'vasek_purchart.rabbit_mq_consumer_handler.stop_consumer_sleep_seconds',
			'expectedParameterValue' => 2,
		];

		yield 'disable stop_consumer_sleep_seconds' => [
			'configuration' => [
				'stop_consumer_sleep_seconds' => false,
			],
			'parameterName' => 'vasek_purchart.rabbit_mq_consumer_handler.stop_consumer_sleep_seconds',
			'expectedParameterValue' => 0,
		];

		yield 'disable entity_manager.clear' => [
			'configuration' => [
				'entity_manager' => [
					'clear_em_before_message' => false,
				],
			],
			'parameterName' => 'vasek_purchart.rabbit_mq_consumer_handler.entity_manager.clear',
			'expectedParameterValue' => false,
		];
	}

	/**
	 * @dataProvider configureContainerParameterDataProvider
	 *
	 * @param mixed[][] $configuration
	 * @param string $parameterName
	 * @param mixed $expectedParameterValue
	 */
	public function testConfigureContainerParameter(
		array $configuration,
		string $parameterName,
		$expectedParameterValue
	): void
	{
		$this->load($configuration);

		$this->assertContainerBuilderHasParameter(
			$parameterName,
			$expectedParameterValue
		);

		$this->compile();
	}

	/**
	 * @return mixed[][]|\Generator
	 */
	public function configureContainerServiceAliasDataProvider(): Generator
	{
		yield 'default logger' => [
			'configuration' => [],
			'aliasId' => RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
			'expectedServiceId' => 'logger',
		];

		yield 'default entity manager' => [
			'configuration' => [],
			'aliasId' => RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
			'expectedServiceId' => 'doctrine.orm.default_entity_manager',
		];

		yield 'configure custom logger instance' => [
			'configuration' => [
				'logger' => [
					'service_id' => 'my_logger',
				],
			],
			'aliasId' => RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
			'expectedServiceId' => 'my_logger',
		];

		yield 'configure custom entity manager instance' => [
			'configuration' => [
				'entity_manager' => [
					'service_id' => 'my_entity_manager',
				],
			],
			'aliasId' => RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
			'expectedServiceId' => 'my_entity_manager',
		];
	}

	/**
	 * @dataProvider configureContainerServiceAliasDataProvider
	 *
	 * @param mixed[][] $configuration
	 * @param string $aliasId
	 * @param string $expectedServiceId
	 */
	public function testConfigureContainerService(
		array $configuration,
		string $aliasId,
		string $expectedServiceId
	): void
	{
		$this->load($configuration);

		$this->assertContainerBuilderHasAlias(
			$aliasId,
			$expectedServiceId
		);

		$this->compile();
	}

	public function testConfigureStopConsumerSleepSecondsDifferentlyOnlyForOneConsumer(): void
	{
		$customConfiguration = [
			'stop_consumer_sleep_seconds' => 3,
		];
		$this->load([
			'consumers' => [
				'my-consumer' => $customConfiguration,
			],
		]);

		$this->assertContainerBuilderHasParameter(
			'vasek_purchart.rabbit_mq_consumer_handler.custom_consumer_configurations'
		);
		$customConsumerConfigurations = $this->container->getParameter('vasek_purchart.rabbit_mq_consumer_handler.custom_consumer_configurations');
		Assert::assertArrayHasKey('my_consumer', $customConsumerConfigurations);

		Assert::assertArrayHasKey('stop_consumer_sleep_seconds', $customConsumerConfigurations['my_consumer']);
		Assert::assertSame(3, $customConsumerConfigurations['my_consumer']['stop_consumer_sleep_seconds']);

		$this->compile();
	}

}
