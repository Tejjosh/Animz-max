-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 01:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `animzmax_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$WdiLb4b28A2my3oBhHPUyebn86VZWOgubvruxSMyTzdZuyiRCr1V2', 'admin@animzmax.com', '2025-06-18 14:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `submitted_at`) VALUES
(14, 'tej joshi', 'tej@gmail.com', '123456', '2025-06-23 11:55:47'),
(15, 'tej', 'varesh@gmail.com', '123654', '2025-06-23 12:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `contact_replies`
--

CREATE TABLE `contact_replies` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `reply_message` text NOT NULL,
  `replied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_replies`
--

INSERT INTO `contact_replies` (`id`, `contact_id`, `reply_message`, `replied_at`) VALUES
(3, 14, 'thank you for password', '2025-06-23 12:05:45'),
(4, 15, 'cba', '2025-06-25 07:50:28'),
(5, 14, 'ahdbuab', '2025-06-25 07:50:36');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `user_email`, `subject`, `status`, `sent_at`) VALUES
(14, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:30:27'),
(15, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:31:31'),
(16, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:41:40'),
(17, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:41:41'),
(18, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:44:31'),
(19, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:48:56'),
(20, 'tej1@gmail.com', 'Password Reset - Animz Max', 'Success', '2025-06-27 09:51:33');

-- --------------------------------------------------------

--
-- Table structure for table `messagess`
--

CREATE TABLE `messagess` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messagess`
--

INSERT INTO `messagess` (`id`, `sender_id`, `receiver_id`, `message`, `sent_at`, `is_read`) VALUES
(50, 6, 1, 'asvc', '2025-06-24 13:15:09', 1),
(51, 1, 6, 'afzsv', '2025-06-24 14:14:58', 1),
(52, 1, 6, 'adbevzasfd', '2025-06-24 14:15:16', 1),
(53, 6, 1, 'abc', '2025-06-24 14:42:14', 1),
(54, 6, 1, 'abd', '2025-06-25 13:19:13', 1),
(55, 1, 6, 'cba', '2025-06-25 13:20:14', 1),
(56, 15, 1, 'abc', '2025-06-27 16:30:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `target_type` enum('all','selected') DEFAULT 'all',
  `target_user_ids` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `content`, `target_type`, `target_user_ids`, `created_at`) VALUES
(9, 1, 'djavbjbjb', 'all', NULL, '2025-06-24 11:32:24'),
(10, 1, 'cba', 'all', NULL, '2025-06-25 07:50:42');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(500) NOT NULL,
  `city` varchar(100) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `order_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `full_name`, `email`, `address`, `city`, `zip`, `payment_method`, `order_total`, `status`, `created_at`, `updated_at`, `phone`) VALUES
(79, 15, 'tej1', 'tej1@gmail.com', 'abc', 'abc', '123', 'NetBanking', 999.00, 'Shipped', '2025-06-27 16:06:58', '2025-06-27 16:06:58', '123456987');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `update_order_count_after_delete` AFTER DELETE ON `orders` FOR EACH ROW BEGIN
    IF OLD.user_id IS NOT NULL THEN
        UPDATE users
        SET order_count = order_count - 1
        WHERE user_id = OLD.user_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_order_count_after_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.user_id IS NOT NULL THEN
        UPDATE users
        SET order_count = order_count + 1
        WHERE user_id = NEW.user_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
(18, 79, 'AOT9', 'Reiner Armored Titan Shirt', 1, 999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price`, `description`, `image_url`, `category`, `slug`) VALUES
('AOT1', 'Eren Yeager Titan Hoodie', 999.00, 'Hoodie featuring Eren Yeager in his Titan form.', 'Attack_on_titanphoto/attackontitan1.jpg', 'Attack on Titan', 'eren-yeager-titan-hoodie'),
('AOT10', 'Attack Titan Legacy Tee', 999.00, 'T-shirt celebrating the legacy of the Attack Titan.', 'Attack_on_titanphoto/attackontitan10.jpg', 'Attack on Titan', 'attack-titan-legacy-tee'),
('AOT2', 'Survey Corps Emblem Tee', 999.00, 'T-shirt with the Wings of Freedom emblem of the Survey Corps.', 'Attack_on_titanphoto/attackontitan2.jpg', 'Attack on Titan', 'survey-corps-emblem-tee'),
('AOT3', 'Mikasa Ackerman Blade Shirt', 999.00, 'Shirt inspired by Mikasa wielding her dual blades in battle.', 'Attack_on_titanphoto/attackontitan3.jpg', 'Attack on Titan', 'mikasa-ackerman-blade-shirt'),
('AOT4', 'Levi Ackerman Clean Cut Tee', 999.00, 'T-shirt featuring Levi in action, known for his precision and speed.', 'Attack_on_titanphoto/attackontitan4.jpg', 'Attack on Titan', 'levi-ackerman-clean-cut-tee'),
('AOT5', 'Colossal Titan Wall Shirt', 999.00, 'Shirt showing the Colossal Titan breaching Wall Maria.', 'Attack_on_titanphoto/attackontitan5.jpg', 'Attack on Titan', 'colossal-titan-wall-shirt'),
('AOT6', 'Beast Titan Silhouette Hoodie', 999.00, 'Hoodie featuring the silhouette of the Beast Titan.', 'Attack_on_titanphoto/attackontitan6.jpg', 'Attack on Titan', 'beast-titan-silhouette-hoodie'),
('AOT7', 'Armin Firestorm Tee', 999.00, 'T-shirt depicting Armin and his transformation into the Colossal Titan.', 'Attack_on_titanphoto/attackontitan7.jpg', 'Attack on Titan', 'armin-firestorm-tee'),
('AOT8', 'Female Titan Rage Hoodie', 999.00, 'Hoodie themed around Annie in her Female Titan form.', 'Attack_on_titanphoto/attackontitan8.jpg', 'Attack on Titan', 'female-titan-rage-hoodie'),
('AOT9', 'Reiner Armored Titan Shirt', 999.00, 'Shirt inspired by Reiner Braun\'s armored transformation.', 'Attack_on_titanphoto/attackontitan9.jpg', 'Attack on Titan', 'reiner-armored-titan-shirt'),
('B1', 'Ichigo Bankai Hoodie', 999.00, 'Hoodie featuring Ichigo Kurosaki in his Bankai transformation.', 'bleachphoto/bleach1.jpg', 'Bleach', 'ichigo-bankai-hoodie'),
('B10', 'Espada Ulquiorra Tee', 999.00, 'T-shirt showing Ulquiorra in his second resurrection form.', 'bleachphoto/bleach10.jpg', 'Bleach', 'espada-ulquiorra-tee'),
('B2', 'Zangetsu Slash Tee', 999.00, 'T-shirt inspired by Ichigo’s iconic Zangetsu attack.', 'bleachphoto/bleach2.jpg', 'Bleach', 'zangetsu-slash-tee'),
('B3', 'Rukia Ice Queen Shirt', 999.00, 'Shirt showing Rukia’s ice-based Zanpakuto, Sode no Shirayuki.', 'bleachphoto/bleach3.jpg', 'Bleach', 'rukia-ice-queen-shirt'),
('B4', 'Byakuya Senbonzakura Hoodie', 999.00, 'Elegant hoodie with Byakuya’s cherry blossom blades.', 'bleachphoto/bleach4.jpg', 'Bleach', 'byakuya-senbonzakura-hoodie'),
('B5', 'Soul Reaper Squad 13 Tee', 999.00, 'T-shirt showcasing the Squad 13 symbol and theme.', 'bleachphoto/bleach5.jpg', 'Bleach', 'soul-reaper-squad13-tee'),
('B6', 'Hollow Ichigo Mask Hoodie', 999.00, 'Dark hoodie representing Ichigo’s hollow side mask.', 'bleachphoto/bleach6.jpg', 'Bleach', 'hollow-ichigo-mask-hoodie'),
('B7', 'Aizen Final Form Shirt', 999.00, 'Shirt themed around Aizen\'s ultimate transformation.', 'bleachphoto/bleach7.jpg', 'Bleach', 'aizen-final-form-shirt'),
('B8', 'Urahara Kisuke Classic Tee', 999.00, 'Casual tee inspired by Urahara’s classic striped hat look.', 'bleachphoto/bleach8.jpg', 'Bleach', 'urahara-kisuke-classic-tee'),
('B9', 'Toshiro Hitsugaya Hoodie', 999.00, 'Cool-toned hoodie featuring Captain Hitsugaya’s ice dragon.', 'bleachphoto/bleach9.jpg', 'Bleach', 'toshiro-hitsugaya-hoodie'),
('BAC1', 'Asta Demon Form Tee', 999.00, 'T-shirt featuring Asta in his partial demon transformation.', 'blackcloverphoto/blackclover1.jpg', 'Black Clover', 'asta-demon-form-tee'),
('BAC10', 'Nacht Shadow Magic Hoodie', 999.00, 'Hoodie depicting Nacht with his devil shadows.', 'blackcloverphoto/blackclover10.jpg', 'Black Clover', 'nacht-shadow-magic-hoodie'),
('BAC11', 'Asta Determined Look Tee', 999.00, 'T-shirt showing Asta in a fearless battle stance.', 'blackcloverphoto/blackclover11.jpg', 'Black Clover', 'asta-determined-look-tee'),
('BAC12', 'Charlotte Roselei Briar Armor Shirt', 999.00, 'Shirt featuring Captain Charlotte’s Briar magic armor.', 'blackcloverphoto/blackclover12.jpg', 'Black Clover', 'charlotte-briar-armor-shirt'),
('BAC13', 'Luck Voltia Lightning Tee', 999.00, 'T-shirt inspired by Luck’s lightning-enhanced battle mode.', 'blackcloverphoto/blackclover13.jpg', 'Black Clover', 'luck-voltia-lightning-tee'),
('BAC14', 'Finral Spatial Warp Shirt', 999.00, 'Shirt featuring Finral using his spatial magic portals.', 'blackcloverphoto/blackclover14.jpg', 'Black Clover', 'finral-spatial-warp-shirt'),
('BAC15', 'Zora Trap Magic Shirt', 999.00, 'Shirt showcasing Zora’s trap-based magic techniques.', 'blackcloverphoto/blackclover15.jpg', 'Black Clover', 'zora-trap-magic-shirt'),
('BAC2', 'Yuno Wind Spirit Shirt', 999.00, 'Shirt showcasing Yuno alongside Sylph, the Wind Spirit.', 'blackcloverphoto/blackclover2.jpg', 'Black Clover', 'yuno-wind-spirit-shirt'),
('BAC3', 'Black Bulls Squad Tee', 999.00, 'T-shirt with the Black Bulls emblem and members artwork.', 'blackcloverphoto/blackclover3.jpg', 'Black Clover', 'black-bulls-squad-tee'),
('BAC4', 'Noelle Valkyrie Armor Hoodie', 999.00, 'Hoodie featuring Noelle in her Valkyrie battle form.', 'blackcloverphoto/blackclover4.jpg', 'Black Clover', 'noelle-valkyrie-armor-hoodie'),
('BAC5', 'Magic Knight Emblem Shirt', 999.00, 'Shirt with the Clover Kingdom’s Magic Knights symbol.', 'blackcloverphoto/blackclover5.jpg', 'Black Clover', 'magic-knight-emblem-shirt'),
('BAC6', 'Yami Dimension Slash Hoodie', 999.00, 'Dark hoodie themed on Captain Yami’s Dimension Slash move.', 'blackcloverphoto/blackclover6.jpg', 'Black Clover', 'yami-dimension-slash-hoodie'),
('BAC7', 'Black Clover Grimoire Hoodie', 999.00, 'Hoodie showcasing Asta’s five-leaf grimoire.', 'blackcloverphoto/blackclover7.jpg', 'Black Clover', 'black-clover-grimoire-hoodie'),
('BAC8', 'Dark Triad Threat Shirt', 999.00, 'Shirt highlighting the powerful Dark Triad trio.', 'blackcloverphoto/blackclover8.jpg', 'Black Clover', 'dark-triad-threat-shirt'),
('BAC9', 'Golden Dawn Elite Tee', 999.00, 'T-shirt featuring the crest of the elite Golden Dawn squad.', 'blackcloverphoto/blackclover9.jpg', 'Black Clover', 'golden-dawn-elite-tee'),
('BSD1', 'Atsushi Beast Jacket', 999.00, 'Stylish jacket inspired by Atsushi’s Beast Beneath the Moonlight ability.', 'bungostraydogsphoto/bungoustraydogs1.jpg', 'Bungo Stray Dogs', 'atsushi-beast-jacket'),
('BSD10', 'Sigma Casino Heist Hoodie', 999.00, 'Hoodie inspired by Sigma and the mysterious Decay of Angels organization.', 'bungostraydogsphoto/bungoustraydogs10.jpg', 'Bungo Stray Dogs', 'sigma-casino-heist-hoodie'),
('BSD2', 'Dazai No Longer Human Tee', 999.00, 'T-shirt featuring Dazai Osamu and his signature ability No Longer Human.', 'bungostraydogsphoto/bungoustraydogs2.jpg', 'Bungo Stray Dogs', 'dazai-no-longer-human-tee'),
('BSD3', 'Akutagawa Rashomon Hoodie', 999.00, 'Hoodie showcasing Akutagawa’s powerful ability Rashomon.', 'bungostraydogsphoto/bungoustraydogs3.jpg', 'Bungo Stray Dogs', 'akutagawa-rashomon-hoodie'),
('BSD4', 'Port Mafia Streetwear Tee', 999.00, 'Urban-style t-shirt inspired by the members of Port Mafia.', 'bungostraydogsphoto/bungoustraydogs4.jpg', 'Bungo Stray Dogs', 'port-mafia-streetwear-tee'),
('BSD5', 'Chuuya Gravity Control Hoodie', 999.00, 'Hoodie featuring Chuuya Nakahara and his gravity ability, Upon the Tainted Sorrow.', 'bungostraydogsphoto/bungoustraydogs5.jpg', 'Bungo Stray Dogs', 'chuuya-gravity-control-hoodie'),
('BSD6', 'Ranpo Detective Agency Tee', 999.00, 'Detective-themed shirt honoring Edogawa Ranpo and the Armed Detective Agency.', 'bungostraydogsphoto/bungoustraydogs6.jpg', 'Bungo Stray Dogs', 'ranpo-detective-agency-tee'),
('BSD7', 'Fyodor Crime and Punishment Hoodie', 999.00, 'Dark-themed hoodie inspired by Fyodor Dostoevsky’s deadly intellect.', 'bungostraydogsphoto/bungoustraydogs7.jpg', 'Bungo Stray Dogs', 'fyodor-crime-and-punishment-hoodie'),
('BSD8', 'Kunikida Idealism Tee', 999.00, 'T-shirt showing Kunikida Doppo and his ability to manifest from ideals.', 'bungostraydogsphoto/bungoustraydogs8.jpg', 'Bungo Stray Dogs', 'kunikida-idealism-tee'),
('BSD9', 'Mori Ougai Mafia Leader Shirt', 999.00, 'Shirt featuring Mori Ougai and his creepy yet refined leadership style.', 'bungostraydogsphoto/bungoustraydogs9.jpg', 'Bungo Stray Dogs', 'mori-ougai-mafia-leader-shirt'),
('DB1', 'Goku Super Saiyan Tee', 999.00, 'T-shirt featuring Goku in his iconic Super Saiyan transformation.', 'dragonballphoto/dragonball1.jpg', 'Dragon Ball', 'goku-super-saiyan-tee'),
('DB10', 'Dragon Ball Z Logo Hoodie', 999.00, 'Classic hoodie featuring the Dragon Ball Z logo with vibrant colors.', 'dragonballphoto/dragonball10.jpg', 'Dragon Ball', 'dragon-ball-z-logo-hoodie'),
('DB2', 'Vegeta Galick Gun Shirt', 999.00, 'Shirt showcasing Vegeta unleashing his powerful Galick Gun.', 'dragonballphoto/dragonball2.jpg', 'Dragon Ball', 'vegeta-galick-gun-shirt'),
('DB3', 'Gohan Mystic Power Hoodie', 999.00, 'Hoodie featuring Gohan in his Mystic (Ultimate) form.', 'dragonballphoto/dragonball3.jpg', 'Dragon Ball', 'gohan-mystic-power-hoodie'),
('DB4', 'Capsule Corp Style Tee', 999.00, 'T-shirt with Capsule Corp logo worn by Bulma and Trunks.', 'dragonballphoto/dragonball4.jpg', 'Dragon Ball', 'capsule-corp-style-tee'),
('DB5', 'Frieza Final Form Shirt', 999.00, 'Shirt showing Frieza’s terrifying final form.', 'dragonballphoto/dragonball5.jpg', 'Dragon Ball', 'frieza-final-form-shirt'),
('DB6', 'Piccolo Special Beam Hoodie', 999.00, 'Hoodie inspired by Piccolo’s Special Beam Cannon attack.', 'dragonballphoto/dragonball6.jpg', 'Dragon Ball', 'piccolo-special-beam-hoodie'),
('DB7', 'Trunks Sword Slash Tee', 999.00, 'T-shirt featuring Future Trunks with his energy sword.', 'dragonballphoto/dragonball7.jpg', 'Dragon Ball', 'trunks-sword-slash-tee'),
('DB8', 'Majin Buu Chaos Hoodie', 999.00, 'Hoodie featuring the unpredictable and powerful Majin Buu.', 'dragonballphoto/dragonball8.jpg', 'Dragon Ball', 'majin-buu-chaos-hoodie'),
('DB9', 'Android 18 Power Stance Shirt', 999.00, 'Shirt showing Android 18 in a dynamic battle-ready stance.', 'dragonballphoto/dragonball9.jpg', 'Dragon Ball', 'android-18-power-stance-shirt'),
('DS1', 'Tanjiro Breath of Water Hoodie', 999.00, 'Hoodie inspired by Tanjiro Kamado\'s Water Breathing technique.', 'demonslayerphoto/demonslayer1.jpeg', 'Demon Slayer', 'tanjiro-breath-of-water-hoodie'),
('DS10', 'Rengoku Flame Jacket', 999.00, 'Jacket inspired by Kyojuro Rengoku\'s blazing Flame Breathing.', 'demonslayerphoto/demonslayer10.jpg', 'Demon Slayer', 'rengoku-flame-jacket'),
('DS2', 'Nezuko Flame Dance Shirt', 999.00, 'Shirt featuring Nezuko Kamado in her fierce Flame Dance form.', 'demonslayerphoto/demonslayer2.jpg', 'Demon Slayer', 'nezuko-flame-dance-shirt'),
('DS3', 'Zenitsu Thunder Flash Tee', 999.00, 'T-shirt showcasing Zenitsu Agatsuma\'s lightning-fast Thunder Flash attack.', 'demonslayerphoto/demonslayer3.jpg', 'Demon Slayer', 'zenitsu-thunder-flash-tee'),
('DS4', 'Inosuke Beast Roar Jacket', 999.00, 'Jacket inspired by Inosuke Hashibira\'s wild Beast Roar fighting style.', 'demonslayerphoto/demonslayer4.jpg', 'Demon Slayer', 'inosuke-beast-roar-jacket'),
('DS5', 'Gojo Black Hoodie', 999.00, 'Black hoodie inspired by Gojo Satoru\'s limitless power.', 'demonslayerphoto/demonslayer5.jpg', 'Demon Slayer', 'gojo-black-hoodie'),
('DS6', 'Shinobu Wisteria Charm Shirt', 999.00, 'Shirt featuring Shinobu Kocho and the protective Wisteria charm.', 'demonslayerphoto/demonslayer6.jpg', 'Demon Slayer', 'shinobu-wisteria-charm-shirt'),
('DS7', 'Kanao Blossom Tee', 999.00, 'T-shirt inspired by Kanao Tsuyuri and her delicate flower motif.', 'demonslayerphoto/demonslayer7.jpg', 'Demon Slayer', 'kanao-blossom-tee'),
('DS8', 'Giyu Water Style Hoodie', 999.00, 'Hoodie inspired by Giyu Tomioka\'s Water Breathing techniques.', 'demonslayerphoto/demonslayer8.jpg', 'Demon Slayer', 'giyu-water-style-hoodie'),
('DS9', 'Muzan Shadow Hoodie', 999.00, 'Dark hoodie themed after Muzan Kibutsuji\'s sinister shadow.', 'demonslayerphoto/demonslayer9.jpg', 'Demon Slayer', 'muzan-shadow-hoodie'),
('FF1', 'Shinra Fire Burst Tee', 999.00, 'T-shirt showcasing Shinra Kusakabe igniting his signature devil’s footprints.', 'fire_forcephoto/fireforce1.jpg', 'Fire Force', 'shinra-fire-burst-tee'),
('FF10', 'Shinmon Style Combat Shirt', 999.00, 'Shirt with traditional Asakusa flair inspired by Shinmon-style fire combat.', 'fire_forcephoto/fireforce10.jpg', 'Fire Force', 'shinmon-style-combat-shirt'),
('FF2', 'Arthur Plasma Excalibur Shirt', 999.00, 'Shirt featuring Arthur Boyle wielding his plasma sword Excalibur.', 'fire_forcephoto/fireforce2.jpg', 'Fire Force', 'arthur-plasma-excalibur-shirt'),
('FF3', 'Maki Fire Soldier Hoodie', 999.00, 'Hoodie depicting Maki Oze with her fireball spirits and Iron Owls.', 'fire_forcephoto/fireforce3.jpg', 'Fire Force', 'maki-fire-soldier-hoodie'),
('FF4', 'Company 8 Emblem Tee', 999.00, 'T-shirt bearing the symbol of Special Fire Force Company 8.', 'fire_forcephoto/fireforce4.jpg', 'Fire Force', 'company-8-emblem-tee'),
('FF5', 'Tamaki Lucky Lecher Hoodie', 999.00, 'Playful hoodie representing Tamaki’s “Lucky Lecher” moments and cat-themed attire.', 'fire_forcephoto/fireforce5.jpg', 'Fire Force', 'tamaki-lucky-lecher-hoodie'),
('FF6', 'Joker Mystery Flame Shirt', 999.00, 'Shirt featuring the enigmatic Joker with flaming playing cards.', 'fire_forcephoto/fireforce6.jpg', 'Fire Force', 'joker-mystery-flame-shirt'),
('FF7', 'Benimaru Asakusa Hoodie', 999.00, 'Hoodie inspired by Benimaru Shinmon’s dual-wielding inferno attacks.', 'fire_forcephoto/fireforce7.jpg', 'Fire Force', 'benimaru-asakusa-hoodie'),
('FF8', 'Captain Obi Power Tee', 999.00, 'T-shirt celebrating Akitaru Obi’s pure strength and heroic leadership.', 'fire_forcephoto/fireforce8.jpg', 'Fire Force', 'captain-obi-power-tee'),
('FF9', 'Iris Graceful Sister Hoodie', 999.00, 'Hoodie featuring Sister Iris and her solemn prayer stance.', 'fire_forcephoto/fireforce9.jpg', 'Fire Force', 'iris-graceful-sister-hoodie'),
('JK1', 'Yuji Itadori Fight Mode Tee', 999.00, 'T-shirt featuring Yuji Itadori in his fierce fighting stance.', 'jujutsukaisienphoto/jujutsukaisien1.jpg', 'Jujutsu Kaisen', 'yuji-itadori-fight-mode-tee'),
('JK10', 'Panda Fierce Fighter Hoodie', 999.00, 'Hoodie inspired by Panda\'s strength and spirit.', 'jujutsukaisienphoto/jujutsukaisien10.jpg', 'Jujutsu Kaisen', 'panda-fierce-fighter-hoodie'),
('JK2', 'Megumi Fushiguro Shadow Hoodie', 999.00, 'Hoodie inspired by Megumi Fushiguro and his shadow shikigami.', 'jujutsukaisienphoto/jujutsukaisien2.jpg', 'Jujutsu Kaisen', 'megumi-fushiguro-shadow-hoodie'),
('JK3', 'Nobara Kugisaki Hammer Shirt', 999.00, 'Shirt inspired by Nobara Kugisaki and her signature hammer.', 'jujutsukaisienphoto/jujutsukaisien3.jpg', 'Jujutsu Kaisen', 'nobara-kugisaki-hammer-shirt'),
('JK4', 'Zenin Family Flashstrike Tee', 999.00, 'T-shirt inspired by the Zenin family powerful techniques.', 'jujutsukaisienphoto/jujutsukaisien4.jpg', 'Jujutsu Kaisen', 'zenin-family-flashstrike-tee'),
('JK5', 'Sukuna Curse Shadow Shirt', 999.00, 'Shirt themed around Sukuna\'s ominous cursed energy.', 'jujutsukaisienphoto/jujutsukaisien5.jpg', 'Jujutsu Kaisen', 'sukuna-curse-shadow-shirt'),
('JK6', 'Satoru Gojo Infinity Hoodie', 999.00, 'Iconic hoodie inspired by Satoru Gojo\'s limitless power.', 'jujutsukaisienphoto/jujutsukaisien6.jpg', 'Jujutsu Kaisen', 'satoru-gojo-infinity-hoodie'),
('JK7', 'Mahito Distortion Tee', 999.00, 'T-shirt featuring Mahito and his cursed soul distortion.', 'jujutsukaisienphoto/jujutsukaisien7.jpg', 'Jujutsu Kaisen', 'mahito-distortion-tee'),
('JK8', 'Toge Inumaki Whisper Hoodie', 999.00, 'Hoodie inspired by Toge Inumaki\'s silent but deadly cursed speech.', 'jujutsukaisienphoto/jujutsukaisien8.jpg', 'Jujutsu Kaisen', 'toge-inumaki-whisper-hoodie'),
('JK9', 'Kento Nanami Business Casual Shirt', 999.00, 'Shirt representing Kento Nanami\'s calm and collected style.', 'jujutsukaisienphoto/jujutsukaisien9.jpg', 'Jujutsu Kaisen', 'kento-nanami-business-casual-shirt'),
('MHA1', 'Izuku Midoriya Hero Tee', 999.00, 'T-shirt featuring Izuku Midoriya in his hero costume.', 'My_hero_academiaphoto/my-hero-academia1.jpg', 'My Hero Academia', 'izuku-midoriya-hero-tee'),
('MHA10', 'Fumikage Tokoyami Dark Shadow Hoodie', 999.00, 'Hoodie inspired by Tokoyami\'s Dark Shadow quirk.', 'My_hero_academiaphoto/my-hero-academia10.jpg', 'My Hero Academia', 'fumikage-tokoyami-dark-shadow-hoodie'),
('MHA2', 'All Might Symbol Hoodie', 999.00, 'Hoodie with the iconic All Might emblem.', 'My_hero_academiaphoto/my-hero-academia2.jpg', 'My Hero Academia', 'all-might-symbol-hoodie'),
('MHA3', 'Ochaco Uravity Shirt', 999.00, 'Shirt inspired by Ochaco Uraraka and her zero gravity quirk.', 'My_hero_academiaphoto/my-hero-academia3.jpg', 'My Hero Academia', 'ochaco-uravity-shirt'),
('MHA4', 'Katsuki Bakugo Explosion Tee', 999.00, 'T-shirt showcasing Bakugo\'s explosive quirk.', 'My_hero_academiaphoto/my-hero-academia4.jpg', 'My Hero Academia', 'katsuki-bakugo-explosion-tee'),
('MHA5', 'Shoto Todoroki Dual Quirk Shirt', 999.00, 'Shirt featuring Todoroki\'s fire and ice powers.', 'My_hero_academiaphoto/my-hero-academia5.jpg', 'My Hero Academia', 'shoto-todoroki-dual-quirk-shirt'),
('MHA6', 'Eijiro Kirishima Hardening Hoodie', 999.00, 'Hoodie inspired by Kirishima\'s hardening quirk.', 'My_hero_academiaphoto/my-hero-academia6.jpg', 'My Hero Academia', 'eijiro-kirishima-hardening-hoodie'),
('MHA7', 'Tsuyu Asui Frog Power Tee', 999.00, 'T-shirt inspired by Tsuyu Asui and her frog abilities.', 'My_hero_academiaphoto/my-hero-academia7.jpg', 'My Hero Academia', 'tsuyu-asui-frog-power-tee'),
('MHA8', 'Momo Yaoyorozu Creation Hoodie', 999.00, 'Hoodie themed around Momo Yaoyorozu\'s creation quirk.', 'My_hero_academiaphoto/my-hero-academia8.jpg', 'My Hero Academia', 'momo-yaoyorozu-creation-hoodie'),
('MHA9', 'Denki Kaminari Electric Charge Shirt', 999.00, 'Shirt featuring Denki Kaminari\'s electric quirk.', 'My_hero_academiaphoto/my-hero-academia9.jpg', 'My Hero Academia', 'denki-kaminari-electric-charge-shirt'),
('N1', 'Naruto Sage Mode Hoodie', 999.00, 'Powerful hoodie featuring Naruto in Sage Mode.', 'narutophoto/naruto1.jpg', 'Naruto', 'naruto-sage-mode-hoodie'),
('N10', 'Gaara Sand Coffin Hoodie', 999.00, 'Sand-themed hoodie symbolizing Gaara\'s jutsu.', 'narutophoto/naruto10.jpg', 'Naruto', 'gaara-sand-coffin-hoodie'),
('N11', 'Rock Lee Lotus Tee', 999.00, 'Energetic tee inspired by Rock Lee\'s Lotus moves.', 'narutophoto/naruto11.jpg', 'Naruto', 'rock-lee-lotus-tee'),
('N12', 'Neji Gentle Fist Shirt', 999.00, 'Clean design with Neji\'s Gentle Fist stance.', 'narutophoto/naruto12.jpg', 'Naruto', 'neji-gentle-fist-shirt'),
('N13', 'Obito Masked Hoodie', 999.00, 'Hoodie with Obito\'s iconic spiral mask.', 'narutophoto/naruto13.jpg', 'Naruto', 'obito-masked-hoodie'),
('N14', 'Tobi Akatsuki Cloak Tee', 999.00, 'Classic tee with Tobi in Akatsuki attire.', 'narutophoto/naruto14.jpg', 'Naruto', 'tobi-akatsuki-cloak-tee'),
('N15', 'Hashirama Wood Style Shirt', 999.00, 'Strong design inspired by Hashirama\'s jutsu.', 'narutophoto/naruto15.jpg', 'Naruto', 'hashirama-wood-style-shirt'),
('N2', 'Kurama Cloak Tee', 999.00, 'Bold tee with Naruto\'s Nine-Tails cloak design.', 'narutophoto/naruto2.jpg', 'Naruto', 'kurama-cloak-tee'),
('N3', 'Sasuke Rinnegan Shirt', 999.00, 'Epic shirt featuring Sasuke\'s Rinnegan power.', 'narutophoto/naruto3.jpg', 'Naruto', 'sasuke-rinnegan-shirt'),
('N4', 'Kakashi Sharingan Hoodie', 999.00, 'Stylish hoodie with Kakashi\'s Sharingan eye.', 'narutophoto/naruto4.jpg', 'Naruto', 'kakashi-sharingan-hoodie'),
('N5', 'Itachi Akatsuki Hoodie', 999.00, 'Dark-themed hoodie inspired by Itachi Uchiha.', 'narutophoto/naruto5.jpg', 'Naruto', 'itachi-akatsuki-hoodie'),
('N6', 'Madara Eternal Mangekyo Tee', 999.00, 'Graphic tee with Madara\'s Mangekyo design.', 'narutophoto/naruto6.jpg', 'Naruto', 'madara-eternal-mangekyo-tee'),
('N7', 'Hinata Byakugan Shirt', 999.00, 'Elegant shirt showing Hinata\'s Byakugan gaze.', 'narutophoto/naruto7.jpg', 'Naruto', 'hinata-byakugan-shirt'),
('N8', 'Minato Flash Hoodie', 999.00, 'Flashy hoodie honoring Minato the Yellow Flash.', 'narutophoto/naruto8.jpg', 'Naruto', 'minato-flash-hoodie'),
('N9', 'Jiraiya Toad Sage Tee', 999.00, 'Whimsical tee with Jiraiya and his toads.', 'narutophoto/naruto9.jpg', 'Naruto', 'jiraiya-toad-sage-tee'),
('OP1', 'Luffy Gear 5 Hoodie', 999.00, 'High-quality hoodie inspired by Luffy\'s Gear 5.', 'onepiecephoto/onepiece1.jpg', 'One Piece', 'luffy-gear-5-hoodie'),
('OP10', 'Jinbe Fishman Karate Hoodie', 999.00, 'Hoodie with Jinbe\'s fishman karate stance.', 'onepiecephoto/onepiece10.jpg', 'One Piece', 'jinbe-fishman-karate-hoodie'),
('OP11', 'Ace Fire Fist Shirt', 999.00, 'Flaming shirt in memory of Portgas D. Ace.', 'onepiecephoto/onepiece11.jpg', 'One Piece', 'ace-fire-fist-shirt'),
('OP12', 'Sabo Flame Emperor Hoodie', 999.00, 'Epic hoodie with Sabo\'s flame abilities.', 'onepiecephoto/onepiece12.jpg', 'One Piece', 'sabo-flame-emperor-hoodie'),
('OP13', 'Shanks Red Hair Tee', 999.00, 'Minimalist tee with Red-Haired Shanks.', 'onepiecephoto/onepiece13.jpg', 'One Piece', 'shanks-red-hair-tee'),
('OP14', 'Buggy the Clown Hoodie', 999.00, 'Colorful hoodie themed after Buggy.', 'onepiecephoto/onepiece14.jpg', 'One Piece', 'buggy-the-clown-hoodie'),
('OP15', 'Law Op-Op Fruit Shirt', 999.00, 'Cool shirt with Trafalgar Law\'s powers.', 'onepiecephoto/onepiece15.jpg', 'One Piece', 'law-op-op-fruit-shirt'),
('OP2', 'Zoro Three Sword Tee', 999.00, 'T-shirt featuring Zoro\'s Santoryu pose.', 'onepiecephoto/onepiece2.jpg', 'One Piece', 'zoro-three-sword-tee'),
('OP3', 'Nami Climate Staff Shirt', 999.00, 'Stylish shirt with Nami\'s climate baton artwork.', 'onepiecephoto/onepiece3.jpg', 'One Piece', 'nami-climate-staff-shirt'),
('OP4', 'Sanji Black Leg Hoodie', 999.00, 'Hoodie inspired by Sanji\'s fire leg technique.', 'onepiecephoto/onepiece4.jpg', 'One Piece', 'sanji-black-leg-hoodie'),
('OP5', 'Chopper Cute Mode Tee', 999.00, 'Adorable tee featuring Tony Tony Chopper.', 'onepiecephoto/onepiece5.jpg', 'One Piece', 'chopper-cute-mode-tee'),
('OP6', 'Robin Devil Bloom Hoodie', 999.00, 'Elegant hoodie with Robin\'s Hana Hana no Mi.', 'onepiecephoto/onepiece6.jpg', 'One Piece', 'robin-devil-bloom-hoodie'),
('OP7', 'Franky SUPER Shirt', 999.00, 'Bold and colorful shirt with Franky design.', 'onepiecephoto/onepiece7.jpg', 'One Piece', 'franky-super-shirt'),
('OP8', 'Brook Soul King Hoodie', 999.00, 'Musical-themed hoodie featuring Brook.', 'onepiecephoto/onepiece8.jpg', 'One Piece', 'brook-soul-king-hoodie'),
('OP9', 'Usopp Sniper King Tee', 999.00, 'Graphic tee celebrating Usopp\'s alter ego.', 'onepiecephoto/onepiece9.jpg', 'One Piece', 'usopp-sniper-king-tee'),
('SL1', 'Sung Jin-Woo Shadow Hoodie', 999.00, 'Epic hoodie featuring Sung Jin-Woo embracing his shadow powers.', 'sololevelingphoto/sololeveling1.jpg', 'Solo Leveling', 'sung-jinwoo-shadow-hoodie'),
('SL10', 'Solo Leveling Final Battle Tee', 999.00, 'Epic tee symbolizing the final showdown of Jin-Woo.', 'sololevelingphoto/sololeveling10.jpg', 'Solo Leveling', 'solo-leveling-final-battle-tee'),
('SL2', 'Monarch of Shadows Tee', 999.00, 'T-shirt inspired by the Shadow Monarch transformation.', 'sololevelingphoto/sololeveling2.png', 'Solo Leveling', 'monarch-of-shadows-tee'),
('SL3', 'Igris Elite Warrior Shirt', 999.00, 'Shirt featuring Igris, the loyal shadow knight.', 'sololevelingphoto/sololeveling3.jpg', 'Solo Leveling', 'igris-elite-warrior-shirt'),
('SL4', 'Beru Insect Commander Hoodie', 999.00, 'Hoodie designed with Beru’s terrifying presence.', 'sololevelingphoto/sololeveling4.jpg', 'Solo Leveling', 'beru-insect-commander-hoodie'),
('SL5', 'Hunter Association Classic Tee', 999.00, 'Basic tee showing the Hunter Association insignia.', 'sololevelingphoto/sololeveling5.jpg', 'Solo Leveling', 'hunter-association-classic-tee'),
('SL6', 'Jeju Island Raid Hoodie', 999.00, 'Hoodie capturing the intensity of the Jeju Island raid.', 'sololevelingphoto/sololeveling6.jpg', 'Solo Leveling', 'jeju-island-raid-hoodie'),
('SL7', 'Go Gun-Hee Veteran Shirt', 999.00, 'Shirt honoring Go Gun-Hee’s strength and legacy.', 'sololevelingphoto/sololeveling7.jpg', 'Solo Leveling', 'go-gunhee-veteran-shirt'),
('SL8', 'Double Dungeon Survivor Tee', 999.00, 'Inspired by the turning point in Jin-Woo’s life.', 'sololevelingphoto/sololeveling8.jpg', 'Solo Leveling', 'double-dungeon-survivor-tee'),
('SL9', 'Shadow Army Emblem Hoodie', 999.00, 'Hoodie with emblem of the feared shadow army.', 'sololevelingphoto/sololeveling9.jpg', 'Solo Leveling', 'shadow-army-emblem-hoodie');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'https://via.placeholder.com/100',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `order_count` int(11) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `avatar`, `created_at`, `address`, `city`, `zip`, `phone`, `order_count`, `reset_token`, `reset_expires`) VALUES
(6, 'tej', 'tej@gmail.com', '$2y$10$NK9cdffC8vJ3Y9OCALMeXOuTKAI4WPo4rEQXrdTu9vJOzr2g0Gn/q', 'uploads/avatars/avatar_6859471ec0f39.jpg', '2025-06-17 09:21:04', 'velani heights', 'atladara', '390012', '8401907776', 0, NULL, NULL),
(7, 'varesh', 'varesh@gmail.com', '$2y$10$jxVqaBC157bmU65avGY/LOR//31CnES5OLEQWSPtauRP4E/d760J6', 'https://via.placeholder.com/100', '2025-06-17 12:12:09', 'velani heights', 'atladara', '390012', '9106562158', 0, NULL, NULL),
(14, 'abc', 'abcdeasf@abc.com', '$2y$10$6uBZDG7b2wKd6TXm.ATzKeOk9OGih9zeDPHnxnU19XDQs5tTyn1S2', 'https://via.placeholder.com/100', '2025-06-26 12:10:26', NULL, NULL, NULL, NULL, 0, NULL, NULL),
(15, 'tej1', 'tej1@gmail.com', '$2y$10$i532dYtArCxE6D1HNseNWu7aD080nbLEvZHbzsmhfJRfRyQH/yaIe', 'https://via.placeholder.com/100', '2025-06-26 12:10:53', 'abc', 'abc', '123', '123456987', 1, NULL, '2025-06-27 12:51:29'),
(16, 'tej12', 'tej12@gmail.com', '$2y$10$Ax5Keslb/gTuVQWu.2XReeo5fXXZ9Y.xNtST4SBMvS6HnqzbePKqe', 'https://via.placeholder.com/100', '2025-06-26 12:12:28', NULL, NULL, NULL, NULL, 0, '0a2036fd62d7d798929fb6e1515aca1bcea66456c8825c6bae3d316c54371092', '2025-06-26 15:35:44'),
(17, 'bca', 'bac@bac.com', '$2y$10$haeRJ8aixBQ.tgnfwrRO9OJktD6B1aUqX9bFBUUV1HzC7zkW0l0Ki', 'https://via.placeholder.com/100', '2025-06-26 12:17:26', NULL, NULL, NULL, NULL, 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messagess`
--
ALTER TABLE `messagess`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_product` (`product_id`),
  ADD KEY `order_id_fk` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `contact_replies`
--
ALTER TABLE `contact_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `messagess`
--
ALTER TABLE `messagess`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD CONSTRAINT `contact_replies_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contact_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
