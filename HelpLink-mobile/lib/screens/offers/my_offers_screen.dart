import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:cached_network_image/cached_network_image.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';

import 'offer_create_screen.dart';

class MyOffersScreen extends StatefulWidget {
  const MyOffersScreen({super.key});

  @override
  State<MyOffersScreen> createState() => _MyOffersScreenState();
}

class _MyOffersScreenState extends State<MyOffersScreen> {
  bool _loading = true;
  List<dynamic> _offers = [];

  @override
  void initState() {
    super.initState();
    _fetchMyOffers();
  }

  Future<void> _fetchMyOffers() async {
    if (!mounted) return;
    setState(() => _loading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null || token.isEmpty) {
        _snack('Session expired. Please login again.', error: true);
        return;
      }

      final res = await http.get(
        Uri.parse('${ApiConfig.apiBase}/offers/my'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      final data = jsonDecode(res.body);

      if (res.statusCode == 200 && data['success'] == true) {
        setState(() => _offers = data['offers'] ?? []);
      } else {
        _snack(data['message'] ?? 'Failed to load offers', error: true);
      }
    } catch (e) {
      _snack('Error: $e', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _deleteOffer(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Delete Offer'),
        content: const Text('Are you sure you want to delete this offer?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.redAccent),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      final res = await http.delete(
        Uri.parse('${ApiConfig.apiBase}/offers/$id'),
      );

      final data = jsonDecode(res.body);

      if (res.statusCode == 200 && data['success'] == true) {
        _snack('Offer deleted');
        _fetchMyOffers();
      } else {
        _snack(data['message'] ?? 'Delete failed', error: true);
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
        backgroundColor: error ? Colors.redAccent : Colors.green,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Offers'),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchMyOffers,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _offers.isEmpty
              ? const Center(
                  child: Text(
                    'You haven’t created any offers yet.',
                    style: TextStyle(color: Colors.grey),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _offers.length,
                  itemBuilder: (_, i) {
                    final o = _offers[i];
                    final id = o['offer_id'] ?? o['id'];

                    final title = o['item_name'] ?? '-';
                    final desc = o['description'] ?? '';
                    final status =
                        (o['status'] ?? 'available').toString().toLowerCase();

                    final img = o['image'];
                    final imgUrl =
                        img != null && img.toString().isNotEmpty
                            ? '${ApiConfig.baseUrl}/$img'
                            : null;

                    final canOpen =
                        status == 'claimed' || status == 'received';

                    Color badge;
                    switch (status) {
                      case 'claimed':
                        badge = Colors.orange;
                        break;
                      case 'received':
                        badge = Colors.blue;
                        break;
                      case 'completed':
                        badge = Colors.green;
                        break;
                      default:
                        badge = Colors.blueGrey;
                    }

                    return InkWell(
                      onTap: null,
                     
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 14),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.05),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(12),
                              child: imgUrl != null
                                  ? CachedNetworkImage(
                                      imageUrl: imgUrl,
                                      width: 70,
                                      height: 70,
                                      fit: BoxFit.cover,
                                    )
                                  : Container(
                                      width: 70,
                                      height: 70,
                                      color: Colors.grey.shade300,
                                      child: const Icon(
                                        Icons.image_not_supported,
                                        color: Colors.white,
                                      ),
                                    ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    title,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    desc,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 13,
                                      color: Colors.black54,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: badge.withOpacity(0.15),
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Text(
                                      status.toUpperCase(),
                                      style: TextStyle(
                                        color: badge,
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Column(
                              children: [
                                IconButton(
                                  icon: Icon(
                                    Icons.edit,
                                    color: status == 'available'
                                        ? AppColors.primary
                                        : Colors.grey.shade400,
                                  ),
                                  onPressed: status == 'available'
                                      ? () async {
                                          final r = await Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (_) =>
                                                  OfferCreateScreen(offer: o),
                                            ),
                                          );
                                          if (r == true) _fetchMyOffers();
                                        }
                                      : null,
                                ),
                                IconButton(
                                  icon: const Icon(Icons.delete,
                                      color: Colors.redAccent),
                                  onPressed: id != null
                                      ? () => _deleteOffer(id)
                                      : null,
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}
