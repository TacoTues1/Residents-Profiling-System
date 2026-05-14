<?php
session_start();
// Include db.php only if you need to validate household numbers against the DB before proceeding
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Save the household data into a session variable
    $_SESSION['temp_household_data'] = [
        'hh_no' => $_POST['hh_no'],
        'survey_date' => $_POST['survey_date'],
        'address' => $_POST['address'],
        'purok' => $_POST['purok'],
        'house' => $_POST['house'],
        'electricity' => $_POST['electricity'],
        'house_type' => $_POST['house_type'],
        'toilet' => $_POST['toilet'],
        'vulnerability' => $_POST['vulnerability'],
        'water' => $_POST['water']
    ];

    // Redirect to the "Add Member" page
    header("Location: add_member.php");
    exit();
}
?>














