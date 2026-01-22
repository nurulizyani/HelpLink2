import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/screens/chat/chat_room_screen.dart';
import 'package:part_1/screens/requests/my_help_responses.dart';
import 'package:part_1/theme/app_colors.dart';

class HelpResponseDetailScreen extends StatelessWidget {
  final Map<String, dynamic> claim;

  const HelpResponseDetailScreen({
    super.key,
    required this.claim,
  });

  @override
  Widget build(BuildContext context) {
    final request = claim['request'] ?? {};

    final int claimId = claim['id'];
    final String status = claim['status'] ?? 'active';
    final int conversationId = claim['conversation_id'];

    final String title = request['item_name'] ?? 'Untitled Request';
    final String desc =
        request['description'] ?? 'No description available.';
    final String address =
        request['address'] ?? 'No address provided';
    final String category =
        request['category'] ?? 'Not specified';

    final String? lat = request['latitude']?.toString();
    final String? lng = request['longitude']?.toString();

    final String? imageUrl =
        request['image'] != null && request['image'].toString().isNotEmpty
            ? '${ApiConfig.baseUrl}/${request['image']}'
                .replaceAll('public/', 'storage/')
            : null;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Help Details'),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // IMAGE
            ClipRRect(
              borderRadius: BorderRadius.circular(18),
              child: imageUrl != null
                  ? Image.network(
                      imageUrl,
                      width: double.infinity,
                      height: 230,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) =>
                          _imagePlaceholder(),
                    )
                  : _imagePlaceholder(),
            ),

            const SizedBox(height: 20),

            // STATUS + CATEGORY
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _statusChip(status),
                _categoryChip(category),
              ],
            ),

            const SizedBox(height: 18),

            // TITLE
            Text(
              title,
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),

            const SizedBox(height: 8),

            // DESCRIPTION
            Text(
              desc,
              style: const TextStyle(
                fontSize: 15,
                color: AppColors.textSecondary,
                height: 1.45,
              ),
            ),

            const SizedBox(height: 26),

            // INFO CARD
            _infoCard(
              children: [
                _infoRow(
                  icon: Icons.location_on_outlined,
                  label: 'Address',
                  value: address,
                  lat: lat,
                  lng: lng,
                ),
                _divider(),
                _infoRow(
                  icon: Icons.person_outline,
                  label: 'Requested by',
                  value: request['user_name'] ?? 'Unknown',
                ),
              ],
            ),

            const SizedBox(height: 30),

            // ACTION BUTTONS
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.chat_bubble_outline),
                    label: const Text('Chat'),
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(double.infinity, 50),
                      foregroundColor: AppColors.primary,
                      side: const BorderSide(color: AppColors.primary),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => ChatRoomScreen(
                            conversationId: conversationId,
                            otherUserName: request['user_name'],
                          ),
                        ),
                      );
                    },
                  ),
                ),
                if (status == 'active') ...[
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      icon:
                          const Icon(Icons.check_circle_outline),
                      label: const Text('Complete'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor:
                            AppColors.success,
                        minimumSize:
                            const Size(double.infinity, 50),
                        shape: RoundedRectangleBorder(
                          borderRadius:
                              BorderRadius.circular(14),
                        ),
                      ),
                      onPressed: () =>
                          _markCompleted(context, claimId),
                    ),
                  ),
                ],
              ],
            ),

            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  // =============================
  // ACTION
  // =============================
  Future<void> _markCompleted(
    BuildContext context,
    int claimId,
  ) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) throw Exception('Not authenticated');

      final response = await http.post(
        Uri.parse(
            '${ApiConfig.apiBase}/claim-requests/complete'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'claim_id': claimId}),
      );

      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content:
                Text('Help marked as completed'),
            backgroundColor: AppColors.success,
          ),
        );

        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) =>
                const MyHelpResponsesScreen(),
          ),
        );
      } else {
        throw Exception(response.body);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  // =============================
  // UI COMPONENTS
  // =============================
  Widget _infoCard({required List<Widget> children}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(children: children),
    );
  }

  Widget _infoRow({
    required IconData icon,
    required String label,
    required String value,
    String? lat,
    String? lng,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: AppColors.primary, size: 20),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textMuted,
                ),
              ),
              const SizedBox(height: 4),
              lat != null && lng != null
                  ? GestureDetector(
                      onTap: () async {
                        final uri = Uri.parse(
                          'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
                        );
                        if (await canLaunchUrl(uri)) {
                          await launchUrl(
                            uri,
                            mode:
                                LaunchMode.externalApplication,
                          );
                        }
                      },
                      child: Text(
                        value,
                        style: const TextStyle(
                          color: AppColors.primary,
                          decoration:
                              TextDecoration.underline,
                          fontSize: 14,
                        ),
                      ),
                    )
                  : Text(
                      value,
                      style: const TextStyle(
                        fontSize: 14,
                        color:
                            AppColors.textPrimary,
                      ),
                    ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _divider() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 10),
      child: Divider(height: 1),
    );
  }

  Widget _imagePlaceholder() {
    return Container(
      height: 230,
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(18),
      ),
      alignment: Alignment.center,
      child: const Icon(
        Icons.receipt_long_rounded,
        color: Colors.white70,
        size: 60,
      ),
    );
  }

  Widget _categoryChip(String category) {
    return Chip(
      label: Text(
        category,
        style: const TextStyle(
          color: AppColors.primary,
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
      ),
      backgroundColor:
          AppColors.primary.withOpacity(0.12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
    );
  }

  Widget _statusChip(String status) {
    return Chip(
      label: Text(
        _capitalize(status),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
        ),
      ),
      backgroundColor: _statusColor(status),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
    );
  }

  static Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'completed':
        return AppColors.success;
      case 'cancelled':
        return AppColors.danger;
      default:
        return AppColors.warning;
    }
  }

  static String _capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() +
        text.substring(1).toLowerCase();
  }
}
