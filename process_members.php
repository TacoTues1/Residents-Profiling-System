<?php
session_start();

// FIXED: Changed 'db_connection.php' to 'db.php' to match your folder screenshot
$connection_file = __DIR__ . '/db.php';

if (!file_exists($connection_file)) {
    die("ERROR: The file 'db.php' was not found. Please check your folder again.");
}

include($connection_file);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['members'])) {
    
    // Ensure the uploads folder from your screenshot is ready
    if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

    $household_no = $_POST['household_no'] ?? '';
    $safe_hh_no = mysqli_real_escape_string($conn, $household_no);
    $members = $_POST['members'];

    foreach ($members as $i => $m) {
        $current_photo_path = '';

        // Photo Logic (Captures both Webcam and Uploads)
        if (!empty($m['photo'])) {
            $data = $m['photo'];
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = base64_decode(substr($data, strpos($data, ',') + 1));
                $filename = "res_" . time() . "_" . $i . ".jpg";
                $filepath = "uploads/" . $filename;
                if (file_put_contents($filepath, $data)) { 
                    $current_photo_path = $filepath; 
                }
            }
        }

        // Mapping Logic
        $is_voter  = isset($m['voter']) ? 1 : 0;
        $is_4ps    = isset($m['four_ps']) ? 1 : 0;
        $is_pwd    = isset($m['pwd']) ? 1 : 0;
        $is_solo   = isset($m['solo_parent']) ? 1 : 0;
        $age       = (int)($m['age'] ?? 0);
        $is_senior = ($age >= 60) ? 1 : 0;
        $is_minor  = ($age < 18) ? 1 : 0;

        $sql = "INSERT INTO residents (
                    last_name, first_name, middle_name, dob, gender, 
                    relationship, employment_status, civil_status, 
                    education, age, photo_path, household_no, status,
                    is_voter, is_4ps, is_pwd, is_solo, is_senior, is_minor
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssssissiiiiii", 
                $m['last_name'], $m['first_name'], $m['middle_name'], $m['dob'], $m['gender'],
                $m['relationship'], $m['employment'], $m['civil_status'], $m['education'], 
                $age, $current_photo_path, $safe_hh_no,
                $is_voter, $is_4ps, $is_pwd, $is_solo, $is_senior, $is_minor
            );
            $stmt->execute();
            $stmt->close();
        }
    }
    $redirect_url = 'household_members.php?household_no=' . rawurlencode($household_no) . '&success=residents_added';
    header("Location: " . $redirect_url);
    exit();
}
?>














