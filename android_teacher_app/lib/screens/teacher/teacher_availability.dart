import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class TeacherAvailabilityView extends StatefulWidget {
  const TeacherAvailabilityView({super.key});

  @override
  State<TeacherAvailabilityView> createState() => _TeacherAvailabilityViewState();
}

class _TeacherAvailabilityViewState extends State<TeacherAvailabilityView> {
  final _key = GlobalKey<AsyncBuilderState>();
  bool _busy = false;

  String _timeLabel(String raw) {
    final parts = raw.split(':');
    if (parts.length < 2) return raw;
    final hour = int.tryParse(parts[0]) ?? 0;
    final minute = parts[1];
    final display = hour % 12 == 0 ? 12 : hour % 12;
    return '$display:$minute ${hour >= 12 ? 'PM' : 'AM'}';
  }

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

  Future<void> _addRule(Map<String, dynamic> catalog) async {
    final weekdays = catalog.obj('weekdays') ?? const <String, dynamic>{};
    var weekday = '1';
    var mode = 'both';
    var start = const TimeOfDay(hour: 17, minute: 0);
    var end = const TimeOfDay(hour: 19, minute: 0);
    final sessionMinutes = TextEditingController(text: '60');
    final bufferMinutes = TextEditingController(text: '0');

    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) {
          String label(TimeOfDay time) {
            final hour = time.hour % 12 == 0 ? 12 : time.hour % 12;
            return '$hour:${time.minute.toString().padLeft(2, '0')} ${time.hour >= 12 ? 'PM' : 'AM'}';
          }

          return AlertDialog(
            title: const Text('Weekly availability'),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  DropdownButtonFormField<String>(
                    initialValue: weekday,
                    decoration: const InputDecoration(labelText: 'Weekday'),
                    items: weekdays.entries
                        .map(
                          (entry) => DropdownMenuItem(
                            value: entry.key,
                            child: Text(entry.value.toString()),
                          ),
                        )
                        .toList(),
                    onChanged: (value) => setDialogState(() => weekday = value ?? '1'),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () async {
                            final picked = await showTimePicker(context: context, initialTime: start);
                            if (picked != null) setDialogState(() => start = picked);
                          },
                          child: Text('From ${label(start)}'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () async {
                            final picked = await showTimePicker(context: context, initialTime: end);
                            if (picked != null) setDialogState(() => end = picked);
                          },
                          child: Text('To ${label(end)}'),
                        ),
                      ),
                    ],
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
                    controller: sessionMinutes,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Session length (minutes)'),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: bufferMinutes,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Buffer between classes (minutes)'),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
              FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Add')),
            ],
          );
        },
      ),
    );

    if (saved != true) return;

    await _run(
      () => Api.instance.post('/teacher/availability/rules', {
        'weekday': int.parse(weekday),
        'start_time': '${start.hour.toString().padLeft(2, '0')}:${start.minute.toString().padLeft(2, '0')}',
        'end_time': '${end.hour.toString().padLeft(2, '0')}:${end.minute.toString().padLeft(2, '0')}',
        'mode': mode,
        'session_minutes': int.tryParse(sessionMinutes.text) ?? 60,
        'buffer_minutes': int.tryParse(bufferMinutes.text) ?? 0,
      }),
    );
  }

  Future<void> _addSlot() async {
    final date = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (date == null || !mounted) return;

    var start = const TimeOfDay(hour: 10, minute: 0);
    var end = const TimeOfDay(hour: 11, minute: 0);
    var status = 'available';
    var mode = 'both';
    final note = TextEditingController();

    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) {
          String label(TimeOfDay time) {
            final hour = time.hour % 12 == 0 ? 12 : time.hour % 12;
            return '$hour:${time.minute.toString().padLeft(2, '0')} ${time.hour >= 12 ? 'PM' : 'AM'}';
          }

          return AlertDialog(
            title: Text('One-off slot · ${formatDate(date.toIso8601String(), withTime: false)}'),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () async {
                            final picked = await showTimePicker(context: context, initialTime: start);
                            if (picked != null) setDialogState(() => start = picked);
                          },
                          child: Text('From ${label(start)}'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () async {
                            final picked = await showTimePicker(context: context, initialTime: end);
                            if (picked != null) setDialogState(() => end = picked);
                          },
                          child: Text('To ${label(end)}'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: status,
                    decoration: const InputDecoration(labelText: 'Type'),
                    items: const [
                      DropdownMenuItem(value: 'available', child: Text('Available')),
                      DropdownMenuItem(value: 'blocked', child: Text('Blocked')),
                      DropdownMenuItem(value: 'holiday', child: Text('Holiday')),
                    ],
                    onChanged: (value) => setDialogState(() => status = value ?? 'available'),
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
                    controller: note,
                    decoration: const InputDecoration(labelText: 'Note (optional)'),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
              FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Add')),
            ],
          );
        },
      ),
    );

    if (saved != true) return;

    await _run(
      () => Api.instance.post('/teacher/availability/slots', {
        'slot_date': date.toIso8601String().substring(0, 10),
        'start_time': '${start.hour.toString().padLeft(2, '0')}:${start.minute.toString().padLeft(2, '0')}',
        'end_time': '${end.hour.toString().padLeft(2, '0')}:${end.minute.toString().padLeft(2, '0')}',
        'status': status,
        'mode': mode,
        'note': note.text.trim(),
      }),
    );
  }

  Future<void> _delete(String path, String what) async {
    final confirmed = await confirmDialog(
      context,
      title: 'Remove $what',
      message: 'Remove this $what? Existing bookings are not affected.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!confirmed) return;
    await _run(() => Api.instance.delete(path));
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        AsyncBuilder(
          key: _key,
          loader: () => Api.instance.get('/teacher/availability'),
          builder: (context, data) {
            final rules = data.objects('rules');
            final slots = data.objects('slots');
            final catalog = data;
            final calendar = data.obj('calendar') ?? const <String, dynamic>{};
            final days = calendar.objects('days').where((day) => !day.boolean('is_past')).toList();
            final totalAvailable = days.fold<int>(0, (sum, day) => sum + day.integer('available_slots'));

            return RefreshIndicator(
              onRefresh: () => _key.currentState!.reload(),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
                children: [
                  SectionCard(
                    title: calendar.str('label', 'This month'),
                    trailing: Text(
                      '$totalAvailable slots',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                    ),
                    child: SizedBox(
                      height: 74,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: days.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 8),
                        itemBuilder: (context, index) {
                          final day = days[index];
                          final available = day.integer('available_slots');
                          final isToday = day.boolean('is_today');

                          return Container(
                            width: 54,
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            decoration: BoxDecoration(
                              color: available > 0
                                  ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.08)
                                  : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(12),
                              border: isToday
                                  ? Border.all(color: Theme.of(context).colorScheme.primary, width: 1.4)
                                  : null,
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  formatDate(day.str('date'), withTime: false).split(' ').take(2).join(' '),
                                  style: TextStyle(fontSize: 11, color: Colors.grey.shade700),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '$available',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w800,
                                    color: available > 0
                                        ? Theme.of(context).colorScheme.primary
                                        : Colors.grey.shade500,
                                  ),
                                ),
                                Text('slots', style: TextStyle(fontSize: 9.5, color: Colors.grey.shade500)),
                              ],
                            ),
                          );
                        },
                      ),
                    ),
                  ),
                  SectionCard(
                    title: 'Weekly availability',
                    trailing: TextButton.icon(
                      onPressed: _busy ? null : () => _addRule(catalog),
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('Add'),
                    ),
                    child: rules.isEmpty
                        ? Text(
                            'Add your weekly teaching hours so parents can book you.',
                            style: TextStyle(color: Colors.grey.shade600),
                          )
                        : Column(
                            children: rules
                                .map(
                                  (rule) => _AvailabilityRow(
                                    title: (data.obj('weekdays')?[rule.str('weekday')] ??
                                            humanise(rule.str('weekday')))
                                        .toString(),
                                    subtitle:
                                        '${_timeLabel(rule.str('start_time'))} – ${_timeLabel(rule.str('end_time'))} • '
                                        '${humanise(rule.str('mode'))} • ${rule.integer('session_minutes')} min',
                                    onDelete: () => _delete(
                                      '/teacher/availability/rules/${rule.integer('id')}',
                                      'weekly rule',
                                    ),
                                  ),
                                )
                                .toList(),
                          ),
                  ),
                  SectionCard(
                    title: 'One-off slots',
                    trailing: TextButton.icon(
                      onPressed: _busy ? null : _addSlot,
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('Add'),
                    ),
                    child: slots.isEmpty
                        ? Text(
                            'No upcoming one-off slots. Weekly rules are enough for most teachers.',
                            style: TextStyle(color: Colors.grey.shade600),
                          )
                        : Column(
                            children: slots
                                .map(
                                  (slot) => _AvailabilityRow(
                                    title: formatDate(slot.str('slot_date'), withTime: false),
                                    subtitle:
                                        '${_timeLabel(slot.str('start_time'))} – ${_timeLabel(slot.str('end_time'))} • '
                                        '${humanise(slot.str('status'))} • ${humanise(slot.str('mode'))}',
                                    badge: slot.str('status'),
                                    onDelete: () => _delete(
                                      '/teacher/availability/slots/${slot.integer('id')}',
                                      'slot',
                                    ),
                                  ),
                                )
                                .toList(),
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
                        Icon(Icons.info_outline, size: 20, color: Theme.of(context).colorScheme.primary),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            'Bookable slots are generated from your weekly rules using the platform slot length of '
                            '${data.integer('granularity_minutes', 30)} minutes.',
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

class _AvailabilityRow extends StatelessWidget {
  const _AvailabilityRow({
    required this.title,
    required this.subtitle,
    required this.onDelete,
    this.badge,
  });

  final String title;
  final String subtitle;
  final VoidCallback onDelete;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
                    if (badge != null) ...[
                      const SizedBox(width: 8),
                      StatusChip(status: badge!, compact: true),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(subtitle, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
              ],
            ),
          ),
          IconButton(
            onPressed: onDelete,
            icon: Icon(Icons.delete_outline, color: Colors.red.shade400, size: 20),
          ),
        ],
      ),
    );
  }
}
