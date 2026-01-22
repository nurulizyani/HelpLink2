import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:url_launcher/url_launcher.dart';

import 'edit_profile_screen.dart';
import 'login_screen.dart';
import 'package:part_1/theme/app_colors.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  // ================= CONTACT ADMIN =================
  Future<void> _contactAdmin() async {
    final uri = Uri.parse(
      'mailto:helplink.support@gmail.com?subject=Profile Update Request',
    );
    await launchUrl(uri);
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text(
              'Logout',
              style: TextStyle(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await FirebaseAuth.instance.signOut();
      if (!context.mounted) return;
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (_) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Profile'),
        centerTitle: true,
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),

      body: StreamBuilder<DocumentSnapshot<Map<String, dynamic>>>(
      stream: FirebaseFirestore.instance
          .collection('users')
          .doc(FirebaseAuth.instance.currentUser!.uid)
          .snapshots(),
      builder: (context, snapshot) {

          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppColors.primary),
            );
          }

          if (!snapshot.hasData || !snapshot.data!.exists) {
            return const Center(child: Text('No user data found.'));
          }

          final data = snapshot.data!.data()!;
          final String? photoUrl = data['photoUrl'];

          return Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      // ================= HEADER =================
                      CircleAvatar(
                        radius: 55,
                        backgroundColor:
                            AppColors.primary.withOpacity(0.2),
                        backgroundImage:
                            photoUrl != null ? NetworkImage(photoUrl) : null,
                        child: photoUrl == null
                            ? const Icon(
                                Icons.person,
                                size: 65,
                                color: AppColors.primary,
                              )
                            : null,
                      ),

                      const SizedBox(height: 20),

                      Text(
                        data['name'] ?? 'No Name',
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textPrimary,
                        ),
                      ),

                      const SizedBox(height: 6),

                      Text(
                        data['email'] ?? 'No Email',
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                        ),
                      ),

                      const SizedBox(height: 24),

                      // ================= INFO =================
                      _infoCard(
                        icon: Icons.phone,
                        label: data['phone'] ?? 'No phone number',
                      ),

                      _infoCard(
                        icon: Icons.home,
                        label: data['address'] ?? 'No address',
                      ),

                      const SizedBox(height: 28),

                      // ================= ACCOUNT & SUPPORT =================
                      _sectionTitle('Account & Support'),

                      _actionTile(
                        icon: Icons.edit,
                        title: 'Edit Profile',
                        subtitle: 'Update phone number or address',
                        onTap: () async {
                          await Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  EditProfileScreen(userData: data),
                            ),
                          );
                        },
                      ),

                      _actionTile(
                        icon: Icons.support_agent,
                        title: 'Contact Admin',
                        subtitle: 'Need to updated your account details?',
                        onTap: _contactAdmin,
                      ),
                    ],
                  ),
                ),
              ),

              // ================= LOGOUT =================
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () => _confirmLogout(context),
                    icon: const Icon(Icons.logout),
                    label: const Text(
                      'Logout',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.danger,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  // ================= UI HELPERS =================

  Widget _sectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Text(
          title,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
        ),
      ),
    );
  }

  Widget _actionTile({
    required IconData icon,
    required String title,
    String? subtitle,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            blurRadius: 6,
            color: Colors.black.withOpacity(0.05),
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: ListTile(
        leading: Icon(icon, color: AppColors.primary),
        title: Text(title),
        subtitle: subtitle != null ? Text(subtitle) : null,
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }

  Widget _infoCard({required IconData icon, required String label}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            blurRadius: 6,
            color: Colors.black.withOpacity(0.05),
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: ListTile(
        leading: Icon(icon, color: AppColors.primary),
        title: Text(
          label,
          style: const TextStyle(color: AppColors.textPrimary),
        ),
      ),
    );
  }
}
