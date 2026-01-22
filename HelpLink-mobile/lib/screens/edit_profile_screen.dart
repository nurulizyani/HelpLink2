import 'dart:io';
import 'package:flutter/material.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:image_picker/image_picker.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

import 'package:part_1/theme/app_colors.dart';

class EditProfileScreen extends StatefulWidget {
  final Map<String, dynamic> userData;

  const EditProfileScreen({
    super.key,
    required this.userData,
  });

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  late TextEditingController _addressController;
  late TextEditingController _emailController;

  final ImagePicker _picker = ImagePicker();
  File? _imageFile;

  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _nameController =
        TextEditingController(text: widget.userData['name'] ?? '');
    _phoneController =
        TextEditingController(text: widget.userData['phone'] ?? '');
    _addressController =
        TextEditingController(text: widget.userData['address'] ?? '');
    _emailController =
        TextEditingController(text: widget.userData['email'] ?? '');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  // =============================
  // PICK IMAGE SOURCE
  // =============================
  Future<void> _pickImageSource() async {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: const Text('Take Photo'),
              onTap: () async {
                Navigator.pop(context);
                await _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Choose from Gallery'),
              onTap: () async {
                Navigator.pop(context);
                await _pickImage(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    final picked = await _picker.pickImage(
      source: source,
      imageQuality: 75,
      maxWidth: 1024,
    );

    if (picked != null) {
      setState(() {
        _imageFile = File(picked.path);
      });
    }
  }

  // =============================
  // UPLOAD IMAGE TO SUPABASE
  // =============================
  Future<String?> _uploadProfileImage(String uid) async {
    if (_imageFile == null) {
      return widget.userData['photoUrl']; // tak tukar gambar
    }

    final supabase = Supabase.instance.client;
    final fileExt = _imageFile!.path.split('.').last;
    final filePath = 'profiles/$uid.$fileExt';

    await supabase.storage
        .from('profile-images')
        .upload(
          filePath,
          _imageFile!,
          fileOptions: const FileOptions(
            upsert: true,
            contentType: 'image/jpeg',
          ),
        );

    return supabase.storage
        .from('profile-images')
        .getPublicUrl(filePath);
  }

  // =============================
  // SAVE PROFILE
  // =============================
  Future<void> _saveProfile() async {
    if (_isSaving) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Save Changes'),
        content: const Text(
          'Are you sure you want to save these profile changes?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Save'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isSaving = true);

    try {
      final user = FirebaseAuth.instance.currentUser;
      if (user == null) throw Exception('User not logged in');

      final uid = user.uid;
      final photoUrl = await _uploadProfileImage(uid);

      await FirebaseFirestore.instance.collection('users').doc(uid).update({
        'phone': _phoneController.text.trim(),
        'address': _addressController.text.trim(),
        if (photoUrl != null) 'photoUrl': photoUrl,
        'updated_at': FieldValue.serverTimestamp(),
      });

      if (!mounted) return;

      Navigator.pop(context, true);

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Profile updated successfully'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to update profile: $e'),
          backgroundColor: AppColors.danger,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final photoUrl = widget.userData['photoUrl'];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Edit Profile'),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(22),
        child: Column(
          children: [
            // AVATAR
            GestureDetector(
              onTap: _pickImageSource,
              child: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: AppColors.primary,
                    width: 2,
                  ),
                ),
                child: CircleAvatar(
                  radius: 52,
                  backgroundColor: AppColors.accent,
                  backgroundImage: _imageFile != null
                      ? FileImage(_imageFile!)
                      : (photoUrl != null ? NetworkImage(photoUrl) : null)
                          as ImageProvider?,
                  child: _imageFile == null && photoUrl == null
                      ? const Icon(
                          Icons.camera_alt_rounded,
                          size: 32,
                          color: AppColors.primary,
                        )
                      : null,
                ),
              ),
            ),

            const SizedBox(height: 28),

            // FORM
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.card,
                borderRadius: BorderRadius.circular(18),
                boxShadow: [
                  BoxShadow(
                    blurRadius: 10,
                    color: Colors.black.withOpacity(0.06),
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  _input(
                    label: 'Full Name',
                    icon: Icons.person_outline,
                    controller: _nameController,
                    readOnly: true,
                    helper:
                        'Name changes require administrator verification',
                  ),
                  _input(
                    label: 'Email Address',
                    icon: Icons.email_outlined,
                    controller: _emailController,
                    readOnly: true,
                    helper: 'Email cannot be changed',
                  ),
                  _input(
                    label: 'Phone Number',
                    icon: Icons.phone_outlined,
                    controller: _phoneController,
                    keyboard: TextInputType.phone,
                  ),
                  _input(
                    label: 'Address',
                    icon: Icons.location_on_outlined,
                    controller: _addressController,
                    maxLines: 2,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 28),

            _isSaving
                ? const CircularProgressIndicator(color: AppColors.primary)
                : SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _saveProfile,
                      icon: const Icon(Icons.save_rounded),
                      label: const Text(
                        'Save Changes',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 16,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                    ),
                  ),
          ],
        ),
      ),
    );
  }

  Widget _input({
    required String label,
    required IconData icon,
    required TextEditingController controller,
    int maxLines = 1,
    TextInputType keyboard = TextInputType.text,
    bool readOnly = false,
    String? helper,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: TextField(
        controller: controller,
        maxLines: maxLines,
        keyboardType: keyboard,
        readOnly: readOnly,
        decoration: InputDecoration(
          labelText: label,
          helperText: helper,
          prefixIcon: Icon(icon, color: AppColors.primary),
          filled: true,
          fillColor: AppColors.accent,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide.none,
          ),
        ),
      ),
    );
  }
}
