import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class MoreScreen extends StatelessWidget {
  MoreScreen({super.key, this.extraTiles = const []});

  final List<Widget> extraTiles;

  @override
  Widget build(BuildContext context) {
    final user = session.user ?? const <String, dynamic>{};
    final teacherProfile = user.obj('teacher_profile');
    final roleLabel = switch (session.role) {
      'teacher' => 'Teacher',
      'parent' => 'Parent',
      'admin' => 'Administrator',
      _ => humanise(session.role),
    };

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      children: [
        Card(
          elevation: 0,
          color: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Row(
              children: [
                CircleAvatarLabel(name: session.name, radius: 28),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        session.name.isEmpty ? 'Signed in' : session.name,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 2),
                      Text(session.email, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                      const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          roleLabel,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        if (teacherProfile != null) ...[
          const SizedBox(height: 12),
          SectionCard(
            title: 'Teacher status',
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(child: Text('Verification')),
                    StatusChip(status: teacherProfile.str('verification_status', 'pending')),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    const Expanded(child: Text('Rating')),
                    Text(
                      '${teacherProfile.dbl('rating_average').toStringAsFixed(1)} ★ '
                      '(${teacherProfile.integer('rating_count')})',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
        if (extraTiles.isNotEmpty) ...[
          SectionCard(
            title: 'Manage',
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Column(children: extraTiles),
          ),
        ],
        const SizedBox(height: 4),
        Card(
          elevation: 0,
          color: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Column(
            children: [
              ListTile(
                leading: const Icon(Icons.notifications_outlined),
                title: const Text('Notifications'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => showSnack(context, 'Feature coming soon.'),
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.support_agent_outlined),
                title: const Text('Help & support'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => showSnack(context, 'Contact support via the app.'),
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.dns_outlined),
                title: const Text('Server address'),
                subtitle: Text(session.baseUrl, style: const TextStyle(fontSize: 12)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        OutlinedButton.icon(
          onPressed: () async {
            final confirmed = await confirmDialog(
              context,
              title: 'Sign out',
              message: 'Do you want to sign out of EduBook?',
              confirmLabel: 'Sign out',
              destructive: true,
            );
            if (confirmed) await session.signOut();
          },
          icon: const Icon(Icons.logout),
          label: const Text('Sign out'),
          style: OutlinedButton.styleFrom(foregroundColor: Colors.red.shade700),
        ),
        const SizedBox(height: 12),
        Center(
          child: Text('EduBook • v1.0.0', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
        ),
      ],
    );
  }
}

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final _key = GlobalKey<AsyncBuilderState>();

  Future<void> _markAll() async {
    try {
      await Api.instance.post('/notifications/read-all');
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(onPressed: _markAll, child: const Text('Mark all read')),
        ],
      ),
      body: AsyncBuilder(
        key: _key,
        loader: () => Api.instance.get('/notifications'),
        builder: (context, data) {
          final items = data.objects('items');

          if (items.isEmpty) {
            return const EmptyState(
              message: 'You have no notifications yet.',
              icon: Icons.notifications_none_rounded,
            );
          }

          return RefreshIndicator(
            onRefresh: () => _key.currentState!.reload(),
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final unread = item.str('read_at').isEmpty;

                return Card(
                  elevation: 0,
                  color: unread ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.05) : Colors.white,
                  margin: const EdgeInsets.only(bottom: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: unread
                          ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.15)
                          : Colors.grey.shade200,
                      child: Icon(
                        Icons.notifications_outlined,
                        color: unread ? Theme.of(context).colorScheme.primary : Colors.grey,
                        size: 20,
                      ),
                    ),
                    title: Text(
                      item.str('title'),
                      style: TextStyle(fontWeight: unread ? FontWeight.w700 : FontWeight.w500),
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (item.str('message').isNotEmpty) ...[
                          const SizedBox(height: 3),
                          Text(item.str('message')),
                        ],
                        const SizedBox(height: 4),
                        Text(
                          formatDate(item.str('created_at')),
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                        ),
                      ],
                    ),
                    onTap: unread
                        ? () async {
                            await Api.instance.post('/notifications/${item.integer('id')}/read');
                            await _key.currentState?.reload();
                          }
                        : null,
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
