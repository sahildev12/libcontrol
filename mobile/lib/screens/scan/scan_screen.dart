import 'dart:async';

import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/api/attendance_api.dart';
import 'package:libcontrol_app/core/attendance/scan_attendance_success_store.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';
import 'package:libcontrol_app/widgets/scan/scan_mode_switch.dart';
import 'package:libcontrol_app/widgets/scan/scan_success_panel.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:permission_handler/permission_handler.dart';

class ScanScreen extends StatefulWidget {
  const ScanScreen({
    super.key,
    this.onBack,
    this.isActive = true,
  });

  final VoidCallback? onBack;
  final bool isActive;

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  final _attendanceApi = AttendanceApi();
  int _modeIndex = 0;
  late final MobileScannerController _scannerController;
  bool _cameraGranted = false;
  bool _checkingPermission = true;
  bool _handlingScan = false;
  String? _lastScannedCode;
  ScanAttendanceSuccess? _success;
  Timer? _successExpiryTimer;

  bool get _hasActiveSuccess => _success != null && _success!.isActive;

  bool get _successMatchesMode {
    if (!_hasActiveSuccess) {
      return false;
    }
    final isCheckOutMode = _modeIndex == 1;
    return _success!.isCheckOut == isCheckOutMode;
  }

  @override
  void initState() {
    super.initState();
    _scannerController = MobileScannerController(
      detectionSpeed: DetectionSpeed.normal,
      facing: CameraFacing.back,
    );
    _ensureCameraPermission();
    _restoreSuccess();
  }

  @override
  void didUpdateWidget(ScanScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.isActive != widget.isActive) {
      _syncCameraWithVisibility();
    }
  }

  @override
  void dispose() {
    _successExpiryTimer?.cancel();
    _scannerController.dispose();
    super.dispose();
  }

  Future<void> _restoreSuccess() async {
    final stored = await ScanAttendanceSuccessStore.load();
    if (!mounted || stored == null) {
      return;
    }

    setState(() => _success = stored);
    _scheduleSuccessExpiry();
    await _syncCameraWithVisibility();
  }

  void _scheduleSuccessExpiry() {
    _successExpiryTimer?.cancel();
    final success = _success;
    if (success == null || !success.isActive) {
      return;
    }

    final remaining = success.expiresAt.difference(DateTime.now());
    if (remaining <= Duration.zero) {
      _clearSuccess();
      return;
    }

    _successExpiryTimer = Timer(remaining, () {
      if (mounted) {
        _clearSuccess();
      }
    });
  }

  Future<void> _persistSuccess(ScanAttendanceSuccess success) async {
    await ScanAttendanceSuccessStore.save(success);
    setState(() => _success = success);
    _scheduleSuccessExpiry();
    await _syncCameraWithVisibility();
  }

  Future<void> _syncCameraWithVisibility() async {
    if (!widget.isActive || _successMatchesMode) {
      await _scannerController.stop();
      return;
    }
    if (_cameraGranted && !_checkingPermission) {
      await _scannerController.start();
    }
  }

  Future<void> _ensureCameraPermission() async {
    final status = await Permission.camera.request();
    if (!mounted) return;
    setState(() {
      _cameraGranted = status.isGranted;
      _checkingPermission = false;
    });
    await _syncCameraWithVisibility();
  }

  Future<void> _clearSuccess() async {
    _successExpiryTimer?.cancel();
    await ScanAttendanceSuccessStore.clear();
    if (!mounted) return;
    setState(() => _success = null);
    await _syncCameraWithVisibility();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_handlingScan || _successMatchesMode || !widget.isActive) return;

    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;

    final value = barcodes.first.rawValue;
    if (value == null || value.isEmpty || value == _lastScannedCode) return;

    _handlingScan = true;
    _lastScannedCode = value;

    if (!mounted) return;

    final isCheckOut = _modeIndex == 1;

    try {
      if (isCheckOut) {
        await _attendanceApi.checkOutFromQr(value);
      } else {
        await _attendanceApi.checkInFromQr(value);
      }
      if (!mounted) return;
      await _persistSuccess(
        ScanAttendanceSuccess(isCheckOut: isCheckOut, at: DateTime.now()),
      );
      await AuthService.instance.bootstrap(validateOnline: true);
    } on ApiException catch (error) {
      if (!mounted) return;
      final color = error.statusCode == 422 ? AppColors.warning : AppColors.danger;
      _showSnack(error.message, color);
    } catch (_) {
      if (!mounted) return;
      _showSnack(
        isCheckOut
            ? 'Could not complete check-out. Try again.'
            : 'Could not complete check-in. Try again.',
        AppColors.danger,
      );
    }

    await Future<void>.delayed(const Duration(seconds: 1));
    if (mounted) {
      _handlingScan = false;
      _lastScannedCode = null;
    }
  }

  void _showSnack(String message, Color background) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: background,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _onModeChanged(int index) {
    setState(() => _modeIndex = index);
    _syncCameraWithVisibility();
  }

  @override
  Widget build(BuildContext context) {
    final success = _successMatchesMode ? _success : null;

    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            CenteredPageHeader(
              title: 'Scan QR',
              subtitle: 'Mark your presence in seconds',
              onBack: widget.onBack,
            ),
            const SizedBox(height: 12),
            ScanModeSwitch(
              selectedIndex: _modeIndex,
              onChanged: _onModeChanged,
            ),
            const SizedBox(height: 12),
            Expanded(
              child: success != null
                  ? ScanSuccessPanel(
                      isCheckOut: success.isCheckOut,
                      time: success.at,
                    )
                  : _buildScanner(),
            ),
            if (success == null) ...[
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.qr_code_scanner_rounded, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Text(
                    'Align the QR code within the frame',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildScanner() {
    if (!widget.isActive) {
      return Container(
        decoration: BoxDecoration(
          color: AppColors.primaryDark,
          borderRadius: BorderRadius.circular(20),
        ),
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: Stack(
        fit: StackFit.expand,
        children: [
          if (_checkingPermission)
            Container(
              color: AppColors.primaryDark,
              child: const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              ),
            )
          else if (!_cameraGranted)
            Container(
              color: AppColors.primaryDark,
              padding: const EdgeInsets.all(20),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.videocam_off_outlined, color: Colors.white54, size: 40),
                  const SizedBox(height: 10),
                  const Text(
                    'Camera permission is required to scan QR codes.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: _ensureCameraPermission,
                    style: FilledButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      minimumSize: const Size(0, 40),
                    ),
                    child: const Text('Allow camera'),
                  ),
                ],
              ),
            )
          else
            MobileScanner(
              controller: _scannerController,
              onDetect: _onDetect,
            ),
          if (_cameraGranted) ...[
            Positioned(top: 24, left: 24, child: _corner()),
            Positioned(top: 24, right: 24, child: Transform.rotate(angle: 1.5708, child: _corner())),
            Positioned(bottom: 24, left: 24, child: Transform.rotate(angle: -1.5708, child: _corner())),
            Positioned(bottom: 24, right: 24, child: Transform.rotate(angle: 3.14159, child: _corner())),
          ],
        ],
      ),
    );
  }

  Widget _corner() {
    return Container(
      width: 26,
      height: 26,
      decoration: const BoxDecoration(
        border: Border(
          top: BorderSide(color: AppColors.secondary, width: 4),
          left: BorderSide(color: AppColors.secondary, width: 4),
        ),
      ),
    );
  }
}
