--
-- Tablo için tablo yapısı `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','warning','success','critical') DEFAULT 'info',
  `author_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blocks`
--

CREATE TABLE `blocks` (
  `blocker_id` int(11) NOT NULL,
  `blocked_id` int(11) NOT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `commenter_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `target_type` enum('drawing','profile') NOT NULL,
  `target_id` int(11) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `drawings`
--

CREATE TABLE `drawings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `first_row_length` int(11) DEFAULT 6,
  `width` int(11) DEFAULT 11,
  `category` varchar(50) NOT NULL DEFAULT 'Genel',
  `comments_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `follows`
--

CREATE TABLE `follows` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `followed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `follow_requests`
--

CREATE TABLE `follow_requests` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `status` enum('pending') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rank_settings`
--

CREATE TABLE `rank_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` decimal(10,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Tablo döküm verisi `rank_settings`
--

INSERT INTO `rank_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'comment_points', '1.00', '2025-11-11 11:03:14'),
(2, 'drawing_points', '2.00', '2025-11-11 11:03:14'),
(3, 'follower_points', '0.50', '2025-11-11 11:03:14'),
(4, 'upvote_points', '0.20', '2025-11-11 11:03:14'),
(5, 'profile_comment_points', '0.30', '2025-11-11 11:17:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sessions`
--

CREATE TABLE `sessions` (
  `session_key` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `last_active` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `social_media_platforms`
--

CREATE TABLE `social_media_platforms` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `url_regex` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Tablo döküm verisi `social_media_platforms`
--

INSERT INTO `social_media_platforms` (`id`, `name`, `emoji`, `url_regex`, `is_active`, `created_at`) VALUES
(27, 'Instagram', '?', 'instagram.com/', 1, '2025-11-11 12:57:05'),
(28, 'Twitter', '?', 'twitter.com/|x.com/', 1, '2025-11-11 12:57:05'),
(29, 'Facebook', '?', 'facebook.com/', 1, '2025-11-11 12:57:05'),
(30, 'YouTube', '?', 'youtube.com/|youtu.be/', 1, '2025-11-11 12:57:05'),
(31, 'Telegram', '?', 't.me/', 1, '2025-11-11 12:57:05'),
(32, 'TikTok', '?', 'tiktok.com/', 1, '2025-11-11 12:57:05'),
(33, 'LinkedIn', '?', 'linkedin.com/', 1, '2025-11-11 12:57:05'),
(34, 'GitHub', '?', 'github.com/', 1, '2025-11-11 12:57:05'),
(35, 'Discord', '?', 'discord.com/|discord.gg/', 1, '2025-11-11 12:57:05'),
(36, 'Linktree', '?', 'linktr.ee/', 1, '2025-11-11 12:57:05');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stats`
--

CREATE TABLE `stats` (
  `key_name` varchar(50) NOT NULL,
  `value` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','moderator','admin') NOT NULL DEFAULT 'user',
  `privacy_mode` enum('public','private') NOT NULL DEFAULT 'public',
  `profile_picture` text DEFAULT NULL,
  `profile_views` int(11) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `comment_mute_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rank` int(11) DEFAULT 1,
  `special_badge` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_social_links`
--

CREATE TABLE `user_social_links` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `profile_url` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `target_type` enum('drawing','comment','profile','category') NOT NULL,
  `target_id` int(11) NOT NULL,
  `vote_type` enum('up','down') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Tablo için indeksler `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Tablo için indeksler `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`blocker_id`,`blocked_id`),
  ADD KEY `fk_blocks_blocker` (`blocker_id`),
  ADD KEY `fk_blocks_blocked` (`blocked_id`);

--
-- Tablo için indeksler `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comment_user` (`commenter_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Tablo için indeksler `drawings`
--
ALTER TABLE `drawings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_drawing_user` (`user_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_visible` (`is_visible`);

--
-- Tablo için indeksler `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `fk_follows_follower` (`follower_id`),
  ADD KEY `fk_follows_following` (`following_id`);

--
-- Tablo için indeksler `follow_requests`
--
ALTER TABLE `follow_requests`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `fk_requests_follower` (`follower_id`),
  ADD KEY `fk_requests_following` (`following_id`);

--
-- Tablo için indeksler `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Tablo için indeksler `rank_settings`
--
ALTER TABLE `rank_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_key`),
  ADD KEY `idx_last_active` (`last_active`),
  ADD KEY `fk_session_user` (`user_id`);

--
-- Tablo için indeksler `social_media_platforms`
--
ALTER TABLE `social_media_platforms`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`key_name`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_google_id` (`google_id`);

--
-- Tablo için indeksler `user_social_links`
--
ALTER TABLE `user_social_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_platform` (`user_id`,`platform_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Tablo için indeksler `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`voter_id`,`target_type`,`target_id`);

--
-- Tablo için AUTO_INCREMENT değeri `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `drawings`
--
ALTER TABLE `drawings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `rank_settings`
--
ALTER TABLE `rank_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `social_media_platforms`
--
ALTER TABLE `social_media_platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `user_social_links`
--
ALTER TABLE `user_social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `blocks`
--
ALTER TABLE `blocks`
  ADD CONSTRAINT `fk_blocks_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blocks_blocker` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`commenter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `drawings`
--
ALTER TABLE `drawings`
  ADD CONSTRAINT `fk_drawing_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `fk_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_follows_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `follow_requests`
--
ALTER TABLE `follow_requests`
  ADD CONSTRAINT `fk_requests_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;
