import 'package:another_telephony/telephony.dart';
import 'package:permission_handler/permission_handler.dart';
import 'sms_parser.dart';
import 'api_sync_service.dart';
import '../models/transfer_transaction.dart';

// Top level background handler for Telephony
@pragma('vm:entry-point')
void backgroundMessageHandler(SmsMessage message) async {
  final body = message.body ?? '';
  final address = message.address ?? '';
  final tx = SmsParser.parse(body, senderAddress: address);
  if (tx != null) {
    await ApiSyncService.syncTransaction(tx);
  }
}

class SmsListenerService {
  static final Telephony telephony = Telephony.instance;
  static bool isListening = false;

  static Future<bool> checkPermissions() async {
    final smsStatus = await Permission.sms.status;
    final phoneStatus = await Permission.phone.status;
    return smsStatus.isGranted && phoneStatus.isGranted;
  }

  static Future<bool> requestPermissions() async {
    // 1. Request via permission_handler
    Map<Permission, PermissionStatus> statuses = await [
      Permission.sms,
      Permission.phone,
      Permission.notification,
    ].request();

    bool granted = statuses[Permission.sms]?.isGranted == true;

    // 2. Fallback request via Telephony directly
    if (!granted) {
      try {
        final telephonyGranted = await telephony.requestPhoneAndSmsPermissions;
        if (telephonyGranted == true) {
          granted = true;
        }
      } catch (e) {
        // ignore
      }
    }

    return granted;
  }

  static Future<void> openSettings() async {
    await openAppSettings();
  }

  static void startListening({Function(TransferTransaction)? onNewTransaction}) async {
    final hasPermission = await requestPermissions();
    if (!hasPermission) {
      isListening = false;
      return;
    }

    try {
      telephony.listenIncomingSms(
        onNewMessage: (SmsMessage message) async {
          final body = message.body ?? '';
          final address = message.address ?? '';
          final tx = SmsParser.parse(body, senderAddress: address);
          if (tx != null) {
            await ApiSyncService.syncTransaction(tx);
            if (onNewTransaction != null) {
              onNewTransaction(tx);
            }
          }
        },
        onBackgroundMessage: backgroundMessageHandler,
        listenInBackground: true,
      );
      isListening = true;
    } catch (e) {
      isListening = false;
    }
  }
}
