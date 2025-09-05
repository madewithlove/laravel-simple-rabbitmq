<?php

namespace Usmonaliyev\SimpleRabbit;

use Exception;
use Illuminate\Support\Facades\App;
use PhpAmqpLib\Message\AMQPMessage;
use Usmonaliyev\SimpleRabbit\MQ\Message;

class ActionMQ
{
    /**
     * Stores the registered events and their handlers.
     *
     * @var array<string, array|callable|class-string>
     */
    private array $eventHandlers = [];

    /**
     * Register a new action
     * @param array|callable|class-string $callback
     */
    public function register(string $eventName, array|callable|string $callback): void
    {
        $this->eventHandlers[$eventName] = $callback;
    }

    /**
     * Getter of actions property
     *
     * @return array<string, array|callable|class-string>
     */
    public function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    /**
     * Loading actions from route/amqp-handlers.php
     */
    public function load(): void
    {
        $callback = fn () => include_once base_path('routes/amqp-handlers.php');

        $callback();
    }

    /**
     * Main consumer
     */
    public function consume(AMQPMessage $amqpMessage): mixed
    {
        $message = new Message($amqpMessage);

        // If there is no handler which match to message, message is deleted
        if (! isset($this->eventHandlers[$message->getEventName()])) {
            $message->ack();

            return null;
        }

        return $this->dispatch($message);
    }

    /**
     * Dispatcher to execute handler
     */
    protected function dispatch(Message $message): mixed
    {
        $handler = $this->eventHandlers[$message->getEventName()];

        try {
            if (is_string($handler)) {
                $handler = App::make($handler);
            }

            if (is_callable($handler)) {
                return call_user_func_array($handler, [$message]);
            }

            [$class, $method] = $handler;
            $instance = App::make($class);

            return $instance->{$method}($message);

        } catch (Exception $e) {
            $error = sprintf('ERROR [%s] %s: %s'.PHP_EOL, gmdate('Y-m-d H:i:s'), get_class($e), $e->getMessage());
            echo $error;

            return null;
        }
    }
}
