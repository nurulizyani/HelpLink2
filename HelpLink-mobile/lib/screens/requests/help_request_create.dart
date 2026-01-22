import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';

import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import 'package:part_1/config/api_config.dart';
import 'package:part_1/theme/app_colors.dart';
import 'package:part_1/screens/location/pick_location_page.dart';

class HelpRequestCreateScreen extends StatefulWidget {
  final Map<String, dynamic>? request;

  const HelpRequestCreateScreen({
    super.key,
    this.request,
  });

  @override
  State<HelpRequestCreateScreen> createState() =>
      _HelpRequestCreateScreenState();
}

class _HelpRequestCreateScreenState extends State<HelpRequestCreateScreen> {
  final _formKey = GlobalKey<FormState>();

  final _itemCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();

  String? _category;
  File? _image;
  File? _document;

  bool _loading = false;
  bool _locating = false;

  double? _lat;
  double? _lng;

  bool get isEdit => widget.request != null;

  @override
  void initState() {
    super.initState();
    if (isEdit) {
      final r = widget.request!;
      _itemCtrl.text = r['item_name'] ?? '';
      _descCtrl.text = r['description'] ?? '';
      _addressCtrl.text = r['address'] ?? '';
      _category = r['category'];

      _lat = double.tryParse(r['latitude']?.toString() ?? '');
      _lng = double.tryParse(r['longitude']?.toString() ?? '');
    }
  }

  @override
  void dispose() {
    _itemCtrl.dispose();
    _descCtrl.dispose();
    _addressCtrl.dispose();
    super.dispose();
  }

  // =============================
  // IMAGE
  // =============================
  Future<void> _pickImage() async {
    final picked = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
    );
    if (picked != null) {
      setState(() => _image = File(picked.path));
    }
  }

  // =============================
  // DOCUMENT
  // =============================
  Future<void> _pickDocument() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
    );
    if (result != null && result.files.single.path != null) {
      setState(() => _document = File(result.files.single.path!));
    }
  }

  // =============================
  // LOCATION (GPS)
  // =============================
  Future<void> _useGps() async {
    setState(() => _locating = true);
    try {
      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      String addr = 'Selected area';
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
        _addressCtrl.text = addr;
      });
    } catch (_) {
      _showMsg('Failed to get location');
    } finally {
      setState(() => _locating = false);
    }
  }

  // =============================
  // MAP PICKER
  // =============================
  Future<void> _openMapPicker() async {
  final result = await Navigator.push(
    context,
    MaterialPageRoute(
      builder: (_) => const PickLocationPage(),
    ),
  );

  if (result != null && mounted) {
    setState(() {
      _lat = result['lat'];
      _lng = result['lng'];
      _addressCtrl.text = result['address'];
    });
  }
}
  // =============================
  // SUBMIT
  // =============================
  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_document == null) {
      _showMsg('Supporting document is required');
      return;
    }

    setState(() => _loading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw 'Session expired';

      final uri = isEdit
          ? Uri.parse('${ApiConfig.apiBase}/requests/${widget.request!['id']}')
          : Uri.parse('${ApiConfig.apiBase}/requests');

      final req = http.MultipartRequest('POST', uri)
        ..headers.addAll({
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        })
        ..fields.addAll({
          'item_name': _itemCtrl.text.trim(),
          'description': _descCtrl.text.trim(),
          'category': _category ?? '',
          'address': _addressCtrl.text.trim(),
        });

      if (_lat != null && _lng != null) {
        req.fields['latitude'] = _lat.toString();
        req.fields['longitude'] = _lng.toString();
      }

      if (isEdit) req.fields['_method'] = 'PUT';

      if (_image != null) {
        req.files.add(
          await http.MultipartFile.fromPath('image', _image!.path),
        );
      }

      req.files.add(
        await http.MultipartFile.fromPath('document', _document!.path),
      );

      final res = await req.send();
      final body = await res.stream.bytesToString();
      final data = jsonDecode(body);

      if ((res.statusCode == 200 || res.statusCode == 201) &&
          data['success'] == true) {
        _showMsg(
          isEdit
              ? 'Request updated. Pending admin review.'
              : 'Request submitted. Pending admin review.',
        );
        Navigator.pop(context, true);
      } else {
        throw data['message'] ?? 'Failed';
      }
    } catch (e) {
      _showMsg(e.toString());
    } finally {
      setState(() => _loading = false);
    }
  }

  void _showMsg(String msg) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(msg)));
  }

  // =============================
  // UI
  // =============================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Help Request' : 'Create Help Request'),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _section('Request Information'),

              _field('Item Name', _itemCtrl),
              _field(
                'Description / Story',
                _descCtrl,
                maxLines: 4,
                helper:
                    'Explain your situation clearly to help admin verification.',
              ),

              DropdownButtonFormField<String>(
                value: _category,
                decoration: _dec('Category'),
                items: const [
                  DropdownMenuItem(value: 'Food', child: Text('Food')),
                  DropdownMenuItem(value: 'Clothing', child: Text('Clothing')),
                  DropdownMenuItem(value: 'Medical', child: Text('Medical')),
                  DropdownMenuItem(value: 'Education', child: Text('Education')),
                  DropdownMenuItem(value: 'Others', child: Text('Others')),
                ],
                onChanged: (v) => setState(() => _category = v),
                validator: (v) => v == null ? 'Required' : null,
              ),

              const SizedBox(height: 20),
              _section('Location'),

              TextFormField(
                controller: _addressCtrl,
                readOnly: true,
                decoration: _dec('Selected Area'),
                validator: (v) =>
                    v == null || v.isEmpty ? 'Required' : null,
              ),

              const SizedBox(height: 10),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _locating ? null : _useGps,
                      icon: const Icon(Icons.my_location),
                      label: const Text('Use GPS'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _openMapPicker,
                      icon: const Icon(Icons.map_outlined),
                      label: const Text('Select on Map'),
                    ),
                  ),
                ],
              ),

              if (_lat != null && _lng != null) ...[
                const SizedBox(height: 12),
                ClipRRect(
                  borderRadius: BorderRadius.circular(14),
                  child: SizedBox(
                    height: 180,
                    child: FlutterMap(
                      options: MapOptions(
                        initialCenter: LatLng(_lat!, _lng!),
                        initialZoom: 14,
                        interactiveFlags: InteractiveFlag.none,
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
              ],

              const SizedBox(height: 26),
              _section('Attachments'),

              _uploadCard(
                title: 'Image',
                file: _image,
                onPick: _pickImage,
              ),

              const SizedBox(height: 12),

              _uploadCard(
                title: 'Supporting Document (Required)',
                subtitle:
                    'Examples: salary slip, medical letter, utility bill',
                file: _document,
                onPick: _pickDocument,
                required: true,
              ),

              const SizedBox(height: 36),

              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _loading ? null : _submit,
                  child: _loading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : Text(isEdit ? 'Update Request' : 'Submit Request'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _section(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Text(
        title,
        style: const TextStyle(
          fontWeight: FontWeight.bold,
          fontSize: 15,
          color: AppColors.primary,
        ),
      ),
    );
  }

  Widget _field(
    String label,
    TextEditingController ctrl, {
    int maxLines = 1,
    String? helper,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: ctrl,
        maxLines: maxLines,
        decoration: _dec(label).copyWith(helperText: helper),
        validator: (v) => v == null || v.isEmpty ? 'Required' : null,
      ),
    );
  }

  InputDecoration _dec(String label) {
    return InputDecoration(
      labelText: label,
      filled: true,
      fillColor: AppColors.surface,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
    );
  }

  Widget _uploadCard({
    required String title,
    String? subtitle,
    required File? file,
    required VoidCallback onPick,
    bool required = false,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Icon(
            required ? Icons.assignment_turned_in : Icons.attach_file,
            color: AppColors.primary,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  file != null
                      ? file.path.split('/').last
                      : 'No file selected',
                  overflow: TextOverflow.ellipsis,
                ),
                if (subtitle != null)
                  Text(
                    subtitle,
                    style: const TextStyle(fontSize: 11, color: Colors.grey),
                  ),
              ],
            ),
          ),
          TextButton(
            onPressed: onPick,
            child: Text(title),
          ),
        ],
      ),
    );
  }
}
