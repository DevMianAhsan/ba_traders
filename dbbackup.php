<?php
/**
 * Direct Database Backup & Download Utility
 */

$settings_file = __DIR__ . '/settings.txt';
$settings = file_exists($settings_file) ? explode('|', trim(file_get_contents($settings_file))) : [];

if (file_exists(__DIR__ . '/php_action/db_connect.php')) {
    @include_once __DIR__ . '/php_action/db_connect.php';
}

$dbhost = !empty($settings[0]) ? $settings[0] : ($localhost ?? 'localhost');
$dbuser = isset($settings[1]) && $settings[1] !== '' ? $settings[1] : ($username ?? 'root');
$dbpass = isset($settings[2]) ? $settings[2] : ($password ?? '');
$dbname = !empty($settings[3]) ? $settings[3] : ($dbname ?? 'chairman');
$backup_dir = !empty($settings[4]) ? rtrim($settings[4], '/\\') : 'backup';
$tables = '*';

// Call the core function
backup_tables($dbhost, $dbuser, $dbpass, $dbname, $backup_dir, $tables);

// Core function
function backup_tables($host, $user, $pass, $dbname, $backup_dir = 'backup', $tables = '*') {
    $link = mysqli_connect($host, $user, $pass, $dbname);

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit;
    }

    mysqli_query($link, "SET NAMES 'utf8'");

    if ($tables == '*') {
        $tables = array();
        $result = mysqli_query($link, 'SHOW TABLES');
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    $return = "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $result = mysqli_query($link, 'SELECT * FROM `' . $table . '`');
        $num_fields = mysqli_num_fields($result);

        $return .= 'DROP TABLE IF EXISTS `' . $table . '`;';
        $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE `' . $table . '`'));
        $return .= "\n\n" . $row2[1] . ";\n\n";

        while ($row = mysqli_fetch_row($result)) {
            $return .= 'INSERT INTO `' . $table . '` VALUES(';
            for ($j = 0; $j < $num_fields; $j++) {
                if (isset($row[$j])) {
                    $val = mysqli_real_escape_string($link, $row[$j]);
                    $return .= "'" . $val . "'";
                } else {
                    $return .= 'NULL';
                }
                if ($j < ($num_fields - 1)) {
                    $return .= ',';
                }
            }
            $return .= ");\n";
        }
        $return .= "\n\n";
    }

    $return .= "SET FOREIGN_KEY_CHECKS=1;\n";

    if (!is_dir($backup_dir)) {
        @mkdir($backup_dir, 0777, true);
    }

    $fileName = 'db-backup-' . date('d-m-Y-H-i-s') . '-' . $dbname . '.sql';
    $filePath = $backup_dir . '/' . $fileName;

    if (file_put_contents($filePath, $return, LOCK_EX)) {
        if (!headers_sent()) {
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . filesize($filePath));
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile($filePath);
            exit;
        } else {
            echo "Backup created successfully at: " . $filePath;
            exit;
        }
    } else {
        echo "Failed to save backup file to: " . $filePath;
        exit;
    }
}
