<?php

ob_start();                                 
session_start();                            
date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_OFF);            



$host = "sql106.infinityfree.com";
$user = "if0_43145712";
$password = "h9HrcdjWqR";
$database = "if0_43145712_library_system";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    error_log("MySQL connection error: " . $conn->connect_error);
    die("Database connection failed. Please check your settings.");
}

$conn->set_charset("utf8mb4");


if ($conn->connect_error) {
    error_log("Database connection error: " . $conn->connect_error);
    die("Database connection failed. Please check your settings.");
}

$conn->set_charset("utf8mb4");

define("LOAN_DAYS", 7);    
define("MAX_LOANS", 3);   


function run_query($conn, $sql, $types = "", $params = array()) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Query error: " . mysqli_error($conn));
    }
    if ($types != "") {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        die("Query error: " . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    if ($result === false) {
        return true;       
    }
    return $result;      
}


function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}


function set_message($type, $text) {
    $_SESSION['message_type'] = $type;    
    $_SESSION['message']      = $text;
}

function valid_phone($phone) {
    return preg_match('/^09[0-9]{9}$/', $phone) === 1;
}


function first_letter($text) {
    return function_exists('mb_substr') ? mb_strtoupper(mb_substr($text, 0, 1)) : strtoupper(substr($text, 0, 1));
}


function loan_badge($loan) {
    if ($loan['returned_on'] !== null) {
        return '<span class="badge bg-secondary">Returned</span>';
    }
    if ($loan['due_on'] < date('Y-m-d')) {
        return '<span class="badge bg-danger">Overdue</span>';
    }
    return '<span class="badge bg-primary">Borrowed</span>';
}
?>
