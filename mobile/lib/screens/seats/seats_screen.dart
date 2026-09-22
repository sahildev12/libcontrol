import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';
import 'package:libcontrol_app/widgets/seats/my_seat_card.dart';
import 'package:libcontrol_app/widgets/seats/sibling_seat_card.dart';

class SeatsScreen extends StatelessWidget {
  const SeatsScreen({super.key, this.onViewQr, this.onBack});

  final VoidCallback? onViewQr;
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    final allottedSeat = DummyData.myAllottedSeat;
    final siblings = DummyData.siblingSeats;

    return SafeArea(
      bottom: false,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: CenteredPageHeader(
              title: 'My Seat',
              subtitle: 'Your library seating details',
              onBack: onBack,
            ),
          ),
          Expanded(
            child: allottedSeat == null
                ? _EmptyState()
                : ListView(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                    children: [
                      MySeatCard(
                        seat: allottedSeat,
                        onViewQr: onViewQr ?? () {},
                      ),
                      if (siblings.isNotEmpty) ...[
                        const SizedBox(height: 28),
                        Text(
                          'Family seats',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Seats linked to your family group',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                        const SizedBox(height: 12),
                        ...siblings.map(
                          (sibling) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: SiblingSeatCard(sibling: sibling),
                          ),
                        ),
                      ],
                    ],
                  ),
          ),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: AppColors.primaryBg,
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Icon(Icons.event_seat_outlined, color: AppColors.primary, size: 36),
            ),
            const SizedBox(height: 20),
            Text(
              'No seat allotted yet',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'When staff assign you a seat, hall, floor, and booking details will show here.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
