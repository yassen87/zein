<?php
require_once __DIR__ . '/../includes/config.php';

$pdo = medal_pdo();
if (!$pdo) {
    die("No database connection.\n");
}

try {
    // 1. Create faqs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS faqs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        question_en TEXT NOT NULL,
        question_ar TEXT NOT NULL,
        answer_en TEXT NOT NULL,
        answer_ar TEXT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "Created table 'faqs'.\n";

    // 2. Insert initial FAQs from translations.php
    $items = [
        [
            'q_en' => 'How long does delivery take?',
            'q_ar' => 'كم مدة الشحن؟',
            'a_en' => 'Typically 2–5 business days depending on the region. Tracking details will be shared via email or WhatsApp once dispatched.',
            'a_ar' => 'عادة 2–5 أيام عمل حسب المنطقة. ستصلك تفاصيل التتبع بالبريد أو واتساب بمجرد الشحن.',
            'sort' => 10
        ],
        [
            'q_en' => 'Can I return or exchange my order?',
            'q_ar' => 'هل يمكن الإرجاع أو الاستبدال؟',
            'a_en' => 'Unused items in original packaging may be exchanged within a short window. Opened perfumes cannot be returned for hygiene reasons.',
            'a_ar' => 'المنتجات غير المستخدمة وبعبواتها الأصلية قد تُستبدل خلال فترة قصيرة. العطور المفتوحة لا تُسترجع لأسباب صحية.',
            'sort' => 20
        ],
        [
            'q_en' => 'What alcohol is used?',
            'q_ar' => 'ما نوع الكحول المستخدم؟',
            'a_en' => 'We use high-quality cosmetic-grade ethanol in line with industry standards for fine fragrance.',
            'a_ar' => 'نستخدم إيثانول تجميلي عالي الجودة وفق معايير صناعة العطور.',
            'sort' => 30
        ],
        [
            'q_en' => 'Do all perfumes perform the same?',
            'q_ar' => 'هل ثبات كل العطور واحد؟',
            'a_en' => 'Longevity and projection vary by composition, skin chemistry, and application. Heavier bases often last longer than very airy citrus openings.',
            'a_ar' => 'الثبات والفوحان يختلفان حسب التركيبة وبشرتك وطريقة الاستخدام. القواعد الأثقل غالبًا أطول من الافتتاحيات الحمضية الخفيفة.',
            'sort' => 40
        ],
    ];

    $st = $pdo->prepare("INSERT INTO faqs (question_en, question_ar, answer_en, answer_ar, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $st->execute([$item['q_en'], $item['q_ar'], $item['a_en'], $item['a_ar'], $item['sort']]);
    }
    echo "Initialized 4 FAQs.\n";

    echo "Database setup successful!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
