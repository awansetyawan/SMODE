#include <WiFi.h>
#include <AsyncMqttClient.h>
//#include <WiFiClientSecure.h>

#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEScan.h>

#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>
#include <Wire.h>

Adafruit_MPU6050 mpu;

#define BUZZER_PIN 18
#define RELAY_PIN 19

const char *itag_uuid = "0000ffe0-0000-1000-8000-00805f9b34fb";
//const char *itag_mac_address = "5b:02:ba:48:90:22"; // Alamat MAC iTag
const int RSSI_THRESHOLD = -70;

// const char *ssid = "_localhost_";
// const char *password = "samarinda46";

const char *ssid = "Cloud Iphone";
const char *password = "udahketemukah";

// Tembak IP Network di Mikrotik
const char *mqtt_broker = "172.20.10.2";
// const char *mqtt_broker = "broker.emqx.io";
const char *topicAman = "vehicle/aman/1";
const char *topicMesin = "vehicle/mesin/1";
const char *topicDeteksi = "vehicle/deteksi/1";
const char *mqtt_username = "smode";
const char *mqtt_password = "12345678";
const int mqtt_port = 1883;

BLEScan *bleScan;
WiFiClient espClient;
AsyncMqttClient mqttClient;

// Variabel BLE
bool itag_found = false;
bool itag_in_range = false;
bool last_itag_in_range = false;

// Timer untuk deteksi hilangnya BLE
unsigned long last_ble_seen_time = 0;
unsigned long ble_timeout = 20000; // Timeout BLE dalam milidetik (20 detik)

// Status mesin
int mesin = 1;

// Status aman
int aman = 1;

// Status deteksi
int deteksi = 0;

unsigned long last_ble_scan_time = 0;
unsigned long ble_scan_interval = 2000; // Interval pemindaian dalam milidetik

unsigned long lastReconnectAttempt = 0;
unsigned long reconnectInterval = 5000; // Interval 5 detik untuk mencoba reconnect

// Variabel untuk status
bool lastMotionStatus = false;  // Variabel untuk menyimpan status gerakan sebelumnya

void connectToWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  WiFi.setAutoReconnect(true);
  WiFi.persistent(true);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.println("Connecting to WiFi...");
  }
  Serial.println("Connected to WiFi");
  Serial.print("ESP32 IP Address: ");
  Serial.println(WiFi.localIP());
}

void connectToMQTT() {
  mqttClient.setServer(mqtt_broker, mqtt_port);
  mqttClient.setCredentials(mqtt_username, mqtt_password);  // Set username and password
  mqttClient.connect();
}

// Callback untuk koneksi MQTT
void onMqttConnect(bool sessionPresent) {
  Serial.println("Connected to MQTT broker");
  Serial.print("Session Present: ");
  Serial.println(sessionPresent); // Menampilkan status sesi MQTT

  // Berlangganan topik
  mqttClient.subscribe(topicAman, 1);
  Serial.println("Subscribed to topic: vehicle/aman/1");
  mqttClient.subscribe(topicMesin, 1);
  Serial.println("Subscribed to topic: vehicle/mesin/1");
  mqttClient.subscribe(topicDeteksi, 1);
  Serial.println("Subscribed to topic: vehicle/deteksi/1");

  // Publikasi pesan awal (opsional)
  // mqttClient.publish(topicAman, 1, false, "1");
  // mqttClient.publish(topicMesin, 1, false, "1");
  // mqttClient.publish(topicDeteksi, 1, false, "0");
}

// Callback untuk disconnect MQTT
void onMqttDisconnect(AsyncMqttClientDisconnectReason reason) {
  Serial.println("Disconnected from MQTT broker");
  lastReconnectAttempt = millis(); // Catat waktu disconnect
}

// Callback untuk menerima pesan MQTT
void onMqttMessage(char* topic, char* payload, AsyncMqttClientMessageProperties properties, unsigned int length, unsigned int offset, unsigned int totalLength) {
  String message = "";
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  Serial.printf("Message received on topic %s: %s\n", topic, message.c_str());

  // Menangani pesan pada topik mesin
  if (String(topic) == topicMesin) {
    if (message == "0") {
      mesin = 0;
      Serial.println("Motor Mati");
    } else if (message == "1") {
      mesin = 1;
      Serial.println("Motor Nyala");
    }
  }
  // Menangani pesan pada topik aman
  else if (String(topic) == topicAman) {
    if (message == "0" || message == "APK 0") {
      aman = 0;
    } else if (message == "1" || message == "APK 1") {
      aman = 1;
    }
  }
}

void deteksiBLE() {
  BLEScanResults scanResults = bleScan->start(2, false); // Scan selama 2 detik
  itag_found = false;
  itag_in_range = false;

  // Proses hasil pemindaian BLE
  for (int i = 0; i < scanResults.getCount(); i++) {
    BLEAdvertisedDevice device = scanResults.getDevice(i);
    if (device.getServiceUUID().toString() == itag_uuid) {
      itag_found = true;
      itag_in_range = device.getRSSI() >= RSSI_THRESHOLD;
      last_ble_seen_time = millis(); // Perbarui waktu terakhir perangkat terlihat
      break;
    }
    // if (device.getAddress().toString() == itag_mac_address) {
    //   itag_found = true;
    //   itag_in_range = device.getRSSI() >= RSSI_THRESHOLD;
    //   last_ble_seen_time = millis(); // Perbarui waktu terakhir perangkat terlihat
    //   break;
    // }
  }

  // Logika untuk mendeteksi hilangnya perangkat BLE
  if (!itag_found && (millis() - last_ble_seen_time >= ble_timeout)) {
    // Jika perangkat tidak terdeteksi dalam pemindaian berturut-turut
    if (last_itag_in_range) {
      mqttClient.publish(topicAman, 1, false, "1"); // iTag hilang
      Serial.println("iTag hilang");
      last_itag_in_range = false;
    }
  }

  bleScan->clearResults(); // Bersihkan hasil pemindaian
}

void handleBLEStatus() {
  unsigned long current_time = millis();

  if (itag_found) {
    if (itag_in_range != last_itag_in_range) {
      if (itag_in_range) {
        mqttClient.publish(topicAman, 1, false, "0"); // iTag dalam jangkauan
        aman = 0;
        Serial.println("iTag dalam jangkauan");
      } else {
        mqttClient.publish(topicAman, 1, false, "1"); // iTag di luar jangkauan
        aman = 1;
        Serial.println("iTag di luar jangkauan");
      }
      last_itag_in_range = itag_in_range;
    }
  } else if (current_time - last_ble_seen_time >= ble_timeout) {
    // BLE menghilang
    if (last_itag_in_range) {
      mqttClient.publish(topicAman, 1, false, "1"); // iTag hilang
      aman = 1;
      Serial.println("iTag hilang");
      last_itag_in_range = false;
    }
  }
}

void handleRelay() {
  if (mesin == 0) {
    digitalWrite(RELAY_PIN, HIGH); // Relay aktif (mesin mati)
  } else {
    digitalWrite(RELAY_PIN, LOW); // Relay mati (mesin nyala)
  }
}

void detectMotion() {
  bool currentMotionStatus = mpu.getMotionInterruptStatus(); // Ambil status gerakan saat ini

  // Cek jika status gerakan berubah
  if (currentMotionStatus != lastMotionStatus) {
    if (currentMotionStatus) {
      // Gerakan terdeteksi, jadi tampilkan "Tidak Aman"
      // Serial.println("Tidak Aman");

      // Ambil data akselerasi dan giroskop jika ada gerakan
      sensors_event_t a, g, temp;
      mpu.getEvent(&a, &g, &temp);

      /* Print out the values */
      Serial.print("AccelX:");
      Serial.print(a.acceleration.x);
      Serial.print(", ");
      Serial.print("AccelY:");
      Serial.print(a.acceleration.y);
      Serial.print(", ");
      Serial.print("AccelZ:");
      Serial.print(a.acceleration.z);
      Serial.print(", ");
      Serial.print("GyroX:");
      Serial.print(g.gyro.x);
      Serial.print(", ");
      Serial.print("GyroY:");
      Serial.print(g.gyro.y);
      Serial.print(", ");
      Serial.print("GyroZ:");
      Serial.print(g.gyro.z);
      Serial.println("");

      // Buzzer hidup
      digitalWrite(BUZZER_PIN, HIGH);

      mqttClient.publish(topicDeteksi, 1, false, "1"); // Getaran dan Gerakan Terdeteksi
    } else {
      // Tidak ada gerakan, tampilkan "Aman"
      // Serial.println("Aman");

      // Buzzer mati
      digitalWrite(BUZZER_PIN, LOW);

      mqttClient.publish(topicDeteksi, 1, false, "0"); // Getaran dan Gerakan Terdeteksi
    }

    // Update status gerakan terakhir
    lastMotionStatus = currentMotionStatus;
  }
}

void checkWiFi() {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi Disconnected, reconnecting...");
        WiFi.disconnect();
        WiFi.reconnect();
    }
}

void setup() {
  Serial.begin(115200);

  // Inisialisasi BLE
  BLEDevice::init("ESP32_BLE_Scanner");
  bleScan = BLEDevice::getScan();
  bleScan->setActiveScan(true); // Aktifkan pemindaian aktif

  // Inisialisasi WiFi
  connectToWiFi();

  // Inisialisasi MQTT
  mqttClient.onConnect(onMqttConnect);
  mqttClient.onDisconnect(onMqttDisconnect);
  mqttClient.onMessage(onMqttMessage); // Menghubungkan callback untuk menerima pesan
  connectToMQTT();

  // Inisialisasi pin
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(RELAY_PIN, OUTPUT);

  while (!Serial)
    delay(10); // will pause Zero, Leonardo, etc until serial console opens

  Serial.println("Adafruit MPU6050 test!");

  // Try to initialize!
  if (!mpu.begin()) {
    Serial.println("Failed to find MPU6050 chip");
    while (1) {
      delay(10);
    }
  }
  Serial.println("MPU6050 Found!");

  // Set up motion detection
  mpu.setHighPassFilter(MPU6050_HIGHPASS_5_HZ); // Filter rendah untuk deteksi lebih sensitif
  mpu.setMotionDetectionThreshold(1); // Threshold lebih rendah agar lebih sensitif
  mpu.setMotionDetectionDuration(10); // Durasi lebih singkat untuk lebih cepat merespons
  mpu.setInterruptPinLatch(true);  // Keep it latched.  Will turn off when reinitialized.
  mpu.setInterruptPinPolarity(true);
  mpu.setMotionInterrupt(true); // Aktifkan interrupt gerakan

  Serial.println("");
  delay(100);
}

void loop() {
 
  unsigned long current_time = millis();

  // Logika reconnect MQTT
  if (WiFi.isConnected() && !mqttClient.connected() && (current_time - lastReconnectAttempt >= reconnectInterval)) {
    Serial.println("Attempting to reconnect to MQTT broker...");
    lastReconnectAttempt = current_time;
    connectToMQTT();
  }

  // Pemindaian BLE setiap 5 detik
  if (current_time - last_ble_scan_time >= ble_scan_interval) {
    last_ble_scan_time = current_time;
    deteksiBLE();
    handleBLEStatus();
  }

  if(aman == 1){
    detectMotion(); // Panggil fungsi untuk mendeteksi gerakan
    delay(500); // Kurangi delay agar lebih responsif
  }else if(aman == 0){
    lastMotionStatus = true;
  }

  // Kontrol relay dan buzzer
  handleRelay();

  checkWiFi();
}
