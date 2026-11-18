<?php
/**
 * Automated Cron Backup Script for Chairman Management System
 * Can be run via:
 * 1. CLI / Server Cron Job:
 *    /usr/bin/php /home/youruser/public_html/cron_backup.php
 * 2. URL / Webhook:
 *    https://yourdomain.com/cron_backup.php?secret=chairman_auto_backup_key
 */

// Set time and memory limits
@ini_set('memory_limit', '512M');
@set_time_limit(900); // 15 minutes max
date_default_timezone_set("Asia/Karachi");

$secret_key = "chairman_auto_backup_key"; // Change this if calling via URL

// Verify access
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
    $provided_key = $_GET['secret'] ?? '';
    if ($provided_key !== $secret_key) {
        http_response_code(403);
        die(json_encode(["status" => "error", "message" => "Unauthorized: Invalid secret key."]));
    }
}

// Load database settings
$settings_file = __DIR__ . '/settings.txt';
$settings = file_exists($settings_file) ? explode('|', trim(file_get_contents($settings_file))) : [];

// Fallback to db_connect.php if settings.txt is missing
if (empty($settings[0]) || empty($settings[3])) {
    if (file_exists(__DIR__ . '/php_action/db_connect.php')) {
        @include_once __DIR__ . '/php_action/db_connect.php';
        $db_host = $localhost ?? 'localhost';
        $db_user = $username ?? 'root';
        $db_pass = $password ?? '';
        $db_name = $dbname ?? 'chairman';
        $db_dir  = 'backup';
    }
} else {
    $db_host = $settings[0] ?? 'localhost';
    $db_user = $settings[1] ?? 'root';
    $db_pass = $settings[2] ?? '';
    $db_name = $settings[3] ?? 'chairman';
    $db_dir  = !empty($settings[4]) ? $settings[4] : 'backup';
}

$target_dir = __DIR__ . '/' . trim($db_dir, '/\\');
if (!is_dir($target_dir)) {
    @mkdir($target_dir, 0777, true);
}

// Connect to MySQL
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    $err = "Database connection failed: " . mysqli_connect_error();
    if ($is_cli) {
        fwrite(STDERR, "[ERROR] " . $err . PHP_EOL);
    } else {
        echo json_encode(["status" => "error", "message" => $err]);
    }
    exit(1);
}

mysqli_query($conn, "SET NAMES 'utf8'");

// Fetch all tables
$tables = [];
$res = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    $tables[] = $row[0];
}

if (empty($tables)) {
    $err = "No tables found in database `{$db_name}`.";
    if ($is_cli) echo "[WARNING] {$err}\n";
    else echo json_encode(["status" => "warning", "message" => $err]);
    exit(0);
}

// Generate SQL Dump
$sql_dump = "-- Chairman Database Automated Backup\n";
$sql_dump .= "-- Host: {$db_host} | Database: {$db_name}\n";
$sql_dump .= "-- Generated: " . date("Y-m-d H:i:s") . "\n";
$sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $tbl) {
    // Drop and create table
    $create_res = mysqli_query($conn, "SHOW CREATE TABLE `{$tbl}`");
    $create_row = mysqli_fetch_row($create_res);
    $sql_dump .= "DROP TABLE IF EXISTS `{$tbl}`;\n";
    $sql_dump .= $create_row[1] . ";\n\n";

    // Insert data
    $data_res = mysqli_query($conn, "SELECT * FROM `{$tbl}`");
    $num_fields = mysqli_num_fields($data_res);

    while ($data_row = mysqli_fetch_row($data_res)) {
        $sql_dump .= "INSERT INTO `{$tbl}` VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            if (isset($data_row[$j])) {
                $val = mysqli_real_escape_string($conn, $data_row[$j]);
                $sql_dump .= "'" . $val . "'";
            } else {
                $sql_dump .= "NULL";
            }
            if ($j < ($num_fields - 1)) {
                $sql_dump .= ",";
            }
        }
        $sql_dump .= ");\n";
    }
    $sql_dump .= "\n\n";
}

$sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Save dump file
$filename = 'backup-' . $db_name . '-' . date("d-M-Y_H-i-s_a") . '.sql';
$filepath = $target_dir . '/' . $filename;

if (file_put_contents($filepath, $sql_dump, LOCK_EX)) {
    // Retention policy: remove backups older than 30 days
    $files = scandir($target_dir);
    $retention_days = 30;
    $now = time();
    $deleted_count = 0;

    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && (substr($f, -4) === '.sql' || substr($f, -7) === '.sql.gz')) {
            $f_path = $target_dir . '/' . $f;
            if (is_file($f_path) && ($now - filemtime($f_path) > ($retention_days * 86400))) {
                @unlink($f_path);
                $deleted_count++;
            }
        }
    }

    $msg = "Backup successfully created: {$filename} (" . round(filesize($filepath) / 1024, 2) . " KB).";
    if ($deleted_count > 0) {
        $msg .= " Cleaned up {$deleted_count} backup(s) older than {$retention_days} days.";
    }

    if ($is_cli) {
        echo "[SUCCESS] " . $msg . PHP_EOL;
    } else {
        echo json_encode(["status" => "success", "file" => $filename, "message" => $msg]);
    }
} else {
    $err = "Failed to write backup file to: {$filepath}. Check folder permissions.";
    if ($is_cli) {
        fwrite(STDERR, "[ERROR] " . $err . PHP_EOL);
    } else {
        echo json_encode(["status" => "error", "message" => $err]);
    }
}
