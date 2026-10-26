import 'package:flutter/material.dart';

const Color appSeed = Color(0xFF4F46E5);

ThemeData buildAppTheme() {
  final scheme = ColorScheme.fromSeed(seedColor: appSeed);

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: const Color(0xFFF5F6FA),
    appBarTheme: AppBarTheme(
      backgroundColor: scheme.surface,
      foregroundColor: scheme.onSurface,
      elevation: 0,
      centerTitle: false,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: scheme.primary, width: 1.6),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size(0, 48),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size(0, 46),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
    snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: scheme.surface,
      indicatorColor: scheme.primary.withValues(alpha: 0.14),
    ),
  );
}

const List<String> monthNames = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
];

String formatMoney(num? value, {String symbol = '₹'}) {
  final amount = (value ?? 0).toDouble();
  final negative = amount < 0;
  final fixed = amount.abs().toStringAsFixed(2);
  final parts = fixed.split('.');
  final whole = parts.first;
  final buffer = StringBuffer();

  for (var i = 0; i < whole.length; i++) {
    if (i > 0 && (whole.length - i) % 3 == 0) buffer.write(',');
    buffer.write(whole[i]);
  }

  return '${negative ? '-' : ''}$symbol$buffer.${parts.last}';
}

String formatMoneyShort(num? value, {String symbol = '₹'}) {
  final amount = (value ?? 0).toDouble();
  if (amount >= 10000000) return '$symbol${(amount / 10000000).toStringAsFixed(1)}Cr';
  if (amount >= 100000) return '$symbol${(amount / 100000).toStringAsFixed(1)}L';
  if (amount >= 1000) return '$symbol${(amount / 1000).toStringAsFixed(1)}K';
  return '$symbol${amount.toStringAsFixed(0)}';
}

DateTime? parseDate(String? raw) {
  if (raw == null || raw.isEmpty) return null;
  return DateTime.tryParse(raw)?.toLocal();
}

String formatDate(String? raw, {bool withTime = true}) {
  final date = parseDate(raw);
  if (date == null) return '—';

  final day = date.day.toString().padLeft(2, '0');
  final month = monthNames[date.month - 1];
  final year = date.year;
  var result = '$day $month $year';

  if (withTime) {
    final hour = date.hour % 12 == 0 ? 12 : date.hour % 12;
    final minute = date.minute.toString().padLeft(2, '0');
    result += ' · $hour:$minute ${date.hour >= 12 ? 'PM' : 'AM'}';
  }

  return result;
}

String formatTime(String? raw) {
  final date = parseDate(raw);
  if (date == null) return '—';
  final hour = date.hour % 12 == 0 ? 12 : date.hour % 12;
  final minute = date.minute.toString().padLeft(2, '0');
  return '$hour:$minute ${date.hour >= 12 ? 'PM' : 'AM'}';
}

String formatDay(String? raw) {
  final date = parseDate(raw);
  if (date == null) return '—';
  return '${date.day.toString().padLeft(2, '0')} ${monthNames[date.month - 1]}';
}

String formatDateTime(DateTime value) {
  final hour = value.hour % 12 == 0 ? 12 : value.hour % 12;
  final minute = value.minute.toString().padLeft(2, '0');
  return '${value.day.toString().padLeft(2, '0')} ${monthNames[value.month - 1]} ${value.year} · $hour:$minute ${value.hour >= 12 ? 'PM' : 'AM'}';
}

String humanise(String value) {
  if (value.isEmpty) return '—';
  final cleaned = value.replaceAll('_', ' ');
  return cleaned
      .split(' ')
      .where((part) => part.isNotEmpty)
      .map((part) => part[0].toUpperCase() + part.substring(1))
      .join(' ');
}

Color statusColor(String status) {
  switch (status) {
    case 'confirmed':
    case 'paid':
    case 'completed':
    case 'published':
    case 'approved':
    case 'active':
    case 'available':
    case 'resolved':
      return const Color(0xFF15803D);
    case 'pending_payment':
    case 'pending_teacher':
    case 'pending':
    case 'created':
    case 'requested':
    case 'processing':
    case 'ongoing':
    case 'under_review':
      return const Color(0xFFB45309);
    case 'cancelled':
    case 'failed':
    case 'refunded':
    case 'rejected':
    case 'suspended':
    case 'blocked':
    case 'hidden':
      return const Color(0xFFB91C1C);
    case 'disputed':
    case 'partially_refunded':
      return const Color(0xFF7C3AED);
    default:
      return const Color(0xFF475569);
  }
}
