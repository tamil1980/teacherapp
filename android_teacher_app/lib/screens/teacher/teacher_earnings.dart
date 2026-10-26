import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class TeacherEarningsScreen extends StatefulWidget {
  const TeacherEarningsScreen({super.key});

  @override
  State<TeacherEarningsScreen> createState() => _TeacherEarningsScreenState();
}

class _TeacherEarningsScreenState extends State<TeacherEarningsScreen> {
  final _key = GlobalKey<AsyncBuilderState>();

  Future<void> _requestPayout(double available) async {
    if (available <= 0) {
      showSnack(context, 'There is no available balance to withdraw yet.', error: true);
      return;
    }

    final controller = TextEditingController(text: available.toStringAsFixed(2));

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Request payout'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Available balance: ${formatMoney(available)}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Amount (₹)'),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Request')),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final response = await Api.instance.post('/teacher/earnings/payout-request', {
        'amount': double.tryParse(controller.text),
      });
      if (!mounted) return;
      showSnack(context, response.str('message', 'Payout requested.'));
      await _key.currentState?.reload();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Earnings & payouts')),
      body: AsyncBuilder(
        key: _key,
        loader: () => Api.instance.get('/teacher/earnings'),
        builder: (context, data) {
          final summary = data.obj('summary') ?? const <String, dynamic>{};
          final earnings = data.objects('earnings');
          final payouts = data.objects('payouts');
          final available = data.dbl('available_balance');

          return RefreshIndicator(
            onRefresh: () => _key.currentState!.reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
              children: [
                Card(
                  elevation: 0,
                  color: const Color(0xFF1E1B4B),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Available for payout',
                          style: TextStyle(color: Colors.white.withValues(alpha: 0.75)),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          formatMoney(available),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 30,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 14),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: const Color(0xFF1E1B4B)),
                            onPressed: () => _requestPayout(available),
                            icon: const Icon(Icons.account_balance_outlined),
                            label: const Text('Request payout'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                gridOfStats([
                  StatCard(label: 'Gross earnings', value: formatMoney(summary.dbl('gross')), icon: Icons.summarize_outlined),
                  StatCard(
                    label: 'Platform commission',
                    value: formatMoney(summary.dbl('commission')),
                    icon: Icons.percent,
                    color: const Color(0xFFB45309),
                  ),
                  StatCard(
                    label: 'Net earned',
                    value: formatMoney(summary.dbl('net_earned')),
                    icon: Icons.savings_outlined,
                    color: const Color(0xFF15803D),
                  ),
                  StatCard(
                    label: 'Adjustments',
                    value: formatMoney(summary.dbl('adjustments')),
                    icon: Icons.tune,
                    color: const Color(0xFF7C3AED),
                  ),
                ]),
                const SizedBox(height: 18),
                const Text('Earnings history', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                const SizedBox(height: 8),
                if (earnings.isEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 24),
                    child: EmptyState(
                      icon: Icons.savings_outlined,
                      message: 'No earnings recorded yet. Earnings appear after a paid class is completed.',
                    ),
                  )
                else
                  ...earnings.map(
                    (earning) => Card(
                      elevation: 0,
                      color: Colors.white,
                      margin: const EdgeInsets.only(bottom: 10),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      child: Padding(
                        padding: const EdgeInsets.all(14),
                        child: Row(
                          children: [
                            CircleAvatarLabel(name: earning.str('status'), radius: 19),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Booking ${earning.obj('booking')?.str('reference_no') ?? '—'}',
                                    style: const TextStyle(fontWeight: FontWeight.w600),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '${earning.obj('booking')?.obj('subject')?.str('name') ?? 'Class'} • '
                                    '${formatDate(earning.str('created_at'), withTime: false)}',
                                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                  ),
                                ],
                              ),
                            ),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  formatMoney(earning.dbl('net_amount')),
                                  style: const TextStyle(fontWeight: FontWeight.w700),
                                ),
                                const SizedBox(height: 4),
                                StatusChip(status: earning.str('status'), compact: true),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                const SizedBox(height: 18),
                const Text('Payouts', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                const SizedBox(height: 8),
                if (payouts.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14)),
                    child: Text('No payouts requested yet.', style: TextStyle(color: Colors.grey.shade600)),
                  )
                else
                  ...payouts.map(
                    (payout) => ListRow(
                      title: '${formatMoney(payout.dbl('amount'))} • ${payout.str('reference_no')}',
                      subtitle:
                          '${humanise(payout.str('method'))} • requested ${formatDate(payout.str('created_at'), withTime: false)}',
                      leading: CircleAvatarLabel(name: payout.str('status'), radius: 18),
                      trailing: StatusChip(status: payout.str('status'), compact: true),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
