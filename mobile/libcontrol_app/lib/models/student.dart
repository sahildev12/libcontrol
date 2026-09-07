import 'dart:convert';

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

  factory Student.fromJson(Map<String, dynamic> json) {
    return Student(
      name: json['name'] as String? ?? '',
      id: json['student_code'] as String? ?? '',
      type: json['type'] as String? ?? 'Student',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      homeBranch: json['home_branch'] as String? ?? '',
      currentSeat: json['current_seat'] as String? ?? '',
      currentHall: json['current_hall'] as String? ?? '',
      planValidTill: json['plan_valid_till'] as String? ?? '',
      isCheckedIn: json['is_checked_in'] as bool? ?? false,
      checkedInAt: json['checked_in_at'] as String? ?? '',
      avatarUrl: json['avatar_url'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'student_code': id,
      'type': type,
      'email': email,
      'phone': phone,
      'home_branch': homeBranch,
      'current_seat': currentSeat,
      'current_hall': currentHall,
      'plan_valid_till': planValidTill,
      'is_checked_in': isCheckedIn,
      'checked_in_at': checkedInAt,
      'avatar_url': avatarUrl,
    };
  }

  String toJsonString() => jsonEncode(toJson());
}
