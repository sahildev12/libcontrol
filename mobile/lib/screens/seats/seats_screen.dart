import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/models/allotted_seat.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';
import 'package:libcontrol_app/widgets/seats/my_seat_card.dart';
import 'package:libcontrol_app/widgets/seats/payment_history_section.dart';
import 'package:libcontrol_app/widgets/seats/seat_expiry_marquee.dart';
import 'package:libcontrol_app/widgets/seats/sibling_seat_card.dart';

class SeatsScreen extends StatefulWidget {
  const SeatsScreen({super.key, this.onViewQr, this.onBack});

  final VoidCallback? onViewQr;
  final VoidCallback? onBack;

  @override
  State<SeatsScreen> createState() => _SeatsScreenState();
}

class _SeatsScreenState extends State<SeatsScreen> {
  bool _refreshing = false;

  Future<void> _refreshProfile() async {
    setState(() => _refreshing = true);
    await AuthService.instance.bootstrap(validateOnline: true);
    if (mounted) setState(() => _refreshing = false);
  }

  String? _expiryMarqueeMessage() {
    final student = AuthService.instance.student;
    if (student == null || !student.seatExpiringSoon) {
      return null;
    }

    final days = student.daysUntilPlanExpiry;
    final till = student.planValidTill;

    if (days == null || till.isEmpty) {
      return 'Your seat plan is expiring soon. Please renew at the library desk.';
    }

    if (days == 0) {
      return 'Your seat plan expires today ($till). Renew now to keep your seat.';
    }

    return 'Your seat plan expires in $days day${days == 1 ? '' : 's'} ($till). Renew at the library desk.';
  }

  AllottedSeat? _seatFromStudent() {
    final student = AuthService.instance.student;
    if (student == null || student.currentSeat.isEmpty) {
      return DummyData.myAllottedSeat;
    }

    final hallParts = student.currentHall.split(' — ');
    final paid = student.amountPaid;

    return AllottedSeat(
      seatCode: student.currentSeat,
      hall: hallParts.isNotEmpty ? hallParts.first : student.currentHall,
      floor: hallParts.length > 1 ? hallParts[1] : '',
      status: SeatAllotmentStatus.active,
      bookedOn: _parseBookedOn(student.bookedOn),
      amountPaid: paid != null ? paid.round() : 0,
      planValidTill: student.planValidTill,
      feeAmount: student.feeAmount,
    );
  }

  DateTime _parseBookedOn(String label) {
    if (label.isEmpty) {
      return DateTime.now();
    }
    final parts = label.split(' ');
    if (parts.length >= 3) {
      const months = {
        'Jan': 1,
        'Feb': 2,
        'Mar': 3,
        'Apr': 4,
        'May': 5,
        'Jun': 6,
        'Jul': 7,
        'Aug': 8,
        'Sep': 9,
        'Oct': 10,
        'Nov': 11,
        'Dec': 12,
      };
      final month = months[parts[1]];
      final day = int.tryParse(parts[0]);
      final year = int.tryParse(parts[2]);
      if (month != null && day != null && year != null) {
        return DateTime(year, month, day);
      }
    }
    return DateTime.now();
  }

  @override
  Widget build(BuildContext context) {
    final allottedSeat = _seatFromStudent();
    final siblings = DummyData.siblingSeats;
    final student = AuthService.instance.student;
    final marquee = _expiryMarqueeMessage();

    return SafeArea(
      bottom: false,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: CenteredPageHeader(
              title: 'My Seat',
              subtitle: 'Seat, plan, and payments',
              onBack: widget.onBack,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refreshProfile,
              child: allottedSeat == null
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: const [_EmptyState()],
                    )
                  : ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                      children: [
                        if (marquee != null) ...[
                          SeatExpiryMarquee(message: marquee),
                          const SizedBox(height: 14),
                        ],
                        if (_refreshing)
                          const Padding(
                            padding: EdgeInsets.only(bottom: 12),
                            child: LinearProgressIndicator(minHeight: 2, color: AppColors.primary),
                          ),
                        MySeatCard(
                          seat: allottedSeat,
                          onViewQr: widget.onViewQr ?? () {},
                        ),
                        const SizedBox(height: 24),
                        PaymentHistorySection(
                          payments: student?.paymentHistory ?? const [],
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
          ),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Padding(
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
            'When staff assign you a seat, hall, plan dates, and payments will show here.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
