import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/library_resolver_service.dart';
import 'package:libcontrol_app/core/config/server_config.dart';
import 'package:libcontrol_app/widgets/libcontrol_logo.dart';
import 'package:libcontrol_app/widgets/primary_button.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:permission_handler/permission_handler.dart';

class LibraryConnectScreen extends StatefulWidget {
  const LibraryConnectScreen({super.key, required this.onConnected});

  final VoidCallback onConnected;

  @override
  State<LibraryConnectScreen> createState() => _LibraryConnectScreenState();
}

class _LibraryConnectScreenState extends State<LibraryConnectScreen> {
  final _codeController = TextEditingController();
  final _resolver = LibraryResolverService();
  bool _loading = false;
  bool _scanning = false;

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  Future<void> _connect({
    required String apiBaseUrl,
    String? libraryName,
    String? libraryCode,
  }) async {
    setState(() => _loading = true);

    try {
      await _resolver.validateLibraryServer(apiBaseUrl);
      await ServerConfig.instance.setLibrary(
        apiBaseUrl: apiBaseUrl,
        libraryName: libraryName ?? libraryCode,
      );
      widget.onConnected();
    } on LibraryResolverException catch (e) {
      _showMessage(e.message);
    } catch (_) {
      _showMessage('Could not connect to your library. Check your internet and try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _continueWithCode() async {
    final code = _codeController.text.trim();
    if (code.isEmpty) {
      _showMessage('Enter your library code.');
      return;
    }

    setState(() => _loading = true);

    try {
      final connection = await _resolver.resolveCode(code);
      await _connect(
        apiBaseUrl: connection.apiBaseUrl,
        libraryName: connection.name,
        libraryCode: connection.code,
      );
    } on LibraryResolverException catch (e) {
      _showMessage(e.message);
    } catch (_) {
      _showMessage('Could not look up your library. Check your internet and try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openQrScanner() async {
    final status = await Permission.camera.request();
    if (!status.isGranted) {
      _showMessage('Camera permission is required to scan the attendance QR code.');
      return;
    }

    if (!mounted) return;
    setState(() => _scanning = true);

    await Navigator.of(context).push(
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (_) => _LibraryQrScannerScreen(
          onScanned: (value) async {
            final apiBaseUrl = ServerConfig.parseAttendanceQrUrl(value);
            if (apiBaseUrl == null) {
              _showMessage('Scan your library attendance QR code.');
              return;
            }

            await _connect(apiBaseUrl: apiBaseUrl);
          },
        ),
      ),
    );

    if (mounted) setState(() => _scanning = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: Column(
            children: [
              const SizedBox(height: 24),
              const LibControlLogo(size: 80),
              const SizedBox(height: 16),
              Text('Connect to your library', style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 8),
              const Text(
                'Enter the 6-digit library code from your branch staff. Codes are looked up via libcontrol.phenomit.com.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 32),
              TextField(
                controller: _codeController,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: false,
                  signed: false,
                ),
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(6),
                ],
                maxLength: 6,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  letterSpacing: 4,
                  fontWeight: FontWeight.w700,
                ),
                decoration: const InputDecoration(
                  labelText: 'Library code',
                  hintText: '6 digits',
                  counterText: '',
                  border: OutlineInputBorder(),
                ),
                onSubmitted: (_) => _continueWithCode(),
              ),
              const SizedBox(height: 16),
              PrimaryButton(
                label: 'Continue',
                isLoading: _loading,
                onPressed: _loading ? null : _continueWithCode,
              ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _loading || _scanning ? null : _openQrScanner,
                icon: const Icon(Icons.qr_code_scanner_outlined),
                label: const Text('Scan attendance QR'),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LibraryQrScannerScreen extends StatefulWidget {
  const _LibraryQrScannerScreen({required this.onScanned});

  final Future<void> Function(String value) onScanned;

  @override
  State<_LibraryQrScannerScreen> createState() => _LibraryQrScannerScreenState();
}

class _LibraryQrScannerScreenState extends State<_LibraryQrScannerScreen> {
  final _controller = MobileScannerController(
    detectionSpeed: DetectionSpeed.normal,
    facing: CameraFacing.back,
  );
  bool _handling = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _handleScan(String? value) async {
    if (_handling || value == null || value.isEmpty) return;

    _handling = true;
    await widget.onScanned(value);
    if (mounted) {
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scan attendance QR')),
      body: MobileScanner(
        controller: _controller,
        onDetect: (capture) async {
          final value = capture.barcodes.isNotEmpty
              ? capture.barcodes.first.rawValue
              : null;
          await _handleScan(value);
        },
      ),
    );
  }
}
