<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Generator;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RabbitMqConsumerHandlerExtensionTest extends \PHPUnit\Framework\TestCase
{

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
	 * @param mixed[][]|array $configuration
	 * @param string $parameterName
	 * @param mixed $expectedParameterValue
	 */
	public function testConfigureContainerParameter(
		array $configuration,
		string $parameterName,
		$expectedParameterValue
	): void
	{
		$container = self::createContainer();
		$container->registerExtension(new RabbitMqConsumerHandlerExtension());

		$this->load($container, $configuration);

		self::assertContainerHasParameter($container, $parameterName);
		Assert::assertSame($expectedParameterValue, $container->getParameter($parameterName));

		$container->compile();
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
	 * @param mixed[][]|array $configuration
	 * @param string $aliasId
	 * @param string $expectedServiceId
	 */
	public function testConfigureContainerService(
		array $configuration,
		string $aliasId,
		string $expectedServiceId
	): void
	{
		$container = self::createContainer();
		$container->registerExtension(new RabbitMqConsumerHandlerExtension());

		$this->load($container, $configuration);

		self::assertContainerHasAlias($container, $aliasId);
		Assert::assertSame($expectedServiceId, $container->getAlias($aliasId)->__toString());

		$container->compile();
	}

	public function testConfigureStopConsumerSleepSecondsDifferentlyOnlyForOneConsumer(): void
	{
		$container = self::createContainer();
		$container->registerExtension(new RabbitMqConsumerHandlerExtension());

		$customConfiguration = [
			'stop_consumer_sleep_seconds' => 3,
		];
		$this->load(
			$container,
			[
				'consumers' => [
					'my-consumer' => $customConfiguration,
				],
			]
		);

		$parameterName = 'vasek_purchart.rabbit_mq_consumer_handler.custom_consumer_configurations';
		self::assertContainerHasParameter($container, $parameterName);

		$customConsumerConfigurations = $container->getParameter($parameterName);
		Assert::assertArrayHasKey('my_consumer', $customConsumerConfigurations);

		Assert::assertArrayHasKey('stop_consumer_sleep_seconds', $customConsumerConfigurations['my_consumer']);
		Assert::assertSame(3, $customConsumerConfigurations['my_consumer']['stop_consumer_sleep_seconds']);

		$container->compile();
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @param mixed[][]|array $configuration
	 */
	private function load(ContainerBuilder $container, array $configuration): void
	{
		foreach ($container->getExtensions() as $extension) {
			$extension->load([$configuration], $container);
		}
	}

	public static function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		return $container;
	}

	private static function assertContainerHasParameter(ContainerBuilder $container, string $parameterName): void
	{
		Assert::assertTrue(
			$container->hasParameter($parameterName),
			sprintf('Container is missing required parameter `%s`.', $parameterName)
		);
	}

	private static function assertContainerHasAlias(ContainerBuilder $container, string $aliasId): void
	{
		Assert::assertTrue(
			$container->hasAlias($aliasId),
			sprintf('Container is missing required alias `%s`.', $aliasId)
		);
	}

}
