import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

class BiometricAuthHelper {
  BiometricAuthHelper(this._localAuth);

  final LocalAuthentication _localAuth;

  Future<bool> isAvailable() async {
    try {
      if (!await _localAuth.isDeviceSupported()) {
        return false;
      }

      if (await _localAuth.canCheckBiometrics) {
        return true;
      }

      final enrolled = await _localAuth.getAvailableBiometrics();
      return enrolled.isNotEmpty;
    } on PlatformException {
      return false;
    }
  }

  Future<bool> authenticate({required String reason}) async {
    if (!await isAvailable()) {
      return false;
    }

    try {
      return await _localAuth.authenticate(
        localizedReason: reason,
        biometricOnly: true,
        persistAcrossBackgrounding: true,
      );
    } on LocalAuthException catch (e) {
      if (e.code == LocalAuthExceptionCode.userCanceled ||
          e.code == LocalAuthExceptionCode.systemCanceled) {
        return false;
      }

      return _localAuth.authenticate(
        localizedReason: reason,
        biometricOnly: false,
        persistAcrossBackgrounding: true,
      );
    } on PlatformException {
      return false;
    }
  }
}
