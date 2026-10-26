import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/ui.dart';
import 'teacher_booking_detail.dart';
import 'teacher_widgets.dart';

class TeacherBookingsView extends StatelessWidget {
  const TeacherBookingsView({super.key});

  static const _tabs = [
    ('upcoming', 'Upcoming'),
    ('pending', 'Requests'),
    ('completed', 'Completed'),
    ('all', 'All'),
  ];

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: _tabs.length,
      child: Column(
        children: [
          TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: _tabs.map((tab) => Tab(text: tab.$2)).toList(),
          ),
          Expanded(
            child: TabBarView(
              children: _tabs.map((tab) => _BookingList(tab: tab.$1)).toList(),
            ),
          ),
        ],
      ),
    );
  }
}

class _BookingList extends StatefulWidget {
  const _BookingList({required this.tab});

  final String tab;

  @override
  State<_BookingList> createState() => _BookingListState();
}

class _BookingListState extends State<_BookingList> {
  final _key = GlobalKey<AsyncBuilderState>();

  Future<void> _open(Map<String, dynamic> booking) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TeacherBookingDetailScreen(bookingId: booking.integer('id')),
      ),
    );
    await _key.currentState?.reload();
  }

  @override
  Widget build(BuildContext context) {
    return AsyncBuilder(
      key: _key,
      loader: () => Api.instance.get('/teacher/bookings', query: {'tab': widget.tab}),
      builder: (context, data) {
        final items = data.objects('items');
        final counts = data.obj('counts') ?? const <String, dynamic>{};

        return RefreshIndicator(
          onRefresh: () => _key.currentState!.reload(),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
            children: [
              Row(
                children: [
                  _CountPill(label: 'Upcoming', value: counts.integer('upcoming')),
                  const SizedBox(width: 8),
                  _CountPill(
                    label: 'Requests',
                    value: counts.integer('pending'),
                    highlight: counts.integer('pending') > 0,
                  ),
                  const SizedBox(width: 8),
                  _CountPill(label: 'Completed', value: counts.integer('completed')),
                ],
              ),
              const SizedBox(height: 14),
              if (items.isEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 50),
                  child: EmptyState(
                    icon: widget.tab == 'pending' ? Icons.mark_email_read_outlined : Icons.event_busy_outlined,
                    message: widget.tab == 'pending'
                        ? 'No class requests waiting for you.'
                        : 'No bookings in this list yet.',
                  ),
                )
              else
                ...items.map(
                  (booking) => BookingCard(
                    booking: booking,
                    onTap: () => _open(booking),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}

class _CountPill extends StatelessWidget {
  const _CountPill({required this.label, required this.value, this.highlight = false});

  final String label;
  final int value;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
        decoration: BoxDecoration(
          color: highlight ? scheme.primary.withValues(alpha: 0.12) : Colors.white,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 3),
            Text(
              '$value',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 17,
                color: highlight ? scheme.primary : null,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
