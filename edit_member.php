<?php
include('db.php');
session_start();

// Security check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.php");
    exit();
}

$member_id = $_GET['id'] ?? '';
$safe_id = mysqli_real_escape_string($conn, $member_id);

$query = "SELECT * FROM residents WHERE id = '$safe_id'";
$result = mysqli_query($conn, $query);
$member = mysqli_fetch_assoc($result);

if (!$member) {
    echo "<script>alert('Member not found.'); window.history.back();</script>";
    exit();
}

// UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $photo_update_sql = "";
    if (!empty($_POST['photo'])) {
        $data = $_POST['photo'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = base64_decode(substr($data, strpos($data, ',') + 1));
            $filename = "res_" . time() . "_" . $safe_id . ".jpg";
            $filepath = "uploads/" . $filename;
            if (file_put_contents($filepath, $data)) { 
                $photo_update_sql = "photo_path='$filepath', "; 
            }
        }
    }

    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $mname = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $age   = mysqli_real_escape_string($conn, $_POST['age']);
    $sex   = mysqli_real_escape_string($conn, $_POST['sex']);
    $dob   = mysqli_real_escape_string($conn, $_POST['dob']);
    $civil_status = mysqli_real_escape_string($conn, $_POST['civil_status']);
    $employment   = mysqli_real_escape_string($conn, $_POST['employment_status']);
    $education    = mysqli_real_escape_string($conn, $_POST['education']);
    $relationship = mysqli_real_escape_string($conn, $_POST['relationship']);
    $allowed_statuses = ['Active', 'Transferred', 'Deceased'];
    $status = $_POST['status'] ?? 'Active';
    if (!in_array($status, $allowed_statuses, true)) {
        $status = 'Active';
    }
    $safe_status = mysqli_real_escape_string($conn, $status);

    // Checkboxes mapped to your ACTUAL database columns (is_solo)
    $is_voter  = isset($_POST['is_voter']) ? 1 : 0;
    $is_4ps    = isset($_POST['is_4ps']) ? 1 : 0;
    $is_pwd    = isset($_POST['is_pwd']) ? 1 : 0;
    $is_solo   = isset($_POST['is_solo']) ? 1 : 0; 
    
    // Auto enforce senior and minor based on age
    $age_int   = (int)$age;
    $is_senior = ($age_int >= 60) ? 1 : 0;
    $is_minor  = ($age_int < 18) ? 1 : 0;

    // Auto-archive if status is Deceased or Transferred
    $is_archived = 0;
    $archive_reason = '';
    if ($status === 'Deceased') {
        $is_archived = 1;
        $archive_reason = 'Deceased';
    } elseif ($status === 'Transferred') {
        $is_archived = 1;
        $archive_reason = 'Transferred to Another Location';
    }
    $safe_archive_reason = mysqli_real_escape_string($conn, $archive_reason);

    $update_query = "UPDATE residents SET 
        $photo_update_sql
        last_name='$lname', first_name='$fname', middle_name='$mname', 
        age='$age', gender='$sex', dob='$dob', civil_status='$civil_status', 
        employment_status='$employment', education='$education', relationship='$relationship',
        is_voter='$is_voter', is_4ps='$is_4ps', is_pwd='$is_pwd', 
        is_solo='$is_solo', is_senior='$is_senior', is_minor='$is_minor',
        status='$safe_status', is_archived='$is_archived', archive_reason='$safe_archive_reason'
        WHERE id='$safe_id'";

    if (mysqli_query($conn, $update_query)) {
        if ($is_archived) {
            echo "<script>alert('Resident marked as $safe_status and automatically archived.'); window.location.href='household_members.php?household_no=" . $member['household_no'] . "';</script>";
        } else {
            echo "<script>alert('Information Updated Successfully!'); window.location.href='household_members.php?household_no=" . $member['household_no'] . "';</script>";
        }
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Resident Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text: #1e293b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px; }

        .edit-card {
            background: white;
            width: 100%;
            max-width: 1050px;
            border-radius: 24px;
            
            padding: 40px;
        }

        .header {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 30px;
            padding-bottom: 15px;
        }

        .header h2 { margin: 0; font-size: 24px; font-weight: 800; color: var(--text); }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 14px; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .span-2 { grid-column: span 2; }
        .span-1 { grid-column: span 1; }

        label {
            font-size: 12px;
            font-weight: 700;
            text-
            color: #64748b;
            letter-spacing: 0.5px;
        }

        input, select {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background-color: #f1f5f9;
            font-size: 14px;
            
        }

        input:focus, select:focus {
            outline: none;
            background-color: white;
            border-color: var(--primary);
            
        }

        .benefits-title {
            grid-column: span 3;
            font-size: 14px;
            font-weight: 800;
            color: var(--text);
            margin-top: 20px;
            text-
        }

        .checkbox-container {
            grid-column: span 3;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .check-item input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-size: 14px;
            
        }

        .btn-cancel { background: #f1f5f9; color: var(--text); }
        .btn-save { background: var(--primary); color: white; }

        .photo-wrap { display: flex; flex-direction: column; gap: 12px; align-items: center; }
        .capture-box {
            width: 180px; height: 220px; background: #f1f5f9; border: 2px dashed #d1d5db;
            border-radius: 14px; display: flex; flex-direction: column; align-items: center;
            justify-content: center; cursor: pointer; color: #4b5563; overflow: hidden;
        }
        .capture-box video, .capture-box img { width: 100%; height: 100%; object-fit: cover; }
        .btn-upload-small {
            width: 180px; background: var(--primary); color: white; border: none;
            border-radius: 10px; padding: 10px; font-weight: 800; font-size: 11px;
            cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-sizing: border-box;
        }

        /* Dark Mode Overrides */
        body.dark-mode { background-color: #0f172a; color: white; }
        body.dark-mode .edit-card { background: #1e293b; border-color: #334155; }
        body.dark-mode .header { border-bottom-color: #334155; }
        body.dark-mode .header h2 { color: white !important; }
        body.dark-mode .header p { color: #94a3b8 !important; }
        body.dark-mode label { color: #cbd5e1; }
        body.dark-mode input, body.dark-mode select { background-color: #0f172a; border-color: #334155; color: white; }
        body.dark-mode input:focus, body.dark-mode select:focus { background-color: #0f172a; border-color: var(--primary); }
        body.dark-mode .benefits-title { color: white !important; }
        body.dark-mode .checkbox-container { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        body.dark-mode .footer { border-top-color: #334155; }
        body.dark-mode .btn-cancel { background: #334155; color: white; }
        body.dark-mode .capture-box { background: #0f172a; border-color: #334155; color: #94a3b8; }

    </style>
</head>
<body>
<script>
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>

<div class="edit-card">
    <div class="header">
        <h2>Edit Household Member</h2>
        <p>Manage profile for: <strong><?php echo htmlspecialchars($member['first_name'] . " " . $member['last_name']); ?></strong></p>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 30px;">
            <div class="photo-wrap">
                <div class="capture-box" id="preview-box" onclick="<?php echo empty($member['photo_path']) ? 'startCamera()' : ''; ?>">
                    <?php if (!empty($member['photo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($member['photo_path']); ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-camera" style="font-size:32px; margin-bottom:10px; color: #64748b;"></i>
                        <span style="font-size:11px; font-weight:800;">Click to Capture</span>
                    <?php endif; ?>
                </div>
                <div id="camera-controls" style="display:none; width:180px; gap:8px;">
                    <button type="button" class="btn-capture" onclick="capturePhoto()" style="flex:1; background:#10b981; color:white; border:none; padding:8px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:13px;"><i class="fa-solid fa-camera"></i> Capture</button>
                    <button type="button" class="btn-cancel-cam" onclick="cancelCamera()" style="background:#ef4444; color:white; border:none; padding:8px 14px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:13px;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div id="recapture-controls" style="display:<?php echo !empty($member['photo_path']) ? 'block' : 'none'; ?>; width:180px;">
                    <button type="button" class="btn-recapture" onclick="startCamera()" style="width:100%; background:#f59e0b; color:white; border:none; padding:8px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:13px;"><i class="fa-solid fa-rotate"></i> Recapture</button>
                </div>
                <label class="btn-upload-small" id="upload-label" style="display:inline-flex;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload Photo
                    <input type="file" accept="image/*" style="display:none" onchange="handleFileUpload(this)">
                </label>
                <input type="hidden" name="photo" id="photo-data">
            </div>

            <div class="inputs-column">
                <div class="form-grid">
            <div class="form-group span-1">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($member['last_name']); ?>" required>
            </div>
            <div class="form-group span-1">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
            </div>
            <div class="form-group span-1">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($member['middle_name']); ?>">
            </div>
            <div class="form-group span-1">
                <label>Age</label>
                <input type="number" name="age" id="editAge" value="<?php echo htmlspecialchars($member['age']); ?>" required oninput="updateClassificationFromAge(this.value)">
            </div>

            <div class="form-group span-1">
                <label>Sex</label>
                <select name="sex">
                    <option value="Male" <?php echo ($member['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($member['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="form-group span-1">
                <label>Birth Date</label>
                <input type="date" name="dob" id="editDob" value="<?php echo $member['dob']; ?>" required onchange="calculateEditAge(this.value)">
            </div>
            <div class="form-group span-2">
                <label>Civil Status</label>
                <select name="civil_status">
                    <option value="Single" <?php echo ($member['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                    <option value="Married" <?php echo ($member['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                    <option value="Widowed" <?php echo ($member['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                </select>
            </div>

            <div class="form-group span-1">
                <label>Employment</label>
                <select name="employment_status">
                    <option value="Yes" <?php echo ($member['employment_status'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($member['employment_status'] == 'No') ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
            <div class="form-group span-2">
                <label>Education</label>
                <select name="education">
                    <option value="Elementary" <?php echo ($member['education'] == 'Elementary') ? 'selected' : ''; ?>>Elementary</option>
                    <option value="High School" <?php echo ($member['education'] == 'High School') ? 'selected' : ''; ?>>High School</option>
                    <option value="College" <?php echo ($member['education'] == 'College') ? 'selected' : ''; ?>>College</option>
                </select>
            </div>
            <div class="form-group span-1">
                <label>Relationship</label>
                <select name="relationship">
                    <option value="Head" <?php echo ($member['relationship'] == 'Head') ? 'selected' : ''; ?>>Head</option>
                    <option value="Spouse" <?php echo ($member['relationship'] == 'Spouse') ? 'selected' : ''; ?>>Spouse</option>
                    <option value="Child" <?php echo ($member['relationship'] == 'Child') ? 'selected' : ''; ?>>Child</option>
                </select>
            </div>
            <div class="form-group span-1">
                <label>Resident Status</label>
                <select name="status">
                    <option value="Active" <?php echo (($member['status'] ?? 'Active') == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Transferred" <?php echo (($member['status'] ?? '') == 'Transferred') ? 'selected' : ''; ?>>Transferred</option>
                    <option value="Deceased" <?php echo (($member['status'] ?? '') == 'Deceased') ? 'selected' : ''; ?>>Deceased</option>
                </select>
            </div>

            <div class="benefits-title">Categories & Benefits</div>
            <div class="checkbox-container">
                <label class="check-item"><input type="checkbox" name="is_voter" <?php echo $member['is_voter'] ? 'checked' : ''; ?>> Registered Voter</label>
                <label class="check-item"><input type="checkbox" name="is_pwd" <?php echo $member['is_pwd'] ? 'checked' : ''; ?>> PWD</label>
                <label class="check-item"><input type="checkbox" name="is_solo" <?php echo $member['is_solo'] ? 'checked' : ''; ?>> Solo Parent</label>
                <label class="check-item"><input type="checkbox" name="is_senior" id="editSeniorCheck" <?php echo $member['is_senior'] ? 'checked' : ''; ?>> Senior Citizen</label>
                <label class="check-item"><input type="checkbox" name="is_minor" id="editMinorCheck" <?php echo $member['is_minor'] ? 'checked' : ''; ?>> Minor</label>
            </div>
            </div>
        </div>
    </div>

    <div class="footer">
            <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancel</button>
            <button type="submit" class="btn btn-save">Save Changes</button>
        </div>
    </form>
</div>

<script>
    function calculateEditAge(dobVal) {
        if (!dobVal) return;
        const birth = new Date(dobVal);
        const now = new Date();
        let age = now.getFullYear() - birth.getFullYear();
        if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) age--;
        age = age >= 0 ? age : 0;
        
        document.getElementById('editAge').value = age;
        updateClassificationFromAge(age);
    }

    function updateClassificationFromAge(ageVal) {
        const age = parseInt(ageVal, 10) || 0;
        document.getElementById('editSeniorCheck').checked = (age >= 60);
        document.getElementById('editMinorCheck').checked = (age < 18);
    }

    let cameraStream = null;

    function handleFileUpload(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewBox = document.getElementById('preview-box');
                document.getElementById('photo-data').value = e.target.result;
                previewBox.innerHTML = `<img src="${e.target.result}">`;
                previewBox.onclick = null;

                document.getElementById('recapture-controls').style.display = 'block';
                document.getElementById('upload-label').style.display = 'inline-flex';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function startCamera() {
        const div = document.getElementById('preview-box');
        const camControls = document.getElementById('camera-controls');
        const recapControls = document.getElementById('recapture-controls');

        div.onclick = null;

        const video = document.createElement('video');
        video.setAttribute('playsinline', '');
        video.setAttribute('autoplay', '');
        div.innerHTML = ''; 
        div.appendChild(video);
        
        navigator.mediaDevices.getUserMedia({ video: true }).then(s => {
            video.srcObject = s; 
            video.play();
            cameraStream = s;
            
            camControls.style.display = 'flex';
            recapControls.style.display = 'none';
        }).catch(err => {
            alert("Camera access denied.");
            div.innerHTML = `<i class="fa-solid fa-camera-slash" style="font-size:32px;"></i>`;
            div.onclick = startCamera;
        });
    }

    function capturePhoto() {
        const div = document.getElementById('preview-box');
        const video = div.querySelector('video');
        if (!video) return;

        const canvas = document.createElement('canvas');
        canvas.width = 400; canvas.height = 500;
        canvas.getContext('2d').drawImage(video, 0, 0, 400, 500);
        const data = canvas.toDataURL('image/jpeg');

        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }

        div.innerHTML = `<img src="${data}">`;
        document.getElementById('photo-data').value = data;

        document.getElementById('camera-controls').style.display = 'none';
        document.getElementById('recapture-controls').style.display = 'block';
    }

    function cancelCamera() {
        const div = document.getElementById('preview-box');
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }

        document.getElementById('photo-data').value = '';
        const existingPhoto = <?php echo json_encode(!empty($member['photo_path']) ? $member['photo_path'] : ''); ?>;
        if (existingPhoto) {
            div.innerHTML = `<img src="${existingPhoto}">`;
            document.getElementById('recapture-controls').style.display = 'block';
        } else {
            div.innerHTML = `
                <i class="fa-solid fa-camera" style="font-size:32px; margin-bottom:10px; color: #64748b;"></i>
                <span style="font-size:11px; font-weight:800;">Click to Capture</span>
            `;
            div.onclick = startCamera;
            document.getElementById('recapture-controls').style.display = 'none';
        }

        document.getElementById('camera-controls').style.display = 'none';
    }
</script>
</body>
</html>














