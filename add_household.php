<?php 
include('db.php');
session_start();

// Security check: Only Secretary role
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Household</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --accent-blue: #2563eb; --text-gray: #64748b; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .modal-container { background: white; border: 1px solid #e5e7eb; width: 800px; padding: 30px; border-radius: 24px;  }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 10px; box-sizing: border-box; font-family: inherit; }
        /* Improvement for placeholder visibility */
        .form-group input::placeholder { color: #9ca3af; font-weight: 400; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn-cancel { background: #e2e8f0; padding: 12px 25px; border-radius: 12px; border: none; cursor: pointer; text-decoration: none; color: black; font-weight: 500; }
        .btn-next { background: var(--accent-blue); color: white; padding: 12px 40px; border-radius: 12px; border: none; cursor: pointer; font-weight: 600; }
        
        /* Dark Mode Overrides */
        body.dark-mode { background: #0f172a; color: white; }
        body.dark-mode .modal-container { background: #1e293b; border-color: #334155; }
        body.dark-mode h2 { color: white !important; }
        body.dark-mode p { color: #94a3b8 !important; }
        body.dark-mode .form-group label { color: #cbd5e1; }
        body.dark-mode input, body.dark-mode select { background: #0f172a; border-color: #334155; color: white; }
        body.dark-mode .form-group input::placeholder { color: #475569; }
        body.dark-mode .modal-footer { border-top-color: #334155; }
        body.dark-mode .btn-cancel { background: #334155; color: white; }
    </style>
</head>
<body>
<script>
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>

<div class="modal-container">
    <h2 style="margin-top:0;">Add New Household</h2>
    <p style="color:var(--text-gray);">Step 1: Fill in the household information</p>
    
    <form action="save_household_session.php" method="POST">
        <div class="modal-grid">
            <div class="form-group">
                <label>Household No. *</label>
                <input type="text" name="hh_no" required>
            </div>
            <div class="form-group">
                <label>Date of Survey</label>
                <input type="date" name="survey_date" required>
            </div>
            
            <div class="form-group">
                <label>Complete Address *</label>
                <input type="text" name="address" placeholder="e.g., Block 1, Lot 5" required>
            </div>
            
            <div class="form-group">
                <label>Purok *</label>
                <select name="purok" required>
                    <option value="" disabled selected>Select Purok</option>
                    <option value="Maya">Maya</option>
                    <option value="Tikling">Tikling</option>
                    <option value="Perico">Perico</option>
                    <option value="Salampati">Salampati</option>
                    <option value="Punay">Punay</option>
                    <option value="Tamsi">Tamsi</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>House *</label>
                <select name="house">
                    <option value="Owned">Owned</option>
                    <option value="Rented">Rented</option>
                </select>
            </div>
            <div class="form-group">
                <label>Electricity? *</label>
                <select name="electricity">
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>
            <div class="form-group">
                <label>Type of House *</label>
                <select name="house_type">
                    <option value="Concrete">Concrete</option>
                    <option value="Semi-concrete">Semi-concrete</option>
                    <option value="Light Materials">Light Materials</option>
                </select>
            </div>
            <div class="form-group">
                <label>Toilet *</label>
                <select name="toilet">
                    <option value="Flushed">Flushed</option>
                    <option value="Non-flushed">Non-flushed</option>
                    <option value="No">No</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vulnerability *</label>
                <select name="vulnerability">
                    <option value="Flood">Flood</option>
                    <option value="Storm Surge">Storm Surge</option>
                    <option value="Fire">Fire</option>
                </select>
            </div>
            <div class="form-group">
                <label>Water Source</label>
                <select name="water">
                    <option value="Metro Dumaguete City Water District">Metro Dumaguete City Water District</option>
                    <option value="Deep Well">Deep Well</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <a href="residents.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-next">Next</button>
        </div>
    </form>
</div>

</body>
</html>














