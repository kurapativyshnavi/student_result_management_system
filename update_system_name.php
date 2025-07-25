<?php
include 'db_connect.php';

$new_name = "Student Result Management System";
$stmt = $conn->prepare("UPDATE system_settings SET name = ? WHERE id = 1");
$stmt->bind_param("s", $new_name);

if($stmt->execute()){
    echo "System name updated successfully";
} else {
    echo "Error updating system name: " . $conn->error;
}

$stmt->close();
$conn->close();
?> 