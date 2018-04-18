<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler;

class ConsumerHandlerCompilerPassTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase
{

	protected function registerCompilerPass(ContainerBuilder $container)
	{
		$container->addCompilerPass(new ConsumerHandlerCompilerPass());
	}

	protected function setUp()
	{
		parent::setUp();

		$this->container->registerExtension(new RabbitMqConsumerHandlerExtension());
		$this->container->loadFromExtension('rabbit_mq_consumer_handler');
	}

	public function testWrapProducersWithDatabaseTransactionProducer(): void
	{
		$consumerServiceId = 'old_sound_rabbit_mq.my_consumer_name_consumer';
		$this->registerConsumer($consumerServiceId);

		$this->compile();

		$this->assertContainerBuilderHasService(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.my_consumer_name',
			ConsumerHandler::class
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.my_consumer_name',
			'$dequeuer'
		);
		$dequeuerArgument = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.my_consumer_name'
		)->getArgument('$dequeuer');
		$this->assertInstanceOf(Reference::class, $dequeuerArgument);
		$this->assertSame(
			$consumerServiceId,
			$dequeuerArgument->__toString()
		);
	}

	public function testConsumerConfiguration(): void
	{
		$this->container->loadFromExtension('rabbit_mq_consumer_handler', [
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

		$this->registerConsumer('old_sound_rabbit_mq.default_configuration_consumer');
		$this->registerConsumer('old_sound_rabbit_mq.custom_configuration_consumer');

		$this->compile();

		$this->assertContainerBuilderHasService(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration',
			ConsumerHandler::class
		);
		$this->assertContainerBuilderHasService(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration',
			ConsumerHandler::class
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration',
			'$stopConsumerSleepSeconds'
		);
		$stopConsumerSleepSeconds = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration'
		)->getArgument('$stopConsumerSleepSeconds');
		$this->assertInstanceOf(Parameter::class, $stopConsumerSleepSeconds);
		$this->assertSame(
			RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			$stopConsumerSleepSeconds->__toString()
		);
		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration',
			'$stopConsumerSleepSeconds'
		);
		$stopConsumerSleepSeconds = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration'
		)->getArgument('$stopConsumerSleepSeconds');
		$this->assertSame(
			3,
			$stopConsumerSleepSeconds
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration',
			'$logger'
		);
		$logger = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration'
		)->getArgument('$logger');
		$this->assertInstanceOf(Reference::class, $logger);
		$this->assertSame(
			RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER,
			$logger->__toString()
		);
		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration',
			'$logger'
		);
		$logger = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration'
		)->getArgument('$logger');
		$this->assertInstanceOf(Reference::class, $logger);
		$this->assertSame(
			'my_custom_logger',
			$logger->__toString()
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration',
			'$entityManager'
		);
		$entityManager = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration'
		)->getArgument('$entityManager');
		$this->assertInstanceOf(Reference::class, $entityManager);
		$this->assertSame(
			RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER,
			$entityManager->__toString()
		);
		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration',
			'$entityManager'
		);
		$entityManager = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration'
		)->getArgument('$entityManager');
		$this->assertInstanceOf(Reference::class, $entityManager);
		$this->assertSame(
			'my_custom_entity_manager',
			$entityManager->__toString()
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration',
			'$clearEntityManager'
		);
		$clearEntityManager = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration'
		)->getArgument('$clearEntityManager');
		$this->assertInstanceOf(Parameter::class, $clearEntityManager);
		$this->assertSame(
			RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR,
			$clearEntityManager->__toString()
		);
		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration',
			'$clearEntityManager'
		);
		$clearEntityManager = $this->container->findDefinition(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.custom_configuration'
		)->getArgument('$clearEntityManager');
		$this->assertSame(
			false,
			$clearEntityManager
		);
	}

	public function testDetectUnusedConsumerConfiguration(): void
	{
		$this->container->loadFromExtension('rabbit_mq_consumer_handler', [
			'consumers' => [
				'my-consumer-xxx' => [
					'stop_consumer_sleep_seconds' => 3,
				],
			],
		]);

		$this->registerConsumer('old_sound_rabbit_mq.my_consumer_consumer');

		try {
			$this->compile();

			$this->fail('Exception expected, should not be reached');

		} catch (\VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection\UnusedConsumerConfigurationException $e) {
			$this->assertContains('my_consumer_xxx', $e->getConsumerNames());
		}
	}

	private function registerConsumer(string $consumerServiceId): void
	{
		$consumerDefinition = new Definition(Consumer::class);
		$consumerDefinition->addTag(ConsumerHandlerCompilerPass::RABBIT_MQ_EXTENSION_CONSUMER_TAG);
		$this->setDefinition($consumerServiceId, $consumerDefinition);
	}

}
