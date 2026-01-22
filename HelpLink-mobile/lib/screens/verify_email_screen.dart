import 'dart:async';
import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'login_screen.dart';
import 'package:part_1/theme/app_colors.dart';

class VerifyEmailSentScreen extends StatefulWidget {
  final String email;

  const VerifyEmailSentScreen({
    super.key,
    required this.email,
  });

  @override
  State<VerifyEmailSentScreen> createState() =>
      _VerifyEmailSentScreenState();
}

class _VerifyEmailSentScreenState
    extends State<VerifyEmailSentScreen> {

  bool _canResend = false;
  bool _checking = false;
  bool _emailSent = false;

  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _initVerification();
  }

  // =============================
  // INIT VERIFICATION (SAFE)
  // =============================
  Future<void> _initVerification() async {
    User? user = FirebaseAuth.instance.currentUser;

    if (user == null) {
      _redirectToLogin();
      return;
    }

    // 🔥 CRITICAL FIX: force reload
    await user.reload();
    user = FirebaseAuth.instance.currentUser;

    if (user == null) {
      _redirectToLogin();
      return;
    }

    if (user.emailVerified) {
      _redirectToLogin();
      return;
    }

    // ✅ Send email ONCE only
    if (!_emailSent) {
      await _sendVerificationEmail();
      _emailSent = true;
    }

    _timer = Timer.periodic(
      const Duration(seconds: 4),
      (_) => _checkVerified(),
    );
  }

  // =============================
  // SEND EMAIL (THROTTLED)
  // =============================
  Future<void> _sendVerificationEmail() async {
    try {
      final user = FirebaseAuth.instance.currentUser;
      if (user == null) return;

      await user.sendEmailVerification();

      if (!mounted) return;

      setState(() => _canResend = false);

      _showSnack(
        'Verification email sent to ${widget.email}',
        success: true,
      );

      await Future.delayed(const Duration(seconds: 15));
      if (mounted) setState(() => _canResend = true);
    } catch (e) {
      _showSnack(
        'Failed to send verification email',
        error: true,
      );
    }
  }

  // =============================
  // CHECK VERIFIED
  // =============================
  Future<void> _checkVerified() async {
    if (_checking) return;
    _checking = true;

    try {
      User? user = FirebaseAuth.instance.currentUser;
      if (user == null) {
        _redirectToLogin();
        return;
      }

      await user.reload();
      user = FirebaseAuth.instance.currentUser;

      if (user != null && user.emailVerified) {
        _timer?.cancel();

        if (!mounted) return;

        _showSnack(
          'Email verified successfully!',
          success: true,
        );

        await Future.delayed(const Duration(seconds: 1));
        _redirectToLogin();
      }
    } finally {
      _checking = false;
    }
  }

  // =============================
  // REDIRECT
  // =============================
  void _redirectToLogin() {
    if (!mounted) return;
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  // =============================
  // SNACK
  // =============================
  void _showSnack(
    String msg, {
    bool error = false,
    bool success = false,
  }) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: error
            ? AppColors.error
            : success
                ? AppColors.success
                : AppColors.primary,
      ),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  // =============================
  // UI
  // =============================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 28),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(
                  Icons.email_outlined,
                  size: 90,
                  color: AppColors.primary,
                ),
                const SizedBox(height: 24),
                const Text(
                  'Verify Your Email',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  'A verification email has been sent to:\n${widget.email}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 30),
                if (_canResend)
                  TextButton(
                    onPressed: _sendVerificationEmail,
                    child: const Text('Resend verification email'),
                  )
                else
                  const Text(
                    'You can resend after a short wait',
                    style: TextStyle(
                      fontSize: 13,
                      color: AppColors.textSecondary,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
