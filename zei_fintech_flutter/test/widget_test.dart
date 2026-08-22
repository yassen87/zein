import 'package:flutter_test/flutter_test.dart';
import 'package:zei_fintech_flutter/main.dart';

void main() {
  testWidgets('App smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const ZeiFintechApp());
    expect(find.byType(ZeiFintechApp), findsOneWidget);
  });
}
