-- ######################################################
-- # UYGULAMA TABLOLARI OLUŞTURMA SORGUSU
-- ######################################################

-- Veritabanında bir hata oluşursa işlemi durdur
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- -----------------------------------------------------
-- 1. KULLANICILAR TABLOSU (users)
-- Kullanıcı bilgileri, rol, gizlilik, ban ve yorum yasağı durumu
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,

  -- Yönetici Rolleri: user (varsayılan), moderator, admin
  `role` ENUM('user','moderator','admin') NOT NULL DEFAULT 'user',

  -- Gizlilik Modu: public (herkese açık), private (gizli)
  `privacy_mode` ENUM('public','private') NOT NULL DEFAULT 'public',

  `profile_picture` VARCHAR(255) DEFAULT 'default.png',
  `profile_views` INT(11) NOT NULL DEFAULT 0,

  -- Moderasyon Alanları
  `is_banned` BOOLEAN NOT NULL DEFAULT FALSE,
  `comment_mute_until` DATETIME NULL DEFAULT NULL, -- Yorum yasağı bitiş tarihi

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------
-- 2. ÇİZİMLER TABLOSU (drawings)
-- Çizim içeriği ve meta verileri
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `drawings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,

  -- Anonim çizimler için NULL olabilir
  `user_id` INT(11) NULL DEFAULT NULL,

  `content` TEXT NOT NULL, -- Çizimin kendisi (ASCII/Emoji sanat)
  `category` VARCHAR(50) NOT NULL DEFAULT 'Genel',

  -- Kullanıcı Ayarları
  `comments_allowed` BOOLEAN NOT NULL DEFAULT TRUE,

  -- Moderatör Tarafından Görünürlük Ayarı
  `is_visible` BOOLEAN NOT NULL DEFAULT TRUE,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `fk_drawing_user` (`user_id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_visible` (`is_visible`),
  CONSTRAINT `fk_drawing_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------
-- 3. YORUMLAR TABLOSU (comments)
-- Çizimlere veya kullanıcı panolarına yapılan yorumlar
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `commenter_id` INT(11) NOT NULL,
  `content` TEXT NOT NULL,

  -- Yorumun hedefi: 'drawing' veya 'profile'
  `target_type` ENUM('drawing','profile') NOT NULL,

  -- drawing_id veya users.id (target_type'a bağlı olarak)
  `target_id` INT(11) NOT NULL,

  -- Moderatör Tarafından Görünürlük Ayarı
  `is_visible` BOOLEAN NOT NULL DEFAULT TRUE,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `fk_comment_user` (`commenter_id`),
  KEY `idx_target` (`target_type`, `target_id`),
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`commenter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------
-- 4. TAKİP İLİŞKİLERİ TABLOSU (follows)
-- Başarılı takip ilişkileri (gizli profillerde onaylanmış ilişkiler)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `follows` (
  `follower_id` INT(11) NOT NULL,
  `following_id` INT(11) NOT NULL,
  `followed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`follower_id`, `following_id`), -- Aynı kişiyi sadece bir kez takip edebilir

  KEY `fk_follows_follower` (`follower_id`),
  KEY `fk_follows_following` (`following_id`),

  CONSTRAINT `fk_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_follows_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------
-- 5. TAKİP İSTEKLERİ TABLOSU (follow_requests)
-- Gizli profillere gönderilen bekleyen istekler
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `follow_requests` (
  `follower_id` INT(11) NOT NULL,
  `following_id` INT(11) NOT NULL,
  `status` ENUM('pending') NOT NULL DEFAULT 'pending',
  `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`follower_id`, `following_id`),

  KEY `fk_requests_follower` (`follower_id`),
  KEY `fk_requests_following` (`following_id`),

  CONSTRAINT `fk_requests_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_requests_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------
-- 6. ENGELLEME TABLOSU (blocks)
-- Kullanıcıların kara listesi
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `blocks` (
  `blocker_id` INT(11) NOT NULL, -- Engelleyen kişi
  `blocked_id` INT(11) NOT NULL, -- Engellenen kişi
  `blocked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`blocker_id`, `blocked_id`), -- Aynı engeli tekrar oluşturamaz

  KEY `fk_blocks_blocker` (`blocker_id`),
  KEY `fk_blocks_blocked` (`blocked_id`),

  CONSTRAINT `fk_blocks_blocker` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_blocks_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ######################################################
-- # İŞLEMİ SONLANDIR
-- ######################################################
COMMIT;

-- ######################################################
-- # YENİ SAYAÇ TABLOLARI EKLE (counter_manager.php gereksinimi)
-- ######################################################

-- -----------------------------------------------------
-- 7. İSTATİSTİKLER TABLOSU (stats)
-- Total Views gibi kalıcı sayaçlar için
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stats` (
  `key_name` VARCHAR(50) NOT NULL,
  `value` BIGINT(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Başlangıç değeri ekle (total_views = 0)
INSERT IGNORE INTO `stats` (`key_name`, `value`) VALUES ('total_views', 0);


-- -----------------------------------------------------
-- 8. OTURUM KAYITLARI TABLOSU (sessions)
-- Çevrimiçi kullanıcı/ziyaretçi takibi için
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `session_key` VARCHAR(255) NOT NULL, -- IP Adresi veya Kullanıcı ID'si
  `user_id` INT(11) NULL DEFAULT NULL,
  `last_active` DATETIME NOT NULL,
  PRIMARY KEY (`session_key`),
  KEY `idx_last_active` (`last_active`),
  KEY `fk_session_user` (`user_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bu sorguları generate.sql dosyanıza ekleyip çalıştırdığınızda, sayaç sistemi hazır olacaktır.
