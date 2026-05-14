<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capture and sanitize all 10 fields from your form
    $hh_no = mysqli_real_escape_string($conn, $_POST['hh_no']);
    $survey_date = mysqli_real_escape_string($conn, $_POST['survey_date']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $purok = mysqli_real_escape_string($conn, $_POST['purok']);
    $house = mysqli_real_escape_string($conn, $_POST['house']);
    $electricity = mysqli_real_escape_string($conn, $_POST['electricity']);
    $house_type = mysqli_real_escape_string($conn, $_POST['house_type']);
    $toilet = mysqli_real_escape_string($conn, $_POST['toilet']);
    $vulnerability = mysqli_real_escape_string($conn, $_POST['vulnerability']);
    $water = mysqli_real_escape_string($conn, $_POST['water']);

    // 2. Duplicate Check
    $check_query = "SELECT household_no FROM households WHERE household_no = '$hh_no'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Error: This Household Number already exists.'); window.location.href='add_household.php';</script>";
        exit();
    }

    // 3. The Corrected INSERT Query
    // This connects your form variables to your new database columns (10-14)
    $query = "INSERT INTO households (
                household_no, 
                survey_date, 
                address, 
                purok, 
                house, 
                house_type, 
                electricity, 
                toilet, 
                vulnerability, 
                water
              ) VALUES (
                '$hh_no', 
                '$survey_date', 
                '$address', 
                '$purok', 
                '$house', 
                '$house_type', 
                '$electricity', 
                '$toilet', 
                '$vulnerability', 
                '$water'
              )";

    if (mysqli_query($conn, $query)) {
        $_SESSION['current_household_no'] = $hh_no;
        header("Location: add_members.php"); 
        exit();
    } else {
        die("Database Error: " . mysqli_error($conn));
    }
} else {
    header("Location: add_household.php");
    exit();
}
?>














