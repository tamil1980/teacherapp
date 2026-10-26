import 'package:flutter/material.dart';

import '../../core/api.dart';
import '../../core/theme.dart';
import '../../core/ui.dart';

class TeacherProfileScreen extends StatefulWidget {
  const TeacherProfileScreen({super.key});

  @override
  State<TeacherProfileScreen> createState() => _TeacherProfileScreenState();
}

class _TeacherProfileScreenState extends State<TeacherProfileScreen> {
  final _key = GlobalKey<AsyncBuilderState>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Teacher profile')),
      body: AsyncBuilder(
        key: _key,
        loader: () => Api.instance.get('/teacher/profile'),
        builder: (context, data) {
          final profile = data.obj('profile') ?? const <String, dynamic>{};

          return _ProfileForm(
            profile: profile,
            onSaved: () async => await _key.currentState?.reload(),
          );
        },
      ),
    );
  }
}

class _ProfileForm extends StatefulWidget {
  const _ProfileForm({required this.profile, required this.onSaved});

  final Map<String, dynamic> profile;
  final Future<void> Function() onSaved;

  @override
  State<_ProfileForm> createState() => _ProfileFormState();
}

class _ProfileFormState extends State<_ProfileForm> {
  late final TextEditingController _headline;
  late final TextEditingController _bio;
  late final TextEditingController _teaching;
  late final TextEditingController _methodology;
  late final TextEditingController _experience;
  late final TextEditingController _city;
  late final TextEditingController _state;
  late final TextEditingController _pincode;
  late final TextEditingController _sessionMinutes;
  late final TextEditingController _cancellation;

  late bool _acceptsOnline;
  late bool _acceptsOffline;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final profile = widget.profile;

    _headline = TextEditingController(text: profile.str('headline'));
    _bio = TextEditingController(text: profile.str('bio'));
    _teaching = TextEditingController(text: profile.str('teaching_description'));
    _methodology = TextEditingController(text: profile.str('methodology'));
    _experience = TextEditingController(text: profile.integer('experience_years').toString());
    _city = TextEditingController(text: profile.str('city'));
    _state = TextEditingController(text: profile.str('state'));
    _pincode = TextEditingController(text: profile.str('pincode'));
    _sessionMinutes = TextEditingController(text: profile.integer('default_session_minutes', 60).toString());
    _cancellation = TextEditingController(text: profile.str('cancellation_policy'));
    _acceptsOnline = profile.boolean('accepts_online', true);
    _acceptsOffline = profile.boolean('accepts_offline', true);
  }

  @override
  void dispose() {
    _headline.dispose();
    _bio.dispose();
    _teaching.dispose();
    _methodology.dispose();
    _experience.dispose();
    _city.dispose();
    _state.dispose();
    _pincode.dispose();
    _sessionMinutes.dispose();
    _cancellation.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _saving = true);

    try {
      final response = await Api.instance.put('/teacher/profile', {
        'headline': _headline.text.trim(),
        'bio': _bio.text.trim(),
        'teaching_description': _teaching.text.trim(),
        'methodology': _methodology.text.trim(),
        'experience_years': int.tryParse(_experience.text) ?? 0,
        'city': _city.text.trim(),
        'state': _state.text.trim(),
        'pincode': _pincode.text.trim(),
        'default_session_minutes': int.tryParse(_sessionMinutes.text) ?? 60,
        'accepts_online': _acceptsOnline,
        'accepts_offline': _acceptsOffline,
        'cancellation_policy': _cancellation.text.trim(),
      });

      if (!mounted) return;
      showSnack(context, response.str('message', 'Profile updated.'));
      await widget.onSaved();
    } on ApiException catch (error) {
      if (mounted) showSnack(context, error.message, error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final profile = widget.profile;
    final user = profile.obj('user') ?? const <String, dynamic>{};
    final subjects = profile.objects('teacher_subjects');
    final boards = profile.objects('teacher_boards');
    final classes = profile.objects('teacher_class_standards');
    final qualifications = profile.objects('qualifications');

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
      children: [
        SectionCard(
          title: 'Account',
          child: Column(
            children: [
              KeyValueRow(label: 'Name', value: user.str('name', '—')),
              KeyValueRow(label: 'Email', value: user.str('email', '—')),
              KeyValueRow(label: 'Mobile', value: user.str('mobile', '—')),
              KeyValueRow(
                label: 'Verification',
                value: humanise(profile.str('verification_status', 'pending')),
                valueColor: statusColor(profile.str('verification_status', 'pending')),
              ),
              if (profile.str('rejection_reason').isNotEmpty)
                KeyValueRow(
                  label: 'Rejection reason',
                  value: profile.str('rejection_reason'),
                  valueColor: Colors.red.shade700,
                ),
            ],
          ),
        ),
        SectionCard(
          title: 'Public profile',
          child: Column(
            children: [
              TextField(
                controller: _headline,
                decoration: const InputDecoration(
                  labelText: 'Headline',
                  hintText: 'CBSE Physics & Maths tutor for classes 9–12',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _bio,
                minLines: 3,
                maxLines: 6,
                decoration: const InputDecoration(labelText: 'Short bio'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _teaching,
                minLines: 2,
                maxLines: 5,
                decoration: const InputDecoration(labelText: 'Teaching description'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _methodology,
                minLines: 2,
                maxLines: 5,
                decoration: const InputDecoration(labelText: 'Methodology'),
              ),
            ],
          ),
        ),
        SectionCard(
          title: 'Teaching preferences',
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _experience,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Experience (years)'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _sessionMinutes,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Default minutes'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _city,
                      decoration: const InputDecoration(labelText: 'City'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _state,
                      decoration: const InputDecoration(labelText: 'State'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _pincode,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Pincode'),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                value: _acceptsOnline,
                title: const Text('Accept online classes'),
                onChanged: (value) => setState(() => _acceptsOnline = value),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                value: _acceptsOffline,
                title: const Text('Accept in-person classes'),
                onChanged: (value) => setState(() => _acceptsOffline = value),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _cancellation,
                minLines: 2,
                maxLines: 4,
                decoration: const InputDecoration(labelText: 'Cancellation policy'),
              ),
            ],
          ),
        ),
        if (subjects.isNotEmpty || boards.isNotEmpty || classes.isNotEmpty)
          SectionCard(
            title: 'Subjects & classes',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (subjects.isNotEmpty)
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: subjects
                        .map((row) => Chip(label: Text(row.obj('subject')?.str('name') ?? 'Subject')))
                        .toList(),
                  ),
                if (boards.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Text('Boards', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.grey.shade700)),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: boards
                        .map((row) => Chip(label: Text(row.obj('board')?.str('name') ?? 'Board')))
                        .toList(),
                  ),
                ],
                if (classes.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Text('Classes', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.grey.shade700)),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: classes
                        .map((row) => Chip(label: Text(row.obj('class_standard')?.str('name') ?? 'Class')))
                        .toList(),
                  ),
                ],
              ],
            ),
          ),
        if (qualifications.isNotEmpty)
          SectionCard(
            title: 'Qualifications',
            child: Column(
              children: qualifications
                  .map(
                    (qualification) => KeyValueRow(
                      label: qualification.str('degree', qualification.str('title', 'Qualification')),
                      value: qualification.str('institution'),
                    ),
                  )
                  .toList(),
            ),
          ),
        FilledButton(
          onPressed: _saving ? null : _save,
          child: _saving
              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white))
              : const Text('Save profile'),
        ),
      ],
    );
  }
}
