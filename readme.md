RabbitMQ Consumer Handler Bundle
================================

**Handle messages in RabbitMQ consumers in a safe and effective way**

> **Note:** This bundle expects you are using [RabbitMqBundle](https://github.com/php-amqplib/RabbitMqBundle/)

Message queue consumers require usually long running processes, which should process many different messages, before they are terminated. In an ideal scenario, they will be running indefinitely. But since we are not living in an ideal world, errors will inevitably occur. These can be typically:

* expected application exceptions,
* unexpected application exceptions,
* other exceptions and errors like connection interruptions etc.,
* memory leaks and other unexpected behavior.

The purpose of this bundle is to encapsulate handling of these states, automate the ones, which can be automated and provide comfortable ways to handle the remaining ones.

This bundle can automatically handle:

* stopping consumer on uncaught exceptions (so that it can be safely restarted)
* logging uncaught exceptions,
* clearing Doctrine EntityManager before processing a message,
* stopping consumer when Doctrine EntityManager is closed.

Usage
-----

In order to receive all the benefits of automated handling you need only to run the message processing through the `ConsumerHandler`, so standard consumer could look something like this:

```php
<?php

declare(strict_types = 1);

namespace Example;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler;

class ExampleConsumer implements \OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface
{

	/** @var \VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler */
	private $consumerHandler;

	public function __construct(
		ConsumerHandler $consumerHandler
	)
	{
		$this->consumerHandler = $consumerHandler;
	}

	public function execute(AMQPMessage $msg): int
	{
		return $this->consumerHandler->processMessage(function () use ($msg): int {
			$data = $msg->body;

			// do your magic with $data, basically anything you would put in the consumer
			// without this bundle, apart from the stuff this bundle handles automatically 

			return ConsumerInterface::MSG_ACK;
		});
	}

}
```

This bundle will create `ConsumerHandler` instance for every one of your consumers, because it needs to access specific instance of `OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface`, which it needs to control consuming messages from the configured queue.

Assuming your consumer is called `example` in the `old_sound_rabbit_mq` configuration, `vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.example` service will be prepared, so you can just pass this instance to your consumer:

```yaml
old_sound_rabbit_mq:
    consumers:
        example:
            callback: 'Example\ExampleConsumer'
            # ...

services:
    Example\ExampleConsumer:
        arguments:
            $consumerHandler: '@vasek_purchart.rabbit_mq_consumer_handler.consumer_handler.id.example'
```

Restarting consumers
--------------------

With consumers you will generaly need something to keep them running in case they fail. This is achieved usually with some kind of daemon (for example [`supervisord`](http://supervisord.org/introduction.html)), which will run the consumers for you, watch them if they are running and start them again if not (based on your configuration).

In order for the tool to be able to restart the consumer reliably, it first needs to be able to tell, whether the consumer has even *started*, so that it would just not get stuck in booting cycle. The usual configuration is to not start the program again after several retries when it cannot start properly. In `suprvisord` the program is considered to be started after [`startsecs`](http://supervisord.org/configuration.html#program-x-section-settings) number of seconds.

This bundle is making sure, that always, when the consumer is shutting down due to an uncaught exception or error, the consumer has been running at least this amount of time by sleeping for `stop_consumer_sleep_seconds`, which should be configured to the same value as `startsecs`. This means that the behavior can be handled correctly by `supervisord` - restarting the consumer when it fails *due to processing a message*, but not restarting it indefinitely if the application cannot even start.

Handling exceptions
-------------------

In the example below, there are some examples of the most common situations you will encounter:

```php
<?php

declare(strict_types = 1);

namespace Example;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LogLevel;
use VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler;

class ExampleConsumer implements \OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface
{

	/** @var \VasekPurchart\RabbitMqConsumerHandlerBundle\ConsumerHandler\ConsumerHandler */
	private $consumerHandler;

	public function __construct(
		ConsumerHandler $consumerHandler
	)
	{
		$this->consumerHandler = $consumerHandler;
	}

	public function execute(AMQPMessage $msg): int
	{
		return $this->consumerHandler->processMessage(
			function (ConsumerHandler $consumerHandler) use ($msg): int {
				// the correct ConsumerHandler is passed into the callback,
				// so you can use it for custom logging etc

				try {
					$data = $msg->body;

					// ... 

					return ConsumerInterface::MSG_ACK;

				} catch (\ResourceNotFound $e) {
					// might be cause by the asynchronous nature of message queues
					// - a resource might not yet be accessible
					// or it might have been deleted already

					// basically you can choose if this is OK (ACK or REJECT,
					// depending on semantics), or if you want to try later
					// again (REJECT_REQUEUE)

					return ConsumerInterface::REJECT;

				} catch (\UnexpectedBusinessLogicException $e) {
					// situation which you are not sure, why it happens,
					// but you need to investigate further (perhaps with more logging)
					// and perhaps throw away these messages, because
					// it might clutter the queue

					$consumerHandler->log(LogLevel::ERROR, 'My custom message');
					$consumerHandler->logException($e);

					return ConsumerInterface::MSG_REJECT;

				} catch (\UnexpectedException $e) {
					// situation where you might need to decide further
					// what to do in the catch block

					if ($e->getCode() === 123) {
						return ConsumerInterface::MSG_REJECT;
					}

					throw $e; // handle with default "catchall"

				} catch (\ExpectedBusinessLogicException $e) {
					// situation where you can solve it in a different way

					// call a service

					return ConsumerInterface::MSG_ACK;

				} catch (\ConnectionTimeoutCustomException $e) {
					// situation where the application would need a restart
					// to reinitialize for example a connection

					// this would happen also by default in the "catchall",
					// but you might want to handle a specific case separately,
					// for example not to log these exceptions

					// this will stop the consumer
					$consumerHandler->stopConsumer('Connection timeout');

					return ConsumerInterface::MSG_REJECT_REQUEUE;

				}
			}
		);
	}

}
```

Configuration
-------------

Configuration structure with listed default values:

```yaml
# config/packages/rabbit_mq_consumer_handler.yml
rabbit_mq_consumer_handler:
    # Generally how long is needed for the program to run, to be considered started,
    # achieved by sleeping when stopping prematurely
    stop_consumer_sleep_seconds: 1
    logger:
        # Logger service ID, which instance will be used to log messages and exceptions
        service_id: 'logger'
    entity_manager:
        # EntityManager service ID, which instance is used withing the consumer
        service_id: 'doctrine.orm.default_entity_manager'
        # Clear EntityManager before processing message
        clear_em_before_message: true
    consumers:
        # configuration specifically for this consumer
        <my_consumer_name>:
            # identical structure as the options above
```

Installation
------------

Install package [`vasek-purchart/rabbit-mq-consumer-handler-bundle`](https://packagist.org/packages/vasek-purchart/rabbit-mq-consumer-handler-bundle) with [Composer](https://getcomposer.org/):

```bash
composer require vasek-purchart/rabbit-mq-consumer-handler-bundle
```

Register the bundle in your application kernel:
```php
// config/bundles.php
return [
	// ...
	VasekPurchart\RabbitMqConsumerHandlerBundle\RabbitMqConsumerHandlerBundle::class => ['all' => true],
];
```
