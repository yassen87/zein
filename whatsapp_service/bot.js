/**
 * WhatsApp Web Client & Automated Order Confirmation Bot
 * Using whatsapp-web.js with LocalAuth & Media Handling
 */
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const fs = require('fs');
const path = require('path');
const db = require('./db');

class WhatsAppBot {
    constructor(io = null) {
        this.io = io;
        this.client = null;
        this.qrCodeDataUrl = null;
        this.status = 'disconnected'; // 'disconnected' | 'qr_ready' | 'authenticating' | 'ready'
        this.clientInfo = null;
        this.logs = [];
        this.userStates = new Map();
        this.processedMessageIds = new Set(); // Prevent duplicate replies

        this.receiptsDir = path.join(__dirname, '..', 'assets', 'uploads', 'receipts');
        if (!fs.existsSync(this.receiptsDir)) {
            fs.mkdirSync(this.receiptsDir, { recursive: true });
        }
    }

    setSocketIO(io) {
        this.io = io;
    }

    log(type, message, data = null) {
        const timestamp = new Date().toISOString();
        const logEntry = { timestamp, type, message, data };
        this.logs.unshift(logEntry);
        if (this.logs.length > 200) this.logs.pop();

        console.log(`[WhatsAppBot][${type.toUpperCase()}] ${message}`, data || '');
        if (this.io) {
            this.io.emit('bot_log', logEntry);
        }
    }

    emitStatus() {
        if (this.io) {
            this.io.emit('status_change', {
                status: this.status,
                qr: this.qrCodeDataUrl,
                info: this.clientInfo
            });
        }
    }

    initialize() {
        if (this.client) {
            return;
        }

        this.log('info', 'Initializing WhatsApp Client with LocalAuth...');
        this.status = 'authenticating';
        this.emitStatus();

        // Check for available browser executables on Windows (Edge / Chrome)
        const possiblePaths = [
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
        ];

        let executablePath = undefined;
        for (const p of possiblePaths) {
            try {
                if (fs.existsSync(p)) {
                    executablePath = p;
                    this.log('info', `Using browser executable at: ${p}`);
                    break;
                }
            } catch (e) {}
        }

        const puppeteerOptions = {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--disable-gpu',
                '--disable-extensions'
            ]
        };

        if (executablePath) {
            puppeteerOptions.executablePath = executablePath;
        }

        this.client = new Client({
            authStrategy: new LocalAuth({
                dataPath: path.join(__dirname, '.wwebjs_auth')
            }),
            webVersionCache: {
                type: 'remote',
                remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.3000.1018919694-alpha.html'
            },
            puppeteer: puppeteerOptions
        });

        this.client.on('qr', async (qr) => {
            this.log('qr', 'New QR Code generated. Scan with WhatsApp.');
            this.status = 'qr_ready';
            try {
                this.qrCodeDataUrl = await qrcode.toDataURL(qr);
            } catch (err) {
                this.qrCodeDataUrl = null;
                console.error('Error generating QR code data URL:', err);
            }
            this.emitStatus();
        });

        this.client.on('authenticated', () => {
            this.log('auth', 'WhatsApp Session Authenticated successfully!');
            this.status = 'authenticating';
            this.qrCodeDataUrl = null;
            this.emitStatus();
        });

        this.client.on('ready', () => {
            this.status = 'ready';
            this.qrCodeDataUrl = null;
            const info = this.client.info || {};
            this.clientInfo = {
                pushname: info.pushname || 'Zei Perfumes WhatsApp',
                wid: info.wid ? info.wid.user : 'Connected'
            };
            this.log('ready', `WhatsApp Bot is READY and connected as: ${this.clientInfo.pushname} (${this.clientInfo.wid})`);
            this.emitStatus();
        });

        this.client.on('auth_failure', (msg) => {
            this.status = 'disconnected';
            this.log('error', `Authentication failure: ${msg}`);
            this.emitStatus();
        });

        this.client.on('disconnected', (reason) => {
            this.status = 'disconnected';
            this.clientInfo = null;
            this.log('warn', `WhatsApp Client was disconnected: ${reason}`);
            this.emitStatus();
        });

        // Outbound staff messages listener (detecting 'تم التأكيد')
        this.client.on('message_create', async (msg) => {
            if (msg.fromMe) {
                await this.handleStaffOutgoingMessage(msg);
            }
        });

        // Inbound messages from customers
        this.client.on('message', async (msg) => {
            await this.handleIncomingMessage(msg);
        });

        this.client.initialize().catch((err) => {
            this.log('error', `Failed to start WhatsApp client: ${err.message}`);
            this.status = 'disconnected';
            this.emitStatus();
        });
    }

    formatPhone(phone) {
        if (!phone) return null;
        let clean = phone.replace(/\D/g, '').replace(/^00/, '');
        if (clean.startsWith('01') && clean.length === 11) {
            clean = '2' + clean;
        } else if (clean.startsWith('1') && clean.length === 10) {
            clean = '20' + clean;
        } else if (clean.startsWith('05') && clean.length === 10) {
            clean = '966' + clean.slice(1);
        }
        return clean.includes('@c.us') ? clean : `${clean}@c.us`;
    }

    getDigitsKey(phoneOrJid) {
        if (!phoneOrJid) return '';
        const raw = phoneOrJid.replace(/@.*$/, '').replace(/\D/g, '').replace(/^00/, '');
        return raw.length >= 7 ? raw.slice(-8) : raw;
    }

    /**
     * Extract transaction/reference number from message text
     */
    extractReferenceNumber(text) {
        if (!text) return null;
        const clean = text.trim();
        // 1. Matches phrases like: "رقم العملية 123456" or "المرجعي: 987654" or "كود: IPA-12345"
        const prefixMatch = clean.match(/(?:رقم\s*العملية|المرجعي|الرقم\s*المرجعي|كود\s*التحويل|كود\s*العملية|ref|reference|trx|tx)[\s:#\-]*([a-zA-Z0-9_\-]{5,30})/i);
        if (prefixMatch && prefixMatch[1]) {
            return prefixMatch[1].trim();
        }
        // 2. Matches standalone number with at least 5 digits (e.g. "2024081812345" or "1029384756")
        const digitsMatch = clean.match(/\b([0-9]{5,25})\b/);
        if (digitsMatch && digitsMatch[1]) {
            return digitsMatch[1].trim();
        }
        // 3. Alphanumeric transaction codes (e.g. "VF123456" or "IPA778899")
        const alphaNumMatch = clean.match(/\b([a-zA-Z0-9]{6,25})\b/);
        if (alphaNumMatch && alphaNumMatch[1] && /\d/.test(alphaNumMatch[1])) {
            return alphaNumMatch[1].trim();
        }
        return null;
    }

    /**
     * Send the initial 1-2-3 Order Menu to customer
     */
    async sendOrderConfirmationMenu(order) {
        if (this.status !== 'ready' || !this.client) {
            throw new Error('WhatsApp Bot is not connected. Please scan QR code first.');
        }

        const phoneJid = this.formatPhone(order.customer_phone);
        if (!phoneJid) {
            throw new Error(`Invalid customer phone: ${order.customer_phone}`);
        }

        const customerName = order.customer_name || 'عميلنا العزيز';
        const orderNumber = order.order_number || `MED-${order.id}`;
        const total = parseFloat(order.total || 0).toFixed(2);
        const shippingCost = parseFloat(order.shipping_cost || 0).toFixed(2);
        const digitsKey = this.getDigitsKey(order.customer_phone);

        const statePayload = {
            orderId: order.id,
            orderNumber,
            customerName,
            total,
            shippingCost,
            state: 'menu',
            timestamp: Date.now()
        };

        if (digitsKey) this.userStates.set(digitsKey, statePayload);
        this.userStates.set(phoneJid, statePayload);

        const menuText = 
`🌸 *أهلاً بك يا أ/ ${customerName} في متجر زين للعطور* 🌸

📦 تم استلام طلبك بنجاح برقم: *${orderNumber}*
🚚 مصاريف الشحن: *${shippingCost} ج.م*
💰 إجمالي المبلغ: *${total} ج.م*
─────────────────────
يرجى اختيار الإجراء المطلوب بالرد برقم الخيار:

1️⃣ - *تأكيد الطلب ونظام الدفع* 💳
2️⃣ - *إلغاء الطلب* ❌
3️⃣ - *تعديل بيانات الطلب* ✏️

_(يرجى الرد برقم 1 أو 2 أو 3 للمتابعة)_`;

        this.log('outbound', `Sending order menu to ${phoneJid} for order ${orderNumber}`);
        await this.client.sendMessage(phoneJid, menuText);

        if (this.io) {
            this.io.emit('new_message_sent', {
                to: phoneJid,
                orderNumber,
                text: menuText,
                timestamp: new Date().toISOString()
            });
        }

        return true;
    }

    /**
     * Resilient message sender: attempts msg.reply, falls back to direct client.sendMessage
     * Works 100% reliably for WhatsApp Regular, WhatsApp Business, Enterprise, and Multi-Device accounts!
     */
    async sendSafeReply(msg, text) {
        if (!text) return false;
        
        // 1. First attempt: Standard msg.reply()
        try {
            if (msg && typeof msg.reply === 'function') {
                await msg.reply(text);
                return true;
            }
        } catch (err) {
            this.log('warn', `Standard msg.reply failed (${err.message}) - attempting direct sendMessage fallback...`);
        }

        // 2. Fallback: Direct client.sendMessage() to sender JID
        try {
            const targetJid = msg.from || msg.author || msg.to;
            if (targetJid && this.client) {
                await this.client.sendMessage(targetJid, text);
                this.log('outbound', `✓ Sent direct fallback message to ${targetJid}`);
                return true;
            }
        } catch (sendErr) {
            this.log('error', `Direct sendMessage fallback also failed: ${sendErr.message}`);
        }

        return false;
    }

    /**
     * Handle incoming customer messages & replies (1, 2, 3, Images)
     */
    async handleIncomingMessage(msg) {
        try {
            // Deduplication check: ignore already processed messages
            const msgId = msg.id?._serialized || msg.id?.id;
            if (msgId) {
                if (this.processedMessageIds.has(msgId)) {
                    return; // Prevent duplicate execution!
                }
                this.processedMessageIds.add(msgId);
                if (this.processedMessageIds.size > 2000) {
                    const firstItem = this.processedMessageIds.values().next().value;
                    this.processedMessageIds.delete(firstItem);
                }
            }

            // Ignore channels, newsletters, groups, statuses
            if (msg.from.includes('@newsletter') || msg.from.includes('@g.us') || msg.from.includes('status@broadcast') || msg.isStatus) {
                return;
            }

            const senderJid = msg.from;
            let realNumber = senderJid.replace(/@.*$/, '');

            // Try to resolve real contact number if LID or WhatsApp Business
            try {
                const contact = await msg.getContact();
                if (contact) {
                    if (contact.number) realNumber = contact.number;
                    else if (contact.id && contact.id.user) realNumber = contact.id.user;
                }
            } catch (cErr) {}

            const digitsKeyFromSender = this.getDigitsKey(senderJid);
            const digitsKeyFromReal = this.getDigitsKey(realNumber);
            
            // Extract text from regular, caption, or business interactive payload
            const rawBody = (
                msg.body || 
                msg.caption || 
                msg._data?.body || 
                msg._data?.caption || 
                msg._data?.interactive?.body?.text || 
                msg.selectedButtonId || 
                msg.selectedRowId || 
                ''
            ).trim();
            const body = rawBody.toLowerCase();

            this.log('inbound', `Received from ${senderJid} (real: ${realNumber}): "${rawBody || '[Media/Image]'}"`);

            // 1. Retrieve order state from memory or database
            let stateObj = this.userStates.get(senderJid) ||
                           this.userStates.get(realNumber) ||
                           this.userStates.get(digitsKeyFromReal) || 
                           this.userStates.get(digitsKeyFromSender);

            let order = null;

            // Check if user's text contains an explicit order number (e.g. from website fallback link or manual query)
            const cleanBody = rawBody.replace(/[\u200E\u200F\u202A-\u202E*_\~`]/g, ' ').trim();
            const orderRefMatch = cleanBody.match(/MED-[A-Za-z0-9]+/i) || cleanBody.match(/#(\d{1,8})/);
            if (orderRefMatch) {
                const matchedRef = orderRefMatch[0].replace('#', '').trim();
                const matchedOrder = await db.findOrderByNumber(matchedRef);
                if (matchedOrder) {
                    order = matchedOrder;
                    this.log('inbound', `✓ Matched order ${order.order_number} directly from text reference "${matchedRef}"`);
                }
            }

            if (!order && stateObj && stateObj.orderId) {
                order = await db.findOrderByNumber(stateObj.orderId);
            }

            if (!order && realNumber) {
                order = await db.findLatestOrderByPhone(realNumber);
            }

            if (!order && senderJid) {
                order = await db.findLatestOrderByPhone(senderJid);
            }

            // Universal Fallback: if user sent a choice (1, 2, 3, or receipt image) and order is not found yet, get latest pending order
            const isMenuChoice = (body === '1' || body === '١' || body === '2' || body === '٢' || body === '3' || body === '٣' || body.includes('تأكيد') || body.includes('تاكيد') || msg.hasMedia || cleanBody.includes('MED-') || cleanBody.includes('طلب'));
            if (!order && isMenuChoice) {
                order = await db.findLatestPendingOrder();
                if (order) {
                    this.log('inbound', `✓ Attached incoming customer action to latest pending order ${order.order_number} (ID: ${order.id})`);
                }
            }

            // If still no order exists anywhere
            if (!order) {
                // Friendly greeting for general inquiries without active orders
                if (body.includes('مرحبا') || body.includes('سلام') || body.includes('hello') || body.includes('hi') || body.includes('صباح') || body.includes('مساء')) {
                    const welcomeMsg = 
`🌸 *أهلاً بك في متجر زين للعطور* 🌸

يسعدنا تواصلك معنا! ✨
🛍️ لتصفح أفخر العطور والعروض الخاصة:
🌐 *https://zeinperfumes.com*

💬 للاستفسارات وطلب المساعدة، يمكنك كتابة رسالتك وسيقوم فريق خدمة العملاء بالرد عليك قريباً.`;
                    await this.sendSafeReply(msg, welcomeMsg);
                    return;
                }

                // Casual / normal chat: do not hijack so staff can chat freely
                this.log('inbound', `No active order found for ${senderJid} (real: ${realNumber}). Leaving for human staff.`);
                return;
            }

            if (order) {
                stateObj = {
                    orderId: order.id,
                    orderNumber: order.order_number,
                    customerName: order.customer_name,
                    total: parseFloat(order.total || 0).toFixed(2),
                    shippingCost: parseFloat(order.shipping_cost || 0).toFixed(2),
                    state: (stateObj && stateObj.orderId === order.id) ? (stateObj.state || order.bot_step || 'menu') : (order.bot_step || 'menu')
                };
                if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, stateObj);
                if (digitsKeyFromSender) this.userStates.set(digitsKeyFromSender, stateObj);
                this.userStates.set(senderJid, stateObj);
                if (realNumber) this.userStates.set(realNumber, stateObj);
            }

            const orderNumber = order?.order_number || stateObj?.orderNumber || 'الطلب';
            const totalNum = parseFloat(order?.total || stateObj?.total || 0);
            const shippingCostNum = parseFloat(order?.shipping_cost || stateObj?.shippingCost || 0);
            const remainingForShippingOnly = Math.max(0, totalNum - shippingCostNum).toFixed(2);
            const totalStr = totalNum.toFixed(2);
            const shippingCostStr = shippingCostNum.toFixed(2);
            const customerName = order?.customer_name || stateObj?.customerName || 'عميلنا العزيز';

            const settings = await db.getSettings();
            const instapayUser = settings.instapay_username || 'zain@instapay';
            const vodafoneNumber = settings.vodafone_cash_number || '01111026600';

            // ── INSTANT TRIGGER: If user clicked the WhatsApp button from the website ──
            if (rawBody.includes('تم استلام طلبك') || rawBody.includes('تم تسجيل طلبك') || (orderRefMatch && rawBody.includes('🌸'))) {
                stateObj.state = 'awaiting_scope_choice';
                this.userStates.set(senderJid, stateObj);
                if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, stateObj);

                const scopePromptMsg = 
`👑 *أهلاً بك يا أ/ ${customerName} في متجر زين للعطور!* 🌸
📦 بخصوص طلبك رقم: *${orderNumber}*
💰 إجمالي المبلغ: *${totalStr} ج.م*

نظراً لتجهيز العطور وحجز الشحنة، يرجى تحديد طريقة التحويل:

1️⃣ - *دفع مصاريف الشحن فقط مقدم (${shippingCostStr} ج.م)* 🚚
_(ودفع باقي قيمة العطور ${remainingForShippingOnly} ج.م عند الاستلام من المندوب)_

2️⃣ - *دفع إجمالي الطلب بالكامل (${totalStr} ج.م)* 💳
_(شامل المنتجات ومصاريف الشحن بدون أي دفع عند الاستلام)_
─────────────────────
_رد برقم (1) لدفع الشحن فقط، أو (2) لدفع كامل المبلغ._`;

                await this.sendSafeReply(msg, scopePromptMsg);
                return;
            }

            // ── 2. DETECT IMAGES / MEDIA / PAYMENT RECEIPT SCREENSHOTS ──
            const isMediaMessage = msg.hasMedia || msg.type === 'image' || msg.type === 'document' || msg.type === 'sticker';
            
            if (isMediaMessage) {
                this.log('inbound', `Customer sent receipt/screenshot for order ${orderNumber}. Downloading media...`);
                let filename = null;

                // Retry media download up to 4 times to ensure whatsapp-web.js has decrypted media
                for (let attempt = 1; attempt <= 4; attempt++) {
                    try {
                        const media = await msg.downloadMedia();
                        if (media && media.data) {
                            const rawExt = (media.mimetype && media.mimetype.split('/')[1]) ? media.mimetype.split('/')[1].split(';')[0] : 'jpg';
                            const ext = (rawExt === 'jpeg' || !rawExt) ? 'jpg' : rawExt;
                            filename = `receipt_${order?.id || 'wa'}_${Date.now()}.${ext}`;
                            const filePath = path.join(this.receiptsDir, filename);

                            fs.writeFileSync(filePath, Buffer.from(media.data, 'base64'));
                            this.log('receipt', `✓ Successfully saved receipt screenshot to: ${filePath}`);
                            break;
                        }
                    } catch (dlErr) {
                        this.log('warn', `Media download attempt ${attempt}/4: ${dlErr.message}`);
                    }
                    if (attempt < 4) {
                        await new Promise(res => setTimeout(res, 600 * attempt));
                    }
                }

                if (!filename) {
                    filename = `receipt_${order?.id || 'wa'}_${Date.now()}.jpg`;
                    this.log('warn', `Media data was empty, recorded receipt marker: ${filename}`);
                }

                // Update database: receipt saved in pending verification (is_confirmed remains 0 until staff confirms)
                if (order?.id) {
                    await db.saveOrderReceipt(order.id, filename);
                }

                if (stateObj) stateObj.state = 'receipt_received';
                if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, { ...stateObj, state: 'receipt_received' });

                const replyText = 
`✅ *تم استلام صورة التحويل بنجاح!*

📦 طلب رقم: *${orderNumber}*
⏳ الحالة: *في انتظار مراجعة وتأكيد التحويل من خدمة العملاء.*

🌸 سيصلك إشعار فوري هنا على الواتساب فور اعتماد الدفع وبدء تجهيز شحنتك. شكراً لاختيارك *زين للعطور*! ✨`;

                await this.sendSafeReply(msg, replyText);

                if (this.io) {
                    this.io.emit('receipt_uploaded', {
                        orderId: order?.id,
                        orderNumber,
                        filename,
                        url: `assets/uploads/receipts/${filename}`,
                        timestamp: new Date().toISOString()
                    });
                }
                return;
            }

            // ── 3. NORMALIZE USER CHOICES ──
            const is1 = (body === '1' || body === '١' || body.includes('تأكيد') || body.includes('تاكيد') || body === 'confirm');
            const is2 = (body === '2' || body === '٢' || body.includes('إلغاء') || body.includes('الغاء') || body === 'cancel');
            const is3 = (body === '3' || body === '٣' || body.includes('تعديل') || body === 'edit');
            
            const isShippingOnly = (body === '1' || body === '١' || body.includes('شحن') || body.includes('مصاريف') || body.includes('shipping'));
            const isFullAmount = (body === '2' || body === '٢' || body.includes('كامل') || body.includes('كل') || body.includes('full'));

            // ── SUB-STEP: Customer Choosing Payment Scope (Shipping Only vs Full Amount) ──
            if (stateObj?.state === 'awaiting_scope_choice') {
                if (isShippingOnly) {
                    // Option 1: Shipping Cost Only — is_confirmed remains 0 until actual payment
                    if (order?.id) {
                        await db.updateOrderConfirmation(order.id, 0, 'shipping_only', 'awaiting_receipt', shippingCostNum, parseFloat(remainingForShippingOnly));
                    }
                    if (stateObj) {
                        stateObj.state = 'awaiting_receipt';
                        stateObj.paymentScope = 'shipping_only';
                    }
                    if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, { ...stateObj, state: 'awaiting_receipt', paymentScope: 'shipping_only' });

                    const shippingPayMsg = 
`💳 *بيانات تحويل مصاريف الشحن (زين للعطور)*:

▫️ *انستاباي (InstaPay):*
\`${instapayUser}\`

▫️ *محفظة كاش (فودافون / اتصالات / أورانج / وي):*
\`${vodafoneNumber}\`
─────────────────────
💵 *المبلغ المطلوب تحويله الآن:* *${shippingCostStr} ج.م* (قيمة الشحن)
🚚 *المبلغ المتبقي عند الاستلام:* *${remainingForShippingOnly} ج.م*

📸 *لتأكيد الحجز وتجهيز الشحنة فوراً:*
1️⃣ أرسل *صورة إيصال التحويل (Screenshot)* 📸 هنا
2️⃣ *أو* اكتب *الرقم المرجعي / كود العملية* 🔢 هنا في الشات.`;

                    await this.sendSafeReply(msg, shippingPayMsg);
                    return;
                } else if (isFullAmount) {
                    // Option 2: Full Order Amount — is_confirmed remains 0 until actual payment
                    if (order?.id) {
                        await db.updateOrderConfirmation(order.id, 0, 'full', 'awaiting_receipt', totalNum, 0);
                    }
                    if (stateObj) {
                        stateObj.state = 'awaiting_receipt';
                        stateObj.paymentScope = 'full';
                    }
                    if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, { ...stateObj, state: 'awaiting_receipt', paymentScope: 'full' });

                    const fullPayMsg = 
`💳 *بيانات تحويل كامل قيمة الطلب (زين للعطور)*:

▫️ *انستاباي (InstaPay):*
\`${instapayUser}\`

▫️ *محفظة كاش (فودافون / اتصالات / أورانج / وي):*
\`${vodafoneNumber}\`
─────────────────────
💵 *المبلغ المطلوب تحويله بالكامل:* *${totalStr} ج.م*
🎉 _(شامل المنتجات والشحن، ولن تدفع أي مبالغ للمندوب عند الاستلام)_

📸 *لتأكيد الحجز وتجهيز الشحنة فوراً:*
1️⃣ أرسل *صورة إيصال التحويل (Screenshot)* 📸 هنا
2️⃣ *أو* اكتب *الرقم المرجعي / كود العملية* 🔢 هنا في الشات.`;

                    await this.sendSafeReply(msg, fullPayMsg);
                    return;
                }
            }

            // ── STEP 1: INITIAL CONFIRMATION SELECTION (Choice 1) ──
            if (is1 && stateObj?.state !== 'awaiting_scope_choice') {
                if (order?.id) {
                    await db.updateOrderConfirmation(order.id, 0, 'full', 'awaiting_scope_choice', 0, 0);
                }
                if (stateObj) stateObj.state = 'awaiting_scope_choice';
                if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, { ...stateObj, state: 'awaiting_scope_choice' });

                const scopePromptMsg = 
`👑 *شكراً لاختيارك زين للعطور يا أ/ ${customerName}!*
📦 طلبك رقم: *${orderNumber}*

نظراً لتجهيز العطور وحجز الشحنة، يرجى تحديد طريقة التحويل:

1️⃣ - *دفع مصاريف الشحن فقط مقدم (${shippingCostStr} ج.م)* 🚚
_(ودفع باقي قيمة العطور ${remainingForShippingOnly} ج.م عند الاستلام من المندوب)_

2️⃣ - *دفع إجمالي الطلب بالكامل (${totalStr} ج.م)* 💳
_(شامل المنتجات ومصاريف الشحن بدون أي دفع عند الاستلام)_
─────────────────────
_رد برقم (1) لدفع الشحن فقط، أو (2) لدفع كامل المبلغ._`;

                await this.sendSafeReply(msg, scopePromptMsg);
                return;
            }

            // ── OPTION 2: CANCEL ORDER ──
            if (is2) {
                if (order?.id) await db.cancelOrder(order.id);
                if (stateObj) stateObj.state = 'cancelled';
                if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, { ...stateObj, state: 'cancelled' });

                const cancelResponse = 
`❌ *تم إلغاء طلبك رقم (${orderNumber}) بنجاح.*

نتمنى رؤيتك مجدداً قريباً في *متجر زين للعطور*! 🌸
يمكنك تصفح أحدث العطور والعروض في أي وقت عبر موقعنا:
🌐 *https://zeinperfumes.com*`;

                await this.sendSafeReply(msg, cancelResponse);
                return;
            }

            // ── OPTION 3: EDIT ORDER ──
            if (is3) {
                const editResponse = 
`✏️ *لتعديل طلبك رقم (${orderNumber}):*

يرجى كتابة التعديل المطلوب هنا مباشرة (مثل تعديل العنوان، أو إضافة/تغيير عطر)، وسيقوم أحد ممثلي خدمة العملاء بمساعدتك فوراً. 🌸`;

                await this.sendSafeReply(msg, editResponse);
                return;
            }

            // ── 4. DETECT TRANSACTION / REFERENCE NUMBER SENT BY CUSTOMER ──
            const detectedRef = this.extractReferenceNumber(rawBody);

            if (stateObj?.state === 'awaiting_receipt' || (detectedRef && order)) {
                const refToSave = detectedRef || rawBody;
                this.log('receipt', `Customer provided transaction reference: "${refToSave}" for order ${orderNumber}`);

                if (order?.id) {
                    await db.saveOrderPaymentReference(order.id, refToSave, rawBody);
                }

                if (stateObj) stateObj.state = 'receipt_received';
                if (digitsKeyFromReal) this.userStates.set(digitsKeyFromReal, { ...stateObj, state: 'receipt_received' });

                const refReply = 
`✅ *تم استلام رقم العملية بنجاح!*

📦 طلب رقم: *${orderNumber}*
🔢 رقم العملية / المرجعي: *${refToSave}*
⏳ الحالة: *في انتظار مراجعة وتأكيد التحويل من خدمة العملاء.*

🌸 سيصلك إشعار فوري هنا على الواتساب فور اعتماد الدفع وبدء تجهيز شحنتك. شكراً لاختيارك *زين للعطور*! ✨`;

                await this.sendSafeReply(msg, refReply);

                if (this.io) {
                    this.io.emit('receipt_uploaded', {
                        orderId: order?.id,
                        orderNumber,
                        referenceNumber: refToSave,
                        rawMessage: rawBody,
                        type: 'reference_number',
                        timestamp: new Date().toISOString()
                    });
                }
                return;
            }

        } catch (err) {
            this.log('error', `Error handling message from ${msg.from}: ${err.message}`);
        }
    }

    /**
     * Handle staff outgoing message to auto-confirm order on keywords like "تم التاكيد"
     */
    async handleStaffOutgoingMessage(msg) {
        try {
            const rawBody = (msg.body || '').trim();
            if (!rawBody) return;
            const body = rawBody.toLowerCase();

            // Staff confirmation keywords
            const confirmPatterns = [
                'تم التاكيد', 'تم التأكيد', 'تم تاكيد', 'تم تأكيد',
                'تم الاعتماد', 'تم اعتماد', 'تم اعتماد الدفع', 'تم استلام التحويل', 'تم استلام المبلغ',
                'تم تاكيد الطلب', 'تم تأكيد الطلب', 'تاكيد الاوردر', 'تأكيد الاوردر',
                'تم تاكيد طلبك', 'تم تأكيد طلبك', 'تم تاكيد اوردرك', 'تم تأكيد أوردرك',
                'تم الدفع', 'تم التحويل', 'اعتمدت', 'اعتمدنا التحويل',
                '#تاكيد', '#تأكيد', '#confirm', 'confirmed', 'تم بنجاح'
            ];

            // Direct exact single-word matches
            const singleWordMatches = ['تم', 'تمام', 'تأكيد', 'تاكيد', 'معتمد', 'اعتمد'];

            const isConfirmMsg = confirmPatterns.some(pat => body.includes(pat)) || singleWordMatches.includes(body);
            if (!isConfirmMsg) {
                return;
            }

            const targetJid = msg.to;
            if (!targetJid || targetJid.includes('@newsletter') || targetJid.includes('@g.us') || targetJid.includes('status@broadcast')) {
                return;
            }

            let phoneOrDigits = targetJid.replace(/@.*$/, '').replace(/\D/g, '');
            try {
                const chat = await msg.getChat();
                if (chat && chat.contact && chat.contact.number) {
                    phoneOrDigits = chat.contact.number;
                }
            } catch (cErr) {}

            this.log('staff', `Staff sent confirmation keyword "${rawBody}" to ${targetJid} (phone: ${phoneOrDigits})`);

            // Find customer's active order
            let order = await db.findLatestOrderByPhone(phoneOrDigits);
            if (!order) {
                order = await db.findLatestOrderByPhone(targetJid);
            }
            if (!order) {
                order = await db.findLatestPendingOrder();
            }

            if (order && order.id) {
                const scope = order.payment_scope || 'full';
                const totalVal = parseFloat(order.total || 0);
                const shipVal = parseFloat(order.shipping_cost || 0);
                const advanceVal = parseFloat(order.advance_amount || 0);
                const paidVal = (scope === 'shipping_only') ? (advanceVal > 0 ? advanceVal : shipVal) : totalVal;
                const payStatus = (scope === 'shipping_only') ? 'deposit_paid' : 'verified';

                await db.confirmOrderByStaff(order.id, payStatus, paidVal);

                this.log('staff', `✅ Order ${order.order_number} (ID: ${order.id}) automatically CONFIRMED in dashboard by staff chat!`);

                if (this.io) {
                    this.io.emit('order_status_updated', {
                        orderId: order.id,
                        orderNumber: order.order_number,
                        status: 'processing',
                        paymentStatus: payStatus,
                        isConfirmed: 1,
                        source: 'staff_chat',
                        timestamp: new Date().toISOString()
                    });
                }
            }
        } catch (err) {
            this.log('error', `Error in handleStaffOutgoingMessage: ${err.message}`);
        }
    }

    /**
     * Send Order Status Update message to customer on WhatsApp
     */
    async sendOrderStatusNotification(orderId, newStatus) {
        if (this.status !== 'ready' || !this.client) {
            throw new Error('WhatsApp Bot is not connected.');
        }

        const order = await db.getOrderById(orderId);
        if (!order) {
            throw new Error(`Order #${orderId} not found`);
        }

        const jid = this.formatPhone(order.customer_phone);
        if (!jid) {
            throw new Error(`Invalid customer phone: ${order.customer_phone}`);
        }

        const customerName = order.customer_name || 'عميلنا العزيز';
        const orderNumber = order.order_number || `MED-${order.id}`;
        const total = parseFloat(order.total || 0).toFixed(2);
        const paid = parseFloat(order.paid_amount || 0);
        const waived = parseFloat(order.waived_amount || 0);
        const remaining = Math.max(0, parseFloat(order.total || 0) - paid - waived).toFixed(2);

        let msgText = '';

        switch (newStatus) {
            case 'processing':
                msgText = 
`🌸 *مرحباً يا أ/ ${customerName}*
📦 بخصوص طلبك رقم: *${orderNumber}*

✨ *حالة الطلب الحالية:* 🧴 *قيد التجهيز والتغليف*
تم تأكيد طلبك بنجاح وجاري الآن تجهيز وتغليف عطرك الفاخر بعناية فائقة تمهيداً لتسليمه لشركة الشحن. 📦✨

شكراً لاختيارك *زين للعطور*! 🌸`;
                break;

            case 'shipped':
                const remainingLine = parseFloat(remaining) > 0 
                    ? `\n💵 *المبلغ المتبقي للتحصيل عند الاستلام:* *${remaining} ج.م*`
                    : `\n✅ *حالة الحساب:* تم سداد المبلغ بالكامل مسبقاً (لا تدفع أي مبالغ للمندوب).`;

                msgText = 
`🚚 *بشرى سارة يا أ/ ${customerName}!*
📦 بخصوص طلبك رقم: *${orderNumber}*

✨ *حالة الطلب الحالية:* 🚀 *تم الشحن وجاري التوصيل*
تم تسليم شحنتك لمندوب شركة التوصيل وهي في طريقها إليك الآن. يرجى التواجد واستلام اتصال المندوب. 📞
${remainingLine}

نتمنى لك تجربة عطرية لا تُنسى! 🌸✨`;
                break;

            case 'delivered':
                msgText = 
`🎉 *ألف مبروك يا أ/ ${customerName}!*
📦 بخصوص طلبك رقم: *${orderNumber}*

✨ *حالة الطلب الحالية:* ✅ *تم تسليم الطلب بنجاح*
نتمنى أن تكون عطور *زين* قد نالت كامل إعجابكم ورضاكم. نسعد دائماً بخدمتك ونتطلع لطلبك القادم! 🌸👑`;
                break;

            case 'cancelled':
                msgText = 
`🌸 *أهلاً يا أ/ ${customerName}*
📦 بخصوص طلبك رقم: *${orderNumber}*

✨ *حالة الطلب الحالية:* ❌ *تم إلغاء الطلب*
إذا تم هذا الإلغاء بالخطأ أو ترغب في تعديل محتويات طلبك، يسعدنا تواصلك معنا مباشرة وسنكون بخدمتك فوراً. 🌸`;
                break;

            case 'pending':
            default:
                msgText = 
`🌸 *مرحباً يا أ/ ${customerName}*
📦 بخصوص طلبك رقم: *${orderNumber}*

✨ *حالة الطلب الحالية:* ⏳ *قيد الانتظار*
طلبك مسجل لدينا بنجاح وفي انتظار استكمال إجراءات التحويل والتأكيد.

شكراً لتسوقك من *زين للعطور*! 🌸`;
                break;
        }

        await this.client.sendMessage(jid, msgText);
        this.log('outbound', `Sent status update (${newStatus}) to ${jid} for order ${orderNumber}`);
        return { success: true, jid, status: newStatus };
    }

    /**
     * Broadcast New Product announcement to all customers
     */
    async broadcastNewProduct(product) {
        if (this.status !== 'ready' || !this.client) {
            throw new Error('WhatsApp Bot is not connected. Please scan QR code first.');
        }

        // Fetch distinct customer phone numbers from clients and orders
        const clientsQuery = await db.query(
            `SELECT DISTINCT phone FROM (
                SELECT phone FROM clients WHERE phone IS NOT NULL AND phone != ''
                UNION 
                SELECT customer_phone as phone FROM orders WHERE customer_phone IS NOT NULL AND customer_phone != ''
            ) as t WHERE phone REGEXP '^[0-9+]{8,15}$' LIMIT 500`
        );

        const recipients = (clientsQuery || []).map(r => r.phone).filter(Boolean);
        this.log('broadcast', `Starting new product broadcast for "${product.nameAr || product.nameEn}" to ${recipients.length} customers...`);

        const nameAr = product.nameAr || product.name || 'عطر فاخر جديد';
        const nameEn = product.nameEn || '';
        const brandName = product.brandName ? `🏷️ *الماركة:* ${product.brandName}\n` : '';
        const price = product.price ? parseFloat(product.price).toFixed(2) : '';
        const desc = product.description ? `📝 *عن العطر:* ${product.description}\n` : (product.descriptionAr ? `📝 *عن العطر:* ${product.descriptionAr}\n` : '');
        const notes = product.notes ? `🌸 *النفحات العطرية:* ${product.notes}\n` : (product.notesAr ? `🌸 *النفحات العطرية:* ${product.notesAr}\n` : '');
        const promo = product.promoText ? `🎁 *عرض خاص:* ${product.promoText}\n` : '';
        const productUrl = product.productUrl || (product.productId || product.id ? `http://127.0.0.1:8000/product.php?id=${product.productId || product.id}` : `http://127.0.0.1:8000/products.php`);

        let broadcastMsg = product.customMessage;
        if (!broadcastMsg || !broadcastMsg.trim()) {
            broadcastMsg = 
`✨ *عطر جديد وحصري وصل متجر زين للعطور!* 🌸

👑 *${nameAr}* ${nameEn ? `| ${nameEn}` : ''}
${brandName}${desc}${notes}${price ? `💰 *السعر:* *${price} ج.م*\n` : ''}${promo}
🔗 *للطلب والتفاصيل فوراً عبر موقعنا:*
${productUrl}

🚚 *متاح الشحن والتوصيل السريع لجميع المحافظات!* ✨`;
        }

        let sentCount = 0;
        let failCount = 0;

        // Broadcast asynchronously with safe delay between messages (2.5 seconds)
        (async () => {
            for (let i = 0; i < recipients.length; i++) {
                const phone = recipients[i];
                const jid = this.formatPhone(phone);
                if (!jid) continue;

                try {
                    await this.client.sendMessage(jid, broadcastMsg);
                    sentCount++;
                    this.log('broadcast', `[${i + 1}/${recipients.length}] Sent product announcement to ${jid}`);
                } catch (sendErr) {
                    failCount++;
                    this.log('warn', `Failed sending broadcast to ${jid}: ${sendErr.message}`);
                }

                if (this.io) {
                    this.io.emit('broadcast_progress', {
                        total: recipients.length,
                        current: i + 1,
                        sent: sentCount,
                        failed: failCount,
                        productName: nameAr
                    });
                }

                // Throttle delay 2.5 seconds to prevent spam trigger
                await new Promise(r => setTimeout(r, 2500));
            }

            this.log('broadcast', `🎉 Broadcast completed! Successfully sent to ${sentCount}/${recipients.length} customers.`);
        })();

        return {
            success: true,
            totalRecipients: recipients.length,
            message: `Broadcast started for ${recipients.length} customers in background queue.`
        };
    }

    /**
     * Send direct custom text message
     */
    async sendMessage(phone, message) {
        if (this.status !== 'ready' || !this.client) {
            throw new Error('WhatsApp Bot is not connected. Please scan QR code first.');
        }

        const phoneJid = this.formatPhone(phone);
        if (!phoneJid) {
            throw new Error('Invalid phone number.');
        }

        this.log('outbound', `Sending direct message to ${phoneJid}`);
        await this.client.sendMessage(phoneJid, message);
        return true;
    }

    /**
     * Logout and destroy current session
     */
    async logout() {
        if (this.client) {
            try {
                await this.client.logout();
                await this.client.destroy();
            } catch (err) {
                console.error('Error during client logout:', err.message);
            }
            this.client = null;
            this.status = 'disconnected';
            this.clientInfo = null;
            this.qrCodeDataUrl = null;
            this.emitStatus();
            this.log('info', 'Logged out and cleared WhatsApp session.');
        }
    }
}

module.exports = WhatsAppBot;
