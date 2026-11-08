CREATE DATABASE IF NOT EXISTS netwatch
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE netwatch;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator', 'viewer') NOT NULL DEFAULT 'viewer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_active_role (is_active, role)
) ENGINE=InnoDB;

CREATE TABLE devices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    hostname VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17) NULL,
    device_type ENUM(
        'router',
        'switch',
        'server',
        'desktop',
        'laptop',
        'printer',
        'access_point',
        'camera',
        'nas',
        'other'
    ) NOT NULL DEFAULT 'other',
    description TEXT NULL,
    monitoring_enabled TINYINT(1) NOT NULL DEFAULT 1,
    current_status ENUM('unknown', 'online', 'offline', 'warning') NOT NULL DEFAULT 'unknown',
    current_latency_ms DECIMAL(10, 3) NULL,
    packet_loss_percent DECIMAL(5, 2) NULL,
    last_seen_at DATETIME NULL,
    status_changed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_devices_ip_address (ip_address),
    KEY idx_devices_status_type (current_status, device_type),
    KEY idx_devices_monitoring_status (monitoring_enabled, current_status),
    KEY idx_devices_last_seen (last_seen_at),
    CONSTRAINT fk_devices_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE monitoring_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id BIGINT UNSIGNED NOT NULL,
    checked_at DATETIME NOT NULL,
    is_reachable TINYINT(1) NOT NULL,
    response_time_ms DECIMAL(10, 3) NULL,
    packet_loss_percent DECIMAL(5, 2) NULL,
    error_message VARCHAR(500) NULL,
    source VARCHAR(80) NOT NULL DEFAULT 'python-agent',
    PRIMARY KEY (id),
    KEY idx_results_device_checked (device_id, checked_at),
    KEY idx_results_checked (checked_at),
    CONSTRAINT fk_results_device
        FOREIGN KEY (device_id) REFERENCES devices (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE incidents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id BIGINT UNSIGNED NOT NULL,
    type ENUM('offline', 'high_latency', 'packet_loss', 'recovery') NOT NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    last_observed_at DATETIME NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'warning',
    summary VARCHAR(255) NOT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_incidents_device_time (device_id, started_at),
    KEY idx_incidents_open (ended_at, severity),
    CONSTRAINT fk_incidents_device
        FOREIGN KEY (device_id) REFERENCES devices (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id BIGINT UNSIGNED NULL,
    incident_id BIGINT UNSIGNED NULL,
    type ENUM(
        'device_offline',
        'device_recovery',
        'high_latency',
        'packet_loss',
        'new_device'
    ) NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    deduplication_key VARCHAR(255) NOT NULL,
    is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    acknowledged_by BIGINT UNSIGNED NULL,
    acknowledged_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_alerts_deduplication_key (deduplication_key),
    KEY idx_alerts_created_acknowledged (created_at, is_acknowledged),
    KEY idx_alerts_device_type (device_id, type),
    CONSTRAINT fk_alerts_device
        FOREIGN KEY (device_id) REFERENCES devices (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_alerts_incident
        FOREIGN KEY (incident_id) REFERENCES incidents (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_alerts_acknowledged_by
        FOREIGN KEY (acknowledged_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE network_connections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_device_id BIGINT UNSIGNED NOT NULL,
    target_device_id BIGINT UNSIGNED NOT NULL,
    connection_type VARCHAR(50) NOT NULL DEFAULT 'ethernet',
    label VARCHAR(120) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_connections_endpoints (source_device_id, target_device_id),
    KEY idx_connections_target (target_device_id),
    CONSTRAINT chk_connections_distinct_devices
        CHECK (source_device_id <> target_device_id),
    CONSTRAINT fk_connections_source
        FOREIGN KEY (source_device_id) REFERENCES devices (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_connections_target
        FOREIGN KEY (target_device_id) REFERENCES devices (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_connections_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE monitoring_settings (
    id TINYINT UNSIGNED NOT NULL,
    ping_interval_seconds INT UNSIGNED NOT NULL DEFAULT 30,
    ping_timeout_seconds INT UNSIGNED NOT NULL DEFAULT 5,
    latency_warning_ms DECIMAL(10, 3) NOT NULL DEFAULT 100,
    latency_critical_ms DECIMAL(10, 3) NOT NULL DEFAULT 500,
    packet_loss_warning_percent DECIMAL(5, 2) NOT NULL DEFAULT 5,
    packet_loss_critical_percent DECIMAL(5, 2) NOT NULL DEFAULT 25,
    offline_after_failures INT UNSIGNED NOT NULL DEFAULT 2,
    discord_enabled TINYINT(1) NOT NULL DEFAULT 0,
    discord_webhook_url VARCHAR(500) NULL,
    application_name VARCHAR(120) NOT NULL DEFAULT 'NetWatch',
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    updated_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT chk_settings_singleton CHECK (id = 1),
    CONSTRAINT fk_settings_updated_by
        FOREIGN KEY (updated_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    details JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    KEY idx_audit_user_time (user_id, created_at),
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO monitoring_settings (id)
VALUES (1)
ON DUPLICATE KEY UPDATE id = VALUES(id);
