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
		$customConsumerConfigurations = $container->getParameter(RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_CUSTOM_CONSUMER_CONFIGURATIONS);

		foreach ($container->findTaggedServiceIds(self::RABBIT_MQ_EXTENSION_CONSUMER_TAG) as $consumerServiceId => $attributes) {
			$consumerName = $this->getConsumerName($consumerServiceId);
			$consumerHandlerServiceId = $this->getConsumerHandlerServiceId($consumerName);
			$customConfiguration = ArrayType::findValue($customConsumerConfigurations, $consumerName);
			$consumerHandlerDefinition = $this->getConsumerHandlerDefinition($consumerServiceId, $customConfiguration);
			$container->setDefinition($consumerHandlerServiceId, $consumerHandlerDefinition);
			if ($customConfiguration !== null) {
				unset($customConsumerConfigurations[$consumerName]);
			}
		}
		if (count($customConsumerConfigurations) > 0) {
			throw new \VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection\UnusedConsumerConfigurationException(array_keys($customConsumerConfigurations));
		}
	}

	/**
	 * @param string $consumerServiceId
	 * @param mixed[]|null $customConfiguration options for this runner from configuration
	 * @return \Symfony\Component\DependencyInjection\Definition
	 */
	private function getConsumerHandlerDefinition(string $consumerServiceId, ?array $customConfiguration): Definition
	{
		$stopConsumerSleepSeconds = $customConfiguration !== null && ArrayType::containsKey(
			$customConfiguration,
			Configuration::PARAMETER_STOP_CONSUMER_SLEEP_SECONDS
		)
			? $customConfiguration[Configuration::PARAMETER_STOP_CONSUMER_SLEEP_SECONDS]
			: new Parameter(RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS);

		$logger = $customConfiguration !== null && ArrayType::containsKey(
			$customConfiguration,
			Configuration::SECTION_LOGGER
		) && ArrayType::containsKey(
			$customConfiguration[Configuration::SECTION_LOGGER],
			Configuration::PARAMETER_LOGGER_SERVICE_ID
		)
			? new Reference($customConfiguration[Configuration::SECTION_LOGGER][Configuration::PARAMETER_LOGGER_SERVICE_ID])
			: new Reference(RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_LOGGER);

		$entityManager = $customConfiguration !== null && ArrayType::containsKey(
			$customConfiguration,
			Configuration::SECTION_ENTITY_MANAGER
		) && ArrayType::containsKey(
			$customConfiguration[Configuration::SECTION_ENTITY_MANAGER],
			Configuration::PARAMETER_ENTITY_MANAGER_SERVICE_ID
		)
			? new Reference($customConfiguration[Configuration::SECTION_ENTITY_MANAGER][Configuration::PARAMETER_ENTITY_MANAGER_SERVICE_ID])
			: new Reference(RabbitMqConsumerHandlerExtension::CONTAINER_SERVICE_ENTITY_MANAGER);

		$clearEntityManager = $customConfiguration !== null && ArrayType::containsKey(
			$customConfiguration,
			Configuration::SECTION_ENTITY_MANAGER
		) && ArrayType::containsKey(
			$customConfiguration[Configuration::SECTION_ENTITY_MANAGER],
			Configuration::PARAMETER_ENTITY_MANAGER_CLEAR
		)
			? $customConfiguration[Configuration::SECTION_ENTITY_MANAGER][Configuration::PARAMETER_ENTITY_MANAGER_CLEAR]
			: new Parameter(RabbitMqConsumerHandlerExtension::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR);

		$consumerHandlerServiceDefinition = new Definition(ConsumerHandler::class, [
			'$stopConsumerSleepSeconds' => $stopConsumerSleepSeconds,
			'$dequeuer' => new Reference($consumerServiceId),
			'$logger' => $logger,
			'$entityManager' => $entityManager,
			'$clearEntityManager' => $clearEntityManager,
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
