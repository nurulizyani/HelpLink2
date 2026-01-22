import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';

// CHAT
import 'package:part_1/screens/chat/chat_api_service.dart';
import 'package:part_1/screens/chat/chat_room_screen.dart';

class ClaimOfferDetailScreen extends StatelessWidget {
  final Map<String, dynamic> claim;

  const ClaimOfferDetailScreen({
    super.key,
    required this.claim,
  });

  // ===============================
  // IMAGE NORMALIZER (FIXED)
  // ===============================
  String? _imageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http')) return path;
    return '${ApiConfig.baseUrl}/storage/$path';
  }

  @override
  Widget build(BuildContext context) {
    final offer = (claim['offer'] ?? {}) as Map<String, dynamic>;

    final String title =
        (offer['item_name'] ?? 'Unnamed Offer').toString();
    final String desc =
        (offer['description'] ?? 'No description provided').toString();

    final String status =
        (claim['status'] ?? 'active').toString().toLowerCase();

    final String address =
        (offer['address'] ?? 'No address provided').toString();

    final double? lat = offer['latitude'] != null
        ? double.tryParse(offer['latitude'].toString())
        : null;

    final double? lng = offer['longitude'] != null
        ? double.tryParse(offer['longitude'].toString())
        : null;

    final imageUrl = _imageUrl(offer['image']);

    final owner = offer['user'] as Map<String, dynamic>?;
    final String ownerName =
        (owner?['name'] ?? 'Unknown').toString();

    final int? ownerId = _asInt(owner?['id']);
    final int? offerId =
        _asInt(offer['offer_id'] ?? offer['id']);

    final chatApi = ChatApiService();

    Color statusColor;
    switch (status) {
      case 'completed':
        statusColor = AppColors.success;
        break;
      case 'cancelled':
        statusColor = AppColors.error;
        break;
      case 'received':
        statusColor = Colors.blueAccent;
        break;
      default:
        statusColor = AppColors.warning;
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Claimed Offer Details'),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ===============================
            // IMAGE
            // ===============================
            if (imageUrl != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(18),
                child: Image.network(
                  imageUrl,
                  height: 220,
                  width: double.infinity,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    height: 220,
                    color: AppColors.surface,
                    alignment: Alignment.center,
                    child:
                        const Icon(Icons.broken_image, size: 40),
                  ),
                ),
              ),

            const SizedBox(height: 24),

            // ===============================
            // TITLE & DESC
            // ===============================
            Text(
              title,
              style: Theme.of(context)
                  .textTheme
                  .headlineSmall
                  ?.copyWith(fontWeight: FontWeight.bold),
            ),

            const SizedBox(height: 8),

            Text(
              desc,
              style: Theme.of(context)
                  .textTheme
                  .bodyMedium
                  ?.copyWith(height: 1.5),
            ),

            const SizedBox(height: 24),

            // ===============================
            // INFO CARD
            // ===============================
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(18),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.06),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _infoRow(
                    Icons.person_outline,
                    'Offer Owner',
                    ownerName,
                  ),
                  const SizedBox(height: 10),
                  _infoRow(
                    Icons.info_outline,
                    'Status',
                    status.toUpperCase(),
                    color: statusColor,
                  ),

                  const SizedBox(height: 16),

                  // ===============================
                  // ADDRESS (CLICKABLE)
                  // ===============================
                  Row(
                    children: const [
                      Icon(Icons.location_on_outlined,
                          size: 20),
                      SizedBox(width: 8),
                      Text(
                        'Address',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: Colors.black54,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  GestureDetector(
                    onTap: (lat != null && lng != null)
                        ? () async {
                            final uri = Uri.parse(
                              'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
                            );
                            await launchUrl(
                              uri,
                              mode: LaunchMode
                                  .externalApplication,
                            );
                          }
                        : null,
                    child: Text(
                      address,
                      style: TextStyle(
                        color: (lat != null && lng != null)
                            ? Colors.blueAccent
                            : Colors.black87,
                        decoration:
                            (lat != null && lng != null)
                                ? TextDecoration.underline
                                : TextDecoration.none,
                        fontSize: 15,
                      ),
                    ),
                  ),

                  // ===============================
                  // MINI MAP PREVIEW (READ-ONLY)
                  // ===============================
                  if (lat != null && lng != null) ...[
                    const SizedBox(height: 14),
                    ClipRRect(
                      borderRadius:
                          BorderRadius.circular(14),
                      child: SizedBox(
                        height: 180,
                        child: FlutterMap(
                          options: MapOptions(
                            initialCenter:
                                LatLng(lat, lng),
                            initialZoom: 13,
                            interactiveFlags:
                                InteractiveFlag.none,
                          ),
                          children: [
                            TileLayer(
                              urlTemplate:
                                  'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                              userAgentPackageName:
                                  'com.example.part_1',
                            ),
                            MarkerLayer(
                              markers: [
                                Marker(
                                  point:
                                      LatLng(lat, lng),
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
                ],
              ),
            ),

            const SizedBox(height: 32),

            // ===============================
            // CHAT BUTTON
            // ===============================
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                icon: const Icon(Icons.chat_bubble_outline),
                label:
                    const Text('Chat with Offer Owner'),
                onPressed: () async {
                  if (ownerId == null ||
                      offerId == null) {
                    _snack(
                      context,
                      'Invalid offer data',
                      error: true,
                    );
                    return;
                  }

                  try {
                    final chat = await chatApi
                        .getOrCreateConversation(
                      otherUserId: ownerId,
                      offerId: offerId,
                    );

                    final convoId =
                        _asInt(chat['id']);
                    if (convoId == null) {
                      _snack(
                        context,
                        'Chat error',
                        error: true,
                      );
                      return;
                    }

                    if (!context.mounted) return;

                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            ChatRoomScreen(
                          conversationId: convoId,
                          otherUserName: ownerName,
                        ),
                      ),
                    );
                  } catch (e) {
                    _snack(context, e.toString(),
                        error: true);
                  }
                },
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                      vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius:
                        BorderRadius.circular(16),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(
    IconData icon,
    String label,
    String value, {
    Color? color,
  }) {
    return Row(
      children: [
        Icon(icon, color: AppColors.primary, size: 20),
        const SizedBox(width: 12),
        Text(
          '$label: ',
          style: const TextStyle(
            fontSize: 15,
            color: Colors.black54,
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 15,
              color: color ?? Colors.black87,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }

  void _snack(BuildContext context, String msg,
      {bool error = false}) {
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor:
            error ? AppColors.error : AppColors.success,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  int? _asInt(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    return int.tryParse(v.toString());
  }
}
