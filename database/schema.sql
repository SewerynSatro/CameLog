-- CameLog – schema SQL (MySQL / MariaDB)
-- Wersja: 1.0

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS care_history;
DROP TABLE IF EXISTS care_tasks;
DROP TABLE IF EXISTS plant_photos;
DROP TABLE IF EXISTS plants;
DROP TABLE IF EXISTS species;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Użytkownicy
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)  NOT NULL,
    email           VARCHAR(190)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    role            ENUM('user','admin') NOT NULL DEFAULT 'user',
    status          ENUM('active','blocked') NOT NULL DEFAULT 'active',
    bio             TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_status (status),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Gatunki (cache zewnętrznego API + ręcznie dodane)
-- ============================================================
CREATE TABLE species (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_api_id     VARCHAR(100) NULL,
    common_name         VARCHAR(190) NOT NULL,
    scientific_name     VARCHAR(190) NULL,
    care_level          ENUM('easy','medium','hard') NULL,
    watering_info       TEXT NULL,
    sunlight_info       TEXT NULL,
    climate_info        TEXT NULL,
    raw_api_data        LONGTEXT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_species_external (external_api_id),
    INDEX idx_species_common (common_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Rośliny użytkownika
-- ============================================================
CREATE TABLE plants (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     INT UNSIGNED NOT NULL,
    species_id                  INT UNSIGNED NULL,
    name                        VARCHAR(150) NOT NULL,
    custom_species_name         VARCHAR(190) NULL,
    location                    VARCHAR(80)  NULL,
    planted_at                  DATE NULL,
    notes                       TEXT NULL,
    watering_interval_days      SMALLINT UNSIGNED NULL,
    fertilizing_interval_days   SMALLINT UNSIGNED NULL,
    care_level                  ENUM('easy','medium','hard') NULL,
    api_recommendations_used    TINYINT(1) NOT NULL DEFAULT 0,
    health_status               ENUM('healthy','needs_attention','sick') NOT NULL DEFAULT 'healthy',
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_plants_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_plants_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE SET NULL,
    INDEX idx_plants_user (user_id),
    INDEX idx_plants_health (health_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Zdjęcia rośliny
-- ============================================================
CREATE TABLE plant_photos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plant_id    INT UNSIGNED NOT NULL,
    file_path   VARCHAR(255) NOT NULL,
    is_main     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photos_plant FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    INDEX idx_photos_plant (plant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Taski (planowanie pielęgnacji)
-- ============================================================
CREATE TABLE care_tasks (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT UNSIGNED NOT NULL,
    plant_id                INT UNSIGNED NOT NULL,
    type                    ENUM('watering','fertilizing','pruning','repotting','misting','custom') NOT NULL,
    title                   VARCHAR(200) NOT NULL,
    description             TEXT NULL,
    due_date                DATETIME NOT NULL,
    status                  ENUM('pending','done','skipped') NOT NULL DEFAULT 'pending',
    repeat_interval_days    SMALLINT UNSIGNED NULL,
    priority                ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    completed_at            DATETIME NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_tasks_plant FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    INDEX idx_tasks_user_status (user_id, status),
    INDEX idx_tasks_due (due_date),
    INDEX idx_tasks_plant (plant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Historia pielęgnacji
-- ============================================================
CREATE TABLE care_history (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    plant_id        INT UNSIGNED NOT NULL,
    task_id         INT UNSIGNED NULL,
    type            ENUM('watering','fertilizing','pruning','repotting','misting','custom') NOT NULL,
    note            TEXT NULL,
    performed_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_user  FOREIGN KEY (user_id)  REFERENCES users(id)     ON DELETE CASCADE,
    CONSTRAINT fk_history_plant FOREIGN KEY (plant_id) REFERENCES plants(id)    ON DELETE CASCADE,
    CONSTRAINT fk_history_task  FOREIGN KEY (task_id)  REFERENCES care_tasks(id) ON DELETE SET NULL,
    INDEX idx_history_user (user_id),
    INDEX idx_history_plant_time (plant_id, performed_at),
    INDEX idx_history_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Powiadomienia
-- ============================================================
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    task_id     INT UNSIGNED NULL,
    title       VARCHAR(200) NOT NULL,
    message     TEXT NULL,
    type        ENUM('today','incoming','overdue','system') NOT NULL DEFAULT 'system',
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id)     ON DELETE CASCADE,
    CONSTRAINT fk_notif_task FOREIGN KEY (task_id) REFERENCES care_tasks(id) ON DELETE SET NULL,
    INDEX idx_notif_user_type (user_id, type),
    INDEX idx_notif_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
