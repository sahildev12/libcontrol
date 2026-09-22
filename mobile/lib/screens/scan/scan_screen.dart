import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/api/attendance_api.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
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
  _ScanSuccess? _success;

  @override
  void initState() {
    super.initState();
    _scannerController = MobileScannerController(
      detectionSpeed: DetectionSpeed.normal,
      facing: CameraFacing.back,
    );
    _ensureCameraPermission();
  }

  @override
  void didUpdateWidget(ScanScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.isActive != widget.isActive) {
      _syncCameraWithVisibility();
      if (!widget.isActive) {
        setState(() => _success = null);
      }
    }
  }

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  Future<void> _syncCameraWithVisibility() async {
    if (!widget.isActive || _success != null) {
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

  void _clearSuccess() {
    setState(() => _success = null);
    _syncCameraWithVisibility();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_handlingScan || _success != null || !widget.isActive) return;

    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;

    final value = barcodes.first.rawValue;
    if (value == null || value.isEmpty || value == _lastScannedCode) return;

    _handlingScan = true;
    _lastScannedCode = value;

    if (!mounted) return;

    final isCheckOut = _modeIndex == 1;

    if (isCheckOut) {
      _showSnack('Check-out is not available yet. Use Check In to mark attendance.', AppColors.warning);
      await Future<void>.delayed(const Duration(seconds: 2));
      if (mounted) {
        _handlingScan = false;
        _lastScannedCode = null;
      }
      return;
    }

    try {
      await _attendanceApi.checkInFromQr(value);
      if (!mounted) return;
      await _scannerController.stop();
      setState(() {
        _success = _ScanSuccess(isCheckOut: false, at: DateTime.now());
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      final color = error.statusCode == 422 ? AppColors.warning : AppColors.danger;
      _showSnack(error.message, color);
    } catch (_) {
      if (!mounted) return;
      _showSnack('Could not complete check-in. Try again.', AppColors.danger);
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
    if (_success != null) {
      _clearSuccess();
    }
    setState(() => _modeIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    if (_success != null) {
      return SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: Column(
            children: [
              Expanded(
                child: ScanSuccessPanel(
                  isCheckOut: _success!.isCheckOut,
                  time: _success!.at,
                ),
              ),
            ],
          ),
        ),
      );
    }

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
            Expanded(child: _buildScanner()),
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
            const SizedBox(height: 8),
            _LocationCard(onTap: () {}),
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

class _ScanSuccess {
  const _ScanSuccess({required this.isCheckOut, required this.at});

  final bool isCheckOut;
  final DateTime at;
}

class _LocationCard extends StatelessWidget {
  const _LocationCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
            boxShadow: const [
              BoxShadow(color: Color(0x06000000), blurRadius: 8, offset: Offset(0, 2)),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: AppColors.primaryBg,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.location_on_outlined, color: AppColors.primary, size: 20),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        AuthService.instance.student?.homeBranch ?? DummyData.fallbackStudent.homeBranch,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                      ),
                      Text(
                        AuthService.instance.student?.currentHall ?? DummyData.fallbackStudent.currentHall,
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary, size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
