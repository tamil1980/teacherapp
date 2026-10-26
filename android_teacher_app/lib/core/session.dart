import 'dart:convert';

import 'package:flutter/foundation.dart';

import 'api.dart';

/// Holds the signed-in user and the API token.
/// No persistent storage - token is lost on app restart (user re-logs in).
class Session extends ChangeNotifier {
  Session();

  /// Current authentication token.
  String? _token;

  /// Logged-in user payload.
  Map<String, dynamic>? _user;

  /// Base URL for the API backend.
  String _baseUrl = defaultApiBase();

  /// Current authentication token.
  String? get token => _token;
  set token(String? t) {
    _token = t;
    notifyListeners();
  }

  /// Logged-in user payload.
  Map<String, dynamic>? get user => _user;
  set user(Map<String, dynamic>? u) {
    _user = u;
    notifyListeners();
  }

  /// Base URL for the API backend.
  String get baseUrl => _baseUrl;
  set baseUrl(String v) {
    _baseUrl = v;
    notifyListeners();
  }

  /// Whether the app is initially loading (always false after restore).
  bool get isInitialising => false;

  /// Logged-in user role, e.g. 'teacher', 'parent', 'admin'.
  String get role => _user?.str('role') ?? '';

  /// User's display name.
  String get name => _user?.str('name') ?? '';

  /// User's email address.
  String get email => _user?.str('email') ?? '';

  /// True if the user is signed in.
  bool get isSignedIn => _user != null && _token != null;

  /// Refresh the user profile from the API.
  Future<void> refreshProfile() async {
    try {
      final response = await Api.instance.get('/me');
      final payload = response.obj('data')?.obj('user');
      if (payload != null) {
        user = payload;
        notifyListeners();
      }
    } catch (_) {
      // Ignore - will re-login when needed.
    }
  }

  /// Sign in with email/mobile and password.
  Future<void> signIn({
    required String baseUrl,
    required String login,
    required String password,
  }) async {
    var normalized = baseUrl.trim();
    if (normalized.endsWith('/')) normalized = normalized.substring(0, normalized.length - 1);
    if (!normalized.endsWith('/api')) normalized = '$normalized/api';

    _baseUrl = normalized;
    notifyListeners();

    final response = await Api.instance.post('/login', {
      'login': login,
      'password': password,
      'device_name': 'flutter-mobile',
    });

    final data = response.obj('data');
    final token = data?.str('token');
    final payload = data?.obj('user');

    if (token == null || payload == null) {
      throw ApiException('The server did not return a session token.');
    }

    _token = token;
    _user = payload;

    notifyListeners();
  }

  /// Sign out, notifying the server if possible.
  Future<void> signOut({bool notifyServer = true}) async {
    if (notifyServer && _token != null) {
      try {
        await Api.instance.post('/logout');
      } catch (_) {
        // Ignore network failures.
      }
    }

    _token = null;
    _user = null;

    notifyListeners();
  }

  /// Called once at app start. No-op after the initial frame.
  Future<void> restore() async {
    // No persistent storage - start fresh.
    _token = null;
    _user = null;
    _baseUrl = defaultApiBase();
    notifyListeners();
  }
}

/// Global session instance.
final Session session = Session();