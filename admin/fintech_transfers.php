<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
admin_require_permission('orders');

$page_title = 'إدارة التحويلات البنكية والمطابقة الذكية (Fintech & OCR Live)';
require_once __DIR__ . '/_layout_start.php';

$pdo = medal_pdo();
?>

<div class="admin-fintech-container">
    <!-- Header Banner -->
    <div class="fintech-header-card">
        <div class="fintech-header-content">
            <div class="fintech-badge">
                <span class="pulse-dot"></span>
                <span>نظام المطابقة البنكية والذكاء الاصطناعي لايف (AI OCR & Bank Sync)</span>
            </div>
            <h1 class="fintech-title">رادار التحويلات البنكية والمحافظ الإلكترونية</h1>
            <p class="fintech-subtitle">
                متابعة لحظية لرسائل فودافون كاش، إنستاباي، والمحافظ، مع مطابقة إيصالات العملاء المرفوعة آلياً عبر الـ OCR وتأكيد الطلبات فوراً.
            </p>
        </div>
        <div class="fintech-header-actions">
            <button type="button" class="btn-fintech-test" onclick="openTestModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                <span>⚡️ تجربة تحويل وهمي (بدون فلوس)</span>
            </button>
            <button type="button" class="btn-fintech-refresh" onclick="fetchLiveTransfers(true)">
                <svg id="refreshIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                <span>تحديث فوري</span>
            </button>
        </div>
    </div>

    <!-- Live Metrics Grid -->
    <div class="fintech-metrics-grid">
        <div class="fintech-metric-card metric-gold">
            <div class="metric-icon">💰</div>
            <div class="metric-info">
                <span class="metric-label">إجمالي التحويلات المحصلة</span>
                <h3 class="metric-val" id="statTotalAmount">0.00 ج.م</h3>
            </div>
        </div>
        <div class="fintech-metric-card metric-green">
            <div class="metric-icon">✅</div>
            <div class="metric-info">
                <span class="metric-label">طلبات تم تأكيدها آلياً</span>
                <h3 class="metric-val" id="statMatchedCount">0</h3>
            </div>
        </div>
        <div class="fintech-metric-card metric-blue">
            <div class="metric-icon">📲</div>
            <div class="metric-info">
                <span class="metric-label">إجمالي الرسائل الواردة</span>
                <h3 class="metric-val" id="statTotalCount">0</h3>
            </div>
        </div>
        <div class="fintech-metric-card metric-orange">
            <div class="metric-icon">⏳</div>
            <div class="metric-info">
                <span class="metric-label">تحويلات بانتظار المطابقة</span>
                <h3 class="metric-val" id="statUnmatchedCount">0</h3>
            </div>
        </div>
    </div>

    <!-- Live Transactions Stream Table -->
    <div class="fintech-stream-card">
        <div class="stream-header">
            <div class="stream-title-group">
                <span class="stream-icon">📡</span>
                <h2 class="stream-title">سجل العمليات والرسائل الواردة المباشرة</h2>
            </div>
            <div class="stream-status-pill">
                <span class="live-blink"></span>
                <span>المزامنة المباشرة نشطة (كل 5 ثوانٍ)</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="fintech-table">
                <thead>
                    <tr>
                        <th>المصدر / المحفظة</th>
                        <th>المبلغ</th>
                        <th>الرقم المرجعي</th>
                        <th>المُرسل</th>
                        <th>الطلب المرتبط</th>
                        <th>إيصال العميل المرفوع</th>
                        <th>الحالة</th>
                        <th>التاريخ / الوقت</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="transfersTableBody">
                    <tr>
                        <td colspan="9" class="loading-td">
                            <div class="spinner"></div>
                            <span>جاري جلب التحويلات المباشرة...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Test Transfer Simulator (محاكي التحويلات التجريبية بدون فلوس) -->
<div class="fintech-modal-backdrop" id="testModal" style="display:none;">
    <div class="fintech-modal-card">
        <div class="modal-header">
            <div class="modal-title-wrap">
                <span class="modal-badge-icon">⚡️</span>
                <h3>محاكاة استلام تحويل تجريبي (بدون خصم فلوس)</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeTestModal()">&times;</button>
        </div>
        <p class="modal-desc">
            أنشئ رسالة تحويل تجريبية وهمية للتأكد من وصول الإشعار في الأدمن واختبار المطابقة الآلية مع طلبات العملاء فوراً حتى لو لم تكن هناك شبكة محمول.
        </p>

        <form id="simulateForm" onsubmit="submitSimulation(event)">
            <div class="form-group-custom">
                <label>نوع المحفظة / طريقة الدفع:</label>
                <select id="simProvider" class="input-custom" required>
                    <option value="vodafone_cash">🔴 فودافون كاش (Vodafone Cash)</option>
                    <option value="instapay">🟣 إنستاباي (InstaPay IPN)</option>
                    <option value="orange_cash">🟠 أورانج كاش (Orange Money)</option>
                    <option value="etisalat_cash">🟢 اتصالات كاش (e& money)</option>
                    <option value="bank_transfer">🏦 تحويل بنكي فوري (Bank Instant)</option>
                </select>
            </div>

            <div class="form-row-2">
                <div class="form-group-custom">
                    <label>المبلغ (ج.م):</label>
                    <input type="number" step="0.01" id="simAmount" class="input-custom" value="350.00" required>
                </div>
                <div class="form-group-custom">
                    <label>رقم هاتف المحفظة أو InstaPay ID:</label>
                    <input type="text" id="simSender" class="input-custom" value="01005250838" required>
                </div>
            </div>

            <div class="form-group-custom">
                <label>الرقم المرجعي (اتركه فارغاً للتوليد التلقائي):</label>
                <input type="text" id="simRefId" class="input-custom" placeholder="مثال: VF-98765432 أو IPA-102938">
            </div>

            <div class="form-group-custom">
                <label>ربط بطلب محدد (اختياري، أو اتركه للذكاء الاصطناعي للمطابقة التلقائية):</label>
                <input type="number" id="simTargetOrder" class="input-custom" placeholder="رقم الطلب ID (اختياري)">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeTestModal()">إلغاء</button>
                <button type="submit" class="btn-submit-sim" id="btnSubmitSim">
                    <span>🚀 إرسال التحويل التجريبي وتأكيد الربط</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Manual Match -->
<div class="fintech-modal-backdrop" id="manualMatchModal" style="display:none;">
    <div class="fintech-modal-card">
        <div class="modal-header">
            <h3>🔗 ربط التحويل بطلب يدوياً</h3>
            <button type="button" class="modal-close" onclick="closeManualMatchModal()">&times;</button>
        </div>
        <input type="hidden" id="matchTxId">
        <div class="form-group-custom">
            <label>ابحث عن الطلب (برقم الطلب، اسم العميل، أو رقم الهاتف):</label>
            <input type="text" id="orderSearchInput" class="input-custom" placeholder="اكتب للبحث..." oninput="searchOrdersForMatch(this.value)">
        </div>
        <div id="searchResultsList" class="search-results-box">
            <p class="text-muted-custom">اكتب رقم الطلب أو اسم العميل للبحث والربط...</p>
        </div>
    </div>
</div>

<!-- Modal: Receipt Image Preview -->
<div class="fintech-modal-backdrop" id="receiptModal" style="display:none;" onclick="closeReceiptModal()">
    <div class="receipt-preview-box" onclick="event.stopPropagation()">
        <img id="receiptModalImg" src="" alt="صورة الإيصال">
        <button type="button" class="modal-close-corner" onclick="closeReceiptModal()">&times;</button>
    </div>
</div>

<style>
.admin-fintech-container {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
    font-family: inherit;
    color: #e5e7eb;
}

.fintech-header-card {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border: 1px solid rgba(212, 175, 55, 0.25);
    border-radius: 16px;
    padding: 1.8rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}
.fintech-header-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.12) 0%, transparent 70%);
    pointer-events: none;
}

.fintech-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(212, 175, 55, 0.12);
    border: 1px solid rgba(212, 175, 55, 0.35);
    color: #d4af37;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 8px;
}
.pulse-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 8px #10b981;
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.8); }
}

.fintech-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    margin: 0 0 6px 0;
    letter-spacing: -0.5px;
}
.fintech-subtitle {
    font-size: 0.9rem;
    color: #9ca3af;
    margin: 0;
    max-width: 700px;
    line-height: 1.5;
}

.fintech-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.btn-fintech-test {
    background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
    color: #000;
    font-weight: 800;
    border: none;
    padding: 0.75rem 1.4rem;
    border-radius: 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}
.btn-fintech-test:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
}
.btn-fintech-refresh {
    background: #1f2937;
    color: #e5e7eb;
    border: 1px solid #374151;
    font-weight: 600;
    padding: 0.75rem 1.2rem;
    border-radius: 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-fintech-refresh:hover {
    background: #374151;
}

/* Metrics */
.fintech-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.2rem;
    margin-bottom: 2rem;
}
.fintech-metric-card {
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 14px;
    padding: 1.4rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, border-color 0.2s;
}
.fintech-metric-card:hover {
    transform: translateY(-3px);
    border-color: rgba(212, 175, 55, 0.3);
}
.metric-icon {
    font-size: 2.2rem;
    line-height: 1;
}
.metric-label {
    display: block;
    font-size: 0.82rem;
    color: #9ca3af;
    margin-bottom: 4px;
}
.metric-val {
    font-size: 1.4rem;
    font-weight: 900;
    color: #fff;
    margin: 0;
}
.metric-gold .metric-val { color: #fbbf24; }
.metric-green .metric-val { color: #10b981; }
.metric-blue .metric-val { color: #38bdf8; }
.metric-orange .metric-val { color: #f97316; }

/* Stream Table */
.fintech-stream-card {
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}
.stream-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.2rem;
    border-bottom: 1px solid #1f2937;
    padding-bottom: 1rem;
}
.stream-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
}
.stream-icon { font-size: 1.3rem; }
.stream-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #fff;
    margin: 0;
}
.stream-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    padding: 4px 12px;
    border-radius: 12px;
}
.live-blink {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 1s infinite;
}

.table-responsive {
    overflow-x: auto;
}
.fintech-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.fintech-table th {
    background: #1f2937;
    color: #9ca3af;
    font-weight: 700;
    text-align: start;
    padding: 12px 14px;
    border-bottom: 2px solid #374151;
}
.fintech-table td {
    padding: 14px;
    border-bottom: 1px solid #1f2937;
    vertical-align: middle;
}
.fintech-table tr:hover td {
    background: rgba(255,255,255,0.02);
}

.provider-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
}
.provider-vf { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.provider-ipa { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
.provider-orange { background: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.3); }
.provider-etisalat { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.provider-bank { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }

.amount-badge {
    font-weight: 900;
    color: #fbbf24;
    font-size: 1rem;
}
.ref-code {
    font-family: monospace;
    background: #0f172a;
    padding: 3px 6px;
    border-radius: 4px;
    border: 1px solid #1e293b;
    color: #e2e8f0;
    font-size: 0.82rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
}
.status-matched { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.status-unmatched { background: rgba(234, 179, 8, 0.15); color: #fde047; }

.receipt-thumb {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #374151;
    cursor: pointer;
    transition: transform 0.2s;
}
.receipt-thumb:hover {
    transform: scale(1.1);
    border-color: #d4af37;
}

.action-btn-sm {
    background: #1f2937;
    border: 1px solid #374151;
    color: #e5e7eb;
    padding: 5px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.78rem;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.action-btn-sm:hover { background: #374151; color: #fff; }
.action-btn-danger { color: #f87171; border-color: rgba(239,68,68,0.3); }
.action-btn-danger:hover { background: rgba(239,68,68,0.15); color: #fff; }

/* Modals */
.fintech-modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}
.fintech-modal-card {
    background: #111827;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    padding: 1.8rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    animation: modalPop 0.25s ease-out;
}
@keyframes modalPop {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.8rem;
}
.modal-title-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
.modal-title-wrap h3 {
    margin: 0;
    font-size: 1.15rem;
    color: #fff;
}
.modal-close {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 1.5rem;
    cursor: pointer;
}
.modal-desc {
    font-size: 0.85rem;
    color: #9ca3af;
    margin-bottom: 1.2rem;
    line-height: 1.4;
}

.form-group-custom {
    margin-bottom: 1.1rem;
}
.form-group-custom label {
    display: block;
    font-size: 0.82rem;
    color: #d1d5db;
    margin-bottom: 6px;
    font-weight: 600;
}
.input-custom {
    width: 100%;
    background: #1f2937;
    border: 1px solid #374151;
    border-radius: 8px;
    padding: 10px 12px;
    color: #fff;
    font-size: 0.9rem;
    box-sizing: border-box;
}
.input-custom:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 1.5rem;
}
.btn-cancel {
    background: #1f2937;
    border: 1px solid #374151;
    color: #9ca3af;
    padding: 9px 16px;
    border-radius: 8px;
    cursor: pointer;
}
.btn-submit-sim {
    background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
    color: #000;
    font-weight: 800;
    border: none;
    padding: 9px 18px;
    border-radius: 8px;
    cursor: pointer;
}

.receipt-preview-box {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
}
.receipt-preview-box img {
    max-width: 100%;
    max-height: 85vh;
    border-radius: 12px;
    border: 2px solid #d4af37;
}
.modal-close-corner {
    position: absolute;
    top: -15px;
    right: -15px;
    background: #ef4444;
    color: #fff;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
}

.search-results-box {
    max-height: 250px;
    overflow-y: auto;
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 8px;
    padding: 8px;
    margin-top: 10px;
}
.search-item {
    padding: 8px 12px;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #1e293b;
    cursor: pointer;
}
.search-item:hover { background: #1e293b; }
.loading-td {
    text-align: center;
    padding: 2.5rem !important;
    color: #9ca3af;
}
.spinner {
    display: inline-block;
    width: 24px;
    height: 24px;
    border: 3px solid rgba(212,175,55,0.3);
    border-radius: 50%;
    border-top-color: #d4af37;
    animation: spin 1s ease-in-out infinite;
    margin-bottom: 8px;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
let lastTransfersCount = 0;

function fetchLiveTransfers(isManual = false) {
    const icon = document.getElementById('refreshIcon');
    if (icon) icon.style.transform = 'rotate(180deg)';

    fetch('ajax_fintech_handler.php?action=fetch_live')
        .then(r => r.json())
        .then(data => {
            if (icon) icon.style.transform = 'none';
            if (!data.success) return;

            // Render stats
            const s = data.stats;
            document.getElementById('statTotalAmount').innerText = parseFloat(s.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' ج.م';
            document.getElementById('statMatchedCount').innerText = s.matched_count || '0';
            document.getElementById('statTotalCount').innerText = s.total_transfers || '0';
            document.getElementById('statUnmatchedCount').innerText = s.unmatched_count || '0';

            // Render Table
            renderTable(data.transactions);

            // Play gentle ding if new transfer arrived
            if (data.transactions.length > lastTransfersCount && lastTransfersCount > 0) {
                playNotificationSound();
            }
            lastTransfersCount = data.transactions.length;
        })
        .catch(err => {
            if (icon) icon.style.transform = 'none';
            console.error('Error fetching transfers:', err);
        });
}

function renderTable(txs) {
    const tbody = document.getElementById('transfersTableBody');
    if (!txs || txs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align:center; padding: 3rem; color: #6b7280;">
                    <div style="font-size: 2.5rem; margin-bottom: 8px;">📭</div>
                    <div>لا توجد تحويلات مسجلة حتى الآن.</div>
                    <div style="font-size: 0.8rem; margin-top: 4px;">يمكنك الضغط على زر "تجربة تحويل وهمي" بالأعلى للاختبار فوراً!</div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    txs.forEach(t => {
        let providerBadge = '';
        if (t.provider === 'vodafone_cash') {
            providerBadge = '<span class="provider-badge provider-vf">🔴 فودافون كاش</span>';
        } else if (t.provider === 'instapay') {
            providerBadge = '<span class="provider-badge provider-ipa">🟣 إنستاباي IPN</span>';
        } else if (t.provider === 'orange_cash') {
            providerBadge = '<span class="provider-badge provider-orange">🟠 أورانج كاش</span>';
        } else if (t.provider === 'etisalat_cash') {
            providerBadge = '<span class="provider-badge provider-etisalat">🟢 اتصالات كاش</span>';
        } else {
            providerBadge = '<span class="provider-badge provider-bank">🏦 تحويل بنكي</span>';
        }

        const isMatched = (t.status === 'matched' && t.matched_order_id);
        const statusBadge = isMatched 
            ? '<span class="status-badge status-matched">✅ تم المطابقة والتأكيد</span>'
            : '<span class="status-badge status-unmatched">⏳ بانتظار الربط</span>';

        let orderCol = '<span style="color:#6b7280;">غير مربوط</span>';
        if (isMatched) {
            orderCol = `
                <div>
                    <a href="order_view.php?id=${t.matched_order_id}" style="color:#fbbf24; font-weight:bold; text-decoration:none;">
                        #${t.order_number || t.matched_order_id}
                    </a>
                    <div style="font-size:0.75rem; color:#9ca3af;">${t.customer_name || ''}</div>
                </div>
            `;
        }

        let receiptCol = '<span style="color:#6b7280; font-size:0.78rem;">لا يوجد</span>';
        if (t.payment_receipt) {
            const receiptPath = '../assets/uploads/receipts/' + t.payment_receipt;
            receiptCol = `
                <img src="${receiptPath}" class="receipt-thumb" onclick="openReceiptModal('${receiptPath}')" title="اضغط لتكبير الإيصال">
            `;
        }

        let actions = `
            <div style="display:flex; gap:6px;">
                ${!isMatched ? `<button type="button" class="action-btn-sm" onclick="openManualMatch(${t.id})">🔗 ربط بطلب</button>` : ''}
                <button type="button" class="action-btn-sm action-btn-danger" onclick="deleteTx(${t.id})" title="حذف">🗑️</button>
            </div>
        `;

        html += `
            <tr>
                <td>${providerBadge}</td>
                <td><span class="amount-badge">${parseFloat(t.amount).toFixed(2)} ج.م</span></td>
                <td><span class="ref-code">${t.reference_id}</span></td>
                <td><span style="font-size:0.85rem;">${t.sender_number_or_handle || 'غير محدد'}</span></td>
                <td>${orderCol}</td>
                <td>${receiptCol}</td>
                <td>${statusBadge}</td>
                <td style="font-size:0.78rem; color:#9ca3af;">${t.received_at}</td>
                <td>${actions}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function openTestModal() {
    document.getElementById('testModal').style.display = 'flex';
}
function closeTestModal() {
    document.getElementById('testModal').style.display = 'none';
}

function submitSimulation(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitSim');
    btn.disabled = true;
    btn.innerText = 'جاري الإرسال...';

    const formData = new FormData();
    formData.append('action', 'simulate_test_transfer');
    formData.append('provider', document.getElementById('simProvider').value);
    formData.append('amount', document.getElementById('simAmount').value);
    formData.append('sender', document.getElementById('simSender').value);
    formData.append('reference_id', document.getElementById('simRefId').value);
    formData.append('target_order_id', document.getElementById('simTargetOrder').value);

    fetch('ajax_fintech_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = '🚀 إرسال التحويل التجريبي وتأكيد الربط';
        if (res.success) {
            closeTestModal();
            fetchLiveTransfers(true);
            alert(res.message);
        } else {
            alert('حدث خطأ: ' + res.error);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = '🚀 إرسال التحويل التجريبي وتأكيد الربط';
        alert('فشل الاتصال');
    });
}

function openManualMatch(txId) {
    document.getElementById('matchTxId').value = txId;
    document.getElementById('orderSearchInput').value = '';
    document.getElementById('searchResultsList').innerHTML = '<p class="text-muted-custom">اكتب رقم الطلب أو اسم العميل للبحث والربط...</p>';
    document.getElementById('manualMatchModal').style.display = 'flex';
}
function closeManualMatchModal() {
    document.getElementById('manualMatchModal').style.display = 'none';
}

function searchOrdersForMatch(q) {
    if (q.length < 2) return;
    fetch('ajax_fintech_handler.php?action=search_orders&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('searchResultsList');
            if (!data.orders || data.orders.length === 0) {
                list.innerHTML = '<p style="color:#9ca3af; padding:8px;">لا توجد طلبات مطابقة للبحث</p>';
                return;
            }
            let html = '';
            data.orders.forEach(o => {
                html += `
                    <div class="search-item" onclick="executeMatch(${o.id})">
                        <div>
                            <strong style="color:#fbbf24;">#${o.order_number}</strong> - <span>${o.customer_name}</span> (${o.customer_phone})
                            <div style="font-size:0.75rem; color:#9ca3af;">المبلغ: ${o.total} ج.م | الحالة: ${o.status}</div>
                        </div>
                        <button type="button" class="btn-submit-sim" style="padding:4px 10px; font-size:0.78rem;">ربط الآن</button>
                    </div>
                `;
            });
            list.innerHTML = html;
        });
}

function executeMatch(orderId) {
    const txId = document.getElementById('matchTxId').value;
    const formData = new FormData();
    formData.append('action', 'manual_match');
    formData.append('transaction_id', txId);
    formData.append('order_id', orderId);

    fetch('ajax_fintech_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeManualMatchModal();
            fetchLiveTransfers(true);
            alert(res.message);
        } else {
            alert('حدث خطأ: ' + res.error);
        }
    });
}

function deleteTx(id) {
    if (!confirm('هل أنت متأكد من حذف هذه المعاملة؟')) return;
    const formData = new FormData();
    formData.append('action', 'delete_transaction');
    formData.append('transaction_id', id);

    fetch('ajax_fintech_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) fetchLiveTransfers(true);
    });
}

function openReceiptModal(url) {
    document.getElementById('receiptModalImg').src = url;
    document.getElementById('receiptModal').style.display = 'flex';
}
function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

function playNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
        osc.start(audioCtx.currentTime);
        osc.stop(audioCtx.currentTime + 0.4);
    } catch(e) {}
}

// Initial fetch & start 5-second polling
fetchLiveTransfers();
setInterval(fetchLiveTransfers, 5000);
</script>

<?php
require_once __DIR__ . '/_layout_end.php';
?>
