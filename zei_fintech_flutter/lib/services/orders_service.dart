import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/store_order.dart';

class OrdersFetchResult {
  final bool success;
  final List<StoreOrder> orders;
  final String? errorMessage;
  final int statusCode;
  final String endpointUsed;

  OrdersFetchResult({
    required this.success,
    required this.orders,
    this.errorMessage,
    required this.statusCode,
    required this.endpointUsed,
  });
}

class OrdersService {
  static const String _defaultUrl = 'https://zeinperfumes.com/api/fintech_orders.php';
  static const String _defaultKey = 'zei_fintech_secret_key_2026';

  static String? lastError;
  static int lastStatusCode = 0;

  static Future<String> getEndpointUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final customUrl = prefs.getString('api_endpoint_url');
    if (customUrl != null && customUrl.isNotEmpty) {
      return customUrl.replaceAll('fintech_sync.php', 'fintech_orders.php');
    }
    return _defaultUrl;
  }

  static Future<String> getApiKey() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('api_key') ?? _defaultKey;
  }

  static Future<OrdersFetchResult> fetchOrdersDetailed({String status = '', String query = ''}) async {
    final urlStr = await getEndpointUrl();
    final apiKey = await getApiKey();

    try {
      final uri = Uri.parse(urlStr).replace(queryParameters: {
        if (status.isNotEmpty) 'status': status,
        if (query.isNotEmpty) 'q': query,
        'api_key': apiKey,
      });

      final res = await http.get(uri, headers: {
        'Accept': 'application/json',
        'X-API-KEY': apiKey,
      }).timeout(const Duration(seconds: 12));

      lastStatusCode = res.statusCode;

      if (res.statusCode == 200) {
        final data = json.decode(utf8.decode(res.bodyBytes));
        if (data['success'] == true && data['orders'] is List) {
          final orders = (data['orders'] as List).map((i) => StoreOrder.fromJson(i)).toList();
          lastError = null;
          return OrdersFetchResult(
            success: true,
            orders: orders,
            statusCode: 200,
            endpointUsed: urlStr,
          );
        } else {
          lastError = data['error']?.toString() ?? 'استجابة غير متوقعة من السيرفر';
          return OrdersFetchResult(
            success: false,
            orders: [],
            errorMessage: lastError,
            statusCode: 200,
            endpointUsed: urlStr,
          );
        }
      } else {
        lastError = 'خطأ من السيرفر (${res.statusCode}): ${res.body.length > 80 ? res.body.substring(0, 80) : res.body}';
        return OrdersFetchResult(
          success: false,
          orders: [],
          errorMessage: lastError,
          statusCode: res.statusCode,
          endpointUsed: urlStr,
        );
      }
    } catch (e) {
      lastError = 'تعذر الاتصال بالسيرفر: $e';
      return OrdersFetchResult(
        success: false,
        orders: [],
        errorMessage: lastError,
        statusCode: 0,
        endpointUsed: urlStr,
      );
    }
  }

  static Future<List<StoreOrder>> fetchOrders({String status = '', String query = ''}) async {
    final res = await fetchOrdersDetailed(status: status, query: query);
    return res.orders;
  }

  static Future<bool> updateOrderStatus(int orderId, String newStatus, {String paymentStatus = ''}) async {
    try {
      final urlStr = await getEndpointUrl();
      final apiKey = await getApiKey();

      final res = await http.post(
        Uri.parse(urlStr),
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          'X-API-KEY': apiKey,
        },
        body: json.encode({
          'order_id': orderId,
          'status': newStatus,
          if (paymentStatus.isNotEmpty) 'payment_status': paymentStatus,
        }),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = json.decode(utf8.decode(res.bodyBytes));
        return data['success'] == true;
      }
    } catch (e) {
      // ignore
    }
    return false;
  }
}
