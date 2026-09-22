class StudentPayment {
  const StudentPayment({
    required this.amountLabel,
    required this.paymentDate,
    required this.paymentMethod,
    required this.reference,
  });

  final String amountLabel;
  final String paymentDate;
  final String paymentMethod;
  final String reference;

  factory StudentPayment.fromJson(Map<String, dynamic> json) {
    return StudentPayment(
      amountLabel: json['amount_label'] as String? ?? '',
      paymentDate: json['payment_date'] as String? ?? '',
      paymentMethod: json['payment_method'] as String? ?? '',
      reference: json['reference'] as String? ?? '',
    );
  }
}
