import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../services/api_client.dart';

class NotificationApiService {
  // ===============================
  // FETCH ALL NOTIFICATIONS
  // ===============================
  Future<List<dynamic>> fetchNotifications() async {
    try {
      final res = await ApiClient.get(
        Uri.parse('${ApiConfig.apiBase}/notifications'),
      );

      if (res.statusCode != 200) return [];

      final decoded = jsonDecode(res.body);

      if (decoded is Map &&
          decoded['success'] == true &&
          decoded['data'] is List) {
        return decoded['data'];
      }

      return [];
    } catch (_) {
      return [];
    }
  }

  // ===============================
  // MARK SINGLE NOTIFICATION AS READ
  // ===============================
  Future<bool> markAsRead(int notificationId) async {
    try {
      final res = await ApiClient.post(
        Uri.parse(
          '${ApiConfig.apiBase}/notifications/$notificationId/read',
        ),
      );

      if (res.statusCode != 200) return false;

      final decoded = jsonDecode(res.body);
      return decoded is Map && decoded['success'] == true;
    } catch (_) {
      return false;
    }
  }

  // ===============================
  // MARK ALL AS READ
  // ===============================
  Future<bool> markAllAsRead() async {
    try {
      final res = await ApiClient.post(
        Uri.parse('${ApiConfig.apiBase}/notifications/read-all'),
      );

      if (res.statusCode != 200) return false;

      final decoded = jsonDecode(res.body);
      return decoded is Map && decoded['success'] == true;
    } catch (_) {
      return false;
    }
  }
}
