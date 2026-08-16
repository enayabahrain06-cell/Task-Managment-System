<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Throwable;

class MqttService
{
    private static ?MqttClient $client = null;

    private static function connect(): ?MqttClient
    {
        if (self::$client) {
            return self::$client;
        }

        try {
            $settings = (new ConnectionSettings)
                ->setUsername(config('mqtt.username'))
                ->setPassword(config('mqtt.password'))
                ->setConnectTimeout(3)
                ->setUseTls(false);

            $client = new MqttClient(
                config('mqtt.host'),
                config('mqtt.port'),
                'laravel-publisher-' . substr(uniqid(), -6)
            );

            $client->connect($settings, true);
            self::$client = $client;
            return $client;
        } catch (Throwable $e) {
            logger()->error('MQTT connect failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function publish(string $topic, array $payload, int $qos = 0): void
    {
        $client = self::connect();
        if (! $client) {
            return;
        }

        try {
            $client->publish($topic, json_encode($payload), $qos);
        } catch (Throwable $e) {
            logger()->error('MQTT publish failed: ' . $e->getMessage());
            self::$client = null;
        }
    }

    // Notify a specific user of a new notification
    public static function notifyUser(int $userId, array $data): void
    {
        self::publish("tm/user/{$userId}/notifications", $data);
    }

    // Broadcast a presence update
    public static function presenceUpdate(int $userId, string $status): void
    {
        self::publish("tm/presence/{$userId}", [
            'user_id' => $userId,
            'status'  => $status,
        ]);
    }

    // Push a task status change to everyone subscribed to that task
    public static function taskUpdate(int $taskId, array $data): void
    {
        self::publish("tm/tasks/{$taskId}/status", $data);
    }
}
