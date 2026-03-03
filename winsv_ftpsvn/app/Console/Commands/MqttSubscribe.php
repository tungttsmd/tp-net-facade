<?php

namespace App\Console\Commands;

use App\Events\MessageSent;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:start';
    protected $description = 'Subscribe MQTT and forward to Reverb';
    private $signalWildTopic = 'winsv_wtpsvn/+/health';

    public function handle()
    {
        $topic = $this->signalWildTopic;

        $client = new MqttClient(
            config('mqtt.host'),
            config('mqtt.port'),
            config('mqtt.client_id')
        );

        $settings = (new ConnectionSettings)
            ->setKeepAliveInterval(config('mqtt.keep_alive'));

        if (config('mqtt.username')) {
            $settings = $settings->setUsername(config('mqtt.username'))
                                ->setPassword(config('mqtt.password'));
            $this->info('[INFO] MQTT will be auth with ' . config('mqtt.username') . ' and password');
        }

        $this->info('[INFO] Username: ' . var_export(config('mqtt.username'), true));
        $this->info('[INFO] Password set: ' . (config('mqtt.password') ? 'YES' : 'NO'));

        $client->connect($settings, true);

        $this->info('[INFO] MQTT connected');
        $this->info("[INFO] Subscribing to: {$topic}");

        $client->subscribe($topic, function ($topic, $message) {
            $this->subscribeHandler($topic, $message);
        }, 0);

        $this->info('[INFO] Listening for messages...');
        $client->loop(true);
    }
    
    private function subscribeHandler(string $topic, string $message): void
    {
        try {
            $data = $this->parseSignalMessage($topic, $message);

            if ($data === null) {
                return; // không phải signal → bỏ qua im lặng
            }

            broadcast(new MessageSent(
                [
                    'from'       => $data['from'],
                    'topic'      => $topic,
                    'type' => $data['type'],
                    'note' => $data['note'],
                    'timestamp'  => $data['timestamp'],
                ],
                'reverb.websocket.redis.signal.event',
                'reverb.websocket.redis.signal.chanel'
            ));

            $this->info(
                "[SIGNAL] {$data['from']} | {$data['type']} | {$data['note']} | {$data['timestamp']}"
            );

        } catch (\Throwable $e) {
            $this->error('[MQTT ERROR] ' . $e->getMessage());
        }
    }

    private function parseSignalMessage(string $topic, string $message): ?array
    {
        if ($message === '' || trim($message) === '') {
            return null;
        }
    
        $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
    
        if (($payload['meta']['json_response'] ?? null) !== 'heartbeat') {
            return null;
        }
    
        $meta = $payload['meta'] ?? [];
        $data = $payload['data'] ?? [];
    
        $type = $data['heartbeatType'] ?? null;
        $note = $data['heartbeatNote'] ?? null;
        $timestamp = $meta['timestamp'] ?? null;
        $from      = $meta['node_from'] ?? null;
    
        if (!$type || !$note || !$timestamp || !$from) {
            return null; // signal nhưng thiếu field → bỏ
        }

        switch ($type) {
            case 'health':
                return [
                    'from'       => $from,
                    'topic'      => $topic,
                    'type' => $type,
                    'timestamp'  => $timestamp,
                ];
            case 'sensor':
                return [
                    'from' => $from,
                    'topic' => $topic,
                    'type' => $type,
                    'note' => $note,
                    'timestamp' => $timestamp,
                ];
            default:
                break;
        }
    
        return null;
    }
    
}
