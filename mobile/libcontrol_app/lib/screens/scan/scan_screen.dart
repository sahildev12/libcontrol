import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/widgets/app_header.dart';
import 'package:libcontrol_app/widgets/info_card.dart';
import 'package:libcontrol_app/widgets/secondary_button.dart';

class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  int _modeIndex = 0;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const AppHeader(title: 'Scan QR'),
            const SizedBox(height: 20),
            SegmentedButton<int>(
              segments: const [
                ButtonSegment(value: 0, label: Text('Check In')),
                ButtonSegment(value: 1, label: Text('Check Out')),
              ],
              selected: {_modeIndex},
              onSelectionChanged: (value) => setState(() => _modeIndex = value.first),
            ),
            const SizedBox(height: 20),
            AspectRatio(
              aspectRatio: 0.85,
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    const Icon(Icons.qr_code_2_rounded, color: Colors.white24, size: 120),
                    Positioned(
                      top: 32,
                      left: 32,
                      child: _corner(),
                    ),
                    Positioned(
                      top: 32,
                      right: 32,
                      child: Transform.rotate(angle: 1.5708, child: _corner()),
                    ),
                    Positioned(
                      bottom: 32,
                      left: 32,
                      child: Transform.rotate(angle: -1.5708, child: _corner()),
                    ),
                    Positioned(
                      bottom: 32,
                      right: 32,
                      child: Transform.rotate(angle: 3.14159, child: _corner()),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Center(
              child: Text(
                'Align the QR code within the frame',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            ),
            const SizedBox(height: 20),
            InfoCard(
              onTap: () {},
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(DummyData.student.homeBranch, style: Theme.of(context).textTheme.titleMedium),
                        const SizedBox(height: 4),
                        Text(
                          DummyData.student.currentHall,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SecondaryButton(
              label: 'Enter Manually',
              icon: Icons.keyboard_outlined,
              onPressed: () {},
            ),
          ],
        ),
      ),
    );
  }

  Widget _corner() {
    return Container(
      width: 28,
      height: 28,
      decoration: const BoxDecoration(
        border: Border(
          top: BorderSide(color: AppColors.primary, width: 4),
          left: BorderSide(color: AppColors.primary, width: 4),
        ),
      ),
    );
  }
}
