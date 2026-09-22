import 'package:flutter/material.dart';

/// Closes student auth sub-screens (PIN login / create PIN) so [AuthGate]
/// can show the authenticated home screen underneath.
void completeStudentAuthFlow(BuildContext context) {
  final navigator = Navigator.of(context);
  if (navigator.canPop()) {
    navigator.popUntil((route) => route.isFirst);
  }
}
