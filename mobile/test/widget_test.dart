import 'package:flutter_test/flutter_test.dart';
import 'package:libcontrol_app/app/app.dart';

void main() {
  testWidgets('Login screen renders', (WidgetTester tester) async {
    await tester.pumpWidget(const LibControlApp());
    await tester.pumpAndSettle();

    expect(find.text('LibControl'), findsOneWidget);
    expect(find.text('Sign In'), findsOneWidget);
  });
}
