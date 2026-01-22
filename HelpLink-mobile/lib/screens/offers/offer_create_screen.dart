import 'dart:io';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:path_provider/path_provider.dart';
import 'package:flutter/services.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';
import 'package:part_1/screens/location/pick_location_page.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';


class OfferCreateScreen extends StatefulWidget {
  final Map<String, dynamic>? offer;
  const OfferCreateScreen({super.key, this.offer});

  @override
  State<OfferCreateScreen> createState() => _OfferCreateScreenState();
}

class _OfferCreateScreenState extends State<OfferCreateScreen> {
  final _formKey = GlobalKey<FormState>();

  final _itemNameCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController(text: '1');
  final _locationCtrl = TextEditingController();

  final List<String> _categories = [
    'Food',
    'Clothes',
    'Household Items',
    'Books',
    'Medical Supplies',
    'Others',
  ];

  String _category = 'Food';
  String _delivery = 'Pickup';
  bool _loading = false;

  File? _image;
  String? _existingImage;
  double? _lat, _lng;

  @override
  void initState() {
    super.initState();
    _loadEditData();
  }

  void _loadEditData() {
    if (widget.offer == null) return;

    final o = widget.offer!;
    _itemNameCtrl.text = (o['item_name'] ?? '').toString();
    _descCtrl.text = (o['description'] ?? '').toString();
    _qtyCtrl.text = (o['quantity'] ?? 1).toString();
    _locationCtrl.text = (o['address'] ?? '').toString();
    _category = (o['category'] ?? _category).toString();

    final dt = (o['delivery_type'] ?? 'pickup').toString().toLowerCase();
    _delivery = dt == 'delivery' ? 'Delivery' : 'Pickup';

    _lat = double.tryParse((o['latitude'] ?? '').toString());
    _lng = double.tryParse((o['longitude'] ?? '').toString());

    if (o['image'] != null && o['image'].toString().isNotEmpty) {
      _existingImage = '${ApiConfig.baseUrl}/${o['image']}';
    }
  }

  @override
  void dispose() {
    _itemNameCtrl.dispose();
    _descCtrl.dispose();
    _qtyCtrl.dispose();
    _locationCtrl.dispose();
    super.dispose();
  }

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(msg)));
  }

  String _safeQty() {
    final v = int.tryParse(_qtyCtrl.text.trim());
    if (v == null || v < 1) return '1';
    return v.toString();
  }

  // ================= IMAGE =================
  Future<void> _pickImage() async {
    final picked = await ImagePicker().pickImage(
      source: ImageSource.gallery,
    );
    if (picked == null) return;

    setState(() {
      _image = File(picked.path);
      _existingImage = null;
    });
  }

  // ================= LOCATION =================
  Future<bool> _ensureLocationPermission() async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      _snack('Please enable location service');
      return false;
    }

    LocationPermission perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }

    if (perm == LocationPermission.denied ||
        perm == LocationPermission.deniedForever) {
      _snack('Location permission denied');
      return false;
    }

    return true;
  }

  Future<void> _getLocation() async {
    final ok = await _ensureLocationPermission();
    if (!ok) return;

    final pos = await Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
    );

    String addr = 'Current Location';
    try {
      final placemarks =
          await placemarkFromCoordinates(pos.latitude, pos.longitude);
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        addr =
            '${p.subLocality ?? ''}, ${p.locality ?? ''}'.trim().replaceAll(RegExp(r'^,|,$'), '');
      }
    } catch (_) {}

    setState(() {
      _lat = pos.latitude;
      _lng = pos.longitude;
      _locationCtrl.text = addr;
    });
  }

  Future<void> _openMapPicker() async {
  final result = await Navigator.push(
    context,
    MaterialPageRoute(
      builder: (_) => const PickLocationPage(),
    ),
  );

  if (result != null && mounted && result is Map) {
    setState(() {
      _lat = double.tryParse(result['lat'].toString());
      _lng = double.tryParse(result['lng'].toString());
      _locationCtrl.text =
          result['address'] ??
          'Selected area (${_lat!.toStringAsFixed(5)}, ${_lng!.toStringAsFixed(5)})';
    });
  }
}

  // ================= SUBMIT =================
  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;
    if (_loading) return;

    setState(() => _loading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final isEdit = widget.offer != null;
      final id =
          (widget.offer?['id'] ?? widget.offer?['offer_id'])?.toString();

      final url = isEdit
          ? '${ApiConfig.apiBase}/offers/$id'
          : '${ApiConfig.apiBase}/offers';

      final request = http.MultipartRequest('POST', Uri.parse(url));

      request.headers['Accept'] = 'application/json';
      if (token != null && token.isNotEmpty) {
        request.headers['Authorization'] = 'Bearer $token';
      }

      if (isEdit) request.fields['_method'] = 'PUT';

      request.fields.addAll({
        'item_name': _itemNameCtrl.text.trim(),
        'description': _descCtrl.text.trim(),
        'quantity': _safeQty(),
        'category': _category,
        'delivery_type': _delivery.toLowerCase(),
        'address': _locationCtrl.text.trim(),
      });

      if (_lat != null && _lng != null) {
        request.fields['latitude'] = _lat.toString();
        request.fields['longitude'] = _lng.toString();
      }

      if (_image != null) {
        final tempDir = await getTemporaryDirectory();
        final compressedPath =
            '${tempDir.path}/offer_${DateTime.now().millisecondsSinceEpoch}.jpg';

        final compressed = await FlutterImageCompress.compressAndGetFile(
          _image!.path,
          compressedPath,
          quality: 70,
        );

        request.files.add(
          await http.MultipartFile.fromPath(
            'image',
            compressed?.path ?? _image!.path,
          ),
        );
      }

      final res = await request.send();
      if (res.statusCode == 200 || res.statusCode == 201) {
        if (!mounted) return;
        Navigator.pop(context, true);
      } else {
        _snack('Submit failed (${res.statusCode})');
      }
    } catch (_) {
      _snack('Submit error');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ================= UI =================
  @override
  Widget build(BuildContext context) {
    final isEdit = widget.offer != null;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Offer' : 'Create Offer'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              _field(_itemNameCtrl, 'Item Name', 'e.g. Food'),
              _field(_descCtrl, 'Description', 'Describe item', maxLines: 3),
              _field(
                _qtyCtrl,
                'Quantity',
                'e.g. 2',
                type: TextInputType.number,
                formatter: [FilteringTextInputFormatter.digitsOnly],
              ),
              DropdownButtonFormField<String>(
                value: _category,
                decoration: _dec('Category'),
                items: _categories
                    .map((e) =>
                        DropdownMenuItem(value: e, child: Text(e)))
                    .toList(),
                onChanged: (v) => setState(() => _category = v ?? _category),
              ),
              Row(
                children: ['Pickup', 'Delivery']
                    .map(
                      (e) => Expanded(
                        child: RadioListTile<String>(
                          value: e,
                          groupValue: _delivery,
                          onChanged: (v) =>
                              setState(() => _delivery = v ?? _delivery),
                          title: Text(e),
                        ),
                      ),
                    )
                    .toList(),
              ),
              // ================= LOCATION SECTION =================
Container(
  padding: const EdgeInsets.all(16),
  margin: const EdgeInsets.only(bottom: 16),
  decoration: BoxDecoration(
    color: AppColors.surface,
    borderRadius: BorderRadius.circular(16),
    boxShadow: [
      BoxShadow(
        color: Colors.black.withOpacity(0.04),
        blurRadius: 6,
        offset: const Offset(0, 3),
      ),
    ],
  ),
  child: Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        children: const [
          Icon(Icons.location_on, color: AppColors.primary),
          SizedBox(width: 6),
          Text('Location',style: TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 15,
            ),
          ),
        ],
      ),
      const SizedBox(height: 10),

      TextFormField(
        controller: _locationCtrl,
        readOnly: true,
        decoration: InputDecoration(
          hintText: 'Select area using GPS or map',
          filled: true,
          fillColor: AppColors.background,
          prefixIcon: const Icon(Icons.place_outlined),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide.none,
          ),
        ),
      ),

      const SizedBox(height: 12),

      Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _getLocation,
              icon: const Icon(Icons.my_location),
              label: const Text('Use GPS'),
              style: OutlinedButton.styleFrom(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _openMapPicker,
              icon: const Icon(Icons.map_outlined),
              label: const Text('Select on Map'),
              style: OutlinedButton.styleFrom(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
        ],
      ),

      const SizedBox(height: 8),

      const Text(
        'You may select an approximate area for privacy. Distance will be calculated if location is provided.',
        style: TextStyle(
          fontSize: 12,
          color: Colors.black54,
        ),
      ),
    ],
  ),
),
if (_lat != null && _lng != null)
  Container(
    margin: const EdgeInsets.only(bottom: 16),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(16),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withOpacity(0.05),
          blurRadius: 6,
          offset: const Offset(0, 3),
        ),
      ],
    ),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: SizedBox(
        height: 180,
        child: FlutterMap(
          options: MapOptions(
            initialCenter: LatLng(_lat!, _lng!),
            initialZoom: 14,
            interactiveFlags: InteractiveFlag.none, // 🔒 READ-ONLY
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
                  point: LatLng(_lat!, _lng!),
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
  ),

              GestureDetector(
                onTap: _pickImage,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    height: 180,
                    width: double.infinity,
                    color: AppColors.surface,
                    child: _image != null
                        ? Image.file(_image!, fit: BoxFit.cover)
                        : _existingImage != null
                            ? Image.network(_existingImage!,
                                fit: BoxFit.cover)
                            : const Center(
                                child: Icon(Icons.image_outlined, size: 40),
                              ),
                  ),
                ),
              ),
              const SizedBox(height: 30),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _loading ? null : _submit,
                  child: _loading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : Text(isEdit ? 'Save Changes' : 'Submit Offer'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController c,
    String label,
    String hint, {
    int maxLines = 1,
    TextInputType type = TextInputType.text,
    List<TextInputFormatter>? formatter,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: c,
        maxLines: maxLines,
        keyboardType: type,
        inputFormatters: formatter,
        validator: (v) =>
            v == null || v.trim().isEmpty ? 'Required' : null,
        decoration: _dec(label, hint),
      ),
    );
  }

  InputDecoration _dec(String label, [String? hint]) => InputDecoration(
        labelText: label,
        hintText: hint,
        filled: true,
        fillColor: AppColors.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
      );
}
