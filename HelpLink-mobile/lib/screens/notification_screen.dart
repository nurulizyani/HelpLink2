import 'dart:convert';
import 'package:flutter/material.dart';

import '../services/notification_api_service.dart';

// CHAT
import 'chat/chat_room_screen.dart';

// REQUEST
import 'requests/help_request_detail.dart';

// OFFER (CLAIM CONTEXT)
import 'offers/claims_offer_detail_screen.dart';

import 'package:part_1/theme/app_colors.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  final NotificationApiService _api = NotificationApiService();

  bool loading = true;
  List<dynamic> notifications = [];

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  // ===============================
  // LOAD NOTIFICATIONS
  // ===============================
  Future<void> _loadNotifications() async {
    if (!mounted) return;

    setState(() => loading = true);

    final data = await _api.fetchNotifications();

    if (!mounted) return;

    setState(() {
      notifications = data;
      loading = false;
    });
  }

  // ===============================
  // MARK ALL AS READ
  // ===============================
  Future<void> _markAllAsRead() async {
    await _api.markAllAsRead();
    if (!mounted) return;
    await _loadNotifications();
  }

  // ===============================
  // HANDLE TAP
  // ===============================
  Future<void> _handleTap(dynamic n) async {
    final int? notificationId =
        n['id'] != null ? int.tryParse(n['id'].toString()) : null;

    if (notificationId != null) {
      await _api.markAsRead(notificationId);
    }

    if (!mounted) return;

    final String? type = n['type']?.toString();
    final rawData = n['data'];

    Map<String, dynamic> data = {};

    // data boleh jadi JSON string atau Map
    if (rawData is String && rawData.isNotEmpty) {
      try {
        data = jsonDecode(rawData);
      } catch (_) {}
    } else if (rawData is Map) {
      data = Map<String, dynamic>.from(rawData);
    }

    // ===============================
    // CHAT
    // ===============================
    if (type == 'chat' && data['conversation_id'] != null) {
      final conversationId =
          int.tryParse(data['conversation_id'].toString());

      if (conversationId != null) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => ChatRoomScreen(
              conversationId: conversationId,
              otherUserName: n['title'] ?? 'Chat',
            ),
          ),
        );
      }
    }

    // ===============================
    // REQUEST
    // ===============================
    if (type == 'request' && data['request_id'] != null) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => HelpRequestDetailScreen(
            request: data,
          ),
        ),
      );
    }

    // ===============================
    // OFFER (CLAIM DETAIL)
    // ===============================
    if (type == 'offer' && data['offer_id'] != null) {
      final offerId = int.tryParse(data['offer_id'].toString());

      if (offerId != null) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => ClaimOfferDetailScreen(
              claim: data,
            ),
          ),
        );
      }
    }

    await _loadNotifications();
  }

  @override
  Widget build(BuildContext context) {
    final hasUnread = notifications.any((n) => n['is_read'] == 0);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Notifications'),
        centerTitle: true,
        actions: [
          if (hasUnread)
            IconButton(
              tooltip: 'Mark all as read',
              icon: const Icon(Icons.done_all),
              onPressed: _markAllAsRead,
            ),
        ],
      ),
      body: loading
          ? const Center(
              child: CircularProgressIndicator(
                color: AppColors.primary,
              ),
            )
          : notifications.isEmpty
              ? _emptyState()
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: notifications.length,
                  itemBuilder: (context, i) {
                    final n = notifications[i];
                    final unread = n['is_read'] == 0;

                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      decoration: BoxDecoration(
                        color: AppColors.card,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            blurRadius: 8,
                            color: Colors.black.withOpacity(0.05),
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                        leading: CircleAvatar(
                          backgroundColor: unread
                              ? AppColors.primary.withOpacity(0.15)
                              : AppColors.textMuted.withOpacity(0.15),
                          child: Icon(
                            Icons.notifications,
                            color: unread
                                ? AppColors.primary
                                : AppColors.textMuted,
                          ),
                        ),
                        title: Text(
                          n['title'] ?? 'Notification',
                          style: TextStyle(
                            fontWeight:
                                unread ? FontWeight.w600 : FontWeight.normal,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            n['message'] ?? '',
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ),
                        trailing: unread
                            ? const Icon(
                                Icons.circle,
                                size: 10,
                                color: AppColors.danger,
                              )
                            : null,
                        onTap: () => _handleTap(n),
                      ),
                    );
                  },
                ),
    );
  }

  Widget _emptyState() {
    return const Center(
      child: Text(
        'No notifications yet',
        style: TextStyle(
          color: AppColors.textMuted,
        ),
      ),
    );
  }
}
