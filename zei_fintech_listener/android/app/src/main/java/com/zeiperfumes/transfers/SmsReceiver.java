package com.zeiperfumes.transfers;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.telephony.SmsMessage;
import android.util.Log;

import org.json.JSONObject;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;

public class SmsReceiver extends BroadcastReceiver {
    private static final String TAG = "ZeiSmsReceiver";
    private static final String DEFAULT_ENDPOINT = "http://127.0.0.1:8000/api/fintech_sync.php";
    private static final String DEFAULT_API_KEY = "zei_fintech_secret_key_2026";

    @Override
    public void onReceive(Context context, Intent intent) {
        if (intent.getAction() != null && intent.getAction().equals("android.provider.Telephony.SMS_RECEIVED")) {
            Bundle bundle = intent.getExtras();
            if (bundle != null) {
                Object[] pdus = (Object[]) bundle.get("pdus");
                if (pdus != null) {
                    for (Object pdu : pdus) {
                        SmsMessage message = SmsMessage.createFromPdu((byte[]) pdu);
                        String sender = message.getDisplayOriginatingAddress();
                        String body = message.getMessageBody();

                        Log.d(TAG, "SMS Received from: " + sender + " Body: " + body);

                        // Parse and sync in background thread
                        new Thread(() -> processAndSync(sender, body)).start();
                    }
                }
            }
        }
    }

    private void processAndSync(String sender, String body) {
        try {
            // Check if SMS is a financial transfer
            String upper = (sender != null ? sender : "").toUpperCase();
            boolean isFintech = upper.contains("VF") || upper.contains("VODAFONE") ||
                                upper.contains("INSTAPAY") || upper.contains("IPN") ||
                                upper.contains("ORANGE") || upper.contains("ETISALAT") ||
                                upper.contains("CIB") || upper.contains("NBE") || upper.contains("BM") ||
                                body.contains("تم استلام مبلغ") || body.contains("تحويل لحظي") || body.contains("الرقم المرجعي");

            if (!isFintech) {
                return;
            }

            // Construct payload
            JSONObject json = new JSONObject();
            json.put("api_key", DEFAULT_API_KEY);
            json.put("provider", upper.contains("VF") ? "vodafone_cash" : (upper.contains("INSTAPAY") ? "instapay" : "bank"));
            json.put("sender", sender);
            json.put("raw_message", body);
            json.put("reference_id", "REF-" + System.currentTimeMillis());
            json.put("amount", 0.0); // Server fallback or full JS sync

            URL url = new URL(DEFAULT_ENDPOINT);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json; utf-8");
            conn.setRequestProperty("X-API-KEY", DEFAULT_API_KEY);
            conn.setDoOutput(true);
            conn.setConnectTimeout(4000);
            conn.setReadTimeout(4000);

            try (OutputStream os = conn.getOutputStream()) {
                byte[] input = json.toString().getBytes("utf-8");
                os.write(input, 0, input.length);
            }

            int responseCode = conn.getResponseCode();
            Log.d(TAG, "Sync Response Code: " + responseCode);
            conn.disconnect();
        } catch (Exception e) {
            Log.e(TAG, "Error syncing SMS: " + e.getMessage());
        }
    }
}
