class StoreOrder {
  final int id;
  final String orderNumber;
  final String customerName;
  final String? customerPhone;
  final String? customerPhone2;
  final String? shippingAddress;
  final String? city;
  final double total;
  final double subtotal;
  final double shippingCost;
  final String paymentMethod;
  final String paymentStatus;
  final double paidAmount;
  final String? paymentReference;
  final String? paymentReceipt;
  final String? ocrStatus;
  final String status;
  final bool isConfirmed;
  final String? createdAt;
  final String? itemsSummary;

  StoreOrder({
    required this.id,
    required this.orderNumber,
    required this.customerName,
    this.customerPhone,
    this.customerPhone2,
    this.shippingAddress,
    this.city,
    required this.total,
    required this.subtotal,
    required this.shippingCost,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.paidAmount,
    this.paymentReference,
    this.paymentReceipt,
    this.ocrStatus,
    required this.status,
    required this.isConfirmed,
    this.createdAt,
    this.itemsSummary,
  });

  factory StoreOrder.fromJson(Map<String, dynamic> json) {
    return StoreOrder(
      id: int.tryParse(json['id'].toString()) ?? 0,
      orderNumber: json['order_number']?.toString() ?? '',
      customerName: json['customer_name']?.toString() ?? 'عميل',
      customerPhone: json['customer_phone']?.toString(),
      customerPhone2: json['customer_phone_2']?.toString(),
      shippingAddress: json['shipping_address']?.toString(),
      city: json['city']?.toString(),
      total: double.tryParse(json['total'].toString()) ?? 0.0,
      subtotal: double.tryParse(json['subtotal']?.toString() ?? '0') ?? 0.0,
      shippingCost: double.tryParse(json['shipping_cost']?.toString() ?? '0') ?? 0.0,
      paymentMethod: json['payment_method']?.toString() ?? 'cod',
      paymentStatus: json['payment_status']?.toString() ?? 'unpaid',
      paidAmount: double.tryParse(json['paid_amount']?.toString() ?? '0') ?? 0.0,
      paymentReference: json['payment_reference']?.toString(),
      paymentReceipt: json['payment_receipt']?.toString(),
      ocrStatus: json['ocr_status']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      isConfirmed: (json['is_confirmed'] == 1 || json['is_confirmed'] == '1' || json['is_confirmed'] == true),
      createdAt: json['created_at']?.toString(),
      itemsSummary: json['items_summary']?.toString(),
    );
  }

  String get statusLabelArabic {
    switch (status) {
      case 'pending': return 'معلق بانتظار التأكيد';
      case 'processing': return 'قيد التجهيز والتغليف';
      case 'shipped': return 'تم الشحن والتسليم';
      case 'completed': return 'مكتمل بنجاح';
      case 'cancelled': return 'ملغي';
      default: return status;
    }
  }

  String get paymentMethodLabelArabic {
    switch (paymentMethod) {
      case 'vodafone_cash': return 'فودافون كاش';
      case 'instapay': return 'إنستاباي IPN';
      case 'wallet': return 'محفظة إلكترونية';
      case 'cod': return 'دفع عند الاستلام';
      default: return paymentMethod;
    }
  }
}
