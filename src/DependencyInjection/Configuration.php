<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration extends \Consistence\ObjectPrototype implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	/** @link http://supervisord.org/configuration.html#program-x-section-settings startsecs section should be same default */
	public const DEFAULT_STOP_CONSUMER_SLEEP_SECONDS = 1;
	public const DEFAULT_LOGGER_SERVICE_ID = 'logger';
	public const DEFAULT_ENTITY_MANAGER_SERVICE_ID = 'doctrine.orm.default_entity_manager';

	public const PARAMETER_ENTITY_MANAGER_SERVICE_ID = 'service_id';
	public const PARAMETER_ENTITY_MANAGER_CLEAR = 'clear_em_before_message';
	public const PARAMETER_LOGGER_SERVICE_ID = 'service_id';
	public const PARAMETER_STOP_CONSUMER_SLEEP_SECONDS = 'stop_consumer_sleep_seconds';

	public const SECTION_CONSUMERS = 'consumers';
	public const SECTION_ENTITY_MANAGER = 'entity_manager';
	public const SECTION_LOGGER = 'logger';

	/** @var string */
	private $rootNode;

	public function __construct(
		string $rootNode
	)
	{
		$this->rootNode = $rootNode;
	}

	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder($this->rootNode);
		if (method_exists($treeBuilder, 'getRootNode')) {
			$rootNode = $treeBuilder->getRootNode();
		} else {
			// BC layer for symfony/config 4.1 and older
			$rootNode = $treeBuilder->root($this->rootNode);
		}

		$this->addConsumerConfiguration($rootNode, true);

		$consumersSection = $rootNode
				->children()
				->arrayNode(self::SECTION_CONSUMERS);
		$consumersSection->useAttributeAsKey('name');
		$this->addConsumerConfiguration($consumersSection->arrayPrototype(), false);

		return $treeBuilder;
	}

	private function addConsumerConfiguration(ArrayNodeDefinition $node, bool $addDefaults): void
	{
		$consumerSleepSecondsParameter = $node
			->children()
			->integerNode(self::PARAMETER_STOP_CONSUMER_SLEEP_SECONDS);
		$consumerSleepSecondsParameter
			->info('Generally how long is needed for the program to run, to be considered started, achieved by sleeping when stopping prematurely')
			->min(0)
			->treatFalseLike(0);

		$loggerSection = $node
			->children()
			->arrayNode(self::SECTION_LOGGER);

		$loggerServiceIdParameter = $loggerSection
			->children()
			->scalarNode(self::PARAMETER_LOGGER_SERVICE_ID);
		$loggerServiceIdParameter
			->info('Logger service ID, which instance will be used to log messages and exceptions');

		$entityManagerSection = $node
			->children()
			->arrayNode(self::SECTION_ENTITY_MANAGER);

		$entityManagerServiceIdParameter = $entityManagerSection
			->children()
			->scalarNode(self::PARAMETER_ENTITY_MANAGER_SERVICE_ID);
		$entityManagerServiceIdParameter
			->info('EntityManager service ID, which instance is used within the consumer');

		$entityManagerClearParameter = $entityManagerSection
			->children()
			->booleanNode(self::PARAMETER_ENTITY_MANAGER_CLEAR);
		$entityManagerClearParameter
			->info('Clear EntityManager before processing message');

		if ($addDefaults) {
			$consumerSleepSecondsParameter->defaultValue(self::DEFAULT_STOP_CONSUMER_SLEEP_SECONDS);

			$loggerSection->addDefaultsIfNotSet();
			$loggerServiceIdParameter->defaultValue(self::DEFAULT_LOGGER_SERVICE_ID);

			$entityManagerSection->addDefaultsIfNotSet();
			$entityManagerServiceIdParameter->defaultValue(self::DEFAULT_ENTITY_MANAGER_SERVICE_ID);
			$entityManagerClearParameter->defaultValue(true);
		}
	}

}
