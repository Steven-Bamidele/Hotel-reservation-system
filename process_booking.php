<?php
// process_booking.php s
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $check_in  = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $room_name = $_POST['room_name'] ?? 'Double Room Deluxe';

    // Map room name to type_id
    $type_map = [
        'Double Room Deluxe' => 1,
        'Royal Standard'     => 2,
        'Royal Executive'    => 3,
        'Executive Suite'    => 4,
        'Premium Royal Suite'=> 5
    ];
    $type_id = $type_map[$room_name] ?? 1;

    // Validation
    if (empty($full_name) || empty($phone) || empty($check_in) || empty($check_out)) {
        die("Error: Please fill in all required fields. <a href='index.html'>Go back</a>");
    }

    if ($check_in >= $check_out) {
        die("Error: Check-out date must be strictly after the check-in date. <a href='index.html'>Go back</a>");
    }

    // Availability Query from Chapter 4 of the Write-up
    $sql = "
        SELECT r.room_id, r.room_number, rt.rate_per_night 
        FROM rooms r
        JOIN room_types rt ON r.type_id = rt.type_id
        WHERE r.type_id = ? AND r.status <> 'maintenance'
        AND r.room_id NOT IN (
            SELECT room_id FROM reservations
            WHERE status IN ('Confirmed', 'Checked-in')
            AND check_in < ? AND check_out > ?
        )
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$type_id, $check_out, $check_in]);
    $free_room = $stmt->fetch();

    if ($free_room) {
        // 1. Insert Guest
        $g_stmt = $pdo->prepare("INSERT INTO guests (full_name, phone, email) VALUES (?, ?, ?)");
        $g_stmt->execute([$full_name, $phone, $email]);
        $guest_id = $pdo->lastInsertId();

        // 2. Default staff ID (admin user_id = 1) for web-initiated bookings
        $created_by = 1;

        // 3. Insert Reservation
        $res_stmt = $pdo->prepare("
            INSERT INTO reservations (guest_id, room_id, created_by, check_in, check_out, status)
            VALUES (?, ?, ?, ?, ?, 'Confirmed')
        ");
        $res_stmt->execute([$guest_id, $free_room['room_id'], $created_by, $check_in, $check_out]);
        $reservation_id = $pdo->lastInsertId();

        // Calculate bill
        $nights = max(1, (int)((strtotime($check_out) - strtotime($check_in)) / 86400));
        $total = $nights * $free_room['rate_per_night'];

        // Display Confirmation Slip
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Booking Confirmed - UI Hotels</title>
            <style>
                body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; margin: 0; padding: 40px; display: flex; justify-content: center; }
                .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 500px; width: 100%; border-top: 4px solid #f2a900; }
                h2 { color: #002147; margin-top: 0; }
                .success-badge { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 14px; display: inline-block; margin-bottom: 15px; }
                .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; }
                .btn { display: inline-block; margin-top: 20px; background: #002147; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="card">
                <span class="success-badge">✔ Reservation Successfully Confirmed</span>
                <h2>UI Hotels Reservation Slip</h2>
                <div class="row"><span>Booking Reference:</span> <strong>#RES-<?php echo $reservation_id; ?></strong></div>
                <div class="row"><span>Guest Name:</span> <strong><?php echo htmlspecialchars($full_name); ?></strong></div>
                <div class="row"><span>Room Category:</span> <strong><?php echo htmlspecialchars($room_name); ?></strong></div>
                <div class="row"><span>Assigned Door:</span> <strong>Room <?php echo htmlspecialchars($free_room['room_number']); ?></strong></div>
                <div class="row"><span>Stay Duration:</span> <strong><?php echo $check_in; ?> to <?php echo $check_out; ?> (<?php echo $nights; ?> Nights)</strong></div>
                <div class="row"><span>Total Estimated Bill:</span> <strong>₦<?php echo number_format($total, 2); ?></strong></div>
                <p style="font-size: 13px; color: #64748b; margin-top: 15px;">Please show this reservation reference to the receptionist upon check-in at the UI Conference Centre Building, Chapel Road.</p>
                <a href="index.html" class="btn">&larr; Return to Homepage</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        // Rejection message (Test Case T7 in Chapter 4)
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Room Unavailable - UI Hotels</title>
            <style>
                body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; margin: 0; padding: 40px; display: flex; justify-content: center; }
                .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 480px; width: 100%; border-top: 4px solid #dc2626; }
                h2 { color: #991b1b; margin-top: 0; }
                .btn { display: inline-block; margin-top: 20px; background: #002147; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="card">
                <h2>No Available Vacancy</h2>
                <p>Sorry, all rooms in the <strong><?php echo htmlspecialchars($room_name); ?></strong> category are currently reserved for the selected dates (<?php echo $check_in; ?> to <?php echo $check_out; ?>).</p>
                <p>Please select alternative dates or consider another room tier.</p>
                <a href="index.html" class="btn">&larr; Try Different Dates</a>
            </div>
        </body>
        </html>
        <?php
    }
}
?>
