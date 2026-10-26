import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class TeacherFeesView extends StatefulWidget {
  const TeacherFeesView({super.key});

  @override
  State<TeacherFeesView> createState() => _TeacherFeesViewState();
}

class _TeacherFeesViewState extends State<TeacherFeesView> {
  final _key = GlobalKey<AsyncBuilderState>();
  bool _busy = false;

  Future<void> _run(Future<Map<String, dynamic>> Function() action) async {
    setState(() => _busy = true);
    try {
      final response = await action();
      if (!mounted) return;
      showSnack(context, response.str('message', 'Saved'));
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _feeDialog(
    Map<String, dynamic> catalog, {
    Map<String, dynamic>? existing,
  }) async {
    final subjects = catalog.objects('subjects');
    final boards = catalog.objects('boards');
    final classes = catalog.objects('classes');

    int? subjectId = existing?.integer('subject_id');
    int? boardId = existing?.integer('board_id');
    int? classId = existing?.integer('class_standard_id');
    var mode = existing?.str('mode', 'both') ?? 'both';

    final hourlyRate = TextEditingController(text: existing?.dbl('hourly_rate').toString() ?? '500');
    final sessionMinutes = TextEditingController(text: existing?.integer('session_minutes', 60).toString() ?? '60');
    final travelCharge = TextEditingController(text: existing?.dbl('travel_charge').toString() ?? '0');

    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(existing == null ? 'Add teaching fee' : 'Edit teaching fee'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<int?>(
                  initialValue: subjectId,
                  decoration: const InputDecoration(labelText: 'Subject'),
                  hint: const Text('Any subject'),
                  items: [
                    const DropdownMenuItem<int?>(value: null, child: Text('Any subject')),
                    ...subjects.map(
                      (subject) => DropdownMenuItem<int?>(
                        value: subject.integer('id'),
                        child: Text(subject.str('name')),
                      ),
                    ),
                  ],
                  onChanged: (value) => setDialogState(() => subjectId = value),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<int?>(
                  initialValue: classId,
                  decoration: const InputDecoration(labelText: 'Class'),
                  hint: const Text('Any class'),
                  items: [
                    const DropdownMenuItem<int?>(value: null, child: Text('Any class')),
                    ...classes.map(
                      (row) => DropdownMenuItem<int?>(
                        value: row.integer('id'),
                        child: Text(row.str('name')),
                      ),
                    ),
                  ],
                  onChanged: (value) => setDialogState(() => classId = value),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<int?>(
                  initialValue: boardId,
                  decoration: const InputDecoration(labelText: 'Board'),
                  hint: const Text('Any board'),
                  items: [
                    const DropdownMenuItem<int?>(value: null, child: Text('Any board')),
                    ...boards.map(
                      (row) => DropdownMenuItem<int?>(
                        value: row.integer('id'),
                        child: Text(row.str('name')),
                      ),
                    ),
                  ],
                  onChanged: (value) => setDialogState(() => boardId = value),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: mode,
                  decoration: const InputDecoration(labelText: 'Mode'),
                  items: const [
                    DropdownMenuItem(value: 'both', child: Text('Online & offline')),
                    DropdownMenuItem(value: 'online', child: Text('Online only')),
                    DropdownMenuItem(value: 'offline', child: Text('Offline only')),
                  ],
                  onChanged: (value) => setDialogState(() => mode = value ?? 'both'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: hourlyRate,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Hourly rate (₹)'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: sessionMinutes,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Session length (minutes)'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: travelCharge,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Travel charge (₹)'),
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

    final body = {
      'subject_id': subjectId,
      'class_standard_id': classId,
      'board_id': boardId,
      'mode': mode,
      'hourly_rate': double.tryParse(hourlyRate.text) ?? 0,
      'session_minutes': int.tryParse(sessionMinutes.text) ?? 60,
      'travel_charge': double.tryParse(travelCharge.text) ?? 0,
    };

    if (existing == null) {
      await _run(() => Api.instance.post('/teacher/fees', body));
    } else {
      await _run(() => Api.instance.put('/teacher/fees/${existing.integer('id')}', body));
    }
  }

  Future<void> _packageDialog() async {
    final name = TextEditingController(text: 'Monthly package');
    final sessions = TextEditingController(text: '8');
    final minutes = TextEditingController(text: '60');
    final price = TextEditingController(text: '500');

    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('New class package'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: name, decoration: const InputDecoration(labelText: 'Package name')),
              const SizedBox(height: 12),
              TextField(
                controller: sessions,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Number of sessions'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: minutes,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Minutes per session'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: price,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Price per session (₹)'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Create')),
        ],
      ),
    );

    if (saved != true) return;

    await _run(
      () => Api.instance.post('/teacher/packages', {
        'name': name.text.trim(),
        'mode': 'both',
        'total_sessions': int.tryParse(sessions.text) ?? 8,
        'session_minutes': int.tryParse(minutes.text) ?? 60,
        'price_per_session': double.tryParse(price.text) ?? 0,
      }),
    );
  }

  Future<void> _delete(String path, String label) async {
    final confirmed = await confirmDialog(
      context,
      title: 'Delete $label',
      message: 'Delete this $label? This cannot be undone.',
      confirmLabel: 'Delete',
      destructive: true,
    );
    if (!confirmed) return;
    await _run(() => Api.instance.delete(path));
  }

  String _feeTitle(Map<String, dynamic> fee) {
    final subject = fee.obj('subject')?.str('name');
    final classStandard = fee.obj('class_standard')?.str('name');
    final board = fee.obj('board')?.str('name');
    final parts = [subject ?? 'Any subject', classStandard ?? 'Any class', board ?? 'Any board'];
    return parts.join(' • ');
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        AsyncBuilder(
          key: _key,
          loader: () => Api.instance.get('/teacher/fees'),
          builder: (context, data) {
            final fees = data.objects('fees');
            final packages = data.objects('packages');
            final catalog = data.obj('catalog') ?? const <String, dynamic>{};

            return RefreshIndicator(
              onRefresh: () => _key.currentState!.reload(),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
                children: [
                  SectionCard(
                    title: 'Teaching fees',
                    trailing: TextButton.icon(
                      onPressed: _busy ? null : () => _feeDialog(catalog),
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('Add'),
                    ),
                    child: fees.isEmpty
                        ? Text(
                            'No fees yet. Parents can only book you after you publish a rate for their subject.',
                            style: TextStyle(color: Colors.grey.shade600),
                          )
                        : Column(
                            children: fees.map((fee) {
                              final active = fee.boolean('is_active', true);

                              return Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Expanded(
                                                child: Text(
                                                  _feeTitle(fee),
                                                  style: const TextStyle(fontWeight: FontWeight.w600),
                                                ),
                                              ),
                                              if (!active) const StatusChip(status: 'hidden', compact: true),
                                            ],
                                          ),
                                          const SizedBox(height: 3),
                                          Text(
                                            '${formatMoney(fee.dbl('hourly_rate'))}/hr • '
                                            '${fee.integer('session_minutes')} min • ${humanise(fee.str('mode'))}',
                                            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                          ),
                                        ],
                                      ),
                                    ),
                                    IconButton(
                                      onPressed: _busy ? null : () => _feeDialog(catalog, existing: fee),
                                      icon: const Icon(Icons.edit_outlined, size: 20),
                                    ),
                                    IconButton(
                                      onPressed: _busy
                                          ? null
                                          : () => _delete('/teacher/fees/${fee.integer('id')}', 'fee'),
                                      icon: Icon(Icons.delete_outline, color: Colors.red.shade400, size: 20),
                                    ),
                                  ],
                                ),
                              );
                            }).toList(),
                          ),
                  ),
                  SectionCard(
                    title: 'Class packages',
                    trailing: TextButton.icon(
                      onPressed: _busy ? null : _packageDialog,
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('Add'),
                    ),
                    child: packages.isEmpty
                        ? Text(
                            'Sell a bundle of sessions as a package. Parents can book it at a fixed price.',
                            style: TextStyle(color: Colors.grey.shade600),
                          )
                        : Column(
                            children: packages.map((package) {
                              return Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            package.str('name'),
                                            style: const TextStyle(fontWeight: FontWeight.w600),
                                          ),
                                          const SizedBox(height: 3),
                                          Text(
                                            '${package.integer('total_sessions')} sessions × '
                                            '${package.integer('session_minutes')} min • '
                                            '${formatMoney(package.dbl('total_price'))}',
                                            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                          ),
                                        ],
                                      ),
                                    ),
                                    IconButton(
                                      onPressed: _busy
                                          ? null
                                          : () => _delete(
                                                '/teacher/packages/${package.integer('id')}',
                                                'package',
                                              ),
                                      icon: Icon(Icons.delete_outline, color: Colors.red.shade400, size: 20),
                                    ),
                                  ],
                                ),
                              );
                            }).toList(),
                          ),
                  ),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.tips_and_updates_outlined, size: 20, color: Colors.amber.shade700),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            'The most specific active fee wins: a fee for one subject and class beats a general hourly rate.',
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        ),
        if (_busy) const LinearProgressIndicator(minHeight: 2),
      ],
    );
  }
}
