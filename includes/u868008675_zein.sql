-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 17, 2026 at 10:12 AM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u868008675_zein`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(64) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `message_ar` text DEFAULT NULL,
  `message_en` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `type`, `title_ar`, `title_en`, `message_ar`, `message_en`, `link`, `is_read`, `created_at`) VALUES
(1, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:31:02'),
(2, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:31:25'),
(3, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:31:37'),
(4, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:31:58'),
(5, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:32:14'),
(6, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:32:34'),
(7, 'new_order', 'طلب جديد: MED-F63F858B', 'New Order: MED-F63F858B', 'لديك طلب جديد من Yassen Mostafa بقيمة 895.00 ج.م.', 'New order from Yassen Mostafa total 895.00 ج.م.', 'order_view.php?id=9', 0, '2026-05-18 18:34:07'),
(8, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:35:33'),
(9, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-18 18:35:44'),
(10, 'new_order', 'طلب جديد: MED-C305082C', 'New Order: MED-C305082C', 'لديك طلب جديد من Yassen Mostafa بقيمة 895.00 ج.م.', 'New order from Yassen Mostafa total 895.00 ج.م.', 'order_view.php?id=10', 0, '2026-05-18 18:36:19'),
(11, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-30 15:53:59'),
(12, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-30 15:54:06'),
(13, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-30 15:54:10'),
(14, 'test', 'إشعار تجريبي من الإعدادات', 'Test Notification from Settings', 'هذا إشعار تجريبي للتأكد من عمل نظام التنبيهات بنجاح.', 'This is a test notification to ensure the alert system is working correctly.', 'settings.php', 0, '2026-05-30 15:54:47'),
(15, 'new_order', 'طلب جديد: MED-8CF8F595', 'New Order: MED-8CF8F595', 'لديك طلب جديد من Yassen Mostafa بقيمة 2,445.00 ج.م.', 'New order from Yassen Mostafa total 2,445.00 ج.م.', 'order_view.php?id=11', 0, '2026-06-13 02:20:46'),
(16, 'new_order', 'طلب جديد: MED-C6F9FDC5', 'New Order: MED-C6F9FDC5', 'لديك طلب جديد من Yassen Mostafa بقيمة 1,670.00 ج.م.', 'New order from Yassen Mostafa total 1,670.00 ج.م.', 'order_view.php?id=12', 0, '2026-06-13 02:26:45'),
(17, 'new_product', 'منتج جديد: نساء', 'New Product: mmm', 'تمت إضافة منتج جديد إلى المتجر: نساء', 'A new product has been added to the store: mmm', 'product_edit.php?id=4', 0, '2026-06-18 16:22:16'),
(18, 'new_product', 'منتج جديد: لومال', 'New Product: hhhh', 'تمت إضافة منتج جديد إلى المتجر: لومال', 'A new product has been added to the store: hhhh', 'product_edit.php?id=5', 0, '2026-06-18 16:32:21'),
(19, 'new_product', 'منتج جديد: لومال الكسيرننن', 'New Product: mmmmتت', 'تمت إضافة منتج جديد إلى المتجر: لومال الكسيرننن', 'A new product has been added to the store: mmmmتت', 'product_edit.php?id=6', 0, '2026-06-18 16:46:21'),
(20, 'new_product', 'منتج جديد: ييييي', 'New Product: hhhhي', 'تمت إضافة منتج جديد إلى المتجر: ييييي', 'A new product has been added to the store: hhhhي', 'product_edit.php?id=7', 0, '2026-06-18 16:48:39'),
(21, 'new_product', 'منتج جديد: لومال الكسيرتتتتتت', 'New Product: Le Male  Elixir', 'تمت إضافة منتج جديد إلى المتجر: لومال الكسيرتتتتتت', 'A new product has been added to the store: Le Male  Elixir', 'product_edit.php?id=8', 0, '2026-06-18 17:27:49'),
(22, 'new_product', 'منتج جديد: امبرليزر', 'New Product: Ombre Leather', 'تمت إضافة منتج جديد إلى المتجر: امبرليزر', 'A new product has been added to the store: Ombre Leather', 'product_edit.php?id=9', 0, '2026-06-18 18:11:06'),
(23, 'new_product', 'منتج جديد: امبرليزر', 'New Product: Ombre Leather', 'تمت إضافة منتج جديد إلى المتجر: امبرليزر', 'A new product has been added to the store: Ombre Leather', 'product_edit.php?id=10', 0, '2026-06-18 18:14:10'),
(24, 'new_product', 'منتج جديد: امبرليزر', 'New Product: Ombre Leather', 'تمت إضافة منتج جديد إلى المتجر: امبرليزر', 'A new product has been added to the store: Ombre Leather', 'product_edit.php?id=11', 0, '2026-06-18 18:20:49'),
(25, 'new_product', 'منتج جديد: اربابورا', 'New Product: Erba Pura', 'تمت إضافة منتج جديد إلى المتجر: اربابورا', 'A new product has been added to the store: Erba Pura', 'product_edit.php?id=12', 0, '2026-06-18 18:25:28'),
(26, 'new_product', 'منتج جديد: الثائر', 'New Product: Althaïr Parfums de Marly', 'تمت إضافة منتج جديد إلى المتجر: الثائر', 'A new product has been added to the store: Althaïr Parfums de Marly', 'product_edit.php?id=13', 0, '2026-06-18 18:31:28'),
(27, 'new_product', 'منتج جديد: لايتون', 'New Product: Layton Parfums de Marly', 'تمت إضافة منتج جديد إلى المتجر: لايتون', 'A new product has been added to the store: Layton Parfums de Marly', 'product_edit.php?id=14', 0, '2026-06-18 18:34:19'),
(28, 'new_product', 'منتج جديد: إيماجينيشن', 'New Product: Imagination', 'تمت إضافة منتج جديد إلى المتجر: إيماجينيشن', 'A new product has been added to the store: Imagination', 'product_edit.php?id=15', 0, '2026-06-18 18:36:29'),
(29, 'new_product', 'منتج جديد: انجل شير', 'New Product: Angels’ share', 'تمت إضافة منتج جديد إلى المتجر: انجل شير', 'A new product has been added to the store: Angels’ share', 'product_edit.php?id=16', 0, '2026-06-18 18:39:42'),
(30, 'new_product', 'منتج جديد: اكستريم نوار', 'New Product: Noir Extreme Tom Ford', 'تمت إضافة منتج جديد إلى المتجر: اكستريم نوار', 'A new product has been added to the store: Noir Extreme Tom Ford', 'product_edit.php?id=17', 0, '2026-06-18 18:42:40'),
(31, 'new_product', 'منتج جديد: أمواج ديسيشين', 'New Product: Amouage Decision', 'تمت إضافة منتج جديد إلى المتجر: أمواج ديسيشين', 'A new product has been added to the store: Amouage Decision', 'product_edit.php?id=18', 0, '2026-06-18 18:45:05'),
(32, 'new_product', 'منتج جديد: أمواج جايدينس 46', 'New Product: Amouage Guidance 46', 'تمت إضافة منتج جديد إلى المتجر: أمواج جايدينس 46', 'A new product has been added to the store: Amouage Guidance 46', 'product_edit.php?id=19', 0, '2026-06-18 18:48:11'),
(33, 'new_product', 'منتج جديد: انستانت كراش', 'New Product: Instant Crush', 'تمت إضافة منتج جديد إلى المتجر: انستانت كراش', 'A new product has been added to the store: Instant Crush', 'product_edit.php?id=20', 0, '2026-06-18 18:50:55'),
(34, 'new_product', 'منتج جديد: ريد توباكو', 'New Product: Red Tobacco', 'تمت إضافة منتج جديد إلى المتجر: ريد توباكو', 'A new product has been added to the store: Red Tobacco', 'product_edit.php?id=21', 0, '2026-06-18 18:53:39'),
(35, 'new_product', 'منتج جديد: بوا امبريال', 'New Product: Bois Imperial', 'تمت إضافة منتج جديد إلى المتجر: بوا امبريال', 'A new product has been added to the store: Bois Imperial', 'product_edit.php?id=22', 0, '2026-06-18 18:56:21'),
(36, 'new_product', 'منتج جديد: سبايس بوم اكستريم', 'New Product: Spicebomb Extreme', 'تمت إضافة منتج جديد إلى المتجر: سبايس بوم اكستريم', 'A new product has been added to the store: Spicebomb Extreme', 'product_edit.php?id=23', 0, '2026-06-18 19:00:12'),
(37, 'new_product', 'منتج جديد: بلاك افغانو', 'New Product: Black Afgano', 'تمت إضافة منتج جديد إلى المتجر: بلاك افغانو', 'A new product has been added to the store: Black Afgano', 'product_edit.php?id=24', 0, '2026-06-18 19:02:55'),
(38, 'new_product', 'منتج جديد: باسيفيك شيل', 'New Product: Pacific Chill', 'تمت إضافة منتج جديد إلى المتجر: باسيفيك شيل', 'A new product has been added to the store: Pacific Chill', 'product_edit.php?id=25', 0, '2026-06-18 19:05:35'),
(39, 'new_product', 'منتج جديد: بكرات روج', 'New Product: Baccarat Rouge', 'تمت إضافة منتج جديد إلى المتجر: بكرات روج', 'A new product has been added to the store: Baccarat Rouge', 'product_edit.php?id=26', 0, '2026-06-18 19:08:10'),
(40, 'new_product', 'منتج جديد: ستيلر تايمز', 'New Product: Stellar Times', 'تمت إضافة منتج جديد إلى المتجر: ستيلر تايمز', 'A new product has been added to the store: Stellar Times', 'product_edit.php?id=27', 0, '2026-06-18 19:13:25'),
(41, 'new_product', 'منتج جديد: سايد إفيكت', 'New Product: Side Effect Initio', 'تمت إضافة منتج جديد إلى المتجر: سايد إفيكت', 'A new product has been added to the store: Side Effect Initio', 'product_edit.php?id=28', 0, '2026-06-19 02:46:35'),
(42, 'new_product', 'منتج جديد: سوفاج الكسير', 'New Product: Sauvage Elixir', 'تمت إضافة منتج جديد إلى المتجر: سوفاج الكسير', 'A new product has been added to the store: Sauvage Elixir', 'product_edit.php?id=29', 0, '2026-06-19 02:48:42'),
(43, 'new_product', 'منتج جديد: سيمفوني', 'New Product: Symphony', 'تمت إضافة منتج جديد إلى المتجر: سيمفوني', 'A new product has been added to the store: Symphony', 'product_edit.php?id=30', 0, '2026-06-19 02:50:41'),
(44, 'new_product', 'منتج جديد: توباكو فانيليا', 'New Product: Tobacco Vanille', 'تمت إضافة منتج جديد إلى المتجر: توباكو فانيليا', 'A new product has been added to the store: Tobacco Vanille', 'product_edit.php?id=31', 0, '2026-06-19 02:52:51'),
(45, 'new_product', 'منتج جديد: تايجر بلغاري', 'New Product: Tygar Bvlgari', 'تمت إضافة منتج جديد إلى المتجر: تايجر بلغاري', 'A new product has been added to the store: Tygar Bvlgari', 'product_edit.php?id=32', 0, '2026-06-19 02:55:42'),
(46, 'new_product', 'منتج جديد: توكسيدو', 'New Product: Tuxedo', 'تمت إضافة منتج جديد إلى المتجر: توكسيدو', 'A new product has been added to the store: Tuxedo', 'product_edit.php?id=33', 0, '2026-06-19 02:58:36'),
(47, 'new_product', 'منتج جديد: عود ستان مود', 'New Product: Oud Satin Mood', 'تمت إضافة منتج جديد إلى المتجر: عود ستان مود', 'A new product has been added to the store: Oud Satin Mood', 'product_edit.php?id=34', 0, '2026-06-19 03:02:35'),
(48, 'new_product', 'منتج جديد: تيروني', 'New Product: Terroni', 'تمت إضافة منتج جديد إلى المتجر: تيروني', 'A new product has been added to the store: Terroni', 'product_edit.php?id=35', 0, '2026-06-19 03:05:38'),
(49, 'new_product', 'منتج جديد: ناكسوس', 'New Product: Naxos', 'تمت إضافة منتج جديد إلى المتجر: ناكسوس', 'A new product has been added to the store: Naxos', 'product_edit.php?id=36', 0, '2026-06-19 03:07:26'),
(50, 'new_product', 'منتج جديد: فورت نوتس', 'New Product: 40 Knots', 'تمت إضافة منتج جديد إلى المتجر: فورت نوتس', 'A new product has been added to the store: 40 Knots', 'product_edit.php?id=37', 0, '2026-06-19 03:09:24'),
(51, 'new_product', 'منتج جديد: نيشاني هاشيفات', 'New Product: Nishane Hacivat', 'تمت إضافة منتج جديد إلى المتجر: نيشاني هاشيفات', 'A new product has been added to the store: Nishane Hacivat', 'product_edit.php?id=38', 0, '2026-06-19 03:12:20'),
(52, 'new_product', 'منتج جديد: ليس سابل روز', 'New Product: Les Sables Roses', 'تمت إضافة منتج جديد إلى المتجر: ليس سابل روز', 'A new product has been added to the store: Les Sables Roses', 'product_edit.php?id=39', 0, '2026-06-19 03:16:02'),
(53, 'new_product', 'منتج جديد: كريد أفنتوس', 'New Product: Creed Aventus', 'تمت إضافة منتج جديد إلى المتجر: كريد أفنتوس', 'A new product has been added to the store: Creed Aventus', 'product_edit.php?id=40', 0, '2026-06-19 03:18:37'),
(54, 'new_product', 'منتج جديد: كافي روز', 'New Product: Café Rose', 'تمت إضافة منتج جديد إلى المتجر: كافي روز', 'A new product has been added to the store: Café Rose', 'product_edit.php?id=41', 0, '2026-06-19 03:25:27'),
(55, 'new_product', 'منتج جديد: فلور ناركوتك', 'New Product: Fleur Narcotique', 'تمت إضافة منتج جديد إلى المتجر: فلور ناركوتك', 'A new product has been added to the store: Fleur Narcotique', 'product_edit.php?id=42', 0, '2026-06-19 03:28:24'),
(56, 'new_product', 'منتج جديد: امبريال فالي', 'New Product: Imperial Valley', 'تمت إضافة منتج جديد إلى المتجر: امبريال فالي', 'A new product has been added to the store: Imperial Valley', 'product_edit.php?id=43', 0, '2026-06-19 03:32:08'),
(57, 'new_product', 'منتج جديد: ميجامير', 'New Product: Megamare', 'تمت إضافة منتج جديد إلى المتجر: ميجامير', 'A new product has been added to the store: Megamare', 'product_edit.php?id=44', 0, '2026-06-19 03:34:20'),
(58, 'new_product', 'منتج جديد: فيبراتو سوسبيرو', 'New Product: Vibrato Sospiro', 'تمت إضافة منتج جديد إلى المتجر: فيبراتو سوسبيرو', 'A new product has been added to the store: Vibrato Sospiro', 'product_edit.php?id=45', 0, '2026-06-19 03:38:38'),
(59, 'new_product', 'منتج جديد: جراند سوار', 'New Product: Grand Soir', 'تمت إضافة منتج جديد إلى المتجر: جراند سوار', 'A new product has been added to the store: Grand Soir', 'product_edit.php?id=46', 0, '2026-06-19 03:41:37'),
(60, 'new_product', 'منتج جديد: زيرجوف تورينو', 'New Product: 21 Torino21 Xerjoff', 'تمت إضافة منتج جديد إلى المتجر: زيرجوف تورينو', 'A new product has been added to the store: 21 Torino21 Xerjoff', 'product_edit.php?id=47', 0, '2026-06-19 03:43:43'),
(61, 'new_product', 'منتج جديد: اكسبريمنتوم', 'New Product: Experimentum', 'تمت إضافة منتج جديد إلى المتجر: اكسبريمنتوم', 'A new product has been added to the store: Experimentum', 'product_edit.php?id=48', 0, '2026-06-19 03:48:38'),
(62, 'new_product', 'منتج جديد: اكسبريمنتوم', 'New Product: Afternoon Swim', 'تمت إضافة منتج جديد إلى المتجر: اكسبريمنتوم', 'A new product has been added to the store: Afternoon Swim', 'product_edit.php?id=49', 0, '2026-06-19 03:52:01'),
(63, 'new_product', 'منتج جديد: الكسندريا 2', 'New Product: Alexandria II', 'تمت إضافة منتج جديد إلى المتجر: الكسندريا 2', 'A new product has been added to the store: Alexandria II', 'product_edit.php?id=50', 0, '2026-06-19 03:54:05'),
(64, 'new_product', 'منتج جديد: رسيندو ماتيو 5', 'New Product: Rosendo Mateu 5', 'تمت إضافة منتج جديد إلى المتجر: رسيندو ماتيو 5', 'A new product has been added to the store: Rosendo Mateu 5', 'product_edit.php?id=51', 0, '2026-06-19 03:59:09'),
(65, 'new_product', 'منتج جديد: هوس ايس الرصاصي', 'New Product: Hawas Ice Rasasi', 'تمت إضافة منتج جديد إلى المتجر: هوس ايس الرصاصي', 'A new product has been added to the store: Hawas Ice Rasasi', 'product_edit.php?id=52', 0, '2026-06-19 16:07:59'),
(66, 'new_product', 'منتج جديد: ارماني كود برفومو', 'New Product: Armani Code Profumo', 'تمت إضافة منتج جديد إلى المتجر: ارماني كود برفومو', 'A new product has been added to the store: Armani Code Profumo', 'product_edit.php?id=53', 0, '2026-06-19 16:12:03'),
(67, 'new_product', 'منتج جديد: اكوا دي جيو', 'New Product: Acqua di Gio', 'تمت إضافة منتج جديد إلى المتجر: اكوا دي جيو', 'A new product has been added to the store: Acqua di Gio', 'product_edit.php?id=54', 0, '2026-06-19 16:13:53'),
(68, 'new_product', 'منتج جديد: اكوا دي جيو برفومو', 'New Product: Acqua di Gio Profumo', 'تمت إضافة منتج جديد إلى المتجر: اكوا دي جيو برفومو', 'A new product has been added to the store: Acqua di Gio Profumo', 'product_edit.php?id=55', 0, '2026-06-19 16:15:14'),
(69, 'new_product', 'منتج جديد: بلاك ليجزيس', 'New Product: Black XS L\'Exces', 'تمت إضافة منتج جديد إلى المتجر: بلاك ليجزيس', 'A new product has been added to the store: Black XS L\'Exces', 'product_edit.php?id=56', 0, '2026-06-19 16:16:51'),
(70, 'new_product', 'منتج جديد: بلاك X.S', 'New Product: Black XS', 'تمت إضافة منتج جديد إلى المتجر: بلاك X.S', 'A new product has been added to the store: Black XS', 'product_edit.php?id=57', 0, '2026-06-19 16:18:22'),
(71, 'new_product', 'منتج جديد: بلو دي شانيل', 'New Product: Bleu de Chanel', 'تمت إضافة منتج جديد إلى المتجر: بلو دي شانيل', 'A new product has been added to the store: Bleu de Chanel', 'product_edit.php?id=58', 0, '2026-06-19 16:20:01'),
(72, 'new_product', 'منتج جديد: سوفاج', 'New Product: Sauvage', 'تمت إضافة منتج جديد إلى المتجر: سوفاج', 'A new product has been added to the store: Sauvage', 'product_edit.php?id=59', 0, '2026-06-19 16:21:41'),
(73, 'new_product', 'منتج جديد: باد بوي', 'New Product: Bad Boy', 'تمت إضافة منتج جديد إلى المتجر: باد بوي', 'A new product has been added to the store: Bad Boy', 'product_edit.php?id=60', 0, '2026-06-19 16:23:31'),
(74, 'new_product', 'منتج جديد: بوما جام', 'New Product: Puma Jam', 'تمت إضافة منتج جديد إلى المتجر: بوما جام', 'A new product has been added to the store: Puma Jam', 'product_edit.php?id=61', 0, '2026-06-19 16:25:32'),
(75, 'new_product', 'منتج جديد: هريرا 212', 'New Product: 212 Men Carolina Herrera', 'تمت إضافة منتج جديد إلى المتجر: هريرا 212', 'A new product has been added to the store: 212 Men Carolina Herrera', 'product_edit.php?id=62', 0, '2026-06-19 16:28:15'),
(76, 'new_product', 'منتج جديد: سكسي 212', 'New Product: 212 Sexy Men Carolina Herrera', 'تمت إضافة منتج جديد إلى المتجر: سكسي 212', 'A new product has been added to the store: 212 Sexy Men Carolina Herrera', 'product_edit.php?id=63', 0, '2026-06-19 16:29:35'),
(77, 'new_product', 'منتج جديد: Al Wisam Rasasi', 'New Product: الوسام', 'تمت إضافة منتج جديد إلى المتجر: Al Wisam Rasasi', 'A new product has been added to the store: الوسام', 'product_edit.php?id=64', 0, '2026-06-19 16:43:14'),
(78, 'new_product', 'منتج جديد: كريد سيلفر ماونتن ووتر', 'New Product: Creed Silver Mountain Water', 'تمت إضافة منتج جديد إلى المتجر: كريد سيلفر ماونتن ووتر', 'A new product has been added to the store: Creed Silver Mountain Water', 'product_edit.php?id=65', 0, '2026-06-19 16:45:58'),
(79, 'new_product', 'منتج جديد: ليجند مونت بلانك', 'New Product: Legend Montblanc', 'تمت إضافة منتج جديد إلى المتجر: ليجند مونت بلانك', 'A new product has been added to the store: Legend Montblanc', 'product_edit.php?id=66', 0, '2026-06-19 16:47:39'),
(80, 'new_product', 'منتج جديد: لاكوست اسينشيال', 'New Product: Lacoste Essential', 'تمت إضافة منتج جديد إلى المتجر: لاكوست اسينشيال', 'A new product has been added to the store: Lacoste Essential', 'product_edit.php?id=67', 0, '2026-06-19 16:49:41'),
(81, 'new_product', 'منتج جديد: لاكوست وايت', 'New Product: Lacoste White', 'تمت إضافة منتج جديد إلى المتجر: لاكوست وايت', 'A new product has been added to the store: Lacoste White', 'product_edit.php?id=68', 0, '2026-06-19 16:51:24'),
(82, 'new_product', 'منتج جديد: سيلفر سنت', 'New Product: Silver Scent', 'تمت إضافة منتج جديد إلى المتجر: سيلفر سنت', 'A new product has been added to the store: Silver Scent', 'product_edit.php?id=69', 0, '2026-06-19 16:53:16'),
(83, 'new_product', 'منتج جديد: واي', 'New Product: Y Intense', 'تمت إضافة منتج جديد إلى المتجر: واي', 'A new product has been added to the store: Y Intense', 'product_edit.php?id=70', 0, '2026-06-19 16:54:48'),
(84, 'new_product', 'منتج جديد: ايروس فيرزاتشي', 'New Product: Eros Versace', 'تمت إضافة منتج جديد إلى المتجر: ايروس فيرزاتشي', 'A new product has been added to the store: Eros Versace', 'product_edit.php?id=71', 0, '2026-06-19 17:27:02'),
(85, 'new_product', 'منتج جديد: مليون لاكي', 'New Product: 1 Million Lucky', 'تمت إضافة منتج جديد إلى المتجر: مليون لاكي', 'A new product has been added to the store: 1 Million Lucky', 'product_edit.php?id=72', 0, '2026-06-19 17:29:35'),
(86, 'new_product', 'منتج جديد: وان مليون الكسير', 'New Product: 1 Million Elixir', 'تمت إضافة منتج جديد إلى المتجر: وان مليون الكسير', 'A new product has been added to the store: 1 Million Elixir', 'product_edit.php?id=73', 0, '2026-06-19 23:25:00'),
(87, 'new_product', 'منتج جديد: لانوي لاهوم', 'New Product: La Nuit de l\'Homme', 'تمت إضافة منتج جديد إلى المتجر: لانوي لاهوم', 'A new product has been added to the store: La Nuit de l\'Homme', 'product_edit.php?id=74', 0, '2026-06-19 23:26:29'),
(88, 'new_product', 'منتج جديد: ذا وان', 'New Product: The One', 'تمت إضافة منتج جديد إلى المتجر: ذا وان', 'A new product has been added to the store: The One', 'product_edit.php?id=75', 0, '2026-06-19 23:29:02'),
(89, 'new_product', 'منتج جديد: كريد بلاك', 'New Product: Creed Black', 'تمت إضافة منتج جديد إلى المتجر: كريد بلاك', 'A new product has been added to the store: Creed Black', 'product_edit.php?id=76', 0, '2026-06-19 23:30:38'),
(90, 'new_product', 'منتج جديد: أمبر بالدسرين', 'New Product: Ambré Baldessarini', 'تمت إضافة منتج جديد إلى المتجر: أمبر بالدسرين', 'A new product has been added to the store: Ambré Baldessarini', 'product_edit.php?id=77', 0, '2026-06-19 23:32:22'),
(91, 'new_product', 'منتج جديد: خمرة', 'New Product: Khamrah Lattafa', 'تمت إضافة منتج جديد إلى المتجر: خمرة', 'A new product has been added to the store: Khamrah Lattafa', 'product_edit.php?id=78', 0, '2026-06-19 23:34:55'),
(92, 'new_product', 'منتج جديد: لوميل لي بارفيوم', 'New Product: Le Male Le Parfum', 'تمت إضافة منتج جديد إلى المتجر: لوميل لي بارفيوم', 'A new product has been added to the store: Le Male Le Parfum', 'product_edit.php?id=79', 0, '2026-06-19 23:36:22'),
(93, 'new_product', 'منتج جديد: سترونجر ويذ يو', 'New Product: Stronger With You', 'تمت إضافة منتج جديد إلى المتجر: سترونجر ويذ يو', 'A new product has been added to the store: Stronger With You', 'product_edit.php?id=80', 0, '2026-06-19 23:39:07'),
(94, 'new_product', 'منتج جديد: سترونجر ويذ يو انتنسلي', 'New Product: Stronger With You Intensely', 'تمت إضافة منتج جديد إلى المتجر: سترونجر ويذ يو انتنسلي', 'A new product has been added to the store: Stronger With You Intensely', 'product_edit.php?id=81', 0, '2026-06-19 23:40:25'),
(95, 'new_product', 'منتج جديد: سترونجر ويذ يو عنبر', 'New Product: Stronger With You Amber', 'تمت إضافة منتج جديد إلى المتجر: سترونجر ويذ يو عنبر', 'A new product has been added to the store: Stronger With You Amber', 'product_edit.php?id=82', 0, '2026-06-19 23:42:08'),
(96, 'new_product', 'منتج جديد: ديور هوم انتنس', 'New Product: Dior Homme Intense', 'تمت إضافة منتج جديد إلى المتجر: ديور هوم انتنس', 'A new product has been added to the store: Dior Homme Intense', 'product_edit.php?id=83', 0, '2026-06-19 23:44:59'),
(97, 'new_product', 'منتج جديد: بلغاري مان إن بلاك', 'New Product: Bvlgari Man In Black', 'تمت إضافة منتج جديد إلى المتجر: بلغاري مان إن بلاك', 'A new product has been added to the store: Bvlgari Man In Black', 'product_edit.php?id=84', 0, '2026-06-19 23:46:39'),
(98, 'new_product', 'منتج جديد: VIP 212 black', 'New Product: VIP 212 black', 'تمت إضافة منتج جديد إلى المتجر: VIP 212 black', 'A new product has been added to the store: VIP 212 black', 'product_edit.php?id=85', 0, '2026-06-19 23:50:37'),
(99, 'new_product', 'منتج جديد: بلاك أوركيد', 'New Product: Black Orchid', 'تمت إضافة منتج جديد إلى المتجر: بلاك أوركيد', 'A new product has been added to the store: Black Orchid', 'product_edit.php?id=86', 0, '2026-06-19 23:53:53'),
(100, 'new_product', 'منتج جديد: روز فانيليا', 'New Product: Roses Vanille', 'تمت إضافة منتج جديد إلى المتجر: روز فانيليا', 'A new product has been added to the store: Roses Vanille', 'product_edit.php?id=87', 0, '2026-06-19 23:57:13'),
(101, 'new_product', 'منتج جديد: اللور هوم سبورت', 'New Product: Allure Homme Sport', 'تمت إضافة منتج جديد إلى المتجر: اللور هوم سبورت', 'A new product has been added to the store: Allure Homme Sport', 'product_edit.php?id=88', 0, '2026-06-19 23:59:44'),
(102, 'new_product', 'منتج جديد: بلاتنيوم', 'New Product: Egoiste Platinum Chanel', 'تمت إضافة منتج جديد إلى المتجر: بلاتنيوم', 'A new product has been added to the store: Egoiste Platinum Chanel', 'product_edit.php?id=89', 0, '2026-06-20 00:02:21'),
(103, 'new_product', 'منتج جديد: ديلان بلو', 'New Product: Versace Pour Homme Dylan Blue', 'تمت إضافة منتج جديد إلى المتجر: ديلان بلو', 'A new product has been added to the store: Versace Pour Homme Dylan Blue', 'product_edit.php?id=90', 0, '2026-06-20 00:03:47'),
(104, 'new_product', 'منتج جديد: هوجو', 'New Product: Hugo', 'تمت إضافة منتج جديد إلى المتجر: هوجو', 'A new product has been added to the store: Hugo', 'product_edit.php?id=91', 0, '2026-06-20 00:05:06'),
(105, 'new_product', 'منتج جديد: سكاندل رجالي', 'New Product: Scandal Pour Homme', 'تمت إضافة منتج جديد إلى المتجر: سكاندل رجالي', 'A new product has been added to the store: Scandal Pour Homme', 'product_edit.php?id=92', 0, '2026-06-20 00:06:22'),
(106, 'new_product', 'منتج جديد: فانيليا باودر', 'New Product: Vanilla Powder', 'تمت إضافة منتج جديد إلى المتجر: فانيليا باودر', 'A new product has been added to the store: Vanilla Powder', 'product_edit.php?id=93', 0, '2026-06-20 00:08:33'),
(107, 'new_product', 'منتج جديد: ارماني كود', 'New Product: Armani Code', 'تمت إضافة منتج جديد إلى المتجر: ارماني كود', 'A new product has been added to the store: Armani Code', 'product_edit.php?id=94', 0, '2026-06-20 00:10:46'),
(108, 'new_product', 'منتج جديد: خمرة قهوة', 'New Product: Khamrah Qahwa Lattafa', 'تمت إضافة منتج جديد إلى المتجر: خمرة قهوة', 'A new product has been added to the store: Khamrah Qahwa Lattafa', 'product_edit.php?id=95', 0, '2026-06-20 00:12:40'),
(109, 'new_product', 'منتج جديد: جيمي شو', 'New Product: Jimmy Choo Man', 'تمت إضافة منتج جديد إلى المتجر: جيمي شو', 'A new product has been added to the store: Jimmy Choo Man', 'product_edit.php?id=96', 0, '2026-06-20 00:14:48'),
(110, 'new_product', 'منتج جديد: وان مليون', 'New Product: 1 Million', 'تمت إضافة منتج جديد إلى المتجر: وان مليون', 'A new product has been added to the store: 1 Million', 'product_edit.php?id=97', 0, '2026-06-20 00:17:02'),
(111, 'new_product', 'منتج جديد: Pure XS', 'New Product: بيور X.S', 'تمت إضافة منتج جديد إلى المتجر: Pure XS', 'A new product has been added to the store: بيور X.S', 'product_edit.php?id=98', 0, '2026-06-20 00:18:42'),
(112, 'new_product', 'منتج جديد: فوياج', 'New Product: Nautica Voyage', 'تمت إضافة منتج جديد إلى المتجر: فوياج', 'A new product has been added to the store: Nautica Voyage', 'product_edit.php?id=99', 0, '2026-06-20 00:20:55'),
(113, 'new_product', 'منتج جديد: VIP 212', 'New Product: VIP 212', 'تمت إضافة منتج جديد إلى المتجر: VIP 212', 'A new product has been added to the store: VIP 212', 'product_edit.php?id=100', 0, '2026-06-21 11:30:08'),
(114, 'new_product', 'منتج جديد: انفكتوس الكسير', 'New Product: Invictus Elixir', 'تمت إضافة منتج جديد إلى المتجر: انفكتوس الكسير', 'A new product has been added to the store: Invictus Elixir', 'product_edit.php?id=101', 0, '2026-06-21 11:32:56'),
(115, 'new_product', 'منتج جديد: انفكتوس فيكتوري', 'New Product: Invictus Victory', 'تمت إضافة منتج جديد إلى المتجر: انفكتوس فيكتوري', 'A new product has been added to the store: Invictus Victory', 'product_edit.php?id=102', 0, '2026-06-21 11:35:08'),
(116, 'new_product', 'منتج جديد: فانيليا 6', 'New Product: Vanilla 6', 'تمت إضافة منتج جديد إلى المتجر: فانيليا 6', 'A new product has been added to the store: Vanilla 6', 'product_edit.php?id=103', 0, '2026-06-21 11:43:15'),
(117, 'new_product', 'منتج جديد: لوميل الكسير', 'New Product: Le Male Elixir', 'تمت إضافة منتج جديد إلى المتجر: لوميل الكسير', 'A new product has been added to the store: Le Male Elixir', 'product_edit.php?id=104', 0, '2026-06-21 11:46:23'),
(118, 'new_product', 'منتج جديد: خمرة', 'New Product: Khamrah Lattafa', 'تمت إضافة منتج جديد إلى المتجر: خمرة', 'A new product has been added to the store: Khamrah Lattafa', 'product_edit.php?id=105', 0, '2026-06-21 11:51:03'),
(119, 'new_order', 'طلب جديد: MED-2E5F9078', 'New Order: MED-2E5F9078', 'لديك طلب جديد من Yassen Mostafa بقيمة 120.00 ج.م.', 'New order from Yassen Mostafa total 120.00 ج.م.', 'order_view.php?id=13', 0, '2026-06-22 04:40:53'),
(120, 'new_product', 'منتج جديد: لاف از هيفنلي', 'New Product: Love is Heavenly', 'تمت إضافة منتج جديد إلى المتجر: لاف از هيفنلي', 'A new product has been added to the store: Love is Heavenly', 'product_edit.php?id=106', 0, '2026-06-23 13:50:27'),
(121, 'new_product', 'منتج جديد: سكاندل حريمي', 'New Product: Scandal', 'تمت إضافة منتج جديد إلى المتجر: سكاندل حريمي', 'A new product has been added to the store: Scandal', 'product_edit.php?id=107', 0, '2026-06-23 13:54:15'),
(122, 'new_product', 'منتج جديد: اوليمبيا', 'New Product: Olympéa', 'تمت إضافة منتج جديد إلى المتجر: اوليمبيا', 'A new product has been added to the store: Olympéa', 'product_edit.php?id=108', 0, '2026-06-23 13:57:23'),
(123, 'new_product', 'منتج جديد: لافي بيل', 'New Product: La Vie Est Belle', 'تمت إضافة منتج جديد إلى المتجر: لافي بيل', 'A new product has been added to the store: La Vie Est Belle', 'product_edit.php?id=109', 0, '2026-06-23 13:58:57'),
(124, 'new_product', 'منتج جديد: بامب شيل', 'New Product: Bombshell', 'تمت إضافة منتج جديد إلى المتجر: بامب شيل', 'A new product has been added to the store: Bombshell', 'product_edit.php?id=110', 0, '2026-06-23 14:00:55'),
(125, 'new_product', 'منتج جديد: جود جيرل', 'New Product: Good Girl', 'تمت إضافة منتج جديد إلى المتجر: جود جيرل', 'A new product has been added to the store: Good Girl', 'product_edit.php?id=111', 0, '2026-06-23 14:02:30'),
(126, 'new_product', 'منتج جديد: بلاك ابيوم', 'New Product: Black Opium', 'تمت إضافة منتج جديد إلى المتجر: بلاك ابيوم', 'A new product has been added to the store: Black Opium', 'product_edit.php?id=112', 0, '2026-06-23 14:04:11'),
(127, 'new_product', 'منتج جديد: جوي', 'New Product: Joy by Dior', 'تمت إضافة منتج جديد إلى المتجر: جوي', 'A new product has been added to the store: Joy by Dior', 'product_edit.php?id=113', 0, '2026-06-23 14:06:40'),
(128, 'new_product', 'منتج جديد: ميد نايت', 'New Product: Midnight Fantasy Britney Spears', 'تمت إضافة منتج جديد إلى المتجر: ميد نايت', 'A new product has been added to the store: Midnight Fantasy Britney Spears', 'product_edit.php?id=114', 0, '2026-06-23 14:13:11'),
(129, 'new_product', 'منتج جديد: جوتشي فلورا', 'New Product: Gucci Flora', 'تمت إضافة منتج جديد إلى المتجر: جوتشي فلورا', 'A new product has been added to the store: Gucci Flora', 'product_edit.php?id=115', 0, '2026-06-23 14:16:46'),
(130, 'new_product', 'منتج جديد: سكسي حريمي 212', 'New Product: 212 Sexy Carolina Herrera', 'تمت إضافة منتج جديد إلى المتجر: سكسي حريمي 212', 'A new product has been added to the store: 212 Sexy Carolina Herrera', 'product_edit.php?id=116', 0, '2026-06-23 14:24:05'),
(131, 'new_product', 'منتج جديد: كوكو مادمزيل شانيل', 'New Product: Coco Mademoiselle Chanel', 'تمت إضافة منتج جديد إلى المتجر: كوكو مادمزيل شانيل', 'A new product has been added to the store: Coco Mademoiselle Chanel', 'product_edit.php?id=117', 0, '2026-06-23 14:26:36'),
(132, 'new_product', 'منتج جديد: سي باشون', 'New Product: Sì Passione', 'تمت إضافة منتج جديد إلى المتجر: سي باشون', 'A new product has been added to the store: Sì Passione', 'product_edit.php?id=118', 0, '2026-06-23 14:29:36'),
(133, 'new_product', 'منتج جديد: بربري بادي', 'New Product: Body Burberry', 'تمت إضافة منتج جديد إلى المتجر: بربري بادي', 'A new product has been added to the store: Body Burberry', 'product_edit.php?id=119', 0, '2026-06-23 14:36:20'),
(134, 'new_product', 'منتج جديد: سي ارماني', 'New Product: Si Giorgio Armani', 'تمت إضافة منتج جديد إلى المتجر: سي ارماني', 'A new product has been added to the store: Si Giorgio Armani', 'product_edit.php?id=120', 0, '2026-06-23 14:39:00'),
(135, 'new_product', 'منتج جديد: ليبر', 'New Product: Libre', 'تمت إضافة منتج جديد إلى المتجر: ليبر', 'A new product has been added to the store: Libre', 'product_edit.php?id=121', 0, '2026-06-23 14:41:54'),
(136, 'new_product', 'منتج جديد: كيالي 28', 'New Product: Vanilla | 28 Kayali', 'تمت إضافة منتج جديد إلى المتجر: كيالي 28', 'A new product has been added to the store: Vanilla | 28 Kayali', 'product_edit.php?id=122', 0, '2026-06-23 14:43:55'),
(137, 'new_product', 'منتج جديد: فيري سكسي ناو', 'New Product: Very Sexy Now', 'تمت إضافة منتج جديد إلى المتجر: فيري سكسي ناو', 'A new product has been added to the store: Very Sexy Now', 'product_edit.php?id=123', 0, '2026-06-23 18:12:30'),
(138, 'new_product', 'منتج جديد: ريبيرتو كافالي', 'New Product: Roberto Cavalli', 'تمت إضافة منتج جديد إلى المتجر: ريبيرتو كافالي', 'A new product has been added to the store: Roberto Cavalli', 'product_edit.php?id=124', 0, '2026-06-23 18:14:31'),
(139, 'new_product', 'منتج جديد: مون سباركل', 'New Product: Escada Moon Sparkle', 'تمت إضافة منتج جديد إلى المتجر: مون سباركل', 'A new product has been added to the store: Escada Moon Sparkle', 'product_edit.php?id=125', 0, '2026-06-23 18:17:28'),
(140, 'new_product', 'منتج جديد: بربري هير', 'New Product: Burberry Her', 'تمت إضافة منتج جديد إلى المتجر: بربري هير', 'A new product has been added to the store: Burberry Her', 'product_edit.php?id=126', 0, '2026-06-23 18:19:55'),
(141, 'new_product', 'منتج جديد: جادور', 'New Product: J\'adore Dior', 'تمت إضافة منتج جديد إلى المتجر: جادور', 'A new product has been added to the store: J\'adore Dior', 'product_edit.php?id=127', 0, '2026-06-23 18:22:22'),
(142, 'new_product', 'منتج جديد: ألف ليلة وليلة', 'New Product: Alf Lail o Lail', 'تمت إضافة منتج جديد إلى المتجر: ألف ليلة وليلة', 'A new product has been added to the store: Alf Lail o Lail', 'product_edit.php?id=128', 0, '2026-07-06 02:23:49'),
(143, 'new_product', 'منتج جديد: بلاك عود', 'New Product: Black Aoud Montale', 'تمت إضافة منتج جديد إلى المتجر: بلاك عود', 'A new product has been added to the store: Black Aoud Montale', 'product_edit.php?id=129', 0, '2026-07-06 02:25:57'),
(144, 'new_product', 'منتج جديد: شهره', 'New Product: Shuhrah Rasasi', 'تمت إضافة منتج جديد إلى المتجر: شهره', 'A new product has been added to the store: Shuhrah Rasasi', 'product_edit.php?id=130', 0, '2026-07-06 02:27:53'),
(145, 'new_product', 'منتج جديد: عود فور جرتنس', 'New Product: Oud for Greatness', 'تمت إضافة منتج جديد إلى المتجر: عود فور جرتنس', 'A new product has been added to the store: Oud for Greatness', 'product_edit.php?id=131', 0, '2026-07-06 02:33:10'),
(146, 'new_product', 'منتج جديد: طرف', 'New Product: Taraf', 'تمت إضافة منتج جديد إلى المتجر: طرف', 'A new product has been added to the store: Taraf', 'product_edit.php?id=132', 0, '2026-07-06 02:37:38'),
(147, 'new_product', 'منتج جديد: مضاوي', 'New Product: Madawi', 'تمت إضافة منتج جديد إلى المتجر: مضاوي', 'A new product has been added to the store: Madawi', 'product_edit.php?id=133', 0, '2026-07-06 02:41:30'),
(148, 'new_product', 'منتج جديد: مضاوي جولد', 'New Product: Madawi Gold', 'تمت إضافة منتج جديد إلى المتجر: مضاوي جولد', 'A new product has been added to the store: Madawi Gold', 'product_edit.php?id=134', 0, '2026-07-06 02:43:17'),
(149, 'new_product', 'منتج جديد: كلمات', 'New Product: Kalemat', 'تمت إضافة منتج جديد إلى المتجر: كلمات', 'A new product has been added to the store: Kalemat', 'product_edit.php?id=135', 0, '2026-07-06 02:44:57'),
(150, 'new_product', 'منتج جديد: عود بوكيه', 'New Product: Oud Bouquet', 'تمت إضافة منتج جديد إلى المتجر: عود بوكيه', 'A new product has been added to the store: Oud Bouquet', 'product_edit.php?id=136', 0, '2026-07-07 07:33:23'),
(151, 'new_product', 'منتج جديد: وصال', 'New Product: Wisal', 'تمت إضافة منتج جديد إلى المتجر: وصال', 'A new product has been added to the store: Wisal', 'product_edit.php?id=137', 0, '2026-07-07 07:37:15'),
(152, 'new_product', 'منتج جديد: عطر فاخر ومميز.', 'New Product: Premium fragrance.', 'تمت إضافة منتج جديد إلى المتجر: عطر فاخر ومميز.', 'A new product has been added to the store: Premium fragrance.', 'product_edit.php?id=138', 0, '2026-07-22 22:42:33'),
(153, 'new_product', 'منتج جديد: عطر فاخر ومميز.', 'New Product: Premium fragrance.', 'تمت إضافة منتج جديد إلى المتجر: عطر فاخر ومميز.', 'A new product has been added to the store: Premium fragrance.', 'product_edit.php?id=139', 0, '2026-07-22 22:50:05'),
(154, 'new_product', 'منتج جديد: عطر فاخر ومميز.', 'New Product: Premium fragrance.', 'تمت إضافة منتج جديد إلى المتجر: عطر فاخر ومميز.', 'A new product has been added to the store: Premium fragrance.', 'product_edit.php?id=140', 0, '2026-07-22 22:53:50'),
(155, 'new_order', 'طلب جديد: MED-6D9E137F', 'New Order: MED-6D9E137F', 'لديك طلب جديد من Yassen Mostafa بقيمة 895.00 ج.م.', 'New order from Yassen Mostafa total 895.00 ج.م.', 'order_view.php?id=14', 0, '2026-07-29 03:22:40'),
(156, 'new_order', 'طلب جديد: MED-E2148594', 'New Order: MED-E2148594', 'لديك طلب جديد من test order بقيمة 970.00 ج.م.', 'New order from test order total 970.00 ج.م.', 'order_view.php?id=15', 0, '2026-07-31 02:57:16'),
(157, 'new_order', 'طلب جديد: MED-EBBFA4D7', 'New Order: MED-EBBFA4D7', 'لديك طلب جديد من test order بقيمة 1,620.00 ج.م.', 'New order from test order total 1,620.00 ج.م.', 'order_view.php?id=16', 0, '2026-07-31 04:56:08'),
(158, 'new_order', 'طلب جديد: MED-C24D1C76', 'New Order: MED-C24D1C76', 'لديك طلب جديد من test order بقيمة 1,370.00 ج.م.', 'New order from test order total 1,370.00 ج.م.', 'order_view.php?id=17', 0, '2026-07-31 04:56:51'),
(159, 'new_order', 'طلب جديد: MED-8DC296B2', 'New Order: MED-8DC296B2', 'لديك طلب جديد من test order بقيمة 1,370.00 ج.م.', 'New order from test order total 1,370.00 ج.م.', 'order_view.php?id=18', 0, '2026-07-31 04:57:17'),
(160, 'new_order', 'طلب جديد: MED-3AC6DDCB', 'New Order: MED-3AC6DDCB', 'لديك طلب جديد من test order بقيمة 1,370.00 ج.م.', 'New order from test order total 1,370.00 ج.م.', 'order_view.php?id=19', 0, '2026-07-31 05:18:43'),
(161, 'new_order', 'طلب جديد: MED-EF75799B', 'New Order: MED-EF75799B', 'لديك طلب جديد من test order بقيمة 1,370.00 ج.م.', 'New order from test order total 1,370.00 ج.م.', 'order_view.php?id=20', 0, '2026-07-31 05:19:04'),
(162, 'new_order', 'طلب جديد: MED-F1D9E122', 'New Order: MED-F1D9E122', 'لديك طلب جديد من test order بقيمة 1,370.00 ج.م.', 'New order from test order total 1,370.00 ج.م.', 'order_view.php?id=21', 0, '2026-07-31 05:29:36'),
(163, 'new_order', 'طلب جديد: MED-5A7BE24C', 'New Order: MED-5A7BE24C', 'لديك طلب جديد من test order بقيمة 895.00 ج.م.', 'New order from test order total 895.00 ج.م.', 'order_view.php?id=22', 0, '2026-07-31 05:32:04'),
(164, 'new_product', 'منتج جديد: تجربه', 'New Product: test', 'تمت إضافة منتج جديد إلى المتجر: تجربه', 'A new product has been added to the store: test', 'product_edit.php?id=141', 0, '2026-07-31 05:38:52'),
(165, 'new_order', 'طلب جديد: MED-1981C0C7', 'New Order: MED-1981C0C7', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=23', 0, '2026-07-31 05:39:19'),
(166, 'new_order', 'طلب جديد: MED-387FC0AB', 'New Order: MED-387FC0AB', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=24', 0, '2026-07-31 05:43:14'),
(167, 'new_order', 'طلب جديد: MED-2D6C5C24', 'New Order: MED-2D6C5C24', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=25', 0, '2026-07-31 05:49:27'),
(168, 'new_order', 'طلب جديد: MED-1570C108', 'New Order: MED-1570C108', 'لديك طلب جديد من test order بقيمة 1,720.00 ج.م.', 'New order from test order total 1,720.00 ج.م.', 'order_view.php?id=26', 0, '2026-07-31 05:53:30'),
(169, 'new_order', 'طلب جديد: MED-E77BD401', 'New Order: MED-E77BD401', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=27', 0, '2026-07-31 06:05:39'),
(170, 'new_order', 'طلب جديد: MED-2A9A78F1', 'New Order: MED-2A9A78F1', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=28', 0, '2026-07-31 06:08:50'),
(171, 'new_order', 'طلب جديد: MED-D8EA75A2', 'New Order: MED-D8EA75A2', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=29', 0, '2026-07-31 06:09:56'),
(172, 'new_order', 'طلب جديد: MED-409AD34E', 'New Order: MED-409AD34E', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=30', 0, '2026-07-31 06:17:24'),
(173, 'new_order', 'طلب جديد: MED-BD9C80ED', 'New Order: MED-BD9C80ED', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=31', 0, '2026-07-31 06:17:41'),
(174, 'new_order', 'طلب جديد: MED-AFEA7A8B', 'New Order: MED-AFEA7A8B', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=32', 0, '2026-07-31 20:41:28'),
(175, 'new_order', 'طلب جديد: MED-CEB0040F', 'New Order: MED-CEB0040F', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=33', 0, '2026-07-31 20:46:23'),
(176, 'new_order', 'طلب جديد: MED-0FAFDB6B', 'New Order: MED-0FAFDB6B', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=34', 0, '2026-07-31 20:46:58'),
(177, 'new_order', 'طلب جديد: MED-4FD62D6B', 'New Order: MED-4FD62D6B', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=35', 0, '2026-07-31 20:50:54'),
(178, 'new_order', 'طلب جديد: MED-462E82F2', 'New Order: MED-462E82F2', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=36', 0, '2026-07-31 20:53:25'),
(179, 'new_order', 'طلب جديد: MED-F5629B7F', 'New Order: MED-F5629B7F', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=37', 0, '2026-07-31 20:56:42'),
(180, 'new_order', 'طلب جديد: MED-0B42412F', 'New Order: MED-0B42412F', 'لديك طلب جديد من test order بقيمة 320.00 ج.م.', 'New order from test order total 320.00 ج.م.', 'order_view.php?id=38', 0, '2026-07-31 21:03:50');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'superadmin',
  `permissions` text DEFAULT NULL,
  `role_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`, `role`, `permissions`, `role_id`) VALUES
(1, 'admin', '$2y$10$hOH7evJnAgDVWZ1SCzfQYumpBi/3jxBSqfOZMMUeWm8s84FOwexR2', '2026-04-21 12:16:40', 'superadmin', NULL, NULL),
(9, 'Ahmed', '$2y$10$hR9MmOp4VL2DfWHdRNpok.5AvyhwXpSmbiB/js2g7VIM/231jEvoq', '2026-05-08 18:30:26', 'admin', 'products,promo_codes', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(10) UNSIGNED NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `is_popular` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(64) NOT NULL,
  `name_en` varchar(128) NOT NULL,
  `name_ar` varchar(128) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image` varchar(500) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `slug`, `name_en`, `name_ar`, `sort_order`, `image`) VALUES
(2, 'women', 'Women\'s perfumes', 'عطور نسائي', 10, 'cat_women_1780183079.png'),
(3, 'men', 'Men', 'رجال', 20, 'cat_men_1780306520.png'),
(55, 'niche-perfumes', 'Niche Perfumes', 'عطور نيش', 1, ''),
(56, 'designer-perfumes', 'Designer Perfumes', 'عطور دزاينر', 2, ''),
(58, 'creation-zein-perfume-s', 'Creation zein perfume\'s', 'عطور زين الخاصه', 25, '');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `password_hash` varchar(255) DEFAULT NULL,
  `otp_code` varchar(16) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `social_id` varchar(255) DEFAULT NULL,
  `social_provider` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `phone`, `email`, `created_at`, `password_hash`, `otp_code`, `otp_expires_at`, `is_verified`, `social_id`, `social_provider`) VALUES
(3, 'Yassen Mostafa', '01141058632', 'yassen47mostafa@gmail.com', '2026-05-15 19:18:19', NULL, NULL, NULL, 0, NULL, NULL),
(4, 'amr', '01157686224', 'amrsamir2088@gmail.com', '2026-06-01 14:20:05', '$2y$10$lGZIjEoVlhv2d1AriKSTn.ino6t4ablPPs9EgsUTQMEdpm50IRH/W', NULL, NULL, 1, NULL, NULL),
(5, 'test order', '01141058632', 'yassen74mostafa@gmail.com', '2026-07-29 03:22:40', NULL, NULL, NULL, 0, NULL, NULL),
(6, 'test user', '0111111111', 'test@example.com', '2026-07-31 02:56:17', '$2y$10$4bqzwHX5yGUwCcyN5l9y4.K4/0NOLuR3932JHgRZe7vFNAhME570W', NULL, NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`, `read_at`) VALUES
(1, 'Jake Rover', 'k2za.unsworn423@passmail.com', 'Hey would you like to acquire the domain name inperfumes.com?', '2026-05-05 03:01:01', '2026-05-30 16:13:56'),
(2, 'GeraldCip', 'jacksrenome@gmx.com', 'YyErjcwdkdjwjjwjjdwjddjwsjf ndsaKAqwdweihduncbbwebidaa iudwnishqwuvdwqihbfvweuiojsqjqioqdefiw dwqsqwijbfiewdncbhvdifqhioqsjnqw zeinperfumes.com', '2026-06-08 01:03:13', '2026-06-18 15:55:48'),
(3, 'Russelltot', 'joannadixon94@gmail.com', 'TODAY’S THE PERFECT DAY TO AIM FOR THE $27,000,000 JACKPOT https://jbiv.com/HspZP', '2026-06-12 07:24:45', '2026-06-18 15:55:47'),
(5, 'Russelltot', 'mamaduds14@gmail.com', 'STEP UP AND STAKE YOUR CLAIM TO THE $27,000,000 JACKPOT https://s.ubyt.es/v72E34', '2026-06-20 00:51:34', NULL),
(6, 'Russelltot', 'bill.richards611@yahoo.com', 'The $27,000,000 Jackpot Is a Laurel for Luck https://3scomputers.com/omceK', '2026-06-25 06:50:22', NULL),
(9, 'Mahmoud', 'mahmoud907mohamed@gmail.com', 'يُباح التعطرُ للنساء داخل المنزل، \r\n وهو مُستحبّ إذا كان بهدف إدخال السرور على قلب زوجها، ولكنّه يصبح مُحرماً في حالة التعطر والخروج بقصد أن يشمَّه الرجال الأجانب، وتُؤثم المرأة التي تفعل ذلك، لأنّ في عطرها فتنة للرجال.\r\nبنذكر بعض بس 😊', '2026-06-25 22:00:31', NULL),
(10, 'Russelltot', 'contact@theviptraveller.com.au', 'THE $27,000,000 JACKPOT IS A NETWORK OF NET WORTH https://short.vird.co/VjZyh', '2026-07-02 23:41:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_en` text NOT NULL,
  `question_ar` text NOT NULL,
  `answer_en` text NOT NULL,
  `answer_ar` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question_en`, `question_ar`, `answer_en`, `answer_ar`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'How long does delivery take?', 'كم مدة الشحن؟', 'Typically 2–5 business days depending on the region. Tracking details will be shared via email or WhatsApp once dispatched.', 'الشحن يتم من يومين ل 4 أيام كحد أقصى من بعد مكالمة التأكيد وبيكون من خلال شركة أراميكس', 10, '2026-06-02 11:38:29', '2026-06-25 23:07:57'),
(2, 'Can I return or exchange my order?', 'هل يمكن الإرجاع أو الاستبدال؟', 'Unused items in original packaging may be exchanged within a short window. Opened perfumes cannot be returned for hygiene reasons.', 'يوجد استبدال واسترجاع وبيكون من خلال شركة اراميكس وقيمة الطرد 245 جنيه. يرجى مراجعة صفحة سياسة', 20, '2026-06-02 11:38:29', '2026-06-25 23:11:40'),
(3, 'What alcohol is used?', 'ما نوع الكحول المستخدم؟', 'We use high-quality cosmetic-grade ethanol in line with industry standards for fine fragrance.', 'نستخدم كحول إيثانول طبي عالي الجودة وفق معايير صناعة العطور.', 30, '2026-06-02 11:38:29', '2026-06-25 23:12:42'),
(4, 'Do all perfumes perform the same?', 'هل ثبات كل العطور واحد؟', 'Longevity and projection vary by composition, skin chemistry, and application. Heavier bases often last longer than very airy citrus openings.', 'الثبات والفوحان يختلفان حسب التركيبة وبشرتك وطريقة الاستخدام. القواعد الأثقل غالبًا أطول من الافتتاحيات الحمضية الخفيفة.', 1, '2026-06-02 11:38:29', '2026-06-25 23:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_offers`
--

CREATE TABLE `homepage_offers` (
  `id` int(10) UNSIGNED NOT NULL,
  `image_key` varchar(128) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homepage_offers`
--

INSERT INTO `homepage_offers` (`id`, `image_key`, `link_url`, `sort_order`) VALUES
(44, 'img_6a1ad9678838e3.68286676.jpeg', '', 0),
(45, 'img_6a1ad96adaa3d8.81536791.jpeg', '', 1),
(46, 'img_6a1ad96e787cf4.66024559.jpg', '', 2),
(47, 'img_6a1ad9869a8829.65730388.jpg', '', 3),
(48, 'img_6a1ad9890904a2.34871210.jpg', '', 4),
(49, 'img_6a1ad98c1c6528.66885183.jpg', '', 5),
(50, 'img_6a1ad991acf089.55357415.png', '', 6),
(51, 'img_6a1ad99697afe0.62304562.png', '', 7);

-- --------------------------------------------------------

--
-- Table structure for table `internal_products`
--

CREATE TABLE `internal_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `type` enum('gift','sample','promotional') DEFAULT 'gift',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internal_products`
--

INSERT INTO `internal_products` (`id`, `name_en`, `name_ar`, `description`, `cost`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Welcome Gift Box', ' puzzles', 'A special gift box for new customers with sample products', 15.00, 'gift', '2026-05-30 13:33:37', '2026-05-30 13:33:37'),
(2, 'Perfume Sample Set', ' puzzles', 'Collection of perfume samples for customers to try', 5.00, 'sample', '2026-05-30 13:33:37', '2026-05-30 13:33:37'),
(3, 'Loyalty Reward', ' puzzles', 'Special reward for loyal customers', 25.00, 'promotional', '2026-05-30 13:33:37', '2026-05-30 13:33:37');

-- --------------------------------------------------------

--
-- Table structure for table `offer_bundles`
--

CREATE TABLE `offer_bundles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `description_ar` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `image_key` varchar(500) DEFAULT '',
  `discount_type` enum('none','percent','fixed') DEFAULT 'none',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `variant_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` int(11) DEFAULT 2,
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offer_bundle_products`
--

CREATE TABLE `offer_bundle_products` (
  `bundle_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(32) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `delivered_at` datetime DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(64) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `city` varchar(128) DEFAULT NULL,
  `address_landmark` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `promo_code` varchar(64) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_conf_sent` tinyint(1) DEFAULT 0,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `waived_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(64) DEFAULT 'paymob',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `paymob_order_id` varchar(128) DEFAULT NULL,
  `paymob_transaction_id` varchar(128) DEFAULT NULL,
  `kashier_order_id` varchar(128) DEFAULT NULL,
  `kashier_transaction_id` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `status`, `delivered_at`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `city`, `address_landmark`, `admin_notes`, `promo_code`, `subtotal`, `discount_amount`, `shipping_cost`, `total`, `created_at`, `updated_at`, `email_conf_sent`, `paid_amount`, `waived_amount`, `payment_method`, `payment_status`, `paymob_order_id`, `paymob_transaction_id`, `kashier_order_id`, `kashier_transaction_id`) VALUES
(22, 'MED-5A7BE24C', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 775.00, NULL, 120.00, 895.00, '2026-07-31 05:32:04', '2026-07-31 05:32:08', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_e89716d04392499e9d4d65056f09239c', NULL, NULL, NULL),
(23, 'MED-1981C0C7', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 05:39:19', '2026-07-31 05:39:22', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_2e65430e65b049d8bdd43f2460d3b4e0', NULL, NULL, NULL),
(24, 'MED-387FC0AB', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 05:43:14', '2026-07-31 05:43:17', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_da26addeb33844759e496cbc52342658', NULL, NULL, NULL),
(25, 'MED-2D6C5C24', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 05:49:27', '2026-07-31 05:49:31', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_096a28b1359a47d5b90929e57b465638', NULL, NULL, NULL),
(26, 'MED-1570C108', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 1600.00, NULL, 120.00, 1720.00, '2026-07-31 05:53:30', '2026-07-31 05:53:32', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_9813cc53c6d743489235f73423c517e3', NULL, NULL, NULL),
(27, 'MED-E77BD401', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 06:05:39', '2026-07-31 06:05:41', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_408a4d464efe4b84bf2c044b005441bb', NULL, NULL, NULL),
(28, 'MED-2A9A78F1', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 06:08:50', '2026-07-31 06:08:53', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_55c7425bb3774c19baaa878d7de9921f', NULL, NULL, NULL),
(29, 'MED-D8EA75A2', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 06:09:56', '2026-07-31 06:09:59', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_9523b6424db9439e9f4b99b8ffb64603', NULL, NULL, NULL),
(30, 'MED-409AD34E', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 06:17:24', '2026-07-31 06:17:27', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_6cd3e68db36947a2be0bd52649297d5b', NULL, NULL, NULL),
(31, 'MED-BD9C80ED', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 06:17:41', '2026-07-31 06:17:44', 0, 0.00, 0.00, 'paymob', 'pending', 'pi_test_5b1dd82bc6004250b553271e22946742', NULL, NULL, NULL),
(32, 'MED-AFEA7A8B', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 20:41:28', '2026-07-31 20:41:28', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, NULL, NULL),
(33, 'MED-CEB0040F', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 20:46:23', '2026-07-31 20:46:26', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, 'MED-CEB0040F', NULL),
(34, 'MED-0FAFDB6B', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 20:46:58', '2026-07-31 20:47:00', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, 'MED-0FAFDB6B', NULL),
(35, 'MED-4FD62D6B', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 20:50:54', '2026-07-31 20:50:56', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, 'MED-4FD62D6B', NULL),
(36, 'MED-462E82F2', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 20:53:25', '2026-07-31 20:53:27', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, 'MED-462E82F2', NULL),
(37, 'MED-F5629B7F', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 20:56:42', '2026-07-31 20:56:44', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, 'MED-F5629B7F', NULL),
(38, 'MED-0B42412F', 'pending', NULL, 'test order', 'yassen74mostafa@gmail.com', '01141058632', 'test location', 'بورسعيد', NULL, NULL, NULL, 200.00, NULL, 120.00, 320.00, '2026-07-31 21:03:50', '2026-07-31 21:03:52', 0, 0.00, 0.00, 'kashier', 'pending', NULL, NULL, 'MED-0B42412F', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_internal_products`
--

CREATE TABLE `order_internal_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `internal_product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `variant_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name_snapshot` varchar(255) NOT NULL,
  `variant_label_snapshot` varchar(255) DEFAULT NULL,
  `qty` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `product_name_snapshot`, `variant_label_snapshot`, `qty`, `unit_price`, `line_total`) VALUES
(23, 22, 111, 364, 'جود جيرل', '100 ml', 1, 775.00, 775.00),
(24, 23, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(25, 24, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(26, 25, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(27, 26, 141, 445, 'تجربه', 'Original', 8, 200.00, 1600.00),
(28, 27, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(29, 28, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(30, 29, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(31, 30, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(32, 31, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(33, 32, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(34, 33, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(35, 34, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(36, 35, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(37, 36, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(38, 37, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00),
(39, 38, 141, 445, 'تجربه', 'Original', 1, 200.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(128) NOT NULL,
  `category` varchar(64) NOT NULL,
  `season` enum('winter','summer','both') NOT NULL DEFAULT 'both',
  `is_bestseller` tinyint(1) NOT NULL DEFAULT 0,
  `is_offer` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `notes_en` text DEFAULT NULL,
  `notes_ar` text DEFAULT NULL,
  `description_en` text NOT NULL,
  `description_ar` text NOT NULL,
  `primary_image_key` varchar(128) NOT NULL DEFAULT 'default',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `file_sharing_url` text DEFAULT NULL,
  `brand_id` int(10) UNSIGNED DEFAULT NULL,
  `is_brand_product` tinyint(1) NOT NULL DEFAULT 0,
  `ai_profile_ar` text DEFAULT NULL,
  `ai_keywords_ar` text DEFAULT NULL,
  `ai_intensity` varchar(32) DEFAULT NULL,
  `ai_longevity` varchar(32) DEFAULT NULL,
  `ai_sillage` varchar(32) DEFAULT NULL,
  `ai_best_for` varchar(255) DEFAULT NULL,
  `ai_sensitivity_safe` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `slug`, `category`, `season`, `is_bestseller`, `is_offer`, `view_count`, `active`, `name_en`, `name_ar`, `notes_en`, `notes_ar`, `description_en`, `description_ar`, `primary_image_key`, `sort_order`, `created_at`, `updated_at`, `file_sharing_url`, `brand_id`, `is_brand_product`, `ai_profile_ar`, `ai_keywords_ar`, `ai_intensity`, `ai_longevity`, `ai_sillage`, `ai_best_for`, `ai_sensitivity_safe`) VALUES
(11, 'ombre-leather', 'niche-perfumes', 'both', 0, 0, 28, 1, 'Ombre Leather', 'او مبري ليذر', '', '', 'Ombré Leather (2018) by Tom Ford is a Leather fragrance for women and men. Ombré Leather (2018) was launched in 2018. The nose behind this fragrance is Sonia Constant. Top note is Cardamom; middle notes are Leather and Jasmine Sambac; base notes are Amber, Moss and Patchouli.', 'عطر أومبريه ليذر (2018) من توم فورد هو عطر جلدي للجنسين. أُطلق هذا العطر عام 2018، وهو من ابتكار خبيرة العطور سونيا كونستانت. تتكون مقدمة العطر من الهيل، وقلبه من الجلد والياسمين، وقاعدته من العنبر والطحلب والباتشولي.', 'img_6a3436d2904965.04611872.webp', 0, '2026-06-18 18:20:49', '2026-08-07 12:09:01', '', 0, 0, 'عطر هادي و شيك جداً للصيف', 'هادي و شيك', 'قوي', 'عالي', 'عالي', 'شغل و الخروج بين الناس', 1),
(12, 'erba-pura', 'niche-perfumes', 'both', 0, 0, 26, 1, 'Erba Pura', 'ايربا بورا', '', '', 'Erba Pura by Xerjoff is an oriental unisex fragrance. Erba Pura was launched in 2019. Erba Pura was created by Christian Carbonnel and Laura Santander. The top notes are Sicilian orange, Calabrian bergamot, and Sicilian lemon; the heart notes are fruity; and the base notes are white musk, Madagascan vanilla, and amber.', 'Erba Pura Xerjoff عطر شرقي للجنسين. Erba Pura صدر عام 2019. Erba Pura من توقيع Christian Carbonnel و Laura Santander. إفتتاحية العطر البرتقال الصقلي, برغموت كالابريا و الليمون الصقلي; قلب العطر الفواكه; قاعدة العطر تتكون من المسك الأبيض, فانيليا مدغشقر و العنبر.', 'img_6a3437f421d023.92384874.webp', 0, '2026-06-18 18:25:28', '2026-08-08 12:13:01', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(13, 'altha-r-parfums-de-marly', 'niche-perfumes', 'both', 0, 0, 32, 1, 'Althaïr Parfums de Marly', 'الثائر', '', '', 'Althaïr by Parfums de Marly is an oriental-vanilla fragrance for men. Althaïr was launched in 2023. Althaïr was created by Hamid Merati-Kashani and Ilias Ermenidis. The top notes are cinnamon, orange blossom, cardamom, and bergamot; the middle notes are Bourbon vanilla and elemi; and the base notes are praline, musk, ambroxan, guaiac wood, tonka bean, and candied almond.', 'Althaïr Parfums de Marly عطر شرقي - فانيليا للرجال . Althaïr صدر عام 2023. Althaïr من توقيع Hamid Merati-Kashani و Ilias Ermenidis. إفتتاحية العطر القرفة, زهر البرتقال, الهيل و البرغموت; قلب العطر فانيليا بوربون و الإليمي; قاعدة العطر تتكون من حلوي اللوز, المسك, الأمبروكسان, أخشاب الغاياك, حبوب التونكا و اللوز المحلى.', 'img_6a34393f79c5a0.57904810.webp', 0, '2026-06-18 18:31:28', '2026-08-11 00:55:17', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(14, 'layton-parfums-de-marly', 'niche-perfumes', 'both', 0, 0, 8, 1, 'Layton Parfums de Marly', 'لايتون', '', '', 'Layton by Parfums de Marly is an oriental-floral fragrance for both men and women. Layton was launched in 2016. The nose behind this fragrance is Hamid Merati-Kashani. Top notes are apple, lavender, bergamot, and mandarin orange; middle notes are geranium, violet, and jasmine; base notes are vanilla, cardamom, sandalwood, pepper, guaiac wood, patchouli, ambermax, and coumarin.', 'Layton Parfums de Marly عطر شرقي - زهري للجنسين. Layton صدر عام 2016. Hamid Merati-Kashani قام بتوقيع هذا العطر. إفتتاحية العطر التفاح, الخزامي, البرغموت و الماندرين (اليوسفي); قلب العطر إبره الراعي, البنفسج و الياسمين; قاعدة العطر تتكون من الفانيليا, الهيل, خشب الصندل, الفلفل, أخشاب الغاياك, الباتشولي, أمبرماكس (Ambermax) و الكومارين.', 'img_6a3439c2786a28.59992722.webp', 0, '2026-06-18 18:34:19', '2026-08-07 12:14:14', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(15, 'imagination', 'niche-perfumes', 'both', 1, 0, 25, 1, 'Imagination', 'إيماجينيشن', '', '', 'Imagination by Louis Vuitton is a citrus-aromatic fragrance for men. Imagination was launched in 2021. The nose behind this fragrance is Jacques Cavallier Belletrud. Top notes are citron, Calabrian bergamot, and Sicilian orange; middle notes are Tunisian orange blossom, Nigerian ginger, and Ceylon cinnamon; base notes are Chinese black tea, ambroxan, guaiac wood, and frankincense.', 'Imagination Louis Vuitton عطر حمضيات - أروماتك للرجال . Imagination صدر عام 2021. Jacques Cavallier Belletrud قام بتوقيع هذا العطر. إفتتاحية العطر الأترج, برغموت كالابريا و البرتقال الصقلي; قلب العطر زهر البرتقال التونسي, الزنجبيل النيجيري و قرفة سيلان; قاعدة العطر تتكون من الشاي الصيني الأسود, الأمبروكسان, أخشاب الغاياك و اللبان.', 'img_6a343a63d97d83.38808985.webp', 0, '2026-06-18 18:36:29', '2026-07-31 21:04:53', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(16, 'angels-share', 'niche-perfumes', 'both', 0, 0, 6, 1, 'Angels’ share', 'انجل شير', '', '', 'Angels\' Share by Kilian is an oriental-vanilla fragrance for both men and women. Angels\' Share was launched in 2020. The nose behind this fragrance is Benoist Lapouza. The top note is cognac; the middle notes are cinnamon, tonka bean, oakmoss, and hedione; the base notes are vanilla, praline, sandalwood, and candied almond.', 'Angels\' Share By Kilian عطر شرقي - فانيليا للجنسين. Angels\' Share صدر عام 2020. Benoist Lapouza قام بتوقيع هذا العطر. إفتتاحية العطر الكونياك; قلب العطر القرفة, حبوب التونكا, البلوط و جزئ هديون; قاعدة العطر تتكون من الفانيليا, حلوي اللوز, خشب الصندل و اللوز المحلى.', 'img_6a343b5c2fedf1.69423871.webp', 0, '2026-06-18 18:39:42', '2026-08-17 10:11:59', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(17, 'noir-extreme-tom-ford', 'niche-perfumes', 'both', 0, 0, 18, 1, 'Noir Extreme Tom Ford', 'اكستريم نوار', '', '', 'Noir Extreme by Tom Ford is an oriental-woody fragrance for men. Noir Extreme was launched in 2015. The nose behind this fragrance is Sonia Constant. Top notes are cardamom, nutmeg, saffron, mandarin orange, and neroli; middle notes are kulfi, rose, mastic, orange blossom, and jasmine; base notes are vanilla, amber, woods, and sandalwood.', 'Noir Extreme Tom Ford عطر شرقي - خشبي للرجال . Noir Extreme صدر عام 2015. Sonia Constant قام بتوقيع هذا العطر. إفتتاحية العطر الهيل, جوزه الطيب, الزعفران, الماندرين (اليوسفي) و النيرولي; قلب العطر حلوي الكولفي, الورد, المستكة, زهر البرتقال و الياسمين; قاعدة العطر تتكون من الفانيليا, العنبر, الأخشاب و خشب الصندل.', 'img_6a343bd670d739.06959762.webp', 0, '2026-06-18 18:42:40', '2026-08-07 12:13:39', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(18, 'amouage-decision', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Amouage Decision', 'أمواج ديسيجن', '', '', 'Decision by Amouage is a woody-aromatic fragrance for both men and women. This new fragrance, Decision, was launched in 2025. The nose behind this fragrance is Quentin Bisch. The top notes are cardamom, bergamot, and pink pepper; the middle notes are frankincense, juniper berries, and myrrh; and the base notes are vanilla, cedarwood, and patchouli.', 'Decision Amouage عطر خشبي - أروماتك للجنسين. هذا عطر جديد Decision صدر عام 2025. Quentin Bisch قام بتوقيع هذا العطر. إفتتاحية العطر الهيل, البرغموت و الفلفل الوردي; قلب العطر اللبان, توت العرعر و المر; قاعدة العطر تتكون من الفانيليا, خشب الأرز و الباتشولي.', 'img_6a343c93d086c1.98746795.webp', 0, '2026-06-18 18:45:05', '2026-07-21 13:18:49', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(19, 'amouage-guidance-46', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Amouage Guidance 46', 'أمواج جايدينس 46', '', '', 'Guidance 46 by Amouage is an oriental-floral fragrance for both men and women. Guidance 46 was launched in 2024. The nose behind this fragrance is Quentin Bisch. Top notes are rose water, pear, hazelnut, bitter almond, frankincense, and pink pepper; middle notes are rose, osmanthus, saffron, and jasmine sambac; base notes are sandalwood, akgalwood, vanilla, ambrette, labdanum, ambergris, gorse wood, and nagramucha.', 'Guidance 46 Amouage عطر شرقي - زهري للجنسين. Guidance 46 صدر عام 2024. Quentin Bisch قام بتوقيع هذا العطر. إفتتاحية العطر ماء الورد, الكمثري, البندق, اللوز المر, اللبان و الفلفل الوردي; قلب العطر الورد, أوسمانثوس , الزعفران و ياسمين سامباك; قاعدة العطر تتكون من خشب الصندل, أكيغالاوود, الفانيليا, الأمبريت, اللابدانوم, الآمبرغريس, جورجي وود و السيبرول (الناجراموثا).', 'img_6a343d26bb8742.47371903.webp', 0, '2026-06-18 18:48:11', '2026-06-30 12:10:17', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(20, 'instant-crush', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Instant Crush', 'انستانت كراش', '', '', 'Instant Crush by Mancera is an oriental-floral fragrance for both men and women. Instant Crush was launched in 2019. The nose behind this fragrance is Pierre Montale. Top notes are saffron, ginger, Sicilian mandarin, and Sicilian bergamot; middle notes are amberwood, Moroccan rose, Egyptian jasmine, and Indonesian patchouli leaves; base notes are Madagascan vanilla, white musk, sandalwood, and oakmoss.', 'Instant Crush Mancera عطر شرقي - زهري للجنسين. Instant Crush صدر عام 2019. Pierre Montale قام بتوقيع هذا العطر. إفتتاحية العطر الزعفران, الزنجبيل, الماندرين الصقلي و البرغموت الصقلي; قلب العطر خشب العنبر, الورد المغربي, الياسمين المصري و أوراق الباتشولي الإندونيسي; قاعدة العطر تتكون من فانيليا مدغشقر, المسك الأبيض, خشب الصندل و طحلب البلوط (طحلب السنديان).', 'img_6a343da7096157.46057706.webp', 0, '2026-06-18 18:50:55', '2026-07-03 21:02:07', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(21, 'red-tobacco', 'niche-perfumes', 'both', 0, 0, 4, 1, 'Red Tobacco', 'ريد توباكو', '', '', 'Red Tobacco by Mancera is a woody-spicy fragrance for both men and women. Red Tobacco was launched in 2017. The nose behind this fragrance is Pierre Montale. Top notes are cinnamon, oud, incense, saffron, nutmeg, green apple, and white pear; middle notes are patchouli and jasmine; base notes are tobacco, Madagascan vanilla, amber, sandalwood, guaiac wood, white musk, and Haitian vetiver.', 'Red Tobacco Mancera عطر خشبي - حار للجنسين. Red Tobacco صدر عام 2017. Pierre Montale قام بتوقيع هذا العطر. إفتتاحية العطر القرفة, العود, البخور, الزعفران, جوزه الطيب, التفاح الأخضر و الكمثري البيضاء; قلب العطر الباتشولي و الياسمين; قاعدة العطر تتكون من التبغ, فانيليا مدغشقر, العنبر, خشب الصندل, أخشاب الغاياك, المسك الأبيض و نجيل الهند من هايتي.', 'img_6a343ea2c72156.17437254.webp', 0, '2026-06-18 18:53:39', '2026-07-21 13:39:24', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(22, 'bois-imperial', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Bois Imperial', 'بوا امبريال', '', '', 'Bois Impérial Essential Parfums is an aromatic unisex fragrance. Bois Impérial was launched in 2020. The nose behind this fragrance is Quentin Bisch. Fragrance notes include Akigalawood, woods, basil, vetiver, Timor pepper, ambroxan, and patchouli.', 'Bois Impérial Essential Parfums عطر أروماتك للجنسين. Bois Impérial صدر عام 2020. Quentin Bisch قام بتوقيع هذا العطر. معلومات عن العطر أكيغالاوود, الأخشاب, الريحان, نجيل الهند, فلفل تيمور, الأمبروكسان و الباتشولي.', 'img_6a343f449ac1a6.44730526.webp', 0, '2026-06-18 18:56:21', '2026-07-07 17:15:54', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(23, 'spicebomb-extreme', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Spicebomb Extreme', 'سبايس بومب اكستريم', '', '', 'Spicebomb Extreme by Viktor&Rolf is an oriental-spicy fragrance for men. Spicebomb Extreme was launched in 2015. Spicebomb Extreme was created by Carlos Benaïm and Jean-Christophe Hérault. Fragrance notes include vanilla, tobacco, cinnamon, cumin, bourbon, and saffron.', 'Spicebomb Extreme Viktor&Rolf عطر شرقي - حار للرجال . Spicebomb Extreme صدر عام 2015. Spicebomb Extreme من توقيع Carlos Benaïm و Jean-Christophe Hérault. معلومات عن العطر الفانيليا, التبغ, القرفة, الكمون, البوربون و الزعفران.', 'img_6a343fded54ef1.43098027.webp', 0, '2026-06-18 19:00:12', '2026-08-08 12:14:02', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(24, 'black-afgano', 'niche-perfumes', 'both', 0, 0, 0, 1, 'Black Afgano', 'بلاك افغانو', '', '', 'Black Afgano by Nasomatto is a woody-aromatic fragrance for both men and women. Black Afgano was launched in 2009. The nose behind this fragrance is Alessandro Gualtieri. Top notes are cannabis, green notes, davana, saffron, and thyme; middle notes are resins, woods, tobacco, coffee, cinnamon, violet, and raspberry; base notes are agarwood (oud), incense, amber, an animalic note, guaiac wood, musk, tonka bean, cedarwood, gurjun balsam, ambroxan, and vanilla.', 'Black Afgano Nasomatto عطر خشبي - أروماتك للجنسين. Black Afgano صدر عام 2009. Alessandro Gualtieri قام بتوقيع هذا العطر. إفتتاحية العطر القنب, النوتات الخضراء, الدافانا, الزعفران و الزعتر; قلب العطر الراتينجات, الأخشاب, التبغ, القهوه, القرفة, البنفسج و توت العليق; قاعدة العطر تتكون من العود, البخور, العنبر, نوتة حيوانية, أخشاب الغاياك, المسك, حبوب التونكا, خشب الأرز, بلسم غرجان, الأمبروكسان و الفانيليا.', 'img_6a3440bed77508.30759901.webp', 0, '2026-06-18 19:02:55', '2026-06-19 23:11:50', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(25, 'pacific-chill', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Pacific Chill', 'باسيفيك شيل', '', '', 'Pacific Chill by Louis Vuitton is an aromatic-fruity fragrance for both men and women. Pacific Chill was launched in 2023. The nose behind this fragrance is Jacques Cavallier Belletrud. Top notes are citron, orange, lemon, mint, blackcurrant, and coriander; middle notes are apricot, basil, carrot seeds, and May rose; base notes are fig, date, and ambrette.', 'Pacific Chill Louis Vuitton عطر أروماتك - فواكه للجنسين. Pacific Chill صدر عام 2023. Jacques Cavallier Belletrud قام بتوقيع هذا العطر. إفتتاحية العطر الأترج, البرتقال, الليمون, النعناع, الكشمش الأسود و الكزبرة; قلب العطر المشمش, الريحان, بذور الجزر و ورد ماي; قاعدة العطر تتكون من ثمار التين, التمر/البلح و الأمبريت.', 'img_6a34415b349292.33727257.webp', 0, '2026-06-18 19:05:35', '2026-07-23 13:41:53', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(26, 'baccarat-rouge', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Baccarat Rouge', 'بكرات روج', '', '', 'Baccarat Rouge 540 Extrait de Parfum by Maison Francis Kurkdjian is an oriental-floral fragrance for both men and women. Baccarat Rouge 540 Extrait de Parfum was launched in 2017. The nose behind this fragrance is Francis Kurkdjian. Top notes are bitter almond and saffron; middle notes are Egyptian jasmine and Virginia cedarwood; base notes are ambergris, woods, musk, ambroxan, and cashmere wood.', 'Baccarat Rouge 540 Extrait de Parfum Maison Francis Kurkdjian عطر شرقي - زهري للجنسين. Baccarat Rouge 540 Extrait de Parfum صدر عام 2017. Francis Kurkdjian قام بتوقيع هذا العطر. إفتتاحية العطر اللوز المر و الزعفران; قلب العطر الياسمين المصري و خشب الأرز من فرجينيا; قاعدة العطر تتكون من الآمبرغريس, الأخشاب, المسك, الأمبروكسان و أخشاب الكشمير.', 'img_6a3441dd0383b8.65884518.webp', 0, '2026-06-18 19:08:10', '2026-07-05 21:22:04', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(27, 'stellar-times', 'niche-perfumes', 'both', 0, 0, 7, 1, 'Stellar Times', 'ستيلر تايمز', '', '', 'Stellar Times by Louis Vuitton is an oriental-floral fragrance for both men and women. Stellar Times was launched in 2021. The nose behind this fragrance is Jacques Cavallier Belletrud. Fragrance notes include orange blossom, amber, and woods.', 'Stellar Times Louis Vuitton عطر شرقي - زهري للجنسين. Stellar Times صدر عام 2021. Jacques Cavallier Belletrud قام بتوقيع هذا العطر. معلومات عن العطر زهر البرتقال, العنبر و الأخشاب.', 'img_6a344331229ee0.98771501.webp', 0, '2026-06-18 19:13:25', '2026-08-09 15:06:20', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(28, 'side-effect-initio', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Side Effect Initio', 'سايد افكت', '', '', 'Side Effect by Initio Parfums Prives is an oriental unisex fragrance. Side Effect was launched in 2016. Fragrance notes include rum, tobacco, cinnamon, saffron, sandalwood, and hedione.', 'Side Effect Initio Parfums Prives عطر شرقي للجنسين. Side Effect صدر عام 2016. معلومات عن العطر الروم, التبغ, القرفة, الزعفران, خشب الصندل و جزئ هديون.', 'img_6a34ad76439d94.69198031.webp', 0, '2026-06-19 02:46:35', '2026-07-21 13:30:56', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(29, 'sauvage-elixir', 'niche-perfumes', 'both', 0, 0, 17, 1, 'Sauvage Elixir', 'سوفاج الكسير', '', '', 'Sauvage Elixir by Dior is an aromatic fragrance for men. Sauvage Elixir was launched in 2021. The nose behind this fragrance is François Demachy. Top notes are nutmeg, cinnamon, cardamom, and grapefruit; middle note is lavender; base notes are licorice, sandalwood, amber, patchouli, and Haitian vetiver.', 'Sauvage Elixir Dior عطر أروماتك للرجال . Sauvage Elixir صدر عام 2021. François Demachy قام بتوقيع هذا العطر. إفتتاحية العطر جوزه الطيب, القرفة, الهيل و الجريب فروت; قلب العطر الخزامي; قاعدة العطر تتكون من العرقسوس, خشب الصندل, العنبر, الباتشولي و نجيل الهند من هايتي.', 'img_6a34adf498e8b6.37168628.webp', 0, '2026-06-19 02:48:42', '2026-07-09 09:40:09', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(30, 'symphony', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Symphony', 'سيمفوني', '', '', 'Symphony by Louis Vuitton is a unisex citrus fragrance. It was launched in 2021. The nose behind this fragrance is Jacques Cavallier Belletrud. The fragrance features notes of grapefruit, bergamot, and ginger.', 'Symphony Louis Vuitton عطر الحمضيات للجنسين. Symphony صدر عام 2021. Jacques Cavallier Belletrud قام بتوقيع هذا العطر. معلومات عن العطر الجريب فروت, البرغموت و الزنجبيل.', 'img_6a34ae3af039c3.31668875.webp', 0, '2026-06-19 02:50:41', '2026-07-05 10:01:45', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(31, 'tobacco-vanille', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Tobacco Vanille', 'توباكو فانيليا', '', '', 'Tobacco Vanille by Tom Ford is an oriental-spicy fragrance for both men and women. Tobacco Vanille was launched in 2007. The nose behind this fragrance is Olivier Gillotin. The top notes are tobacco leaf and spices; the middle notes are vanilla, cacao, tonka bean, and tobacco blossom; and the base notes are dried fruits and woods.', 'Tobacco Vanille Tom Ford عطر شرقي - حار للجنسين. Tobacco Vanille صدر عام 2007. Olivier Gillotin قام بتوقيع هذا العطر. إفتتاحية العطر أوراق التبغ و رائحه التوابل; قلب العطر الفانيليا, الكاكاو, حبوب التونكا و زهر التبغ; قاعدة العطر تتكون من الفواكه المجففة و الأخشاب.', 'img_6a34aeeb067882.81644759.webp', 0, '2026-06-19 02:52:51', '2026-07-06 22:16:12', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(32, 'tygar-bvlgari', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Tygar Bvlgari', 'تايجر بلغاري', '', '', 'Tygar by Bvlgari is a Citrus Aromatic fragrance for men. Tygar was launched in 2016. The nose behind this fragrance is Jacques Cavallier Belletrud. Top note is Grapefruit; middle notes are Ginger and Ambrette; base notes are Ambroxan, Musk, Vetiver and Patchouli.', 'عطر تايجار من بولغاري هو عطر حمضي عطري للرجال. أُطلق تايجار عام ٢٠١٦. مصمم هذا العطر هو جاك كافالييه بيلترود. تتكون مقدمة العطر من الجريب فروت، وقلبه من الزنجبيل والأمبريت، وقاعدته من الأمبروكسان والمسك ونجيل الهند والباتشولي.', 'img_6a34af6e170860.15417470.webp', 0, '2026-06-19 02:55:42', '2026-07-06 00:12:18', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(33, 'tuxedo', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Tuxedo', 'توكسيدو', '', '', 'Tuxedo by Yves Saint Laurent is a chypre unisex fragrance. Tuxedo was launched in 2015. The nose behind this fragrance is Juliette Karagueuzoglou. Top notes are violet leaf, coriander, and bergamot; middle notes are rose, black pepper, and lily-of-the-valley; base notes are patchouli, bourbon vanilla, and ambergris.', 'Tuxedo Yves Saint Laurent عطر تشيبر للجنسين. Tuxedo صدر عام 2015. Juliette Karagueuzoglou قام بتوقيع هذا العطر. إفتتاحية العطر أوراق البنفسج, الكزبرة و البرغموت; قلب العطر الورد, الفلفل الأسود و زنابق الوادي; قاعدة العطر تتكون من الباتشولي, فانيليا بوربون و الآمبرغريس.', 'img_6a34aff2bbe520.02033150.webp', 0, '2026-06-19 02:58:36', '2026-07-21 18:12:34', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(34, 'oud-satin-mood', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Oud Satin Mood', 'عود ستان مود', '', '', 'Oud Satin Mood Extrait de Parfum by Maison Francis Kurkdjian is an oriental-woody fragrance for both men and women. Oud Satin Mood Extrait de Parfum was launched in 2017. The nose behind this fragrance is Francis Kurkdjian. Top notes are violet, geranium, Ceylon cinnamon, and cardamom; middle notes are Damask rose and Turkish rose; base notes are oud, vanilla, amber, benzoin, caramel, musk, and cedarwood.', 'Oud Satin Mood Extrait de Parfum Maison Francis Kurkdjian عطر شرقي - خشبي للجنسين. Oud Satin Mood Extrait de Parfum صدر عام 2017. Francis Kurkdjian قام بتوقيع هذا العطر. إفتتاحية العطر البنفسج, إبره الراعي, قرفة سيلان و الهيل; قلب العطر الورد الدمشقي و الورد التركي; قاعدة العطر تتكون من العود, الفانيليا, العنبر, البنزوين - الجاوي, الكاراميل, المسك و خشب الأرز.', 'img_6a34b132d2ca30.32501283.webp', 0, '2026-06-19 03:02:35', '2026-07-05 09:51:55', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(35, 'terroni', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Terroni', 'تيروني', '', '', 'Terroni Orto Parisi is an oriental-woody fragrance for both men and women. Terroni was launched in 2017. The nose behind this fragrance is Alessandro Gualtieri. The top note is raspberry; the middle notes are birch, amber, and benzoin; the base notes are guaiac wood, vetiver, cedarwood, musk, moss, patchouli, tonka bean, and vanilla.', 'Terroni Orto Parisi عطر شرقي - خشبي للجنسين. Terroni صدر عام 2017. Alessandro Gualtieri قام بتوقيع هذا العطر. إفتتاحية العطر توت العليق; قلب العطر أخشاب البتولا, العنبر و البنزوين - الجاوي; قاعدة العطر تتكون من أخشاب الغاياك, نجيل الهند, خشب الأرز, المسك, الطحالب, الباتشولي, حبوب التونكا و الفانيليا.', 'img_6a34b1a0557a62.22988318.webp', 0, '2026-06-19 03:05:38', '2026-07-04 00:36:17', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(36, 'naxos', 'niche-perfumes', 'both', 0, 0, 4, 1, 'Naxos', 'ناكسوس', '', '', 'XJ 1861 Naxos by Xerjoff is a unisex citrus-gourmand fragrance. XJ 1861 Naxos was launched in 2015. The top notes are lavender, bergamot, and lemon; the middle notes are honey, cinnamon, cashmere wood, and jasmine sambac; the base notes are tobacco leaf, vanilla, and tonka bean.', 'XJ 1861 Naxos Xerjoff عطر حمضيات - جورماند للجنسين. XJ 1861 Naxos صدر عام 2015. إفتتاحية العطر الخزامي, البرغموت و الليمون; قلب العطر العسل, القرفة, أخشاب الكشمير و ياسمين سامباك; قاعدة العطر تتكون من أوراق التبغ, الفانيليا و حبوب التونكا.', 'img_6a34b22813eed3.03447190.webp', 0, '2026-06-19 03:07:26', '2026-07-06 23:44:05', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(37, '40-knots', 'niche-perfumes', 'both', 0, 0, 2, 1, '40 Knots', 'فورت نوتس', '', '', '40 Knots by Xerjoff is an aromatic-aquatic fragrance for both men and women. 40 Knots was launched in 2012. Fragrance notes include wood, cedarwood, salt, sea water, and green notes.', '40 Knots Xerjoff عطر أروماتك - مائي للجنسين. 40 Knots صدر عام 2012. معلومات عن العطر الأخشاب, خشب الأرز, الملح, ماء البحر و النوتات الخضراء.', 'img_6a34b2d5908631.39197613.webp', 0, '2026-06-19 03:09:24', '2026-07-06 10:34:56', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(38, 'nishane-hacivat', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Nishane Hacivat', 'نيشاني هاشيفات', '', '', 'Hacivat by Nishane is a unisex chypre fragrance. Hacivat was launched in 2017. The nose behind this fragrance is Jorge Lee. Top notes are pineapple, grapefruit, and bergamot; middle notes are cedarwood, patchouli, and jasmine; base notes are oakmoss and woods.', 'Hacivat Nishane عطر تشيبر للجنسين. Hacivat صدر عام 2017. Jorge Lee قام بتوقيع هذا العطر. إفتتاحية العطر الأناناس, الجريب فروت و البرغموت; قلب العطر خشب الأرز, الباتشولي و الياسمين; قاعدة العطر تتكون من طحلب البلوط (طحلب السنديان) و الأخشاب.', 'img_6a34b33f211489.07100391.webp', 0, '2026-06-19 03:12:20', '2026-07-22 08:08:37', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(39, 'les-sables-roses', 'niche-perfumes', 'both', 0, 0, 1, 1, 'Les Sables Roses', 'ليس سابل روز', '', '', 'Les Sables Roses by Louis Vuitton is an oriental-floral fragrance for both men and women. It was launched in 2019. The nose behind this fragrance is Jacques Cavallier Belletrud. Fragrance notes include rose, Bulgarian rose, oud, ambergris, saffron, and black pepper.', 'Les Sables Roses Louis Vuitton عطر شرقي - زهري للجنسين. Les Sables Roses صدر عام 2019. Jacques Cavallier Belletrud قام بتوقيع هذا العطر. معلومات عن العطر الورد, الورد البلغاري, العود, الآمبرغريس, الزعفران و الفلفل الأسود.', 'img_6a34b430219950.16273757.webp', 0, '2026-06-19 03:16:02', '2026-07-21 13:10:21', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(40, 'creed-aventus', 'niche-perfumes', 'both', 0, 0, 0, 1, 'Creed Aventus', 'كريد أفنتوس', '', '', 'Aventus by Creed is a Chypre Fruity fragrance for men. Aventus was launched in 2010. Aventus was created by Jean-Christophe Hérault and Erwin Creed. Top notes are bergamot, blackcurrant, apple, lemon, and pink pepper; middle notes are pineapple, patchouli, and Moroccan jasmine; base notes are birch, musk, oakmoss, cedarwood, and ambroxan.', 'Aventus Creed عطر تشيبر - فواكه للرجال . Aventus صدر عام 2010. Aventus من توقيع Jean-Christophe Hérault و Erwin Creed. إفتتاحية العطر البرغموت, الكشمش الأسود, التفاح, الليمون و الفلفل الوردي; قلب العطر الأناناس, الباتشولي و الياسمين المغربي; قاعدة العطر تتكون من أخشاب البتولا, المسك, طحلب البلوط (طحلب السنديان), خشب الأرز و الأمبروكسان.', 'img_6a34b4bcd62ec2.84168969.webp', 0, '2026-06-19 03:18:37', '2026-06-19 23:18:53', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(41, 'caf-rose', 'niche-perfumes', 'both', 0, 0, 7, 1, 'Café Rose', 'كافي روز', '', '', 'Café Rose (2023) by Tom Ford is an oriental-floral fragrance for women. Café Rose (2023) was launched in 2023. The nose behind this fragrance is Antoine Lie. Top notes are Turkish rose and coffee; middle notes are Bulgarian rose, patchouli, cardamom, coriander, and ylang-ylang; base notes are frankincense and sandalwood.', 'Café Rose (2023) Tom Ford عطر شرقي - زهري للنساء . Café Rose (2023) صدر عام 2023. Antoine Lie قام بتوقيع هذا العطر. إفتتاحية العطر الورد التركي و القهوه; قلب العطر الورد البلغاري, الباتشولي, الهيل, الكزبرة و الإيلنغ; قاعدة العطر تتكون من اللبان و خشب الصندل.', 'img_6a34b68fce4a71.69933140.webp', 0, '2026-06-19 03:25:27', '2026-07-10 05:05:45', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(42, 'fleur-narcotique', 'niche-perfumes', 'both', 0, 0, 1, 1, 'Fleur Narcotique', 'فلور ناركوتيك', '', '', 'Fleur Narcotique Ex Nihilo is a floral-fruity fragrance for both men and women. Fleur Narcotique was launched in 2014. The nose behind this fragrance is Quentin Bisch. Top notes are lychee, bergamot, and peach; middle notes are peony, orange blossom, jasmine, and petalia; base notes are musk, moss, and woods.', 'Fleur Narcotique Ex Nihilo عطر زهري - فواكه للجنسين. Fleur Narcotique صدر عام 2014. Quentin Bisch قام بتوقيع هذا العطر. إفتتاحية العطر الليتشي, البرغموت و الخوخ; قلب العطر الفاوانيا, زهر البرتقال, الياسمين و بيتاليا; قاعدة العطر تتكون من المسك, الطحالب و الأخشاب.', 'img_6a34b7442593f9.87821830.webp', 0, '2026-06-19 03:28:24', '2026-07-21 13:28:25', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(43, 'imperial-valley', 'niche-perfumes', 'both', 0, 0, 5, 1, 'Imperial Valley', 'امبريال فالي', '', '', 'Imperial Valley Gissah is an oriental-woody fragrance for both men and women. Imperial Valley was launched in 2021. The top notes are Sicilian bergamot, pink pepper, and davana; the middle notes are oud, white amber, and rosemary; and the base notes are leather, musk, and Haitian vetiver.', 'Imperial Valley Gissah عطر شرقي - خشبي للجنسين. Imperial Valley صدر عام 2021. إفتتاحية العطر البرغموت الصقلي, الفلفل الوردي و الدافانا; قلب العطر العود, العنبر الأبيض و إكليل الجبل; قاعدة العطر تتكون من الجلود, المسك و نجيل الهند من هايتي.', 'img_6a34b7ccc1f357.03050052.webp', 0, '2026-06-19 03:32:08', '2026-08-05 10:51:25', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(44, 'megamare', 'niche-perfumes', 'both', 0, 0, 6, 1, 'Megamare', 'ميجامير', '', '', 'Megamare by Orto Parisi is an aromatic-aquatic fragrance for both men and women. Megamare was launched in 2019. The nose behind this fragrance is Alessandro Gualtieri. Top notes are bergamot and lemon; middle notes are seaweed, calone, and hedione; base notes are musk, ambroxan, and cedarwood.', 'Megamare Orto Parisi عطر أروماتك - مائي للجنسين. Megamare صدر عام 2019. Alessandro Gualtieri قام بتوقيع هذا العطر. إفتتاحية العطر البرغموت و الليمون; قلب العطر الطحالب البحرية, جزئ الكالون و جزئ هديون; قاعدة العطر تتكون من المسك, الأمبروكسان و خشب الأرز.', 'img_6a34b86373b728.33275574.webp', 0, '2026-06-19 03:34:20', '2026-07-22 08:10:26', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(45, 'vibrato-sospiro', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Vibrato Sospiro', 'فيبراتو سوسبيرو', '', '', 'Vibrato by Sospiro Perfumes is a unisex fragrance. Vibrato was launched in 2022. The nose behind this fragrance is Christian Provenzano. Top notes are grapefruit, bergamot, jasmine, and magnolia; middle notes are ginger, herbal notes, and powdery notes; base notes are musk, cedarwood, amber, patchouli, and orris root.', 'Vibrato Sospiro Perfumes عطر للجنسين. Vibrato صدر عام 2022. Christian Provenzano قام بتوقيع هذا العطر. إفتتاحية العطر الجريب فروت, البرغموت, الياسمين و الماغنوليا; قلب العطر الزنجبيل, النوتات العشبية و النوتات البودرية; قاعدة العطر تتكون من المسك, خشب الأرز, العنبر, الباتشولي و جذور السوسن.', 'img_6a34b9abc2ca54.63014428.webp', 0, '2026-06-19 03:38:38', '2026-07-05 23:03:47', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(46, 'grand-soir', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Grand Soir', 'جراند سوار', '', '', 'Grand Soir by Maison Francis Kurkdjian is an oriental unisex fragrance. Grand Soir was launched in 2016. The nose behind this fragrance is Francis Kurkdjian. The top notes are Spanish labdanum and orange; the middle notes are lavender and Siam benzoin; the base notes are amber, vanilla, tonka bean, musk, and cedarwood.', 'Grand Soir Maison Francis Kurkdjian عطر شرقي للجنسين. Grand Soir صدر عام 2016. Francis Kurkdjian قام بتوقيع هذا العطر. إفتتاحية العطر اللابدانوم الأسباني و البرتقال; قلب العطر الخزامي و البنزوين من سيام; قاعدة العطر تتكون من العنبر, الفانيليا, حبوب التونكا, المسك و خشب الأرز.', 'img_6a34ba616ceea1.26941393.webp', 0, '2026-06-19 03:41:37', '2026-07-07 13:36:46', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(47, '21-torino21-xerjoff', 'niche-perfumes', 'both', 0, 0, 1, 1, '21 Torino21 Xerjoff', 'زيرجوف تورينو 21', '', '', 'Torino21 by Xerjoff is an aromatic-herbal fragrance for both men and women. Torino21 was launched in 2021. The top notes are mint, lemon, basil, and thyme; the middle notes are blackcurrant, lavender, rosemary, and jasmine; and the base notes are musk and verbena.', 'Torino21 Xerjoff عطر أروماتك - عشبي للجنسين. Torino21 صدر عام 2021. إفتتاحية العطر النعناع, الليمون, الريحان و الزعتر; قلب العطر الكشمش الأسود, الخزامي, إكليل الجبل و الياسمين; قاعدة العطر تتكون من المسك و رعي الحمام.', 'img_6a34baa6baeec6.76876800.webp', 0, '2026-06-19 03:43:43', '2026-07-21 13:17:37', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(48, 'experimentum', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Experimentum', 'اكسبريمنتوم', '', '', 'Experimentum Crucis Etat Libre d\'Orange is a woody-aromatic fragrance for both men and women. It was launched in 2019. The nose behind this fragrance is Quentin Bisch. Top notes are cumin, apple, and lychee; middle notes are rose, honey, and jasmine; base notes are Akigalawood, patchouli, and musk.', 'Experimentum Crucis Etat Libre d\'Orange عطر خشبي - أروماتك للجنسين. Experimentum Crucis صدر عام 2019. Quentin Bisch قام بتوقيع هذا العطر. إفتتاحية العطر الكمون, التفاح و الليتشي; قلب العطر الورد, العسل و الياسمين; قاعدة العطر تتكون من أكيغالاوود, الباتشولي و المسك.', 'img_6a34bbaea7f654.00059645.webp', 0, '2026-06-19 03:48:38', '2026-07-06 00:51:45', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(49, 'afternoon-swim', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Afternoon Swim', 'افترنون سويم', '', '', 'Experimentum Crucis Etat Libre d\'Orange is a woody-aromatic fragrance for both men and women. It was launched in 2019. The nose behind this fragrance is Quentin Bisch. Top notes are cumin, apple, and lychee; middle notes are rose, honey, and jasmine; base notes are Akigalawood, patchouli, and musk.', 'Experimentum Crucis Etat Libre d\'Orange عطر خشبي - أروماتك للجنسين. Experimentum Crucis صدر عام 2019. Quentin Bisch قام بتوقيع هذا العطر. إفتتاحية العطر الكمون, التفاح و الليتشي; قلب العطر الورد, العسل و الياسمين; قاعدة العطر تتكون من أكيغالاوود, الباتشولي و المسك.', 'img_6a34bc7aa36e79.65943657.webp', 0, '2026-06-19 03:52:01', '2026-07-03 10:30:23', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(50, 'alexandria-ii', 'niche-perfumes', 'both', 0, 0, 1, 1, 'Alexandria II', 'الكسندريا 2', '', '', 'Alexandria II by Xerjoff is an oriental-woody fragrance for both men and women. Alexandria II was launched in 2012. The nose behind this fragrance is Chris Maurice. The top notes are rosewood, lavender, apple, and cinnamon; the middle notes are rose, cedarwood, and lily-of-the-valley; and the base notes are agarwood (oud), sandalwood, amber, vanilla, and musk.', 'Alexandria II Xerjoff عطر شرقي - خشبي للجنسين. Alexandria II صدر عام 2012. Chris Maurice قام بتوقيع هذا العطر. إفتتاحية العطر خشب الورد الباليساندر, الخزامي, التفاح و القرفة; قلب العطر الورد, خشب الأرز و زنابق الوادي; قاعدة العطر تتكون من العود, خشب الصندل, العنبر, الفانيليا و المسك.', 'img_6a34bd48b30198.26222506.webp', 0, '2026-06-19 03:54:05', '2026-07-04 14:01:54', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(51, 'rosendo-mateu-5', 'niche-perfumes', 'both', 0, 0, 2, 1, 'Rosendo Mateu 5', 'روسيندو ماتيو 5', '', '', 'Rosendo Mateu Nº 5 Floral, Amber, Sensual Musk by Rosendo Mateu Olfactive Expressions is an oriental-floral fragrance for both men and women. Rosendo Mateu Nº 5 Floral, Amber, Sensual Musk was launched in 2017. The nose behind this fragrance is Rosendo Mateu. The top notes are spices and tropical flowers; the middle notes are carnation and lily-of-the-valley; and the base notes are amber, vanilla, and musk.', 'Rosendo Mateu Nº 5 Floral, Amber, Sensual Musk Rosendo Mateu Olfactive Expressions عطر شرقي - زهري للجنسين. Rosendo Mateu Nº 5 Floral, Amber, Sensual Musk صدر عام 2017. Rosendo Mateu قام بتوقيع هذا العطر. إفتتاحية العطر التوابل و الزهور الإستوائية; قلب العطر زهر القرنفل و زنابق الوادي; قاعدة العطر تتكون من العنبر, الفانيليا و المسك.', 'img_6a34be80a56704.59165318.webp', 0, '2026-06-19 03:59:09', '2026-07-21 13:32:03', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(52, 'hawas-ice-rasasi', 'men', 'both', 0, 0, 0, 1, 'Hawas Ice Rasasi', 'هوس ايس الرصاصي', '', '', 'Hawas Ice by Rasasi is an aromatic fragrance for men. Hawas Ice was launched in 2023. The top notes are apple, Italian lemon, Sicilian bergamot and star anise; the middle notes are plum, orange blossom and cardamom; the base notes are musk, amber, driftwood and moss.', 'Hawas Ice Rasasi عطر أروماتك للرجال . Hawas Ice صدر عام 2023. إفتتاحية العطر التفاح, الليمون الإيطالي, البرغموت الصقلي و الينسون النجمي; قلب العطر البرقوق, زهر البرتقال و الهيل; قاعدة العطر تتكون من المسك, العنبر, الأخشاب الطافية و الطحالب.', 'img_6a356925398d98.22655835.webp', 0, '2026-06-19 16:07:59', '2026-06-19 23:20:42', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(53, 'armani-code-profumo', 'men', 'both', 0, 0, 0, 1, 'Armani Code Profumo', 'ارماني كود برفومو', '', '', 'Armani Code Profumo by Giorgio Armani is an oriental-spicy fragrance for men. Armani Code Profumo was launched in 2016. The nose behind this fragrance is Antoine Maisondieu. Top notes are cardamom, green apple, and green mandarin; middle notes are nutmeg, lavender, and orange blossom; base notes are tonka bean, amber, and leather.', 'Armani Code Profumo Giorgio Armani عطر شرقي - حار للرجال . Armani Code Profumo صدر عام 2016. Antoine Maisondieu قام بتوقيع هذا العطر. إفتتاحية العطر الهيل, التفاح الأخضر و الماندرين الأخضر; قلب العطر جوزه الطيب, الخزامي و زهر البرتقال; قاعدة العطر تتكون من حبوب التونكا, العنبر و الجلود.', 'img_6a356a24703eb6.45723576.webp', 0, '2026-06-19 16:12:03', '2026-06-19 16:12:03', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(54, 'acqua-di-gio', 'designer-perfumes', 'both', 0, 0, 7, 1, 'Acqua di Gio', 'اكوا دي جيو', '', '', 'Acqua di Gio by Giorgio Armani is an aromatic aquatic fragrance for men. Acqua di Gio was launched in 1996. Acqua di Gio was created by Alberto Morillas, Annick Menardo, and Christian Dussoulier. Top notes are lime, lemon, bergamot, jasmine, orange, mandarin orange, and neroli; middle notes are sea breeze, jasmine, calone, rosemary, peach, freesia, hyacinth, cyclamen, violet, coriander, rose, nutmeg, and resida; base notes are white musk, cedarwood, oakmoss, patchouli, and amber.', 'Acqua di Gio Giorgio Armani عطر أروماتك - مائي للرجال . Acqua di Gio صدر عام 1996. Acqua di Gio من توقيع Alberto Morillas, Annick Menardo و Christian Dussoulier. إفتتاحية العطر الليم, الليمون, البرغموت, الياسمين, البرتقال, الماندرين (اليوسفي) و النيرولي; قلب العطر نسيم البحر, الياسمين, جزئ الكالون, إكليل الجبل, الخوخ, الفريزيا, الياقوتية, زهر بخور مريم, البنفسج, الكزبرة, الورد, جوزه الطيب و ريزيدا; قاعدة العطر تتكون من المسك الأبيض, خشب الأرز, طحلب البلوط (طحلب السنديان), الباتشولي و العنبر.', 'img_6a356a8ca5e964.31794312.webp', 0, '2026-06-19 16:13:53', '2026-07-25 03:37:35', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(55, 'acqua-di-gio-profumo', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Acqua di Gio Profumo', 'اكوا دي جيو برفومو', '', '', 'Acqua di Giò Profumo by Giorgio Armani is an aromatic aquatic fragrance for men. Acqua di Giò Profumo was launched in 2015. The nose behind this fragrance is Alberto Morillas. Top notes are sea breeze and bergamot; middle notes are rosemary, sage, and geranium; base notes are incense and patchouli.', 'Acqua di Giò Profumo Giorgio Armani عطر أروماتك - مائي للرجال . Acqua di Giò Profumo صدر عام 2015. Alberto Morillas قام بتوقيع هذا العطر. إفتتاحية العطر نسيم البحر و البرغموت; قلب العطر إكليل الجبل, المريمية و إبره الراعي; قاعدة العطر تتكون من البخور و الباتشولي.', 'img_6a356ae82e3832.00512715.webp', 0, '2026-06-19 16:15:14', '2026-07-21 14:57:25', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(56, 'black-xs-l-exces', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Black XS L\'Exces', 'بلاك ليجزيس', '', '', 'Black XS L\'Exces for Him by Rabanne is a woody-aromatic fragrance for men. Black XS L\'Exces for Him was launched in 2012. The nose behind this fragrance is Fabrice Pellegrin. Top notes are lemon and lavender; middle notes are cypriol (nagramotha) and sea breeze; base notes are amber and patchouli.', 'Black XS L\'Exces for Him Rabanne عطر خشبي - أروماتك للرجال . Black XS L\'Exces for Him صدر عام 2012. Fabrice Pellegrin قام بتوقيع هذا العطر. إفتتاحية العطر الليمون و الخزامي; قلب العطر السيبرول (الناجراموثا) و نسيم البحر; قاعدة العطر تتكون من العنبر و الباتشولي.', 'img_6a356b44edf8d5.77949771.webp', 0, '2026-06-19 16:16:51', '2026-07-09 14:40:51', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(57, 'black-xs', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Black XS', 'بلاك X.S', '', '', 'Black XS by Rabanne is an oriental-woody fragrance for men. Black XS was launched in 2005. Black XS was created by Olivier Cresp, Rosendo Mateu, and Christian Dussoulier. The top notes are lemon and sage; the middle notes are praline, cinnamon, tolu balsam, and black cardamom; and the base notes are patchouli, Brazilian rosewood, and black amber.', 'Black XS Rabanne عطر شرقي - خشبي للرجال . Black XS صدر عام 2005. Black XS من توقيع Olivier Cresp, Rosendo Mateu و Christian Dussoulier. إفتتاحية العطر الليمون و المريمية; قلب العطر حلوي اللوز, القرفة, بلسم تولو و الهيل الأسود; قاعدة العطر تتكون من الباتشولي, خشب الورد البرازيلي و العنبر الأسود.', 'img_6a356b9ea641c7.33767511.webp', 0, '2026-06-19 16:18:22', '2026-07-05 18:38:51', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(58, 'bleu-de-chanel', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Bleu de Chanel', 'بلو دي شانيل', '', '', 'Bleu de Chanel Eau de Parfum by Chanel is a woody-aromatic fragrance for men. It was launched in 2014. The nose behind this fragrance is Jacques Polge. Top notes are grapefruit, lemon, mint, bergamot, pink pepper, aldehydes, and coriander; middle notes are ginger, nutmeg, jasmine, and melon; base notes are incense, amber, cedarwood, sandalwood, ambergris, patchouli, and labdanum.', 'Bleu de Chanel Eau de Parfum Chanel عطر خشبي - أروماتك للرجال . Bleu de Chanel Eau de Parfum صدر عام 2014. Jacques Polge قام بتوقيع هذا العطر. إفتتاحية العطر الجريب فروت, الليمون, النعناع, البرغموت, الفلفل الوردي, الألدهيدات و الكزبرة; قلب العطر الزنجبيل, جوزه الطيب, الياسمين و شمام; قاعدة العطر تتكون من البخور, العنبر, خشب الأرز, خشب الصندل, خشب العنبر, الباتشولي و اللابدانوم.', 'img_6a356bf72d3449.84549274.webp', 0, '2026-06-19 16:20:01', '2026-07-05 23:19:04', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(59, 'sauvage', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Sauvage', 'سوفاج', '', '', 'Sauvage by Dior is an aromatic fougère fragrance for men. Sauvage was launched in 2015. The nose behind this fragrance is François Demachy. Top notes are Calabrian bergamot and pepper; middle notes are Sichuan pepper, lavender, pink pepper, vetiver, patchouli, geranium, and elemi; base notes are ambroxan, cedarwood, and labdanum.', 'Sauvage Dior عطر أروماتك - فوچير للرجال . Sauvage صدر عام 2015. François Demachy قام بتوقيع هذا العطر. إفتتاحية العطر برغموت كالابريا و الفلفل; قلب العطر فلفل سيشوان, الخزامي, الفلفل الوردي, نجيل الهند, الباتشولي, إبره الراعي و الإليمي; قاعدة العطر تتكون من الأمبروكسان, خشب الأرز و اللابدانوم.', 'img_6a356c6adc11b4.76568082.webp', 0, '2026-06-19 16:21:41', '2026-08-08 12:16:05', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(60, 'bad-boy', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Bad Boy', 'باد بوي', '', '', 'Bad Boy by Carolina Herrera is an oriental-spicy fragrance for men. Bad Boy was launched in 2019. Bad Boy was created by Quentin Bisch and Louise Turner. The top notes are white pepper, bergamot, and pink pepper; the middle notes are cedarwood and sage; and the base notes are tonka bean and cacao.', 'Bad Boy Carolina Herrera عطر شرقي - حار للرجال . Bad Boy صدر عام 2019. Bad Boy من توقيع Quentin Bisch و Louise Turner. إفتتاحية العطر الفلفل الأبيض, البرغموت و الفلفل الوردي; قلب العطر خشب الأرز و المريمية; قاعدة العطر تتكون من حبوب التونكا و الكاكاو.', 'img_6a356cd4cc51c6.10523331.webp', 0, '2026-06-19 16:23:31', '2026-07-06 21:12:25', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(61, 'puma-jam', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Puma Jam', 'بوما جام', '', '', 'Jam Man by Puma is an aromatic-aquatic fragrance for men. Jam Man was launched in 2011. The top notes are pineapple, green apple and mandarin; the middle notes are sea water, lavender, cardamom and anise; the base notes are driftwood, patchouli and amber.', 'Jam Man Puma عطر أروماتك - مائي للرجال . Jam Man صدر عام 2011. إفتتاحية العطر الأناناس, التفاح الأخضر و الماندرين (اليوسفي); قلب العطر ماء البحر, الخزامي, الهيل و الينسون; قاعدة العطر تتكون من الأخشاب الطافية , الباتشولي و العنبر.', 'img_6a356d73c24377.42136555.webp', 0, '2026-06-19 16:25:32', '2026-07-04 14:30:10', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(62, '212-men-carolina-herrera', 'designer-perfumes', 'both', 0, 0, 5, 1, '212 Men Carolina Herrera', 'هريرا 212', '', '', '212 Men by Carolina Herrera is a Woody Floral Musk fragrance for men. 212 Men was launched in 1999. 212 Men was created by Alberto Morillas, Rosendo Mateu and Ann Gottlieb. Top notes are Green Notes, Grapefruit, Spices, Bergamot, Lavender and Petitgrain; middle notes are Ginger, Violet, Gardenia and Sage; base notes are Musk, Sandalwood, Incense, Vetiver, Guaiac Wood and Labdanum.', 'عطر 212 Men من كارولينا هيريرا هو عطر خشبي زهري مسكي للرجال. أُطلق عام 1999. ابتكره ألبرتو موريلاس، روزيندو ماتيو، وآن غوتليب. تتكون مقدمة العطر من نفحات خضراء، جريب فروت، توابل، برغموت، لافندر، وبيتي غراين؛ أما قلب العطر فيتكون من زنجبيل، بنفسج، غاردينيا، ومريمية؛ بينما تتكون قاعدة العطر من المسك، خشب الصندل، بخور، نجيل الهند، خشب الغاياك، واللابدانوم.', 'img_6a356db1659f40.12875719.webp', 0, '2026-06-19 16:28:15', '2026-07-15 12:35:23', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(64, 'product-1781887394', 'men', 'both', 0, 0, 2, 1, 'الوسام', 'Al Wisam Rasasi', '', '', 'Al Wisam Day by Rasasi is an oriental-fougere fragrance for men. The top notes are bergamot, geranium and lavender; the middle notes are rose, sandalwood, sage and cedarwood; the base notes are musk, oakmoss, oud and amber.', 'Al Wisam Day Rasasi عطر شرقي - فوچير للرجال . إفتتاحية العطر البرغموت, إبره الراعي و الخزامي; قلب العطر الورد, خشب الصندل, المريمية و خشب الأرز; قاعدة العطر تتكون من المسك, طحلب البلوط (طحلب السنديان), العود و العنبر.', 'img_6a35718cd32737.93782181.webp', 0, '2026-06-19 16:43:14', '2026-08-08 12:16:31', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(65, 'creed-silver-mountain-water', 'men', 'both', 0, 0, 1, 1, 'Creed Silver Mountain Water', 'كريد سيلفر ماونتن ووتر', '', '', 'Silver Mountain Water by Creed is an aromatic fragrance for both men and women. Silver Mountain Water was launched in 1995. It was created by Olivier Creed and Pierre Bourdon. The top notes are bergamot and mandarin orange; the middle notes are green tea and blackcurrant; and the base notes are musk, petitgrain, sandalwood, and galbanum.', 'Silver Mountain Water Creed عطر أروماتك للجنسين. Silver Mountain Water صدر عام 1995. Silver Mountain Water من توقيع Olivier Creed و Pierre Bourdon. إفتتاحية العطر البرغموت و الماندرين (اليوسفي); قلب العطر الشاي الأخضر و الكشمش الأسود; قاعدة العطر تتكون من المسك, البيتيتغرين, خشب الصندل و الغلابانوم.', 'img_6a3571e22c5610.46130175.webp', 0, '2026-06-19 16:45:58', '2026-08-08 12:13:44', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(66, 'legend-montblanc', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Legend Montblanc', 'ليجند مونت بلانك', '', '', 'Legend Eau de Parfum by Montblanc is a leather fragrance for men. Legend Eau de Parfum was launched in 2020. The nose behind this fragrance is Olivier Pescheux. Top notes are violet leaf and bergamot; middle notes are woods, jasmine, and magnolia; base notes are oakmoss and leather.', 'Legend Eau de Parfum Montblanc عطر الجلود للرجال . Legend Eau de Parfum صدر عام 2020. Olivier Pescheux قام بتوقيع هذا العطر. إفتتاحية العطر أوراق البنفسج و البرغموت; قلب العطر الأخشاب, الياسمين و الماغنوليا; قاعدة العطر تتكون من طحلب البلوط (طحلب السنديان) و الجلود.', 'img_6a357276858b40.39866423.webp', 0, '2026-06-19 16:47:39', '2026-07-06 17:42:30', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(67, 'lacoste-essential', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Lacoste Essential', 'لاكوست اسينشيال', '', '', 'Lacoste Essential Collector Edition by Lacoste Fragrances is a woody-aromatic fragrance for men. Lacoste Essential Collector Edition was launched in 2008. The nose behind this fragrance is Laurent Bruyere. Top notes are bergamot, tangerine, and cassia; middle notes are rose and pepper; base notes are patchouli and sandalwood.', 'Lacoste Essential Collector Edition Lacoste Fragrances عطر خشبي - أروماتك للرجال . Lacoste Essential Collector Edition صدر عام 2008. Laurent Bruyere قام بتوقيع هذا العطر. إفتتاحية العطر البرغموت, تانجرين (اليوسفي) و القرفة الصينية; قلب العطر الورد و الفلفل; قاعدة العطر تتكون من الباتشولي و خشب الصندل.', 'img_6a3572e5edb975.19164929.webp', 0, '2026-06-19 16:49:41', '2026-07-06 22:20:13', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(68, 'lacoste-white', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Lacoste White', 'لاكوست وايت', '', '', 'Eau de Lacoste L.12.12. White by Lacoste Fragrances is a woody-aromatic fragrance for men. Eau de Lacoste L.12.12. White was launched in 2011. The nose behind this fragrance is Sonia Constant. Top notes are grapefruit, rosemary, and cardamom; middle notes are ylang-ylang and tuberose; base notes are Virginia cedar, suede, vetiver, and leather.', 'Eau de Lacoste L.12.12. White Lacoste Fragrances عطر خشبي - أروماتك للرجال . Eau de Lacoste L.12.12. White صدر عام 2011. Sonia Constant قام بتوقيع هذا العطر. إفتتاحية العطر الجريب فروت, إكليل الجبل و الهيل; قلب العطر الإيلنغ و مسك الروم; قاعدة العطر تتكون من خشب الأرز من فرجينيا, جلد الغزال (الجلد المدبوغ), نجيل الهند و الجلود.', 'img_6a35735b96a576.73769995.webp', 0, '2026-06-19 16:51:24', '2026-08-07 18:49:59', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(69, 'silver-scent', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Silver Scent', 'سيلفر سنت', '', '', 'Silver Scent by Jacques Bogart is an oriental-woody fragrance for men. Silver Scent was launched in 2006. The top notes are orange blossom and lemon; the middle notes are lavender, cardamom, nutmeg, rosemary, coriander and geranium; the base notes are lychee, tonka bean, teak wood and vetiver.', 'Silver Scent Jacques Bogart عطر شرقي - خشبي للرجال . Silver Scent صدر عام 2006. إفتتاحية العطر زهر البرتقال و الليمون; قلب العطر الخزامي, الهيل, جوزه الطيب, إكليل الجبل, الكزبرة و إبره الراعي; قاعدة العطر تتكون من الليتشي, حبوب التونكا, خشب الساج و نجيل الهند.', 'img_6a3573c918b8a4.86949798.webp', 0, '2026-06-19 16:53:16', '2026-07-10 08:05:35', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0);
INSERT INTO `products` (`id`, `slug`, `category`, `season`, `is_bestseller`, `is_offer`, `view_count`, `active`, `name_en`, `name_ar`, `notes_en`, `notes_ar`, `description_en`, `description_ar`, `primary_image_key`, `sort_order`, `created_at`, `updated_at`, `file_sharing_url`, `brand_id`, `is_brand_product`, `ai_profile_ar`, `ai_keywords_ar`, `ai_intensity`, `ai_longevity`, `ai_sillage`, `ai_best_for`, `ai_sensitivity_safe`) VALUES
(70, 'y-intense', 'men', 'both', 0, 0, 0, 1, 'Y Intense', 'واي', '', '', 'Y Eau de Parfum Intense by Yves Saint Laurent is an aromatic fougère fragrance for men. Y Eau de Parfum Intense was launched in 2023. Y Eau de Parfum Intense was created by Dominique Ropion and Claire Liégent. Top notes are ginger, juniper berries, and bergamot; middle notes are sage, lavender, and geranium; base notes are vetiver, patchouli, and cedarwood.', 'Y Eau de Parfum Intense Yves Saint Laurent عطر أروماتك - فوچير للرجال . Y Eau de Parfum Intense صدر عام 2023. Y Eau de Parfum Intense من توقيع Dominique Ropion و Claire Liégent. إفتتاحية العطر الزنجبيل, توت العرعر و البرغموت; قلب العطر المريمية, الخزامي و إبره الراعي; قاعدة العطر تتكون من نجيل الهند, الباتشولي و خشب الأرز.', 'img_6a3574205ca376.00725777.webp', 0, '2026-06-19 16:54:48', '2026-06-19 16:54:48', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(71, 'eros-versace', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Eros Versace', 'ايروس فيرزاتشي', '', '', 'Eros by Versace is an aromatic fougère fragrance for men. Eros was launched in 2012. The nose behind this fragrance is Aurélien Guichard. Top notes are mint, green apple, and lemon; middle notes are tonka bean, ambroxan, and geranium; base notes are Madagascan vanilla, Virginia cedar, Atlas cedar, vetiver, and oakmoss.', 'Eros Versace عطر أروماتك - فوچير للرجال . Eros صدر عام 2012. Aurélien Guichard قام بتوقيع هذا العطر. إفتتاحية العطر النعناع, التفاح الأخضر و الليمون; قلب العطر حبوب التونكا, الأمبروكسان و إبره الراعي; قاعدة العطر تتكون من فانيليا مدغشقر, خشب الأرز من فرجينيا, خشب الأرز الأطلسي, نجيل الهند و طحلب البلوط (طحلب السنديان).', 'img_6a357bd7c7ac69.93059405.webp', 0, '2026-06-19 17:27:02', '2026-06-19 23:03:50', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(72, '1-million-lucky', 'designer-perfumes', 'both', 0, 0, 0, 1, '1 Million Lucky', 'مليون لاكي', '', '', '1 Million Lucky by Rabanne is a woody fragrance for men. 1 Million Lucky was launched in 2018. The nose behind this fragrance is Natalie Gracia-Cetto. Top notes are plum, ozonic notes, grapefruit, and bergamot; middle notes are hazelnut, honey, cedarwood, cashmere wood, orange blossom, and jasmine; base notes are amberwood, patchouli, vetiver, and oakmoss.', '1 Million Lucky Rabanne عطر خشبي للرجال . 1 Million Lucky صدر عام 2018. Natalie Gracia-Cetto قام بتوقيع هذا العطر. إفتتاحية العطر البرقوق, نوتات أوزونية, الجريب فروت و البرغموت; قلب العطر البندق, العسل, خشب الأرز, أخشاب الكشمير, زهر البرتقال و الياسمين; قاعدة العطر تتكون من خشب العنبر, الباتشولي, نجيل الهند و طحلب البلوط (طحلب السنديان).', 'img_6a357c641db542.13599957.webp', 0, '2026-06-19 17:29:35', '2026-06-19 23:03:28', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(73, '1-million-elixir', 'designer-perfumes', 'both', 0, 0, 1, 1, '1 Million Elixir', 'وان مليون الكسير', '', '', '1 Million Elixir Rabanne is a woody-aromatic fragrance for men. 1 Million Elixir was launched in 2022. The top notes are apple and davana; the middle notes are Damask rose, cedarwood and osmanthus; the base notes are vanilla, tonka bean and patchouli.', '1 Million Elixir Rabanne عطر خشبي - أروماتك للرجال . 1 Million Elixir صدر عام 2022. إفتتاحية العطر التفاح و الدافانا; قلب العطر الورد الدمشقي, خشب الأرز و أوسمانثوس; قاعدة العطر تتكون من الفانيليا, حبوب التونكا و الباتشولي.', 'img_6a35cfacdab008.51567944.webp', 0, '2026-06-19 23:25:00', '2026-07-09 17:10:37', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(74, 'la-nuit-de-l-homme', 'designer-perfumes', 'both', 0, 0, 0, 1, 'La Nuit de l\'Homme', 'لانوي لاهوم', '', '', 'La Nuit de l\'Homme by Yves Saint Laurent is a woody-spicy fragrance for men. La Nuit de l\'Homme was launched in 2009. It was created by Anne Flipo, Pierre Wargnye, and Dominique Ropion. The top note is cardamom; the middle notes are lavender, Virginia cedar, and bergamot; and the base notes are vetiver and caraway.', 'La Nuit de l\'Homme Yves Saint Laurent عطر خشبي - حار للرجال . La Nuit de l\'Homme صدر عام 2009. La Nuit de l\'Homme من توقيع Anne Flipo, Pierre Wargnye و Dominique Ropion. إفتتاحية العطر الهيل; قلب العطر الخزامي, خشب الأرز من فرجينيا و البرغموت; قاعدة العطر تتكون من نجيل الهند و الكاراوية.', 'img_6a35cffc4354f3.36536098.webp', 0, '2026-06-19 23:26:29', '2026-06-19 23:26:29', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(75, 'the-one', 'men', 'both', 0, 0, 1, 1, 'The One', 'ذا وان', '', '', 'The One for Men Eau de Parfum by Dolce&Gabbana is a woody-spicy fragrance for men. The One for Men Eau de Parfum was launched in 2015. The nose behind this fragrance is Olivier Polge. Top notes are grapefruit, coriander, and basil; middle notes are cardamom, ginger, and orange blossom; base notes are amber, tobacco, and cedarwood.', 'The One for Men Eau de Parfum Dolce&Gabbana عطر خشبي - حار للرجال . The One for Men Eau de Parfum صدر عام 2015. Olivier Polge قام بتوقيع هذا العطر. إفتتاحية العطر الجريب فروت, الكزبرة و الريحان; قلب العطر الهيل, الزنجبيل و زهر البرتقال; قاعدة العطر تتكون من العنبر, التبغ و خشب الأرز.', 'img_6a35d0806045d4.84659979.webp', 0, '2026-06-19 23:29:02', '2026-07-15 21:31:35', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(76, 'creed-black', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Creed Black', 'كريد بلاك', '', '', 'Green Irish Tweed by Creed is a woody-floral-musk fragrance for men. Green Irish Tweed was launched in 1985. It was created by Olivier Creed and Pierre Bourdon. The top notes are iris and verbena; the middle note is violet leaf; and the base notes are ambergris and sandalwood.', 'Green Irish Tweed Creed عطر خشبي - زهري - مسك للرجال . Green Irish Tweed صدر عام 1985. Green Irish Tweed من توقيع Olivier Creed و Pierre Bourdon. إفتتاحية العطر السوسن و رعي الحمام; قلب العطر أوراق البنفسج; قاعدة العطر تتكون من الآمبرغريس و خشب الصندل.', 'img_6a35d1025a30e6.95949481.webp', 0, '2026-06-19 23:30:38', '2026-07-10 08:41:27', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(77, 'ambr-baldessarini', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Ambré Baldessarini', 'أمبر بالدسرين', '', '', 'Ambré Baldessarini is an oriental-woody fragrance for men. Ambré was launched in 2007. The nose behind this fragrance is dsm-firmenich. Top notes are red apple, mandarin orange, and whiskey; middle notes are leather and violet; base notes are amber, oakmoss, vanilla, and labdanum.', 'Ambré Baldessarini عطر شرقي - خشبي للرجال . Ambré صدر عام 2007. dsm-firmenich قام بتوقيع هذا العطر. إفتتاحية العطر التفاح الأحمر, الماندرين (اليوسفي) و الويسكي; قلب العطر الجلود و البنفسج; قاعدة العطر تتكون من العنبر, البلوط, الفانيليا و اللابدانوم.', 'img_6a35d147894381.47872178.webp', 0, '2026-06-19 23:32:22', '2026-06-19 23:32:22', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(78, 'khamrah-lattafa', 'designer-perfumes', 'both', 1, 0, 76, 1, 'Khamrah Lattafa', 'خمرة', '', '', 'Khamrah by Lattafa Perfumes is an oriental-spicy fragrance for both men and women. Khamrah was launched in 2022. The top notes are cinnamon, nutmeg, and bergamot; the middle notes are dates, almond candy, tuberose, and mahonyal; and the base notes are vanilla, tonka bean, amberwood, myrrh, benzoin, and akgalwood.', 'Khamrah Lattafa Perfumes عطر شرقي - حار للجنسين. Khamrah صدر عام 2022. إفتتاحية العطر القرفة, جوزه الطيب و البرغموت; قلب العطر التمر/البلح, حلوي اللوز, مسك الروم و ماهونيال; قاعدة العطر تتكون من الفانيليا, حبوب التونكا, خشب العنبر, المر, البنزوين - الجاوي و أكيغالاوود.', 'img_6a35d1f357d3c2.90398339.webp', 0, '2026-06-19 23:34:55', '2026-08-17 10:12:09', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(79, 'le-male-le-parfum', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Le Male Le Parfum', 'لوميل لي بارفيوم', '', '', 'Le Male Le Parfum by Jean Paul Gaultier is an oriental fragrance for men. Le Male Le Parfum was launched in 2020. It was created by Quentin Bisch and Natalie Gracia-Cetto. The top note is cardamom; the middle notes are lavender and iris; and the base notes are vanilla, oriental notes, and woods.', 'Le Male Le Parfum Jean Paul Gaultier عطر شرقي للرجال . Le Male Le Parfum صدر عام 2020. Le Male Le Parfum من توقيع Quentin Bisch و Natalie Gracia-Cetto. إفتتاحية العطر الهيل; قلب العطر الخزامي و السوسن; قاعدة العطر تتكون من الفانيليا, الروائح الشرقيه و الأخشاب.', 'img_6a35d24dcaeb37.97380080.webp', 0, '2026-06-19 23:36:22', '2026-08-08 12:17:06', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(80, 'stronger-with-you', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Stronger With You', 'سترونجر ويذ يو', '', '', 'Stronger With You is a fragrance by  Giorgio Armani designed for the modern man , embodying the spirit of contemporary life. This unique fragrance surprises with its originality, featuring a spicy top note – a blend of cardamom, pink pepper, and violet leaf. Thanks to its aromatic, marbled heart, you\'ll experience the confidence and elegance of a man, coupled with the carefree spirit of youth. The fragrance\'s allure is expressed through a smoky, forest-inspired vanilla™ scent that intertwines with a hint of sugared chestnut, surrendering to its irresistible charm: Stronger With You. Fragrance family: Sweet, spicy, woody. Key notes: Mint, violet leaf, pink pepper, cardamom, cinnamon, lavender, pineapple, melon, sage, guaiac wood, amber, cedar, chestnut, vanilla.', 'سترونجر ويذ يو عطر مخصص للرجل العصري من جورجيو أرماني ليواكب روح الحداثة.\r\nيفاجئك هذا العطر الفريد للغاية بأصالته حيث تحتوي روائحه الأساسية على رائحة حارّة - مزيج من الهيل والفلفل الوردي وأوراق البنفسج. وبفضل قلب العطر العطري المرمري ستتمتع بثقة وأناقة الرجال ولا مبالاة الشباب في الوقت نفسه.\r\nيُعبر هذا العطر عن الجاذبية عن طريق رائحة الفانيليا الدخانية المستخلصة من الغابة™ التي تتعانق مع نفحة الكستناء المُغلف بالسُّكر، مستسلمةً لجاذبيته التي لا تقاوم: سترونجر وذ يو.', 'img_6a35d2e4ea43c5.98194487.webp', 0, '2026-06-19 23:39:07', '2026-06-19 23:39:07', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(81, 'stronger-with-you-intensely', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Stronger With You Intensely', 'سترونجر ويذ يو انتنسلي', '', '', 'Emporio Armani Stronger With You Intensely by Giorgio Armani is an oriental-fougere fragrance for men. Emporio Armani Stronger With You Intensely was launched in 2019. The top notes are pink pepper, juniper and violet; the middle notes are toffee, cinnamon, lavender and sage; the base notes are vanilla, amber, tonka bean and suede.', 'Emporio Armani Stronger With You Intensely Giorgio Armani عطر شرقي - فوچير للرجال . Emporio Armani Stronger With You Intensely صدر عام 2019. إفتتاحية العطر الفلفل الوردي, العرعر و البنفسج; قلب العطر الطوفي, القرفة, الخزامي و المريمية; قاعدة العطر تتكون من الفانيليا, العنبر, حبوب التونكا و جلد الغزال (الجلد المدبوغ).', 'img_6a35d346ade4a5.91904406.webp', 0, '2026-06-19 23:40:25', '2026-06-19 23:40:25', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(82, 'stronger-with-you-amber', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Stronger With You Amber', 'سترونجر ويذ يو عنبر', '', '', 'Emporio Armani Stronger With You Amber by Giorgio Armani is an oriental-fougere fragrance for both men and women. It was launched in 2023. The fragrance features amber, Madagascan vanilla, and lavender.', 'Emporio Armani Stronger With You Amber Giorgio Armani عطر شرقي - فوچير للجنسين. Emporio Armani Stronger With You Amber صدر عام 2023. معلومات عن العطر العنبر, فانيليا مدغشقر و الخزامي.', 'img_6a35d3a7790c93.57065077.webp', 0, '2026-06-19 23:42:08', '2026-07-15 22:50:14', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(83, 'dior-homme-intense', 'men', 'both', 0, 0, 1, 1, 'Dior Homme Intense', 'ديور هوم انتنس', '', '', 'Dior Homme Intense 2011 by Dior is a woody-floral-musk fragrance for men. Dior Homme Intense 2011 was launched in 2011. The nose behind this fragrance is François Demachy. The top note is lavender; the middle notes are iris, ambrette, and pear; and the base notes are Virginia cedar and vetiver.', 'Dior Homme Intense 2011 Dior عطر خشبي - زهري - مسك للرجال . Dior Homme Intense 2011 صدر عام 2011. François Demachy قام بتوقيع هذا العطر. إفتتاحية العطر الخزامي; قلب العطر السوسن, الأمبريت و الكمثري; قاعدة العطر تتكون من خشب الأرز من فرجينيا و نجيل الهند.', 'img_6a35d438eb0755.79226993.webp', 0, '2026-06-19 23:44:59', '2026-07-10 07:21:55', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(84, 'bvlgari-man-in-black', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Bvlgari Man In Black', 'بلغاري مان إن بلاك', '', '', 'Bvlgari Man In Black is an oriental-floral fragrance for men. It was launched in 2014. The nose behind this fragrance is Alberto Morillas. Top notes are spices, rum, and tobacco; middle notes are leather, iris, and tuberose; base notes are tonka bean, guaiac wood, and benzoin.', 'Bvlgari Man In Black Bvlgari عطر شرقي - زهري للرجال . Bvlgari Man In Black صدر عام 2014. Alberto Morillas قام بتوقيع هذا العطر. إفتتاحية العطر التوابل, الروم و التبغ; قلب العطر الجلود, السوسن و مسك الروم; قاعدة العطر تتكون من حبوب التونكا, أخشاب الغاياك و البنزوين - الجاوي.', 'img_6a35d4d16392c0.10786032.webp', 0, '2026-06-19 23:46:39', '2026-07-10 01:20:26', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(85, 'vip-212-black', 'designer-perfumes', 'both', 0, 0, 1, 1, 'VIP 212 black', 'VIP 212 black', '', '', '212 VIP Black by Carolina Herrera is an aromatic fougère fragrance for men. 212 VIP Black was launched in 2017. It was created by Carlos Benaïm and Anne Flipo. The top notes are absinthe, anise, and fennel; the middle note is lavender; and the base notes are black vanilla husk and musk.', '212 VIP Black Carolina Herrera عطر أروماتك - فوچير للرجال . 212 VIP Black صدر عام 2017. 212 VIP Black من توقيع Carlos Benaïm و Anne Flipo. إفتتاحية العطر شراب الأفسنتين , الينسون و الشمر; قلب العطر الخزامي; قاعدة العطر تتكون من قشور الفانيليا السوداء و المسك.', 'img_6a35d5c3559690.31042675.webp', 0, '2026-06-19 23:50:37', '2026-07-13 12:35:31', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(86, 'black-orchid', 'men', 'both', 0, 0, 1, 1, 'Black Orchid', 'بلاك أوركيد', '', '', 'Black Orchid by Tom Ford is an oriental-floral fragrance for women. Black Orchid was launched in 2006. Black Orchid was created by David Apel and Pierre Negrin. The top notes are truffle, gardenia, blackcurrant, ylang-ylang, jasmine, bergamot, mandarin orange, and lemon; the middle notes are orchid, spices, gardenia, fruity notes, ylang-ylang, jasmine, and lotus; the base notes are Mexican chocolate, patchouli, vanilla, incense, amber, sandalwood, vetiver, and white musk.', 'Black Orchid Tom Ford عطر شرقي - زهري للنساء . Black Orchid صدر عام 2006. Black Orchid من توقيع David Apel و Pierre Negrin. إفتتاحية العطر الكمأة, الغاردينيا, الكشمش الأسود, الإيلنغ, الياسمين, البرغموت, الماندرين (اليوسفي) و الليمون; قلب العطر الأوركيد, التوابل, الغاردينيا, نوتات الفواكه, الإيلنغ, الياسمين و اللوتس; قاعدة العطر تتكون من الشيكولاتة المكسيكية, الباتشولي, الفانيليا, البخور, العنبر, خشب الصندل, نجيل الهند و المسك الأبيض.', 'img_6a35d657131267.78030004.webp', 0, '2026-06-19 23:53:53', '2026-07-13 21:43:00', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(87, 'roses-vanille', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Roses Vanille', 'روز فانيليا', '', '', 'Roses Vanille by Mancera is an oriental-vanilla fragrance for women. Roses Vanille was launched in 2011. The nose behind this fragrance is Pierre Montale. Top note: Italian lemon; middle note: Turkish rose; base notes: vanilla, white musk, and cedarwood.', 'Roses Vanille Mancera عطر شرقي - فانيليا للنساء . Roses Vanille صدر عام 2011. Pierre Montale قام بتوقيع هذا العطر. إفتتاحية العطر الليمون الإيطالي; قلب العطر الورد التركي; قاعدة العطر تتكون من الفانيليا, المسك الأبيض و خشب الأرز.', 'img_6a35d71742a0d0.22759666.webp', 0, '2026-06-19 23:57:13', '2026-07-10 07:46:15', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(88, 'allure-homme-sport', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Allure Homme Sport', 'اللور هوم سبورت', '', '', 'Allure Homme Sport by Chanel is a woody-spicy fragrance for men. It was launched in 2004. The nose behind this fragrance is Jacques Polge. Top notes are orange, sea breeze, aldehydes, and red mandarin; middle notes are pepper, neroli, and cedarwood; base notes are vanilla, tonka bean, white musk, amber, vetiver, and elemi resin.', 'Allure Homme Sport Chanel عطر خشبي - حار للرجال . Allure Homme Sport صدر عام 2004. Jacques Polge قام بتوقيع هذا العطر. إفتتاحية العطر البرتقال, نسيم البحر, الألدهيدات و الماندرين الأحمر; قلب العطر الفلفل, النيرولي و خشب الأرز; قاعدة العطر تتكون من الفانيليا, حبوب التونكا, المسك الأبيض, العنبر, نجيل الهند و راتنج الإليمي.', 'img_6a35d7e1d5a3a1.39117509.webp', 0, '2026-06-19 23:59:44', '2026-07-13 19:02:25', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(89, 'egoiste-platinum-chanel', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Egoiste Platinum Chanel', 'بلاتنيوم', '', '', 'Fragrantica is an independent review platform and not endorsed, affiliated with, or authorized by Chanel. Chanel, its trademarks, bottle designs, and logos are property of Chanel, Inc. Fragrantica does not sell Chanel products or represent Chanel in any capacity.', 'زيج من التوازن والقوة.\r\nيضم هذا التناغم من الفوجير الأخضر كل الانتعاش العطري للخزامى وإكليل الجبل، المعزز بلمسة من البتيغرين من باراغواي. يتفتح قلب العطر بتناغم رجولي من القصعين المتصلب وإبرة الراعي، ثم يكشف عن مكونات نقية للغاية يبرز فيها دفء العنبر لنفحات الأخشاب المميزة', 'img_6a35d830bcae74.02081164.webp', 0, '2026-06-20 00:02:21', '2026-08-08 12:17:36', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(90, 'versace-pour-homme-dylan-blue', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Versace Pour Homme Dylan Blue', 'ديلان بلو', '', '', 'Versace Pour Homme Dylan Blue by Versace is an aromatic fougère fragrance for men. Versace Pour Homme Dylan Blue was launched in 2016. The nose behind this fragrance is Alberto Morillas. Top notes are Calabrian bergamot, aquatic notes, grapefruit, and fig leaves; middle notes are ambroxan, black pepper, patchouli, violet leaf, and papyrus; base notes are incense, musk, tonka bean, and saffron.', 'Versace Pour Homme Dylan Blue Versace عطر أروماتك - فوچير للرجال . Versace Pour Homme Dylan Blue صدر عام 2016. Alberto Morillas قام بتوقيع هذا العطر. إفتتاحية العطر برغموت كالابريا, رائحه الماء, الجريب فروت و أوراق التين; قلب العطر الأمبروكسان, الفلفل الأسود, الباتشولي, أوراق البنفسج و البردي; قاعدة العطر تتكون من البخور, المسك, حبوب التونكا و الزعفران.', 'img_6a35d8d8ded979.31463941.webp', 0, '2026-06-20 00:03:47', '2026-06-20 00:03:47', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(91, 'hugo', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Hugo', 'هوجو', '', '', 'Hugo Boss is an aromatic-herbal fragrance for men. Hugo was launched in 1995. The nose behind this fragrance is Bob Aliano. Top notes are green apple, lavender, mint, grapefruit, and basil; middle notes are sage, geranium, carnation, and jasmine; base notes are fir, cedarwood, and patchouli.', 'Hugo Hugo Boss عطر أروماتك - عشبي للرجال . Hugo صدر عام 1995. Bob Aliano قام بتوقيع هذا العطر. إفتتاحية العطر التفاح الأخضر, الخزامي, النعناع, الجريب فروت و الريحان; قلب العطر المريمية, إبره الراعي, زهر القرنفل و الياسمين; قاعدة العطر تتكون من التنوب, خشب الأرز و الباتشولي.', 'img_6a35d90accc025.55117121.webp', 0, '2026-06-20 00:05:06', '2026-07-10 02:06:53', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(92, 'scandal-pour-homme', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Scandal Pour Homme', 'سكاندل رجالي', '', '', 'Scandal Pour Homme by Jean Paul Gaultier is an oriental-woody fragrance for men. Scandal Pour Homme was launched in 2021. Scandal Pour Homme was created by Quentin Bisch, Christophe Raynaud, and Natalie Gracia-Cetto. The top notes are mandarin orange and sage; the middle notes are caramel and tonka bean; and the base note is vetiver.', 'Scandal Pour Homme Jean Paul Gaultier عطر شرقي - خشبي للرجال . Scandal Pour Homme صدر عام 2021. Scandal Pour Homme من توقيع Quentin Bisch, Christophe Raynaud و Natalie Gracia-Cetto. إفتتاحية العطر الماندرين (اليوسفي) و المريمية; قلب العطر الكاراميل و حبوب التونكا; قاعدة العطر من نجيل الهند.', 'img_6a35d95edf0573.57679610.webp', 0, '2026-06-20 00:06:22', '2026-08-07 12:08:28', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(93, 'vanilla-powder', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Vanilla Powder', 'فانيليا باودر', '', '', 'Vanilla Powder Matiere Premiere is a unisex fragrance. Vanilla Powder was launched in 2023. The nose behind this fragrance is Aurélien Guichard. The top notes are coconut powder and heliotrope; the heart note is Madagascan vanilla; and the base notes are vanilla, white musk, musk, palo santo, coconut, and lactones.', 'Vanilla Powder Matiere Premiere عطر للجنسين. Vanilla Powder صدر عام 2023. Aurélien Guichard قام بتوقيع هذا العطر. إفتتاحية العطر بودرة جوز الهند و الهيلوتروب; قلب العطر فانيليا مدغشقر; قاعدة العطر تتكون من الفانيليا, المسك الأبيض, المسك, بالو سانتو, جوز الهند و لاكتونات.', 'img_6a35d9d8a70bb8.57770077.webp', 0, '2026-06-20 00:08:33', '2026-07-17 22:07:46', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(94, 'armani-code', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Armani Code', 'ارماني كود', '', '', 'Armani Code Parfum by Giorgio Armani is a woody-aromatic fragrance for men. It was launched in 2022. The nose behind this fragrance is Antoine Maisondieu. The top notes are bergamot and bergamot leaf; the middle notes are iris, aldehydes, sage, and orris; and the base notes are tonka bean and cedarwood.', 'Armani Code Parfum Giorgio Armani عطر خشبي - أروماتك للرجال . Armani Code Parfum صدر عام 2022. Antoine Maisondieu قام بتوقيع هذا العطر. إفتتاحية العطر البرغموت و أوراق البارجاموت; قلب العطر و السوسن, الألدهيدات, المريمية و السوسن; قاعدة العطر تتكون من حبوب التونكا و خشب الأرز.', 'img_6a35da66ca1949.36697257.webp', 0, '2026-06-20 00:10:46', '2026-07-18 08:24:16', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(95, 'khamrah-qahwa-lattafa', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Khamrah Qahwa Lattafa', 'خمرة قهوة', '', '', 'Khamrah Qahwa by Lattafa Perfumes is an oriental-vanilla fragrance for both men and women. Khamrah Qahwa was launched in 2023. The top notes are cinnamon, cardamom, and ginger; the middle notes are praline, candied fruits, and white flowers; and the base notes are vanilla, coffee, tonka bean, benzoin, and musk.', 'Khamrah Qahwa Lattafa Perfumes عطر شرقي - فانيليا للجنسين. Khamrah Qahwa صدر عام 2023. إفتتاحية العطر القرفة, الهيل و الزنجبيل; قلب العطر حلوي اللوز, الفواكة المجففة (المسكرة) و الزهور البيضاء; قاعدة العطر تتكون من الفانيليا, القهوه, حبوب التونكا, البنزوين - الجاوي و المسك.', 'img_6a35dad0af1256.51512862.webp', 0, '2026-06-20 00:12:40', '2026-07-15 09:35:51', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(96, 'jimmy-choo-man', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Jimmy Choo Man', 'جيمي شو', '', '', 'Jimmy Choo Man is an aromatic-fruity fragrance for men. It was launched in 2014. The nose behind this fragrance is Anne Flipo. Fragrance notes include pineapple leaf, suede, lavender, melon, pink pepper, and patchouli.', 'Jimmy Choo Man Jimmy Choo عطر أروماتك - فواكه للرجال . Jimmy Choo Man صدر عام 2014. Anne Flipo قام بتوقيع هذا العطر. معلومات عن العطر أوراق الأناناس, جلد الغزال (الجلد المدبوغ), الخزامي, شمام, الفلفل الوردي و الباتشولي.', 'img_6a35db428eb6a3.18850400.webp', 0, '2026-06-20 00:14:48', '2026-07-21 17:17:41', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(97, '1-million', 'designer-perfumes', 'both', 0, 0, 1, 1, '1 Million', 'وان مليون', '', '', '1 Million by Rabanne is a woody-spicy fragrance for men. 1 Million was launched in 2008. 1 Million was created by Christophe Raynaud, Olivier Pescheux, and Michel Girard. The top notes are red mandarin, grapefruit, and mint; the middle notes are cinnamon, spices, and rose; and the base notes are amber, leather, woods, and Indian patchouli.', '1 Million Rabanne عطر خشبي - حار للرجال . 1 Million صدر عام 2008. 1 Million من توقيع Christophe Raynaud, Olivier Pescheux و Michel Girard. إفتتاحية العطر الماندرين الأحمر, الجريب فروت و النعناع; قلب العطر القرفة, رائحه التوابل و الورد; قاعدة العطر تتكون من العنبر, الجلود, الأخشاب و الباتشولي الهندي.', 'img_6a35dbc76b5013.63804177.webp', 0, '2026-06-20 00:17:02', '2026-07-16 20:47:19', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(98, 'x-s', 'designer-perfumes', 'both', 0, 0, 2, 1, 'بيور X.S', 'Pure XS', '', '', 'Pure XS by Rabanne is an aromatic-spicy fragrance for men. Pure XS was launched in 2017. Pure XS was created by Anne Flipo, Caroline Dumur, and Bruno Jovanovic. Top notes are ginger, grapefruit, thyme, bergamot, and green accords; middle notes are vanilla, liqueur, cinnamon, leather, and apple; base notes are myrrh, sugar, woods, cedarwood, cashmere wood, and patchouli.', 'Pure XS Rabanne عطر أروماتك - حار للرجال . Pure XS صدر عام 2017. Pure XS من توقيع Anne Flipo, Caroline Dumur و Bruno Jovanovic. إفتتاحية العطر الزنجبيل, الجريب فروت, الزعتر, البرغموت و الإتفاقات الخضراء; قلب العطر الفانيليا, الخمر, القرفة, الجلود و التفاح; قاعدة العطر تتكون من المر, السكر, الأخشاب, خشب الأرز, أخشاب الكشمير و الباتشولي.', 'img_6a35dc36bd0cb8.85448004.webp', 0, '2026-06-20 00:18:42', '2026-07-09 08:24:14', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(99, 'nautica-voyage', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Nautica Voyage', 'فوياج', '', '', 'Nautica Voyage is a woody-aquatic fragrance for men. It was launched in 2006. The nose behind this fragrance is Maurice Roucel. The top notes are green leaves and apple; the middle notes are lotus and mimosa; and the base notes are musk, cedarwood, oakmoss, and amber.', 'Nautica Voyage Nautica عطر خشبي - مائي للرجال . Nautica Voyage صدر عام 2006. Maurice Roucel قام بتوقيع هذا العطر. إفتتاحية العطر الأوراق الخضراء و التفاح; قلب العطر اللوتس و الميموزا; قاعدة العطر تتكون من المسك, خشب الأرز, طحلب البلوط (طحلب السنديان) و العنبر.', 'img_6a35dcdde0da78.45635492.webp', 0, '2026-06-20 00:20:55', '2026-06-20 00:20:55', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(100, 'vip-212', 'designer-perfumes', 'both', 0, 0, 0, 1, 'VIP 212', 'VIP 212', '', '', '212 VIP Men by Carolina Herrera is an oriental-woody fragrance for men. 212 VIP Men was launched in 2011. 212 VIP Men was created by Emilie Coppermann and Lucas Sieuzac. The top notes are passion fruit, lime, pepper, ginger, and finger lime; the middle notes are vodka, gin, mint, and spices; and the base notes are amber, leather, and woods.', '212 VIP Men Carolina Herrera عطر شرقي - خشبي للرجال . 212 VIP Men صدر عام 2011. 212 VIP Men من توقيع Emilie Coppermann و Lucas Sieuzac. إفتتاحية العطر الباشون فروت, الليم, الفلفل, الزنجبيل و الليم الأصبعي; قلب العطر الفودكا, الجين, النعناع و التوابل; قاعدة العطر تتكون من العنبر, الجلود و الأخشاب.', 'img_6a37cb35c62779.08917752.webp', 0, '2026-06-21 11:30:08', '2026-06-21 11:30:08', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(101, 'invictus-elixir', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Invictus Elixir', 'انفكتوس الكسير', '', '', 'Invictus Victory Elixir by Rabanne is an oriental-woody fragrance for men. Invictus Victory Elixir was launched in 2023. It was created by Domitille Michalon Bertier, Anne Flipo, and Nicolas Beaulieu. The top notes are lavender, cardamom, and black pepper; the middle notes are incense and patchouli; and the base notes are vanilla and tonka bean.', 'Invictus Victory Elixir Rabanne عطر شرقي - خشبي للرجال . Invictus Victory Elixir صدر عام 2023. Invictus Victory Elixir من توقيع Domitille Michalon Bertier, Anne Flipo و Nicolas Beaulieu. إفتتاحية العطر الخزامي, الهيل و الفلفل الأسود; قلب العطر البخور و الباتشولي; قاعدة العطر تتكون من الفانيليا و حبوب التونكا.', 'img_6a37cb97c485d9.35024666.webp', 0, '2026-06-21 11:32:56', '2026-07-15 09:36:11', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(102, 'invictus-victory', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Invictus Victory', 'انفكتوس فيكتوري', '', '', 'Invictus Victory by Rabanne is an oriental fragrance for men. Invictus Victory was launched in 2021. The top notes are pink pepper and lemon; the middle notes are frankincense and lavender; the base notes are vanilla, tonka bean, and amber.', 'Invictus Victory Rabanne عطر شرقي للرجال . Invictus Victory صدر عام 2021. إفتتاحية العطر الفلفل الوردي و الليمون; قلب العطر اللبان و الخزامي; قاعدة العطر تتكون من الفانيليا, حبوب التونكا و العنبر.', 'img_6a37cc19a485f2.03857181.webp', 0, '2026-06-21 11:35:08', '2026-06-21 11:35:08', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(103, 'vanilla-6', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Vanilla 6', 'فانيليا 6', '', '', 'Vanilla by Tom Ford is an oriental-vanilla fragrance for both men and women. It was launched in 2023. The fragrance features notes of Indian vanilla, vanilla, sandalwood, an animalic note, orris root, and jasmine.', 'Vanilla Tom Ford عطر شرقي - فانيليا للجنسين. Vanilla صدر عام 2023. معلومات عن العطر الفانيليا الهنديه, الفانيليا, خشب الصندل, نوتة حيوانية, جذور السوسن و الياسمين.', 'img_6a37ce48341929.68254363.webp', 0, '2026-06-21 11:43:15', '2026-07-10 02:25:59', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(104, 'le-male-elixir', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Le Male Elixir', 'لوميل الكسير', '', '', 'Le Male Elixir by Jean Paul Gaultier is an oriental-fougere fragrance for men. Le Male Elixir was launched in 2023. The nose behind this fragrance is Quentin Bisch. Top notes are lavender and mint; middle notes are vanilla and benzoin; base notes are honey, tonka bean, and tobacco.', 'Le Male Elixir Jean Paul Gaultier عطر شرقي - فوچير للرجال . Le Male Elixir صدر عام 2023. Quentin Bisch قام بتوقيع هذا العطر. إفتتاحية العطر الخزامي و النعناع; قلب العطر الفانيليا و البنزوين - الجاوي; قاعدة العطر تتكون من العسل, حبوب التونكا و التبغ.', 'img_6a37ceede78d61.80295838.webp', 0, '2026-06-21 11:46:23', '2026-06-21 11:46:23', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(105, 'khamrah-lattafa-8948', 'designer-perfumes', 'both', 0, 0, 5, 1, 'Khamrah Lattafa', 'خمرة', '', '', 'Khamrah by Lattafa Perfumes is an oriental-spicy fragrance for both men and women. Khamrah was launched in 2022. The top notes are cinnamon, nutmeg, and bergamot; the middle notes are dates, almond candy, tuberose, and mahonyal; and the base notes are vanilla, tonka bean, amberwood, myrrh, benzoin, and akgalwood.', 'Khamrah Lattafa Perfumes عطر شرقي - حار للجنسين. Khamrah صدر عام 2022. إفتتاحية العطر القرفة, جوزه الطيب و البرغموت; قلب العطر التمر/البلح, حلوي اللوز, مسك الروم و ماهونيال; قاعدة العطر تتكون من الفانيليا, حبوب التونكا, خشب العنبر, المر, البنزوين - الجاوي و أكيغالاوود.', 'img_6a37d01b137b06.19384780.webp', 0, '2026-06-21 11:51:03', '2026-07-21 22:21:21', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(106, 'love-is-heavenly', 'designer-perfumes', 'both', 0, 0, 12, 1, 'Love is Heavenly', 'لاف از هيفنلي', '', '', 'Love is Heavenly (2016) by Victoria\'s Secret is a floral-fruity fragrance for women. Love is Heavenly (2016) was launched in 2016. The top notes are mandarin blossom, blackberry, orange, and kiwi; the middle notes are water lily, freesia, peony, and orchid; the base notes are musk, sandalwood, ebony, and mahogany.', 'Love is Heavenly (2016) Victoria\'s Secret عطر زهري - فواكه للنساء . Love is Heavenly (2016) صدر عام 2016. إفتتاحية العطر زهر الماندرين , التوت الأسود, البرتقال و الكيوي; قلب العطر زنبق الماء, الفريزيا, الفاوانيا و الأوركيد; قاعدة العطر تتكون من المسك, خشب الصندل, أخشاب الأبنوس و أخشاب الماهوجني.', 'img_6a3a8eeb13dd48.33969968.webp', 0, '2026-06-23 13:50:27', '2026-08-08 12:18:18', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(107, 'scandal', 'designer-perfumes', 'both', 0, 0, 9, 1, 'Scandal', 'سكاندل حريمي', '', '', 'Scandal by Jean Paul Gaultier is a Chypre Floral fragrance for women. Scandal was launched in 2017. Scandal was created by Daphné Bugey, Fabrice Pellegrin, and Christophe Raynaud. The top notes are blood orange and mandarin orange; the middle notes are honey, gardenia, orange blossom, jasmine, and peach; and the base notes are beeswax, caramel, patchouli, and licorice.', 'Scandal Jean Paul Gaultier عطر تشيبر - زهري للنساء . Scandal صدر عام 2017. Scandal من توقيع Daphné Bugey, Fabrice Pellegrin و Christophe Raynaud. إفتتاحية العطر البرتقال الأحمر و الماندرين (اليوسفي); قلب العطر العسل, الغاردينيا, زهر البرتقال, الياسمين و الخوخ; قاعدة العطر تتكون من شمع العسل, الكاراميل, الباتشولي و العرقسوس.', 'img_6a3a8fb6a97458.77985990.webp', 0, '2026-06-23 13:54:15', '2026-07-23 00:18:42', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(108, 'olymp-a', 'unisex', 'both', 0, 0, 0, 1, 'Olympéa', 'اوليمبيا', '', '', 'Olympéa Rabanne is an oriental-floral fragrance for women. Olympéa was launched in 2015. Olympéa was created by Loc Dong, Anne Flipo, and Dominique Ropion. The top notes are water jasmine, green mandarin, and ginger flower; the middle notes are vanilla and salt; and the base notes are cashmere wood, ambergris, and sandalwood.', 'Olympéa Rabanne عطر شرقي - زهري للنساء . Olympéa صدر عام 2015. Olympéa من توقيع Loc Dong, Anne Flipo و Dominique Ropion. إفتتاحية العطر الياسمين المائي, الماندرين الأخضر و زهور الزنجبيل; قلب العطر الفانيليا و الملح; قاعدة العطر تتكون من أخشاب الكشمير, الآمبرغريس و خشب الصندل.', 'img_6a3a9057e52dc2.13094765.webp', 0, '2026-06-23 13:57:23', '2026-06-23 13:57:23', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(109, 'la-vie-est-belle', 'designer-perfumes', 'both', 0, 0, 1, 1, 'La Vie Est Belle', 'لافي بيل', '', '', 'La Vie Est Belle by Lancôme is a floral-fruity-gourmand fragrance for women. La Vie Est Belle was launched in 2012. La Vie Est Belle was created by Olivier Polge, Dominique Ropion, and Anne Flipo. The top notes are blackcurrant and pear; the middle notes are iris, jasmine, and orange blossom; and the base notes are praline, vanilla, patchouli, and tonka bean.', 'La Vie Est Belle Lancôme عطر زهري - فواكه - جورماند للنساء . La Vie Est Belle صدر عام 2012. La Vie Est Belle من توقيع Olivier Polge, Dominique Ropion و Anne Flipo. إفتتاحية العطر الكشمش الأسود و الكمثري; قلب العطر السوسن, الياسمين و زهر البرتقال; قاعدة العطر تتكون من حلوي اللوز, الفانيليا, الباتشولي و حبوب التونكا.', 'img_6a3a90ec8841b9.97357894.webp', 0, '2026-06-23 13:58:57', '2026-06-25 21:32:59', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(110, 'bombshell', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Bombshell', 'بامب شيل', '', '', 'Bombshell by Victoria\'s Secret is a floral-fruity fragrance for women. Bombshell was launched in 2010. Bombshell was created by Adriana Medina-Baez and Mark Knitowski. Top notes are passion fruit, grapefruit, pineapple, tangerine, and strawberry; middle notes are peony, vanilla orchid, red berries, jasmine, and lily-of-the-valley; base notes are musk, woods, and oakmoss.', 'Bombshell Victoria\'s Secret عطر زهري - فواكه للنساء . Bombshell صدر عام 2010. Bombshell من توقيع Adriana Medina-Baez و Mark Knitowski. إفتتاحية العطر الباشون فروت, الجريب فروت, الأناناس, تانجرين (اليوسفي) و الفراوله; قلب العطر الفاوانيا, أوركيد الفانيلا, التوت الأحمر, الياسمين و زنابق الوادي; قاعدة العطر تتكون من المسك, الأخشاب و طحلب البلوط (طحلب السنديان).', 'img_6a3a9160f3d175.80436102.webp', 0, '2026-06-23 14:00:55', '2026-08-08 12:18:41', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(111, 'good-girl', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Good Girl', 'جود جيرل', '', '', 'Good Girl by Carolina Herrera is an oriental-floral fragrance for women. Good Girl was launched in 2016. Good Girl was created by Louise Turner and Quentin Bisch. The top notes are almond, coffee, bergamot, and lemon; the middle notes are tuberose, jasmine sambac, orange blossom, Bulgarian rose, and iris; the base notes are tonka bean, cacao, vanilla, praline, sandalwood, musk, amber, cashmere wood, patchouli, cinnamon, and cedarwood.', 'Good Girl Carolina Herrera عطر شرقي - زهري للنساء . Good Girl صدر عام 2016. Good Girl من توقيع Louise Turner و Quentin Bisch. إفتتاحية العطر اللوز, القهوه, البرغموت و الليمون; قلب العطر مسك الروم, ياسمين سامباك , زهر البرتقال, الورد البلغاري و السوسن; قاعدة العطر تتكون من حبوب التونكا, الكاكاو, الفانيليا, حلوي اللوز, خشب الصندل, المسك, العنبر, أخشاب الكشمير, الباتشولي, القرفة و خشب الأرز.', 'img_6a3a91d728f618.90916289.webp', 0, '2026-06-23 14:02:30', '2026-07-12 20:13:03', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(112, 'black-opium', 'designer-perfumes', 'both', 0, 0, 0, 1, 'Black Opium', 'بلاك ابيوم', '', '', 'Black Opium by Yves Saint Laurent is an oriental-vanilla fragrance for women. Black Opium was launched in 2014. Black Opium was created by Nathalie Lorson, Marie Salamagne, Olivier Cresp, and Honorine Blanc. The top notes are pear, pink pepper, and orange blossom; the middle notes are coffee, jasmine, bitter almond, and licorice; and the base notes are vanilla, patchouli, cashmere wood, and cedarwood.', 'Black Opium Yves Saint Laurent عطر شرقي - فانيليا للنساء . Black Opium صدر عام 2014. Black Opium من توقيع Nathalie Lorson, Marie Salamagne, Olivier Cresp و Honorine Blanc. إفتتاحية العطر الكمثري, الفلفل الوردي و زهر البرتقال; قلب العطر القهوه, الياسمين, اللوز المر و العرقسوس; قاعدة العطر تتكون من الفانيليا, الباتشولي, أخشاب الكشمير و خشب الأرز.', 'img_6a3a921f0821b3.42309069.webp', 0, '2026-06-23 14:04:11', '2026-06-23 14:04:11', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(113, 'joy-by-dior', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Joy by Dior', 'جوي', '', '', 'Joy by Dior is a floral-woody-musk fragrance for women. It was launched in 2018. The nose behind this fragrance is François Demachy. Top notes are bergamot and mandarin orange; middle notes are jasmine, Grasse rose, peach, and blackcurrant; base notes are white musk, sandalwood, cedarwood, patchouli, and benzoin.', 'Joy by Dior Dior عطر زهري - خشبي - مسك للنساء . Joy by Dior صدر عام 2018. François Demachy قام بتوقيع هذا العطر. إفتتاحية العطر البرغموت و الماندرين (اليوسفي); قلب العطر الياسمين, ورد غراس , الخوخ و الكشمش الأسود; قاعدة العطر تتكون من المسك الأبيض, خشب الصندل, خشب الأرز, الباتشولي و البنزوين - الجاوي.', 'img_6a3a92d30ab738.53314187.webp', 0, '2026-06-23 14:06:40', '2026-07-13 15:24:08', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(114, 'midnight-fantasy-britney-spears', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Midnight Fantasy Britney Spears', 'ميد نايت', '', '', 'Midnight Fantasy by Britney Spears is a floral-fruity fragrance for women. Midnight Fantasy was launched in 2006. The nose behind this fragrance is Caroline Sabas. Top notes are plum, sour cherry, and raspberry; middle notes are orchid, iris, and freesia; base notes are vanilla, musk, and amber.', 'Midnight Fantasy Britney Spears عطر زهري - فواكه للنساء . Midnight Fantasy صدر عام 2006. Caroline Sabas قام بتوقيع هذا العطر. إفتتاحية العطر البرقوق, الكرز الحامض و توت العليق; قلب العطر الأوركيد, السوسن و الفريزيا; قاعدة العطر تتكون من الفانيليا, المسك و العنبر.', 'img_6a3a94622e9c56.44246553.webp', 0, '2026-06-23 14:13:11', '2026-07-06 02:26:01', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(115, 'gucci-flora', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Gucci Flora', 'جوتشي فلورا', '', '', 'Flora Gorgeous Gardenia by Gucci is a floral fragrance for women. Flora Gorgeous Gardenia was launched in 2021. The nose behind this fragrance is Honorine Blanc. Top notes are pear blossom, red berries, and Italian mandarin; middle notes are gardenia, jasmine, and frangipani; base notes are brown sugar and patchouli.', 'Flora Gorgeous Gardenia Gucci عطر زهري للنساء . Flora Gorgeous Gardenia صدر عام 2021. Honorine Blanc قام بتوقيع هذا العطر. إفتتاحية العطر زهر الكمثري, التوت الأحمر و الماندرين الإيطالي; قلب العطر الغاردينيا, الياسمين و الفرانجيباني; قاعدة العطر تتكون من السكر البني و الباتشولي.', 'img_6a3a9543dd1db1.83257930.webp', 0, '2026-06-23 14:16:46', '2026-07-08 12:05:51', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(116, '212-sexy-carolina-herrera', 'designer-perfumes', 'both', 0, 0, 1, 1, '212 Sexy Carolina Herrera', 'سكسي حريمي 212', '', '', '212 Sexy by Carolina Herrera is an oriental-floral fragrance for women. 212 Sexy was launched in 2004. The nose behind this fragrance is Rosendo Mateu. Top notes are pink pepper, mandarin orange, and bergamot; middle notes are cotton candy, gardenia, floral notes, geranium, and rose; base notes are vanilla, musk, sandalwood, caramel, violet, and patchouli.', '212 Sexy Carolina Herrera عطر شرقي - زهري للنساء . 212 Sexy صدر عام 2004. Rosendo Mateu قام بتوقيع هذا العطر. إفتتاحية العطر الفلفل الوردي, الماندرين (اليوسفي) و البرغموت; قلب العطر غزل البنات, الغاردينيا, الزهور, اللقلقي و الورد; قاعدة العطر تتكون من الفانيليا, المسك, خشب الصندل, الكاراميل, البنفسج و الباتشولي.', 'img_6a3a96edf2d486.47002890.webp', 0, '2026-06-23 14:24:05', '2026-07-06 21:33:29', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(117, 'coco-mademoiselle-chanel', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Coco Mademoiselle Chanel', 'كوكو مادمزيل شانيل', '', '', 'Coco Mademoiselle by Chanel is an oriental-floral fragrance for women. Coco Mademoiselle was launched in 2001. The nose behind this fragrance is Jacques Polge. Top notes are orange, mandarin orange, bergamot, and orange blossom; middle notes are Turkish rose, jasmine, mimosa, and ylang-ylang; base notes are patchouli, white musk, vanilla, vetiver, tonka bean, and galbanum. This fragrance won the FiFi Award for Best National Advertising Campaign/TV in 2008 .', 'Coco Mademoiselle Chanel عطر شرقي - زهري للنساء . Coco Mademoiselle صدر عام 2001. Jacques Polge قام بتوقيع هذا العطر. إفتتاحية العطر البرتقال, الماندرين (اليوسفي), البرغموت و زهر البرتقال; قلب العطر الورد التركي, الياسمين, الميموزا و الإيلنغ; قاعدة العطر تتكون من الباتشولي, المسك الأبيض, الفانيليا, نجيل الهند, حبوب التونكا و الجاوشير. هذاالعطر فائز بجائزة FiFi Award Best National Advertising Campaign / TV 2008.', 'img_6a3a9770678180.92478739.webp', 0, '2026-06-23 14:26:36', '2026-07-08 16:33:43', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(118, 's-passione', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Sì Passione', 'سي باشون', '', '', 'Sì Passione by Giorgio Armani is a floral-fruity fragrance for women. Sì Passione was launched in 2017. Sì Passione was created by Christine Nagel and Julie Massé. Top notes are pear, blackcurrant, pink pepper, and grapefruit; middle notes are pineapple, rose, jasmine, and heliotrope; base notes are vanilla, cedarwood, patchouli, and amberwood.', 'Sì Passione Giorgio Armani عطر زهري - فواكه للنساء . Sì Passione صدر عام 2017. Sì Passione من توقيع Christine Nagel و Julie Massé. إفتتاحية العطر الكمثري, الكشمش الأسود, الفلفل الوردي و الجريب فروت; قلب العطر الأناناس, الورد, الياسمين و الهيلوتروب; قاعدة العطر تتكون من الفانيليا, خشب الأرز, الباتشولي و خشب العنبر.', 'img_6a3a9832046a38.37839664.webp', 0, '2026-06-23 14:29:36', '2026-07-05 08:00:08', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(119, 'body-burberry', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Body Burberry', 'بربري بادي', '', '', 'Body by Burberry is a chypre-fruity fragrance for women. Body was launched in 2011. The nose behind this fragrance is Michel Almairac. Top notes are absinthe, peach, and freesia; middle notes are rose, sandalwood, and iris; base notes are musk, cashmere wood, vanilla, and amber.', 'Body Burberry عطر تشيبر - فواكه للنساء . Body صدر عام 2011. Michel Almairac قام بتوقيع هذا العطر. إفتتاحية العطر الأفسنتين نبات, الخوخ و الفريزيا; قلب العطر الورد, خشب الصندل و السوسن; قاعدة العطر تتكون من المسك, أخشاب الكشمير, الفانيليا و العنبر.', 'img_6a3a99d04db482.13999561.webp', 0, '2026-06-23 14:36:20', '2026-07-08 12:14:01', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(120, 'si-giorgio-armani', 'unisex', 'both', 0, 0, 0, 1, 'Si Giorgio Armani', 'سي ارماني', '', '', 'Si by Giorgio Armani is a chypre-fruity fragrance for women. Si was launched in 2013. The nose behind this fragrance is Christine Nagel. Top note is blackcurrant; middle notes are May rose and freesia; base notes are vanilla, patchouli, woods, and ambroxan.', 'Si Giorgio Armani عطر تشيبر - فواكه للنساء . Si صدر عام 2013. Christine Nagel قام بتوقيع هذا العطر. إفتتاحية العطر الكشمش الأسود; قلب العطر ورد ماي و الفريزيا; قاعدة العطر تتكون من الفانيليا, الباتشولي, الأخشاب و الأمبروكسان.', 'img_6a3a99ff51c3d5.58275149.webp', 0, '2026-06-23 14:39:00', '2026-06-23 14:39:00', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(121, 'libre', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Libre', 'ليبر', '', '', 'Libre by Yves Saint Laurent is an oriental-fougere fragrance for women. Libre was launched in 2019. Libre was created by Anne Flipo and Carlos Benaïm. Top notes are lavender, mandarin orange, blackcurrant, and petitgrain; middle notes are lavender, orange blossom, and jasmine; base notes are Madagascan vanilla, musk, cedarwood, and ambergris.', 'Libre Yves Saint Laurent عطر شرقي - فوچير للنساء . Libre صدر عام 2019. Libre من توقيع Anne Flipo و Carlos Benaïm. إفتتاحية العطر الخزامي, الماندرين (اليوسفي), الكشمش الأسود و البيتيتغرين; قلب العطر الخزامي, زهر البرتقال و الياسمين; قاعدة العطر تتكون من فانيليا مدغشقر, المسك, خشب الأرز و الآمبرغريس.', 'img_6a3a9b1ea52991.39201076.webp', 0, '2026-06-23 14:41:54', '2026-07-09 10:46:16', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(122, 'vanilla-28-kayali', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Vanilla | 28 Kayali', 'كيالي 28', '', '', 'Vanilla | 28 Kayali Fragrances is an oriental-vanilla fragrance for both men and women. Vanilla | 28 was launched in 2018. The nose behind this fragrance is Gabriela Chelariu. Top notes are vanilla orchid and jasmine; middle notes are brown sugar and tonka bean; base notes are amber, ambergris, musk, and patchouli.', 'Vanilla | 28 Kayali Fragrances عطر شرقي - فانيليا للجنسين. Vanilla | 28 صدر عام 2018. Gabriela Chelariu قام بتوقيع هذا العطر. إفتتاحية العطر أوركيد الفانيلا و الياسمين; قلب العطر السكر البني و حبوب التونكا; قاعدة العطر تتكون من العنبر, خشب العنبر, المسك و الباتشولي.', 'img_6a3a9b78118216.48990972.webp', 0, '2026-06-23 14:43:55', '2026-07-14 20:04:02', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(123, 'very-sexy-now', 'designer-perfumes', 'both', 0, 0, 2, 1, 'Very Sexy Now', 'فيري سكسي ناو', '', '', 'Very Sexy Now 2016 Victoria\'s Secret عطر زهري - فواكه - جورماند للنساء . Very Sexy Now 2016 صدر عام 2016. إفتتاحية العطر عصير جوز الهند و نوتات الفواكه; قلب العطر اللوتس الوردي; قاعدة العطر تتكون من رمال, الفانيليا و المسك الأبيض.', 'Very Sexy Now 2016 by Victoria\'s Secret is a floral-fruity-gourmand fragrance for women. Very Sexy Now 2016 was launched in 2016. The top notes are coconut juice and fruity notes; the middle note is pink lotus; and the base notes are sand, vanilla, and white musk.', 'img_6a3acc7fec60f2.71536313.webp', 0, '2026-06-23 18:12:30', '2026-07-06 06:31:47', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(124, 'roberto-cavalli', 'unisex', 'both', 0, 0, 0, 1, 'Roberto Cavalli', 'ريبيرتو كافالي', '', '', 'Roberto Cavalli Eau de Parfum is an oriental-floral fragrance for women. It was launched in 2012. The nose behind this fragrance is Louise Turner. The top note is pink pepper; the middle note is African orange blossom; and the base notes are vanilla, benzoin, and tonka bean.', 'Roberto Cavalli Eau de Parfum Roberto Cavalli عطر شرقي - زهري للنساء . Roberto Cavalli Eau de Parfum صدر عام 2012. Louise Turner قام بتوقيع هذا العطر. إفتتاحية العطر الفلفل الوردي; قلب العطر زهر البرتقال الأفريقي; قاعدة العطر تتكون من الفانيليا, البنزوين - الجاوي و حبوب التونكا.', 'img_6a3accf7804266.67880723.webp', 0, '2026-06-23 18:14:31', '2026-06-23 18:14:31', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(125, 'escada-moon-sparkle', 'designer-perfumes', 'both', 0, 0, 1, 1, 'Escada Moon Sparkle', 'مون سباركل', '', '', 'Escada Moon Sparkle by Escada is a floral-fruity fragrance for women. Escada Moon Sparkle was launched in 2007. The nose behind this fragrance is Aurélien Guichard. Top notes are strawberry, blackcurrant, red apple, and citrus; middle notes are sweet pea, freesia, jasmine, and rose; base notes are raspberry, musk, sandalwood, and amber.', 'Escada Moon Sparkle Escada عطر زهري - فواكه للنساء . Escada Moon Sparkle صدر عام 2007. Aurélien Guichard قام بتوقيع هذا العطر. إفتتاحية العطر الفراوله, الكشمش الأسود, التفاح الأحمر و الحمضيات; قلب العطر البسله الحلوه, الفريزيا, الياسمين و الورد; قاعدة العطر تتكون من توت العليق, المسك, خشب الصندل و العنبر.', 'img_6a3ace64799841.10037712.webp', 0, '2026-06-23 18:17:28', '2026-07-03 11:13:33', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(126, 'burberry-her', 'designer-perfumes', 'both', 0, 0, 3, 1, 'Burberry Her', 'بربري هير', '', '', 'Burberry Her by Burberry is a floral-fruity-gourmand fragrance for women. Burberry Her was launched in 2018. The nose behind this fragrance is Francis Kurkdjian. Top notes are strawberry, raspberry, blackberry, sour cherry, blackcurrant, mandarin orange, and lemon; middle notes are violet and jasmine; base notes are musk, vanilla, cashmere wood, woods, amber, oakmoss, and patchouli.', 'Burberry Her Burberry عطر زهري - فواكه - جورماند للنساء . Burberry Her صدر عام 2018. Francis Kurkdjian قام بتوقيع هذا العطر. إفتتاحية العطر الفراوله, توت العليق, التوت الأسود, الكرز الحامض, الكشمش الأسود, الماندرين (اليوسفي) و الليمون; قلب العطر البنفسج و الياسمين; قاعدة العطر تتكون من المسك, الفانيليا, أخشاب الكشمير, الأخشاب, العنبر, طحلب البلوط (طحلب السنديان) و الباتشولي.', 'img_6a3ace4166b511.75071335.webp', 0, '2026-06-23 18:19:55', '2026-08-08 10:49:23', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0);
INSERT INTO `products` (`id`, `slug`, `category`, `season`, `is_bestseller`, `is_offer`, `view_count`, `active`, `name_en`, `name_ar`, `notes_en`, `notes_ar`, `description_en`, `description_ar`, `primary_image_key`, `sort_order`, `created_at`, `updated_at`, `file_sharing_url`, `brand_id`, `is_brand_product`, `ai_profile_ar`, `ai_keywords_ar`, `ai_intensity`, `ai_longevity`, `ai_sillage`, `ai_best_for`, `ai_sensitivity_safe`) VALUES
(127, 'j-adore-dior', 'unisex', 'both', 0, 0, 0, 1, 'J\'adore Dior', 'جادور', '', '', 'J\'adore by Dior is a floral-fruity fragrance for women. J\'adore was launched in 1999. The nose behind this fragrance is Calice Becker. Top notes are pear, melon, magnolia, peach, mandarin orange, and bergamot; middle notes are jasmine, lily-of-the-valley, tuberose, freesia, rose, orchid, violet, and plum; base notes are musk, vanilla, cedarwood, and blackberry. This fragrance won the FiFi Award for Best National Advertising Campaign/TV in 2007 .', 'J\'adore Dior عطر زهري - فواكه للنساء . J\'adore صدر عام 1999. Calice Becker قام بتوقيع هذا العطر. إفتتاحية العطر الكمثري, شمام, الماغنوليا, الخوخ, الماندرين (اليوسفي) و البرغموت; قلب العطر الياسمين, زنابق الوادي, مسك الروم, الفريزيا, الورد, الأوركيد, البنفسج و البرقوق; قاعدة العطر تتكون من المسك, الفانيليا, خشب الأرز و التوت الأسود. هذاالعطر فائز بجائزة FiFi Award Best National Advertising Campaign / TV 2007.', 'img_6a3aced2a73e20.17004908.webp', 0, '2026-06-23 18:22:22', '2026-06-23 18:22:22', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(128, 'alf-lail-o-lail', 'men', 'both', 0, 0, 0, 1, 'Alf Lail o Lail', 'ألف ليلة وليلة', '', '', 'Alf Lail o Lail by Ajmal is a floral-woody-musk fragrance for women. It was launched in the 2000s. The nose behind this fragrance is Nazir Ajmal. Fragrance notes include woods, musk, spices, and florals.', 'Alf Lail o Lail Ajmal عطر زهري - خشبي - مسك للنساء . Alf Lail o Lail صدر خلال 2000\'s. Nazir Ajmal قام بتوقيع هذا العطر. معلومات عن العطر الأخشاب, المسك, التوابل و النوتات الزهرية.', 'img_6a4b1190e6fa85.44783120.webp', 0, '2026-07-06 02:23:49', '2026-07-06 02:23:49', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(129, 'black-aoud-montale', 'men', 'both', 0, 0, 1, 1, 'Black Aoud Montale', 'بلاك عود', '', '', 'Black Aoud by Montale is a woody-floral-musk fragrance for men. Black Aoud was launched in 2006. The nose behind this fragrance is Pierre Montale. Fragrance notes include rose, oud, patchouli, musk, French labdanum, and mandarin orange.', 'Black Aoud Montale عطر خشبي - زهري - مسك للرجال . Black Aoud صدر عام 2006. Pierre Montale قام بتوقيع هذا العطر. معلومات عن العطر الورد, العود, الباتشولي, المسك, اللابدانوم الفرنسي و الماندرين (اليوسفي).', 'img_6a4b120c619690.16448735.webp', 0, '2026-07-06 02:25:57', '2026-07-10 01:52:56', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(130, 'shuhrah-rasasi', 'men', 'both', 0, 0, 0, 1, 'Shuhrah Rasasi', 'شهره', '', '', 'Shuhrah Pour Homme by Rasasi is a woody-aromatic fragrance for men. The top notes are tomato leaf, rose and freesia; the middle notes are rose, sandalwood, cedarwood and jasmine; the base notes are leather, oud, musk, oakmoss and amber.', 'Shuhrah Pour Homme Rasasi عطر خشبي - أروماتك للرجال . إفتتاحية العطر أوراق الطماطم, الورد و الفريزيا; قلب العطر الورد, خشب الصندل, خشب الأرز و الياسمين; قاعدة العطر تتكون من الجلود, العود, المسك, طحلب البلوط (طحلب السنديان) و العنبر.', 'img_6a4b12a03e36d0.72035137.webp', 0, '2026-07-06 02:27:53', '2026-07-06 02:27:53', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(131, 'oud-for-greatness', 'niche-perfumes', 'both', 0, 0, 0, 1, 'Oud for Greatness', 'عود فور جرتنس', '', '', 'Oud for Greatness by Initio Parfums Prives is a unisex fragrance. Oud for Greatness was launched in 2018. The top notes are saffron, nutmeg, and lavender; the heart note is oud; and the base notes are patchouli and musk.', 'Oud for Greatness Initio Parfums Prives عطر للجنسين. Oud for Greatness صدر عام 2018. إفتتاحية العطر الزعفران, جوزه الطيب و الخزامي; قلب العطر العود; قاعدة العطر تتكون من الباتشولي و المسك.', 'img_6a4b135e37f189.00261519.webp', 0, '2026-07-06 02:33:10', '2026-07-06 02:33:10', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(132, 'taraf', 'niche-perfumes', 'both', 0, 0, 1, 1, 'Taraf', 'ترف', '', '', 'Taraf Arabian Oud is a unisex fragrance. Taraf was launched in 2023. The top notes are sage and bergamot; the middle notes are Bulgarian rose and rosemary; the base notes are amber, leather and cedarwood.', 'Taraf Arabian Oud عطر للجنسين. Taraf صدر عام 2023. إفتتاحية العطر المريمية و البرغموت; قلب العطر الورد البلغاري و إكليل الجبل; قاعدة العطر تتكون من العنبر, الجلود و خشب الأرز.', 'img_6a4b14da998889.00714163.webp', 0, '2026-07-06 02:37:38', '2026-07-21 13:20:04', '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(133, 'madawi', 'men', 'both', 0, 0, 0, 1, 'Madawi', 'مضاوي', '', '', 'Madawi Arabian Oud is a fragrance for women. Madawi was launched in 2017. Madawi was created by Claire Liégent and Dominique Ropion. The top notes are peach and apple blossom; the middle note is pineapple blossom; and the base notes are wild rose, musk, and patchouli.', 'Madawi Arabian Oud عطر للنساء . Madawi صدر عام 2017. Madawi من توقيع Claire Liégent و Dominique Ropion. إفتتاحية العطر الخوخ و زهر التفاح; قلب العطر زهر الأناناس; قاعدة العطر تتكون من الورد البري, المسك و الباتشولي.', 'img_6a4b15b554e257.61056522.webp', 0, '2026-07-06 02:41:30', '2026-07-06 02:41:30', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(134, 'madawi-gold', 'men', 'both', 0, 0, 0, 1, 'Madawi Gold', 'مضاوي جولد', '', '', 'Madawi Gold Edition Arabian Oud is an oriental-floral fragrance for both men and women. Madawi Gold Edition was launched in 2024. The top notes are fruity and cardamom; the middle notes are tonka bean, pineapple blossom, and jasmine; and the base notes are vanilla and patchouli.', 'Madawi Gold Edition Arabian Oud عطر شرقي - زهري للجنسين. Madawi Gold Edition صدر عام 2024. إفتتاحية العطر نوتات الفواكه و الهيل; قلب العطر حبوب التونكا, زهر الأناناس و الياسمين; قاعدة العطر تتكون من الفانيليا و الباتشولي.', 'img_6a4b163723be26.59594425.webp', 0, '2026-07-06 02:43:17', '2026-07-06 02:43:17', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(135, 'kalemat', 'men', 'both', 0, 0, 0, 1, 'Kalemat', 'كلمات', '', '', 'Kalemat Arabian Oud is an oriental fragrance for both men and women. The top notes are blueberry and anise; the heart notes are cashmere wood, floral notes and rosemary; the base notes are amber, honey and musk.', 'Kalemat Arabian Oud عطر شرقي للجنسين. إفتتاحية العطر التوت الأزرق و الينسون; قلب العطر أخشاب الكشمير, النوتات الزهرية و إكليل الجبل; قاعدة العطر تتكون من العنبر, العسل و المسك.', 'img_6a4b169dbac465.06445893.webp', 0, '2026-07-06 02:44:57', '2026-07-06 02:44:57', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(136, 'oud-bouquet', 'niche-perfumes', 'both', 0, 0, 3, 1, 'Oud Bouquet', 'عود بوكيه', '', '', 'by Lancôme is an oriental-woody fragrance for both men and women. Oud Bouquet was launched in 2016. The nose behind this fragrance is Fabrice Pellegrin. Fragrance notes include almond, oud, vanilla, rose, guaiac wood, and copa balsam.', 'Oud Bouquet Lancôme عطر شرقي - خشبي للجنسين. Oud Bouquet صدر عام 2016. Fabrice Pellegrin قام بتوقيع هذا العطر. معلومات عن العطر حلوي اللوز, العود, الفانيليا, الورد, أخشاب الغاياك و بلسم كوباهو.', 'img_6a4caba0a93c81.84855264.webp', 0, '2026-07-07 07:33:23', '2026-08-05 08:41:22', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(137, 'wisal', 'men', 'both', 0, 0, 1, 1, 'Wisal', 'وصال', '', '', 'Wisal Ajmal is a floral fragrance for women. Wisal was launched in 2010. Fragrance notes include rose, spices, musk, floral notes, and sandalwoo', 'Wisal Ajmal عطر زهري للنساء . Wisal صدر عام 2010. معلومات عن العطر الورد, التوابل, المسك, النوتات الزهرية و خشب الصندل..', 'img_6a4cac4a1adac9.98036361.webp', 0, '2026-07-07 07:37:15', '2026-08-05 09:16:21', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(141, 'test', 'brands', 'both', 1, 0, 21, 1, 'test', 'تجربه', '', '', 'Premium fragrance.', 'عطر فاخر ومميز.', 'default', 0, '2026-07-31 05:38:52', '2026-08-07 05:30:46', '', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `category_slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_slug`) VALUES
(11, 'men'),
(11, 'niche-perfumes'),
(12, 'men'),
(12, 'niche-perfumes'),
(13, 'men'),
(13, 'niche-perfumes'),
(14, 'men'),
(14, 'niche-perfumes'),
(15, 'men'),
(15, 'niche-perfumes'),
(16, 'men'),
(16, 'niche-perfumes'),
(17, 'men'),
(17, 'niche-perfumes'),
(18, 'men'),
(18, 'niche-perfumes'),
(19, 'men'),
(19, 'niche-perfumes'),
(20, 'men'),
(20, 'niche-perfumes'),
(21, 'men'),
(21, 'niche-perfumes'),
(22, 'men'),
(22, 'niche-perfumes'),
(23, 'men'),
(23, 'niche-perfumes'),
(24, 'men'),
(24, 'niche-perfumes'),
(25, 'men'),
(25, 'niche-perfumes'),
(26, 'men'),
(26, 'niche-perfumes'),
(27, 'men'),
(27, 'niche-perfumes'),
(28, 'men'),
(28, 'niche-perfumes'),
(29, 'men'),
(29, 'niche-perfumes'),
(30, 'men'),
(30, 'niche-perfumes'),
(31, 'men'),
(31, 'niche-perfumes'),
(32, 'men'),
(32, 'niche-perfumes'),
(33, 'men'),
(33, 'niche-perfumes'),
(34, 'men'),
(34, 'niche-perfumes'),
(35, 'men'),
(35, 'niche-perfumes'),
(36, 'men'),
(36, 'niche-perfumes'),
(37, 'men'),
(37, 'niche-perfumes'),
(38, 'men'),
(38, 'niche-perfumes'),
(39, 'men'),
(39, 'niche-perfumes'),
(40, 'men'),
(40, 'niche-perfumes'),
(41, 'men'),
(41, 'niche-perfumes'),
(42, 'men'),
(42, 'niche-perfumes'),
(43, 'men'),
(43, 'niche-perfumes'),
(44, 'men'),
(44, 'niche-perfumes'),
(45, 'men'),
(45, 'niche-perfumes'),
(46, 'men'),
(46, 'niche-perfumes'),
(47, 'men'),
(47, 'niche-perfumes'),
(48, 'men'),
(48, 'niche-perfumes'),
(49, 'men'),
(49, 'niche-perfumes'),
(50, 'men'),
(50, 'niche-perfumes'),
(51, 'men'),
(51, 'niche-perfumes'),
(52, 'men'),
(53, 'men'),
(54, 'designer-perfumes'),
(54, 'men'),
(55, 'designer-perfumes'),
(55, 'men'),
(56, 'designer-perfumes'),
(56, 'men'),
(57, 'designer-perfumes'),
(57, 'men'),
(58, 'designer-perfumes'),
(58, 'men'),
(59, 'designer-perfumes'),
(59, 'men'),
(60, 'designer-perfumes'),
(60, 'men'),
(61, 'designer-perfumes'),
(61, 'men'),
(62, 'designer-perfumes'),
(62, 'men'),
(64, 'men'),
(65, 'men'),
(66, 'designer-perfumes'),
(66, 'men'),
(67, 'designer-perfumes'),
(67, 'men'),
(68, 'designer-perfumes'),
(68, 'men'),
(69, 'designer-perfumes'),
(69, 'men'),
(70, 'men'),
(71, 'designer-perfumes'),
(71, 'men'),
(72, 'designer-perfumes'),
(72, 'men'),
(73, 'designer-perfumes'),
(73, 'men'),
(74, 'designer-perfumes'),
(74, 'men'),
(75, 'men'),
(76, 'designer-perfumes'),
(76, 'men'),
(77, 'designer-perfumes'),
(77, 'men'),
(78, 'designer-perfumes'),
(78, 'men'),
(79, 'designer-perfumes'),
(79, 'men'),
(80, 'designer-perfumes'),
(80, 'men'),
(81, 'designer-perfumes'),
(81, 'men'),
(82, 'designer-perfumes'),
(82, 'men'),
(83, 'men'),
(84, 'designer-perfumes'),
(84, 'men'),
(85, 'designer-perfumes'),
(85, 'men'),
(86, 'men'),
(87, 'designer-perfumes'),
(87, 'men'),
(88, 'designer-perfumes'),
(88, 'men'),
(89, 'designer-perfumes'),
(89, 'men'),
(90, 'designer-perfumes'),
(90, 'men'),
(91, 'designer-perfumes'),
(91, 'men'),
(92, 'designer-perfumes'),
(92, 'men'),
(93, 'designer-perfumes'),
(93, 'men'),
(94, 'designer-perfumes'),
(94, 'men'),
(95, 'designer-perfumes'),
(95, 'men'),
(96, 'designer-perfumes'),
(96, 'men'),
(97, 'designer-perfumes'),
(97, 'men'),
(98, 'designer-perfumes'),
(98, 'men'),
(99, 'designer-perfumes'),
(99, 'men'),
(100, 'designer-perfumes'),
(100, 'men'),
(101, 'designer-perfumes'),
(101, 'men'),
(102, 'designer-perfumes'),
(102, 'men'),
(103, 'designer-perfumes'),
(103, 'men'),
(104, 'designer-perfumes'),
(104, 'men'),
(105, 'designer-perfumes'),
(105, 'men'),
(106, 'designer-perfumes'),
(106, 'women'),
(107, 'designer-perfumes'),
(107, 'women'),
(108, 'unisex'),
(109, 'designer-perfumes'),
(109, 'women'),
(110, 'designer-perfumes'),
(110, 'women'),
(111, 'designer-perfumes'),
(111, 'women'),
(112, 'designer-perfumes'),
(112, 'women'),
(113, 'designer-perfumes'),
(113, 'women'),
(114, 'designer-perfumes'),
(114, 'women'),
(115, 'designer-perfumes'),
(115, 'women'),
(116, 'designer-perfumes'),
(116, 'women'),
(117, 'designer-perfumes'),
(117, 'women'),
(118, 'designer-perfumes'),
(118, 'women'),
(119, 'designer-perfumes'),
(119, 'women'),
(120, 'unisex'),
(121, 'designer-perfumes'),
(121, 'women'),
(122, 'designer-perfumes'),
(122, 'women'),
(123, 'designer-perfumes'),
(123, 'women'),
(124, 'unisex'),
(125, 'designer-perfumes'),
(125, 'women'),
(126, 'designer-perfumes'),
(126, 'women'),
(127, 'unisex'),
(128, 'men'),
(129, 'men'),
(130, 'men'),
(131, 'men'),
(131, 'niche-perfumes'),
(132, 'men'),
(132, 'niche-perfumes'),
(133, 'men'),
(134, 'men'),
(135, 'men'),
(136, 'men'),
(136, 'niche-perfumes'),
(137, 'men'),
(141, 'brands');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_key` varchar(128) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_key`, `sort_order`) VALUES
(68, 53, 'img_6a356a24703eb6.45723576.webp', 0),
(80, 65, 'img_6a3571e22c5610.46130175.webp', 0),
(85, 70, 'img_6a3574205ca376.00725777.webp', 0),
(91, 72, 'img_6a357c641db542.13599957.webp', 0),
(92, 71, 'img_6a357bd7c7ac69.93059405.webp', 0),
(93, 69, 'img_6a3573c918b8a4.86949798.webp', 0),
(94, 54, 'img_6a356a8ca5e964.31794312.webp', 0),
(95, 55, 'img_6a356ae82e3832.00512715.webp', 0),
(96, 56, 'img_6a356b44edf8d5.77949771.webp', 0),
(97, 57, 'img_6a356b9ea641c7.33767511.webp', 0),
(98, 58, 'img_6a356bf72d3449.84549274.webp', 0),
(99, 60, 'img_6a356cd4cc51c6.10523331.webp', 0),
(100, 59, 'img_6a356c6adc11b4.76568082.webp', 0),
(101, 61, 'img_6a356d73c24377.42136555.webp', 0),
(102, 62, 'img_6a356db1659f40.12875719.webp', 0),
(105, 64, 'img_6a35718cd32737.93782181.webp', 0),
(106, 66, 'img_6a357276858b40.39866423.webp', 0),
(107, 67, 'img_6a3572e5edb975.19164929.webp', 0),
(108, 68, 'img_6a35735b96a576.73769995.webp', 0),
(111, 13, 'img_6a34393f79c5a0.57904810.webp', 0),
(112, 14, 'img_6a3439c2786a28.59992722.webp', 0),
(113, 15, 'img_6a343a63d97d83.38808985.webp', 0),
(114, 16, 'img_6a343b5c2fedf1.69423871.webp', 0),
(117, 19, 'img_6a343d26bb8742.47371903.webp', 0),
(118, 30, 'img_6a34ae3af039c3.31668875.webp', 0),
(119, 29, 'img_6a34adf498e8b6.37168628.webp', 0),
(121, 27, 'img_6a344331229ee0.98771501.webp', 0),
(122, 26, 'img_6a3441dd0383b8.65884518.webp', 0),
(123, 25, 'img_6a34415b349292.33727257.webp', 0),
(124, 24, 'img_6a3440bed77508.30759901.webp', 0),
(126, 22, 'img_6a343f449ac1a6.44730526.webp', 0),
(127, 21, 'img_6a343ea2c72156.17437254.webp', 0),
(128, 20, 'img_6a343da7096157.46057706.webp', 0),
(129, 31, 'img_6a34aeeb067882.81644759.webp', 0),
(130, 50, 'img_6a34bd48b30198.26222506.webp', 0),
(131, 33, 'img_6a34aff2bbe520.02033150.webp', 0),
(132, 32, 'img_6a34af6e170860.15417470.webp', 0),
(133, 34, 'img_6a34b132d2ca30.32501283.webp', 0),
(134, 35, 'img_6a34b1a0557a62.22988318.webp', 0),
(135, 36, 'img_6a34b22813eed3.03447190.webp', 0),
(136, 37, 'img_6a34b2d5908631.39197613.webp', 0),
(137, 38, 'img_6a34b33f211489.07100391.webp', 0),
(138, 49, 'img_6a34bc7aa36e79.65943657.webp', 0),
(139, 48, 'img_6a34bbaea7f654.00059645.webp', 0),
(141, 46, 'img_6a34ba616ceea1.26941393.webp', 0),
(142, 45, 'img_6a34b9abc2ca54.63014428.webp', 0),
(143, 44, 'img_6a34b86373b728.33275574.webp', 0),
(144, 43, 'img_6a34b7ccc1f357.03050052.webp', 0),
(146, 41, 'img_6a34b68fce4a71.69933140.webp', 0),
(147, 40, 'img_6a34b4bcd62ec2.84168969.webp', 0),
(148, 39, 'img_6a34b430219950.16273757.webp', 0),
(152, 52, 'img_6a356925398d98.22655835.webp', 0),
(153, 73, 'img_6a35cfacdab008.51567944.webp', 0),
(154, 74, 'img_6a35cffc4354f3.36536098.webp', 0),
(155, 75, 'img_6a35d0806045d4.84659979.webp', 0),
(156, 76, 'img_6a35d1025a30e6.95949481.webp', 0),
(157, 77, 'img_6a35d147894381.47872178.webp', 0),
(158, 78, 'img_6a35d1f357d3c2.90398339.webp', 0),
(159, 79, 'img_6a35d24dcaeb37.97380080.webp', 0),
(160, 80, 'img_6a35d2e4ea43c5.98194487.webp', 0),
(161, 81, 'img_6a35d346ade4a5.91904406.webp', 0),
(162, 82, 'img_6a35d3a7790c93.57065077.webp', 0),
(163, 83, 'img_6a35d438eb0755.79226993.webp', 0),
(164, 84, 'img_6a35d4d16392c0.10786032.webp', 0),
(165, 85, 'img_6a35d5c3559690.31042675.webp', 0),
(166, 86, 'img_6a35d657131267.78030004.webp', 0),
(167, 87, 'img_6a35d71742a0d0.22759666.webp', 0),
(168, 88, 'img_6a35d7e1d5a3a1.39117509.webp', 0),
(169, 89, 'img_6a35d830bcae74.02081164.webp', 0),
(170, 90, 'img_6a35d8d8ded979.31463941.webp', 0),
(171, 91, 'img_6a35d90accc025.55117121.webp', 0),
(172, 92, 'img_6a35d95edf0573.57679610.webp', 0),
(173, 93, 'img_6a35d9d8a70bb8.57770077.webp', 0),
(174, 94, 'img_6a35da66ca1949.36697257.webp', 0),
(175, 95, 'img_6a35dad0af1256.51512862.webp', 0),
(176, 96, 'img_6a35db428eb6a3.18850400.webp', 0),
(177, 97, 'img_6a35dbc76b5013.63804177.webp', 0),
(178, 98, 'img_6a35dc36bd0cb8.85448004.webp', 0),
(179, 99, 'img_6a35dcdde0da78.45635492.webp', 0),
(180, 100, 'img_6a37cb35c62779.08917752.webp', 0),
(181, 101, 'img_6a37cb97c485d9.35024666.webp', 0),
(182, 102, 'img_6a37cc19a485f2.03857181.webp', 0),
(183, 103, 'img_6a37ce48341929.68254363.webp', 0),
(184, 104, 'img_6a37ceede78d61.80295838.webp', 0),
(185, 105, 'img_6a37d01b137b06.19384780.webp', 0),
(187, 106, 'img_6a3a8eeb13dd48.33969968.webp', 0),
(188, 107, 'img_6a3a8fb6a97458.77985990.webp', 0),
(189, 108, 'img_6a3a9057e52dc2.13094765.webp', 0),
(190, 109, 'img_6a3a90ec8841b9.97357894.webp', 0),
(191, 110, 'img_6a3a9160f3d175.80436102.webp', 0),
(192, 111, 'img_6a3a91d728f618.90916289.webp', 0),
(193, 112, 'img_6a3a921f0821b3.42309069.webp', 0),
(194, 113, 'img_6a3a92d30ab738.53314187.webp', 0),
(195, 114, 'img_6a3a94622e9c56.44246553.webp', 0),
(196, 115, 'img_6a3a9543dd1db1.83257930.webp', 0),
(197, 116, 'img_6a3a96edf2d486.47002890.webp', 0),
(198, 117, 'img_6a3a9770678180.92478739.webp', 0),
(199, 118, 'img_6a3a9832046a38.37839664.webp', 0),
(200, 119, 'img_6a3a99d04db482.13999561.webp', 0),
(201, 120, 'img_6a3a99ff51c3d5.58275149.webp', 0),
(202, 121, 'img_6a3a9b1ea52991.39201076.webp', 0),
(203, 122, 'img_6a3a9b78118216.48990972.webp', 0),
(204, 123, 'img_6a3acc7fec60f2.71536313.webp', 0),
(205, 124, 'img_6a3accf7804266.67880723.webp', 0),
(207, 126, 'img_6a3ace4166b511.75071335.webp', 0),
(208, 125, 'img_6a3ace64799841.10037712.webp', 0),
(209, 127, 'img_6a3aced2a73e20.17004908.webp', 0),
(211, 17, 'img_6a343bd670d739.06959762.webp', 0),
(212, 128, 'img_6a4b1190e6fa85.44783120.webp', 0),
(213, 129, 'img_6a4b120c619690.16448735.webp', 0),
(214, 130, 'img_6a4b12a03e36d0.72035137.webp', 0),
(215, 131, 'img_6a4b135e37f189.00261519.webp', 0),
(217, 133, 'img_6a4b15b554e257.61056522.webp', 0),
(218, 134, 'img_6a4b163723be26.59594425.webp', 0),
(219, 135, 'img_6a4b169dbac465.06445893.webp', 0),
(220, 136, 'img_6a4caba0a93c81.84855264.webp', 0),
(221, 137, 'img_6a4cac4a1adac9.98036361.webp', 0),
(222, 47, 'img_6a34baa6baeec6.76876800.webp', 0),
(223, 18, 'img_6a343c93d086c1.98746795.webp', 0),
(224, 132, 'img_6a4b14da998889.00714163.webp', 0),
(225, 11, 'img_6a3436d2904965.04611872.webp', 0),
(226, 12, 'img_6a3437f421d023.92384874.webp', 0),
(227, 23, 'img_6a343fded54ef1.43098027.webp', 0),
(228, 42, 'img_6a34b7442593f9.87821830.webp', 0),
(229, 28, 'img_6a34ad76439d94.69198031.webp', 0),
(230, 51, 'img_6a34be80a56704.59165318.webp', 0),
(234, 141, 'default', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `review_text` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `label_en` varchar(255) NOT NULL,
  `label_ar` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `label_en`, `label_ar`, `price`, `compare_at_price`, `sort_order`, `stock`) VALUES
(116, 53, '100 ml', '110ml', 850.00, NULL, 0, 104),
(117, 53, '55ml', '55ml', 575.00, NULL, 1, 104),
(140, 65, '100 ml', '110ml', 850.00, NULL, 0, 100),
(141, 65, '55ml', '55ml', 575.00, NULL, 1, 100),
(150, 70, '100 ml', '110ml', 850.00, NULL, 0, 100),
(151, 70, '55ml', '55ml', 575.00, NULL, 1, 100),
(162, 72, '100 ml', '110ml', 775.00, NULL, 0, 108),
(163, 72, '55ml', '55ml', 525.00, NULL, 1, 108),
(164, 71, '100 ml', '110ml', 775.00, NULL, 0, 109),
(165, 71, '55ml', '55ml', 525.00, NULL, 1, 109),
(166, 69, '100 ml', '110ml', 775.00, NULL, 0, 108),
(167, 69, '55ml', '55ml', 525.00, NULL, 1, 108),
(168, 54, '100 ml', '110ml', 775.00, NULL, 0, 104),
(169, 54, '55ml', '55ml', 525.00, NULL, 1, 102),
(170, 55, '100 ml', '110ml', 775.00, NULL, 0, 108),
(171, 55, '55ml', '55ml', 525.00, NULL, 1, 108),
(172, 56, '100 ml', '110ml', 775.00, NULL, 0, 108),
(173, 56, '55ml', '55ml', 525.00, NULL, 1, 108),
(174, 57, '100 ml', '110ml', 775.00, NULL, 0, 108),
(175, 57, '55ml', '55ml', 525.00, NULL, 1, 108),
(176, 58, '100 ml', '110ml', 775.00, NULL, 0, 108),
(177, 58, '55ml', '55ml', 525.00, NULL, 1, 108),
(178, 60, '100 ml', '110ml', 775.00, NULL, 0, 109),
(179, 60, '55ml', '55ml', 525.00, NULL, 1, 109),
(180, 59, '100 ml', '110ml', 775.00, NULL, 0, 109),
(181, 59, '55ml', '55ml', 525.00, NULL, 1, 109),
(182, 61, '100 ml', '110ml', 775.00, NULL, 0, 109),
(183, 61, '55ml', '55ml', 525.00, NULL, 1, 109),
(184, 62, '100 ml', '110ml', 775.00, NULL, 0, 109),
(185, 62, '55ml', '55ml', 525.00, NULL, 1, 109),
(190, 64, '100 ml', '110ml', 850.00, NULL, 0, 140),
(191, 64, '55ml', '55ml', 575.00, NULL, 1, 103),
(192, 66, '100 ml', '110ml', 775.00, NULL, 0, 199),
(193, 66, '55ml', '55ml', 525.00, NULL, 1, 109),
(194, 67, '100 ml', '110ml', 775.00, NULL, 0, 108),
(195, 67, '55ml', '55ml', 525.00, NULL, 1, 109),
(196, 68, '100 ml', '110ml', 775.00, NULL, 0, 108),
(197, 68, '55ml', '55ml', 525.00, NULL, 1, 108),
(202, 13, '100 ml', '110ml', 1250.00, NULL, 0, 99),
(203, 13, '55ml', '55ml', 850.00, NULL, 1, 10),
(204, 14, '100 ml', '110ml', 1250.00, NULL, 0, 101),
(205, 14, '55ml', '55ml', 850.00, NULL, 1, 10),
(206, 15, '100 ml', '110ml', 1500.00, NULL, 0, 101),
(207, 15, '55ml', '55ml', 900.00, NULL, 1, 10),
(208, 16, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(209, 16, '55ml', '55ml', 850.00, NULL, 1, 10),
(214, 19, '100 ml', '110ml', 1800.00, NULL, 0, 102),
(215, 19, '55ml', '55ml', 1100.00, NULL, 1, 103),
(216, 30, '100 ml', '110ml', 1500.00, NULL, 0, 101),
(217, 30, '55ml', '55ml', 900.00, NULL, 1, 108),
(218, 29, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(219, 29, '55ml', '55ml', 850.00, NULL, 1, 102),
(222, 27, '100 ml', '110ml', 1800.00, NULL, 0, 102),
(223, 27, '55ml', '55ml', 1100.00, NULL, 1, 10),
(224, 26, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(225, 26, '55ml', '55ml', 850.00, NULL, 1, 10),
(226, 25, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(227, 25, '55ml', '55ml', 850.00, NULL, 1, 102),
(228, 24, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(229, 24, '55ml', '55ml', 900.00, NULL, 1, 102),
(232, 22, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(233, 22, '55ml', '55ml', 850.00, NULL, 1, 10),
(234, 21, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(235, 21, '55ml', '55ml', 850.00, NULL, 1, 10),
(236, 20, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(237, 20, '55ml', '55ml', 850.00, NULL, 1, 102),
(238, 31, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(239, 31, '55ml', '55ml', 850.00, NULL, 1, 109),
(240, 50, '100 ml', '110ml', 1250.00, NULL, 0, 108),
(241, 50, '55ml', '55ml', 850.00, NULL, 1, 107),
(242, 33, '100 ml', '110ml', 1800.00, NULL, 0, 100),
(243, 33, '55ml', '55ml', 1100.00, NULL, 1, 10),
(244, 32, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(245, 32, '55ml', '55ml', 900.00, NULL, 1, 100),
(246, 34, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(247, 34, '55ml', '55ml', 900.00, NULL, 1, 100),
(248, 35, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(249, 35, '55ml', '55ml', 900.00, NULL, 1, 100),
(250, 36, '100 ml', '110ml', 1250.00, NULL, 0, 100),
(251, 36, '55ml', '55ml', 850.00, NULL, 1, 100),
(252, 37, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(253, 37, '55ml', '55ml', 900.00, NULL, 1, 100),
(254, 38, '100 ml', '110ml', 1500.00, NULL, 0, 103),
(255, 38, '55ml', '55ml', 900.00, NULL, 1, 103),
(256, 49, '100 ml', '110ml', 1500.00, NULL, 0, 107),
(257, 49, '55ml', '55ml', 900.00, NULL, 1, 108),
(258, 48, '100 ml', '110ml', 1800.00, NULL, 0, 109),
(259, 48, '55ml', '55ml', 1100.00, NULL, 1, 102),
(262, 46, '100 ml', '110ml', 1500.00, NULL, 0, 109),
(263, 46, '55ml', '55ml', 900.00, NULL, 1, 108),
(264, 45, '100 ml', '110ml', 1250.00, NULL, 0, 109),
(265, 45, '55ml', '55ml', 850.00, NULL, 1, 102),
(266, 44, '100 ml', '110ml', 1800.00, NULL, 0, 109),
(267, 44, '55ml', '55ml', 1100.00, NULL, 1, 108),
(268, 43, '100 ml', '110ml', 1000.00, NULL, 0, 109),
(269, 43, '55ml', '55ml', 750.00, NULL, 1, 109),
(272, 41, '100 ml', '110ml', 1250.00, NULL, 0, 109),
(273, 41, '55ml', '55ml', 850.00, NULL, 1, 102),
(274, 40, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(275, 40, '55ml', '55ml', 900.00, NULL, 1, 109),
(276, 39, '100 ml', '110ml', 1500.00, NULL, 0, 100),
(277, 39, '55ml', '55ml', 900.00, NULL, 1, 109),
(284, 52, '100 ml', '110ml', 850.00, NULL, 0, 108),
(285, 52, '55ml', '55ml', 575.00, NULL, 1, 102),
(286, 73, '100 ml', '110ml', 775.00, NULL, 0, 108),
(287, 73, '55ml', '55ml', 525.00, NULL, 1, 108),
(288, 74, '100 ml', '110ml', 775.00, NULL, 0, 109),
(289, 74, '55ml', '55ml', 525.00, NULL, 1, 109),
(290, 75, '100 ml', '110ml', 850.00, NULL, 0, 109),
(291, 75, '55ml', '55ml', 575.00, NULL, 1, 107),
(292, 76, '100 ml', '110ml', 775.00, NULL, 0, 100),
(293, 76, '55ml', '55ml', 525.00, NULL, 1, 100),
(294, 77, '100 ml', '110ml', 775.00, NULL, 0, 100),
(295, 77, '55ml', '55ml', 525.00, NULL, 1, 100),
(296, 78, '100 ml', '110ml', 775.00, NULL, 0, 98),
(297, 78, '55ml', '55ml', 525.00, NULL, 1, 100),
(298, 79, '100 ml', '110ml', 775.00, NULL, 0, 100),
(299, 79, '55ml', '55ml', 525.00, NULL, 1, 100),
(300, 80, '100 ml', '110ml', 775.00, NULL, 0, 100),
(301, 80, '55ml', '55ml', 525.00, NULL, 1, 100),
(302, 81, '100 ml', '110ml', 775.00, NULL, 0, 100),
(303, 81, '55ml', '55ml', 525.00, NULL, 1, 100),
(304, 82, '100 ml', '110ml', 775.00, NULL, 0, 100),
(305, 82, '55ml', '55ml', 525.00, NULL, 1, 100),
(306, 83, '100 ml', '110ml', 850.00, NULL, 0, 100),
(307, 83, '55ml', '55ml', 575.00, NULL, 1, 100),
(308, 84, '100 ml', '110ml', 775.00, NULL, 0, 100),
(309, 84, '55ml', '55ml', 525.00, NULL, 1, 100),
(310, 85, '100 ml', '110ml', 775.00, NULL, 0, 100),
(311, 85, '55ml', '55ml', 525.00, NULL, 1, 100),
(312, 86, '100 ml', '110ml', 850.00, NULL, 0, 100),
(313, 86, '55ml', '55ml', 575.00, NULL, 1, 109),
(314, 87, '100 ml', '110ml', 775.00, NULL, 0, 100),
(315, 87, '55ml', '55ml', 525.00, NULL, 1, 100),
(316, 88, '100 ml', '110ml', 775.00, NULL, 0, 100),
(317, 88, '55ml', '55ml', 525.00, NULL, 1, 100),
(318, 89, '100 ml', '110ml', 775.00, NULL, 0, 100),
(319, 89, '55ml', '55ml', 525.00, NULL, 1, 100),
(320, 90, '100 ml', '110ml', 775.00, NULL, 0, 100),
(321, 90, '55ml', '55ml', 525.00, NULL, 1, 100),
(322, 91, '100 ml', '110ml', 775.00, NULL, 0, 100),
(323, 91, '55ml', '55ml', 525.00, NULL, 1, 100),
(324, 92, '100 ml', '110ml', 775.00, NULL, 0, 100),
(325, 92, '55ml', '55ml', 525.00, NULL, 1, 100),
(326, 93, '100 ml', '110ml', 775.00, NULL, 0, 108),
(327, 93, '55ml', '55ml', 525.00, NULL, 1, 108),
(328, 94, '100 ml', '110ml', 775.00, NULL, 0, 100),
(329, 94, '55ml', '55ml', 525.00, NULL, 1, 100),
(330, 95, '100 ml', '110ml', 775.00, NULL, 0, 100),
(331, 95, '55ml', '55ml', 525.00, NULL, 1, 100),
(332, 96, '100 ml', '110ml', 775.00, NULL, 0, 108),
(333, 96, '55ml', '55ml', 525.00, NULL, 1, 108),
(334, 97, '100 ml', '110ml', 775.00, NULL, 0, 100),
(335, 97, '55ml', '55ml', 525.00, NULL, 1, 100),
(336, 98, '100 ml', '110ml', 775.00, NULL, 0, 100),
(337, 98, '55ml', '55ml', 525.00, NULL, 1, 100),
(338, 99, '100 ml', '110ml', 775.00, NULL, 0, 100),
(339, 99, '55ml', '55ml', 525.00, NULL, 1, 100),
(340, 100, '100 ml', '110ml', 775.00, NULL, 0, 100),
(341, 100, '55ml', '55ml', 525.00, NULL, 1, 100),
(342, 101, '100 ml', '110ml', 775.00, NULL, 0, 109),
(343, 101, '55ml', '55ml', 525.00, NULL, 1, 109),
(344, 102, '100 ml', '110ml', 775.00, NULL, 0, 177),
(345, 102, '55ml', '55ml', 525.00, NULL, 1, 109),
(346, 103, '100 ml', '110ml', 850.00, NULL, 0, 100),
(347, 103, '55ml', '55ml', 575.00, NULL, 1, 100),
(348, 104, '100 ml', '110ml', 775.00, NULL, 0, 100),
(349, 104, '55ml', '55ml', 525.00, NULL, 1, 109),
(350, 105, '100 ml', '110ml', 775.00, NULL, 0, 109),
(351, 105, '55ml', '55ml', 525.00, NULL, 1, 109),
(354, 106, '100 ml', '110ml', 775.00, NULL, 0, 107),
(355, 106, '55ml', '55ml', 525.00, NULL, 1, 104),
(356, 107, '100 ml', '110ml', 775.00, NULL, 0, 100),
(357, 107, '55ml', '55ml', 525.00, NULL, 1, 101),
(358, 108, '100 ml', '110ml', 775.00, NULL, 0, 102),
(359, 108, '55ml', '55ml', 525.00, NULL, 1, 102),
(360, 109, '100 ml', '110ml', 775.00, NULL, 0, 102),
(361, 109, '55ml', '55ml', 525.00, NULL, 1, 102),
(362, 110, '100 ml', '110ml', 775.00, NULL, 0, 108),
(363, 110, '55ml', '55ml', 525.00, NULL, 1, 108),
(364, 111, '100 ml', '110ml', 775.00, NULL, 0, 107),
(365, 111, '55ml', '55ml', 525.00, NULL, 1, 108),
(366, 112, '100 ml', '110ml', 775.00, NULL, 0, 100),
(367, 112, '55ml', '55ml', 525.00, NULL, 1, 100),
(368, 113, '100 ml', '110ml', 775.00, NULL, 0, 102),
(369, 113, '55ml', '55ml', 525.00, NULL, 1, 102),
(370, 114, '100 ml', '110ml', 775.00, NULL, 0, 109),
(371, 114, '55ml', '55ml', 525.00, NULL, 1, 109),
(372, 115, '100 ml', '110ml', 775.00, NULL, 0, 103),
(373, 115, '55ml', '55ml', 525.00, NULL, 1, 102),
(374, 116, '100 ml', '110ml', 775.00, NULL, 0, 102),
(375, 116, '55ml', '55ml', 525.00, NULL, 1, 102),
(376, 117, '100 ml', '110ml', 775.00, NULL, 0, 100),
(377, 117, '55ml', '55ml', 525.00, NULL, 1, 108),
(378, 118, '100 ml', '110ml', 775.00, NULL, 0, 109),
(379, 118, '55ml', '55ml', 525.00, NULL, 1, 109),
(380, 119, '100 ml', '110ml', 775.00, NULL, 0, 108),
(381, 119, '55ml', '55ml', 525.00, NULL, 1, 108),
(382, 120, '100 ml', '110ml', 775.00, NULL, 0, 109),
(383, 120, '55ml', '55ml', 525.00, NULL, 1, 100),
(384, 121, '100 ml', '110ml', 775.00, NULL, 0, 108),
(385, 121, '55ml', '55ml', 525.00, NULL, 1, 108),
(386, 122, '100 ml', '110ml', 775.00, NULL, 0, 109),
(387, 122, '55ml', '55ml', 525.00, NULL, 1, 109),
(388, 123, '100 ml', '110ml', 775.00, NULL, 0, 109),
(389, 123, '55ml', '55ml', 525.00, NULL, 1, 108),
(390, 124, '100 ml', '110ml', 775.00, NULL, 0, 90),
(391, 124, '55ml', '55ml', 525.00, NULL, 1, 108),
(394, 126, '100 ml', '110ml', 775.00, NULL, 0, 100),
(395, 126, '55ml', '55ml', 525.00, NULL, 1, 109),
(396, 125, '100 ml', '110ml', 775.00, NULL, 0, 109),
(397, 125, '55ml', '55ml', 525.00, NULL, 1, 100),
(398, 127, '100 ml', '110ml', 775.00, NULL, 0, 100),
(399, 127, '55ml', '55ml', 525.00, NULL, 1, 100),
(402, 17, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(403, 17, '55ml', '55ml', 850.00, NULL, 1, 102),
(404, 128, '100 ml', '110ml', 850.00, NULL, 0, 102),
(405, 128, '55ml', '55ml', 575.00, NULL, 1, 103),
(406, 129, '100 ml', '110ml', 850.00, NULL, 0, 100),
(407, 129, '55ml', '55ml', 575.00, NULL, 1, 100),
(408, 130, '100 ml', '110ml', 850.00, NULL, 0, 100),
(409, 130, '55ml', '55ml', 575.00, NULL, 1, 100),
(410, 131, '100 ml', '110ml', 1500.00, NULL, 0, 200),
(411, 131, '55ml', '55ml', 900.00, NULL, 1, 107),
(414, 133, '100 ml', '110ml', 850.00, NULL, 0, 109),
(415, 133, '55ml', '55ml', 575.00, NULL, 1, 101),
(416, 134, '100 ml', '110ml', 850.00, NULL, 0, 109),
(417, 134, '55ml', '55ml', 575.00, NULL, 1, 100),
(418, 135, '100 ml', '110ml', 850.00, NULL, 0, 100),
(419, 135, '55ml', '55ml', 575.00, NULL, 1, 100),
(420, 136, '100 ml', '110ml', 1250.00, NULL, 0, 100),
(421, 136, '55ml', '55ml', 850.00, NULL, 1, 90),
(422, 137, '100 ml', '110ml', 850.00, NULL, 0, 180),
(423, 137, '55ml', '55ml', 575.00, NULL, 1, 102),
(424, 47, '100 ml', '110ml', 1500.00, NULL, 0, 109),
(425, 47, '55ml', '55ml', 900.00, NULL, 1, 108),
(426, 18, '100 ml', '110ml', 1800.00, NULL, 0, 102),
(427, 18, '55ml', '55ml', 1100.00, NULL, 1, 102),
(428, 132, '100 ml', '110ml', 850.00, NULL, 0, 100),
(429, 132, '55ml', '55ml', 575.00, NULL, 1, 100),
(430, 11, '100 ml', '110ml', 1250.00, NULL, 0, 96),
(431, 11, '55ml', '55ml', 850.00, NULL, 1, 99),
(432, 12, '100 ml', '110ml', 1250.00, NULL, 0, 100),
(433, 12, '55ml', '55ml', 850.00, NULL, 1, 100),
(434, 23, '100 ml', '110ml', 1250.00, NULL, 0, 102),
(435, 23, '55ml', '55ml', 850.00, NULL, 1, 102),
(436, 42, '100 ml', '110ml', 1250.00, NULL, 0, 109),
(437, 42, '55ml', '55ml', 850.00, NULL, 1, 109),
(438, 28, '100 ml', '110ml', 1500.00, NULL, 0, 102),
(439, 28, '55ml', '55ml', 900.00, NULL, 1, 102),
(440, 51, '100 ml', '110ml', 1250.00, NULL, 0, 106),
(441, 51, '55ml', '55ml', 850.00, NULL, 1, 108),
(445, 141, 'Original', 'الأصلي', 200.00, 350.00, 0, 27);

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(64) NOT NULL,
  `discount_percentage` int(11) NOT NULL DEFAULT 0,
  `usage_limit` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`id`, `code`, `discount_percentage`, `usage_limit`, `used_count`, `active`, `created_at`) VALUES
(4, 'ZEIN2026', 10, 10, 0, 1, '2026-06-25 21:26:29');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `permissions` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_superadmin`, `permissions`, `created_at`) VALUES
(1, 'مدير عام', 'صلاحيات كاملة - جميع الصفحات', 1, '', '2026-06-18 17:43:18'),
(2, 'مدير مبيعات', 'إدارة الطلبات والعملاء والتقارير', 0, 'orders,order_management,orders_export,clients,students,client_statement,clients_export,reports,sales_records,product_statistics,notifications', '2026-06-18 17:43:18'),
(3, 'مدير محتوى', 'إدارة المنتجات والعروض والماركات', 0, 'products,offers,brands,internal_products,reviews,categories,promo_codes', '2026-06-18 17:43:18'),
(4, 'خدمة عملاء', 'الرد على الرسائل ومتابعة الطلبات', 0, 'orders,order_management,messages,notifications,clients', '2026-06-18 17:43:18'),
(5, 'موظف شحن', 'إدارة الشحن والطلبات', 0, 'orders,shipping,notifications', '2026-06-18 17:43:18');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(64) NOT NULL,
  `setting_value_en` text DEFAULT NULL,
  `setting_value_ar` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value_en`, `setting_value_ar`, `created_at`, `updated_at`) VALUES
(1, 'announce_shipping', 'زين لعطور', 'زين لعطور', '2026-05-30 14:49:17', '2026-07-09 14:40:35'),
(4, 'hero_new_badge', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(5, 'hero_new_title_pre', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(6, 'hero_new_title_em', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(7, 'hero_new_sub', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(8, 'hero_new_cta', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(9, 'promo_free_shipping_tag', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(10, 'promo_free_shipping_title', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(11, 'promo_free_shipping_sub', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(12, 'promo_free_shipping_color', '#1e3a8a', '#1e3a8a', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(13, 'promo_discount_tag', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(14, 'promo_discount_title', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(15, 'promo_discount_sub', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(16, 'promo_discount_color', '#2b2118', '#2b2118', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(17, 'hero_image_1', 'hero_1_1782427185.jpg', 'hero_1_1782427185.jpg', '2026-05-30 15:54:13', '2026-06-25 22:39:45'),
(18, 'hero_image_2', 'hero_2_1782426231.jpeg', 'hero_2_1782426231.jpeg', '2026-05-30 15:54:13', '2026-06-25 22:23:51'),
(19, 'hero_image_3', 'hero_3_1782427185.jpg', 'hero_3_1782427185.jpg', '2026-05-30 15:54:13', '2026-06-25 22:39:45'),
(20, 'cat_women_icon', 'cat_women_1780153118.jpeg', 'cat_women_1780153118.jpeg', '2026-05-30 15:54:13', '2026-05-30 17:58:38'),
(21, 'cat_oud_icon', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(22, 'cat_gifts_icon', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(23, 'cat_offers_icon', '', '', '2026-05-30 15:54:13', '2026-05-30 15:54:13'),
(50, 'policy_hero_title', 'Return & Exchange Policy', 'سياسة الارتجاع', '2026-06-02 11:55:43', '2026-06-02 11:56:03'),
(51, 'policy_hero_lead', 'We want your experience to always be excellent. Read our policy to know your full rights.', 'نريد أن تكون تجربتك ممتازة دائمًا. اقرأ سياستك لتعرف حقوقك كاملة.', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(52, 'policy_hero_updated', 'Last updated: May 2025', 'آخر تحديث: مايو ٢٠٢٥', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(53, 'policy_exchange_title', 'Exchange Policy', 'سياسة الاستبدال', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(54, 'policy_exchange_badge', 'Within 7 days', 'خلال ٧ أيام', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(55, 'policy_exchange_text', 'We offer product exchange within <strong style=\"color:var(--gold);\">7 days</strong> of receipt, under the conditions outlined below.', 'نوفر إمكانية استبدال المنتجات خلال <strong style=\"color:var(--gold);\">٧ أيام</strong> من تاريخ الاستلام وفق الشروط الموضحة أدناه.', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(56, 'policy_exchange_list_en', '', '', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(57, 'policy_exchange_list_ar', '', '', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(58, 'policy_exchange_note', 'The period is calculated from the actual delivery date, not the order date.', 'يُحسب الوقت من تاريخ التسليم الفعلي لا من تاريخ الطلب.', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(59, 'policy_returns_title', 'Returns & Refunds', 'الاسترجاع الإرجاع', '2026-06-02 11:55:43', '2026-06-02 11:57:36'),
(60, 'policy_returns_lead', 'We accept return requests only in the following cases:', 'نقبل طلبات الإرجاع في الحالات التالية فقط:', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(61, 'policy_returns_list_en', '', '', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(62, 'policy_returns_list_ar', '', '', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(63, 'policy_returns_note', 'Please contact us immediately and send photos of the product to review and take appropriate action.', 'يُرجى التواصل فوراً وإرسال صور للمنتج لمراجعة الطلب واتخاذ الإجراء المناسب.', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(64, 'policy_shipping_title', 'Exchange Shipping Costs', 'تكاليف الاستبدال', '2026-06-02 11:55:43', '2026-06-02 11:57:11'),
(65, 'policy_shipping_list_en', '', '', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(66, 'policy_shipping_list_ar', '', '', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(67, 'policy_contact_title', 'Have a Question About Our Policy?', 'هل لديك سؤال عن سياستنا؟', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(68, 'policy_contact_text', 'Our team is ready to help. Contact us via WhatsApp or our contact form.', 'فريقنا جاهز للمساعدة. تواصل معنا عبر واتساب أو نموذج التواصل.', '2026-06-02 11:55:43', '2026-06-02 11:55:43'),
(126, 'about_hero_title', 'Fragrances That <em>Tell a Story</em>', 'عطورٌ <em>تحكي قصة</em>', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(127, 'about_hero_lead', 'Since our founding, we believe fragrance isn\'t just a scent — it\'s a memory in the making, a personality expressed.', 'منذ تأسيسنا، ونحن نؤمن بأن العطر ليس مجرد رائحة — بل هو ذكرى تُصنع، وشخصية تُعبّر.', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(128, 'about_story_title', 'From a Small Passion to a <em>Known Fragrance House</em>', 'من شغفٍ صغير إلى <em>دار عطور</em> معروفة', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(129, 'about_story_image', 'about_story_1780391032.png', 'about_story_1780391032.png', '2026-06-02 12:03:22', '2026-06-02 12:03:52'),
(130, 'about_story_p1', 'Zain Perfumes started from a genuine passion for the world of fragrance and the art of composition, with the belief that every person has a scent that expresses their identity and leaves a beautiful impression wherever they go.', 'بدأت زين للعطور من شغف حقيقي بعالم العطور وفن التركيب، إيمانًا بأن لكل شخص عطره الذي يعبّر عن هويته ويترك أثرًا جميلًا في كل مكان يمر به.', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(131, 'about_story_p2', 'Today we offer over 500 original fragrances at competitive prices, from authentic Gulf perfumes and luxury oud to global signatures and exclusive scents. Every piece carefully selected to suit different tastes.', 'اليوم نقدم أكثر من 200 عطر أصيل بأسعار تنافسية، بدءًا من العطور الخليجية الأصيلة والعود الفاخر، وصولًا إلى التوقيعات العالمية والعطور الحصرية. كل قطعة مختارة بعناية لتناسب أذواق مختلفة.', '2026-06-02 12:03:22', '2026-06-25 22:59:35'),
(132, 'about_story_p3', 'We believe quality fragrance shouldn\'t be limited to a high price tag, which is why we always strive to deliver the best experience at fair prices with service from the heart.', 'نؤمن بأن جودة العطر لا يجب أن تكون حكرًا على سعر باهظ، لذلك نسعى دائمًا لتقديم أفضل تجربة بأسعار عادلة وخدمة من القلب.', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(133, 'about_values_title', 'Our Values <em>&amp; Philosophy</em>', 'قيمنا <em>وفلسفتنا</em>', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(134, 'about_values_lead', 'The principles that guide every decision we make.', 'المبادئ التي تقود كل قرار نتخذه.', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(135, 'about_values_en', '', '', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(136, 'about_values_ar', '', '', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(137, 'about_promises_en', '', '', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(138, 'about_promises_ar', '', '', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(139, 'about_promise_title', 'An <em>Unforgettable</em> Shopping Experience', 'تجربة شراء <em>لا تُنسى</em>', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(140, 'about_promise_text', 'Every order is an opportunity to prove our commitment to quality and service. Shop with confidence — we\'re with you every step.', 'كل طلب هو فرصة لنثبت التزامنا بالجودة وخدمة العملاء. تسوّق بثقة — نحن معك في كل خطوة.', '2026-06-02 12:03:22', '2026-06-02 12:03:22'),
(157, 'hero_title', '', '', '2026-06-20 18:55:28', '2026-06-25 22:21:40'),
(158, 'hero_subtitle', '', '', '2026-06-20 18:55:28', '2026-06-25 22:21:40'),
(159, 'hero_cta_text', '', '', '2026-06-20 18:55:28', '2026-06-25 22:21:40'),
(160, 'hero_cta_link', '', 'products.php', '2026-06-20 18:55:28', '2026-06-25 22:21:40'),
(162, 'ga_id', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(163, 'fb_pixel_id', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(164, 'google_client_id', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(165, 'google_client_secret', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(166, 'facebook_app_id', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(167, 'facebook_app_secret', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(171, 'hero_link_1', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(172, 'hero_link_2', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(173, 'hero_link_3', '', '', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(178, 'cart_drawer_message', 'hhhhhhhhhhhh', 'fgjhgfhgfh', '2026-06-20 19:22:13', '2026-06-20 19:22:13'),
(214, 'women_category_cart_message', 'يُباح التعطرُ للنساء داخل المنزل، \r\n وهو مُستحبّ إذا كان بهدف إدخال السرور على قلب زوجها، ولكنّه يصبح مُحرماً في حالة التعطر والخروج بقصد أن يشمَّه الرجال الأجانب، وتُؤثم المرأة التي تفعل ذلك، لأنّ في عطرها فتنة للرجال.\r\nبنذكر بعض بس 😊', 'الحديث الشريف: عن أبي موسى الأشعري رضي الله عنه، قال رسول الله ﷺ: \"أيُّما امرأةٍ استعطرت فمرَّت على قومٍ ليجدوا ريحَها فهي زانيةٌ\" [صحيح أبي داود].', '2026-06-20 19:40:15', '2026-06-25 22:00:55');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_cities`
--

CREATE TABLE `shipping_cities` (
  `id` int(10) UNSIGNED NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_cities`
--

INSERT INTO `shipping_cities` (`id`, `name_en`, `name_ar`, `shipping_cost`, `sort_order`, `active`) VALUES
(1, 'بورسعيد', 'بورسعيد', 120.00, 0, 1),
(2, 'بورسعيد', 'بورسعيد', 120.00, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_wallets`
--

CREATE TABLE `user_wallets` (
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_wallets`
--

INSERT INTO `user_wallets` (`user_id`, `balance`, `updated_at`) VALUES
(6, 0.00, '2026-07-31 04:17:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_read` (`is_read`),
  ADD KEY `idx_notif_created` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cm_created` (`created_at`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `homepage_offers`
--
ALTER TABLE `homepage_offers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `internal_products`
--
ALTER TABLE `internal_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offer_bundles`
--
ALTER TABLE `offer_bundles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offer_bundle_products`
--
ALTER TABLE `offer_bundle_products`
  ADD PRIMARY KEY (`bundle_id`,`product_id`),
  ADD KEY `fk_obp_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_created` (`created_at`);

--
-- Indexes for table `order_internal_products`
--
ALTER TABLE `order_internal_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_oip_order` (`order_id`),
  ADD KEY `fk_oip_product` (`internal_product_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_oi_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_products_slug` (`slug`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_slug`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_product` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pr_product` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pv_product` (`product_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `shipping_cities`
--
ALTER TABLE `shipping_cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_wallets`
--
ALTER TABLE `user_wallets`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `homepage_offers`
--
ALTER TABLE `homepage_offers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `internal_products`
--
ALTER TABLE `internal_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `offer_bundles`
--
ALTER TABLE `offer_bundles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `order_internal_products`
--
ALTER TABLE `order_internal_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=446;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=531;

--
-- AUTO_INCREMENT for table `shipping_cities`
--
ALTER TABLE `shipping_cities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `offer_bundle_products`
--
ALTER TABLE `offer_bundle_products`
  ADD CONSTRAINT `fk_obp_bundle` FOREIGN KEY (`bundle_id`) REFERENCES `offer_bundles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_obp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_internal_products`
--
ALTER TABLE `order_internal_products`
  ADD CONSTRAINT `fk_oip_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_oip_product` FOREIGN KEY (`internal_product_id`) REFERENCES `internal_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_pr_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
