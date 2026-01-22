import 'package:http/http.dart' as http;

Future<void> testConnection() async {
  const testUrl = "http://10.82.147.187:8000/api/ping"; // tukar ikut IP PC Laravel
  try {
    print("🌐 Testing connection to Laravel...");
    final response = await http.get(Uri.parse(testUrl));
    print("✅ Status: ${response.statusCode}");
    print("📦 Body: ${response.body}");
  } catch (e) {
    print("❌ Connection failed: $e");
  }
}
