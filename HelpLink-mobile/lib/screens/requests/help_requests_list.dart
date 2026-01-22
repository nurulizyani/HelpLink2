import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geolocator/geolocator.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';
import 'help_request_detail.dart';
import 'help_request_create.dart';

class HelpRequestsListScreen extends StatefulWidget {
  const HelpRequestsListScreen({super.key});

  @override
  State<HelpRequestsListScreen> createState() =>
      _HelpRequestsListScreenState();
}

class _HelpRequestsListScreenState extends State<HelpRequestsListScreen> {
  List<dynamic> _requests = [];
  bool _loading = true;

  double? _userLat;
  double? _userLng;
  int selectedRadius = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _initData();
    });
  }

  Future<void> _initData() async {
    await _getUserLocation();
    await _fetchRequests();
  }

  // ================= USER LOCATION =================
  Future<void> _getUserLocation() async {
    try {
      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.medium,
      );

      if (!mounted) return;

      setState(() {
        _userLat = pos.latitude;
        _userLng = pos.longitude;
      });
    } catch (e) {
      debugPrint('Location error: $e');
      setState(() {
        _userLat = null;
        _userLng = null;
      });
    }
  }

  // ================= FETCH REQUESTS =================
  Future<void> _fetchRequests() async {
    if (!mounted) return;
    setState(() => _loading = true);

    try {
      final params = <String, String>{};

      if (_userLat != null && _userLng != null && selectedRadius > 0) {
        params['latitude'] = _userLat!.toString();
        params['longitude'] = _userLng!.toString();
        params['radius'] = selectedRadius.toString();
      }

      final uri = Uri.parse('${ApiConfig.apiBase}/requests')
          .replace(queryParameters: params.isEmpty ? null : params);

      final response = await http.get(
        uri,
        headers: {'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        final jsonData = jsonDecode(response.body);
        _requests = jsonData['data'] ?? [];
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to load requests')),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= DISTANCE CALC =================
  double? _calculateDistanceKm(double? lat, double? lng) {
    if (_userLat == null ||
        _userLng == null ||
        lat == null ||
        lng == null) {
      return null;
    }

    final meters = Geolocator.distanceBetween(
      _userLat!,
      _userLng!,
      lat,
      lng,
    );

    return meters / 1000;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Requests for Help'),
        centerTitle: true,
        // 🔴 Refresh button removed
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _requests.isEmpty
              ? _emptyState()
              : Column(
                  children: [
                    _radiusSelector(),
                    Expanded(
                      child: RefreshIndicator(
                        onRefresh: _fetchRequests,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _requests.length,
                          itemBuilder: (_, i) =>
                              _requestCard(_requests[i]),
                        ),
                      ),
                    ),
                  ],
                ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add),
        label: const Text('New Request'),
        onPressed: () async {
          final res = await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => const HelpRequestCreateScreen(),
            ),
          );
          if (res == true) _fetchRequests();
        },
      ),
    );
  }

  // ================= UI =================

  Widget _radiusSelector() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
      child: Row(
        children: [
          const Text(
            'Radius',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(width: 12),
          DropdownButton<int>(
            value: selectedRadius,
            items: const [
              DropdownMenuItem(value: 0, child: Text('Whole Malaysia')),
              DropdownMenuItem(value: 5, child: Text('5 km')),
              DropdownMenuItem(value: 10, child: Text('10 km')),
              DropdownMenuItem(value: 20, child: Text('20 km')),
            ],
            onChanged: (v) {
              setState(() => selectedRadius = v!);
              _fetchRequests();
            },
          ),
        ],
      ),
    );
  }

  Widget _requestCard(Map<String, dynamic> req) {
    final title = req['item_name'] ?? '-';
    final desc = req['description'] ?? '-';
    final address = req['address'] ?? '-';
    final category = req['category'] ?? 'Others';
    final image = req['image'];

    final double? lat = req['latitude'] != null
        ? double.tryParse(req['latitude'].toString())
        : null;
    final double? lng = req['longitude'] != null
        ? double.tryParse(req['longitude'].toString())
        : null;

    final distanceKm = _calculateDistanceKm(lat, lng);

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => HelpRequestDetailScreen(request: req),
          ),
        );
      },
      child: Card(
        margin: const EdgeInsets.only(bottom: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(18),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _imageSection(image),
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                      if (distanceKm != null)
                        _distanceBadge(distanceKm),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    desc,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      _categoryChip(category),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    address,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textMuted,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // 🔵 Blue distance badge
  Widget _distanceBadge(double km) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.blue.withOpacity(0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        '${km.toStringAsFixed(1)} km',
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: Colors.blue,
        ),
      ),
    );
  }

  Widget _imageSection(String? image) {
    if (image == null) {
      return Container(
        height: 140,
        decoration: const BoxDecoration(
          color: AppColors.accent,
          borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
        ),
        child: const Center(
          child: Icon(
            Icons.volunteer_activism_outlined,
            size: 42,
            color: AppColors.textMuted,
          ),
        ),
      );
    }

    return ClipRRect(
      borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
      child: Image.network(
        '${ApiConfig.baseUrl}/$image',
        height: 140,
        width: double.infinity,
        fit: BoxFit.cover,
      ),
    );
  }

  Widget _categoryChip(String category) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: AppColors.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        category,
        style: const TextStyle(
          fontSize: 12,
          color: AppColors.primary,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  Widget _emptyState() {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.inbox_outlined,
              size: 64, color: AppColors.textMuted),
          SizedBox(height: 12),
          Text(
            'No approved requests available',
            style: TextStyle(color: AppColors.textMuted),
          ),
        ],
      ),
    );
  }
}
