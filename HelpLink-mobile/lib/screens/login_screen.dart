import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:http/http.dart' as http;
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:part_1/app_shell.dart';
import 'package:part_1/screens/register_screen.dart';
import 'verify_email_screen.dart';
import 'forgot_password_screen.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/utils/route_animation.dart';
import 'package:part_1/theme/app_colors.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _email = TextEditingController();
  final TextEditingController _pass = TextEditingController();

  bool _obscure = true;
  bool _loading = false;

  @override
  void dispose() {
    _email.dispose();
    _pass.dispose();
    super.dispose();
  }

  // ===============================
  // UI HELPERS
  // ===============================
  void _showSnack(String msg, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: isError ? AppColors.error : AppColors.success,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  Future<void> _safeLogoutFirebase() async {
    try {
      await FirebaseAuth.instance.signOut();
    } catch (_) {}
  }

  // ===============================
  // SYNC FIRESTORE → LARAVEL USER (BEST EFFORT)
  // ===============================
  Future<void> _syncUserToLaravel(String uid) async {
  try {
    final doc = await FirebaseFirestore.instance
        .collection('users')
        .doc(uid)
        .get();

    if (!doc.exists) return;

    final data = doc.data() ?? {};
    final email = (data['email'] ?? '').toString();
    if (email.trim().isEmpty) return;

    final res = await http.post(
      Uri.parse(ApiConfig.syncUser),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'firebase_uid': uid,
        'name': (data['name'] ?? '').toString(),
        'email': email,
        'phone': (data['phone'] ?? '').toString(),
        'address': (data['address'] ?? '').toString(),
      }),
    );

    debugPrint('SYNC USER STATUS: ${res.statusCode}');
  } catch (e) {
    debugPrint('SYNC USER ERROR: $e');
  }
}


  // ===============================
  // LARAVEL SANCTUM LOGIN (REQUIRED)
  // ===============================
  Future<void> _loginToLaravelWithSanctum(String idToken) async {
    final res = await http
        .post(
          Uri.parse('${ApiConfig.apiBase}/auth/firebase-login'),
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: jsonEncode({'id_token': idToken}),
        )
        .timeout(const Duration(seconds: 12));

    if (res.statusCode != 200) {
      throw Exception('Backend login failed (${res.statusCode}).');
    }

    final decoded = jsonDecode(res.body);
    final token = decoded['token'];
    final userId = decoded['user']?['id'];

    if (token == null || token.toString().trim().isEmpty) {
      throw Exception('Backend returned empty token.');
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_id');

    await prefs.setString('auth_token', token.toString());

    if (userId != null) {
      final parsed = int.tryParse(userId.toString());
      if (parsed != null) {
        await prefs.setInt('user_id', parsed);
      }
    }
  }

  // ===============================
  // SAVE FCM TOKEN (MATCH BACKEND VALIDATION)
  // Backend kau: validate user_id + fcm_token
  // ===============================
  Future<void> _registerFcmToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final authToken = prefs.getString('auth_token');
      final userId = prefs.getInt('user_id');

      if (authToken == null || authToken.isEmpty) return;
      if (userId == null) return;

      final fcmToken = await FirebaseMessaging.instance.getToken();
      if (fcmToken == null || fcmToken.isEmpty) return;

      final lastSent = prefs.getString('last_fcm_token_sent');
      if (lastSent == fcmToken) return;

      final res = await http
          .post(
            Uri.parse('${ApiConfig.apiBase}/save-fcm-token'),
            headers: {
              'Authorization': 'Bearer $authToken',
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: jsonEncode({
              'user_id': userId,
              'fcm_token': fcmToken,
            }),
          )
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200 || res.statusCode == 201) {
        await prefs.setString('last_fcm_token_sent', fcmToken);
      }
    } catch (_) {
      // non-blocking
    }
  }

  // ===============================
  // EMAIL VERIFICATION (AUTO-SEND + REDIRECT)
  // ===============================
  Future<void> _handleUnverified(User user, String fallbackEmail) async {
    // refresh user state
    await user.reload();
    final freshUser = FirebaseAuth.instance.currentUser;

    if (freshUser == null) {
      throw Exception('Session expired. Please login again.');
    }

    if (freshUser.emailVerified) return;

    // try send (jika cooldown/too-many-requests, still redirect)
    try {
      await freshUser.sendEmailVerification();
    } on FirebaseAuthException catch (e) {
      if (e.code != 'too-many-requests') {
        _showSnack('Unable to send verification email right now.', isError: true);
      }
    } catch (_) {}

    if (!mounted) return;

    Navigator.pushReplacement(
      context,
      smoothRoute(
        VerifyEmailSentScreen(
          email: freshUser.email ?? fallbackEmail,
        ),
      ),
    );
  }

  // ===============================
  // LOGIN FLOW (PERFECT VERSION)
  // ===============================
  Future<void> _login() async {
    final email = _email.text.trim();
    final pass = _pass.text.trim();

    if (email.isEmpty || pass.isEmpty) {
      _showSnack('Please fill in all fields', isError: true);
      return;
    }

    if (_loading) return;
    setState(() => _loading = true);

    try {
      // 1) Firebase login
      final cred = await FirebaseAuth.instance
          .signInWithEmailAndPassword(email: email, password: pass)
          .timeout(const Duration(seconds: 15));

      User? user = cred.user;
      if (user == null) {
        throw Exception('Login failed. Please try again.');
      }

      // 2) Refresh status
      await user.reload();
      user = FirebaseAuth.instance.currentUser;

      if (user == null) {
        throw Exception('Session expired. Please login again.');
      }

      // 3) Not verified → auto send + redirect
      if (!user.emailVerified) {
        await _handleUnverified(user, email);
        return;
      }

      // 4) Sync Firestore → Laravel (best effort)
      await _syncUserToLaravel(user.uid);

      // 5) Get Firebase ID token
      final String? idToken = await user.getIdToken(true);

      if (idToken == null || idToken.trim().isEmpty) {
        throw Exception('Failed to get Firebase ID token');
      }


      // 6) Sanctum login (required)
      await _loginToLaravelWithSanctum(idToken);

      // 7) Save FCM token (non-blocking, now matches backend)
      await _registerFcmToken();

      if (!mounted) return;

      Navigator.pushReplacement(
        context,
        smoothRoute(const AppShell()),
      );
    } on TimeoutException {
      _showSnack('Network timeout. Please try again.', isError: true);
    } on FirebaseAuthException catch (e) {
      String msg = 'Login failed';
      if (e.code == 'user-not-found') msg = 'No user found with this email';
      if (e.code == 'wrong-password') msg = 'Wrong password';
      if (e.code == 'invalid-email') msg = 'Invalid email format';
      _showSnack(msg, isError: true);
    } catch (e) {
      _showSnack(e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ===============================
  // UI
  // ===============================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(28),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Icon(
                  Icons.volunteer_activism,
                  size: 72,
                  color: AppColors.primary,
                ),
                const SizedBox(height: 8),
                const Text(
                  'HelpLink',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 40),

                _inputField(
                  controller: _email,
                  label: 'Email',
                  icon: Icons.email_outlined,
                ),
                const SizedBox(height: 14),

                TextField(
                  controller: _pass,
                  obscureText: _obscure,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    prefixIcon: const Icon(
                      Icons.lock_outline,
                      color: AppColors.primary,
                    ),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscure
                            ? Icons.visibility_off_outlined
                            : Icons.visibility_outlined,
                        color: AppColors.primary,
                      ),
                      onPressed: () => setState(() => _obscure = !_obscure),
                    ),
                    filled: true,
                    fillColor: AppColors.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),

                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: () {
                      Navigator.push(
                        context,
                        smoothRoute(const ForgotPasswordScreen()),
                      );
                    },
                    child: const Text(
                      'Forgot Password?',
                      style: TextStyle(color: AppColors.primary),
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                _loading
                    ? const Center(
                        child: CircularProgressIndicator(
                          color: AppColors.primary,
                        ),
                      )
                    : SizedBox(
                        height: 52,
                        child: ElevatedButton(
                          onPressed: _login,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          child: const Text(
                            'Login',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),

                const SizedBox(height: 20),

                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text("Don't have an account? "),
                    GestureDetector(
                      onTap: () {
                        Navigator.push(
                          context,
                          smoothRoute(const RegisterScreen()),
                        );
                      },
                      child: const Text(
                        'Register',
                        style: TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 10),
                TextButton(
                  onPressed: () async {
                    await _safeLogoutFirebase();
                    _showSnack('Signed out. Try login again.');
                  },
                  child: const Text(
                    'Having trouble? Reset session',
                    style: TextStyle(color: AppColors.textSecondary),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _inputField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
  }) {
    return TextField(
      controller: controller,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: AppColors.primary),
        filled: true,
        fillColor: AppColors.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}
