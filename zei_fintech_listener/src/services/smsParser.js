/**
 * Smart Regex Parser for Egyptian Fintech & Mobile Wallet SMS
 * Supports Vodafone Cash, InstaPay (IPN), Orange Money, Etisalat Cash, WE Pay, and Banks
 */

export function parseFintechSms(rawSms, senderAddress = '') {
  if (!rawSms) return null;

  const sms = rawSms.trim();
  const address = (senderAddress || '').toUpperCase();

  // 1. Vodafone Cash (VF-Cash / Vodafone)
  if (
    address.includes('VF') || 
    address.includes('VODAFONE') || 
    sms.includes('فودافون كاش') || 
    sms.includes('تم استلام مبلغ') && sms.includes('الرقم المرجعي')
  ) {
    const amountMatch = sms.match(/(?:مبلغ|قيمة|تحويل)?\s*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)/i) ||
                        sms.match(/([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)/i);
    const refMatch = sms.match(/(?:الرقم المرجعي|رقم العملية|مرجعي|ref(?:erence)?)\s*(?:هو|:)?\s*([0-9a-zA-Z]+)/i);
    const senderMatch = sms.match(/(?:من رقم|من محفظة|من)\s*([0-9]{11})/i);

    const amount = amountMatch ? parseFloat(amountMatch[1].replace(/,/g, '')) : null;
    const refId = refMatch ? refMatch[1].trim() : `VF-${Date.now()}`;
    const sender = senderMatch ? senderMatch[1].trim() : null;

    if (amount && amount > 0) {
      return {
        provider: 'vodafone_cash',
        providerName: 'فودافون كاش (Vodafone Cash)',
        amount,
        sender,
        reference_id: refId,
        raw_message: sms,
        received_at: new Date().toISOString()
      };
    }
  }

  // 2. InstaPay (IPN) & Bank Instant Transfers
  if (
    address.includes('INSTAPAY') || 
    address.includes('IPN') || 
    address.includes('NBE') || 
    address.includes('CIB') || 
    address.includes('BM') || 
    address.includes('QNB') || 
    sms.includes('انستاباي') || 
    sms.includes('إنستاباي') || 
    sms.includes('تحويل لحظي') || 
    sms.includes('IPN')
  ) {
    const amountMatch = sms.match(/(?:بمبلغ|مبلغ|قيمة)?\s*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|جم|EGP)/i) ||
                        sms.match(/(?:EGP|LE)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i);
    const refMatch = sms.match(/(?:الرقم المرجعي|رقم العملية|المرجعي|Ref(?:erence)?(?:\s*No)?)\s*(?:هو|:)?\s*([0-9a-zA-Z_\-]+)/i);
    const senderMatch = sms.match(/(?:من|From)\s*([a-zA-Z0-9._\-@]+(?:@instapay)?)/i);

    const amount = amountMatch ? parseFloat(amountMatch[1].replace(/,/g, '')) : null;
    const refId = refMatch ? refMatch[1].trim() : `IPA-${Date.now()}`;
    const sender = senderMatch ? senderMatch[1].trim() : null;

    if (amount && amount > 0) {
      return {
        provider: 'instapay',
        providerName: 'إنستاباي (InstaPay IPN)',
        amount,
        sender,
        reference_id: refId,
        raw_message: sms,
        received_at: new Date().toISOString()
      };
    }
  }

  // 3. Orange Money (Orange Cash)
  if (address.includes('ORANGE') || sms.includes('أورانج كاش') || sms.includes('اورانج كاش')) {
    const amountMatch = sms.match(/([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)/i);
    const refMatch = sms.match(/(?:الرقم المرجعي|رقم العملية|مرجعي)\s*(?:هو|:)?\s*([0-9a-zA-Z]+)/i);
    const senderMatch = sms.match(/(?:من رقم|من)\s*([0-9]{11})/i);

    const amount = amountMatch ? parseFloat(amountMatch[1].replace(/,/g, '')) : null;
    const refId = refMatch ? refMatch[1].trim() : `ORG-${Date.now()}`;

    if (amount && amount > 0) {
      return {
        provider: 'orange_cash',
        providerName: 'أورانج كاش (Orange Money)',
        amount,
        sender: senderMatch ? senderMatch[1].trim() : null,
        reference_id: refId,
        raw_message: sms,
        received_at: new Date().toISOString()
      };
    }
  }

  // 4. Etisalat Cash
  if (address.includes('ETISALAT') || sms.includes('اتصالات كاش') || sms.includes('إتصالات كاش')) {
    const amountMatch = sms.match(/([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP)/i);
    const refMatch = sms.match(/(?:الرقم المرجعي|رقم العملية|مرجعي)\s*(?:هو|:)?\s*([0-9a-zA-Z]+)/i);
    const senderMatch = sms.match(/(?:من رقم|من)\s*([0-9]{11})/i);

    const amount = amountMatch ? parseFloat(amountMatch[1].replace(/,/g, '')) : null;
    const refId = refMatch ? refMatch[1].trim() : `ETS-${Date.now()}`;

    if (amount && amount > 0) {
      return {
        provider: 'etisalat_cash',
        providerName: 'اتصالات كاش (Etisalat Cash)',
        amount,
        sender: senderMatch ? senderMatch[1].trim() : null,
        reference_id: refId,
        raw_message: sms,
        received_at: new Date().toISOString()
      };
    }
  }

  // Generic Fallback Regex for any money transfer SMS
  const genericAmountMatch = sms.match(/(?:استلام|تم تحويل|إيداع|وارد|مبلغ)\s*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|EGP|جم)/i);
  const genericRefMatch = sms.match(/(?:مرجعي|العملية|ref)\s*[:#\-]?\s*([a-zA-Z0-9]+)/i);

  if (genericAmountMatch) {
    const amount = parseFloat(genericAmountMatch[1].replace(/,/g, ''));
    return {
      provider: 'generic_transfer',
      providerName: 'تحويل بنكي / محفظة',
      amount,
      sender: senderAddress || 'مجهول',
      reference_id: genericRefMatch ? genericRefMatch[1] : `GEN-${Date.now()}`,
      raw_message: sms,
      received_at: new Date().toISOString()
    };
  }

  return null;
}
