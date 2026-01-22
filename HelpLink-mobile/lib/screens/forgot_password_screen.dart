import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'login_screen.dart';
import 'package:part_1/theme/app_colors.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final TextEditingController _emailCtrl = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _emailCtrl.dispose();
    super.dispose();
  }

  // =============================
  // RESET PASSWORD (UNCHANGED)
  // =============================
  Future<void> _resetPassword() async {
    final email = _emailCtrl.text.trim();

    if (email.isEmpty) {
      _showSnack('Please enter your email', isError: true);
      return;
    }

    setState(() => _loading = true);

    try {
      await FirebaseAuth.instance.sendPasswordResetEmail(email: email);

      if (!mounted) return;

      _showSnack('Password reset link sent. Please check your email.');

      Navigator.pushReplacement(
        context,
        _route(const LoginScreen()),
      );
    } on FirebaseAuthException catch (e) {
      _showSnack(e.message ?? 'Failed to send reset email', isError: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showSnack(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor:
            isError ? AppColors.danger : AppColors.success,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new,
            color: AppColors.primary,
          ),
          onPressed: () {
            Navigator.pushReplacement(
              context,
              _route(const LoginScreen()),
            );
          },
        ),
        title: const Text(
          'Forgot Password',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // =============================
                // ICON
                // =============================
                const Icon(
                  Icons.lock_reset_rounded,
                  size: 72,
                  color: AppColors.primary,
                ),

                const SizedBox(height: 18),

                const Text(
                  'Reset your password',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),

                const SizedBox(height: 10),

                const Text(
                  'Enter your registered email address. We will send you a secure link to reset your password.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 14,
                    color: AppColors.textSecondary,
                    height: 1.4,
                  ),
                ),

                const SizedBox(height: 28),

                // =============================
                // EMAIL FIELD
                // =============================
                TextField(
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    labelText: 'Email Address',
                    labelStyle: const TextStyle(
                      color: AppColors.textSecondary,
                    ),
                    prefixIcon: const Icon(
                      Icons.email_outlined,
                      color: AppColors.primary,
                    ),
                    filled: true,
                    fillColor: AppColors.surface,
                    contentPadding: const EdgeInsets.symmetric(
                      vertical: 18,
                      horizontal: 16,
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(
                        color: AppColors.primary,
                        width: 1.5,
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 26),

                // =============================
                // RESET BUTTON
                // =============================
                _loading
                    ? const Center(
                        child: CircularProgressIndicator(
                          color: AppColors.primary,
                        ),
                      )
                    : SizedBox(
                        height: 52,
                        child: ElevatedButton(
                          onPressed: _resetPassword,
                          style: ElevatedButton.styleFrom(
                            elevation: 3,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          child: const Text(
                            'Send Reset Link',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),

                const SizedBox(height: 22),

                // =============================
                // BACK TO LOGIN
                // =============================
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text(
                      'Remember your password?',
                      style: TextStyle(
                        color: AppColors.textSecondary,
                      ),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.pushReplacement(
                          context,
                          _route(const LoginScreen()),
                        );
                      },
                      child: const Text(
                        'Login',
                        style: TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // =============================
  // PAGE TRANSITION (UNCHANGED)
  // =============================
  Route _route(Widget page) {
    return PageRouteBuilder(
      pageBuilder: (_, animation, __) => page,
      transitionDuration: const Duration(milliseconds: 350),
      transitionsBuilder: (_, animation, __, child) {
        final slide = Tween(
          begin: const Offset(-0.15, 0),
          end: Offset.zero,
        ).animate(
          CurvedAnimation(
            parent: animation,
            curve: Curves.easeInOut,
          ),
        );

        final fade =
            Tween(begin: 0.0, end: 1.0).animate(animation);

        return SlideTransition(
          position: slide,
          child: FadeTransition(
            opacity: fade,
            child: child,
          ),
        );
      },
    );
  }
}
