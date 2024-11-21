<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class RabbitMqConsumerHandlerExtension extends \Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension
{

	use \Consistence\Type\ObjectMixinTrait;

	public const CONTAINER_PARAMETER_CUSTOM_CONSUMER_CONFIGURATIONS = 'vasek_purchart.rabbit_mq_consumer_handler.custom_consumer_configurations';
	public const CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR = 'vasek_purchart.rabbit_mq_consumer_handler.entity_manager.clear';
	public const CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS = 'vasek_purchart.rabbit_mq_consumer_handler.stop_consumer_sleep_seconds';

	public const CONTAINER_SERVICE_ENTITY_MANAGER = 'vasek_purchart.rabbit_mq_consumer_handler.entity_manager';
	public const CONTAINER_SERVICE_LOGGER = 'vasek_purchart.rabbit_mq_consumer_handler.logger';

	/**
	 * @param mixed[] $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
	{
		$yamlFileLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));

		$container->setParameter(
			self::CONTAINER_PARAMETER_STOP_CONSUMER_SLEEP_SECONDS,
			$mergedConfig[Configuration::PARAMETER_STOP_CONSUMER_SLEEP_SECONDS]
		);

		$this->loadLogger($mergedConfig, $container);
		$this->loadEntityManager($mergedConfig, $container);
		$this->loadConsumerSpecificConfiguration($mergedConfig, $container);

		$yamlFileLoader->load('services.yaml');
	}

	/**
	 * @param mixed[] $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	private function loadLogger(array $mergedConfig, ContainerBuilder $container): void
	{
		$container->setAlias(
			self::CONTAINER_SERVICE_LOGGER,
			$mergedConfig[Configuration::SECTION_LOGGER][Configuration::PARAMETER_LOGGER_SERVICE_ID]
		);
	}

	/**
	 * @param mixed[] $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	private function loadEntityManager(array $mergedConfig, ContainerBuilder $container): void
	{
		$container->setAlias(
			self::CONTAINER_SERVICE_ENTITY_MANAGER,
			$mergedConfig[Configuration::SECTION_ENTITY_MANAGER][Configuration::PARAMETER_ENTITY_MANAGER_SERVICE_ID]
		);
		$container->setParameter(
			self::CONTAINER_PARAMETER_ENTITY_MANAGER_CLEAR,
			$mergedConfig[Configuration::SECTION_ENTITY_MANAGER][Configuration::PARAMETER_ENTITY_MANAGER_CLEAR]
		);
	}

	/**
	 * @param mixed[] $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	private function loadConsumerSpecificConfiguration(array $mergedConfig, ContainerBuilder $container): void
	{
		$container->setParameter(
			self::CONTAINER_PARAMETER_CUSTOM_CONSUMER_CONFIGURATIONS,
			$mergedConfig[Configuration::SECTION_CONSUMERS]
		);
	}

	/**
	 * @param mixed[] $config
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @return \VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection\Configuration
	 */
	public function getConfiguration(array $config, ContainerBuilder $container): Configuration
	{
		return new Configuration(
			$this->getAlias()
		);
	}

}
