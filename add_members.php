<?php
session_start();
include('db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.php");
    exit();
}

$came_from_household_profile = isset($_GET['household_no']) && trim($_GET['household_no']) !== '';
$requested_household_no = $_GET['household_no'] ?? ($_SESSION['current_household_no'] ?? '');
$requested_household_no = trim($requested_household_no);

if ($requested_household_no === '') {
    header("Location: add_household.php?error=session_expired");
    exit();
}

$safe_hh_no = mysqli_real_escape_string($conn, $requested_household_no);
$household_query = mysqli_query($conn, "SELECT household_no FROM households WHERE household_no = '$safe_hh_no' LIMIT 1");
$household = $household_query ? mysqli_fetch_assoc($household_query) : null;

if (!$household) {
    echo "<script>alert('Household not found.'); window.location.href='residents.php';</script>";
    exit();
}

$hh_no = $household['household_no'];
$_SESSION['current_household_no'] = $hh_no;
$previous_url = $came_from_household_profile ? ('household_members.php?household_no=' . urlencode($hh_no)) : 'add_household.php';

$last_name_suggestions = [];
$suggestion_query = mysqli_query($conn, "SELECT DISTINCT last_name FROM residents WHERE household_no = '$safe_hh_no' AND COALESCE(is_archived, 0) = 0 AND last_name <> '' ORDER BY last_name ASC");
if ($suggestion_query) {
    while ($row = mysqli_fetch_assoc($suggestion_query)) {
        $last_name_suggestions[] = $row['last_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Profiling - Add Members</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --accent-blue: #2563eb; 
            --accent-blue-hover: #1d4ed8;
            --page-bg: #f1f5f9; 
            --card-bg: #ffffff;
            --text-gray: #64748b;
            --
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--page-bg); 
            margin: 0; 
            padding: 0; 
            display: flex;
            justify-content: flex-start;
            height: 100vh;
            overflow: hidden; }

        .main-container { 
            flex: 1;
            height: calc(100vh - 32px);
            background: var(--card-bg); 
            border-radius: 24px; 
            margin: 16px;
            overflow-y: auto;
            border: 0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .content-body { padding: 40px; }

        .btn-add-member, .btn-upload-small, .btn-prev, .btn-save {
            
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            outline: none;
        }

        .btn-add-member:active, .btn-upload-small:active, .btn-prev:active, .btn-save:active {
            
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
        }

        .btn-add-member {
            background: #f1f5f9;
            border: 2px solid #000;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 800;
            text-
            font-size: 12px;
            color: #000;
        }



        .member-card {
            background: #f1f5f9;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }

        .member-title {
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 25px;
            color: var(--accent-blue);
            text-
            letter-spacing: 0.5px;
            border-left: 4px solid var(--accent-blue);
            padding-left: 15px;
        }

        .layout-grid { 
            display: grid; 
            grid-template-columns: 200px 1fr; 
            gap: 30px; 
        }

        .photo-wrap { display: flex; flex-direction: column; gap: 12px; align-items: center; }
        
        .capture-box {
            width: 180px;
            height: 220px;
            background: #f1f5f9;
            border: 2px dashed #d1d5db;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #4b5563;
            overflow: hidden;
            
        }



        .capture-box video, .capture-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-upload-small {
            width: 180px;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px;
            font-weight: 800;
            font-size: 11px;
            text-
        }



        .input-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 20px; 
        }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        
        .form-group label, .benefits-section h5 { 
            font-size: 13px; 
            font-weight: 800; 
            color: #374151; 
            text-
            margin: 0;
        }
        
        .form-group input, .form-group select { 
            width: 100%;
            background: #f1f5f9; 
            border: 1px solid #d1d5db; 
            padding: 12px; 
            border-radius: 10px; 
            box-sizing: border-box;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            color: #000;
            
        }

        .form-group input.age-input {
            background: #f1f5f9;
            cursor: default;
        }

        .suggestion-wrap {
            position: relative;
            width: 100%;
        }

        .suggestion-list {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 30;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
        }

        .suggestion-list[hidden] {
            display: none;
        }

        .suggestion-option {
            width: 100%;
            padding: 10px 12px;
            border: 0;
            background: #fff;
            text-align: left;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        .suggestion-option:hover,
        .suggestion-option:focus {
            background: #eff6ff;
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border: 2px solid var(--accent-blue);
            
        }

        /* Adjusted width for dual column rows if needed */
        .col-span-2 { grid-column: span 2; }

        .benefits-section { 
            margin-top: 30px; 
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }

        .benefits-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px; 
            margin-top: 15px;
        }
        
        .check-item { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-size: 12px; 
            font-weight: 800; 
            color: #374151; 
            text- 
            cursor: pointer;
            
        }



        .check-item input { 
            width: 18px; 
            height: 18px; 
            accent-color: var(--accent-blue);
        }

        .footer-nav {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding: 30px 40px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .btn-prev {
            background: white;
            border: 2px solid #d1d5db;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 16px;
            text-decoration: none;
            color: #4b5563;
        }



        .btn-save {
            background: var(--accent-blue);
            color: white;
            border: none;
            padding: 12px 50px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 16px;
        }

        /* Dark Mode Overrides */
        body.dark-mode .main-container { background: #0f172a; }
        body.dark-mode .section-header { border-bottom-color: #334155; }
        body.dark-mode h1 { color: white !important; }
        body.dark-mode .member-card { background: #1e293b; border-color: #334155; }
        body.dark-mode .capture-box { background: #0f172a; border-color: #334155; color: #94a3b8; }
        body.dark-mode input, body.dark-mode select { background: #0f172a !important; border-color: #334155 !important; color: white !important; }
        body.dark-mode .form-group label, body.dark-mode .benefits-section h5 { color: #cbd5e1; }
        body.dark-mode .check-item { color: #e2e8f0; }
        body.dark-mode .footer-nav { background: #1e293b; border-top-color: #334155; }
        body.dark-mode .btn-prev { background: #0f172a; color: white; border-color: #334155; }
        body.dark-mode .btn-add-member { background: #0f172a; color: white; border-color: #334155; }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <div class="content-body">
        <div class="section-header">
            <div>
                <h1 style="margin:0; font-size:22px; font-weight:800;">Residents Profile</h1>
                <p style="margin:5px 0 0; font-size:14px; color: var(--text-gray);">Step 2: Add all individuals residing in Household #<?php echo htmlspecialchars($hh_no); ?></p>
            </div>
            <button type="button" class="btn-add-member" onclick="addMember()">
                <i class="fa-solid fa-user-plus"></i> Add Another Member
            </button>
        </div>

        <form action="process_members.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="household_no" value="<?php echo htmlspecialchars($hh_no); ?>">
            <div id="members-container"></div>
            <div class="footer-nav">
                <a href="<?php echo htmlspecialchars($previous_url); ?>" class="btn-prev">
                    <i class="fa-solid fa-arrow-left"></i> Previous
                </a>
                <button type="submit" class="btn-save">Save All Records</button>
            </div>
        </form>
    </div>
</div>

<script>
    let mIdx = 0;
    const lastNameSuggestions = <?php echo json_encode($last_name_suggestions); ?>;

    function addMember() {
        const container = document.getElementById('members-container');
        const card = document.createElement('div');
        card.className = 'member-card';
        card.innerHTML = `
            <div class="member-title">Member Information - Entry #${mIdx + 1}</div>
            <div class="layout-grid">
                <div class="photo-wrap">
                    <div class="capture-box" id="preview-${mIdx}" onclick="startCamera(${mIdx})">
                        <i class="fa-solid fa-camera" style="font-size:32px; margin-bottom:10px; color: #64748b;"></i>
                        <span style="font-size:11px; font-weight:800;">Click to Capture</span>
                    </div>
                    <div id="camera-controls-${mIdx}" style="display:none; width:180px; gap:8px;">
                        <button type="button" class="btn-capture" onclick="capturePhoto(${mIdx})" style="flex:1; background:#10b981; color:white; border:none; padding:8px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:13px;"><i class="fa-solid fa-camera"></i> Capture</button>
                        <button type="button" class="btn-cancel-cam" onclick="cancelCamera(${mIdx})" style="background:#ef4444; color:white; border:none; padding:8px 14px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:13px;"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div id="recapture-controls-${mIdx}" style="display:none; width:180px;">
                        <button type="button" class="btn-recapture" onclick="startCamera(${mIdx})" style="width:100%; background:#f59e0b; color:white; border:none; padding:8px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:13px;"><i class="fa-solid fa-rotate"></i> Recapture</button>
                    </div>
                    <label class="btn-upload-small" id="upload-label-${mIdx}">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Photo
                        <input type="file" accept="image/*" style="display:none" onchange="handleFileUpload(this, ${mIdx})">
                    </label>
                    <input type="hidden" name="members[${mIdx}][photo]" id="photo-data-${mIdx}">
                </div>

                <div class="inputs-column">
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Last Name *</label>
                            <div class="suggestion-wrap">
                                <input type="text" class="last-name-input" name="members[${mIdx}][last_name]" autocomplete="off" required>
                                <div class="suggestion-list" hidden></div>
                            </div>
                        </div>
                        <div class="form-group"><label>First Name *</label><input type="text" name="members[${mIdx}][first_name]" required></div>
                        <div class="form-group"><label>Middle Name</label><input type="text" name="members[${mIdx}][middle_name]"></div>
                        
                        <div class="form-group"><label>Suffix Name</label><input type="text" name="members[${mIdx}][suffix_name]" placeholder="e.g. Jr., Sr., III"></div>
                        <div class="form-group"><label>Gender *</label>
                            <select name="members[${mIdx}][gender]" required>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Date of Birth *</label><input type="date" name="members[${mIdx}][dob]" required onchange="calcAge(this, ${mIdx})"></div>
                        
                        <div class="form-group"><label>Age</label><input type="number" name="members[${mIdx}][age]" class="age-input" readonly tabindex="-1"></div>
                        <div class="form-group"><label>Relationship to Head *</label>
                            <select name="members[${mIdx}][relationship]" required>
                                <option value="" disabled>Select Relationship</option>
                                <option value="Head" ${mIdx === 0 ? 'selected' : ''}>Head of Family</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Relative">Other Relative</option>
                                <option value="Non-relative">Non-relative</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Civil Status *</label>
                            <select name="members[${mIdx}][civil_status]" required>
                                <option value="" disabled selected>Select Status</option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Widowed</option>
                                <option>Separated</option>
                            </select>
                        </div>

                        <div class="form-group"><label>Employment Status</label>
                            <select name="members[${mIdx}][employment]" required>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="form-group col-span-2"><label>Educational Attainment *</label>
                            <select name="members[${mIdx}][education]" required>
                                <option value="" disabled selected>Select Level</option>
                                <option>Elementary</option>
                                <option>High School</option>
                                <option>Vocational</option>
                                <option>College </option>
                                <option>Post-Graduate</option>
                                <option>None / Child</option>
                            </select>
                        </div>
                    </div>

                    <div class="benefits-section">
                        <h5>Special Classifications</h5>
                        <div class="benefits-grid">
                            <label class="check-item"><input type="checkbox" name="members[${mIdx}][voter]" value="1"> Registered Voter</label>
                            <label class="check-item"><input type="checkbox" name="members[${mIdx}][pwd]" value="1"> PWD</label>
                            <label class="check-item"><input type="checkbox" name="members[${mIdx}][solo_parent]" value="1"> Solo Parent</label>
                            <label class="check-item"><input type="checkbox" name="members[${mIdx}][senior]" class="senior-check" value="1"> Senior Citizen</label>
                            <label class="check-item"><input type="checkbox" name="members[${mIdx}][minor]" class="minor-check" value="1"> Minor</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(card);
        setupLastNameSuggestions(card);
        mIdx++;
    }

    function setupLastNameSuggestions(card) {
        const input = card.querySelector('.last-name-input');
        const list = card.querySelector('.suggestion-list');
        if (!input || !list || lastNameSuggestions.length === 0) return;

        const showSuggestions = () => {
            const term = input.value.trim().toLowerCase();
            const matches = lastNameSuggestions.filter((name) => name.toLowerCase().includes(term));

            list.innerHTML = '';
            matches.forEach((name) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'suggestion-option';
                option.textContent = name;
                option.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    input.value = name;
                    list.hidden = true;
                });
                list.appendChild(option);
            });

            list.hidden = matches.length === 0;
        };

        input.addEventListener('focus', showSuggestions);
        input.addEventListener('click', showSuggestions);
        input.addEventListener('input', showSuggestions);
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.suggestion-wrap')) {
                list.hidden = true;
            }
        });
    }

    function handleFileUpload(input, index) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewBox = document.getElementById(`preview-${index}`);
                document.getElementById(`photo-data-${index}`).value = e.target.result;
                previewBox.innerHTML = `<img src="${e.target.result}">`;
                previewBox.onclick = null;

                document.getElementById(`recapture-controls-${index}`).style.display = 'block';
                document.getElementById(`upload-label-${index}`).style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    let cameraStreams = {};

    function calcAge(input, index) {
        const card = input.closest('.member-card');
        const ageInp = card.querySelector('.age-input');
        const seniorCheck = card.querySelector('.senior-check');
        const minorCheck = card.querySelector('.minor-check');
        
        const birth = new Date(input.value);
        const now = new Date();
        let age = now.getFullYear() - birth.getFullYear();
        if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) age--;
        
        age = age >= 0 ? age : 0;
        ageInp.value = age;

        seniorCheck.checked = (age >= 60);
        minorCheck.checked = (age < 18);
    }

    function startCamera(index) {
        const div = document.getElementById(`preview-${index}`);
        const camControls = document.getElementById(`camera-controls-${index}`);
        const recapControls = document.getElementById(`recapture-controls-${index}`);
        const uploadLabel = document.getElementById(`upload-label-${index}`);

        div.onclick = null;

        const video = document.createElement('video');
        video.setAttribute('playsinline', '');
        video.setAttribute('autoplay', '');
        div.innerHTML = ''; 
        div.appendChild(video);
        
        navigator.mediaDevices.getUserMedia({ video: true }).then(s => {
            video.srcObject = s; 
            video.play();
            cameraStreams[index] = s;
            
            camControls.style.display = 'flex';
            recapControls.style.display = 'none';
            uploadLabel.style.display = 'none';
        }).catch(err => {
            alert("Camera access denied.");
            div.innerHTML = `<i class="fa-solid fa-camera-slash" style="font-size:32px;"></i>`;
            div.onclick = () => startCamera(index);
        });
    }

    function capturePhoto(index) {
        const div = document.getElementById(`preview-${index}`);
        const video = div.querySelector('video');
        if (!video) return;

        const canvas = document.createElement('canvas');
        canvas.width = 400; canvas.height = 500;
        canvas.getContext('2d').drawImage(video, 0, 0, 400, 500);
        const data = canvas.toDataURL('image/jpeg');

        if (cameraStreams[index]) {
            cameraStreams[index].getTracks().forEach(t => t.stop());
            delete cameraStreams[index];
        }

        div.innerHTML = `<img src="${data}">`;
        document.getElementById(`photo-data-${index}`).value = data;

        document.getElementById(`camera-controls-${index}`).style.display = 'none';
        document.getElementById(`recapture-controls-${index}`).style.display = 'block';
        document.getElementById(`upload-label-${index}`).style.display = 'none';
    }

    function cancelCamera(index) {
        const div = document.getElementById(`preview-${index}`);
        if (cameraStreams[index]) {
            cameraStreams[index].getTracks().forEach(t => t.stop());
            delete cameraStreams[index];
        }

        document.getElementById(`photo-data-${index}`).value = '';
        div.innerHTML = `
            <i class="fa-solid fa-camera" style="font-size:32px; margin-bottom:10px; color: #64748b;"></i>
            <span style="font-size:11px; font-weight:800;">Click to Capture</span>
        `;
        div.onclick = () => startCamera(index);

        document.getElementById(`camera-controls-${index}`).style.display = 'none';
        document.getElementById(`recapture-controls-${index}`).style.display = 'none';
        document.getElementById(`upload-label-${index}`).style.display = 'flex';
    }

    addMember();
</script>
</body>
</html>














