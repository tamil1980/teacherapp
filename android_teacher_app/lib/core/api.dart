import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

/// Default backend address.
///
/// - Android emulator reaches the host machine through 10.0.2.2.
/// - Web / desktop / iOS simulator use localhost.
/// On a real phone replace this with the computer's LAN address, e.g.
/// http://192.168.1.20:8000/api
String defaultApiBase() {
  if (kIsWeb) return 'http://127.0.0.1:8000/api';
  if (Platform.isAndroid) return 'http://10.0.2.2:8000/api';
  return 'http://127.0.0.1:8000/api';
}

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  @override
  String toString() => message;
}

class Api {
  Api._();

  static final Api instance = Api._();

  String baseUrl = defaultApiBase();
  String? token;
  void Function()? onUnauthorized;

  Uri _uri(String path, [Map<String, dynamic>? query]) {
    final normalized = path.startsWith('/') ? path.substring(1) : path;
    final uri = Uri.parse('$baseUrl/$normalized');
    if (query == null || query.isEmpty) return uri;

    final params = <String, String>{};
    query.forEach((key, value) {
      if (value == null) return;
      params[key] = value.toString();
    });

    return uri.replace(queryParameters: {...uri.queryParameters, ...params});
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
    return _send(() => http.get(_uri(path, query), headers: _headers));
  }

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) async {
    return _send(() => http.post(_uri(path), headers: _headers, body: jsonEncode(body ?? {})));
  }

  Future<Map<String, dynamic>> put(String path, [Map<String, dynamic>? body]) async {
    return _send(() => http.put(_uri(path), headers: _headers, body: jsonEncode(body ?? {})));
  }

  Future<Map<String, dynamic>> delete(String path) async {
    return _send(() => http.delete(_uri(path), headers: _headers));
  }

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() request) async {
    late http.Response response;

    try {
      response = await request().timeout(const Duration(seconds: 30));
    } on SocketException {
      throw ApiException('Cannot reach the server. Is the Laravel API running?');
    } on Exception catch (error) {
      throw ApiException('Network error: $error');
    }

    Map<String, dynamic> body = {};
    if (response.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) body = decoded;
      } catch (_) {
        body = {};
      }
    }

    if (response.statusCode == 401) {
      onUnauthorized?.call();
    }

    if (response.statusCode >= 400) {
      final message = (body['message'] ?? 'Request failed (${response.statusCode})').toString();
      final errors = body['errors'] is Map<String, dynamic>
          ? body['errors'] as Map<String, dynamic>
          : null;

      String readable = message;
      if (errors != null && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) readable = first.first.toString();
      }

      throw ApiException(readable, statusCode: response.statusCode, errors: errors);
    }

    return body;
  }
}

/// Convenience helpers for the loosely typed JSON payloads returned by the API.
extension JsonX on Map<String, dynamic> {
  Map<String, dynamic>? obj(String key) {
    final value = this[key];
    return value is Map<String, dynamic> ? value : null;
  }

  List<Map<String, dynamic>> objects(String key) {
    final value = this[key];
    if (value is List) {
      return value.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    }
    return const [];
  }

  List<String> strings(String key) {
    final value = this[key];
    if (value is List) return value.map((e) => e.toString()).toList();
    return const [];
  }

  String str(String key, [String fallback = '']) {
    final value = this[key];
    if (value == null) return fallback;
    return value.toString();
  }

  double dbl(String key, [double fallback = 0]) {
    final value = this[key];
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? fallback;
    return fallback;
  }

  int integer(String key, [int fallback = 0]) {
    final value = this[key];
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value) ?? fallback;
    return fallback;
  }

  bool boolean(String key, [bool fallback = false]) {
    final value = this[key];
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) return value == '1' || value.toLowerCase() == 'true';
    return fallback;
  }
}
