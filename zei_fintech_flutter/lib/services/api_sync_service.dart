import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/transfer_transaction.dart';

class ApiSyncService {
  static const String keyServerUrl = 'zei_server_url';
  static const String keyApiKey = 'zei_api_key';
  static const String keyHistory = 'zei_transactions_history';
  static const String keyOfflineQueue = 'zei_offline_queue';

  static const String defaultServerUrl = 'https://zeinperfumes.com/api/fintech_sync.php';
  static const String defaultApiKey = 'zei_fintech_secret_key_2026';

  static Future<String> getServerUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(keyServerUrl) ?? defaultServerUrl;
  }

  static Future<String> getApiKey() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(keyApiKey) ?? defaultApiKey;
  }

  static Future<void> saveSettings(String url, String apiKey) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(keyServerUrl, url.trim());
    await prefs.setString(keyApiKey, apiKey.trim());
  }

  /// Sync a transaction to the server in real time
  static Future<Map<String, dynamic>> syncTransaction(TransferTransaction tx) async {
    final serverUrl = await getServerUrl();
    final apiKey = await getApiKey();

    final payload = {
      'api_key': apiKey,
      'provider': tx.provider,
      'amount': tx.amount,
      'sender': tx.sender,
      'reference_id': tx.referenceId,
      'raw_message': tx.rawMessage,
      'received_at': tx.receivedAt.toIso8601String(),
    };

    try {
      final response = await http.post(
        Uri.parse(serverUrl),
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': apiKey,
        },
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body) as Map<String, dynamic>;
        
        if (result['success'] == true) {
          final isMatched = result['status'] == 'matched';
          tx.syncStatus = isMatched ? 'matched' : 'synced';
          if (result['matched_order_id'] != null) {
            tx.matchedOrderId = int.tryParse(result['matched_order_id'].toString());
          }
          if (result['matched_order_number'] != null) {
            tx.matchedOrderNumber = result['matched_order_number'].toString();
          }
          if (result['matched_customer_name'] != null) {
            tx.matchedCustomerName = result['matched_customer_name'].toString();
          }
        } else {
          tx.syncStatus = 'failed';
        }

        await saveTransactionToHistory(tx);
        return result;
      } else {
        tx.syncStatus = 'failed';
        await saveTransactionToHistory(tx);
        return {'success': false, 'error': 'HTTP ${response.statusCode}'};
      }
    } catch (e) {
      // Network failure, queue offline
      tx.syncStatus = 'queued_offline';
      await saveTransactionToHistory(tx);
      await queueOffline(payload);
      return {'success': false, 'error': e.toString(), 'offline': true};
    }
  }

  /// Check server connectivity
  static Future<bool> testConnection() async {
    final serverUrl = await getServerUrl();
    final apiKey = await getApiKey();

    try {
      final response = await http.get(
        Uri.parse('$serverUrl?test=1'),
        headers: {'X-API-KEY': apiKey},
      ).timeout(const Duration(seconds: 5));
      return response.statusCode == 200 || response.statusCode == 405; // 405 means endpoint exists
    } catch (_) {
      return false;
    }
  }

  /// History Cache
  static Future<List<TransferTransaction>> getHistory() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(keyHistory);
    if (raw == null || raw.isEmpty) return [];

    try {
      final list = jsonDecode(raw) as List;
      return list.map((item) => TransferTransaction.fromJson(item as Map<String, dynamic>)).toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> saveTransactionToHistory(TransferTransaction tx) async {
    final list = await getHistory();
    list.removeWhere((item) => item.id == tx.id || item.referenceId == tx.referenceId);
    list.insert(0, tx);
    if (list.length > 200) list.removeLast();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(keyHistory, jsonEncode(list.map((e) => e.toJson()).toList()));
  }

  static Future<void> queueOffline(Map<String, dynamic> payload) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(keyOfflineQueue);
    final list = raw != null ? (jsonDecode(raw) as List) : [];
    list.add(payload);
    await prefs.setString(keyOfflineQueue, jsonEncode(list));
  }
}
