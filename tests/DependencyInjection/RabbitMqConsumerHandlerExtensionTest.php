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
	public function defaultConfigurationServiceAliasesDataProvider(): Generator
	{
		yield 'logger' => [
			'aliasName' => RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
			'targetServiceId' => 'logger',
		];
		yield 'entity_manager' => [
			'aliasName' => RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
			'targetServiceId' => 'doctrine.orm.default_entity_manager',
		];
	}

	/**
	 * @dataProvider defaultConfigurationServiceAliasesDataProvider
	 *
	 * @param string $aliasName
	 * @param string $targetServiceId
	 */
	public function testDefaultConfigurationServices(string $aliasName, string $targetServiceId): void
	{
		$this->load();

		$this->assertContainerBuilderHasAlias($aliasName, $targetServiceId);

		$this->compile();
	}

	/**
	 * @return mixed[][]|\Generator
	 */
	public function configureContainerParameterDataProvider(): Generator
	{
		yield 'default stop_consumer_sleep_seconds value' => [
			'configuration' => [],
			'parameterName' => RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			'expectedParameterValue' => 1,
		];

		yield 'default entity_manager.clear value' => [
			'configuration' => [],
			'parameterName' => RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR,
			'expectedParameterValue' => true,
		];

		yield 'set stop_consumer_sleep_seconds' => [
			'configuration' => [
				'stop_consumer_sleep_seconds' => 2,
			],
			'parameterName' => RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			'expectedParameterValue' => 2,
		];

		yield 'disable stop_consumer_sleep_seconds' => [
			'configuration' => [
				'stop_consumer_sleep_seconds' => false,
			],
			'parameterName' => RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			'expectedParameterValue' => 0,
		];

		yield 'disable entity_manager.clear' => [
			'configuration' => [
				'entity_manager' => [
					'clear_em_before_message' => false,
				],
			],
			'parameterName' => RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR,
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

	public function testConfigureCustomLoggerInstance(): void
	{
		$this->load([
			'logger' => [
				'service_id' => 'my_logger',
			],
		]);

		$this->assertContainerBuilderHasAlias(
			RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
			'my_logger'
		);

		$this->compile();
	}

	public function testConfigureCustomEntityManagerInstance(): void
	{
		$this->load([
			'entity_manager' => [
				'service_id' => 'my_entity_manager',
			],
		]);

		$this->assertContainerBuilderHasAlias(
			RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
			'my_entity_manager'
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
			RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_CUSTOM_CONSUMER_CONFIGURATIONS
		);
		$customConsumerConfigurations = $this->container->getParameter(RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_CUSTOM_CONSUMER_CONFIGURATIONS);
		Assert::assertArrayHasKey('my_consumer', $customConsumerConfigurations);

		Assert::assertArrayHasKey('stop_consumer_sleep_seconds', $customConsumerConfigurations['my_consumer']);
		Assert::assertSame(3, $customConsumerConfigurations['my_consumer']['stop_consumer_sleep_seconds']);

		$this->compile();
	}

}
