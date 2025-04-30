<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Exception;

class MqttManager
{
    private static $mqttClient = null;

    public static function getClient()
    {
        // Jika klien MQTT sudah ada
        if (self::$mqttClient !== null) {
            // Cek apakah klien terhubung
            if (self::$mqttClient->isConnected()) {
                echo "Using existing MQTT connection.\n";
                return self::$mqttClient; // Kembalikan klien yang sudah terhubung
            } else {
                // Jika klien ada tetapi tidak terhubung, coba sambungkan kembali
                echo "Reconnecting to MQTT broker...\n";
            }
        }

        // Membuat koneksi baru jika klien belum ada atau tidak terhubung
        // $server   = '192.168.2.4';
        $server   = 'broker.emqx.io';
        $port     = 1883;
        $clientId = 'SMODE PHP ' . uniqid(); // Pastikan Client ID unik
        $mqttUsername = 'smode';
        $mqttPassword = '12345678';
        $clean_session = true;

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($mqttUsername)
            ->setPassword($mqttPassword)
            ->setKeepAliveInterval(60)
            ->setConnectTimeout(3)
            //->setUseTls(true)
            //->setTlsSelfSignedAllowed(true)
            ->setLastWillQualityOfService(1);
            //->setTlsCertificateAuthorityPath('C:/Users/Asus Gk/Documents/1. Tugas-Tugas/Aplikasi_Smode/');

        self::$mqttClient = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1_1);
        
        try {
            self::$mqttClient->connect($connectionSettings, $clean_session);
            echo "Connected to MQTT broker\n";
        } catch (Exception $e) {
            echo "Failed to connect to MQTT broker: " . $e->getMessage() . "\n";
            return null; // Kembalikan null jika koneksi gagal
        }

        return self::$mqttClient; // Kembalikan klien MQTT yang baru saja terhubung
    }
}
