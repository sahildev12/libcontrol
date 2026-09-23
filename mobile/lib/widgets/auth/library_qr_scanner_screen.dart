import 'dart:async';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

const _scanIndigo = Color(0xFF4F46E5);
const _pageBg = Color(0xFFF8FAFC);
const _textPrimary = Color(0xFF0F172A);

class LibraryQrScannerScreen extends StatefulWidget {
  const LibraryQrScannerScreen({
    super.key,
    required this.onScanned,
    this.onManualEntry,
  });

  final Future<void> Function(String value) onScanned;
  final VoidCallback? onManualEntry;

  @override
  State<LibraryQrScannerScreen> createState() => _LibraryQrScannerScreenState();
}

class _LibraryQrScannerScreenState extends State<LibraryQrScannerScreen>
    with SingleTickerProviderStateMixin {
  final _controller = MobileScannerController(
    detectionSpeed: DetectionSpeed.normal,
    facing: CameraFacing.back,
  );
  final _picker = ImagePicker();

  bool _handling = false;
  late final AnimationController _lineController;

  @override
  void initState() {
    super.initState();
    _lineController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _lineController.dispose();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _handleScan(String? value) async {
    if (_handling || value == null || value.isEmpty) return;

    _handling = true;
    try {
      await widget.onScanned(value);
      if (mounted) Navigator.of(context).pop();
    } catch (_) {
      // Keep scanner open when connect/parse fails.
    } finally {
      if (mounted) _handling = false;
    }
  }

  Future<void> _pickFromGallery() async {
    final image = await _picker.pickImage(source: ImageSource.gallery);
    if (image == null) return;

    try {
      final capture = await _controller.analyzeImage(image.path);
      if (capture == null || capture.barcodes.isEmpty) {
        _showToast('No QR code found in that image.');
        return;
      }
      await _handleScan(capture.barcodes.first.rawValue);
    } catch (_) {
      _showToast('Could not read QR code from image.');
    }
  }

  void _showToast(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  static const _controlsBottom = 24.0;
  static const _controlsBlockHeight = 72.0;
  static const _pillGapBelowFrame = 16.0;
  static const _pillHeight = 34.0;
  static const _gapPillToControls = 14.0;

  _ScanLayout _scanLayout(double width, double height) {
    final bottomReserved = _controlsBottom +
        _controlsBlockHeight +
        _gapPillToControls +
        _pillHeight +
        _pillGapBelowFrame;

    final widthBased = width.clamp(240.0, 320.0);
    final heightCap = (height - bottomReserved - 16).clamp(200.0, 320.0);
    final frameSize = widthBased < heightCap ? widthBased : heightCap;

    final minFrameTop = 12.0;
    final maxFrameTop = (height - frameSize - bottomReserved).clamp(minFrameTop, height);
    final frameTop = maxFrameTop <= minFrameTop
        ? minFrameTop
        : (minFrameTop + maxFrameTop) / 2;
    final frameLeft = (width - frameSize) / 2;
    final pillTop = frameTop + frameSize + _pillGapBelowFrame;

    return _ScanLayout(
      frameSize: frameSize,
      frameTop: frameTop,
      frameLeft: frameLeft,
      pillTop: pillTop,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pageBg,
      body: SafeArea(
        child: Column(
          children: [
            _Header(
              onClose: () => Navigator.of(context).pop(),
              onGallery: _pickFromGallery,
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final layout = _scanLayout(
                      constraints.maxWidth,
                      constraints.maxHeight,
                    );

                    return ClipRRect(
                      borderRadius: BorderRadius.circular(20),
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          MobileScanner(
                            controller: _controller,
                            onDetect: (capture) async {
                              final value = capture.barcodes.isNotEmpty
                                  ? capture.barcodes.first.rawValue
                                  : null;
                              await _handleScan(value);
                            },
                          ),
                          _ScannerDimOverlay(
                            frameSize: layout.frameSize,
                            frameTop: layout.frameTop,
                          ),
                          Positioned(
                            left: layout.frameLeft,
                            top: layout.frameTop,
                            width: layout.frameSize,
                            height: layout.frameSize,
                            child: Stack(
                              children: [
                                ..._corners(),
                                AnimatedBuilder(
                                  animation: _lineController,
                                  builder: (context, child) {
                                    final top = 12 +
                                        (layout.frameSize - 36) * _lineController.value;
                                    return Positioned(
                                      left: 16,
                                      right: 16,
                                      top: top,
                                      child: Container(
                                        height: 2,
                                        decoration: BoxDecoration(
                                          color: _scanIndigo.withValues(alpha: 0.95),
                                          boxShadow: [
                                            BoxShadow(
                                              color: _scanIndigo.withValues(alpha: 0.55),
                                              blurRadius: 8,
                                            ),
                                          ],
                                        ),
                                      ),
                                    );
                                  },
                                ),
                              ],
                            ),
                          ),
                          Positioned(
                            left: 12,
                            right: 12,
                            top: layout.pillTop,
                            child: Center(
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 8,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.black.withValues(alpha: 0.55),
                                  borderRadius: BorderRadius.circular(24),
                                ),
                                child: const Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.qr_code_2_rounded,
                                      color: Colors.white,
                                      size: 18,
                                    ),
                                    SizedBox(width: 8),
                                    Text(
                                      'Align the QR code within the frame',
                                      style: TextStyle(color: Colors.white, fontSize: 12),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          Positioned(
                            left: 24,
                            right: 24,
                            bottom: _controlsBottom,
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                _CameraControl(
                                  icon: Icons.flash_on_rounded,
                                  label: 'Flash',
                                  onTap: () => _controller.toggleTorch(),
                                ),
                                _CameraControl(
                                  icon: Icons.cameraswitch_rounded,
                                  label: 'Switch',
                                  onTap: () => _controller.switchCamera(),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ),
            _ManualEntryBar(onTap: widget.onManualEntry),
          ],
        ),
      ),
    );
  }

  List<Widget> _corners() {
    const len = 28.0;
    const stroke = 3.0;
    const color = Colors.white;

    Widget corner({required bool top, required bool left}) {
      return Positioned(
        top: top ? 0 : null,
        bottom: top ? null : 0,
        left: left ? 0 : null,
        right: left ? null : 0,
        child: SizedBox(
          width: len,
          height: len,
          child: DecoratedBox(
            decoration: BoxDecoration(
              border: Border(
                top: top ? BorderSide(color: color, width: stroke) : BorderSide.none,
                bottom: top ? BorderSide.none : BorderSide(color: color, width: stroke),
                left: left ? BorderSide(color: color, width: stroke) : BorderSide.none,
                right: left ? BorderSide.none : BorderSide(color: color, width: stroke),
              ),
              borderRadius: BorderRadius.only(
                topLeft: (top && left) ? const Radius.circular(8) : Radius.zero,
                topRight: (top && !left) ? const Radius.circular(8) : Radius.zero,
                bottomLeft: (!top && left) ? const Radius.circular(8) : Radius.zero,
                bottomRight: (!top && !left) ? const Radius.circular(8) : Radius.zero,
              ),
            ),
          ),
        ),
      );
    }

    return [
      corner(top: true, left: true),
      corner(top: true, left: false),
      corner(top: false, left: true),
      corner(top: false, left: false),
    ];
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.onClose, required this.onGallery});

  final VoidCallback onClose;
  final VoidCallback onGallery;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _CircleIconButton(icon: Icons.close_rounded, onTap: onClose),
          Expanded(
            child: Column(
              children: const [
                Text(
                  'Scan to connect library',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                    color: _textPrimary,
                  ),
                ),
                SizedBox(height: 6),
                Text(
                  'Point your camera at the QR code provided\nby the library staff',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13,
                    height: 1.35,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          _CircleIconButton(icon: Icons.photo_library_outlined, onTap: onGallery),
        ],
      ),
    );
  }
}

class _CircleIconButton extends StatelessWidget {
  const _CircleIconButton({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.blueBg,
      shape: const CircleBorder(),
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: SizedBox(
          width: 40,
          height: 40,
          child: Icon(icon, size: 22, color: AppColors.primary),
        ),
      ),
    );
  }
}

class _CameraControl extends StatelessWidget {
  const _CameraControl({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Material(
          color: Colors.black.withValues(alpha: 0.45),
          shape: const CircleBorder(),
          child: InkWell(
            onTap: onTap,
            customBorder: const CircleBorder(),
            child: SizedBox(
              width: 52,
              height: 52,
              child: Icon(icon, color: Colors.white, size: 26),
            ),
          ),
        ),
        const SizedBox(height: 6),
        Text(label, style: const TextStyle(color: Colors.white, fontSize: 12)),
      ],
    );
  }
}

class _ManualEntryBar extends StatelessWidget {
  const _ManualEntryBar({this.onTap});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: OutlinedButton.icon(
        onPressed: onTap ?? () => Navigator.of(context).pop(),
        icon: const Icon(Icons.keyboard_outlined, color: AppColors.primary),
        label: const Text(
          'Enter Code Manually',
          style: TextStyle(
            color: AppColors.primary,
            fontWeight: FontWeight.w600,
          ),
        ),
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          backgroundColor: AppColors.blueBg,
          side: const BorderSide(color: AppColors.border),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
      ),
    );
  }
}

class _ScanLayout {
  const _ScanLayout({
    required this.frameSize,
    required this.frameTop,
    required this.frameLeft,
    required this.pillTop,
  });

  final double frameSize;
  final double frameTop;
  final double frameLeft;
  final double pillTop;
}

class _ScannerDimOverlay extends StatelessWidget {
  const _ScannerDimOverlay({
    required this.frameSize,
    required this.frameTop,
  });

  final double frameSize;
  final double frameTop;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final w = constraints.maxWidth;
        final left = (w - frameSize) / 2;
        final top = frameTop;
        final overlay = Colors.black.withValues(alpha: 0.45);

        return Stack(
          children: [
            Positioned(left: 0, top: 0, right: 0, height: top, child: ColoredBox(color: overlay)),
            Positioned(left: 0, top: top + frameSize, right: 0, bottom: 0, child: ColoredBox(color: overlay)),
            Positioned(left: 0, top: top, width: left, height: frameSize, child: ColoredBox(color: overlay)),
            Positioned(right: 0, top: top, width: left, height: frameSize, child: ColoredBox(color: overlay)),
          ],
        );
      },
    );
  }
}
