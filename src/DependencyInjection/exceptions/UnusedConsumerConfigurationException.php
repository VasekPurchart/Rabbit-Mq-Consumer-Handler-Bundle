<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection;

class UnusedConsumerConfigurationException extends \Consistence\PhpException
{

	/** @var string[] */
	private $consumerNames;

	/**
	 * @param string[] $consumerNames
	 * @param \Throwable|null $previous
	 */
	public function __construct(array $consumerNames, ?\Throwable $previous = null)
	{
		parent::__construct(sprintf(
			'There are unused consumer configurations: %s (probably forgotten or containing typos)',
			implode(', ', $consumerNames)
		));
		$this->consumerNames = $consumerNames;
	}

	/**
	 * @return string[]
	 */
	public function getConsumerNames(): array
	{
		return $this->consumerNames;
	}

}
