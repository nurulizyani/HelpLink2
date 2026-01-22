import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';

class PickLocationPage extends StatefulWidget {
  const PickLocationPage({super.key});

  @override
  State<PickLocationPage> createState() => _PickLocationPageState();
}

class _PickLocationPageState extends State<PickLocationPage> {
  final MapController _mapController = MapController();
  final TextEditingController _searchController = TextEditingController();

  LatLng? selectedLocation;
  String? selectedAddress;

  List<dynamic> _searchResults = [];
  bool _searching = false;

  // =============================
  // SEARCH PLACE (NOMINATIM)
  // =============================
  Future<void> _searchPlace(String query) async {
    if (query.trim().isEmpty) return;

    setState(() {
      _searching = true;
      _searchResults.clear();
    });

    final url = Uri.parse(
      'https://nominatim.openstreetmap.org/search'
      '?q=$query&format=json&limit=5',
    );

    final res = await http.get(
      url,
      headers: {
        'User-Agent': 'helplink-app', // required by Nominatim
      },
    );

    if (res.statusCode == 200) {
      setState(() {
        _searchResults = jsonDecode(res.body);
      });
    }

    setState(() => _searching = false);
  }

  // =============================
  // SELECT SEARCH RESULT
  // =============================
  void _selectSearchResult(dynamic place) {
    final lat = double.parse(place['lat']);
    final lng = double.parse(place['lon']);

    setState(() {
      selectedLocation = LatLng(lat, lng);
      selectedAddress = place['display_name'];
      _searchResults.clear();
      _searchController.text = place['display_name'];
    });

    _mapController.move(selectedLocation!, 15);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Location'),
        backgroundColor: const Color(0xFF4F6D7A),
        actions: [
          IconButton(
            icon: const Icon(Icons.check),
            onPressed: selectedLocation == null
                ? null
                : () {
                    Navigator.pop(context, {
                      'lat': selectedLocation!.latitude,
                      'lng': selectedLocation!.longitude,
                      'address': selectedAddress ??
                          '${selectedLocation!.latitude}, ${selectedLocation!.longitude}',
                    });
                  },
          ),
        ],
      ),
      body: Column(
        children: [
          // =============================
          // SEARCH BAR
          // =============================
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _searchController,
              textInputAction: TextInputAction.search,
              onSubmitted: _searchPlace,
              decoration: InputDecoration(
                hintText: 'Search area, landmark, or place name',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searching
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              setState(() {
                                _searchController.clear();
                                _searchResults.clear();
                              });
                            },
                          )
                        : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),

          // =============================
          // SEARCH RESULTS
          // =============================
          if (_searchResults.isNotEmpty)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: const [
                  BoxShadow(
                    color: Colors.black12,
                    blurRadius: 6,
                  ),
                ],
              ),
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: _searchResults.length,
                itemBuilder: (context, index) {
                  final place = _searchResults[index];
                  return ListTile(
                    leading: const Icon(Icons.place_outlined),
                    title: Text(
                      place['display_name'],
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    onTap: () => _selectSearchResult(place),
                  );
                },
              ),
            ),

          // =============================
          // MAP
          // =============================
          Expanded(
            child: FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: LatLng(2.1896, 102.2501), // Melaka
                initialZoom: 13,
                onTap: (tapPosition, point) {
                  setState(() {
                    selectedLocation = point;
                    selectedAddress = null;
                  });
                },
              ),
              children: [
                TileLayer(
                  urlTemplate:
                      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                  subdomains: const ['a', 'b', 'c'],
                ),
                if (selectedLocation != null)
                  MarkerLayer(
                    markers: [
                      Marker(
                        point: selectedLocation!,
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

          // =============================
          // HELPER TEXT
          // =============================
          const Padding(
            padding: EdgeInsets.all(10),
            child: Text(
              'Tip: You can search a place, then tap the map to fine-tune the location.',
              style: TextStyle(
                fontSize: 12,
                color: Colors.black54,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
