import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class TeacherBookingDetailScreen extends StatefulWidget {
  const TeacherBookingDetailScreen({super.key, required this.bookingId});

  final int bookingId;

  @override
  State<TeacherBookingDetailScreen> createState() => _TeacherBookingDetailScreenState();
}

class _TeacherBookingDetailScreenState extends State<TeacherBookingDetailScreen> {
  final _key = GlobalKey<AsyncBuilderState>();
  bool _busy = false;

  Future<void> _run(Future<Map<String, dynamic>> Function() action) async {
    setState(() => _busy = true);
    try {
      final response = await action();
      if (!mounted) return;
      showSnack(context, response.str('message', 'Done'));
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _accept() async {
    final confirmed = await confirmDialog(
      context,
      title: 'Accept class',
      message: 'Accept this class? The parent will be notified immediately.',
      confirmLabel: 'Accept',
    );
    if (!confirmed) return;
    await _run(() => Api.instance.post('/teacher/bookings/${widget.bookingId}/accept'));
  }

  Future<void> _reject() async {
    final reason = await promptDialog(
      context,
      title: 'Decline request',
      hint: 'Reason (optional)',
      confirmLabel: 'Decline',
    );
    if (reason == null) return;
    await _run(
      () => Api.instance.post('/teacher/bookings/${widget.bookingId}/reject', {'reason': reason}),
    );
  }

  Future<void> _cancel() async {
    final reason = await promptDialog(
      context,
      title: 'Cancel class',
      hint: 'Please explain why the class is cancelled',
      confirmLabel: 'Cancel class',
    );
    if (reason == null || reason.isEmpty) return;
    await _run(
      () => Api.instance.post('/teacher/bookings/${widget.bookingId}/cancel', {'reason': reason}),
    );
  }

  Future<void> _complete() async {
    final confirmed = await confirmDialog(
      context,
      title: 'Complete session',
      message: 'Mark this class as completed? The parent will be able to leave a review.',
      confirmLabel: 'Complete',
    );
    if (!confirmed) return;
    await _run(() => Api.instance.post('/teacher/bookings/${widget.bookingId}/complete'));
  }

  Future<void> _markAttendance() async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Padding(
              padding: EdgeInsets.all(16),
              child: Text('Mark attendance', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            ),
            ListTile(
              leading: const Icon(Icons.check_circle_outline, color: Colors.green),
              title: const Text('Present'),
              onTap: () => Navigator.pop(context, 'present'),
            ),
            ListTile(
              leading: const Icon(Icons.timer_outlined, color: Colors.orange),
              title: const Text('Late'),
              onTap: () => Navigator.pop(context, 'late'),
            ),
            ListTile(
              leading: const Icon(Icons.cancel_outlined, color: Colors.red),
              title: const Text('Absent'),
              onTap: () => Navigator.pop(context, 'absent'),
            ),
          ],
        ),
      ),
    );

    if (choice == null) return;
    await _run(
      () => Api.instance.post(
        '/teacher/bookings/${widget.bookingId}/attendance',
        {'status': choice},
      ),
    );
  }

  Future<void> _reschedule() async {
    final date = await showDatePicker(
      context: context,
      initialDate: DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 180)),
    );
    if (date == null || !mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 17, minute: 0),
    );
    if (time == null) return;

    final start = DateTime(date.year, date.month, date.day, time.hour, time.minute);
    final confirmed = await confirmDialog(
      context,
      title: 'Move class',
      message: 'Move the class to ${formatDateTime(start)}?',
      confirmLabel: 'Move class',
    );
    if (!confirmed) return;

    await _run(
      () => Api.instance.post('/teacher/bookings/${widget.bookingId}/reschedule', {
        'start_at': start.toIso8601String(),
        'end_at': start.add(const Duration(minutes: 60)).toIso8601String(),
      }),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Booking details'),
        actions: [
          if (_busy)
            const Padding(
              padding: EdgeInsets.only(right: 16),
              child: Center(
                child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2.2)),
              ),
            ),
        ],
      ),
      body: AsyncBuilder(
        key: _key,
        loader: () => Api.instance.get('/teacher/bookings/${widget.bookingId}'),
        builder: (context, data) {
          final booking = data.obj('booking') ?? const <String, dynamic>{};
          final can = booking.obj('can') ?? const <String, dynamic>{};
          final student = booking.obj('student');
          final parent = booking.obj('parent');
          final subject = booking.obj('subject');
          final classStandard = booking.obj('class_standard');
          final board = booking.obj('board');
          final location = booking.obj('location');
          final payments = booking.objects('payments');
          final attendance = booking.obj('attendance');
          final onlineSession = booking.obj('online_session');

          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      booking.str('reference_no'),
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
                    ),
                  ),
                  StatusChip(status: booking.str('status')),
                ],
              ),
              const SizedBox(height: 12),
              SectionCard(
                title: 'Student',
                child: Column(
                  children: [
                    Row(
                      children: [
                        CircleAvatarLabel(name: student?.str('name') ?? 'Student', radius: 24),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                student?.str('name') ?? 'Student',
                                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                [
                                  if (subject != null) subject.str('name'),
                                  if (classStandard != null) classStandard.str('name'),
                                  if (board != null) board.str('name'),
                                ].join(' • '),
                                style: TextStyle(color: Colors.grey.shade600, fontSize: 12.5),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    if (parent != null) ...[
                      const Divider(height: 24),
                      DetailLine(
                        icon: Icons.person_outline,
                        label: 'Parent',
                        value: parent.str('name'),
                      ),
                      if (parent.str('mobile').isNotEmpty)
                        DetailLine(
                          icon: Icons.phone_outlined,
                          label: 'Mobile',
                          value: parent.str('mobile'),
                        ),
                      if (parent.str('email').isNotEmpty)
                        DetailLine(
                          icon: Icons.mail_outline,
                          label: 'Email',
                          value: parent.str('email'),
                        ),
                    ],
                  ],
                ),
              ),
              SectionCard(
                title: 'Schedule',
                child: Column(
                  children: [
                    DetailLine(
                      icon: Icons.calendar_today_outlined,
                      label: 'Date',
                      value: formatDate(booking.str('start_at'), withTime: false),
                    ),
                    DetailLine(
                      icon: Icons.schedule,
                      label: 'Time',
                      value: '${formatTime(booking.str('start_at'))} – ${formatTime(booking.str('end_at'))}',
                    ),
                    DetailLine(
                      icon: booking.str('mode') == 'online' ? Icons.videocam_outlined : Icons.place_outlined,
                      label: 'Mode',
                      value: humanise(booking.str('mode')),
                    ),
                    DetailLine(
                      icon: Icons.timelapse,
                      label: 'Duration',
                      value: '${booking.integer('duration_minutes')} minutes',
                    ),
                    if (location != null)
                      DetailLine(
                        icon: Icons.location_on_outlined,
                        label: 'Location',
                        value: '${location.str('label')}, ${location.str('city')}',
                      ),
                    if (onlineSession != null && onlineSession.str('meeting_url').isNotEmpty)
                      DetailLine(
                        icon: Icons.link,
                        label: 'Meeting link',
                        value: onlineSession.str('meeting_url'),
                      ),
                  ],
                ),
              ),
              SectionCard(
                title: 'Payment',
                child: Column(
                  children: [
                    DetailLine(
                      icon: Icons.payments_outlined,
                      label: 'Session total',
                      value: formatMoney(booking.dbl('total_amount')),
                    ),
                    DetailLine(
                      icon: Icons.percent,
                      label: 'Hourly rate',
                      value: formatMoney(booking.dbl('hourly_rate')),
                    ),
                    DetailLine(
                      icon: Icons.check_circle_outline,
                      label: 'Payment status',
                      value: humanise(booking.str('payment_status')),
                    ),
                    if (payments.isNotEmpty) ...[
                      const Divider(height: 24),
                      ...payments.map(
                        (payment) => DetailLine(
                          icon: Icons.receipt_long_outlined,
                          label: '${humanise(payment.str('status'))} • ${payment.str('method', '—')}',
                          value: formatMoney(payment.dbl('amount')),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              if (booking.str('notes').isNotEmpty)
                SectionCard(
                  title: 'Parent notes',
                  child: Text(booking.str('notes')),
                ),
              if (attendance != null)
                SectionCard(
                  title: 'Attendance',
                  child: Row(
                    children: [
                      StatusChip(status: attendance.str('status')),
                      const SizedBox(width: 10),
                      if (attendance.str('note').isNotEmpty)
                        Expanded(child: Text(attendance.str('note'))),
                    ],
                  ),
                ),
              if (booking.str('cancel_reason').isNotEmpty)
                SectionCard(
                  title: 'Cancellation',
                  child: Text(booking.str('cancel_reason')),
                ),
              SectionCard(
                title: 'Actions',
                child: Column(
                  children: [
                    if (can.boolean('accept'))
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _busy ? null : _accept,
                          icon: const Icon(Icons.check),
                          label: const Text('Accept class'),
                        ),
                      ),
                    if (can.boolean('complete')) ...[
                      const SizedBox(height: 10),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.tonalIcon(
                          onPressed: _busy ? null : _complete,
                          icon: const Icon(Icons.task_alt),
                          label: const Text('Mark as completed'),
                        ),
                      ),
                    ],
                    if (can.boolean('attendance')) ...[
                      const SizedBox(height: 10),
                      SizedBox(
                        width: double.infinity,
                        child: OutlinedButton.icon(
                          onPressed: _busy ? null : _markAttendance,
                          icon: const Icon(Icons.how_to_reg_outlined),
                          label: const Text('Mark attendance'),
                        ),
                      ),
                    ],
                    if (can.boolean('reschedule')) ...[
                      const SizedBox(height: 10),
                      SizedBox(
                        width: double.infinity,
                        child: OutlinedButton.icon(
                          onPressed: _busy ? null : _reschedule,
                          icon: const Icon(Icons.edit_calendar_outlined),
                          label: const Text('Reschedule'),
                        ),
                      ),
                    ],
                    if (can.boolean('reject')) ...[
                      const SizedBox(height: 10),
                      SizedBox(
                        width: double.infinity,
                        child: TextButton.icon(
                          onPressed: _busy ? null : _reject,
                          icon: const Icon(Icons.close),
                          label: const Text('Decline request'),
                          style: TextButton.styleFrom(foregroundColor: Colors.red.shade700),
                        ),
                      ),
                    ],
                    if (can.boolean('cancel')) ...[
                      const SizedBox(height: 6),
                      SizedBox(
                        width: double.infinity,
                        child: TextButton.icon(
                          onPressed: _busy ? null : _cancel,
                          icon: const Icon(Icons.cancel_outlined),
                          label: const Text('Cancel class'),
                          style: TextButton.styleFrom(foregroundColor: Colors.red.shade700),
                        ),
                      ),
                    ],
                    if (!can.boolean('accept') &&
                        !can.boolean('complete') &&
                        !can.boolean('reject') &&
                        !can.boolean('cancel') &&
                        !can.boolean('reschedule'))
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          'No actions available for this booking.',
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
