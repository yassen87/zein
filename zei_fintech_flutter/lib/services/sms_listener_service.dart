import 'package:telephony/telephony.dart';
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

  static Future<bool> requestPermissions() async {
    final smsStatus = await Permission.sms.request();
    final phoneStatus = await Permission.phone.request();
    final notifStatus = await Permission.notification.request();

    return smsStatus.isGranted || await telephony.isSmsCapable;
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
            final result = await ApiSyncService.syncTransaction(tx);
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
