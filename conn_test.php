<?php
require_once dirname(__DIR__) . '/cpms/controller/conn.php'; // Corrected path

if ($conn) {
    echo "Connection successful!";
} else {
    echo "Connection failed!";
}
?>
