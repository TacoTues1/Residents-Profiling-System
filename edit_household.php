<?php
include('db.php');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.php");
    exit();
}

$household_no = $_GET['household_no'] ?? '';
$safe_hh_no = mysqli_real_escape_string($conn, $household_no);

$query = "SELECT * FROM households WHERE household_no = '$safe_hh_no' LIMIT 1";
$result = mysqli_query($conn, $query);
$household = $result ? mysqli_fetch_assoc($result) : null;

if (!$household) {
    echo "<script>alert('Household not found.'); window.location.href='residents.php';</script>";
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_household_no = trim($_POST['household_no'] ?? '');
    $survey_date = trim($_POST['survey_date'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $purok = trim($_POST['purok'] ?? '');
    $house = trim($_POST['house'] ?? '');
    $electricity = trim($_POST['electricity'] ?? '');
    $house_type = trim($_POST['house_type'] ?? '');
    $toilet = trim($_POST['toilet'] ?? '');
    $vulnerability = trim($_POST['vulnerability'] ?? '');
    $water = trim($_POST['water'] ?? '');

    if ($new_household_no === '') $errors[] = 'Household number is required.';
    if ($survey_date === '') $errors[] = 'Survey date is required.';
    if ($address === '') $errors[] = 'Address is required.';
    if ($purok === '') $errors[] = 'Purok is required.';

    if ($new_household_no !== $household['household_no']) {
        $check_stmt = $conn->prepare("SELECT id FROM households WHERE household_no = ? LIMIT 1");
        if ($check_stmt) {
            $check_stmt->bind_param('s', $new_household_no);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $errors[] = 'That household number is already used.';
            }
            $check_stmt->close();
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $household_id = (int)$household['id'];
            $old_household_no = $household['household_no'];

            $stmt = $conn->prepare(
                "UPDATE households
                 SET household_no = ?, survey_date = ?, address = ?, purok = ?, house = ?,
                     electricity = ?, house_type = ?, toilet = ?, vulnerability = ?, water = ?
                 WHERE id = ?"
            );

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                'ssssssssssi',
                $new_household_no,
                $survey_date,
                $address,
                $purok,
                $house,
                $electricity,
                $house_type,
                $toilet,
                $vulnerability,
                $water,
                $household_id
            );
            $stmt->execute();
            $stmt->close();

            if ($new_household_no !== $old_household_no) {
                $member_stmt = $conn->prepare("UPDATE residents SET household_no = ? WHERE household_no = ?");
                if (!$member_stmt) {
                    throw new Exception($conn->error);
                }
                $member_stmt->bind_param('ss', $new_household_no, $old_household_no);
                $member_stmt->execute();
                $member_stmt->close();
            }

            $action_desc = mysqli_real_escape_string($conn, "Updated household #$new_household_no");
            mysqli_query($conn, "INSERT INTO logs (action) VALUES ('$action_desc')");

            $conn->commit();
            header("Location: household_members.php?household_no=" . urlencode($new_household_no));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to update household. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Household</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --accent-blue: #2563eb; --text-gray: #64748b; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; height: 100vh; background: #f1f5f9; overflow: hidden; }
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .top-header { background: white; padding: 20px 40px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .content-body { padding: 16px 20px 20px; }
        .panel { background: white; border: 1px solid var(--border); padding: 30px; border-radius: 20px; max-width: 960px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-size: 13px; font-weight: 700; color: #334155; }
        input, select { padding: 12px; border: 1px solid #d1d5db; border-radius: 10px; font-family: inherit; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: var(--accent-blue); }
        .error-box { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
        .footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        .btn { padding: 12px 22px; border-radius: 12px; font-weight: 700; border: none; text-decoration: none; cursor: pointer; font-family: inherit; }
        .btn-cancel { background: #f1f5f9; color: #1e293b; }
        .btn-save { background: var(--accent-blue); color: white; }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header">
        <div>
            <h2 style="margin:0;">Edit Household</h2>
            <p style="margin:0; color: var(--text-gray);">Update household profile information</p>
        </div>
    </header>

    <div class="content-body">
        <div class="panel">
            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Household No.</label>
                        <input type="text" name="household_no" value="<?php echo htmlspecialchars($_POST['household_no'] ?? $household['household_no']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Survey</label>
                        <input type="date" name="survey_date" value="<?php echo htmlspecialchars($_POST['survey_date'] ?? $household['survey_date']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Complete Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? $household['address']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Purok</label>
                        <?php $purok_value = $_POST['purok'] ?? $household['purok']; ?>
                        <select name="purok" required>
                            <?php foreach (['Maya', 'Tikling', 'Perico', 'Salampati', 'Punay', 'Tamsi'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $purok_value === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>House</label>
                        <?php $house_value = $_POST['house'] ?? $household['house']; ?>
                        <select name="house">
                            <option value="Owned" <?php echo $house_value === 'Owned' ? 'selected' : ''; ?>>Owned</option>
                            <option value="Rented" <?php echo $house_value === 'Rented' ? 'selected' : ''; ?>>Rented</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Electricity</label>
                        <?php $electricity_value = $_POST['electricity'] ?? $household['electricity']; ?>
                        <select name="electricity">
                            <option value="Yes" <?php echo $electricity_value === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?php echo $electricity_value === 'No' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type of House</label>
                        <?php $house_type_value = $_POST['house_type'] ?? $household['house_type']; ?>
                        <select name="house_type">
                            <?php foreach (['Concrete', 'Semi-concrete', 'Light Materials'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $house_type_value === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Toilet</label>
                        <?php $toilet_value = $_POST['toilet'] ?? $household['toilet']; ?>
                        <select name="toilet">
                            <?php foreach (['Flushed', 'Non-flushed', 'No'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $toilet_value === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vulnerability</label>
                        <?php $vulnerability_value = $_POST['vulnerability'] ?? $household['vulnerability']; ?>
                        <select name="vulnerability">
                            <?php foreach (['Flood', 'Storm Surge', 'Fire'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $vulnerability_value === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Water Source</label>
                        <?php $water_value = $_POST['water'] ?? $household['water']; ?>
                        <select name="water">
                            <option value="Metro Dumaguete City Water District" <?php echo $water_value === 'Metro Dumaguete City Water District' ? 'selected' : ''; ?>>Metro Dumaguete City Water District</option>
                            <option value="Deep Well" <?php echo $water_value === 'Deep Well' ? 'selected' : ''; ?>>Deep Well</option>
                        </select>
                    </div>
                </div>

                <div class="footer">
                    <a href="household_members.php?household_no=<?php echo urlencode($household['household_no']); ?>" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-save">Save Household</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
