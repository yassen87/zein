import '../models/transfer_transaction.dart';

class SmsParser {
  /// Parses SMS text from Egyptian Fintech & Mobile Wallets
  static TransferTransaction? parse(String rawSms, {String senderAddress = ''}) {
    if (rawSms.trim().isEmpty) return null;

    final sms = rawSms.trim();
    final address = senderAddress.trim().toUpperCase();

    // 1. Vodafone Cash (VF-Cash / Vodafone)
    if (address.contains('VF') ||
        address.contains('VODAFONE') ||
        sms.contains('فودافون كاش') ||
        (sms.contains('تم استلام مبلغ') && sms.contains('الرقم المرجعي')) ||
        sms.contains('تم استلام تحويل من')) {
      
      final amountRegex = RegExp(r'(?:مبلغ|قيمة|تحويل)?\s*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)', caseSensitive: false);
      final refRegex = RegExp(r'(?:الرقم المرجعي|رقم العملية|مرجعي|كود العملية|ref(?:erence)?)\s*(?:هو|:)?\s*([0-9a-zA-Z]+)', caseSensitive: false);
      final senderRegex = RegExp(r'(?:من رقم|من محفظة|من)\s*([0-9]{11})', caseSensitive: false);

      final amountMatch = amountRegex.firstMatch(sms);
      final refMatch = refRegex.firstMatch(sms);
      final senderMatch = senderRegex.firstMatch(sms);

      final amount = amountMatch != null ? double.tryParse(amountMatch.group(1)!.replaceAll(',', '')) : null;
      final refId = refMatch != null ? refMatch.group(1)!.trim() : 'VF-${DateTime.now().millisecondsSinceEpoch}';
      final sender = senderMatch != null ? senderMatch.group(1)!.trim() : null;

      if (amount != null && amount > 0) {
        return TransferTransaction(
          id: 'VF_${DateTime.now().millisecondsSinceEpoch}',
          provider: 'vodafone_cash',
          providerName: 'فودافون كاش (Vodafone Cash)',
          amount: amount,
          sender: sender,
          referenceId: refId,
          rawMessage: sms,
          receivedAt: DateTime.now(),
        );
      }
    }

    // 2. InstaPay (IPN) & Bank Instant Transfers
    if (address.contains('INSTAPAY') ||
        address.contains('IPN') ||
        address.contains('NBE') ||
        address.contains('CIB') ||
        address.contains('BM') ||
        address.contains('QNB') ||
        sms.contains('انستاباي') ||
        sms.contains('إنستاباي') ||
        sms.contains('تحويل لحظي') ||
        sms.contains('IPN')) {

      final amountRegex = RegExp(r'(?:بمبلغ|مبلغ|قيمة)?\s*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|جم|EGP|LE)', caseSensitive: false);
      final refRegex = RegExp(r'(?:الرقم المرجعي|رقم العملية|المرجعي|Ref(?:erence)?(?:\s*No)?)\s*(?:هو|:)?\s*([0-9a-zA-Z_\-]+)', caseSensitive: false);
      final senderRegex = RegExp(r'(?:من|From)\s*([a-zA-Z0-9._\-@]+(?:@instapay)?)', caseSensitive: false);

      final amountMatch = amountRegex.firstMatch(sms);
      final refMatch = refRegex.firstMatch(sms);
      final senderMatch = senderRegex.firstMatch(sms);

      final amount = amountMatch != null ? double.tryParse(amountMatch.group(1)!.replaceAll(',', '')) : null;
      final refId = refMatch != null ? refMatch.group(1)!.trim() : 'IPA-${DateTime.now().millisecondsSinceEpoch}';
      final sender = senderMatch != null ? senderMatch.group(1)!.trim() : null;

      if (amount != null && amount > 0) {
        return TransferTransaction(
          id: 'IPA_${DateTime.now().millisecondsSinceEpoch}',
          provider: 'instapay',
          providerName: 'إنستاباي (InstaPay IPN)',
          amount: amount,
          sender: sender,
          referenceId: refId,
          rawMessage: sms,
          receivedAt: DateTime.now(),
        );
      }
    }

    // 3. Orange Money (Orange Cash)
    if (address.contains('ORANGE') || sms.contains('أورانج كاش') || sms.contains('اورانج كاش')) {
      final amountRegex = RegExp(r'([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)', caseSensitive: false);
      final refRegex = RegExp(r'(?:الرقم المرجعي|رقم العملية|مرجعي)\s*(?:هو|:)?\s*([0-9a-zA-Z]+)', caseSensitive: false);
      final senderRegex = RegExp(r'(?:من رقم|من)\s*([0-9]{11})', caseSensitive: false);

      final amountMatch = amountRegex.firstMatch(sms);
      final refMatch = refRegex.firstMatch(sms);
      final senderMatch = senderRegex.firstMatch(sms);

      final amount = amountMatch != null ? double.tryParse(amountMatch.group(1)!.replaceAll(',', '')) : null;
      final refId = refMatch != null ? refMatch.group(1)!.trim() : 'ORG-${DateTime.now().millisecondsSinceEpoch}';

      if (amount != null && amount > 0) {
        return TransferTransaction(
          id: 'ORG_${DateTime.now().millisecondsSinceEpoch}',
          provider: 'orange_cash',
          providerName: 'أورانج كاش (Orange Money)',
          amount: amount,
          sender: senderMatch?.group(1)?.trim(),
          referenceId: refId,
          rawMessage: sms,
          receivedAt: DateTime.now(),
        );
      }
    }

    // 4. Etisalat Cash
    if (address.contains('ETISALAT') || sms.contains('اتصالات كاش') || sms.contains('إتصالات كاش')) {
      final amountRegex = RegExp(r'([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)', caseSensitive: false);
      final refRegex = RegExp(r'(?:الرقم المرجعي|رقم العملية|مرجعي)\s*(?:هو|:)?\s*([0-9a-zA-Z]+)', caseSensitive: false);
      final senderRegex = RegExp(r'(?:من رقم|من)\s*([0-9]{11})', caseSensitive: false);

      final amountMatch = amountRegex.firstMatch(sms);
      final refMatch = refRegex.firstMatch(sms);
      final senderMatch = senderRegex.firstMatch(sms);

      final amount = amountMatch != null ? double.tryParse(amountMatch.group(1)!.replaceAll(',', '')) : null;
      final refId = refMatch != null ? refMatch.group(1)!.trim() : 'ETI-${DateTime.now().millisecondsSinceEpoch}';

      if (amount != null && amount > 0) {
        return TransferTransaction(
          id: 'ETI_${DateTime.now().millisecondsSinceEpoch}',
          provider: 'etisalat_cash',
          providerName: 'اتصالات كاش (e& money)',
          amount: amount,
          sender: senderMatch?.group(1)?.trim(),
          referenceId: refId,
          rawMessage: sms,
          receivedAt: DateTime.now(),
        );
      }
    }

    // Generic fallback if text contains amount and transfer keywords
    if (sms.contains('تم استلام') || sms.contains('تم إيداع') || sms.contains('تم تحويل') || sms.contains('received')) {
      final amountRegex = RegExp(r'([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP|LE)', caseSensitive: false);
      final amountMatch = amountRegex.firstMatch(sms);
      final amount = amountMatch != null ? double.tryParse(amountMatch.group(1)!.replaceAll(',', '')) : null;

      if (amount != null && amount > 0) {
        return TransferTransaction(
          id: 'GEN_${DateTime.now().millisecondsSinceEpoch}',
          provider: 'bank_transfer',
          providerName: 'تحويل بنكي / إلكتروني',
          amount: amount,
          referenceId: 'GEN-${DateTime.now().millisecondsSinceEpoch}',
          rawMessage: sms,
          receivedAt: DateTime.now(),
        );
      }
    }

    return null;
  }
}
