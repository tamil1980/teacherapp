import 'package:flutter/material.dart';

import '../../core/session.dart';
import '../../core/ui.dart';
import '../common/more_screen.dart';
import 'teacher_availability.dart';
import 'teacher_bookings.dart';
import 'teacher_dashboard.dart';
import 'teacher_earnings.dart';
import 'teacher_fees.dart';
import 'teacher_locations.dart';
import 'teacher_profile.dart';

class TeacherShell extends StatefulWidget {
  const TeacherShell({super.key});

  @override
  State<TeacherShell> createState() => _TeacherShellState();
}

class _TeacherShellState extends State<TeacherShell> {
  int _index = 0;

  static const _titles = ['Dashboard', 'Bookings', 'Availability', 'Fees', 'More'];

  @override
  Widget build(BuildContext context) {
    final pages = [
      TeacherDashboardView(onOpenBookings: () => setState(() => _index = 1)),
      const TeacherBookingsView(),
      const TeacherAvailabilityView(),
      const TeacherFeesView(),
      MoreScreen(
        extraTiles: [
          ListTile(
            leading: const Icon(Icons.payments_outlined),
            title: const Text('Earnings & payouts'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const TeacherEarningsScreen()),
            ),
          ),
          ListTile(
            leading: const Icon(Icons.place_outlined),
            title: const Text('Teaching locations'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const TeacherLocationsScreen()),
            ),
          ),
          ListTile(
            leading: const Icon(Icons.badge_outlined),
            title: const Text('Teacher profile'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const TeacherProfileScreen()),
            ),
          ),
        ],
      ),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[_index]),
        actions: [
          if (_index == 0)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: CircleAvatarLabel(name: session.name, radius: 17),
            ),
        ],
      ),
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.event_note_outlined), selectedIcon: Icon(Icons.event_note), label: 'Bookings'),
          NavigationDestination(icon: Icon(Icons.schedule_outlined), selectedIcon: Icon(Icons.schedule), label: 'Slots'),
          NavigationDestination(icon: Icon(Icons.currency_rupee_outlined), selectedIcon: Icon(Icons.currency_rupee), label: 'Fees'),
          NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
        ],
      ),
    );
  }
}
