import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'offers/offer_list_screen.dart';
import 'requests/help_requests_list.dart';
import 'package:part_1/screens/my_help_requests.dart';
import 'requests/my_help_responses.dart';
import 'notification_screen.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  User? user;

  int myOffers = 0;
  int myRequests = 0;
  int myResponses = 0;

  bool loadingSummary = true;
  Timer? _pollingTimer;

  @override
  void initState() {
    super.initState();
    user = FirebaseAuth.instance.currentUser;

    _fetchDashboardSummary();

    // 🔁 Auto refresh every 15 seconds
    _pollingTimer = Timer.periodic(
      const Duration(seconds: 15),
      (_) => _fetchDashboardSummary(),
    );
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  // =========================
  // FETCH DASHBOARD SUMMARY (SQL)
  // =========================
  Future<void> _fetchDashboardSummary() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) return;

      final res = await http.get(
        Uri.parse('${ApiConfig.apiBase}/dashboard/summary'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);

        if (!mounted) return;
        setState(() {
          myOffers = data['my_offers'] ?? 0;
          myRequests = data['my_requests'] ?? 0;
          myResponses = data['my_responses'] ?? 0;
          loadingSummary = false;
        });
      }
    } catch (_) {
      // silent fail (dashboard still usable)
    }
  }

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;

    return Scaffold(
      backgroundColor: AppColors.background,

      // =========================
      // APP BAR
      // =========================
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        title: const Text(
          'HelpLink',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            letterSpacing: 0.5,
          ),
        ),
        actions: [
          _notificationButton(context),
        ],
      ),

      // =========================
      // BODY
      // =========================
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _greeting(text),

            const SizedBox(height: 28),

            // =========================
            // SUMMARY (SQL-BASED)
            // =========================
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _summaryCardUI(
                  text,
                  Icons.volunteer_activism_outlined,
                  'My Offers',
                  myOffers,
                ),
                _summaryCardUI(
                  text,
                  Icons.help_outline,
                  'My Requests',
                  myRequests,
                ),
                _summaryCardUI(
                  text,
                  Icons.handshake_outlined,
                  'Responses',
                  myResponses,
                ),
              ],
            ),

            const SizedBox(height: 36),

            // =========================
            // QUICK ACTIONS
            // =========================
            Text(
              'Quick Actions',
              style: text.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 14),

            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              crossAxisSpacing: 14,
              mainAxisSpacing: 14,
              childAspectRatio: 1.1,
              children: [
                _actionTile(
                  icon: Icons.volunteer_activism_outlined,
                  title: 'View Offers',
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const OfferListScreen(),
                      ),
                    );
                  },
                ),
                _actionTile(
                  icon: Icons.help_outline,
                  title: 'View Requests',
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            const HelpRequestsListScreen(),
                      ),
                    );
                  },
                ),
                _actionTile(
                  icon: Icons.folder_open_outlined,
                  title: 'My Requests',
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            const MyHelpRequestsScreen(),
                      ),
                    );
                  },
                ),
                _actionTile(
                  icon: Icons.handshake_outlined,
                  title: 'My Responses',
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            const MyHelpResponsesScreen(),
                      ),
                    );
                  },
                ),
              ],
            ),

            const SizedBox(height: 36),

            _infoCard(
              title: 'Community is active',
              desc:
                  'New offers and requests are updated automatically from the system.',
            ),
            _infoCard(
              title: 'Live dashboard',
              desc:
                  'This dashboard refreshes every 15 seconds to reflect the latest data.',
            ),
          ],
        ),
      ),
    );
  }

  // =========================
  // GREETING (FIRESTORE)
  // =========================
  Widget _greeting(TextTheme text) {
    if (user == null) {
      return Text(
        'Hi there',
        style: text.headlineSmall?.copyWith(
          fontWeight: FontWeight.bold,
          color: AppColors.textPrimary,
        ),
      );
    }

    return StreamBuilder<DocumentSnapshot>(
      stream: FirebaseFirestore.instance
          .collection('users')
          .doc(user!.uid)
          .snapshots(),
      builder: (context, snapshot) {
        final name = snapshot.data?.get('name');

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Hi, ${name ?? 'there'}',
              style: text.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Let’s help each other today.',
              style: text.bodyMedium?.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
        );
      },
    );
  }

  // =========================
  // NOTIFICATIONS (FIRESTORE)
  // =========================
  Widget _notificationButton(BuildContext context) {
    if (user == null) {
      return IconButton(
        icon: const Icon(Icons.notifications_none),
        onPressed: () {},
      );
    }

    return StreamBuilder<QuerySnapshot>(
      stream: FirebaseFirestore.instance
          .collection('notifications')
          .where('userId', isEqualTo: user!.uid)
          .where('isRead', isEqualTo: false)
          .snapshots(),
      builder: (context, snapshot) {
        final count = snapshot.data?.docs.length ?? 0;

        return Stack(
          children: [
            IconButton(
              icon: const Icon(Icons.notifications_none),
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) =>
                        const NotificationScreen(),
                  ),
                );
              },
            ),
            if (count > 0)
              Positioned(
                right: 8,
                top: 8,
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.red,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  constraints: const BoxConstraints(
                    minWidth: 18,
                    minHeight: 18,
                  ),
                  child: Text(
                    count.toString(),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
          ],
        );
      },
    );
  }

  // =========================
  // UI COMPONENTS
  // =========================
  Widget _summaryCardUI(
    TextTheme text,
    IconData icon,
    String label,
    int count,
  ) {
    return Container(
      width: 105,
      height: 110,
      decoration: BoxDecoration(
        color: AppColors.accent,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: AppColors.primary),
          const SizedBox(height: 6),
          Text(
            count.toString(),
            style: text.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
          Text(
            label,
            style: text.bodySmall?.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _actionTile({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon,
                color: AppColors.primary, size: 30),
            const SizedBox(height: 10),
            Text(
              title,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoCard({required String title, required String desc}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            desc,
            style: const TextStyle(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
