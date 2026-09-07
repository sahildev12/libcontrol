class Student {
  const Student({
    required this.name,
    required this.id,
    required this.type,
    required this.email,
    required this.phone,
    required this.homeBranch,
    required this.currentSeat,
    required this.currentHall,
    required this.planValidTill,
    required this.isCheckedIn,
    required this.checkedInAt,
    required this.avatarUrl,
  });

  final String name;
  final String id;
  final String type;
  final String email;
  final String phone;
  final String homeBranch;
  final String currentSeat;
  final String currentHall;
  final String planValidTill;
  final bool isCheckedIn;
  final String checkedInAt;
  final String avatarUrl;
}
