<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Consistence\RegExp\RegExp;
use Consistence\Type\ArrayType\ArrayType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler;
use VasekPurchart\RabbitMqConsumerHandlerBundle\Sleeper\Sleeper;

class ConsumerHandlerCompilerPass extends \Consistence\ObjectPrototype implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{

	public const RABBIT_MQ_EXTENSION_CONSUMER_TAG = 'old_sound_rabbit_mq.consumer';
	private const RABBIT_MQ_EXTENSION_CONSUMER_ID_PATTERN = '~^old_sound_rabbit_mq.(?P<' . self::RABBIT_MQ_EXTENSION_CONSUMER_ID_PATTERN_CONSUMER_NAME . '>.+)_consumer$~';
	private const RABBIT_MQ_EXTENSION_CONSUMER_ID_PATTERN_CONSUMER_NAME = 'consumerName';

	private const CONSUMER_HANDLER_SERVICE_ID_PATTERN = 'vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.%s';

	public function process(ContainerBuilder $container)
	{
		foreach ($container->findTaggedServiceIds(self::RABBIT_MQ_EXTENSION_CONSUMER_TAG) as $consumerServiceId => $attributes) {
			$consumerName = $this->getConsumerName($consumerServiceId);
			$consumerHandlerServiceId = $this->getConsumerHandlerServiceId($consumerName);
			$consumerHandlerDefinition = $this->getConsumerHandlerDefinition($consumerServiceId);
			$container->setDefinition($consumerHandlerServiceId, $consumerHandlerDefinition);
		}
	}

	private function getConsumerHandlerDefinition(string $consumerServiceId): Definition
	{
		$consumerHandlerServiceDefinition = new Definition(ConsumerHandler::class, [
			'$stopConsumerSleepSeconds' => new Parameter(RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS),
			'$dequeuer' => new Reference($consumerServiceId),
			'$logger' => new Reference(RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER),
			'$entityManager' => new Reference(RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER),
			'$sleeper' => new Reference(Sleeper::class),
		]);
		$consumerHandlerServiceDefinition->setPublic(true);

		return $consumerHandlerServiceDefinition;
	}

	private function getConsumerName(string $consumerServiceId): string
	{
		$matches = RegExp::match($consumerServiceId, self::RABBIT_MQ_EXTENSION_CONSUMER_ID_PATTERN);
		return ArrayType::getValue($matches, self::RABBIT_MQ_EXTENSION_CONSUMER_ID_PATTERN_CONSUMER_NAME);
	}

	private function getConsumerHandlerServiceId(string $consumerName): string
	{
		return sprintf(self::CONSUMER_HANDLER_SERVICE_ID_PATTERN, $consumerName);
	}

}
