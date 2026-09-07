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
              subtitle: 'View your allotted seat',
              onBack: onBack,
            ),
          ),
          const SizedBox(height: 16),
          if (allottedSeat == null)
            Expanded(child: _EmptyState())
          else ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: MySeatCard(
                seat: allottedSeat,
                onViewQr: onViewQr ?? () {},
              ),
            ),
            if (siblings.isNotEmpty) ...[
              const SizedBox(height: 24),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Sibling's Seat",
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Seats allotted to your siblings',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                  itemCount: siblings.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 10),
                  itemBuilder: (context, index) => SiblingSeatCard(sibling: siblings[index]),
                ),
              ),
            ],
          ],
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
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: AppColors.primaryBg,
                borderRadius: BorderRadius.circular(16),
              ),
              child: const Icon(Icons.event_seat_outlined, color: AppColors.primary, size: 32),
            ),
            const SizedBox(height: 20),
            Text(
              'No Seat Allotted',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'Your seat details will appear here once a seat is assigned to you.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
