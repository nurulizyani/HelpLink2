import 'dart:async';
import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';

import 'login_screen.dart';
import 'verify_email_screen.dart';
import 'package:part_1/utils/route_animation.dart';
import 'package:part_1/theme/app_colors.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _address = TextEditingController();
  final _pass = TextEditingController();
  final _confirm = TextEditingController();

  bool _obscurePass = true;
  bool _obscureConfirm = true;
  bool _loading = false;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _address.dispose();
    _pass.dispose();
    _confirm.dispose();
    super.dispose();
  }

  void _showSnack(String text, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(text),
        backgroundColor:
            error ? AppColors.error : AppColors.success,
      ),
    );
  }

  // ===============================
  // REGISTER FLOW (FINAL & SAFE)
  // ===============================
  Future<void> _register() async {
    final name = _name.text.trim();
    final email = _email.text.trim();
    final phone = _phone.text.trim();
    final address = _address.text.trim();
    final pass = _pass.text.trim();
    final confirm = _confirm.text.trim();

    if ([name, email, phone, address, pass, confirm]
        .any((v) => v.isEmpty)) {
      _showSnack('Please fill in all fields', error: true);
      return;
    }

    if (pass != confirm) {
      _showSnack('Passwords do not match', error: true);
      return;
    }

    setState(() => _loading = true);

    try {
      // 1️⃣ Create Firebase user
      final cred = await FirebaseAuth.instance
          .createUserWithEmailAndPassword(
        email: email,
        password: pass,
      );

      final user = cred.user;
      if (user == null) {
        throw Exception('Registration failed');
      }

      // 2️⃣ Save profile to Firestore
      await FirebaseFirestore.instance
          .collection('users')
          .doc(user.uid)
          .set({
        'uid': user.uid,
        'name': name,
        'email': email,
        'phone': phone,
        'address': address,
        'createdAt': FieldValue.serverTimestamp(),
      });

      // 3️⃣ Send verification email
      await user.sendEmailVerification();

      if (!mounted) return;

      _showSnack(
        'Verification email sent to $email',
      );

      // 4️⃣ Go to verify screen (USER STILL LOGGED IN)
      Navigator.pushReplacement(
        context,
        smoothRoute(
          VerifyEmailSentScreen(email: email),
        ),
      );
    } on FirebaseAuthException catch (e) {
      _showSnack(
        e.message ?? 'Registration failed',
        error: true,
      );
    } catch (e) {
      _showSnack('Error: $e', error: true);
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
              smoothRoute(const LoginScreen(), fromRight: false),
            );
          },
        ),
        title: const Text(
          'Sign Up',
          style: TextStyle(
            color: AppColors.primary,
            fontWeight: FontWeight.bold,
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
                const Icon(
                  Icons.volunteer_activism,
                  size: 72,
                  color: AppColors.primary,
                ),
                const SizedBox(height: 20),

                _field(
                  _name,
                  'Full Name',
                  Icons.person_outline,
                  helper: 'Use your real name. This cannot be changed later.',
                ),

                _field(
                  _email,
                  'Email',
                  Icons.email_outlined,
                  keyboard: TextInputType.emailAddress,
                ),
                _field(
                  _phone,
                  'Phone Number',
                  Icons.phone_outlined,
                  keyboard: TextInputType.phone,
                ),
                _field(
                  _address,
                  'Address',
                  Icons.home_outlined,
                ),

                _field(
                  _pass,
                  'Password',
                  Icons.lock_outline,
                  obscure: _obscurePass,
                  toggle: () =>
                      setState(() => _obscurePass = !_obscurePass),
                ),
                _field(
                  _confirm,
                  'Confirm Password',
                  Icons.lock_person_outlined,
                  obscure: _obscureConfirm,
                  toggle: () => setState(
                      () => _obscureConfirm = !_obscureConfirm),
                  onSubmit: (_) => _register(),
                ),

                const SizedBox(height: 30),

                _loading
                    ? const Center(
                        child: CircularProgressIndicator(
                          color: AppColors.primary,
                        ),
                      )
                    : SizedBox(
                        height: 52,
                        child: ElevatedButton(
                          onPressed: _register,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius:
                                  BorderRadius.circular(14),
                            ),
                          ),
                          child: const Text(
                            'Sign Up',
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
                    const Text('Already have an account?'),
                    TextButton(
                      onPressed: () {
                        Navigator.pushReplacement(
                          context,
                          smoothRoute(const LoginScreen()),
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

  Widget _field(
    TextEditingController c,
    String label,
    IconData icon, {
    bool obscure = false,
    VoidCallback? toggle,
    TextInputType? keyboard,
    void Function(String)? onSubmit,
    String? helper,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextField(
        controller: c,
        obscureText: obscure,
        keyboardType: keyboard,
        onSubmitted: onSubmit,
        decoration: InputDecoration(
          labelText: label,
          helperText: helper,
          prefixIcon: Icon(icon, color: AppColors.primary),
          suffixIcon: toggle != null
              ? IconButton(
                  icon: Icon(
                    obscure
                        ? Icons.visibility_off_outlined
                        : Icons.visibility_outlined,
                    color: AppColors.primary,
                  ),
                  onPressed: toggle,
                )
              : null,
          filled: true,
          fillColor: Colors.white,
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
    );
  }
}
