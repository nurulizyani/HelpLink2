import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';

// CHAT
import 'package:part_1/screens/chat/chat_api_service.dart';
import 'package:part_1/screens/chat/chat_room_screen.dart';

class HelpRequestDetailScreen extends StatefulWidget {
  final Map<String, dynamic> request;

  const HelpRequestDetailScreen({
    super.key,
    required this.request,
  });

  @override
  State<HelpRequestDetailScreen> createState() =>
      _HelpRequestDetailScreenState();
}

class _HelpRequestDetailScreenState extends State<HelpRequestDetailScreen> {
  bool _helping = false;
  bool _openingChat = false;

  @override
  Widget build(BuildContext context) {
    final req = widget.request;

    final int requestId =
        _asInt(req['id']) ?? _asInt(req['request_id']) ?? 0;

    final String itemName =
        (req['item_name'] ?? 'Request Details').toString();

    final String description =
        (req['description'] ?? 'No description provided').toString();

    final String category = (req['category'] ?? 'Others').toString();
    final String address =
        (req['address'] ?? 'No location provided').toString();

    final String status = (req['status'] ?? 'pending').toString();

    // 🔴 ADMIN REMARK (REJECT ONLY)
    final String? adminRemark =
        req['admin_remark']?.toString();

    final String? imagePath = req['image']?.toString();

    final double? lat = req['latitude'] != null
        ? double.tryParse(req['latitude'].toString())
        : null;

    final double? lng = req['longitude'] != null
        ? double.tryParse(req['longitude'].toString())
        : null;

    final Map<String, dynamic>? userObj =
        req['user'] is Map ? Map<String, dynamic>.from(req['user']) : null;

    final String userName =
        (userObj?['name'] ?? req['user_name'] ?? 'User').toString();

    final int? otherUserId =
        _asInt(userObj?['id']) ?? _asInt(req['user_id']);

    final bool isApproved = status.toLowerCase() == 'approved';
    final bool isRejected = status.toLowerCase() == 'rejected';

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(itemName),
        centerTitle: true,
      ),

      // ================= BODY =================
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 90),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // IMAGE
            ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: Container(
                height: 220,
                color: AppColors.accent.withOpacity(0.2),
                child: (imagePath != null && imagePath.isNotEmpty)
                    ? Image.network(
                        _fileUrl(imagePath),
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) =>
                            _placeholderImage(),
                      )
                    : _placeholderImage(),
              ),
            ),

            const SizedBox(height: 18),

            Row(
              children: [
                _categoryChip(category),
                const SizedBox(width: 10),
                _statusChip(status),
              ],
            ),

            // 🔴 ADMIN REMARK UI (ONLY WHEN REJECTED)
            if (isRejected &&
                adminRemark != null &&
                adminRemark.isNotEmpty) ...[
              const SizedBox(height: 22),
              _sectionTitle('Reason for Rejection'),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.error.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: AppColors.error.withOpacity(0.3),
                  ),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.info_outline,
                      color: AppColors.error,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        adminRemark,
                        style: const TextStyle(
                          fontWeight: FontWeight.w500,
                          height: 1.4,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            const SizedBox(height: 22),

            _sectionTitle('Description'),
            const SizedBox(height: 8),
            _infoCard(description),

            const SizedBox(height: 22),

            _sectionTitle('Location'),
            const SizedBox(height: 8),

            GestureDetector(
              onTap: (lat != null && lng != null)
                  ? () async {
                      final uri = Uri.parse(
                        'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
                      );
                      await launchUrl(uri,
                          mode: LaunchMode.externalApplication);
                    }
                  : null,
              child: _locationCard(
                address,
                clickable: lat != null && lng != null,
              ),
            ),

            if (lat != null && lng != null) ...[
              const SizedBox(height: 14),
              ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: SizedBox(
                  height: 180,
                  child: FlutterMap(
                    options: MapOptions(
                      initialCenter: LatLng(lat, lng),
                      initialZoom: 13,
                      interactiveFlags: InteractiveFlag.none,
                    ),
                    children: [
                      TileLayer(
                        urlTemplate:
                            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        userAgentPackageName: 'com.example.part_1',
                      ),
                      MarkerLayer(
                        markers: [
                          Marker(
                            point: LatLng(lat, lng),
                            width: 40,
                            height: 40,
                            child: const Icon(
                              Icons.location_pin,
                              color: Colors.red,
                              size: 40,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],

            const SizedBox(height: 26),

            if (isApproved)
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.verified,
                        color: AppColors.success),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'This request has been reviewed and approved by admin.',
                        style: TextStyle(
                          color: AppColors.success,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),

      // ================= SHOPEE-STYLE BOTTOM BAR =================
      bottomNavigationBar:
          (isApproved && requestId != 0 && otherUserId != null)
              ? _bottomActionBar(
                  context,
                  requestId,
                  otherUserId,
                  userName,
                )
              : null,
    );
  }

  // ================= BOTTOM ACTION BAR =================
  Widget _bottomActionBar(
    BuildContext context,
    int requestId,
    int otherUserId,
    String otherUserName,
  ) {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Row(
      children: [
        // CHAT (SOFT FILLED – SAME STYLE FAMILY)
        SizedBox(
          width: 100,
          height: 48,
          child: ElevatedButton.icon(
            onPressed: _openingChat
                ? null
                : () => _openChatOnly(
                      context,
                      requestId,
                      otherUserId,
                      otherUserName,
                    ),
            icon: const Padding(
  padding: EdgeInsets.only(right: 2),
  child: Icon(
    Icons.chat_bubble_outline,
    size: 18,
  ),
),

            label: const Text(
              'Chat',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
            style: ElevatedButton.styleFrom(
              elevation: 0,
              backgroundColor: AppColors.primary.withOpacity(0.12),
              foregroundColor: AppColors.primary,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
          ),
        ),

          const SizedBox(width: 12),

          // HELP REQUEST (PRIMARY)
        Expanded(
          child: SizedBox(
            height: 48,
            child: ElevatedButton.icon(
              icon: _helping
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.volunteer_activism),
              label: const Text(
                'Help Request',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 15,
                ),
              ),
              style: ElevatedButton.styleFrom(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              onPressed: _helping
                  ? null
                  : () => _helpAndOpenChat(
                        context,
                        requestId,
                        otherUserId,
                        otherUserName,
                      ),
            ),
          ),
        ),
      ],
    ),
  );
}

  // ================= HELP + CHAT =================
  Future<void> _helpAndOpenChat(
    BuildContext context,
    int requestId,
    int otherUserId,
    String otherUserName,
  ) async {
    setState(() => _helping = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Unauthenticated');

      await http.post(
        Uri.parse('${ApiConfig.apiBase}/claim-requests/store'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'request_id': requestId}),
      );

      await _openChatOnly(
        context,
        requestId,
        otherUserId,
        otherUserName,
      );
    } catch (e) {
      _showError(e.toString());
    } finally {
      if (mounted) setState(() => _helping = false);
    }
  }

  // ================= CHAT ONLY =================
  Future<void> _openChatOnly(
    BuildContext context,
    int requestId,
    int otherUserId,
    String otherUserName,
  ) async {
    setState(() => _openingChat = true);

    try {
      final chatApi = ChatApiService();
      final chat = await chatApi.getOrCreateConversation(
        otherUserId: otherUserId,
        requestId: requestId,
      );

      final convoId = _asInt(chat['id']);
      if (convoId == null) throw Exception('Chat not created');

      if (!context.mounted) return;

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => ChatRoomScreen(
            conversationId: convoId,
            otherUserName: otherUserName,
          ),
        ),
      );
    } catch (e) {
      _showError(e.toString());
    } finally {
      if (mounted) setState(() => _openingChat = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppColors.error,
      ),
    );
  }

  // ================= HELPERS =================
  static int? _asInt(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    return int.tryParse(v.toString());
  }

  String _fileUrl(String path) {
    final cleanPath = path.startsWith('storage/')
        ? path
        : path.replaceFirst('public/', 'storage/');
    return '${ApiConfig.baseUrl}/$cleanPath';
  }

  Widget _sectionTitle(String title) => Text(
        title,
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: AppColors.textPrimary,
        ),
      );

  Widget _infoCard(String text) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Text(text),
      );

  Widget _locationCard(String address, {bool clickable = false}) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            const Icon(Icons.location_on_outlined,
                color: AppColors.primary),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                address,
                style: TextStyle(
                  color: clickable ? Colors.blue : Colors.black87,
                  decoration:
                      clickable ? TextDecoration.underline : null,
                ),
              ),
            ),
          ],
        ),
      );

  Widget _placeholderImage() => const Center(
        child: Icon(Icons.image_not_supported, size: 60),
      );

  Widget _categoryChip(String category) => Chip(
        backgroundColor: AppColors.primary.withOpacity(0.1),
        label: Text(category),
      );

  Widget _statusChip(String status) {
  final isApproved = status.toLowerCase() == 'approved';

  return Chip(
    backgroundColor: isApproved
        ? AppColors.success.withOpacity(0.15)
        : AppColors.warning.withOpacity(0.15),
    label: Text(
      _capitalize(status),
      style: TextStyle(
        color: isApproved ? AppColors.success : AppColors.warning,
        fontWeight: FontWeight.w500,
      ),
    ),
  );
}

String _capitalize(String text) {
  if (text.isEmpty) return text;
  return text[0].toUpperCase() + text.substring(1);
}

}
