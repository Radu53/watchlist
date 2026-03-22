CREATE DATABASE IF NOT EXISTS cphgderk_watchlist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cphgderk_watchlist;

CREATE TABLE media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('unknown', 'movie', 'tv') NOT NULL DEFAULT 'unknown',
    title VARCHAR(255) NOT NULL,
    year SMALLINT UNSIGNED NULL,
    description TEXT NULL,
    cover_url VARCHAR(1000) NULL,
    imdb_rating DECIMAL(3,1) NULL,
    watch_url VARCHAR(1000) NULL,
    watch_domain VARCHAR(255) NULL,
    season_count SMALLINT UNSIGNED NULL,
    episode_count SMALLINT UNSIGNED NULL,
    first_air_date DATE NULL,
    last_air_date DATE NULL,
    status ENUM('draft', 'started', 'watched') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_year (year),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_watch_domain (watch_domain)
);

CREATE TABLE genres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE media_genres (
    media_id INT UNSIGNED NOT NULL,
    genre_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (media_id, genre_id),
    CONSTRAINT fk_media_genres_media
        FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    CONSTRAINT fk_media_genres_genre
        FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

CREATE TABLE watch_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    action_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_watch_history_media
        FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    INDEX idx_media_id (media_id),
    INDEX idx_action_date (action_date)
);

CREATE TABLE parser_sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE parser_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    rule_type ENUM('regex', 'meta') NOT NULL,
    rule_value TEXT NOT NULL,
    fallback_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_parser_rules_site
        FOREIGN KEY (site_id) REFERENCES parser_sites(id) ON DELETE CASCADE,
    INDEX idx_site_field (site_id, field_name)
);