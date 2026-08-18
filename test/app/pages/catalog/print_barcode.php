<?php
$id = (int) ($_GET['id'] ?? 0);
$product = find_product($id);
if (!$product) {
    exit(__('المنتج غير موجود'));
}
$lang = current_lang();
$dir = $lang === 'en' ? 'ltr' : 'rtl';
?>
<!doctype html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <title><?= e(__('طباعة باركود')) ?> - <?= e($product['name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+39&family=Cairo:wght@400;600;700;800&display=swap');
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: #f1f5f9;
            color: #1e293b;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            gap: 15px;
        }
        
        /* Barcode Sticker Preview Box */
        .barcode-container {
            border: 1px dashed #94a3b8;
            padding: 10px;
            text-align: center;
            width: 50mm;
            height: 25mm;
            background: #fff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        
        .barcode-title {
            font-size: 8px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        
        .barcode-display {
            font-family: 'Libre Barcode 39', cursive;
            font-size: 32px;
            line-height: 1;
            margin: 1px 0;
            display: block;
            direction: ltr;
            color: #000;
        }
        
        .barcode-text {
            font-size: 7.5px;
            letter-spacing: 1.5px;
            margin: 0;
            display: block;
            font-weight: 700;
            color: #334155;
            line-height: 1;
        }
        
        .barcode-price {
            font-size: 9px;
            font-weight: 800;
            color: #2563eb;
            margin: 0;
            line-height: 1.2;
            width: 100%;
            border-top: 1px solid #f1f5f9;
            padding-top: 1px;
        }
        
        /* Interactive controls for screen */
        .controls-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .controls-card h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #7c3aed, #06b6d4);
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: transform 0.15s ease, opacity 0.15s ease;
            font-family: inherit;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }
        
        .btn-print:active {
            transform: scale(0.98);
        }
        
        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: color 0.15s ease;
        }
        
        .btn-back:hover {
            color: #0f172a;
        }
        
        @media print {
            @page {
                size: 50mm 25mm;
                margin: 0;
            }
            body {
                background: #fff;
                padding: 0;
                margin: 0;
                min-height: auto;
                width: 50mm;
                height: 25mm;
                display: flex;
                justify-content: center;
                align-items: center;
                overflow: hidden;
            }
            .barcode-container {
                border: 0;
                padding: 2px;
                width: 50mm;
                height: 25mm;
                box-shadow: none;
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    
    <div class="no-print" style="text-align: center; margin-bottom: 5px; color: #64748b; font-size: 12px; font-weight: 600;">
        🔍 <?= e(__('معاينة ملصق الباركود (مقياس 50مم × 25مم)')) ?>
    </div>

    <!-- The actual sticker container -->
    <div class="barcode-container">
        <div class="barcode-title"><?= e($product['name']) ?></div>
        <!-- Asterisks (*) are required at the beginning and end of Libre Barcode 39 strings -->
        <div class="barcode-display">*<?= e($product['barcode']) ?>*</div>
        <div class="barcode-text"><?= e($product['barcode']) ?></div>
        <div class="barcode-price"><?= money($product['sale_price']) ?></div>
    </div>
    
    <div class="controls-card no-print">
        <h3><?= e($product['name']) ?></h3>
        <button class="btn-print" onclick="window.print()"><?= e(__('طباعة الملصق')) ?></button>
        <button type="button" class="btn-print" onclick="window.print()"><?= e(__('طباعة الملصق')) ?></button>
        <button type="button" class="btn-back" onclick="window.close(); if (!window.closed) history.back();">← <?= e(__('العودة إلى إدارة المنتجات')) ?></button>
    </div>

    <script>
        // Keep the user on this preview page after printing instead of redirecting away.
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
