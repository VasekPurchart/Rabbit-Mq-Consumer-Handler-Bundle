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

}
