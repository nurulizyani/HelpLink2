import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/screens/requests/help_response_detail.dart';
import 'package:part_1/screens/chat/chat_room_screen.dart';
import 'package:part_1/theme/app_colors.dart';

class MyHelpResponsesScreen extends StatefulWidget {
  const MyHelpResponsesScreen({super.key});

  @override
  State<MyHelpResponsesScreen> createState() =>
      _MyHelpResponsesScreenState();
}

class _MyHelpResponsesScreenState
    extends State<MyHelpResponsesScreen> {
  bool _loading = true;
  List<dynamic> _claims = [];
  String _filterStatus = 'all';

  @override
  void initState() {
    super.initState();
    _fetchMyHelpResponses();
  }

  // ================= FETCH =================
  Future<void> _fetchMyHelpResponses() async {
    setState(() => _loading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Not authenticated');

      final uri = Uri.parse(
        '${ApiConfig.apiBase}/claim-requests/my?status=$_filterStatus',
      );

      final response = await http.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          _claims = data['data'] ?? [];
        }
      }
    } catch (_) {
      _showSnack('Failed to load help contributions', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= ACTION =================
  Future<void> _postAction({
    required String endpoint,
    required int claimId,
    required String successMsg,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Not authenticated');

      final response = await http.post(
        Uri.parse('${ApiConfig.apiBase}$endpoint'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'claim_id': claimId}),
      );

      if (response.statusCode == 200) {
        _showSnack(successMsg);
        _fetchMyHelpResponses();
      }
    } catch (_) {
      _showSnack('Action failed', error: true);
    }
  }

  void _showSnack(String msg, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor:
            error ? AppColors.error : AppColors.success,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Help Contributions'),
        centerTitle: true,
      ),
      body: Column(
        children: [
          _filterBar(),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _claims.isEmpty
                    ? _emptyState()
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _claims.length,
                        itemBuilder: (_, index) {
                          final claim = _claims[index];
                          final request = claim['request'] ?? {};

                          return _responseCard(
                            claim: claim,
                            request: request,
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }

  // ================= FILTER =================
  Widget _filterBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 6),
      child: Wrap(
        spacing: 8,
        children: [
          _filterChip('all', 'All'),
          _filterChip('active', 'Active'),
          _filterChip('completed', 'Completed'),
          _filterChip('cancelled', 'Cancelled'),
        ],
      ),
    );
  }

  Widget _filterChip(String value, String label) {
    return ChoiceChip(
      label: Text(label),
      selected: _filterStatus == value,
      selectedColor:
          AppColors.primary.withOpacity(0.15),
      onSelected: (_) {
        setState(() => _filterStatus = value);
        _fetchMyHelpResponses();
      },
    );
  }

  // ================= CARD =================
  Widget _responseCard({
    required Map<String, dynamic> claim,
    required Map<String, dynamic> request,
  }) {
    final status = claim['status'] ?? 'active';
    final claimId = claim['id'];
    final conversationId = claim['conversation_id'];

    final imageUrl =
        request['image'] != null &&
                request['image'].toString().isNotEmpty
            ? '${ApiConfig.baseUrl}/${request['image']}'
                .replaceAll('public/', 'storage/')
            : null;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: _imageThumb(imageUrl),
            title: Text(
              request['item_name'] ?? 'Untitled Request',
              style: const TextStyle(
                fontWeight: FontWeight.w600,
              ),
            ),
            subtitle: Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    request['description'] ?? '',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 6),
                  _statusBadge(status),
                ],
              ),
            ),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) =>
                      HelpResponseDetailScreen(
                    claim: claim,
                  ),
                ),
              );
            },
          ),

          const SizedBox(height: 12),

          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.chat_bubble_outline),
                  label: const Text('Chat'),
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ChatRoomScreen(
                          conversationId: conversationId,
                          otherUserName:
                              request['user_name'],
                        ),
                      ),
                    );
                  },
                ),
              ),

              if (status == 'active') ...[
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.success,
                    ),
                    onPressed: () => _postAction(
                      endpoint:
                          '/claim-requests/complete',
                      claimId: claimId,
                      successMsg:
                          'Help marked as completed',
                    ),
                    child: const Text('Complete'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.error,
                    ),
                    onPressed: () => _postAction(
                      endpoint:
                          '/claim-requests/cancel',
                      claimId: claimId,
                      successMsg:
                          'Help cancelled successfully',
                    ),
                    child: const Text('Cancel'),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  // ================= COMPONENTS =================
  Widget _imageThumb(String? imageUrl) {
    if (imageUrl == null) {
      return Container(
        width: 56,
        height: 56,
        decoration: BoxDecoration(
          color: AppColors.accent,
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Icon(
          Icons.image_outlined,
          color: AppColors.textMuted,
        ),
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(10),
      child: Image.network(
        imageUrl,
        width: 56,
        height: 56,
        fit: BoxFit.cover,
      ),
    );
  }

  Widget _statusBadge(String status) {
    final color = _statusColor(status);

    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        _capitalize(status),
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _emptyState() {
    return const Center(
      child: Text(
        'You have not helped any requests yet.',
        style: TextStyle(
          color: AppColors.textSecondary,
        ),
      ),
    );
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'completed':
        return AppColors.success;
      case 'cancelled':
        return AppColors.error;
      default:
        return AppColors.warning;
    }
  }

  String _capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() +
        text.substring(1).toLowerCase();
  }
}
