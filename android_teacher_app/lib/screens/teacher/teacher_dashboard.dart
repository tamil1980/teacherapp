import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';
import 'teacher_booking_detail.dart';
import 'teacher_earnings.dart';
import 'teacher_widgets.dart';

class TeacherDashboardView extends StatefulWidget {
  const TeacherDashboardView({super.key, required this.onOpenBookings});

  final VoidCallback onOpenBookings;

  @override
  State<TeacherDashboardView> createState() => _TeacherDashboardViewState();
}

class _TeacherDashboardViewState extends State<TeacherDashboardView> {
  final _key = GlobalKey<AsyncBuilderState>();

  Future<void> _openBooking(Map<String, dynamic> booking) async {
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
      loader: () => Api.instance.get('/teacher/dashboard'),
      builder: (context, data) {
        final teacher = data.obj('teacher') ?? const <String, dynamic>{};
        final stats = data.obj('stats') ?? const <String, dynamic>{};
        final earnings = data.obj('earnings') ?? const <String, dynamic>{};
        final today = data.objects('today_bookings');
        final upcoming = data.objects('upcoming_bookings');
        final notifications = data.objects('notifications');
        final verification = teacher.str('verification_status', 'pending');
        final firstName = session.name.split(' ').first;

        return RefreshIndicator(
          onRefresh: () => _key.currentState!.reload(),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
            children: [
              if (verification != 'approved')
                Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade50,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.amber.shade200),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.verified_user_outlined, color: Colors.amber.shade800),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          verification == 'rejected'
                              ? 'Your teacher profile was rejected. Review the profile page and contact support.'
                              : 'Your teacher profile is awaiting verification. Parents can find you once approved.',
                          style: TextStyle(color: Colors.amber.shade900, fontSize: 13),
                        ),
                      ),
                      StatusChip(status: verification, compact: true),
                    ],
                  ),
                ),
              Card(
                elevation: 0,
                color: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Namaste, $firstName 👋',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        teacher.str('headline', 'Let us make today a great teaching day.'),
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          _MiniStat(
                            icon: Icons.star_rounded,
                            value: teacher.dbl('rating_average').toStringAsFixed(1),
                            label: '${teacher.integer('rating_count')} reviews',
                            color: Colors.amber.shade700,
                          ),
                          _MiniStat(
                            icon: Icons.event_available_outlined,
                            value: '${stats.integer('upcoming_count')}',
                            label: 'upcoming',
                            color: Theme.of(context).colorScheme.primary,
                          ),
                          _MiniStat(
                            icon: Icons.wallet_outlined,
                            value: formatMoneyShort(stats.dbl('month_earnings')),
                            label: 'this month',
                            color: const Color(0xFF15803D),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),
              gridOfStats(
                [
                  StatCard(
                    label: 'Class requests',
                    value: '${stats.integer('pending_requests')}',
                    icon: Icons.mark_email_unread_outlined,
                    color: const Color(0xFFB45309),
                    onTap: widget.onOpenBookings,
                  ),
                  StatCard(
                    label: 'Available slots',
                    value: '${stats.integer('available_slots')}',
                    icon: Icons.schedule_outlined,
                    color: Theme.of(context).colorScheme.primary,
                    onTap: widget.onOpenBookings,
                  ),
                  StatCard(
                    label: 'Completed sessions',
                    value: '${stats.integer('completed_sessions')}',
                    icon: Icons.task_alt_outlined,
                    color: const Color(0xFF15803D),
                  ),
                  StatCard(
                    label: 'Unread alerts',
                    value: '${stats.integer('unread_notifications')}',
                    icon: Icons.notifications_none_rounded,
                    color: const Color(0xFF7C3AED),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Card(
                elevation: 0,
                color: const Color(0xFF1E1B4B),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                child: Padding(
                  padding: const EdgeInsets.all(18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Expanded(
                            child: Text(
                              'Earnings',
                              style: TextStyle(color: Colors.white70, fontWeight: FontWeight.w600),
                            ),
                          ),
                          TextButton(
                            onPressed: () => Navigator.push(
                              context,
                              MaterialPageRoute(builder: (_) => const TeacherEarningsScreen()),
                            ),
                            child: const Text('Details', style: TextStyle(color: Colors.white)),
                          ),
                        ],
                      ),
                      Text(
                        formatMoney(earnings.dbl('net_earned')),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Lifetime net earnings',
                        style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12),
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Expanded(
                            child: _WhiteStat(label: 'Gross', value: formatMoney(earnings.dbl('gross'))),
                          ),
                          Expanded(
                            child: _WhiteStat(
                              label: 'Commission',
                              value: formatMoney(earnings.dbl('commission')),
                            ),
                          ),
                          Expanded(
                            child: _WhiteStat(
                              label: 'Available',
                              value: formatMoney(earnings.dbl('available')),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  const Expanded(
                    child: Text("Today's classes", style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                  ),
                  TextButton(onPressed: widget.onOpenBookings, child: const Text('All bookings')),
                ],
              ),
              if (today.isEmpty)
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.free_breakfast_outlined, color: Colors.grey.shade400),
                      const SizedBox(width: 12),
                      Expanded(child: Text('No classes scheduled today.', style: TextStyle(color: Colors.grey.shade600))),
                    ],
                  ),
                )
              else
                ...today.map(
                  (booking) => BookingCard(
                    booking: booking,
                    onTap: () => _openBooking(booking),
                  ),
                ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Expanded(
                    child: Text('Next up', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                  ),
                  TextButton(onPressed: widget.onOpenBookings, child: const Text('See all')),
                ],
              ),
              if (upcoming.isEmpty)
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14)),
                  child: Text('Nothing upcoming yet.', style: TextStyle(color: Colors.grey.shade600)),
                )
              else
                ...upcoming.take(3).map(
                      (booking) => BookingCard(
                        booking: booking,
                        onTap: () => _openBooking(booking),
                      ),
                    ),
              if (notifications.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Expanded(
                      child: Text('Latest updates', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                    ),
                    TextButton(onPressed: widget.onOpenBookings, child: const Text('Bookings')),
                  ],
                ),
                ...notifications.take(3).map(
                      (item) => Card(
                        elevation: 0,
                        color: Colors.white,
                        margin: const EdgeInsets.only(bottom: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: ListTile(
                          dense: true,
                          leading: Icon(
                            Icons.notifications_outlined,
                            color: Theme.of(context).colorScheme.primary,
                          ),
                          title: Text(item.str('title'), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                          subtitle: Text(
                            item.str('message'),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ),
                    ),
              ],
            ],
          ),
        );
      },
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({
    required this.icon,
    required this.value,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String value;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 19, color: color),
          const SizedBox(height: 6),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          Text(label, style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600)),
        ],
      ),
    );
  }
}

class _WhiteStat extends StatelessWidget {
  const _WhiteStat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(color: Colors.white.withValues(alpha: 0.65), fontSize: 11)),
        const SizedBox(height: 3),
        Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 14)),
      ],
    );
  }
}
