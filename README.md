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

<img src="./Gambar/Skema Pin Smart Motorcycle Detector.png" alt="Skema Pin SMODE" width="50%">

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
| Aktuator | Relay 1 Channel |
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

2) Tambahkan repository EMQX

```bash
wget https://www.emqx.com/en/downloads/broker/5.0.26/emqx-5.0.26-ubuntu22.04-amd64.deb

sudo dpkg -i emqx-5.0.26-ubuntu22.04-amd64.deb
```

3) Jalankan EMQX

```bash
sudo systemctl start emqx

sudo systemctl status emqx
```

4) Akses Dashboard EMQX

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

php artisan serve "IP Address Backend"
```

> Pastikan konfigurasi .env telah disesuaikan dengan database dan alamat broker MQTT.
