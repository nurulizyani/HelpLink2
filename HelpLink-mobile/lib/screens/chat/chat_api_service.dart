import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:part_1/config/api_config.dart';

class ChatApiService {
  // =============================
  // AUTH HEADER (STRICT)
  // =============================
  Future<Map<String, String>> _authHeader() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');

    if (token == null || token.isEmpty) {
      throw Exception('Unauthenticated: auth_token missing');
    }

    return {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      // IMPORTANT: backend Laravel default ok with form-data / x-www-form-urlencoded
      'Content-Type': 'application/x-www-form-urlencoded',
    };
  }

  // =============================
  // GET OR CREATE CONVERSATION
  // =============================
  Future<Map<String, dynamic>> getOrCreateConversation({
    required int otherUserId,
    int? offerId,
    int? requestId,
  }) async {
    if (offerId == null && requestId == null) {
      throw Exception('Conversation must be linked to offer_id or request_id');
    }

    final headers = await _authHeader();

    final body = <String, String>{
      'other_user_id': otherUserId.toString(),
      if (offerId != null) 'offer_id': offerId.toString(),
      if (requestId != null) 'request_id': requestId.toString(),
    };

    final res = await http.post(
      Uri.parse('${ApiConfig.apiBase}/chat/start'),
      headers: headers,
      body: body,
    );

    final decoded = _safeJson(res.body);

    if (res.statusCode == 200 && decoded['success'] == true) {
      final data = decoded['data'];
      if (data is Map) return Map<String, dynamic>.from(data);
      throw Exception('Chat start: invalid data format');
    }

    final msg = decoded['message'] ?? 'Failed to start chat';
    throw Exception('Chat start failed (${res.statusCode}): $msg');
  }

  // =============================
  // SEND MESSAGE
  // =============================
  Future<bool> sendMessage({
    required int conversationId,
    required String message,
  }) async {
    final msg = message.trim();
    if (msg.isEmpty) return false;

    try {
      final headers = await _authHeader();
      final res = await http.post(
        Uri.parse('${ApiConfig.apiBase}/chat/send'),
        headers: headers,
        body: {
          'conversation_id': conversationId.toString(),
          'message': msg,
        },
      );

      return res.statusCode == 200 || res.statusCode == 201;
    } catch (_) {
      return false;
    }
  }

  // =============================
  // GET MESSAGES
  // =============================
  Future<List<dynamic>> getMessages(int conversationId) async {
    try {
      final headers = await _authHeader();
      final res = await http.get(
        Uri.parse('${ApiConfig.apiBase}/chat/messages/$conversationId'),
        headers: headers,
      );

      final decoded = _safeJson(res.body);

      if (res.statusCode == 200 && decoded['data'] is List) {
        return decoded['data'] as List<dynamic>;
      }

      return [];
    } catch (_) {
      return [];
    }
  }

  // =============================
  // GET MY CONVERSATIONS
  // =============================
  Future<List<dynamic>> getConversations() async {
    try {
      final headers = await _authHeader();
      final res = await http.get(
        Uri.parse('${ApiConfig.apiBase}/chat/conversations'),
        headers: headers,
      );

      final decoded = _safeJson(res.body);

      if (res.statusCode == 200 && decoded['data'] is List) {
        return decoded['data'] as List<dynamic>;
      }

      return [];
    } catch (_) {
      return [];
    }
  }

  // =============================
  // SAFE JSON PARSE
  // =============================
  Map<String, dynamic> _safeJson(String body) {
    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {'success': false, 'message': 'Invalid JSON format'};
    } catch (_) {
      return {'success': false, 'message': 'Invalid JSON'};
    }
  }
}
