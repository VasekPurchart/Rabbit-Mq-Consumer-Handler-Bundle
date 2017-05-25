<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqConsumerHandlerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use VasekPurchart\RabbitMqConsumerHandlerBundle\DependencyInjection\ConsumerHandlerCompilerPass;

class RabbitMqConsumerHandlerBundle extends \Symfony\Component\HttpKernel\Bundle\Bundle
{

	use \Consistence\Type\ObjectMixinTrait;

	/**
	 * @codeCoverageIgnore does not define any logic
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	public function build(ContainerBuilder $container): void
	{
		parent::build($container);

		$container->addCompilerPass(new ConsumerHandlerCompilerPass());
	}

}
