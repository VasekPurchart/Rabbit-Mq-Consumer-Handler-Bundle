<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

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
	 * @return mixed[][]
	 */
	public function defaultConfigurationValuesProvider(): array
	{
		return [
			[
				RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
				1,
			],
			[
				RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR,
				true,
			],
		];
	}

	/**
	 * @dataProvider defaultConfigurationValuesProvider
	 *
	 * @param string $parameterName
	 * @param mixed $parameterValue
	 */
	public function testDefaultConfigurationValues(string $parameterName, $parameterValue): void
	{
		$this->load();

		$this->assertContainerBuilderHasParameter($parameterName, $parameterValue);

		$this->compile();
	}

	/**
	 * @return mixed[][]
	 */
	public function defaultConfigurationServiceAliasesProvider(): array
	{
		return [
			[
				RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
				'logger',
			],
			[
				RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
				'doctrine.orm.default_entity_manager',
			],
		];
	}

	/**
	 * @dataProvider defaultConfigurationServiceAliasesProvider
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

	public function testConfigureStopConsumerSleepSeconds(): void
	{
		$this->load([
			'stop_consumer_sleep_seconds' => 2,
		]);

		$this->assertContainerBuilderHasParameter(
			RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			2
		);

		$this->compile();
	}

	public function testDisableStopConsumerSleepSeconds(): void
	{
		$this->load([
			'stop_consumer_sleep_seconds' => false,
		]);

		$this->assertContainerBuilderHasParameter(
			RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			0
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

	public function testDisableEntityManagerClear(): void
	{
		$this->load([
			'entity_manager' => [
				'clear_em_before_message' => false,
			],
		]);

		$this->assertContainerBuilderHasParameter(
			RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR,
			false
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
		$this->assertArrayHasKey('my_consumer', $customConsumerConfigurations);
		$this->assertArraySubset($customConfiguration, $customConsumerConfigurations['my_consumer']);

		$this->compile();
	}

}
