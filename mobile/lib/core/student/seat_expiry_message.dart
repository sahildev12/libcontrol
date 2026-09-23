import 'package:libcontrol_app/models/student.dart';

class SeatExpiryMessage {
  static String? forStudent(Student? student) {
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
}
