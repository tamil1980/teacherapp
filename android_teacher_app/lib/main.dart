import 'package:flutter/material.dart';

import 'core/session.dart';
import 'core/theme.dart';
import 'screens/login_screen.dart';
import 'screens/teacher/teacher_shell.dart';
import 'screens/common/more_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await session.restore();
  runApp(const EduBookApp());
}

class EduBookApp extends StatelessWidget {
  const EduBookApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: session,
      builder: (context, _) {
        return MaterialApp(
          title: 'EduBook',
          debugShowCheckedModeBanner: false,
          theme: buildAppTheme(),
          home: _home(),
        );
      },
    );
  }

  Widget _home() {
    if (!session.isSignedIn) {
      return const LoginScreen();
    }

    switch (session.role) {
      case 'teacher':
        return const TeacherShell();
      case 'parent':
        return const Scaffold(
          body: Center(
            child: Text('Parent role coming soon.'),
          ),
        );
      case 'admin':
        return const Scaffold(
          body: Center(
            child: Text('Admin role coming soon.'),
          ),
        );
      default:
        return const UnsupportedRoleScreen();
    }
  }
}

class UnsupportedRoleScreen extends StatelessWidget {
  const UnsupportedRoleScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.person_off_outlined, size: 58, color: Colors.grey),
              const SizedBox(height: 16),
              Text(
                'This account has the role "${session.role}", which this app does not support yet.',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              FilledButton.icon(
                onPressed: () => session.signOut(),
                icon: const Icon(Icons.logout),
                label: const Text('Sign out'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}