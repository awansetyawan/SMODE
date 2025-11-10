# 🏍️ IMPLEMENTASI PROTOKOL MESSAGE QUEUING TELEMETRY TRANSPORT (MQTT) SEBAGAI MEDIA KOMUNIKASI PROTOTIPE SMART MOTORCYCLE DETECTOR

**Penulis :** Alif Maulana Setyawan  
**NIM :** 2109106002  
**Program Studi :** Informatika, Fakultas Teknik  
**Universitas Mulawarman – 2025**

---

## 🧠 Deskripsi Singkat

Proyek ini merupakan hasil penelitian skripsi yang bertujuan untuk **merancang dan membangun sistem Smart Motorcycle Detector** berbasis **Internet of Things (IoT)** menggunakan **protokol MQTT**.  

Sistem ini mampu **identifikasi pengguna motor melalui Bluetooth Low Energy (BLE)**, **mendeteksi gerakan atau gerakan kendaraan**, dan **memutus aliran listrik motor dari aplikasi**.  

---

## ⚙️ Rangkaian Smart Motorcycle Detector

<img src="./Gambar/Rangkaian Smart Motorcycle Detector.png" alt="Rangkaian SMODE" width="50%">

---

## 🔌 Skema Pin ESP32

| Komponen | ESP32 Pin |
| -------- | ---------- |
| MPU-6050 | 21 & 22 |
| Relay | 19 |
| Buzzer | 18 |
| Bluetooth (BLE) | Internal ESP32 |
| Daya | 3,3V / GND |

<img src="./Gambar/Skema Pin Smart Motorcycle Detector.png" alt="Skema Pin SMODE" width="40%">

---

## 🌐 Topologi Jaringan

<img src="./Gambar/Topologi Jaringan.png" alt="Topologi Jaringan" width="50%">

> Topologi sistem Smart Motorcycle Detector menggambarkan hubungan antara hardware, broker/server, backend, dan software dalam satu jaringan lokal.

> Broker MQTT, backend aplikasi, dan aplikasi berjalan pada jaringan router yang terhubung dengan Internet Service Provider (ISP), sedangkan NodeMCU ESP32 terhubung langsung ke jaringan ISP.

---

## 🧩 Komponen

| Komponen | Deskripsi |
|-----------|-----------|
| Mikrokontroler | ESP32 |
| Sensor | MPU-6050 |
| Aktuator | Relay 1 Channel & Buzzer |
| Komunikasi | MQTT |
| Broker | EMQX |
| Backend | Laravel (PHP) |
| Database | MySQL (XAMPP) |
| Aplikasi Mobile | Flutter (Dart) |
| Analisis | Wireshark (Throughput, Delay, & Packet Loss) |

---

## 🚀 Cara Instalasi

### 1. Install & Jalankan Broker MQTT (EMQX)

1) Update sistem
   
```bash
sudo apt update && sudo apt upgrade -y
```

2) Download EMQX

```bash
wget https://www.emqx.com/en/downloads/enterprise/5.10.1/emqx-enterprise-5.10.1-ubuntu20.04-amd64.deb

sudo apt install ./emqx-enterprise-5.10.1-ubuntu20.04-amd64.deb
```

3) Jalankan EMQX

```bash
sudo systemctl start emqx

sudo systemctl status emqx
```

4) Konfigurasi file emqx.conf

```bash
sudo nano /etc/emqx/emqx.conf

sesuaikan bagian - name = "emqx@ipaddressbroker"

Simpan dan keluar.

sudo systemctl restart emqx

sudo systemctl status emqx
```

5) Akses Dashboard EMQX

```bash
Buka browser dan akses:

http://ipaddressbroker:18083

Login default:

Username: admin
Password: public
```

### 2. Clone Repository

```bash
git clone https://github.com/awansetyawan/SMODE.git

cd SMODE
```

### 3. Jalankan XAMPP

1) Buka XAMPP Control Panel → Start Apache dan MySQL
2) Buka browser dan akses: http://localhost/phpmyadmin
3) Klik Database → Create New Database
4) Nama database: smode
5) Setelah database dibuat, klik database tersebut → Import
6) Pilih file SQL yang ada di folder:
   
   ```bash
   /SMODE/Database/smode.sql
   ```
8) Klik Go untuk mengimpor tabel dan data awal.

### 4. Jalankan Backend Aplikasi

```bash
cd SMODE/Aplikasi SMODE/smode_backend-main

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan serve --host "IP Address Backend"

php SMODE/Aplikasi SMODE/smode_backend-main/smode.php
```

> Pastikan konfigurasi .env telah disesuaikan dengan database dan alamat broker MQTT.

### 5. Jalankan Aplikasi

1) Konfigurasi File Shared Value
```bash
Aplikasi SMODE/smode/lib/shared/shared_values.dart

sesuaikan bagian - String baseUrl = 'http://ipaddressbackend:8000/api';
```
2) Start Debugging
3) Tampilan Aplikasi

<img src="./Gambar/Tampilan Aplikasi Smart Motorcycle Detector.png" alt="Tampilan Aplikasi" width="50%">

### 6. Upload Code ke ESP32

1) Sesuaikan konfigurasi jaringan dan broker:

```bash
const char* ssid = "NamaWiFi";
const char* password = "PasswordWiFi";
const char* mqtt_broker = "IPBroker";  // IP broker EMQX
const char* topic = "topic/"; // Topic Broker
const char *mqtt_username = "UsernameBroker"; // Authentication EMQX
const char *mqtt_password = "PasswordBroker"; // Authentication EMQX
const int mqtt_port = 1883; // Port Broker
```

2) Upload ke board ESP32.

## 📂 Struktur Folder

```
/smart-motorcycle-detector
│
├─ /Aplikasi SMODE        # Source Code Frontend & Backend Software
├─ /Database              # File Database
├─ /Prototipe SMODE       # Source Code Hardware
├─ /Gambar                # Rangkaian, Skema Pin, Tampilan Aplikasi, & Topologi
├─ /Data Hasil Analisis   # Hasil Data
└─ README.md
```

---

## 🧾 Kesimpulan

* Implementasi protokol MQTT QoS Level 1 pada prototipe Smart Motorcycle Detector (SMODE) berhasil menunjukkan komunikasi yang andal dan efisien antara perangkat IoT dan aplikasi.
* Pengujian menunjukkan bahwa MQTT dapat beroperasi dengan baik pada jaringan lokal, meskipun terdapat variasi throughput dan delay antara hari kerja dan hari libur.   
  a) Throughput tertinggi mencapai 68.38 Kbps pada hari kerja, menunjukkan kinerja jaringan yang lebih optimal saat aktivitas tinggi.   
  b) Delay terendah tercatat 43.01 ms, dengan rata-rata 57.33 ms (hari kerja) dan 64.40 ms (hari libur) — keduanya termasuk kategori “Sangat Bagus” menurut standar ITU-T Y.1541.   
  c) Packet loss 0% pada seluruh pengujian juga menegaskan keandalan komunikasi MQTT.   
* Secara keseluruhan, sistem ini membuktikan bahwa MQTT QoS Level 1 dapat digunakan secara efektif untuk komunikasi real-time pada arsitektur IoT lokal seperti SMODE.

---

## ✉️ Kontak

**Alif Maulana Setyawan**   

📧 [alifmaulanasetyawan@gmail.com](mailto:alifmaulanasetyawan@gmail.com)   
🌐 [github.com/awansetyawan](https://github.com/awansetyawan)   
📍 Samarinda, Kalimantan Timur   

---
