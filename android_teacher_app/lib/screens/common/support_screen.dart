import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen> {
  final _key = GlobalKey<AsyncBuilderState>();

  Future<void> _createTicket() async {
    final category = ValueNotifier<String>('booking');
    final subject = TextEditingController();
    final message = TextEditingController();

    final submitted = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('New support request'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ValueListenableBuilder<String>(
                valueListenable: category,
                builder: (context, value, _) => DropdownButtonFormField<String>(
                  initialValue: value,
                  decoration: const InputDecoration(labelText: 'Category'),
                  items: const [
                    DropdownMenuItem(value: 'booking', child: Text('Booking')),
                    DropdownMenuItem(value: 'payment', child: Text('Payment / refund')),
                    DropdownMenuItem(value: 'account', child: Text('Account')),
                    DropdownMenuItem(value: 'technical', child: Text('Technical issue')),
                    DropdownMenuItem(value: 'other', child: Text('Other')),
                  ],
                  onChanged: (val) => category.value = val ?? 'other',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: subject,
                decoration: const InputDecoration(labelText: 'Subject'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: message,
                minLines: 4,
                maxLines: 7,
                decoration: const InputDecoration(labelText: 'Describe the issue'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Submit')),
        ],
      ),
    );

    if (submitted != true) return;

    if (subject.text.trim().isEmpty || message.text.trim().isEmpty) {
      if (mounted) showSnack(context, 'Please add a subject and a description.', error: true);
      return;
    }

    try {
      await Api.instance.post('/support', {
        'category': category.value,
        'subject': subject.text.trim(),
        'message': message.text.trim(),
      });
      if (!mounted) return;
      showSnack(context, 'Support request submitted.');
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Help & support')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createTicket,
        icon: const Icon(Icons.add_comment_outlined),
        label: const Text('New request'),
      ),
      body: AsyncBuilder(
        key: _key,
        loader: () => Api.instance.get('/support'),
        builder: (context, data) {
          final items = data.objects('items');

          if (items.isEmpty) {
            return const EmptyState(
              message: 'No support requests yet.\nUse the button below to contact the EduBook team.',
              icon: Icons.support_agent_outlined,
            );
          }

          return RefreshIndicator(
            onRefresh: () => _key.currentState!.reload(),
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final ticket = items[index];

                return Card(
                  elevation: 0,
                  color: Colors.white,
                  margin: const EdgeInsets.only(bottom: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  child: ExpansionTile(
                    shape: const Border(),
                    title: Text(ticket.str('subject'), style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Row(
                        children: [
                          StatusChip(status: ticket.str('status', 'open'), compact: true),
                          const SizedBox(width: 8),
                          Text(
                            formatDate(ticket.str('created_at'), withTime: false),
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    ),
                    childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    children: [
                      Align(
                        alignment: Alignment.centerLeft,
                        child: Text(ticket.str('message')),
                      ),
                      if (ticket.str('admin_response').isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.06),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('EduBook support', style: TextStyle(fontWeight: FontWeight.w700)),
                              const SizedBox(height: 4),
                              Text(ticket.str('admin_response')),
                            ],
                          ),
                        ),
                      ],
                    ],
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
