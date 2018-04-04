<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

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
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root($this->rootNode);

		$rootNode
			->children()
				->integerNode(self::PARAMETER_STOP_CONSUMER_SLEEP_SECONDS)
					->info('Generally how long is needed for the program to run, to be considered started, achieved by sleeping when stopping prematurely')
					->min(0)
					->defaultValue(self::DEFAULT_STOP_CONSUMER_SLEEP_SECONDS)
					->treatFalseLike(0)
					->end()
				->arrayNode(self::SECTION_LOGGER)
					->addDefaultsIfNotSet()
					->children()
						->scalarNode(self::PARAMETER_LOGGER_SERVICE_ID)
							->info('Logger service ID, which instance will be used to log messages and exceptions')
							->defaultValue(self::DEFAULT_LOGGER_SERVICE_ID)
							->end()
						->end()
					->end()
				->arrayNode(self::SECTION_ENTITY_MANAGER)
					->addDefaultsIfNotSet()
					->children()
						->scalarNode(self::PARAMETER_ENTITY_MANAGER_SERVICE_ID)
							->info('EntityManager service ID, which instance is used within the consumer')
							->defaultValue(self::DEFAULT_ENTITY_MANAGER_SERVICE_ID)
							->end()
						->booleanNode(self::PARAMETER_ENTITY_MANAGER_CLEAR)
							->info('Clear EntityManager before processing message')
							->defaultValue(true)
							->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}

}
