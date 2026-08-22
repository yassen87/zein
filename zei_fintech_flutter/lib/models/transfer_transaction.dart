class TransferTransaction {
  final String id;
  final String provider; // vodafone_cash, instapay, orange_cash, etisalat_cash, we_pay, bank
  final String providerName;
  final double amount;
  final String? sender;
  final String referenceId;
  final String rawMessage;
  final DateTime receivedAt;
  String syncStatus; // synced, matched, queued_offline, failed
  int? matchedOrderId;
  String? matchedOrderNumber;
  String? matchedCustomerName;

  TransferTransaction({
    required this.id,
    required this.provider,
    required this.providerName,
    required this.amount,
    this.sender,
    required this.referenceId,
    required this.rawMessage,
    required this.receivedAt,
    this.syncStatus = 'synced',
    this.matchedOrderId,
    this.matchedOrderNumber,
    this.matchedCustomerName,
  });

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'provider': provider,
      'providerName': providerName,
      'amount': amount,
      'sender': sender,
      'reference_id': referenceId,
      'raw_message': rawMessage,
      'received_at': receivedAt.toIso8601String(),
      'sync_status': syncStatus,
      'matched_order_id': matchedOrderId,
      'matched_order_number': matchedOrderNumber,
      'matched_customer_name': matchedCustomerName,
    };
  }

  factory TransferTransaction.fromJson(Map<String, dynamic> json) {
    return TransferTransaction(
      id: json['id']?.toString() ?? DateTime.now().millisecondsSinceEpoch.toString(),
      provider: json['provider']?.toString() ?? 'unknown',
      providerName: json['providerName']?.toString() ?? 'تحويل إلكتروني',
      amount: (json['amount'] is num) ? (json['amount'] as num).toDouble() : double.tryParse(json['amount']?.toString() ?? '0') ?? 0.0,
      sender: json['sender']?.toString(),
      referenceId: json['reference_id']?.toString() ?? json['referenceId']?.toString() ?? 'UNKNOWN',
      rawMessage: json['raw_message']?.toString() ?? json['rawMessage']?.toString() ?? '',
      receivedAt: json['received_at'] != null ? DateTime.tryParse(json['received_at'].toString()) ?? DateTime.now() : DateTime.now(),
      syncStatus: json['sync_status']?.toString() ?? json['syncStatus']?.toString() ?? 'synced',
      matchedOrderId: json['matched_order_id'] is int ? json['matched_order_id'] : int.tryParse(json['matched_order_id']?.toString() ?? ''),
      matchedOrderNumber: json['matched_order_number']?.toString(),
      matchedCustomerName: json['matched_customer_name']?.toString(),
    );
  }
}
