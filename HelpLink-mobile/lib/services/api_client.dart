import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiClient {
  static Future<Map<String, String>> _headers({bool json = false}) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');

    return {
      'Accept': 'application/json',
      if (json) 'Content-Type': 'application/json',
      if (token != null && token.isNotEmpty)
        'Authorization': 'Bearer $token',
    };
  }

  // ===============================
  // GET
  // ===============================
  static Future<http.Response> get(Uri url) async {
    return http.get(url, headers: await _headers());
  }

  // ===============================
  // POST (FORM)
  // ===============================
  static Future<http.Response> post(
    Uri url, {
    Map<String, String>? body,
  }) async {
    return http.post(
      url,
      headers: await _headers(),
      body: body,
    );
  }

  // ===============================
  // POST (JSON)
  // ===============================
  static Future<http.Response> postJson(
    Uri url, {
    required Map<String, dynamic> body,
  }) async {
    return http.post(
      url,
      headers: await _headers(json: true),
      body: jsonEncode(body),
    );
  }

  // ===============================
  // PUT (JSON)
  // ===============================
  static Future<http.Response> putJson(
    Uri url, {
    required Map<String, dynamic> body,
  }) async {
    return http.put(
      url,
      headers: await _headers(json: true),
      body: jsonEncode(body),
    );
  }

  // ===============================
  // DELETE
  // ===============================
  static Future<http.Response> delete(Uri url) async {
    return http.delete(
      url,
      headers: await _headers(),
    );
  }
}
