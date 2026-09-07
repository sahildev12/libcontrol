enum SeatAllotmentStatus { active, inactive }

class AllottedSeat {
  const AllottedSeat({
    required this.seatCode,
    required this.hall,
    required this.floor,
    required this.status,
    required this.bookedOn,
    required this.amountPaid,
  });

  final String seatCode;
  final String hall;
  final String floor;
  final SeatAllotmentStatus status;
  final DateTime bookedOn;
  final int amountPaid;

  String get statusLabel => switch (status) {
        SeatAllotmentStatus.active => 'Active',
        SeatAllotmentStatus.inactive => 'Inactive',
      };

  String get formattedBookedOn => _formatDate(bookedOn);

  String get formattedAmount => '₹$amountPaid';

  static String _formatDate(DateTime date) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    final day = date.day.toString().padLeft(2, '0');
    return '$day ${months[date.month - 1]} ${date.year}';
  }
}

class SiblingSeat {
  const SiblingSeat({
    required this.name,
    required this.relationship,
    required this.seat,
  });

  final String name;
  final String relationship;
  final AllottedSeat seat;

  String get displayName => '$name ($relationship)';
}
