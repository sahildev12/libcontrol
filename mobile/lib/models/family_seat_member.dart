class FamilySeatMember {
  const FamilySeatMember({
    required this.name,
    required this.relationship,
    required this.seatCode,
    required this.hall,
    required this.floor,
    required this.bookedOn,
    required this.isActive,
  });

  final String name;
  final String relationship;
  final String seatCode;
  final String hall;
  final String floor;
  final String bookedOn;
  final bool isActive;

  factory FamilySeatMember.fromJson(Map<String, dynamic> json) {
    return FamilySeatMember(
      name: json['name'] as String? ?? '',
      relationship: json['relationship'] as String? ?? 'Family member',
      seatCode: json['seat_code'] as String? ?? '',
      hall: json['hall'] as String? ?? '',
      floor: json['floor'] as String? ?? '',
      bookedOn: json['booked_on'] as String? ?? '',
      isActive: json['status'] as String? == 'active',
    );
  }
}
