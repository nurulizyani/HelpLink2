import 'dart:convert';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

class NotificationService {
  static final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();

  static const AndroidNotificationChannel _channel =
      AndroidNotificationChannel(
    'helplink_channel',
    'HelpLink Notifications',
    description: 'Notifications for HelpLink app',
    importance: Importance.high,
  );

  // ===============================
  // INITIALIZE
  // ===============================
  static Future<void> initialize() async {
    const AndroidInitializationSettings androidInit =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    const InitializationSettings initSettings =
        InitializationSettings(android: androidInit);

    await _plugin.initialize(initSettings);

    await _plugin
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_channel);
  }

  // ===============================
  // SHOW LOCAL NOTIFICATION
  // ===============================
  static Future<void> showNotification(RemoteMessage message) async {
    final androidDetails = AndroidNotificationDetails(
      _channel.id,
      _channel.name,
      channelDescription: _channel.description,
      importance: Importance.high,
      priority: Priority.high,
      playSound: true,
    );

    final details = NotificationDetails(android: androidDetails);

    final payload = <String, dynamic>{};

    message.data.forEach((k, v) {
      payload[k] = v.toString();
    });

    payload.putIfAbsent('type', () => 'system');

    await _plugin.show(
      DateTime.now().millisecondsSinceEpoch ~/ 1000,
      message.notification?.title ?? 'HelpLink',
      message.notification?.body ?? 'You have a new notification',
      details,
      payload: jsonEncode(payload),
    );
  }
}
