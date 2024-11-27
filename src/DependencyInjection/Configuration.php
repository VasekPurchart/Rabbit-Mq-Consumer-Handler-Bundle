<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
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
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root($this->rootNode);

		foreach ($this->createConsumerPrototype(true)->getChildNodeDefinitions() as $consumerChildNode) {
			$rootNode->children()->append($consumerChildNode);
		}

		$rootNode->children()->append($this->createConsumersNode(self::SECTION_CONSUMERS));

		return $treeBuilder;
	}

	private function createConsumerPrototype(bool $addDefaults): ArrayNodeDefinition
	{
		$node = new ArrayNodeDefinition(null);
		$node->children()->append($this->createStopConsumerSleepSecondsNode(self::PARAMETER_STOP_CONSUMER_SLEEP_SECONDS, $addDefaults));
		$node->children()->append($this->createLoggerNode(self::SECTION_LOGGER, $addDefaults));
		$node->children()->append($this->createEntityManagerNode(self::SECTION_ENTITY_MANAGER, $addDefaults));

		return $node;
	}

	private function createConsumersNode(string $nodeName): ArrayNodeDefinition
	{
		$node = new ArrayNodeDefinition($nodeName);
		$node->useAttributeAsKey('name');
		$this->setPrototype($node, $this->createConsumerPrototype(false));

		return $node;
	}

	private function createStopConsumerSleepSecondsNode(string $nodeName, bool $addDefaults): IntegerNodeDefinition
	{
		$node = new IntegerNodeDefinition($nodeName);
		$node->info('Generally how long is needed for the program to run, to be considered started, achieved by sleeping when stopping prematurely');
		$node->min(0);
		$node->treatFalseLike(0);

		if ($addDefaults) {
			$node->defaultValue(self::DEFAULT_STOP_CONSUMER_SLEEP_SECONDS);
		}

		return $node;
	}

	private function createLoggerNode(string $nodeName, bool $addDefaults): ArrayNodeDefinition
	{
		$node = new ArrayNodeDefinition($nodeName);
		$node->children()->append($this->createLoggerServiceIdNode(self::PARAMETER_LOGGER_SERVICE_ID, $addDefaults));

		if ($addDefaults) {
			$node->addDefaultsIfNotSet();
		}

		return $node;
	}

	private function createLoggerServiceIdNode(string $nodeName, bool $addDefaults): ScalarNodeDefinition
	{
		$node = new ScalarNodeDefinition($nodeName);
		$node->info('Logger service ID, which instance will be used to log messages and exceptions');

		if ($addDefaults) {
			$node->defaultValue(self::DEFAULT_LOGGER_SERVICE_ID);
		}

		return $node;
	}

	private function createEntityManagerNode(string $nodeName, bool $addDefaults): ArrayNodeDefinition
	{
		$node = new ArrayNodeDefinition($nodeName);
		$node->children()->append($this->createEntityManagerServiceIdNode(self::PARAMETER_ENTITY_MANAGER_SERVICE_ID, $addDefaults));
		$node->children()->append($this->createEntityManagerClearNode(self::PARAMETER_ENTITY_MANAGER_CLEAR, $addDefaults));

		if ($addDefaults) {
			$node->addDefaultsIfNotSet();
		}

		return $node;
	}

	private function createEntityManagerServiceIdNode(string $nodeName, bool $addDefaults): ScalarNodeDefinition
	{
		$node = new ScalarNodeDefinition($nodeName);
		$node->info('EntityManager service ID, which instance is used within the consumer');

		if ($addDefaults) {
			$node->defaultValue(self::DEFAULT_ENTITY_MANAGER_SERVICE_ID);
		}

		return $node;
	}

	private function createEntityManagerClearNode(string $nodeName, bool $addDefaults): BooleanNodeDefinition
	{
		$node = new BooleanNodeDefinition($nodeName);
		$node->info('Clear EntityManager before processing message');

		if ($addDefaults) {
			$node->defaultValue(true);
		}

		return $node;
	}

	private function setPrototype(ArrayNodeDefinition $node, NodeDefinition $prototype): void
	{
		$arrayNodeDefinition = new class (null) extends \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition {

			public function setPrototype(ArrayNodeDefinition $node, NodeDefinition $prototype): void
			{
				$node->prototype = $prototype;
			}

		};

		$arrayNodeDefinition->setPrototype($node, $prototype);
	}

}
