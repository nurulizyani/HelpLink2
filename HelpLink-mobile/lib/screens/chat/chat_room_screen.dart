import 'dart:async';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'chat_api_service.dart';
import 'package:part_1/theme/app_colors.dart';

class ChatRoomScreen extends StatefulWidget {
  final int conversationId;
  final String? otherUserName;

  const ChatRoomScreen({
    super.key,
    required this.conversationId,
    this.otherUserName,
  });

  @override
  State<ChatRoomScreen> createState() => _ChatRoomScreenState();
}

class _ChatRoomScreenState extends State<ChatRoomScreen> {
  final ChatApiService _api = ChatApiService();
  final TextEditingController _msgCtrl = TextEditingController();
  final ScrollController _scrollCtrl = ScrollController();

  List<dynamic> messages = [];
  bool loading = true;
  Timer? refreshTimer;
  int? myUserId;

  @override
  void initState() {
    super.initState();
    _initChat();
  }

  Future<void> _initChat() async {
    final prefs = await SharedPreferences.getInstance();
    myUserId = prefs.getInt('user_id');

    await _loadMessages();

    refreshTimer = Timer.periodic(
      const Duration(seconds: 2),
      (_) {
        if (mounted) _loadMessages(scroll: false);
      },
    );
  }

  @override
  void dispose() {
    refreshTimer?.cancel();
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadMessages({bool scroll = true}) async {
    final data = await _api.getMessages(widget.conversationId);

    if (!mounted) return;

    setState(() {
      messages = data;
      loading = false;
    });

    if (scroll && _scrollCtrl.hasClients) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (_scrollCtrl.hasClients) {
          _scrollCtrl.jumpTo(_scrollCtrl.position.maxScrollExtent);
        }
      });
    }
  }

  Future<void> _sendMessage() async {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty) return;

    final success = await _api.sendMessage(
      conversationId: widget.conversationId,
      message: text,
    );

    if (success && mounted) {
      _msgCtrl.clear();
      _loadMessages();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        title: Text(
          widget.otherUserName ?? 'Chat',
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      body: loading
          ? const Center(
              child: CircularProgressIndicator(
                color: AppColors.primary,
              ),
            )
          : Column(
              children: [
                Expanded(
                  child: ListView.builder(
                    controller: _scrollCtrl,
                    padding: const EdgeInsets.all(14),
                    itemCount: messages.length,
                    itemBuilder: (context, i) {
                      final msg = messages[i];
                      final bool isMe =
                          myUserId != null &&
                          msg['sender_id'] == myUserId;

                      return _chatBubble(
                        text: msg['message'] ?? '',
                        isMe: isMe,
                      );
                    },
                  ),
                ),
                _inputBar(),
              ],
            ),
    );
  }

  // =============================
  // UI COMPONENTS
  // =============================

  Widget _chatBubble({required String text, required bool isMe}) {
    return Align(
      alignment:
          isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 10,
        ),
        constraints: const BoxConstraints(maxWidth: 280),
        decoration: BoxDecoration(
          color: isMe ? AppColors.primary : AppColors.card,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(14),
            topRight: const Radius.circular(14),
            bottomLeft:
                isMe ? const Radius.circular(14) : Radius.zero,
            bottomRight:
                isMe ? Radius.zero : const Radius.circular(14),
          ),
          boxShadow: [
            BoxShadow(
              blurRadius: 6,
              color: Colors.black.withOpacity(0.06),
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Text(
          text,
          style: TextStyle(
            color: isMe ? Colors.white : AppColors.textPrimary,
            fontSize: 14.5,
          ),
        ),
      ),
    );
  }

  Widget _inputBar() {
    return SafeArea(
      child: Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: AppColors.surface,
          boxShadow: [
            BoxShadow(
              blurRadius: 8,
              color: Colors.black.withOpacity(0.05),
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _msgCtrl,
                decoration: InputDecoration(
                  hintText: 'Type a message…',
                  filled: true,
                  fillColor: AppColors.accent,
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 14),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 10),
            GestureDetector(
              onTap: _sendMessage,
              child: Container(
                height: 44,
                width: 44,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.send_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
