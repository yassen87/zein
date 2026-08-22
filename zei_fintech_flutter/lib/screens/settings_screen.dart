import 'package:flutter/material.dart';
import '../services/api_sync_service.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _urlController = TextEditingController();
  final _keyController = TextEditingController();
  bool _isLoading = true;
  bool _isSaving = false;
  String? _testResult;
  bool? _testSuccess;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    final url = await ApiSyncService.getServerUrl();
    final key = await ApiSyncService.getApiKey();
    setState(() {
      _urlController.text = url;
      _keyController.text = key;
      _isLoading = false;
    });
  }

  Future<void> _save() async {
    setState(() => _isSaving = true);
    await ApiSyncService.saveSettings(_urlController.text, _keyController.text);
    setState(() => _isSaving = false);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: Color(0xFF10B981),
          content: Text('تم حفظ الإعدادات بنجاح!'),
        ),
      );
    }
  }

  Future<void> _test() async {
    setState(() {
      _testResult = 'جاري اختبار الاتصال بالسيرفر...';
      _testSuccess = null;
    });

    final success = await ApiSyncService.testConnection();
    setState(() {
      _testSuccess = success;
      _testResult = success ? 'الاتصال ناجح بالسيرفر ومستعد لاستقبال التحويلات! ✓' : 'فشل الاتصال: يرجى التحقق من الرابط والإنترنت ✗';
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF111827),
        elevation: 0,
        title: const Text('إعدادات الربط مع المتجر', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFD4AF37)))
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Info Banner
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1F2937),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFF374151)),
                  ),
                  child: const Text(
                    'هذه الإعدادات تربط تطبيق قارئ الرسائل مع متجر زين للعطور (zeinperfumes.com) لإرسال التحويلات وتأكيد الطلبات تلقائياً.',
                    style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 12, height: 1.5),
                  ),
                ),
                const SizedBox(height: 20),

                // Server URL
                const Text('رابط السيرفر (Server API Endpoint):', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                TextField(
                  controller: _urlController,
                  style: const TextStyle(color: Colors.white, fontSize: 13, fontFamily: 'monospace'),
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: const Color(0xFF111827),
                    hintText: 'https://zeinperfumes.com/api/fintech_sync.php',
                    hintStyle: const TextStyle(color: Color(0xFF4B5563)),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF374151))),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF1F2937))),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFD4AF37))),
                  ),
                ),
                const SizedBox(height: 16),

                // API Key
                const Text('مفتاح الأمان (API Secret Key):', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                TextField(
                  controller: _keyController,
                  obscureText: true,
                  style: const TextStyle(color: Colors.white, fontSize: 13, fontFamily: 'monospace'),
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: const Color(0xFF111827),
                    hintText: 'zei_fintech_secret_key_2026',
                    hintStyle: const TextStyle(color: Color(0xFF4B5563)),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF374151))),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF1F2937))),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFD4AF37))),
                  ),
                ),
                const SizedBox(height: 24),

                // Buttons
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFFD4AF37),
                          foregroundColor: const Color(0xFF111827),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: _isSaving ? null : _save,
                        icon: const Icon(Icons.save, size: 18),
                        label: const Text('حفظ الإعدادات', style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(width: 12),
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1F2937),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: _test,
                      icon: const Icon(Icons.wifi_tethering, size: 18),
                      label: const Text('اختبار الاتصال'),
                    ),
                  ],
                ),

                if (_testResult != null) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: (_testSuccess == true ? const Color(0xFF10B981) : const Color(0xFFEF4444)).withOpacity(0.15),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: (_testSuccess == true ? const Color(0xFF10B981) : const Color(0xFFEF4444)).withOpacity(0.4)),
                    ),
                    child: Text(
                      _testResult!,
                      style: TextStyle(
                        color: _testSuccess == true ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ],
            ),
    );
  }
}
