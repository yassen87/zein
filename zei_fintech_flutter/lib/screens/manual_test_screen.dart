import 'package:flutter/material.dart';
import '../models/transfer_transaction.dart';
import '../services/sms_parser.dart';
import '../services/api_sync_service.dart';

class ManualTestScreen extends StatefulWidget {
  const ManualTestScreen({super.key});

  @override
  State<ManualTestScreen> createState() => _ManualTestScreenState();
}

class _ManualTestScreenState extends State<ManualTestScreen> {
  final _smsController = TextEditingController();
  final _senderController = TextEditingController();
  TransferTransaction? _parsedTx;
  Map<String, dynamic>? _syncResult;
  bool _isSyncing = false;

  void _parse() {
    final tx = SmsParser.parse(_smsController.text, senderAddress: _senderController.text);
    setState(() {
      _parsedTx = tx;
      _syncResult = null;
    });
  }

  Future<void> _sendToServer() async {
    if (_parsedTx == null) return;
    setState(() => _isSyncing = true);
    final res = await ApiSyncService.syncTransaction(_parsedTx!);
    setState(() {
      _syncResult = res;
      _isSyncing = false;
    });
  }

  void _loadVodafoneSample() {
    final randRef = 'VF${DateTime.now().millisecondsSinceEpoch.toString().substring(5)}';
    _senderController.text = 'VF-Cash';
    _smsController.text = 'تم استلام مبلغ 650.00 جنيه من رقم 01005250838. الرقم المرجعي للعملية هو $randRef.';
    _parse();
  }

  void _loadInstaPaySample() {
    final randRef = 'IPA-${DateTime.now().millisecondsSinceEpoch.toString().substring(5)}';
    _senderController.text = 'InstaPay';
    _smsController.text = 'تم استلام تحويل لحظي بمبلغ 450.00 ج.م من ahmedfayoumy1@instapay. الرقم المرجعي: $randRef.';
    _parse();
  }

  void _loadOrangeSample() {
    final randRef = 'OR-${DateTime.now().millisecondsSinceEpoch.toString().substring(5)}';
    _senderController.text = 'OrangeCash';
    _smsController.text = 'تم استلام مبلغ 300.00 جنيه من محفظة 01223344556. كود العملية: $randRef.';
    _parse();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF111827),
        elevation: 0,
        title: const Text('محاكي واختبار الرسائل', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Sample Presets
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFEF4444),
                    side: const BorderSide(color: Color(0xFFEF4444)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: _loadVodafoneSample,
                  child: const Text('فودافون كاش', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF8B5CF6),
                    side: const BorderSide(color: Color(0xFF8B5CF6)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: _loadInstaPaySample,
                  child: const Text('إنستاباي IPN', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFF97316),
                    side: const BorderSide(color: Color(0xFFF97316)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: _loadOrangeSample,
                  child: const Text('أورانج كاش', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Sender
          const Text('عنوان المرسل (Sender / Shortcode):', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          TextField(
            controller: _senderController,
            style: const TextStyle(color: Colors.white, fontSize: 13),
            decoration: InputDecoration(
              filled: true,
              fillColor: const Color(0xFF111827),
              hintText: 'VF-Cash أو InstaPay',
              hintStyle: const TextStyle(color: Color(0xFF4B5563)),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF1F2937))),
            ),
          ),
          const SizedBox(height: 14),

          // SMS Body
          const Text('نص رسالة التحويل (SMS Body):', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          TextField(
            controller: _smsController,
            maxLines: 4,
            style: const TextStyle(color: Colors.white, fontSize: 13),
            decoration: InputDecoration(
              filled: true,
              fillColor: const Color(0xFF111827),
              hintText: 'الصق نص رسالة التحويل هنا...',
              hintStyle: const TextStyle(color: Color(0xFF4B5563)),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF1F2937))),
            ),
          ),
          const SizedBox(height: 16),

          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: const Color(0xFF111827),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: _parse,
            icon: const Icon(Icons.analytics, size: 18),
            label: const Text('تحليل الرسالة واستخراج البيانات', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
          const SizedBox(height: 20),

          // Result Display
          if (_parsedTx != null) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF111827),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFF10B981).withOpacity(0.4)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('✓ البيانات المستخرجة بنجاح:', style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 14)),
                  const Divider(color: Color(0xFF1F2937), height: 20),
                  _row('الجهة:', _parsedTx!.providerName),
                  _row('المبلغ:', '${_parsedTx!.amount.toStringAsFixed(2)} ج.م'),
                  _row('الرقم المرجعي:', _parsedTx!.referenceId),
                  if (_parsedTx!.sender != null) _row('المرسل:', _parsedTx!.sender!),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF10B981),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: _isSyncing ? null : _sendToServer,
                      icon: const Icon(Icons.cloud_upload, size: 18),
                      label: Text(_isSyncing ? 'جاري الإرسال...' : 'إرسال ومطابقة في المتجر فوراً'),
                    ),
                  ),
                ],
              ),
            ),
          ] else if (_smsController.text.isNotEmpty) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFEF4444).withOpacity(0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text('لم يتم التعرف على نمط رسالة تحويل مالي. تأكد من وجود المبلغ والرقم المرجعي.', style: TextStyle(color: Color(0xFFEF4444), fontSize: 12)),
            ),
          ],

          if (_syncResult != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFF1F2937),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFD4AF37).withOpacity(0.4)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('استجابة السيرفر:', style: TextStyle(color: Color(0xFFD4AF37), fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Text(_syncResult.toString(), style: const TextStyle(color: Colors.white, fontSize: 11, fontFamily: 'monospace')),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 12)),
          Text(value, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
