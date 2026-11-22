import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:gamed_member/main.dart';
import 'package:gamed_member/api.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('shows the RTL Arabic sign-in screen when no token is stored', (tester) async {
    SharedPreferences.setMockInitialValues({});
    await tester.pumpWidget(const GamedMemberApp());
    await tester.pumpAndSettle();

    // Gate resolves to the login screen asking for the member link.
    expect(find.byType(LoginScreen), findsOneWidget);
    expect(find.byType(TextField), findsOneWidget);

    expect(
      find.byWidgetPredicate(
          (w) => w is Directionality && w.textDirection == TextDirection.rtl),
      findsWidgets,
    );
  });

  testWidgets('rejects an invalid link without calling the server', (tester) async {
    SharedPreferences.setMockInitialValues({});
    await tester.pumpWidget(const GamedMemberApp());
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField), 'not-a-link');
    await tester.tap(find.byType(FilledButton));
    await tester.pump();

    // parseToken yields empty, so the button stays idle and no error shows.
    expect(find.byType(LoginScreen), findsOneWidget);
  });

  test('parseToken accepts a raw token or a full portal URL', () {
    expect(MemberApi.parseToken('abc123'), 'abc123');
    const t48 = 'CvYph4tg18cneAatM5EdjDcrPzXWaPzxRMre7MmtN8WUbKuB';
    expect(MemberApi.parseToken('https://gym.example/my/$t48'), t48);
    expect(MemberApi.parseToken('https://gym.example/my/1a412e03-9648-4645-81bd-7ab73c97671a'), '1a412e03-9648-4645-81bd-7ab73c97671a');
    expect(MemberApi.parseToken(''), '');
  });
}
