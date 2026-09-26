<?php
require_once "config/database.php";
$sql = "ALTER TABLE holdings ADD COLUMN purchase_price DECIMAL(10,2) NULL AFTER quantity";
if ($conn->query($sql) === TRUE) {
    echo "Table holdings altered successfully.";
} else {
    echo "Error altering table: " . $conn->error;
}
?>