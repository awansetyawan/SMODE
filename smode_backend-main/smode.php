<?php

require('vendor/autoload.php');

use App\Services\MqttManager;

// Konfigurasi database
$host = "localhost";
$username = "root";
$password = "";
$database = "smode";

// Membuat koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

// Memanggil klien MQTT global
$mqtt = MqttManager::getClient();

if ($mqtt) {
    $id = 1; // ID kendaraan, bisa disesuaikan

    // Subscribe ke topik deteksi
    $topic_deteksi = 'vehicle/deteksi/' . $id;
    $mqtt->subscribe($topic_deteksi, function ($topic, $message) use ($id, $conn) {
        if ($message == '1'){
            printf("Received message on topic [%s]: %s\n", $topic, $message);
    
            // Mengambil token dari database
            $sql = "SELECT token FROM users WHERE id = $id";
            $result = $conn->query($sql);
    
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $token = $row["token"];
                $notif = 'Deteksi';
                sendNotification($token, $notif);
            }
    
            date_default_timezone_set('Asia/Singapore');
            $now = date('Y-m-d H:i:s');
    
            // Menyimpan data deteksi ke database
            $sql = "INSERT INTO detections (vehicle_id, created_at, updated_at) VALUES ('$id', '$now', '$now')";
    
            if (mysqli_query($conn, $sql)) {
                print("[Success] Data Detection Vehicle ID $id berhasil dibuat\n");
            } else {
                print("[Error] Data Detection Vehicle ID $id gagal dibuat\n");
            }
        }else{
            printf("Received message on topic [%s]: %s\n", $topic, $message);
        }
    }, 1);

    // Subscribe ke topik mesin
    $topic_mesin = 'vehicle/mesin/' . $id;
    $mqtt->subscribe($topic_mesin, function ($topic, $message) use ($id, $conn) {
        printf("Received message on topic [%s]: %s\n", $topic, $message);

        // Mengupdate mode mesin di database
        $sql = "UPDATE vehicles SET mode_mesin = '$message' WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            print("\n[Success] Data Vehicle ID $id berhasil diupdate\n");
        } else {
            print("[Error] Data Vehicle ID $id gagal diupdate\n");
        }
    }, 1);

    // Subscribe ke topik aman
    $topic_aman = 'vehicle/aman/' . $id;
    $mqtt->subscribe($topic_aman, function ($topic, $message) use ($id, $conn) {
        printf("Received message on topic [%s]: %s\n", $topic, $message);

        if ($message == 0 || $message == 1){
            // Mengambil token dari database
            $sql = "SELECT token FROM users WHERE id = $id";
            $result = $conn->query($sql);
    
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $token = $row["token"];
                $notif = 'Aman';
                sendNotification($token, $notif);
            }
        }else{
            if($message == 'APK 1'){
                $message = 1;
            }elseif($message == 'APK 0'){
                $message = 0;
            }
        }
        
        printf("Received message : %s\n", $message);

        // Mengupdate mode aman di database
        $sql = "UPDATE vehicles SET mode_aman = '$message' WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            print("\n[Success] Data Vehicle ID $id berhasil diupdate\n");
        } else {
            print("[Error] Data Vehicle ID $id gagal diupdate\n");
        }
    }, 1);

    // Mulai loop MQTT
    $mqtt->loop(true);
} else {
    echo "Failed to connect to MQTT broker. Exiting...\n";
}

// mendapatkan token fcm
function getAccessToken() {
    $serviceAccountPath = 'smode-4dacc-firebase-adminsdk-a75xd-6539d0ed58.json';

    // Sesuaikan dengan file JSON service account Anda
    $credentials = json_decode(file_get_contents($serviceAccountPath), true);

    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $jwtClaim = base64_encode(json_encode([
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $credentials['token_uri'],
        'iat' => $now,
        'exp' => $now + 3600
    ]));

    $jwt = $jwtHeader . '.' . $jwtClaim;
    $signature = '';
    openssl_sign($jwt, $signature, $credentials['private_key'], 'SHA256');
    $jwt .= '.' . base64_encode($signature);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $credentials['token_uri']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['access_token'];
}

function sendNotification($deviceToken, $notif) {

    if ($notif == 'Deteksi'){
        $title = 'Motor anda terindikasi dalam keadaan bahaya';
        $body = 'Segera lakukan tindakan yang diperlukan untuk pengamanan!';
    
        $payload = array(
            'message'=> array(
            "token"=>$deviceToken,
            "notification"=> array(
                'title' => $title,
                'body' => $body
            ),
            )
        );
    }elseif($notif == 'Aman'){
        $title = 'Aman';
        $body = 'Aman';
    
        $payload = array(
            'message'=> array(
            "token"=>$deviceToken,
            "notification"=> array(
                'title' => $title,
                'body' => $body
            ),
            )
        );
    }


    $accessToken = getAccessToken();

    $headers = array(
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    );

    $ch = curl_init('https://fcm.googleapis.com/v1/projects/smode-4dacc/messages:send');

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response; // Optional: Untuk melihat respon dari server
}