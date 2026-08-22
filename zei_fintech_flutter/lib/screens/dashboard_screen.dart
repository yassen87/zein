import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/transfer_transaction.dart';
import '../services/api_sync_service.dart';
import '../services/sms_listener_service.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  List<TransferTransaction> _transactions = [];
  bool _isLoading = true;
  bool _isListening = false;
  double _totalAmount = 0.0;
  int _matchedCount = 0;
  int _pendingCount = 0;

  @override
  void initState() {
    super.initState();
    _loadHistory();
    _initListener();
  }

  void _initListener() {
    SmsListenerService.startListening(
      onNewTransaction: (tx) {
        if (mounted) {
          setState(() {
            _transactions.insert(0, tx);
            _calculateMetrics();
          });
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: const Color(0xFF10B981),
              content: Row(
                children: [
                  const Icon(Icons.check_circle, color: Colors.white),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'تحويل جديد: ${tx.amount} ج.م عبر ${tx.providerName}',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
          );
        }
      },
    );
    setState(() {
      _isListening = true;
    });
  }

  Future<void> _loadHistory() async {
    setState(() => _isLoading = true);
    final history = await ApiSyncService.getHistory();
    setState(() {
      _transactions = history;
      _calculateMetrics();
      _isLoading = false;
    });
  }

  void _calculateMetrics() {
    double total = 0.0;
    int matched = 0;
    int pending = 0;

    for (final tx in _transactions) {
      total += tx.amount;
      if (tx.syncStatus == 'matched') {
        matched++;
      } else {
        pending++;
      }
    }

    setState(() {
      _totalAmount = total;
      _matchedCount = matched;
      _pendingCount = pending;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF111827),
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Color(0xFFD4AF37), Color(0xFFAA8420)]),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.account_balance_wallet, color: Color(0xFF111827), size: 20),
            ),
            const SizedBox(width: 10),
            const Text(
              'زين للعطور • قارئ التحويلات',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Color(0xFFD4AF37)),
            onPressed: _loadHistory,
            tooltip: 'تحديث',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFD4AF37)))
          : RefreshIndicator(
              color: const Color(0xFFD4AF37),
              backgroundColor: const Color(0xFF1F2937),
              onRefresh: _loadHistory,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // 1. Live Status Hero Card
                  _buildStatusCard(),
                  const SizedBox(height: 16),

                  // 2. Metrics Grid
                  _buildMetricsGrid(),
                  const SizedBox(height: 20),

                  // 3. Transactions Section Header
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'سجل التحويلات المستلمة',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1F2937),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: const Color(0xFF374151)),
                        ),
                        child: Text(
                          '${_transactions.length} تحويل',
                          style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // 4. Transactions List
                  if (_transactions.isEmpty)
                    _buildEmptyState()
                  else
                    ..._transactions.map((tx) => _buildTransactionCard(tx)),
                ],
              ),
            ),
    );
  }

  Widget _buildStatusCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            const Color(0xFF10B981).withOpacity(0.12),
            const Color(0xFF111827),
          ],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFF10B981).withOpacity(0.2),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.sensors, color: Color(0xFF10B981), size: 24),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _isListening ? 'المستمع نشط ويستقبل الرسائل 🟢' : 'المستمع متوقف 🔴',
                  style: TextStyle(
                    color: _isListening ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'يتم فحص رسائل فودافون كاش وإنستاباي تلقائياً ومطابقتها فوراً مع المتجر.',
                  style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 11, height: 1.4),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMetricsGrid() {
    return Row(
      children: [
        // Total Amount
        Expanded(
          child: _buildMetricCard(
            title: 'إجمالي المحصل',
            value: '${_totalAmount.toStringAsFixed(0)} ج.م',
            icon: Icons.payments,
            color: const Color(0xFFD4AF37),
          ),
        ),
        const SizedBox(width: 12),
        // Matched Orders
        Expanded(
          child: _buildMetricCard(
            title: 'طلبات مطابقة',
            value: '$_matchedCount',
            icon: Icons.check_circle_outline,
            color: const Color(0xFF10B981),
          ),
        ),
        const SizedBox(width: 12),
        // Pending
        Expanded(
          child: _buildMetricCard(
            title: 'بانتظار الإيصال',
            value: '$_pendingCount',
            icon: Icons.hourglass_empty,
            color: const Color(0xFFF59E0B),
          ),
        ),
      ],
    );
  }

  Widget _buildMetricCard({required String title, required String value, required IconData icon, required Color color}) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1F2937)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(title, style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 11, fontWeight: FontWeight.w500)),
              Icon(icon, color: color, size: 16),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(color: color, fontSize: 16, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionCard(TransferTransaction tx) {
    final isMatched = tx.syncStatus == 'matched';
    final isVodafone = tx.provider.contains('vodafone');
    final isInstaPay = tx.provider.contains('instapay');

    Color badgeColor = const Color(0xFFD4AF37);
    if (isVodafone) badgeColor = const Color(0xFFEF4444);
    if (isInstaPay) badgeColor = const Color(0xFF8B5CF6);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isMatched ? const Color(0xFF10B981).withOpacity(0.4) : const Color(0xFF1F2937)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: badgeColor.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: badgeColor.withOpacity(0.3)),
                    ),
                    child: Text(
                      tx.providerName,
                      style: TextStyle(color: badgeColor, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 8),
                  if (isMatched)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withOpacity(0.15),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
                      ),
                      child: Text(
                        'طلب #${tx.matchedOrderNumber ?? tx.matchedOrderId}',
                        style: const TextStyle(color: Color(0xFF10B981), fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ),
                ],
              ),
              Text(
                '${tx.amount.toStringAsFixed(2)} ج.م',
                style: const TextStyle(color: Color(0xFF10B981), fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Text('المرجعي: ', style: TextStyle(color: Color(0xFF6B7280), fontSize: 12)),
                  Text(
                    tx.referenceId,
                    style: const TextStyle(color: Color(0xFFD4AF37), fontFamily: 'monospace', fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
              Text(
                DateFormat('hh:mm a').format(tx.receivedAt),
                style: const TextStyle(color: Color(0xFF6B7280), fontSize: 11),
              ),
            ],
          ),
          if (tx.sender != null && tx.sender!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'المرسل: ${tx.sender}',
              style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 11),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1F2937)),
      ),
      child: const Column(
        children: [
          Icon(Icons.inbox, size: 48, color: Color(0xFF4B5563)),
          SizedBox(height: 12),
          Text(
            'لا توجد تحويلات مسجلة بعد',
            style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
          ),
          SizedBox(height: 6),
          Text(
            'عند وصول رسالة تحويل من فودافون كاش أو إنستاباي ستظهر هنا وتتم المزامنة تلقائياً.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 12),
          ),
        ],
      ),
    );
  }
}
