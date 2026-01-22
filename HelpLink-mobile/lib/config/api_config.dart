// lib/config/api_config.dart


class ApiConfig {
  // ===============================
  // BASE CONFIG (NETWORK SAFE)
  // ===============================
  static const String _baseIp = "172.20.10.4"; // IP WiFi laptop (ipconfig)
  static const String _port = "8000";

  // ===============================
  // BASE URL
  // ===============================
  static const String baseUrl = "http://$_baseIp:$_port";
  static const String apiBase = "$baseUrl/api";

  // ===============================
  // AUTH & USER
  // ===============================
  static const String syncUser = "$apiBase/sync-user";

  // ===============================
  // OFFER ROUTES
  // ===============================
  static const String offers = "$apiBase/offers"; // GET (list) & POST (create)
}
