<?php
/*
 * WAR COMMAND - Alliance Tool
 * Copyright (c) 2026 [Denise W.]
 * * LIZENZ: NUR FÜR DEN PRIVATEN GEBRAUCH. KEINE KOMMERZIELLE NUTZUNG.
 * VERKAUF ODER VERMIETUNG DIESER SOFTWARE IST STRENGSTENS UNTERSAGT.
 * * LICENSE: PRIVATE USE ONLY. NO COMMERCIAL USE ALLOWED.
 * SELLING OR RENTING THIS SOFTWARE IS STRICTLY PROHIBITED.
 */
// db_setup.php - V72.0 (Complete Feature Set)
error_reporting(0);
ini_set('display_errors', 0);

// --- KONFIGURATION: BITTE VOR DEM START ANPASSEN ---
$host = 'db';          // Hostname im Docker-Netzwerk (Service-Name)
$db   = 'lastwar';     // Datenbankname
$user = 'planner';     // Datenbank-User
$pass = 'change_me_db_password'; // <--- MUSS MIT docker-compose.yml ÜBEREINSTIMMEN
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_TIMEOUT => 5,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Basis Tabellen Struktur
    $sql = [
        "CREATE TABLE IF NOT EXISTS timers (id INT AUTO_INCREMENT PRIMARY KEY, server_id VARCHAR(10), target_name VARCHAR(100), coord VARCHAR(5), end_time DATETIME, x INT DEFAULT 0, y INT DEFAULT 0, action_type VARCHAR(50) DEFAULT 'other', discord_msg_id VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS servers (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(10) UNIQUE)",
        "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(255), role VARCHAR(5) DEFAULT 'R1')",
        "CREATE TABLE IF NOT EXISTS server_stats (server_name VARCHAR(10), rank INT, power BIGINT, alliance_name VARCHAR(100), PRIMARY KEY (server_name, rank))",
        "CREATE TABLE IF NOT EXISTS duel_stats (id INT AUTO_INCREMENT PRIMARY KEY, player_name VARCHAR(100), score BIGINT, import_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS marshall_stats (id INT AUTO_INCREMENT PRIMARY KEY, player_name VARCHAR(100), score BIGINT, import_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS settings (key_name VARCHAR(50) PRIMARY KEY, value TEXT)",
        "CREATE TABLE IF NOT EXISTS week_settings (week_id VARCHAR(20) PRIMARY KEY, is_save_week TINYINT DEFAULT 0)"
    ];
    foreach($sql as $q) $pdo->exec($q);

    // MIGRATIONEN: Spalten hinzufügen falls nicht existent (Abwärtskompatibilität)
    
    // 1. Timer: Discord Msg ID
    try { $pdo->query("SELECT discord_msg_id FROM timers LIMIT 1"); } 
    catch (Exception $e) { $pdo->exec("ALTER TABLE timers ADD COLUMN discord_msg_id VARCHAR(50) DEFAULT NULL"); }

    // 2. Timer: Allianz Name
    try { $pdo->query("SELECT alliance FROM timers LIMIT 1"); } 
    catch (Exception $e) { $pdo->exec("ALTER TABLE timers ADD COLUMN alliance VARCHAR(100) DEFAULT NULL"); }

    // 3. Timer: Farbe
    try { $pdo->query("SELECT color FROM timers LIMIT 1"); } 
    catch (Exception $e) { $pdo->exec("ALTER TABLE timers ADD COLUMN color VARCHAR(20) DEFAULT NULL"); }

    // 4. Timer: Reminder Flags
    try { $pdo->query("SELECT rem_60 FROM timers LIMIT 1"); } 
    catch (Exception $e) { $pdo->exec("ALTER TABLE timers ADD COLUMN rem_60 TINYINT DEFAULT 0"); }
    try { $pdo->query("SELECT rem_30 FROM timers LIMIT 1"); } 
    catch (Exception $e) { $pdo->exec("ALTER TABLE timers ADD COLUMN rem_30 TINYINT DEFAULT 0"); }

    // Init Admin / Server
    // HINWEIS: Standard Passwort ist 'admin123' - Bitte nach Login ändern!
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'R5')")->execute([password_hash("admin123", PASSWORD_DEFAULT)]);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM servers");
    if ($stmt->fetchColumn() == 0) $pdo->exec("INSERT INTO servers (name) VALUES ('1821'), ('1815'), ('1828')");

    // SETTINGS UPDATE (Webhooks)
    // HINWEIS: Bitte eigene Discord Webhooks eintragen
    $defaults = [
        'discord_webhook' => "https://discord.com/api/webhooks/YOUR_ID/YOUR_TOKEN",
        'bagger_webhook' => "https://discord.com/api/webhooks/YOUR_ID/YOUR_TOKEN"
    ];
    $ins = $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES (?, ?)");
    foreach($defaults as $k => $v) $ins->execute([$k, $v]);

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['status'=>'error', 'error'=>'DB Connection Failed: ' . $e->getMessage()]));
}
?>