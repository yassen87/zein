<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = t('page_contact');
$pageDescription = get_page_description('contact');
$canonicalUrl = get_current_url_without_lang();
$errors = [];
$sent = false;
$name = '';
$email = '';
$phone = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = current_lang() === 'ar' ? 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.' : 'Session expired. Please refresh the page and try again.';
    } else {
    $name    = trim((string) ($_POST['name']    ?? ''));
    $email   = trim((string) ($_POST['email']   ?? ''));
    $phone   = trim((string) ($_POST['phone']   ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '') {
        $errors[] = t('err_name');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('err_email');
    }
    if ($message === '' || strlen($message) < 10) {
        $errors[] = t('err_message');
    }

    if ($errors === []) {
        $pdo = medal_pdo();
        if ($pdo !== null) {
            try {
                $st = $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)');
                $st->execute([$name, $email, $message]);
            } catch (Throwable $e) {
                error_log('Error in contact.php contact_messages insert: ' . $e->getMessage());
            }
        }
        $sent = true;
    }
    }
}

$isAr = current_lang() === 'ar';

require __DIR__ . '/includes/header.php';
?>

<style>
/* ── Contact Page ── */
.contact-hero {
    padding: clamp(3rem, 6vw, 5rem) 0 clamp(2rem, 4vw, 3.5rem);
    text-align: center;
    background: linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}
.contact-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 60% at 50% 0%, var(--gold-glow), transparent 65%);
    pointer-events: none;
}
.contact-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.72rem;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
}
.contact-hero__eyebrow span { width: 28px; height: 1px; background: var(--gold); display: inline-block; }
.contact-hero h1 {
    font-family: var(--font-serif);
    font-size: clamp(2rem, 4.5vw, 3.2rem);
    font-weight: 700;
    margin: 0 0 0.8rem;
    background: linear-gradient(135deg, var(--gold-bright) 0%, var(--gold) 60%, var(--gold-dim) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    line-height: 1.15;
}
.contact-hero__lead {
    color: var(--ink-muted);
    font-size: 1.05rem;
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.7;
}

/* ── Layout ── */
.contact-section {
    background: var(--bg);
    padding: clamp(3rem, 6vw, 5rem) 0;
}
.contact-inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 var(--space);
    display: grid;
    grid-template-columns: 1fr 1.6fr;
    gap: 3rem;
    align-items: start;
}
@media (max-width: 900px) { .contact-inner { grid-template-columns: 1fr; gap: 2.5rem; } }

/* ── Info Side ── */
.contact-info__heading {
    font-family: var(--font-serif);
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 0.4rem;
}
.contact-info__sub { color: var(--ink-muted); font-size: 0.92rem; margin: 0 0 1.8rem; line-height: 1.6; }
.contact-cards { display: flex; flex-direction: column; gap: 0.85rem; }

.contact-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius);
    padding: 1.1rem 1.2rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
    box-shadow: var(--shadow-sm);
}
.contact-card:hover {
    border-color: var(--gold);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.contact-card__icon {
    width: 42px; height: 42px;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.3);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: var(--gold);
}
.contact-card__label {
    font-size: 0.7rem; letter-spacing: 0.15em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; display: block; margin-bottom: 0.15rem;
}
.contact-card__value { font-size: 0.92rem; color: var(--ink); font-weight: 500; }
.contact-card__value a { color: var(--ink); text-decoration: none; transition: color 0.2s; }
.contact-card__value a:hover { color: var(--gold); }

.contact-wa-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    margin-top: 1.5rem;
    background: linear-gradient(135deg, #25D366, #128C7E);
    color: #fff !important;
    text-decoration: none;
    padding: 0.8rem 1.5rem;
    border-radius: var(--radius-full);
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(37,211,102,0.25);
}
.contact-wa-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(37,211,102,0.4); }

/* ── Form Box ── */
.contact-form-box {
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 2.2rem;
    box-shadow: var(--shadow-sm);
}
@media (max-width: 560px) { .contact-form-box { padding: 1.4rem; } }
.contact-form-box__heading {
    font-family: var(--font-serif);
    font-size: 1.3rem; font-weight: 700; color: var(--ink); margin: 0 0 0.3rem;
}
.contact-form-box__sub { color: var(--ink-muted); font-size: 0.87rem; margin: 0 0 1.6rem; }

.contact-form { display: flex; flex-direction: column; gap: 1.1rem; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
@media (max-width: 540px) { .form-row-2 { grid-template-columns: 1fr; } }

.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group label {
    font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--gold); font-weight: 700;
}
.form-group input,
.form-group textarea {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--ink);
    font-family: var(--font-sans);
    font-size: 0.95rem;
    padding: 0.8rem 1rem;
    outline: none;
    transition: border-color 0.25s, box-shadow 0.25s;
    width: 100%;
    box-sizing: border-box;
}
.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px var(--gold-glow);
}
.form-group textarea { resize: vertical; min-height: 130px; }

.contact-submit-btn {
    display: flex; align-items: center; justify-content: center; gap: 0.6rem;
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508;
    border: none; border-radius: var(--radius-full);
    padding: 0.95rem 2rem;
    font-size: 0.95rem; font-weight: 800;
    font-family: var(--font-sans);
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 0.4rem;
    box-shadow: 0 4px 20px var(--gold-glow);
    letter-spacing: 0.04em;
}
.contact-submit-btn:hover {
    filter: brightness(1.08);
    transform: translateY(-2px);
    box-shadow: 0 8px 28px var(--gold-glow);
}

/* ── Alerts ── */
.contact-alert {
    padding: 1rem 1.3rem;
    border-radius: var(--radius);
    font-size: 0.93rem;
    font-weight: 500;
    line-height: 1.5;
    margin-bottom: 1.2rem;
}
.contact-alert--success {
    background: rgba(37,211,102,0.08);
    border: 1px solid rgba(37,211,102,0.25);
    color: #16a34a;
    text-align: center;
}
.contact-alert--error {
    background: rgba(220,38,38,0.06);
    border: 1px solid rgba(220,38,38,0.2);
    color: #dc2626;
}
.contact-alert--error ul { margin: 0; padding-inline-start: 1.2rem; }
.contact-alert--error li { margin-bottom: 0.2rem; }
</style>

<!-- Hero -->
<section class="contact-hero">
    <div class="container" style="position:relative;z-index:1;">
        <p class="contact-hero__eyebrow">
            <span></span><?= $isAr ? 'تواصل معنا' : 'Contact Us' ?><span></span>
        </p>
        <h1><?= $isAr ? 'نحن هنا لمساعدتك' : "We're Here For You" ?></h1>
        <p class="contact-hero__lead">
            <?= $isAr
                ? 'سواء كان لديك سؤال عن منتج، طلب خاص، أو رغبة في التحدث عن العطور — نقرأ كل رسالة.'
                : "Whether you have a question, special request, or just want to talk fragrances — we read every message." ?>
        </p>
    </div>
</section>

<!-- Main -->
<section class="contact-section">
    <div class="contact-inner">

        <!-- Info -->
        <div class="contact-info">
            <h2 class="contact-info__heading"><?= $isAr ? 'معلومات التواصل' : 'Contact Information' ?></h2>
            <p class="contact-info__sub">
                <?= $isAr
                    ? 'تواصل معنا عبر أي من الطرق التالية وسنرد عليك في أقرب وقت.'
                    : "Reach out through any of these channels and we'll get back to you promptly." ?>
            </p>

            <div class="contact-cards">
                <div class="contact-card">
                    <div class="contact-card__icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.13 12 19.79 19.79 0 0 1 1.06 3.36 2 2 0 0 1 3.05 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <span class="contact-card__label"><?= $isAr ? 'الهاتف' : 'Phone' ?></span>
                        <div class="contact-card__value">
                            <a href="<?= esc(contact_phone_href()) ?>" style="direction:ltr; display:inline-block;"><?= esc(CONTACT_PHONE_TEL) ?></a>
                        </div>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card__icon" style="background:rgba(37,211,102,0.08);border-color:rgba(37,211,102,0.25);color:#16a34a;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    </div>
                    <div>
                        <span class="contact-card__label">WhatsApp</span>
                        <div class="contact-card__value">
                            <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener" style="direction:ltr; display:inline-block;"><?= esc(CONTACT_PHONE_TEL) ?></a>
                        </div>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card__icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div>
                        <span class="contact-card__label"><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></span>
                        <div class="contact-card__value">
                            <a href="mailto:support@zainperfumes.com">support@zainperfumes.com</a>
                        </div>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card__icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <span class="contact-card__label"><?= $isAr ? 'ساعات العمل' : 'Working Hours' ?></span>
                        <div class="contact-card__value"><?= $isAr ? 'كل أيام الأسبوع من 2 ظهراً إلى 12 ليلاً' : 'Every day of the week from 2 PM to 12 AM' ?></div>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card__icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <span class="contact-card__label"><?= $isAr ? 'الموقع' : 'Location' ?></span>
                        <div class="contact-card__value"><?= $isAr ? '18 شارع منشية البكري، القاهرة، مصر' : '18 Mansheya El-Bakry St., Cairo, Egypt' ?></div>
                    </div>
                </div>
            </div>

            <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener" class="contact-wa-btn">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                <?= $isAr ? 'راسلنا على واتساب' : 'Chat on WhatsApp' ?>
            </a>
        </div>

        <!-- Form -->
        <div class="contact-form-box">
            <?php if ($sent): ?>
                <div class="contact-alert contact-alert--success">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">✉️</div>
                    <?= $isAr
                        ? "شكرًا <strong>" . esc($name) . "</strong>، تم استلام رسالتك. سنتواصل معك قريبًا."
                        : "Thank you <strong>" . esc($name) . "</strong>, your message has been received. We'll be in touch soon." ?>
                </div>
            <?php else: ?>
                <h2 class="contact-form-box__heading"><?= $isAr ? 'أرسل لنا رسالة' : 'Send Us a Message' ?></h2>
                <p class="contact-form-box__sub"><?= $isAr ? 'سنرد عليك خلال 24 ساعة.' : "We'll reply within 24 hours." ?></p>

                <?php if ($errors !== []): ?>
                    <div class="contact-alert contact-alert--error" role="alert">
                        <ul><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="post" action="<?= esc(url('contact.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lang" value="<?= esc(current_lang()) ?>">

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="c-name"><?= $isAr ? 'الاسم' : 'Full Name' ?> *</label>
                            <input type="text" id="c-name" name="name" required autocomplete="name"
                                   value="<?= esc($name) ?>"
                                   placeholder="<?= $isAr ? 'أدخل اسمك' : 'Your name' ?>">
                        </div>
                        <div class="form-group">
                            <label for="c-phone"><?= $isAr ? 'رقم الهاتف' : 'Phone' ?></label>
                            <input type="tel" id="c-phone" name="phone" autocomplete="tel"
                                   value="<?= esc($phone) ?>"
                                   placeholder="+20 1X XXXXXXXX" style="direction:ltr;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="c-email"><?= $isAr ? 'البريد الإلكتروني' : 'Email Address' ?> *</label>
                        <input type="email" id="c-email" name="email" required autocomplete="email"
                               value="<?= esc($email) ?>"
                               placeholder="example@email.com" style="direction:ltr;">
                    </div>

                    <div class="form-group">
                        <label for="c-message"><?= $isAr ? 'رسالتك' : 'Your Message' ?> *</label>
                        <textarea id="c-message" name="message" required rows="6"
                                  placeholder="<?= $isAr ? 'اكتب رسالتك هنا...' : 'Write your message here...' ?>"><?= esc($message) ?></textarea>
                    </div>

                    <button type="submit" class="contact-submit-btn" id="contact-submit-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        <?= $isAr ? 'إرسال الرسالة' : 'Send Message' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
