import 'package:flutter/material.dart';
import '../models/store_order.dart';
import '../services/orders_service.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<StoreOrder> _orders = [];
  bool _isLoading = true;
  String _selectedStatus = '';
  String _searchQuery = '';
  String? _errorMessage;
  String _endpointUsed = '';
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    final result = await OrdersService.fetchOrdersDetailed(
      status: _selectedStatus,
      query: _searchQuery,
    );
    if (mounted) {
      setState(() {
        _orders = result.orders;
        _errorMessage = result.errorMessage;
        _endpointUsed = result.endpointUsed;
        _isLoading = false;
      });
    }
  }

  Future<void> _updateStatus(StoreOrder order, String newStatus, {String paymentStatus = ''}) async {
    final success = await OrdersService.updateOrderStatus(order.id, newStatus, paymentStatus: paymentStatus);
    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('تم تحديث حالة الطلب #${order.orderNumber} بنجاح!'),
          backgroundColor: const Color(0xFF10B981),
        ),
      );
      _loadOrders();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF111827),
        elevation: 0,
        title: const Text('إدارة ومتابعة الطلبات', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Color(0xFFD4AF37)),
            onPressed: _loadOrders,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search & Filter Bar
          Container(
            padding: const EdgeInsets.all(12),
            color: const Color(0xFF111827),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: const Color(0xFF1F2937),
                    hintText: 'ابحث برقم الطلب، اسم العميل، أو الهاتف...',
                    hintStyle: const TextStyle(color: Color(0xFF6B7280), fontSize: 12),
                    prefixIcon: const Icon(Icons.search, color: Color(0xFF9CA3AF), size: 20),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, color: Colors.white70, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _searchQuery = '');
                              _loadOrders();
                            },
                          )
                        : null,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                    contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 12),
                  ),
                  onSubmitted: (v) {
                    setState(() => _searchQuery = v.trim());
                    _loadOrders();
                  },
                ),
                const SizedBox(height: 10),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _filterChip('', 'الكل'),
                      _filterChip('pending', 'المعلقة ⏳'),
                      _filterChip('processing', 'قيد التجهيز 🧴'),
                      _filterChip('shipped', 'تم الشحن 🚚'),
                      _filterChip('completed', 'المكتملة ✅'),
                      _filterChip('cancelled', 'الملغية ❌'),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Orders List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFFD4AF37)))
                : _orders.isEmpty
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                _errorMessage != null ? Icons.wifi_off : Icons.inbox_outlined,
                                size: 50,
                                color: _errorMessage != null ? const Color(0xFFEF4444) : const Color(0xFF4B5563),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                _errorMessage != null ? 'تعذر جلب الطلبات من السيرفر' : 'لا توجد طلبات مسجلة بهذه الفئة',
                                style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                                textAlign: TextAlign.center,
                              ),
                              if (_errorMessage != null) ...[
                                const SizedBox(height: 8),
                                Container(
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF3B1D1D),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: const Color(0xFFEF4444).withOpacity(0.3)),
                                  ),
                                  child: Text(
                                    _errorMessage!,
                                    style: const TextStyle(color: Color(0xFFFCA5A5), fontSize: 11),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 16),
                              ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFD4AF37),
                                  foregroundColor: const Color(0xFF111827),
                                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: _loadOrders,
                                icon: const Icon(Icons.refresh, size: 16),
                                label: const Text('إعادة المحاولة والتحديث', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                              ),
                            ],
                          ),
                        ),
                      )
                    : RefreshIndicator(
                        color: const Color(0xFFD4AF37),
                        backgroundColor: const Color(0xFF111827),
                        onRefresh: _loadOrders,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(12),
                          itemCount: _orders.length,
                          itemBuilder: (ctx, i) => _buildOrderCard(_orders[i]),
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _filterChip(String status, String label) {
    final isSelected = _selectedStatus == status;
    return Padding(
      padding: const EdgeInsets.only(left: 6),
      child: ChoiceChip(
        label: Text(label, style: TextStyle(fontSize: 11, color: isSelected ? Colors.black : Colors.white70, fontWeight: FontWeight.bold)),
        selected: isSelected,
        selectedColor: const Color(0xFFD4AF37),
        backgroundColor: const Color(0xFF1F2937),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        onSelected: (val) {
          setState(() => _selectedStatus = status);
          _loadOrders();
        },
      ),
    );
  }

  Widget _buildOrderCard(StoreOrder order) {
    Color statusColor = const Color(0xFFF59E0B);
    if (order.status == 'processing') statusColor = const Color(0xFF38BDF8);
    if (order.status == 'shipped') statusColor = const Color(0xFFA855F7);
    if (order.status == 'completed') statusColor = const Color(0xFF10B981);
    if (order.status == 'cancelled') statusColor = const Color(0xFFEF4444);

    final isPaid = order.paymentStatus == 'paid' || order.isConfirmed;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1F2937)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Order Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Text(
                    '#${order.orderNumber}',
                    style: const TextStyle(color: Color(0xFFD4AF37), fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: statusColor.withOpacity(0.4)),
                    ),
                    child: Text(
                      order.statusLabelArabic,
                      style: TextStyle(color: statusColor, fontSize: 10, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              Text(
                '${order.total.toStringAsFixed(2)} ج.م',
                style: const TextStyle(color: Color(0xFFFBBF24), fontWeight: FontWeight.w900, fontSize: 14),
              ),
            ],
          ),
          const Divider(color: Color(0xFF1F2937), height: 16),

          // Customer Info
          Row(
            children: [
              const Icon(Icons.person, size: 14, color: Color(0xFF9CA3AF)),
              const SizedBox(width: 6),
              Text(order.customerName, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600)),
              if (order.customerPhone != null) ...[
                const Spacer(),
                const Icon(Icons.phone, size: 14, color: Color(0xFF10B981)),
                const SizedBox(width: 4),
                Text(order.customerPhone!, style: const TextStyle(color: Color(0xFF10B981), fontSize: 12, fontWeight: FontWeight.bold)),
              ],
            ],
          ),

          if (order.shippingAddress != null) ...[
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.location_on, size: 14, color: Color(0xFF9CA3AF)),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    '${order.city ?? ''} - ${order.shippingAddress}',
                    style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 11),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],

          if (order.itemsSummary != null && order.itemsSummary!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              '📦 ${order.itemsSummary!}',
              style: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 11),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],

          const SizedBox(height: 8),
          // Payment Info Badges
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFF1F2937),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  'طريقة الدفع: ${order.paymentMethodLabelArabic}',
                  style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 10),
                ),
              ),
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isPaid ? const Color(0xFF10B981).withOpacity(0.15) : const Color(0xFFEF4444).withOpacity(0.15),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  isPaid ? '✓ تم الدفع' : '⏳ بانتظار الدفع',
                  style: TextStyle(color: isPaid ? const Color(0xFF10B981) : const Color(0xFFEF4444), fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),
          // Action Buttons Bar
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                if (!isPaid)
                  _actionBtn('تأكيد الدفع والطلب ⚡️', const Color(0xFF10B981), () {
                    _updateStatus(order, 'processing', paymentStatus: 'paid');
                  }),
                if (order.status == 'pending')
                  _actionBtn('بدء التجهيز 🧴', const Color(0xFF38BDF8), () {
                    _updateStatus(order, 'processing');
                  }),
                if (order.status == 'processing')
                  _actionBtn('تم الشحن 🚚', const Color(0xFFA855F7), () {
                    _updateStatus(order, 'shipped');
                  }),
                if (order.status == 'shipped')
                  _actionBtn('اكتمل بنجاح ✅', const Color(0xFF10B981), () {
                    _updateStatus(order, 'completed');
                  }),
                if (order.status != 'cancelled' && order.status != 'completed')
                  _actionBtn('إلغاء ❌', const Color(0xFFEF4444), () {
                    _updateStatus(order, 'cancelled');
                  }),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _actionBtn(String label, Color color, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(left: 6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(6),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: color.withOpacity(0.12),
            border: Border.all(color: color.withOpacity(0.4)),
            borderRadius: BorderRadius.circular(6),
          ),
          child: Text(label, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }
}
