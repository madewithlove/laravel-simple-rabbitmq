<?php

namespace Usmonaliyev\SimpleRabbit\MQ;

use Illuminate\Contracts\Support\Arrayable;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Message implements Arrayable
{
    /**
     * Instance of AMQPMessage
     */
    private AMQPMessage $message;

    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;

        $this->separator();
    }

    /**
     * Content-Type of AMQPMessage
     */
    protected string $contentType = 'application/json';

    /**
     * Content-Encoding of AMQPMessage
     */
    protected string $contentEncoding = 'utf-8';

    /**
     * Application headers of AMQPMessage
     */
    protected array $applicationHeaders;

    /**
     * Message priority default is 0
     */
    protected int $priority;

    /**
     * Timestamp of AMQPMessage
     *
     * @var int
     */
    protected mixed $timestamp;

    /**
     * Handler name which accepted from AMQPMessage
     */
    protected string $eventName;

    /**
     * Separator message details from AMQPMessage
     */
    protected function separator(): void
    {
        $this->body = json_decode($this->message->getBody(), true);

        /**
         * @var array<string, AMQPTable|int|string> $properties
         */
        $properties = $this->message->get_properties();

        $headers = $properties['application_headers']->getNativeData();

        $this->eventName = $headers['event_name'] ?? '';
        $this->applicationHeaders = $headers;

        $this->contentType = $properties['content_type'] ?? 'application/json';
        $this->contentEncoding = $properties['content_encoding'] ?? 'utf-8';
        $this->priority = $properties['priority'] ?? 0;
        $this->timestamp = $properties['timestamp'] ?? 0;
    }

    /**
     * Body of AMQPMessage as array
     */
    protected ?array $body;

    /**
     * Acknowledge one or more messages.
     */
    public function ack(): void
    {
        $this->message->ack();
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'headers' => $this->applicationHeaders,
            'body' => $this->body,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Returns value of application_headers in AMQPMessage
     */
    public function getApplicationHeaders(): array
    {
        return $this->applicationHeaders;
    }

    /**
     * Returns handler name of AMQPMessage
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Returns body of AMQPMessage as array
     */
    public function getBody(): ?array
    {
        return $this->body;
    }

    /**
     * Dump this class
     */
    public function dd(): void
    {
        dump($this);
    }

    /**
     * Get body as json
     */
    public function bodyAsJson(): bool|string
    {
        return json_encode($this->body);
    }

    /**
     * Print message to log
     */
    public function log($ack = true): void
    {
        $body = $this->bodyAsJson();

        $ack && $this->ack();

        echo sprintf('%s | %s', date('Y-m-d H:i:s'), $body).PHP_EOL;
    }
}
