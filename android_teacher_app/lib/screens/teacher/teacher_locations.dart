import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class TeacherLocationsScreen extends StatefulWidget {
  const TeacherLocationsScreen({super.key});

  @override
  State<TeacherLocationsScreen> createState() => _TeacherLocationsScreenState();
}

class _TeacherLocationsScreenState extends State<TeacherLocationsScreen> {
  final _key = GlobalKey<AsyncBuilderState>();

  Future<void> _addLocation() async {
    final label = TextEditingController();
    final address = TextEditingController();
    final city = TextEditingController();
    final pincode = TextEditingController();
    final travel = TextEditingController(text: '0');
    var isDefault = false;

    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Add teaching location'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(controller: label, decoration: const InputDecoration(labelText: 'Label (e.g. Home studio)')),
                const SizedBox(height: 12),
                TextField(controller: address, decoration: const InputDecoration(labelText: 'Address')),
                const SizedBox(height: 12),
                TextField(controller: city, decoration: const InputDecoration(labelText: 'City')),
                const SizedBox(height: 12),
                TextField(
                  controller: pincode,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Pincode'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: travel,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Travel charge (₹)'),
                ),
                const SizedBox(height: 4),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  value: isDefault,
                  title: const Text('Default location'),
                  onChanged: (value) => setDialogState(() => isDefault = value),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
            FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Save')),
          ],
        ),
      ),
    );

    if (saved != true) return;

    if (label.text.trim().isEmpty || address.text.trim().isEmpty || city.text.trim().isEmpty) {
      if (mounted) showSnack(context, 'Label, address and city are required.', error: true);
      return;
    }

    try {
      final response = await Api.instance.post('/teacher/locations', {
        'label': label.text.trim(),
        'type': 'teacher_location',
        'address_line1': address.text.trim(),
        'city': city.text.trim(),
        'pincode': pincode.text.trim(),
        'travel_charge': double.tryParse(travel.text) ?? 0,
        'is_default': isDefault,
      });
      if (!mounted) return;
      showSnack(context, response.str('message', 'Location saved.'));
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    }
  }

  Future<void> _delete(Map<String, dynamic> location) async {
    final confirmed = await confirmDialog(
      context,
      title: 'Remove location',
      message: 'Remove "${location.str('label')}"?',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!confirmed) return;

    try {
      final response = await Api.instance.delete('/teacher/locations/${location.integer('id')}');
      if (!mounted) return;
      showSnack(context, response.str('message', 'Location removed.'));
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Teaching locations'),
        actions: [
          IconButton(onPressed: _addLocation, icon: const Icon(Icons.add_location_alt_outlined)),
        ],
      ),
      body: AsyncBuilder(
        key: _key,
        loader: () => Api.instance.get('/teacher/locations'),
        builder: (context, data) {
          final locations = data.objects('locations');

          if (locations.isEmpty) {
            return EmptyState(
              icon: Icons.place_outlined,
              message: 'No teaching locations yet.\nAdd the places where parents can book offline classes.',
              action: FilledButton.icon(
                onPressed: _addLocation,
                icon: const Icon(Icons.add),
                label: const Text('Add location'),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => _key.currentState!.reload(),
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
              itemCount: locations.length,
              itemBuilder: (context, index) {
                final location = locations[index];

                return Card(
                  elevation: 0,
                  color: Colors.white,
                  margin: const EdgeInsets.only(bottom: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: Theme.of(context).colorScheme.primary.withValues(alpha: 0.12),
                      child: Icon(Icons.place_outlined, color: Theme.of(context).colorScheme.primary),
                    ),
                    title: Row(
                      children: [
                        Expanded(
                          child: Text(
                            location.str('label'),
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ),
                        if (location.boolean('is_default'))
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.green.shade50,
                              borderRadius: BorderRadius.circular(99),
                            ),
                            child: Text(
                              'Default',
                              style: TextStyle(fontSize: 10.5, color: Colors.green.shade800),
                            ),
                          ),
                      ],
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('${location.str('address_line1')}, ${location.str('city')}'),
                        if (location.dbl('travel_charge') > 0)
                          Text(
                            'Travel charge ${formatMoney(location.dbl('travel_charge'))}',
                            style: const TextStyle(fontSize: 11.5),
                          ),
                      ],
                    ),
                    isThreeLine: location.dbl('travel_charge') > 0,
                    trailing: IconButton(
                      onPressed: () => _delete(location),
                      icon: Icon(Icons.delete_outline, color: Colors.red.shade400),
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
