import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api.dart';

/// Gamed member app: entry QR card, subscription and class self-booking.
/// Server URL is fixed per deployment; the member signs in with the private
/// portal link their gym shares with them (same token as the web portal).
const String kServerUrl = String.fromEnvironment(
  'GAMED_SERVER',
  defaultValue: 'https://gamed.mancydigitals.com',
);

void main() => runApp(const GamedMemberApp());

class GamedMemberApp extends StatelessWidget {
  const GamedMemberApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gamed',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF16B364)),
        useMaterial3: true,
        fontFamily: 'sans',
      ),
      home: const GateScreen(),
    );
  }
}

/// Decides between the token login and the member home.
class GateScreen extends StatefulWidget {
  const GateScreen({super.key});

  @override
  State<GateScreen> createState() => _GateScreenState();
}

class _GateScreenState extends State<GateScreen> {
  String? _token;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    SharedPreferences.getInstance().then((prefs) {
      setState(() {
        _token = prefs.getString('portal_token');
        _loading = false;
      });
    });
  }

  Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('portal_token', token);
    setState(() => _token = token);
  }

  Future<void> _signOut() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('portal_token');
    setState(() => _token = null);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_token == null) {
      return LoginScreen(onToken: _saveToken);
    }
    return HomeScreen(
      api: MemberApi(baseUrl: kServerUrl, token: _token!),
      onSignOut: _signOut,
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.onToken});

  final ValueChanged<String> onToken;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _error;

  Future<void> _submit() async {
    final token = MemberApi.parseToken(_controller.text);
    if (token.isEmpty) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      // Exchange the one-time link for a bounded session token, then store
      // the session token (never the link itself).
      final sessionToken = await MemberApi.exchange(kServerUrl, token);
      await MemberApi(baseUrl: kServerUrl, token: sessionToken).profile();
      widget.onToken(sessionToken);
    } on MemberAuthException {
      setState(() => _error = 'الرابط غير صحيح. اطلب رابطك من الجيم.');
    } catch (_) {
      setState(() => _error = 'تعذّر الاتصال بالخادم. حاول مرة أخرى.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text('جامد',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text('الصق رابط حسابك الذي وصلك من الجيم',
                    textAlign: TextAlign.center),
                const SizedBox(height: 24),
                TextField(
                  controller: _controller,
                  textDirection: TextDirection.ltr,
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    hintText: 'https://…/my/…',
                  ),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                ],
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _busy ? null : _submit,
                  child: Text(_busy ? '...' : 'دخول'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.api, required this.onSignOut});

  final MemberApi api;
  final VoidCallback onSignOut;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Profile? _profile;
  List<ClassDay> _days = [];
  String? _error;
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final profile = await widget.api.profile();
      final days = await widget.api.classes();
      setState(() {
        _profile = profile;
        _days = days;
        _error = null;
      });
    } on MemberAuthException {
      widget.onSignOut();
    } catch (_) {
      setState(() => _error = 'تعذّر التحميل');
    }
  }

  Future<void> _book(GymClass cls, String date) async {
    try {
      await widget.api.book(cls.id, date);
      await _load();
    } catch (_) {
      _toast('تعذّر الحجز');
    }
  }

  Future<void> _cancel(int bookingId) async {
    try {
      await widget.api.cancel(bookingId);
      await _load();
    } catch (_) {
      _toast('تعذّر الإلغاء');
    }
  }

  void _toast(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final p = _profile;
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(
          title: Text(p?.gymName ?? 'جامد'),
          actions: [
            IconButton(icon: const Icon(Icons.logout), onPressed: widget.onSignOut),
          ],
        ),
        body: p == null
            ? Center(
                child: _error != null ? Text(_error!) : const CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: _load,
                child: _tab == 0 ? _homeTab(p) : _classesTab(p),
              ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (i) => setState(() => _tab = i),
          destinations: const [
            NavigationDestination(icon: Icon(Icons.person), label: 'حسابي'),
            NavigationDestination(icon: Icon(Icons.fitness_center), label: 'الحصص'),
          ],
        ),
      ),
    );
  }

  Widget _homeTab(Profile p) {
    final sub = p.subscription;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(p.name,
                    style: const TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                if (sub != null) ...[
                  Text('الباقة: ${sub.plan}'),
                  if (sub.daysLeft != null) Text('باقي ${sub.daysLeft} يوم'),
                  if (sub.sessionsRemaining != null)
                    Text('باقي ${sub.sessionsRemaining} حصة'),
                  if (sub.endsAt != null) Text('ينتهي في ${sub.endsAt}'),
                ] else
                  const Text('لا يوجد اشتراك حالي'),
                if (p.balance > 0)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      'المتبقي عليك: ${p.balance.toStringAsFixed(0)} ${p.currency}',
                      style: const TextStyle(
                          color: Colors.red, fontWeight: FontWeight.bold),
                    ),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                const Text('بطاقة الدخول',
                    style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 12),
                QrImageView(data: p.qrToken, size: 200),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _classesTab(Profile p) {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _days.length,
      itemBuilder: (context, i) {
        final day = _days[i];
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(day.date,
                  style: const TextStyle(fontWeight: FontWeight.bold)),
            ),
            ...day.classes.map((cls) => Card(
                  child: ListTile(
                    title: Text(cls.name),
                    subtitle: Text(
                        '${cls.startTime} · ${cls.durationMinutes} دقيقة${cls.coach != null ? ' · ${cls.coach}' : ''}'),
                    trailing: cls.bookingStatus != null
                        ? TextButton(
                            onPressed: () => _cancel(cls.bookingId!),
                            child: Text(cls.bookingStatus == 'booked'
                                ? 'محجوز · إلغاء'
                                : 'انتظار · إلغاء'),
                          )
                        : FilledButton.tonal(
                            onPressed: () => _book(cls, day.date),
                            child: Text(cls.full ? 'احجز انتظار' : 'احجز'),
                          ),
                  ),
                )),
          ],
        );
      },
    );
  }
}
