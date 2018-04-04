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
		$this->registerConsumer('old_sound_rabbit_mq.default_configuration_consumer');
		$this->registerConsumer('old_sound_rabbit_mq.custom_configuration_consumer');

		$this->compile();

		$this->assertContainerBuilderHasService(
			'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.default_configuration',
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
	}

	private function registerConsumer(string $consumerServiceId): void
	{
		$consumerDefinition = new Definition(Consumer::class);
		$consumerDefinition->addTag(ConsumerHandlerCompilerPass::RABBIT_MQ_EXTENSION_CONSUMER_TAG);
		$this->setDefinition($consumerServiceId, $consumerDefinition);
	}

}
