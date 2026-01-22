import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';
import 'package:part_1/screens/chat/chat_api_service.dart';
import 'package:part_1/screens/chat/chat_room_screen.dart';

class OfferDetailScreen extends StatefulWidget {
  final Map<String, dynamic> offer;

  const OfferDetailScreen({
    super.key,
    required this.offer,
  });

  @override
  State<OfferDetailScreen> createState() => _OfferDetailScreenState();
}

class _OfferDetailScreenState extends State<OfferDetailScreen> {
  bool _loading = true;
  bool _claiming = false;
  bool _openingChat = false;

  Map<String, dynamic>? _offer;

  final ChatApiService _chatApi = ChatApiService();

  @override
  void initState() {
    super.initState();
    _fetchDetail();
  }

  // ================= FETCH DETAIL =================
  Future<void> _fetchDetail() async {
    try {
      final id = widget.offer['offer_id'] ?? widget.offer['id'];
      final res = await http.get(
        Uri.parse('${ApiConfig.apiBase}/offers/$id'),
        headers: {'Accept': 'application/json'},
      );

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 200 && decoded['success'] == true) {
        _offer = decoded['data'];
      }
    } catch (_) {
      //
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= TOKEN =================
  Future<String?> _token() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  // ================= CLAIM + CHAT =================
  Future<void> _claimAndChat(
    BuildContext context,
    int offerId,
    int ownerId,
    String ownerName,
  ) async {
    if (_claiming) return;
    setState(() => _claiming = true);

    try {
      final token = await _token();
      if (token == null) throw Exception('Please login first');

      await http.post(
        Uri.parse('${ApiConfig.apiBase}/claim-offers/store'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: {'offer_id': offerId.toString()},
      );

      await _openChat(context, offerId, ownerId, ownerName);
    } catch (e) {
      _snack(e.toString(), error: true);
    } finally {
      if (mounted) setState(() => _claiming = false);
    }
  }

  // ================= CHAT ONLY =================
  Future<void> _openChat(
    BuildContext context,
    int offerId,
    int ownerId,
    String ownerName,
  ) async {
    if (_openingChat) return;
    setState(() => _openingChat = true);

    try {
      final chat = await _chatApi.getOrCreateConversation(
        otherUserId: ownerId,
        offerId: offerId,
      );

      if (!context.mounted) return;

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => ChatRoomScreen(
            conversationId: chat['id'],
            otherUserName: ownerName,
          ),
        ),
      );
    } catch (e) {
      _snack(e.toString(), error: true);
    } finally {
      if (mounted) setState(() => _openingChat = false);
    }
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: error ? AppColors.error : AppColors.success,
      ),
    );
  }

  // ================= IMAGE =================
  String? _imageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http')) return path;
    return '${ApiConfig.baseUrl}/${path.startsWith('storage') ? path : 'storage/$path'}';
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_offer == null) {
      return const Scaffold(
        body: Center(child: Text('Offer not found')),
      );
    }

    final offer = _offer!;
    final imageUrl = _imageUrl(offer['image']);
    final owner = offer['user'];

    final int offerId = offer['offer_id'];
    final int ownerId = owner['id'];
    final String ownerName = owner['name'];

    final double? lat = offer['latitude'] != null
        ? double.tryParse(offer['latitude'].toString())
        : null;
    final double? lng = offer['longitude'] != null
        ? double.tryParse(offer['longitude'].toString())
        : null;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Offer Details'),
        centerTitle: true,
      ),

      // ================= BODY =================
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 90),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (imageUrl != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: Image.network(
                  imageUrl,
                  height: 240,
                  width: double.infinity,
                  fit: BoxFit.cover,
                ),
              ),

            const SizedBox(height: 20),

            Text(
              offer['item_name'],
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),

            const SizedBox(height: 10),
            Text(offer['description'] ?? '-'),

            const SizedBox(height: 20),

            _row('Category', offer['category']),
            _row('Quantity', offer['quantity'].toString()),
            _row('Delivery', offer['delivery_type']),
            _row('Posted', offer['created_at']),

            const SizedBox(height: 24),

            Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.person_outline),
                        const SizedBox(width: 8),
                        Text(
                          ownerName,
                          style: const TextStyle(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 12),

                    GestureDetector(
                      onTap: (lat != null && lng != null)
                          ? () async {
                              final uri = Uri.parse(
                                'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
                              );
                              await launchUrl(
                                uri,
                                mode: LaunchMode.externalApplication,
                              );
                            }
                          : null,
                      child: Row(
                        children: [
                          const Icon(Icons.location_on_outlined),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              offer['address'] ?? 'No location provided',
                              style: TextStyle(
                                color: (lat != null && lng != null)
                                    ? Colors.blue
                                    : Colors.black54,
                                decoration: (lat != null && lng != null)
                                    ? TextDecoration.underline
                                    : null,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    if (lat != null && lng != null) ...[
                      const SizedBox(height: 14),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(14),
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
                  ],
                ),
              ),
            ),
          ],
        ),
      ),

      // ================= BOTTOM ACTION BAR =================
      bottomNavigationBar: _bottomOfferActionBar(
        context,
        offerId,
        ownerId,
        ownerName,
      ),
    );
  }

  // ================= BOTTOM BAR =================
  Widget _bottomOfferActionBar(
    BuildContext context,
    int offerId,
    int ownerId,
    String ownerName,
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
          // CHAT
          SizedBox(
            width: 100,
            height: 48,
            child: ElevatedButton.icon(
              onPressed: _openingChat
                  ? null
                  : () => _openChat(
                        context,
                        offerId,
                        ownerId,
                        ownerName,
                      ),
              icon: const Padding(
                padding: EdgeInsets.only(right: 2),
                child: Icon(Icons.chat_bubble_outline, size: 18),
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

          // CLAIM OFFER
          Expanded(
            child: SizedBox(
              height: 48,
              child: ElevatedButton.icon(
                icon: _claiming
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.redeem),
                label: const Text(
                  'Claim Offer',
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
                onPressed: _claiming
                    ? null
                    : () => _claimAndChat(
                          context,
                          offerId,
                          ownerId,
                          ownerName,
                        ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _row(String l, String v) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: Text(
              l,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(flex: 5, child: Text(v)),
        ],
      ),
    );
  }
}
