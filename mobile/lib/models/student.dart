import 'dart:convert';

import 'package:libcontrol_app/models/family_seat_member.dart';
import 'package:libcontrol_app/models/student_payment.dart';

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
    this.planExpiryDate,
    this.daysUntilPlanExpiry,
    this.seatExpiringSoon = false,
    this.bookedOn = '',
    this.amountPaid,
    this.feeAmount,
    this.paymentHistory = const [],
    this.familySeats = const [],
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
  final DateTime? planExpiryDate;
  final int? daysUntilPlanExpiry;
  final bool seatExpiringSoon;
  final String bookedOn;
  final double? amountPaid;
  final double? feeAmount;
  final List<StudentPayment> paymentHistory;
  final List<FamilySeatMember> familySeats;

  factory Student.fromJson(Map<String, dynamic> json) {
    final historyJson = json['payment_history'] as List<dynamic>? ?? [];
    final familyJson = json['family_seats'] as List<dynamic>? ?? [];

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
      planExpiryDate: _parseDate(json['plan_expiry_date'] as String?),
      daysUntilPlanExpiry: (json['days_until_plan_expiry'] as num?)?.toInt(),
      seatExpiringSoon: json['seat_expiring_soon'] as bool? ?? false,
      bookedOn: json['booked_on'] as String? ?? '',
      amountPaid: (json['amount_paid'] as num?)?.toDouble(),
      feeAmount: (json['fee_amount'] as num?)?.toDouble(),
      paymentHistory: historyJson
          .map((item) => StudentPayment.fromJson(item as Map<String, dynamic>))
          .toList(),
      familySeats: familyJson
          .map((item) => FamilySeatMember.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }

  static DateTime? _parseDate(String? raw) {
    if (raw == null || raw.isEmpty) {
      return null;
    }
    return DateTime.tryParse(raw);
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
      'plan_expiry_date': planExpiryDate?.toIso8601String().split('T').first,
      'days_until_plan_expiry': daysUntilPlanExpiry,
      'seat_expiring_soon': seatExpiringSoon,
      'booked_on': bookedOn,
      'amount_paid': amountPaid,
      'fee_amount': feeAmount,
      'is_checked_in': isCheckedIn,
      'checked_in_at': checkedInAt,
      'avatar_url': avatarUrl,
      'payment_history': paymentHistory
          .map(
            (p) => {
              'amount_label': p.amountLabel,
              'payment_date': p.paymentDate,
              'payment_method': p.paymentMethod,
              'reference': p.reference,
            },
          )
          .toList(),
      'family_seats': familySeats
          .map(
            (seat) => {
              'name': seat.name,
              'relationship': seat.relationship,
              'seat_code': seat.seatCode,
              'hall': seat.hall,
              'floor': seat.floor,
              'status': seat.isActive ? 'active' : 'inactive',
              'booked_on': seat.bookedOn,
            },
          )
          .toList(),
    };
  }

  String toJsonString() => jsonEncode(toJson());
}
