import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';
import 'package:part_1/screens/offers/claims_offer_detail_screen.dart';

class MyClaimedOffersScreen extends StatefulWidget {
  const MyClaimedOffersScreen({super.key});

  @override
  State<MyClaimedOffersScreen> createState() =>
      _MyClaimedOffersScreenState();
}

class _MyClaimedOffersScreenState extends State<MyClaimedOffersScreen> {
  bool _loading = true;
  List<dynamic> _claims = [];

  @override
  void initState() {
    super.initState();
    _fetchMyClaimedOffers();
  }

  // ================= AUTH HEADERS =================
  Future<Map<String, String>?> _authHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    if (token == null || token.isEmpty) return null;

    return {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  // ================= FETCH =================
  Future<void> _fetchMyClaimedOffers() async {
    if (!mounted) return;
    setState(() => _loading = true);

    try {
      final headers = await _authHeaders();
      if (headers == null) return;

      final res = await http.get(
        Uri.parse('${ApiConfig.apiBase}/claim-offers/my'),
        headers: headers,
      );

      final decoded = jsonDecode(res.body);
      if (res.statusCode == 200 && decoded['success'] == true) {
        _claims = decoded['data'] ?? [];
      } else {
        _snack(decoded['message'] ?? 'Failed to load', error: true);
      }
    } catch (e) {
      _snack('Error: $e', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= CANCEL CLAIM =================
  Future<void> _confirmCancel(int claimId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Cancel Claim'),
        content: const Text(
          'Are you sure you want to cancel this claim?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.error,
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Yes, Cancel'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      _cancelClaim(claimId);
    }
  }

  Future<void> _cancelClaim(int claimId) async {
    try {
      final headers = await _authHeaders();
      if (headers == null) return;

      final res = await http.post(
        Uri.parse('${ApiConfig.apiBase}/claim-offers/cancel'),
        headers: headers,
        body: {'claim_id': claimId.toString()},
      );

      final decoded = jsonDecode(res.body);
      if (decoded['success'] == true) {
        _snack(decoded['message'] ?? 'Claim cancelled');
        _fetchMyClaimedOffers();
      } else {
        _snack(decoded['message'], error: true);
      }
    } catch (e) {
      _snack('Error: $e', error: true);
    }
  }

  // ================= CONFIRM RECEIVED =================
  Future<void> _confirmReceived(int claimId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Confirm Received'),
        content: const Text(
          'Please confirm that you have received this item.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Not Yet'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Yes, Received'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      _markReceived(claimId);
    }
  }

  Future<void> _markReceived(int claimId) async {
    try {
      final headers = await _authHeaders();
      if (headers == null) return;

      final res = await http.post(
        Uri.parse('${ApiConfig.apiBase}/claim-offers/received'),
        headers: headers,
        body: {'claim_id': claimId.toString()},
      );

      final decoded = jsonDecode(res.body);
      if (decoded['success'] == true) {
        _snack(decoded['message'] ?? 'Item marked as received');
        _fetchMyClaimedOffers();
      } else {
        _snack(decoded['message'], error: true);
      }
    } catch (e) {
      _snack('Error: $e', error: true);
    }
  }

  void _snack(String msg, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor:
            error ? AppColors.error : AppColors.success,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Claimed Offers'),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchMyClaimedOffers,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _claims.isEmpty
              ? const Center(
                  child: Text(
                    'No claimed offers yet',
                    style: TextStyle(color: AppColors.textMuted),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _claims.length,
                  itemBuilder: (_, index) {
                    final claim = _claims[index];
                    final offer = claim['offer'] ?? {};
                    final claimId = claim['id'];
                    final status = claim['status'] ?? 'active';

                    final title =
                        offer['item_name'] ?? 'Unnamed Offer';

                    final imageUrl =
                        offer['image'] != null &&
                                offer['image']
                                    .toString()
                                    .isNotEmpty
                            ? "${ApiConfig.baseUrl}/storage/${offer['image']}"
                            : null;

                    Color statusColor;
                    switch (status) {
                      case 'completed':
                        statusColor = AppColors.success;
                        break;
                      case 'cancelled':
                        statusColor = AppColors.error;
                        break;
                      case 'received':
                        statusColor = AppColors.primary;
                        break;
                      default:
                        statusColor = AppColors.warning;
                    }

                    return Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            blurRadius: 8,
                            color: Colors.black.withOpacity(0.06),
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          ListTile(
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) =>
                                      ClaimOfferDetailScreen(
                                    claim: claim,
                                  ),
                                ),
                              );
                            },
                            leading: ClipRRect(
                              borderRadius:
                                  BorderRadius.circular(10),
                              child: imageUrl != null
                                  ? Image.network(
                                      imageUrl,
                                      width: 56,
                                      height: 56,
                                      fit: BoxFit.cover,
                                    )
                                  : Container(
                                      width: 56,
                                      height: 56,
                                      color: AppColors.accent,
                                      child: const Icon(
                                        Icons.image_not_supported,
                                        color:
                                            AppColors.textMuted,
                                      ),
                                    ),
                            ),
                            title: Text(
                              title,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                color:
                                    AppColors.textPrimary,
                              ),
                            ),
                            subtitle: Text(
                              status.toUpperCase(),
                              style: TextStyle(
                                color: statusColor,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),

                          if (status == 'active')
                            Padding(
                              padding: const EdgeInsets.only(
                                  bottom: 12),
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceEvenly,
                                children: [
                                  OutlinedButton(
                                    onPressed: () =>
                                        _confirmCancel(
                                            claimId),
                                    style:
                                        OutlinedButton.styleFrom(
                                      foregroundColor:
                                          AppColors.error,
                                    ),
                                    child: const Text(
                                        'Cancel Claim'),
                                  ),
                                  ElevatedButton(
                                    onPressed: () =>
                                        _confirmReceived(
                                            claimId),
                                    child: const Text(
                                        'I Have Received'),
                                  ),
                                ],
                              ),
                            ),

                          if (status == 'completed' ||
                              status == 'received')
                            const Padding(
                              padding:
                                  EdgeInsets.only(bottom: 12),
                              child: Text(
                                'Transaction completed',
                                style: TextStyle(
                                  color:
                                      AppColors.textMuted,
                                ),
                              ),
                            ),
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}
