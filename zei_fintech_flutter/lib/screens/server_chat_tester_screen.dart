import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import '../services/orders_service.dart';
import '../services/api_sync_service.dart';
import '../services/sms_parser.dart';

class ChatTestMessage {
  final String text;
  final bool isUser;
  final DateTime timestamp;
  final int? statusCode;
  final int? latencyMs;
  final Map<String, dynamic>? responseData;
  final bool isError;

  ChatTestMessage({
    required this.text,
    required this.isUser,
    required this.timestamp,
    this.statusCode,
    this.latencyMs,
    this.responseData,
    this.isError = false,
  });
}

class ServerChatTesterScreen extends StatefulWidget {
  const ServerChatTesterScreen({super.key});

  @override
  State<ServerChatTesterScreen> createState() => _ServerChatTesterScreenState();
}

class _ServerChatTesterScreenState extends State<ServerChatTesterScreen> {
  final List<ChatTestMessage> _messages = [];
  final _inputController = TextEditingController();
  final _scrollController = ScrollController();
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _addInitialGreeting();
  }

  void _addInitialGreeting() {
    _messages.add(
      ChatTestMessage(
        text: '👋 مرحباً بك في محاكي الشات واختبار الاتصال المباشر مع سيرفر المتجر!\nيمكنك إرسال أي رسالة أو تجربة تحويل لاختبار سرعة استجابة السيرفر وتأكيد الطلبات فوراً.',
        isUser: false,
        timestamp: DateTime.now(),
      ),
    );
  }

  Future<void> _sendMessage(String text, {String? senderAddress}) async {
    final queryText = text.trim();
    if (queryText.isEmpty) return;

    final userMsg = ChatTestMessage(
      text: queryText,
      isUser: true,
      timestamp: DateTime.now(),
    );

    setState(() {
      _messages.add(userMsg);
      _isSending = true;
    });
    _inputController.clear();
    _scrollToBottom();

    final stopwatch = Stopwatch()..start();

    try {
      final endpoint = await OrdersService.getEndpointUrl();
      final apiKey = await OrdersService.getApiKey();

      // Check if it's an SMS transfer simulation
      final parsedTx = SmsParser.parse(queryText, senderAddress: senderAddress ?? 'VF-Cash');

      if (parsedTx != null) {
        // Send SMS transfer to fintech_sync.php
        final syncRes = await ApiSyncService.syncTransaction(parsedTx);
        stopwatch.stop();

        final isSuccess = syncRes != null && syncRes['success'] == true;
        final matchedOrder = syncRes?['reconciliation']?['order_id'];

        String replyText = isSuccess
            ? '✅ تم استلام الرسالة ومعالجتها في السيرفر بنجاح!'
            : '⚠️ تم إرسال الرسالة ولكن السيرفر أعاد استجابة غير مكتملة.';

        if (matchedOrder != null) {
          replyText += '\n🎯 تم مطابقة التحويل مع الطلب رقم #$matchedOrder وتأكيده آلياً!';
        }

        _addServerReply(
          replyText,
          statusCode: 200,
          latencyMs: stopwatch.elapsedMilliseconds,
          responseData: syncRes,
          isError: !isSuccess,
        );
      } else if (queryText.toLowerCase().contains('ping') || queryText.contains('اختبار') || queryText.contains('تيست')) {
        // Ping test
        final uri = Uri.parse(endpoint).replace(queryParameters: {'api_key': apiKey, 'limit': '5'});
        final res = await http.get(uri, headers: {'X-API-KEY': apiKey}).timeout(const Duration(seconds: 10));
        stopwatch.stop();

        final data = json.decode(utf8.decode(res.bodyBytes));
        final isSuccess = res.statusCode == 200 && data['success'] == true;

        String reply = isSuccess
            ? '🟢 اتصال السيرفر ممتاز (200 OK)!\n📡 السيرفر: zeinperfumes.com\n📦 إجمالي الطلبات بالمتجر: ${data['counts']?['total_orders'] ?? '—'}\n⏳ المعلقة: ${data['counts']?['pending_count'] ?? '—'}'
            : '🔴 استجابة غير متوقعة (${res.statusCode}): ${res.body}';

        _addServerReply(
          reply,
          statusCode: res.statusCode,
          latencyMs: stopwatch.elapsedMilliseconds,
          responseData: data is Map<String, dynamic> ? data : null,
          isError: !isSuccess,
        );
      } else {
        // General query to orders API
        final result = await OrdersService.fetchOrdersDetailed(query: queryText);
        stopwatch.stop();

        String reply = result.success
            ? '📦 نتائج البحث في السيرفر:\nتم العثور على ${result.orders.length} طلب مطابق.'
            : '⚠️ خطأ: ${result.errorMessage ?? 'تعذر جلب البيانات'}';

        _addServerReply(
          reply,
          statusCode: result.statusCode,
          latencyMs: stopwatch.elapsedMilliseconds,
          isError: !result.success,
        );
      }
    } catch (e) {
      stopwatch.stop();
      _addServerReply(
        '❌ فشل الاتصال بالسيرفر: $e',
        statusCode: 0,
        latencyMs: stopwatch.elapsedMilliseconds,
        isError: true,
      );
    } finally {
      if (mounted) {
        setState(() => _isSending = false);
        _scrollToBottom();
      }
    }
  }

  void _addServerReply(String text, {int? statusCode, int? latencyMs, Map<String, dynamic>? responseData, bool isError = false}) {
    if (!mounted) return;
    setState(() {
      _messages.add(
        ChatTestMessage(
          text: text,
          isUser: false,
          timestamp: DateTime.now(),
          statusCode: statusCode,
          latencyMs: latencyMs,
          responseData: responseData,
          isError: isError,
        ),
      );
    });
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _sendVodafoneTest() {
    final randRef = 'VF${DateTime.now().millisecondsSinceEpoch.toString().substring(6)}';
    final msg = 'تم استلام مبلغ 650.00 جنيه من رقم 01005250838. الرقم المرجعي للعملية هو $randRef.';
    _sendMessage(msg, senderAddress: 'VF-Cash');
  }

  void _sendInstaPayTest() {
    final randRef = 'IPA-${DateTime.now().millisecondsSinceEpoch.toString().substring(6)}';
    final msg = 'تم استلام تحويل لحظي بمبلغ 450.00 ج.م من ahmedfayoumy1@instapay. الرقم المرجعي: $randRef.';
    _sendMessage(msg, senderAddress: 'InstaPay');
  }

  @override
  Widget build(BuildContext context) {
    final timeFormat = DateFormat('hh:mm a');

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF111827),
        elevation: 0,
        title: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: const LinearGradient(colors: [Color(0xFFD4AF37), Color(0xFF10B981)]),
              ),
              child: const Icon(Icons.chat_bubble_outline, color: Color(0xFF111827), size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Text('محاكي الشات واختبار السيرفر', style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold)),
                Text('متصل بـ zeinperfumes.com 🟢', style: TextStyle(color: Color(0xFF10B981), fontSize: 11)),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.delete_sweep, color: Color(0xFF9CA3AF)),
            onPressed: () {
              setState(() {
                _messages.clear();
                _addInitialGreeting();
              });
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Quick Action Presets Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            color: const Color(0xFF111827),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _presetBtn('⚡️ Ping اختبار اتصال', const Color(0xFFD4AF37), () => _sendMessage('ping')),
                  _presetBtn('🔴 فودافون كاش وهمي', const Color(0xFFEF4444), _sendVodafoneTest),
                  _presetBtn('🟣 إنستاباي وهمي', const Color(0xFF8B5CF6), _sendInstaPayTest),
                  _presetBtn('🛍️ جلب الطلبات', const Color(0xFF38BDF8), () => _sendMessage('orders')),
                ],
              ),
            ),
          ),

          // Messages List
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(14),
              itemCount: _messages.length,
              itemBuilder: (ctx, i) {
                final msg = _messages[i];
                return _buildChatBubble(msg, timeFormat);
              },
            ),
          ),

          if (_isSending)
            Container(
              padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 16),
              alignment: Alignment.centerRight,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: const [
                  SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFD4AF37))),
                  SizedBox(width: 8),
                  Text('السيرفر يعالج الرسالة...', style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 11)),
                ],
              ),
            ),

          // Input Bar
          Container(
            padding: const EdgeInsets.all(10),
            decoration: const BoxDecoration(
              color: Color(0xFF111827),
              border: Border(top: BorderSide(color: Color(0xFF1F2937))),
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _inputController,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFF1F2937),
                      hintText: 'اكتب نص رسالة أو تحويل هنا للتجربة...',
                      hintStyle: const TextStyle(color: Color(0xFF6B7280), fontSize: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    ),
                    onSubmitted: (v) => _sendMessage(v),
                  ),
                ),
                const SizedBox(width: 8),
                CircleAvatar(
                  backgroundColor: const Color(0xFFD4AF37),
                  radius: 22,
                  child: IconButton(
                    icon: const Icon(Icons.send, color: Color(0xFF111827), size: 18),
                    onPressed: () => _sendMessage(_inputController.text),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _presetBtn(String label, Color color, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(left: 6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: color.withOpacity(0.12),
            border: Border.all(color: color.withOpacity(0.4)),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(label, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }

  Widget _buildChatBubble(ChatTestMessage msg, DateFormat timeFormat) {
    final isUser = msg.isUser;
    return Align(
      alignment: isUser ? Alignment.centerLeft : Alignment.centerRight,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isUser
              ? const Color(0xFF1E3A5F)
              : msg.isError
                  ? const Color(0xFF3B1D1D)
                  : const Color(0xFF16251E),
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isUser ? 2 : 16),
            bottomRight: Radius.circular(isUser ? 16 : 2),
          ),
          border: Border.all(
            color: isUser
                ? const Color(0xFF38BDF8).withOpacity(0.3)
                : msg.isError
                    ? const Color(0xFFEF4444).withOpacity(0.4)
                    : const Color(0xFF10B981).withOpacity(0.3),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              msg.text,
              style: TextStyle(
                color: isUser ? Colors.white : (msg.isError ? const Color(0xFFFCA5A5) : const Color(0xFFE2E8F0)),
                fontSize: 13,
                height: 1.4,
              ),
            ),
            if (msg.responseData != null) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.black26,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  json.encode(msg.responseData),
                  style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10, fontFamily: 'monospace'),
                ),
              ),
            ],
            const SizedBox(height: 6),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (msg.latencyMs != null) ...[
                  Text(
                    '⚡ ${msg.latencyMs} ms',
                    style: const TextStyle(color: Color(0xFFD4AF37), fontSize: 10, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(width: 8),
                ],
                if (msg.statusCode != null && msg.statusCode! > 0) ...[
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                    decoration: BoxDecoration(
                      color: msg.statusCode == 200 ? const Color(0xFF10B981).withOpacity(0.2) : const Color(0xFFEF4444).withOpacity(0.2),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      '${msg.statusCode}',
                      style: TextStyle(color: msg.statusCode == 200 ? const Color(0xFF10B981) : const Color(0xFFEF4444), fontSize: 9, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 8),
                ],
                Text(
                  timeFormat.format(msg.timestamp),
                  style: const TextStyle(color: Color(0xFF6B7280), fontSize: 10),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
