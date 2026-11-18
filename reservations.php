<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'db.php';

$types = $pdo->query("SELECT * FROM room_types")->fetchAll();
$available_rooms = [];
$searched = false;
$msg = '';

$check_in  = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';
$type_id   = $_POST['type_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searched = true;
    if ($check_in >= $check_out) {
        $msg = "Error: Check-out date must be strictly after the check-in date.";
    } else {
        // SQL Date Overlap Query from Chapter 4 of the Write-up
        $sql = "
            SELECT r.room_id, r.room_number 
            FROM rooms r 
            WHERE r.type_id = ? AND r.status <> 'maintenance'
            AND r.room_id NOT IN (
                SELECT room_id FROM reservations 
                WHERE status IN ('Confirmed', 'Checked-in')
                AND check_in < ? AND check_out > ?
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type_id, $check_out, $check_in]);
        $available_rooms = $stmt->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $guest_name       = trim($_POST['guest_name']);
    $guest_phone      = trim($_POST['guest_phone']);
    $guest_email      = trim($_POST['guest_email']);
    $selected_room_id = (int)$_POST['room_id'];

    $g_stmt = $pdo->prepare("INSERT INTO guests (full_name, phone, email) VALUES (?, ?, ?)");
    $g_stmt->execute([$guest_name, $guest_phone, $guest_email]);
    $guest_id = $pdo->lastInsertId();

    $res_stmt = $pdo->prepare("
        INSERT INTO reservations (guest_id, room_id, created_by, check_in, check_out, status) 
        VALUES (?, ?, ?, ?, ?, 'Confirmed')
    ");
    $res_stmt->execute([$guest_id, $selected_room_id, $_SESSION['user_id'], $check_in, $check_out]);

    $msg = "Success: Room booked successfully! Returning to dashboard.";
    header("refresh:2;url=dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Availability Search - UI Hotels</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; }
        .header { background: #002147; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f2a900; }
        .header a { color: #f2a900; text-decoration: none; font-weight: bold; font-size: 14px; }
        .container { max-width: 800px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        h2 { color: #002147; margin-top: 0; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        input, select { width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn-primary { background: #002147; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .alert { background: #e0f2fe; color: #0369a1; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .room-box { border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; margin-top: 20px; background: #f8fafc; }
    </style>
</head>
<body>
    <div class="header">
        <h2>UI HOTELS — AVAILABILITY SEARCH</h2>
        <a href="dashboard.php">&larr; Return to Dashboard</a>
    </div>
    <div class="container">
        <h2>Query Real-Time Room Availability</h2>
        <?php if (!empty($msg)): ?>
            <div class="alert"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Room Category</label>
                    <select name="type_id" required>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo $t['type_id']; ?>" <?php if ($type_id == $t['type_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($t['type_name']); ?> (₦<?php echo number_format($t['rate_per_night'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Check-in Date</label>
                    <input type="date" name="check_in" value="<?php echo $check_in; ?>" required>
                </div>
                <div class="form-group">
                    <label>Check-out Date</label>
                    <input type="date" name="check_out" value="<?php echo $check_out; ?>" required>
                </div>
            </div>
            <button type="submit" name="search" class="btn-primary">Search Available Units</button>
        </form>

        <?php if ($searched): ?>
            <div class="room-box">
                <h3>Available Rooms For Selected Dates</h3>
                <?php if (empty($available_rooms)): ?>
                    <p style="color:#dc2626;"><strong>No rooms available in this category for the requested dates. Overlap conflict prevented.</strong></p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Select Physical Room</label>
                            <select name="room_id" required>
                                <?php foreach ($available_rooms as $r): ?>
                                    <option value="<?php echo $r['room_id']; ?>">Room <?php echo htmlspecialchars($r['room_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <h3>Guest Information</h3>
                        <div class="form-group" style="margin-bottom:10px;">
                            <label>Full Name</label>
                            <input type="text" name="guest_name" required placeholder="Guest Name">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="guest_phone" required placeholder="08012345678">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="guest_email" placeholder="guest@example.com">
                            </div>
                        </div>
                        <button type="submit" name="book" class="btn-primary" style="background:#16a34a;margin-top:10px;">Confirm & Hold Room</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
