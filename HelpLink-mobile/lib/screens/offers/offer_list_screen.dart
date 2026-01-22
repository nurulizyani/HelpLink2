import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:geolocator/geolocator.dart';

import 'package:part_1/config/api_config.dart';
import 'offer_detail_screen.dart';
import 'offer_create_screen.dart';

class OfferListScreen extends StatefulWidget {
  const OfferListScreen({super.key});

  @override
  State<OfferListScreen> createState() => _OfferListScreenState();
}

class _OfferListScreenState extends State<OfferListScreen> {
  List<dynamic> _offers = [];
  bool _loading = true;

  String _searchQuery = '';
  int _radius = 0;

  int? _currentUserId;
  double? _userLat;
  double? _userLng;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    await _loadUserId();
    _fetchOffers();
    _detectLocation();
  }

  Future<void> _loadUserId() async {
    final prefs = await SharedPreferences.getInstance();
    _currentUserId = prefs.getInt('user_id');
  }

  Future<void> _detectLocation() async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) return;

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        return;
      }

      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      _userLat = pos.latitude;
      _userLng = pos.longitude;

      _fetchOffers();
    } catch (_) {}
  }

  Future<void> _fetchOffers() async {
    if (!mounted) return;
    setState(() => _loading = true);

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');

    final params = <String, String>{};
    if (_userLat != null && _userLng != null) {
      params['latitude'] = _userLat.toString();
      params['longitude'] = _userLng.toString();
      params['radius'] = _radius.toString();
    }

    final uri = Uri.parse('${ApiConfig.apiBase}/offers')
        .replace(queryParameters: params.isEmpty ? null : params);

    try {
      final res = await http.get(
        uri,
        headers: {
          'Accept': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
      );

      if (res.statusCode == 200) {
        final decoded = jsonDecode(res.body);

        if (decoded is List) {
          _offers = decoded;
        } else if (decoded is Map<String, dynamic>) {
          _offers = decoded['offers'] ?? decoded['data'] ?? [];
        } else {
          _offers = [];
        }
      } else {
        _offers = [];
      }
    } catch (_) {
      _offers = [];
    }

    if (mounted) setState(() => _loading = false);
  }

  // ================= IMAGE URL HELPER =================
  String? _imageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http')) return path;
    if (path.startsWith('storage/')) {
      return '${ApiConfig.baseUrl}/$path';
    }
    return '${ApiConfig.baseUrl}/storage/$path';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final text = theme.textTheme;

    final filtered = _offers.where((o) {
      final title = (o['item_name'] ?? '').toString().toLowerCase();
      if (!title.contains(_searchQuery.toLowerCase())) return false;

      if (_currentUserId == null) return true;

      final uid = o['user_id'] ?? o['user']?['id'];
      final offerUserId = int.tryParse(uid?.toString() ?? '');

      return offerUserId != _currentUserId;
    }).toList();

    return Scaffold(
      appBar: AppBar(title: const Text('Available Offers')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: TextField(
                    onChanged: (v) =>
                        setState(() => _searchQuery = v),
                    decoration: InputDecoration(
                      hintText: 'Search offers',
                      prefixIcon: const Icon(Icons.search),
                      filled: true,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ),

                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Row(
                    children: [
                      const Text('Range'),
                      const Spacer(),
                      DropdownButton<int>(
                        value: _radius,
                        underline: const SizedBox(),
                        items: const [
                          DropdownMenuItem(value: 0, child: Text('Whole MY')),
                          DropdownMenuItem(value: 5, child: Text('5 km')),
                          DropdownMenuItem(value: 10, child: Text('10 km')),
                          DropdownMenuItem(value: 20, child: Text('20 km')),
                        ],
                        onChanged: (v) {
                          setState(() => _radius = v!);
                          _fetchOffers();
                        },
                      ),
                    ],
                  ),
                ),

                Expanded(
                  child: RefreshIndicator(
                    onRefresh: _fetchOffers,
                    child: filtered.isEmpty
                        ? ListView(
                            physics: const AlwaysScrollableScrollPhysics(),
                            children: const [
                              SizedBox(height: 200),
                              Center(child: Text('No offers available')),
                            ],
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: filtered.length,
                            itemBuilder: (_, i) {
                              final o = filtered[i];
                              final distance = o['distance'];
                              final imgUrl = _imageUrl(o['image']);

                              return Card(
                                margin: const EdgeInsets.only(bottom: 16),
                                child: InkWell(
                                  onTap: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) =>
                                            OfferDetailScreen(offer: o),
                                      ),
                                    );
                                  },
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      if (imgUrl != null)
                                        Image.network(
                                          imgUrl,
                                          height: 150,
                                          width: double.infinity,
                                          fit: BoxFit.cover,
                                        ),
                                      Padding(
                                        padding: const EdgeInsets.all(14),
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Expanded(
                                                  child: Text(
                                                    o['item_name'] ?? '-',
                                                    style: text.titleMedium,
                                                  ),
                                                ),
                                                if (distance != null)
                                                  Container(
                                                    padding:
                                                        const EdgeInsets.symmetric(
                                                      horizontal: 10,
                                                      vertical: 4,
                                                    ),
                                                    decoration: BoxDecoration(
                                                      color: Colors.blue
                                                          .withOpacity(0.1),
                                                      borderRadius:
                                                          BorderRadius.circular(20),
                                                    ),
                                                    child: Text(
                                                      '${double.parse(distance.toString()).toStringAsFixed(1)} km',
                                                      style: text.bodySmall
                                                          ?.copyWith(
                                                        color: Colors.blue,
                                                        fontWeight:
                                                            FontWeight.w600,
                                                      ),
                                                    ),
                                                  ),
                                              ],
                                            ),
                                            const SizedBox(height: 6),
                                            Text(
                                              o['description'] ?? '-',
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                            const SizedBox(height: 8),
                                            Text(o['address'] ?? '-'),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                  ),
                ),
              ],
            ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final res = await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => const OfferCreateScreen(),
            ),
          );
          if (res == true) _fetchOffers();
        },
        icon: const Icon(Icons.add),
        label: const Text('New Offer'),
      ),
    );
  }
}
