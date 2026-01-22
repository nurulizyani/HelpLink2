import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/screens/notification_screen.dart';
import 'package:part_1/screens/requests/help_request_detail.dart';
import 'package:part_1/screens/requests/help_request_create.dart';
import 'package:part_1/theme/app_colors.dart';

class MyHelpRequestsScreen extends StatefulWidget {
  const MyHelpRequestsScreen({super.key});

  @override
  State<MyHelpRequestsScreen> createState() =>
      _MyHelpRequestsScreenState();
}

class _MyHelpRequestsScreenState extends State<MyHelpRequestsScreen> {
  List<dynamic> _requests = [];
  bool _loading = true;
  String selectedFilter = 'All';

  @override
  void initState() {
    super.initState();
    _fetchMyRequests();
  }

  // ================= FETCH =================
  Future<void> _fetchMyRequests() async {
    setState(() => _loading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Unauthenticated');

      final response = await http.get(
        Uri.parse('${ApiConfig.apiBase}/requests/my'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          _requests = data['data'] ?? [];
        }
      }
    } catch (_) {
      _showSnack('Failed to load requests', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= DELETE =================
  Future<void> _deleteRequest(int requestId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Unauthenticated');

      final response = await http.delete(
        Uri.parse('${ApiConfig.apiBase}/requests/$requestId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        _showSnack('Request deleted');
        _fetchMyRequests();
      }
    } catch (_) {
      _showSnack('Failed to delete request', error: true);
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
    final filtered = selectedFilter == 'All'
        ? _requests
        : _requests
            .where(
              (r) =>
                  (r['status'] ?? '')
                      .toString()
                      .toLowerCase() ==
                  selectedFilter.toLowerCase(),
            )
            .toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Help Requests'),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const NotificationScreen(),
                ),
              );
            },
          ),
        ],
      ),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(),
            )
          : RefreshIndicator(
              onRefresh: _fetchMyRequests,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment:
                      CrossAxisAlignment.start,
                  children: [
                    _filterBar(),
                    const SizedBox(height: 16),
                    Expanded(
                      child: filtered.isEmpty
                          ? _emptyState()
                          : ListView.builder(
                              itemCount: filtered.length,
                              itemBuilder: (_, i) {
                                final req = filtered[i];
                                final status =
                                    (req['status'] ??
                                            'pending')
                                        .toString();
                                final canEdit =
                                    status == 'pending' ||
                                        status == 'rejected';

                                return _requestCard(
                                  req: req,
                                  status: status,
                                  canEdit: canEdit,
                                );
                              },
                            ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  // ================= FILTER =================
  Widget _filterBar() {
    return Wrap(
      spacing: 8,
      children: [
        _filterChip('All'),
        _filterChip('pending'),
        _filterChip('approved'),
        _filterChip('rejected'),
        _filterChip('fulfilled'),
      ],
    );
  }

  Widget _filterChip(String label) {
    return ChoiceChip(
      label: Text(_capitalize(label)),
      selected: selectedFilter == label,
      selectedColor:
          AppColors.primary.withOpacity(0.15),
      onSelected: (_) {
        setState(() => selectedFilter = label);
      },
    );
  }

  // ================= CARD =================
  Widget _requestCard({
    required Map<String, dynamic> req,
    required String status,
    required bool canEdit,
  }) {
    final itemName =
        req['item_name'] ?? 'Untitled Request';
    final date =
        req['created_at']
                ?.toString()
                .split('T')
                .first ??
            '-';

    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) =>
                HelpRequestDetailScreen(request: req),
          ),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 14),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              blurRadius: 10,
              color: Colors.black.withOpacity(0.06),
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment:
              CrossAxisAlignment.start,
          children: [
            // ICON
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color:
                    _statusColor(status).withOpacity(0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                Icons.help_outline,
                color: _statusColor(status),
              ),
            ),

            const SizedBox(width: 14),

            // CONTENT
            Expanded(
              child: Column(
                crossAxisAlignment:
                    CrossAxisAlignment.start,
                children: [
                  Text(
                    itemName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Submitted on $date',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  _statusBadge(status),
                ],
              ),
            ),

            // ACTION
            canEdit
                ? PopupMenuButton<String>(
                    onSelected: (value) async {
                      if (value == 'edit') {
                        final result =
                            await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) =>
                                HelpRequestCreateScreen(
                              request: req,
                            ),
                          ),
                        );
                        if (result == true) {
                          _fetchMyRequests();
                        }
                      } else if (value == 'delete') {
                        _confirmDelete(req['id']);
                      }
                    },
                    itemBuilder: (_) => const [
                      PopupMenuItem(
                        value: 'edit',
                        child: Text('Edit'),
                      ),
                      PopupMenuItem(
                        value: 'delete',
                        child: Text('Delete'),
                      ),
                    ],
                  )
                : const Icon(
                    Icons.lock_outline,
                    color: AppColors.textMuted,
                  ),
          ],
        ),
      ),
    );
  }

  // ================= STATUS =================
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
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  Widget _emptyState() {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.inbox_outlined,
            size: 64,
            color: AppColors.textMuted,
          ),
          SizedBox(height: 12),
          Text(
            'You have not submitted any requests yet.',
            style: TextStyle(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  void _confirmDelete(int requestId) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Delete Request'),
        content: const Text(
          'Are you sure you want to delete this request?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.error,
            ),
            onPressed: () {
              Navigator.pop(context);
              _deleteRequest(requestId);
            },
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
        return AppColors.success;
      case 'rejected':
        return AppColors.error;
      case 'fulfilled':
        return Colors.blueAccent;
      case 'pending':
        return AppColors.warning;
      default:
        return AppColors.textMuted;
    }
  }

  String _capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() +
        text.substring(1).toLowerCase();
  }
}
