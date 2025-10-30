import 'dart:convert';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:smode/main.dart';

Future<void> handleBackgroundMessage(RemoteMessage message) async {
  // Tangani notifikasi di background
  //print('Background Message: ${message.messageId}');
}

class FirebaseService {
  
  // Callback untuk pembaruan UI
  bool onMessageReceived = false;
  bool onMessageReceivedDeteksi = false;

  final _firebaseMessaging = FirebaseMessaging.instance;

  final _androidChannel = const AndroidNotificationChannel(
    'high_importance_channel', // ID channel
    'High Importance Notifications', // Nama channel
    description: 'This channel is used for important notifications', // Deskripsi channel
    importance: Importance.high, // Tingkat kepentingan
  );

  final _localNotifications = FlutterLocalNotificationsPlugin();

  void handleMessage(RemoteMessage? message) {
    if (message == null) return;

    //print('Notification Opened: ${message.notification?.title}');
    navigatorKey.currentState?.pushNamed(
      '/home',
    );
  }

  Future<void> initLocalNotifications() async {
    // Inisialisasi pengaturan notifikasi
    const iOS = IOSInitializationSettings();
    const android = AndroidInitializationSettings('@drawable/ic_launcher');
    const settings = InitializationSettings(android: android, iOS: iOS);

    await _localNotifications.initialize(
      settings,
      onSelectNotification: (payload) {
        if (payload != null) {
          final message = RemoteMessage.fromMap(jsonDecode(payload));
          handleMessage(message);
        }
      },
    );

    // Tambahkan channel untuk Android
    final androidImplementation = _localNotifications
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();

    await androidImplementation?.createNotificationChannel(_androidChannel);
  }

  Future<void> initPushNotifications() async {
    // Pastikan aplikasi memiliki izin untuk menerima notifikasi
    await _firebaseMessaging.requestPermission();

    // Aktifkan notifikasi di foreground
    await FirebaseMessaging.instance.setForegroundNotificationPresentationOptions(
      alert: true,   // Tampilkan notifikasi
      badge: true,   // Tampilkan badge
      sound: true,   // Suara untuk notifikasi
    );

    // Tampilkan notifikasi di foreground
    FirebaseMessaging.onMessage.listen((message) {
      // print('Foreground Message: ${message.notification?.title}');
      final notification = message.notification;

      // if (notification == null) return;

      // _localNotifications.show(
      //   message.hashCode,
      //   notification.title,
      //   notification.body,
      //   NotificationDetails(
      //     android: AndroidNotificationDetails(
      //       _androidChannel.id,
      //       _androidChannel.name,
      //       channelDescription: _androidChannel.description,
      //       importance: Importance.high,
      //       priority: Priority.high,
      //       icon: '@drawable/ic_launcher',
      //     ),
      //   ),
      //   payload: jsonEncode(message.toMap()),
      // );

      if (notification != null && message.notification?.title == 'Motor anda terindikasi dalam keadaan bahaya') {
        print('Foreground Message: ${message.notification?.title}');
        _localNotifications.show(
          message.hashCode,
          notification.title,
          notification.body,
          NotificationDetails(
            android: AndroidNotificationDetails(
              _androidChannel.id,
              _androidChannel.name,
              channelDescription: _androidChannel.description,
              importance: Importance.high,
              priority: Priority.high,
              icon: '@drawable/ic_launcher',
            ),
          ),
          payload: jsonEncode(message.toMap()),
        );

        onMessageReceivedDeteksi = true;

      }else{
        onMessageReceived = true;
      }
      
      // print('Foreground Message: ${message.notification?.title}');
      // print('Foreground message received, onMessageReceived: $onMessageReceived');

    });

    // Tangani notifikasi saat aplikasi dibuka dari background
    FirebaseMessaging.onMessageOpenedApp.listen(handleMessage);

    // Tangani notifikasi di background
    FirebaseMessaging.onBackgroundMessage(handleBackgroundMessage);

    // Tangani notifikasi awal jika aplikasi dibuka dari klik notifikasi
    FirebaseMessaging.instance.getInitialMessage().then(handleMessage);
  }

  Future<String?> initNotifications() async {
    // Inisialisasi token Firebase
    final fcmToken = await _firebaseMessaging.getToken();
    //print('FCM Token: $fcmToken');

    // Inisialisasi notifikasi
    await initLocalNotifications();
    await initPushNotifications();

    return fcmToken;
  }
}



// import 'dart:convert';

// import 'package:firebase_messaging/firebase_messaging.dart';
// // import 'package:flutter_application_2/main.dart';
// import 'package:flutter_local_notifications/flutter_local_notifications.dart';
// import 'package:smode/main.dart';
// // import 'package:smode/main.dart';

// Future<void> handleBackgroundMessage(RemoteMessage message) async {
// }

// class FirebaseService {
//   final _firebaseMessaging = FirebaseMessaging.instance;

//   final _androidChannel = const AndroidNotificationChannel(
//     'high_importance_channel',
//     'High Importance Notifications',
//     description: 'This channel is used for important notifications',
//     importance: Importance.defaultImportance,
//   );

//   final _localNotifications = FlutterLocalNotificationsPlugin();

//   void handleMessage(RemoteMessage? message) {
//     if (message == null) return;

//     navigatorKey.currentState?.pushNamed(
//       '/home',
//     );
//   }

//   Future initLocalNotifications() async {
//     const iOS = IOSInitializationSettings();
//     const android = AndroidInitializationSettings('@drawable/ic_launcher');
//     const settings = InitializationSettings(android: android, iOS: iOS);

//     await _localNotifications.initialize(
//       settings,
//       onSelectNotification: (payload) {
//         final message = RemoteMessage.fromMap(jsonDecode(payload!));
//         handleMessage(message);
//       },
//     );

//     final platform = _localNotifications.resolvePlatformSpecificImplementation<
//         AndroidFlutterLocalNotificationsPlugin>();

//     await platform?.createNotificationChannel(_androidChannel);
//   }

//   Future initPushNotifications() async {
//     await FirebaseMessaging.instance
//         .setForegroundNotificationPresentationOptions(
//             alert: true, badge: true, sound: true);

//     FirebaseMessaging.instance.getInitialMessage().then(handleMessage);
//     FirebaseMessaging.onMessageOpenedApp.listen(handleMessage);
//     FirebaseMessaging.onBackgroundMessage(handleBackgroundMessage);
//     FirebaseMessaging.onMessage.listen((message) {
//       final notification = message.notification;

//       if (notification == null) return;

//       _localNotifications.show(
//         notification.hashCode,
//         notification.title,
//         notification.body,
//         NotificationDetails(
//             android: AndroidNotificationDetails(
//           _androidChannel.id,
//           _androidChannel.name,
//           channelDescription: _androidChannel.description,
//           icon: '@drawable/ic_launcher',
//         )),
//         payload: jsonEncode(message.toMap()),
//       );
//     });
//   }

//   Future<String?> initNotifications() async {
//     await _firebaseMessaging.requestPermission();
//     final fCMToken = await _firebaseMessaging.getToken();
//     initPushNotifications();
//     initLocalNotifications();
//     print(fCMToken);
//     return fCMToken;
//   }
// }
