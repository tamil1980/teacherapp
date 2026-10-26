import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

/// Compact booking card used by the teacher and parent lists.
class BookingCard extends StatelessWidget {
  const BookingCard({
    super.key,
    required this.booking,
    this.perspective = 'teacher',
    this.onTap,
  });

  final Map<String, dynamic> booking;
  final String perspective;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final teacher = booking.obj('teacher');
    final student = booking.obj('student');
    final subject = booking.obj('subject');
    final status = booking.str('status');

    final title = perspective == 'teacher'
        ? (student?.str('name') ?? 'Student')
        : (teacher?.str('name') ?? 'Teacher');

    final subtitleParts = <String>[
      if (subject != null) subject.str('name'),
      if (booking.str('mode').isNotEmpty) humanise(booking.str('mode')),
    ];

    return Card(
      elevation: 0,
      color: Colors.white,
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatarLabel(name: title, radius: 19),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                        ),
                        if (subtitleParts.isNotEmpty)
                          Text(
                            subtitleParts.join(' • '),
                            style: TextStyle(fontSize: 12.5, color: Colors.grey.shade600),
                          ),
                      ],
                    ),
                  ),
                  StatusChip(status: status, compact: true),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.schedule, size: 15, color: Colors.grey.shade500),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      formatDate(booking.str('start_at')),
                      style: TextStyle(fontSize: 12.5, color: Colors.grey.shade700),
                    ),
                  ),
                  Text(
                    formatMoney(booking.dbl('total_amount')),
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    booking.str('mode') == 'online' ? Icons.videocam_outlined : Icons.place_outlined,
                    size: 15,
                    color: Colors.grey.shade500,
                  ),
                  const SizedBox(width: 5),
                  Text(
                    humanise(booking.str('mode')),
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      booking.str('reference_no'),
                      style: TextStyle(fontSize: 10.5, color: Colors.grey.shade600),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}


