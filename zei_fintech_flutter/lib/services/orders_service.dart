import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/store_order.dart';

class OrdersService {
  static const String _defaultUrl = 'https://zeinperfumes.com/api/fintech_orders.php';
  static const String _defaultKey = 'zei_fintech_secret_key_2026';

  static Future<String> _getEndpointUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final customUrl = prefs.getString('api_endpoint_url');
    if (customUrl != null && customUrl.isNotEmpty) {
      return customUrl.replaceAll('fintech_sync.php', 'fintech_orders.php');
    }
    return _defaultUrl;
  }

  static Future<String> _getApiKey() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('api_key') ?? _defaultKey;
  }

  static Future<List<StoreOrder>> fetchOrders({String status = '', String query = ''}) async {
    try {
      final urlStr = await _getEndpointUrl();
      final apiKey = await _getApiKey();

      final uri = Uri.parse(urlStr).replace(queryParameters: {
        if (status.isNotEmpty) 'status': status,
        if (query.isNotEmpty) 'q': query,
      });

      final res = await http.get(uri, headers: {
        'Accept': 'application/json',
        'X-API-KEY': apiKey,
      }).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = json.decode(utf8.decode(res.bodyBytes));
        if (data['success'] == true && data['orders'] is List) {
          return (data['orders'] as List).map((i) => StoreOrder.fromJson(i)).toList();
        }
      }
    } catch (e) {
      // ignore
    }
    return [];
  }

  static Future<bool> updateOrderStatus(int orderId, String newStatus, {String paymentStatus = ''}) async {
    try {
      final urlStr = await _getEndpointUrl();
      final apiKey = await _getApiKey();

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
