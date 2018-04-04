<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration extends \Consistence\ObjectPrototype implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	/** @link http://supervisord.org/configuration.html#program-x-section-settings startsecs section should be same default */
	public const DEFAULT_STOP_CONSUMER_SLEEP_SECONDS = 1;

	public const PARAMETER_STOP_CONSUMER_SLEEP_SECONDS = 'stop_consumer_sleep_seconds';

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
				->end()
			->end();

		return $treeBuilder;
	}

}
