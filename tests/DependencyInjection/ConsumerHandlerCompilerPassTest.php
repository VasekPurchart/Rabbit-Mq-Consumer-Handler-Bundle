<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler;

class ConsumerHandlerCompilerPassTest extends \PHPUnit\Framework\TestCase
{

	public function testWrapProducersWithDatabaseTransactionProducer(): void
	{
		$container = self::createContainer();
		$container->addCompilerPass(new ConsumerHandlerCompilerPass());
		$container->registerExtension(new RabbitMqConsumerHandlerExtension());
		$container->loadFromExtension('rabbit_mq_consumer_handler');

		$consumerServiceId = 'old_sound_rabbit_mq.my_consumer_name_consumer';
		$this->registerConsumer($container, $consumerServiceId);

		$container->compile();

		$consumerHandlerName = 'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.my_consumer_name';
		self::assertContainerHasService($container, $consumerHandlerName);
		self::assertContainerServiceIsOfType($container, $consumerHandlerName, ConsumerHandler::class);

		$consumerHandlerDefinition = $container->findDefinition($consumerHandlerName);
		$dequeuerArgument = $consumerHandlerDefinition->getArgument('$dequeuer');
		Assert::assertInstanceOf(Reference::class, $dequeuerArgument);
		Assert::assertSame(
			$consumerServiceId,
			$dequeuerArgument->__toString()
		);
	}

	public function testConsumerConfiguration(): void
	{
		$container = self::createContainer();
		$container->addCompilerPass(new ConsumerHandlerCompilerPass());
		$container->registerExtension(new RabbitMqConsumerHandlerExtension());

		$container->loadFromExtension('rabbit_mq_consumer_handler', [
			'consumers' => [
				'custom-configuration' => [
					'stop_consumer_sleep_seconds' => 3,
					'logger' => [
						'service_id' => 'my_custom_logger',
					],
					'entity_manager' => [
						'service_id' => 'my_custom_entity_manager',
						'clear_em_before_message' => false,
					],
				],
			],
		]);

		$this->registerConsumer($container, 'old_sound_rabbit_mq.default_configuration_consumer');
		$this->registerConsumer($container, 'old_sound_rabbit_mq.custom_configuration_consumer');

		$container->compile();

		$defaultConfigurationId = 'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration';
		$customConfigurationId = 'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration';

		self::assertContainerHasService($container, $defaultConfigurationId);
		self::assertContainerServiceIsOfType($container, $defaultConfigurationId, ConsumerHandler::class);

		self::assertContainerHasService($container, $customConfigurationId);
		self::assertContainerServiceIsOfType($container, $customConfigurationId, ConsumerHandler::class);

		$defaultConfigurationDefinition = $container->findDefinition($defaultConfigurationId);
		$customConfigurationDefinition = $container->findDefinition($customConfigurationId);

		$stopConsumerSleepSeconds = $defaultConfigurationDefinition->getArgument('$stopConsumerSleepSeconds');
		Assert::assertInstanceOf(Parameter::class, $stopConsumerSleepSeconds);
		Assert::assertSame(
			'vasek_purchart.rabbit_mq_consumer_handler.stop_consumer_sleep_seconds',
			$stopConsumerSleepSeconds->__toString()
		);

		$stopConsumerSleepSeconds = $customConfigurationDefinition->getArgument('$stopConsumerSleepSeconds');
		Assert::assertSame(
			3,
			$stopConsumerSleepSeconds
		);

		$logger = $defaultConfigurationDefinition->getArgument('$logger');
		Assert::assertInstanceOf(Reference::class, $logger);
		Assert::assertSame(
			RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
			$logger->__toString()
		);

		$logger = $customConfigurationDefinition->getArgument('$logger');
		Assert::assertInstanceOf(Reference::class, $logger);
		Assert::assertSame(
			'my_custom_logger',
			$logger->__toString()
		);

		$entityManager = $defaultConfigurationDefinition->getArgument('$entityManager');
		Assert::assertInstanceOf(Reference::class, $entityManager);
		Assert::assertSame(
			RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
			$entityManager->__toString()
		);

		$entityManager = $customConfigurationDefinition->getArgument('$entityManager');
		Assert::assertInstanceOf(Reference::class, $entityManager);
		Assert::assertSame(
			'my_custom_entity_manager',
			$entityManager->__toString()
		);

		$clearEntityManager = $defaultConfigurationDefinition->getArgument('$clearEntityManager');
		Assert::assertInstanceOf(Parameter::class, $clearEntityManager);
		Assert::assertSame(
			'vasek_purchart.rabbit_mq_consumer_handler.entity_manager.clear',
			$clearEntityManager->__toString()
		);

		$clearEntityManager = $customConfigurationDefinition->getArgument('$clearEntityManager');
		Assert::assertSame(
			false,
			$clearEntityManager
		);
	}

	public function testDetectUnusedConsumerConfiguration(): void
	{
		$container = self::createContainer();
		$container->addCompilerPass(new ConsumerHandlerCompilerPass());
		$container->registerExtension(new RabbitMqConsumerHandlerExtension());

		$container->loadFromExtension('rabbit_mq_consumer_handler', [
			'consumers' => [
				'my-consumer-xxx' => [
					'stop_consumer_sleep_seconds' => 3,
				],
			],
		]);

		$this->registerConsumer($container, 'old_sound_rabbit_mq.my_consumer_consumer');

		try {
			$container->compile();

			Assert::fail('Exception expected');

		} catch (\VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection\UnusedConsumerConfigurationException $e) {
			Assert::assertContains('my_consumer_xxx', $e->getConsumerNames());
		}
	}

	private function registerConsumer(ContainerBuilder $container, string $consumerServiceId): void
	{
		$consumerDefinition = new Definition(Consumer::class);
		$consumerDefinition->addTag(ConsumerHandlerCompilerPass::RABBIT_MQ_EXTENSION_CONSUMER_TAG);
		$container->setDefinition($consumerServiceId, $consumerDefinition);
	}

	private static function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		return $container;
	}

	private static function assertContainerHasService(
		ContainerBuilder $container,
		string $serviceId
	): void
	{
		Assert::assertTrue(
			$container->has($serviceId),
			sprintf('Expecting the container to have service `%s`.', $serviceId)
		);
	}

	private static function assertContainerServiceIsOfType(
		ContainerBuilder $container,
		string $serviceId,
		string $expectedClassString
	): void
	{
		$serviceDefinition = $container->findDefinition($serviceId);

		Assert::assertSame(
			$expectedClassString,
			$container->getParameterBag()->resolveValue($serviceDefinition->getClass()),
			sprintf('Expecting the service `%s` to be of type `%s`.', $serviceId, $expectedClassString)
		);
	}

}
