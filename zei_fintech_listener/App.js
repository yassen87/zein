import React, { useState, useEffect } from 'react';
import {
  SafeAreaView,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  TextInput,
  Modal,
  Alert,
  FlatList,
} from 'react-native';
import { parseFintechSms } from './src/services/smsParser';
import {
  getServerSettings,
  saveServerSettings,
  syncTransferToServer,
  getLocalTransfers,
  flushOfflineQueue,
} from './src/services/apiSync';

export default function App() {
  const [transfers, setTransfers] = useState([]);
  const [serverUrl, setServerUrl] = useState('http://127.0.0.1:8000/api/fintech_sync.php');
  const [apiKey, setApiKey] = useState('zei_fintech_secret_key_2026');
  const [settingsModalVisible, setSettingsModalVisible] = useState(false);
  const [testModalVisible, setTestModalVisible] = useState(false);
  const [testSmsText, setTestSmsText] = useState(
    'تم استلام مبلغ 1250.00 ج.م من رقم 01012345678 والرقم المرجعي هو 2408189988'
  );
  const [testSender, setTestSender] = useState('VF-Cash');
  const [isListening, setIsListening] = useState(true);
  const [syncing, setSyncing] = useState(false);

  // Load initial settings and history
  useEffect(() => {
    loadSettings();
    loadHistory();

    // Auto-flush offline queue periodically
    const interval = setInterval(() => {
      flushOfflineQueue();
      loadHistory();
    }, 15000);

    return () => clearInterval(interval);
  }, []);

  const loadSettings = async () => {
    const s = await getServerSettings();
    setServerUrl(s.serverUrl);
    setApiKey(s.apiKey);
  };

  const loadHistory = async () => {
    const list = await getLocalTransfers();
    setTransfers(list);
  };

  const handleSaveSettings = async () => {
    await saveServerSettings(serverUrl, apiKey);
    setSettingsModalVisible(false);
    Alert.alert('✅ تم الحفظ', 'تم حفظ إعدادات السيرفر ومفتاح الأمان بنجاح.');
  };

  const handleTestSms = async () => {
    setSyncing(true);
    const parsed = parseFintechSms(testSmsText, testSender);

    if (!parsed) {
      setSyncing(false);
      Alert.alert('❌ خطأ في القراءة', 'لم يتم التعرف على نمط رسالة تحويل مالي. تأكد من وجود المبلغ والرقم المرجعي.');
      return;
    }

    const result = await syncTransferToServer(parsed);
    setSyncing(false);
    setTestModalVisible(false);
    loadHistory();

    if (result.success) {
      Alert.alert(
        '🎉 نجاح المزامنة!',
        `تم إرسال التحويل بمبلغ ${parsed.amount} ج.م بنجاح إلى السيرفر!` +
        (result.matched_order_id ? `\n\n✅ تم ربطه تلقائياً بالطلب #${result.matched_order_id}!` : '')
      );
    } else {
      Alert.alert('⚠️ تنبيه المزامنة', `تم حفظ الرسالة محلياً وستتم المزامنة تلقائياً: ${result.error || 'غير متصل'}`);
    }
  };

  const totalToday = transfers.reduce((acc, t) => acc + (parseFloat(t.amount) || 0), 0);

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0B0F19" />

      {/* Top Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>👑 ZEI TRANSFERS</Text>
          <Text style={styles.headerSubtitle}>راصد تحويلات فودافون كاش وإنستاباي 📱</Text>
        </View>

        <TouchableOpacity
          style={styles.settingsBtn}
          onPress={() => setSettingsModalVisible(true)}
        >
          <Text style={styles.settingsBtnText}>⚙️ الإعدادات</Text>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Status Pill Card */}
        <View style={styles.statusCard}>
          <View style={styles.statusPill}>
            <View style={[styles.statusDot, { backgroundColor: isListening ? '#10B981' : '#EF4444' }]} />
            <Text style={styles.statusText}>
              {isListening ? 'الراصد نشط ويعمل في الخلفية 24/7' : 'الراصد متوقف'}
            </Text>
          </View>

          <TouchableOpacity
            style={styles.testTriggerBtn}
            onPress={() => setTestModalVisible(true)}
          >
            <Text style={styles.testTriggerBtnText}>⚡ اختبار رسالة محاكاة</Text>
          </TouchableOpacity>
        </View>

        {/* Stats Grid */}
        <View style={styles.statsGrid}>
          <View style={[styles.statBox, { borderColor: 'rgba(212, 175, 55, 0.4)' }]}>
            <Text style={styles.statLabel}>إجمالي المبالغ المستلمة</Text>
            <Text style={styles.statValue}>
              {totalToday.toLocaleString('en-US', { minimumFractionDigits: 2 })}
              <Text style={styles.statUnit}> ج.م</Text>
            </Text>
          </View>

          <View style={[styles.statBox, { borderColor: 'rgba(16, 185, 129, 0.3)' }]}>
            <Text style={styles.statLabel}>عدد العمليات المسجلة</Text>
            <Text style={[styles.statValue, { color: '#10B981' }]}>
              {transfers.length} <Text style={styles.statUnit}>عملية</Text>
            </Text>
          </View>
        </View>

        {/* Live Stream Header */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>سجل التحويلات المرصودة</Text>
          <TouchableOpacity onPress={loadHistory}>
            <Text style={styles.refreshLink}>🔄 تحديث السجل</Text>
          </TouchableOpacity>
        </View>

        {/* Transfers List */}
        {transfers.length === 0 ? (
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>📥</Text>
            <Text style={styles.emptyText}>في انتظار استقبال رسائل التحويل...</Text>
            <Text style={styles.emptySubtext}>
              ستظهر هنا فور وصول أي رسالة SMS من فودافون كاش أو إنستاباي أو المحافظ
            </Text>
          </View>
        ) : (
          transfers.map((item, index) => (
            <View key={index} style={styles.transferCard}>
              <View style={styles.transferCardTop}>
                <View style={styles.providerBadge}>
                  <Text style={styles.providerBadgeText}>
                    {item.provider === 'vodafone_cash' ? '🔴 فودافون كاش' :
                     item.provider === 'instapay' ? '🟣 إنستاباي' :
                     item.provider === 'orange_cash' ? '🟠 أورانج كاش' :
                     item.provider === 'etisalat_cash' ? '🟢 اتصالات كاش' : '🏦 تحويل بنكي'}
                  </Text>
                </View>

                <Text style={styles.transferAmount}>
                  +{parseFloat(item.amount || 0).toFixed(2)} ج.م
                </Text>
              </View>

              <View style={styles.transferMeta}>
                <Text style={styles.metaItem}>
                  🔢 مرجعي: <Text style={styles.metaCode}>{item.reference_id}</Text>
                </Text>
                {item.sender ? (
                  <Text style={styles.metaItem}>
                    👤 المرسل: <Text style={styles.metaCode}>{item.sender}</Text>
                  </Text>
                ) : null}
              </View>

              <View style={styles.transferCardBottom}>
                <View style={[
                  styles.syncBadge,
                  { backgroundColor: item.syncStatus === 'matched' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(212, 175, 55, 0.15)' }
                ]}>
                  <Text style={[
                    styles.syncBadgeText,
                    { color: item.syncStatus === 'matched' ? '#10B981' : '#D4AF37' }
                  ]}>
                    {item.syncStatus === 'matched' ? '✓ تم الربط بطلب العميل' :
                     item.syncStatus === 'synced' ? '✓ تم الإرسال للسيرفر' : '⏳ قيد المزامنة'}
                  </Text>
                </View>

                <Text style={styles.timestampText}>
                  {new Date(item.received_at || Date.now()).toLocaleTimeString('ar-EG')}
                </Text>
              </View>
            </View>
          ))
        )}
      </ScrollView>

      {/* Settings Modal */}
      <Modal visible={settingsModalVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>⚙️ إعدادات الربط بالسيرفر</Text>

            <Text style={styles.inputLabel}>رابط السيرفر (API Endpoint):</Text>
            <TextInput
              style={styles.input}
              value={serverUrl}
              onChangeText={setServerUrl}
              placeholder="https://yoursite.com/api/fintech_sync.php"
              placeholderTextColor="#64748B"
              autoCapitalize="none"
            />

            <Text style={styles.inputLabel}>مفتاح أمان الجهاز (API Key):</Text>
            <TextInput
              style={styles.input}
              value={apiKey}
              onChangeText={setApiKey}
              placeholder="API Key"
              placeholderTextColor="#64748B"
              autoCapitalize="none"
              secureTextEntry
            />

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.cancelBtn}
                onPress={() => setSettingsModalVisible(false)}
              >
                <Text style={styles.cancelBtnText}>إلغاء</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.saveBtn}
                onPress={handleSaveSettings}
              >
                <Text style={styles.saveBtnText}>حفظ الإعدادات</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Test SMS Simulation Modal */}
      <Modal visible={testModalVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>⚡ اختبار رسالة تحويل</Text>
            <Text style={styles.modalDesc}>
              قم باختبار قراءة رسالة تحويل وهمية ومطابقتها فورياً مع السيرفر:
            </Text>

            <Text style={styles.inputLabel}>اسم المرسل (Sender / Address):</Text>
            <TextInput
              style={styles.input}
              value={testSender}
              onChangeText={setTestSender}
              placeholder="VF-Cash or InstaPay"
              placeholderTextColor="#64748B"
            />

            <Text style={styles.inputLabel}>نص الرسالة (SMS Body):</Text>
            <TextInput
              style={[styles.input, { height: 90, textAlignVertical: 'top' }]}
              value={testSmsText}
              onChangeText={setTestSmsText}
              multiline
              placeholder="نص رسالة التحويل..."
              placeholderTextColor="#64748B"
            />

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.cancelBtn}
                onPress={() => setTestModalVisible(false)}
              >
                <Text style={styles.cancelBtnText}>إغلاق</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.saveBtn, { backgroundColor: '#10B981' }]}
                onPress={handleTestSms}
                disabled={syncing}
              >
                <Text style={styles.saveBtnText}>
                  {syncing ? 'جاري الإرسال...' : '🚀 إرسال ومزامنة الآن'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0B0F19',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: '#D4AF37',
    letterSpacing: 1,
  },
  headerSubtitle: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  settingsBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    backgroundColor: '#1E293B',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#334155',
  },
  settingsBtnText: {
    color: '#E2E8F0',
    fontSize: 12,
    fontWeight: 'bold',
  },
  scrollContent: {
    padding: 16,
  },
  statusCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#111827',
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#1E293B',
    marginBottom: 16,
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  statusText: {
    color: '#E2E8F0',
    fontSize: 11,
    fontWeight: 'bold',
  },
  testTriggerBtn: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: 'rgba(212, 175, 55, 0.15)',
    borderWidth: 1,
    borderColor: '#D4AF37',
    borderRadius: 8,
  },
  testTriggerBtnText: {
    color: '#D4AF37',
    fontSize: 11,
    fontWeight: 'bold',
  },
  statsGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 20,
  },
  statBox: {
    flex: 1,
    backgroundColor: '#111827',
    padding: 16,
    borderRadius: 18,
    borderWidth: 1.5,
  },
  statLabel: {
    fontSize: 11,
    color: '#94A3B8',
    marginBottom: 6,
    fontWeight: '600',
  },
  statValue: {
    fontSize: 20,
    fontWeight: '900',
    color: '#D4AF37',
  },
  statUnit: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: 'normal',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#F1F5F9',
  },
  refreshLink: {
    fontSize: 12,
    color: '#D4AF37',
    fontWeight: 'bold',
  },
  emptyState: {
    padding: 40,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#111827',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#1E293B',
    borderStyle: 'dashed',
  },
  emptyIcon: {
    fontSize: 36,
    marginBottom: 10,
  },
  emptyText: {
    color: '#E2E8F0',
    fontSize: 14,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  emptySubtext: {
    color: '#64748B',
    fontSize: 11,
    textAlign: 'center',
    marginTop: 4,
  },
  transferCard: {
    backgroundColor: '#111827',
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: '#1E293B',
    marginBottom: 10,
  },
  transferCardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  providerBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    backgroundColor: '#1E293B',
    borderRadius: 6,
  },
  providerBadgeText: {
    fontSize: 11,
    fontWeight: 'bold',
    color: '#F8FAFC',
  },
  transferAmount: {
    fontSize: 16,
    fontWeight: '900',
    color: '#10B981',
  },
  transferMeta: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 8,
  },
  metaItem: {
    fontSize: 11,
    color: '#94A3B8',
  },
  metaCode: {
    color: '#E2E8F0',
    fontWeight: 'bold',
    fontFamily: 'monospace',
  },
  transferCardBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#1E293B',
    paddingTop: 8,
  },
  syncBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  syncBadgeText: {
    fontSize: 10,
    fontWeight: 'bold',
  },
  timestampText: {
    fontSize: 10,
    color: '#64748B',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.85)',
    justifyContent: 'center',
    padding: 20,
  },
  modalCard: {
    backgroundColor: '#111827',
    borderRadius: 24,
    padding: 24,
    borderWidth: 1,
    borderColor: '#D4AF37',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#F8FAFC',
    marginBottom: 8,
    textAlign: 'center',
  },
  modalDesc: {
    fontSize: 12,
    color: '#94A3B8',
    marginBottom: 16,
    textAlign: 'center',
  },
  inputLabel: {
    fontSize: 12,
    color: '#CBD5E1',
    fontWeight: 'bold',
    marginBottom: 6,
    marginTop: 8,
  },
  input: {
    backgroundColor: '#0B0F19',
    borderWidth: 1,
    borderColor: '#334155',
    borderRadius: 12,
    padding: 12,
    color: '#F8FAFC',
    fontSize: 13,
  },
  modalActions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 20,
  },
  cancelBtn: {
    flex: 1,
    padding: 12,
    backgroundColor: '#1E293B',
    borderRadius: 12,
    alignItems: 'center',
  },
  cancelBtnText: {
    color: '#94A3B8',
    fontWeight: 'bold',
  },
  saveBtn: {
    flex: 1,
    padding: 12,
    backgroundColor: '#D4AF37',
    borderRadius: 12,
    alignItems: 'center',
  },
  saveBtnText: {
    color: '#0B0F19',
    fontWeight: 'bold',
  },
});
