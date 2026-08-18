import AsyncStorage from '@react-native-async-storage/async-storage';

const STORAGE_KEYS = {
  SERVER_URL: '@zei_server_url',
  API_KEY: '@zei_api_key',
  TRANSACTIONS_LOG: '@zei_transfers_log',
  OFFLINE_QUEUE: '@zei_offline_queue',
};

const DEFAULT_SERVER_URL = 'http://127.0.0.1:8000/api/fintech_sync.php';
const DEFAULT_API_KEY = 'zei_fintech_secret_key_2026';

export async function getServerSettings() {
  try {
    const url = await AsyncStorage.getItem(STORAGE_KEYS.SERVER_URL);
    const key = await AsyncStorage.getItem(STORAGE_KEYS.API_KEY);
    return {
      serverUrl: url || DEFAULT_SERVER_URL,
      apiKey: key || DEFAULT_API_KEY,
    };
  } catch {
    return {
      serverUrl: DEFAULT_SERVER_URL,
      apiKey: DEFAULT_API_KEY,
    };
  }
}

export async function saveServerSettings(serverUrl, apiKey) {
  await AsyncStorage.setItem(STORAGE_KEYS.SERVER_URL, serverUrl.trim());
  await AsyncStorage.setItem(STORAGE_KEYS.API_KEY, apiKey.trim());
}

export async function syncTransferToServer(transferData) {
  const { serverUrl, apiKey } = await getServerSettings();

  const payload = {
    api_key: apiKey,
    provider: transferData.provider,
    amount: transferData.amount,
    sender: transferData.sender,
    reference_id: transferData.reference_id,
    raw_message: transferData.raw_message,
    received_at: transferData.received_at || new Date().toISOString(),
  };

  try {
    const response = await fetch(serverUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-KEY': apiKey,
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();

    // Log to local history
    await logLocalTransfer({
      ...transferData,
      syncStatus: result.success ? (result.status === 'matched' ? 'matched' : 'synced') : 'failed',
      matchedOrderId: result.matched_order_id,
      syncTimestamp: new Date().toISOString(),
    });

    return result;
  } catch (error) {
    console.warn('[Sync Error] Connection failed, queueing offline:', error.message);
    
    // Save to offline queue
    await queueOfflineTransfer(payload);
    
    await logLocalTransfer({
      ...transferData,
      syncStatus: 'queued_offline',
      syncTimestamp: new Date().toISOString(),
    });

    return {
      success: false,
      error: error.message,
      offline: true,
    };
  }
}

export async function logLocalTransfer(tx) {
  try {
    const raw = await AsyncStorage.getItem(STORAGE_KEYS.TRANSACTIONS_LOG);
    const list = raw ? JSON.parse(raw) : [];
    list.unshift(tx);
    if (list.length > 300) list.pop();
    await AsyncStorage.setItem(STORAGE_KEYS.TRANSACTIONS_LOG, JSON.stringify(list));
  } catch (e) {
    console.error('Error logging local transfer:', e);
  }
}

export async function getLocalTransfers() {
  try {
    const raw = await AsyncStorage.getItem(STORAGE_KEYS.TRANSACTIONS_LOG);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export async function queueOfflineTransfer(payload) {
  try {
    const raw = await AsyncStorage.getItem(STORAGE_KEYS.OFFLINE_QUEUE);
    const list = raw ? JSON.parse(raw) : [];
    list.push(payload);
    await AsyncStorage.setItem(STORAGE_KEYS.OFFLINE_QUEUE, JSON.stringify(list));
  } catch (e) {
    console.error('Error queueing offline transfer:', e);
  }
}

export async function flushOfflineQueue() {
  try {
    const raw = await AsyncStorage.getItem(STORAGE_KEYS.OFFLINE_QUEUE);
    if (!raw) return 0;
    const queue = JSON.parse(raw);
    if (queue.length === 0) return 0;

    const { serverUrl, apiKey } = await getServerSettings();
    let flushedCount = 0;
    const remaining = [];

    for (const item of queue) {
      try {
        const res = await fetch(serverUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-API-KEY': apiKey,
          },
          body: JSON.stringify(item),
        });
        if (res.ok) {
          flushedCount++;
        } else {
          remaining.push(item);
        }
      } catch {
        remaining.push(item);
      }
    }

    await AsyncStorage.setItem(STORAGE_KEYS.OFFLINE_QUEUE, JSON.stringify(remaining));
    return flushedCount;
  } catch (e) {
    console.error('Error flushing offline queue:', e);
    return 0;
  }
}
