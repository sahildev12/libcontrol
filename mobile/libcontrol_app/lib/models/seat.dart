enum SeatStatus { available, occupied, selected }

class SeatItem {
  const SeatItem({
    required this.code,
    required this.status,
  });

  final String code;
  final SeatStatus status;
}
