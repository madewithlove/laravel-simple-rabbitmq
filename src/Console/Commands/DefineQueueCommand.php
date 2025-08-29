<?php

namespace Usmonaliyev\SimpleRabbit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Usmonaliyev\SimpleRabbit\Facades\SimpleMQ;

class DefineQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amqp:define-queues {--e|exchange}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Define RabbitMQ queues and exchanges for message handling.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $queues = Config::get('simple-mq.queues');

        foreach ($queues as $name => $queue) {
            $arguments = array_filter($queue['arguments'], fn ($argument) => ! is_null($argument));

            SimpleMQ::connection($queue['connection'])
                ->getDefinition()
                ->setArguments($arguments)
                ->defineQueue($name, $queue['exchange_bind'] ?? []);
        }

        $this->info('Queues are defined.');

        $exchange = $this->option('exchange');
        if ($exchange) {
            $exchanges = Config::get('simple-mq.exchanges');

            foreach ($exchanges as $name => $exchange) {
                $arguments = array_filter($exchange['arguments'], fn ($argument) => ! is_null($argument));

                SimpleMQ::connection($exchange['connection'])
                    ->getDefinition()
                    ->setArguments($arguments)
                    ->defineExchange($name, $exchange['type'] ?? 'direct', $exchange['type_configuration'] ?? []);
            }
        }

        $this->info('Exchanges are defined.');
    }
}
