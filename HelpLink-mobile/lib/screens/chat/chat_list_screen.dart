import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'chat_api_service.dart';
import 'chat_room_screen.dart';
import 'package:part_1/theme/app_colors.dart';

class ChatListScreen extends StatefulWidget {
  const ChatListScreen({super.key});

  @override
  State<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends State<ChatListScreen> {
  final ChatApiService _api = ChatApiService();

  List<dynamic> _conversations = [];
  bool _loading = true;
  int? _myUserId;

  @override
  void initState() {
    super.initState();
    _init();
  }

  // =============================
  // INIT
  // =============================
  Future<void> _init() async {
    final prefs = await SharedPreferences.getInstance();
    _myUserId = prefs.getInt('user_id');
    await _loadConversations();
  }

  Future<void> _loadConversations() async {
    try {
      final data = await _api.getConversations();
      if (!mounted) return;

      setState(() {
        _conversations = data;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  // =============================
  // UI
  // =============================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Chats'),
        centerTitle: true,
      ),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(
                color: AppColors.primary,
              ),
            )
          : _conversations.isEmpty
              ? _emptyState()
              : RefreshIndicator(
                  onRefresh: _loadConversations,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: _conversations.length,
                    itemBuilder: (_, i) {
                      final c = _conversations[i] as Map<String, dynamic>;

                      final int? user1Id = _asInt(c['user1_id']);
                      final int? user2Id = _asInt(c['user2_id']);

                      final bool isUser1 =
                          _myUserId != null && _myUserId == user1Id;

                      final Map<String, dynamic>? otherUser =
                          isUser1 ? c['user2'] : c['user1'];

                      final String otherName =
                          (otherUser?['name'] ?? 'User').toString();

                      final int unreadCount = isUser1
                          ? _asInt(c['unread_by_user1']) ?? 0
                          : _asInt(c['unread_by_user2']) ?? 0;

                      String contextLabel = '';
                      IconData contextIcon = Icons.chat_bubble_outline;

                      if (c['offer_id'] != null) {
                        contextLabel = 'Offer';
                        contextIcon = Icons.card_giftcard_outlined;
                      } else if (c['request_id'] != null) {
                        contextLabel = 'Request';
                        contextIcon = Icons.help_outline;
                      }

                      final String lastMessage =
                          (c['last_message'] ?? 'No messages yet').toString();

                      final int? conversationId = _asInt(c['id']);

                      if (conversationId == null) {
                        return const SizedBox.shrink();
                      }

                      return _chatCard(
                        otherName: otherName,
                        lastMessage: lastMessage,
                        contextLabel: contextLabel,
                        contextIcon: contextIcon,
                        unreadCount: unreadCount,
                        onTap: () async {
                          await Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => ChatRoomScreen(
                                conversationId: conversationId,
                                otherUserName: otherName,
                              ),
                            ),
                          );
                          _loadConversations();
                        },
                      );
                    },
                  ),
                ),
    );
  }

  // =============================
  // CHAT CARD
  // =============================
  Widget _chatCard({
    required String otherName,
    required String lastMessage,
    required String contextLabel,
    required IconData contextIcon,
    required int unreadCount,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            blurRadius: 8,
            color: Colors.black.withOpacity(0.06),
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ListTile(
        onTap: onTap,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        leading: CircleAvatar(
          radius: 26,
          backgroundColor: AppColors.primary.withOpacity(0.15),
          child: const Icon(
            Icons.person_outline,
            color: AppColors.primary,
          ),
        ),
        title: Text(
          otherName,
          style: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 15,
            color: AppColors.textPrimary,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (contextLabel.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4, bottom: 2),
                child: Row(
                  children: [
                    Icon(
                      contextIcon,
                      size: 14,
                      color: AppColors.textMuted,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      contextLabel,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            Text(
              lastMessage,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 13,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (unreadCount > 0)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: const BoxDecoration(
                  color: AppColors.danger,
                  borderRadius: BorderRadius.all(Radius.circular(12)),
                ),
                child: Text(
                  unreadCount.toString(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            const SizedBox(width: 8),
            const Icon(
              Icons.chevron_right,
              color: AppColors.textMuted,
            ),
          ],
        ),
      ),
    );
  }

  // =============================
  // EMPTY STATE
  // =============================
  Widget _emptyState() {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.chat_bubble_outline,
            size: 60,
            color: AppColors.textMuted,
          ),
          SizedBox(height: 12),
          Text(
            'No conversations yet',
            style: TextStyle(color: AppColors.textMuted),
          ),
        ],
      ),
    );
  }

  // =============================
  // SAFE INT PARSE
  // =============================
  int? _asInt(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    return int.tryParse(v.toString());
  }
}
