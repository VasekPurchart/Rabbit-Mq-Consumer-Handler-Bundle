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

}
