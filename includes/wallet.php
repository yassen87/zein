<?php
declare(strict_types=1);

// 1. استدعاء ملف الإعدادات الخاص بموقعك للحفاظ على الجلسات والاتصال بالداتا بيز
require_once __DIR__ . '/config.php';

// الحصول على اتصال PDO المعرف في موقعك
$pdo = medal_pdo();

if ($pdo === null) {
    die("تعذر الاتصال بقاعدة البيانات الخاصة بالمتجر.");
}

// 2. إنشاء جدول المحفظة المستقل أوتوماتيكياً دون المساس بأي جدول قائم
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_wallets (
        user_id INT PRIMARY KEY,
        balance DECIMAL(10,2) DEFAULT 0.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Throwable $e) {
    error_log("Wallet Table Creation Error: " . $e->getMessage());
}

// 3. تحديد رقم العميل الحالي من الجلسة (Client Session)
$clientId = $_SESSION['client_id'] ?? 1; // يعتمد client_id المسجل أو 1 كافتراضي

// جلب رصيد المحفظة الحالي للعميل
$stmt = $pdo->prepare("SELECT balance FROM user_wallets WHERE user_id = ?");
$stmt->execute([$clientId]);
$wallet = $stmt->fetch();

if (!$wallet) {
    $pdo->prepare("INSERT INTO user_wallets (user_id, balance) VALUES (?, 0.00)")->execute([$clientId]);
    $currentBalance = 0.00;
} else {
    $currentBalance = (float)$wallet['balance'];
}

// -------------------------------------------------------------------------
// مفاتيح Paymob الخاصة بالمتجر
// -------------------------------------------------------------------------
$secretKey     = getenv('PAYMOB_SECRET_KEY') ?: "PAYMOB_SECRET_KEY_HERE"; 
$publicKey     = getenv('PAYMOB_PUBLIC_KEY') ?: "PAYMOB_PUBLIC_KEY_HERE"; 
$integrationId = (int)(getenv('PAYMOB_INTEGRATION_ID') ?: 5814216);

// =========================================================================
// 4. معالجة التوجيه بعد نجاح الدفع على Paymob
// =========================================================================
if (isset($_GET['success']) && $_GET['success'] === 'true' && isset($_GET['amount_cents'])) {
    $addedAmount = ((float)$_GET['amount_cents']) / 100;
    
    // إيداع المبلغ في محفظة العميل
    $stmt = $pdo->prepare("UPDATE user_wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$addedAmount, $clientId]);
    
    // العودة للصفحة الرئيسية للمحفظة
    header("Location: " . url('wallet.php?status=success&amount=' . $addedAmount));
    exit;
}

// =========================================================================
// 5. معالجة طلبات הـ API (الباك إند)
// =========================================================================

// أ. طلب شحن المحفظة
if (isset($_GET['action']) && $_GET['action'] === 'topup_wallet') {
    header('Content-Type: application/json');
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'مبلغ غير صالح']);
        exit;
    }

    $redirectUrl = url('wallet.php');

    $payload = [
        "amount" => (int)($amount * 100), // المبلغ بالقرش
        "currency" => "EGP",
        "payment_methods" => [(int)$integrationId],
        "billing_data" => [
            "first_name"   => "Zain",
            "last_name"    => "Perfumes",
            "email"        => "customer@zainperfumes.com",
            "phone_number" => "+201000000000"
        ],
        "customer" => [
            "first_name" => "Zain",
            "last_name"  => "Perfumes",
            "email"      => "customer@zainperfumes.com"
        ],
        "redirection_url" => $redirectUrl
    ];

    $ch = curl_init("https://accept.paymob.com/v1/intention/");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Token " . $secretKey,
            "Content-Type: application/json"
        ]
    ]);

    $responseRaw = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($responseRaw, true);

    if (isset($data['client_secret'])) {
        echo json_encode([
            'success'       => true,
            'client_secret' => $data['client_secret'],
            'public_key'    => $publicKey
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $data]);
    }
    exit;
}

// ب. تجربة شراء عطر بالمحفظة
if (isset($_GET['action']) && $_GET['action'] === 'pay_with_wallet') {
    header('Content-Type: application/json');
    $price = (float)($_POST['price'] ?? 0);
    $itemName = $_POST['item_name'] ?? 'عطر فاخر';

    if ($currentBalance >= $price) {
        $newBalance = $currentBalance - $price;
        $stmt = $pdo->prepare("UPDATE user_wallets SET balance = ? WHERE user_id = ?");
        $stmt->execute([$newBalance, $clientId]);

        echo json_encode(['success' => true, 'new_balance' => $newBalance]);
    } else {
        echo json_encode(['success' => false, 'message' => 'رصيد المحفظة غير كافٍ! قم بشحن رصيدك أولاً.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo esc(current_lang()); ?>" dir="<?php echo current_lang() === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محفظتي | <?php echo esc(get_site_name()); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #845ef7;
            --primary-hover: #7048e8;
            --success: #20c997;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #212529;
            --border: #dee2e6;
        }

        * { box-sizing: border-box; font-family: 'Cairo', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); margin: 0; padding: 30px 15px; }

        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 26px; margin-bottom: 5px; color: #2b2c34; }
        .header p { color: #6c757d; font-size: 14px; margin: 0; }

        .container {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        @media (max-width: 800px) { .container { grid-template-columns: 1fr; } }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .card-title {
            font-size: 17px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .balance-card {
            background: linear-gradient(135deg, #7952b3 0%, #613d98 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
        }

        .balance-amount { font-size: 30px; font-weight: 700; }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-purple { background: var(--primary); color: white; }
        .btn-purple:hover { background: var(--primary-hover); }
        .btn-success { background: var(--success); color: white; }

        .perfume-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .perfume-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            background: #fff;
        }

        .perfume-icon { font-size: 35px; margin-bottom: 8px; }
        .perfume-name { font-weight: 600; font-size: 15px; margin-bottom: 5px; }
        .perfume-price { color: var(--primary); font-weight: 700; margin-bottom: 10px; }

        #paymob-container { margin-top: 15px; }
        iframe { border: none; border-radius: 10px; width: 100%; height: 480px; }
    </style>
</head>
<body>

<div class="header">
    <h1>محفظة <?php echo esc(SITE_NAME); ?> الرقمية 🌸</h1>
    <p>اشحن محفظتك واستمتع بالشراء السريع للعطور بدون بطاقات</p>
</div>

<div class="container">
    <!-- كارت المحفظة للشحن -->
    <div class="card">
        <div class="card-title">💳 رصيد المحفظة</div>
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 8px;">حساب العميل: #<?php echo $clientId; ?></div>
        
        <div class="balance-card">
            <div style="font-size: 12px; opacity: 0.9;">الرصيد المتاح بالمحفظة</div>
            <div class="balance-amount" id="wallet-balance"><?php echo format_price($currentBalance); ?></div>
        </div>

        <input type="number" id="topup-amount" class="form-control" placeholder="مبلغ الشحن (مثلاً: 500)">
        <button class="btn btn-purple" onclick="startTopup()">شحن المحفظة عبر Paymob</button>

        <div id="paymob-container"></div>
    </div>

    <!-- كارت المنتجات التجريبي -->
    <div class="card">
        <div class="card-title">✨ عطور مختارة للتجربة</div>
        <div class="perfume-grid">
            
            <div class="perfume-card">
                <div class="perfume-icon">🪵</div>
                <div class="perfume-name">عطر العود الفاخر</div>
                <div class="perfume-price"><?php echo format_price(450); ?></div>
                <button class="btn btn-success" onclick="buyPerfume('عطر العود الفاخر', 450)">دفع سريع بالمحفظة</button>
            </div>

            <div class="perfume-card">
                <div class="perfume-icon">🌹</div>
                <div class="perfume-name">عطر الورد الفرنسي</div>
                <div class="perfume-price"><?php echo format_price(300); ?></div>
                <button class="btn btn-success" onclick="buyPerfume('عطر الورد الفرنسي', 300)">دفع سريع بالمحفظة</button>
            </div>

            <div class="perfume-card">
                <div class="perfume-icon">🧴</div>
                <div class="perfume-name">مسك زين الخاص</div>
                <div class="perfume-price"><?php echo format_price(250); ?></div>
                <button class="btn btn-success" onclick="buyPerfume('مسك زين الخاص', 250)">دفع سريع بالمحفظة</button>
            </div>

        </div>
    </div>
</div>

<script>
<?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
    alert('🎉 تم شحن محفظتك بنجاح بمبلغ <?php echo esc($_GET['amount'] ?? ''); ?> ج.م!');
<?php endif; ?>

async function buyPerfume(name, price) {
    const formData = new FormData();
    formData.append('item_name', name);
    formData.append('price', price);

    const res = await fetch('wallet.php?action=pay_with_wallet', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
        alert('🎉 تم شراء ' + name + ' بنجاح!');
        location.reload();
    } else {
        alert('❌ ' + data.message);
    }
}

async function startTopup() {
    const amount = document.getElementById('topup-amount').value;
    if (!amount || amount <= 0) return alert('يرجى إدخال مبلغ شحن صحيح');

    const container = document.getElementById('paymob-container');
    container.innerHTML = '<p style="text-align:center; color:#6c757d;">جاري فتح بوابة الدفع...</p>';

    const formData = new FormData();
    formData.append('amount', amount);

    try {
        const res = await fetch('wallet.php?action=topup_wallet', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success && data.client_secret) {
            const iframeUrl = `https://accept.paymob.com/unifiedcheckout/?pulse=true&client_secret=${data.client_secret}`;
            container.innerHTML = `<iframe src="${iframeUrl}"></iframe>`;
        } else {
            container.innerHTML = '';
            alert('حدث خطأ أثناء الاتصال بـ Paymob');
            console.log(data);
        }
    } catch (e) {
        container.innerHTML = '';
        alert('خطأ في الاتصال، حاول مجدداً');
    }
}
</script>

</body>
</html>