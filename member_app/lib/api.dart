import 'dart:convert';
import 'package:http/http.dart' as http;

/// Thin client for the Gamed member API. Authentication is the member's
/// private portal token sent as a Bearer header; the same token backs the
/// web portal (/my/{token}), so a member can paste either the raw token or
/// their full portal link.
class MemberApi {
  MemberApi({required this.baseUrl, required this.token});

  /// e.g. https://gamed.mancydigitals.com
  final String baseUrl;
  final String token;

  static const _prefix = '/api/member';

  Map<String, String> get _headers => {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };

  Uri _u(String path) => Uri.parse('$baseUrl$_prefix$path');

  Future<Map<String, dynamic>> _get(String path) async {
    final res = await http.get(_u(path), headers: _headers);
    if (res.statusCode == 401) {
      throw MemberAuthException();
    }
    if (res.statusCode != 200) {
      throw MemberApiException(res.statusCode, res.body);
    }
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  Future<Profile> profile() async => Profile.fromJson(await _get('/profile'));

  Future<List<ClassDay>> classes() async {
    final body = await _get('/classes');
    return (body['days'] as List)
        .map((d) => ClassDay.fromJson(d as Map<String, dynamic>))
        .toList();
  }

  Future<List<Booking>> bookings() async {
    final body = await _get('/bookings');
    return (body['bookings'] as List)
        .map((b) => Booking.fromJson(b as Map<String, dynamic>))
        .toList();
  }

  Future<BookResult> book(int classId, String date) async {
    final res = await http.post(_u('/bookings'),
        headers: _headers,
        body: jsonEncode({'class_id': classId, 'class_date': date}));
    if (res.statusCode == 201) {
      return BookResult.fromJson(
          jsonDecode(res.body)['booking'] as Map<String, dynamic>);
    }
    if (res.statusCode == 401) {
      throw MemberAuthException();
    }
    throw MemberApiException(res.statusCode, res.body);
  }

  Future<void> cancel(int bookingId) async {
    final res = await http.delete(_u('/bookings/$bookingId'), headers: _headers);
    if (res.statusCode == 401) {
      throw MemberAuthException();
    }
    if (res.statusCode != 200) {
      throw MemberApiException(res.statusCode, res.body);
    }
  }

  /// Accepts a raw token or a full portal URL and returns the token.
  static String parseToken(String input) {
    final trimmed = input.trim();
    final match = RegExp(r'/my/([A-Za-z0-9-]{32,64})').firstMatch(trimmed);
    return match?.group(1) ?? trimmed;
  }
}

class MemberAuthException implements Exception {}

class MemberApiException implements Exception {
  MemberApiException(this.status, this.body);
  final int status;
  final String body;
  @override
  String toString() => 'MemberApiException($status)';
}

class Profile {
  Profile.fromJson(Map<String, dynamic> j)
      : name = j['member']['name'] as String,
        status = j['member']['status'] as String,
        gymName = j['gym']['name'] as String,
        locale = (j['gym']['locale'] as String?) ?? 'ar',
        currency = (j['gym']['currency'] as String?) ?? '',
        subscription = j['subscription'] == null
            ? null
            : Subscription.fromJson(j['subscription'] as Map<String, dynamic>),
        balance = (j['balance'] as num).toDouble(),
        qrToken = j['qr_token'] as String;

  final String name;
  final String status;
  final String gymName;
  final String locale;
  final String currency;
  final Subscription? subscription;
  final double balance;
  final String qrToken;

  bool get isArabic => locale == 'ar';
}

class Subscription {
  Subscription.fromJson(Map<String, dynamic> j)
      : plan = j['plan'] as String,
        status = j['status'] as String,
        daysLeft = j['days_left'] as int?,
        sessionsRemaining = j['sessions_remaining'] as int?,
        endsAt = j['ends_at'] as String?;

  final String plan;
  final String status;
  final int? daysLeft;
  final int? sessionsRemaining;
  final String? endsAt;
}

class ClassDay {
  ClassDay.fromJson(Map<String, dynamic> j)
      : date = j['date'] as String,
        weekday = j['weekday'] as int,
        classes = (j['classes'] as List)
            .map((c) => GymClass.fromJson(c as Map<String, dynamic>))
            .toList();

  final String date;
  final int weekday;
  final List<GymClass> classes;
}

class GymClass {
  GymClass.fromJson(Map<String, dynamic> j)
      : id = j['id'] as int,
        name = j['name'] as String,
        coach = j['coach'] as String?,
        startTime = j['start_time'] as String,
        durationMinutes = j['duration_minutes'] as int,
        full = j['full'] as bool,
        bookingId = j['booking_id'] as int?,
        bookingStatus = j['booking_status'] as String?;

  final int id;
  final String name;
  final String? coach;
  final String startTime;
  final int durationMinutes;
  final bool full;
  final int? bookingId;
  final String? bookingStatus;
}

class Booking {
  Booking.fromJson(Map<String, dynamic> j)
      : id = j['id'] as int,
        className = j['class'] as String,
        date = j['date'] as String,
        startTime = j['start_time'] as String?,
        status = j['status'] as String;

  final int id;
  final String className;
  final String date;
  final String? startTime;
  final String status;
}

class BookResult {
  BookResult.fromJson(Map<String, dynamic> j)
      : id = j['id'] as int,
        status = j['status'] as String;

  final int id;
  final String status;
}
