<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'db.php';

// Handle Check-In Action
if (isset($_GET['checkin_id'])) {
    $res_id = (int)$_GET['checkin_id'];
    $stmt = $pdo->prepare("
        UPDATE reservations res 
        JOIN rooms r ON res.room_id = r.room_id 
        SET res.status = 'Checked-in', r.status = 'occupied' 
        WHERE res.reservation_id = ?
    ");
    $stmt->execute([$res_id]);
    header("Location: dashboard.php");
    exit;
}

// Summary Metrics
$total_rooms     = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupied_rooms  = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$available_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();

// Scheduled Arrivals
$arr_stmt = $pdo->query("
    SELECT res.*, g.full_name, g.phone, r.room_number, rt.type_name 
    FROM reservations res
    JOIN guests g ON res.guest_id = g.guest_id
    JOIN rooms r ON res.room_id = r.room_id
    JOIN room_types rt ON r.type_id = rt.type_id
    WHERE res.status = 'Confirmed'
    ORDER BY res.check_in ASC
");
$arrivals = $arr_stmt->fetchAll();

// In-House Active Stays
$stay_stmt = $pdo->query("
    SELECT res.*, g.full_name, r.room_number, rt.type_name, rt.rate_per_night 
    FROM reservations res
    JOIN guests g ON res.guest_id = g.guest_id
    JOIN rooms r ON res.room_id = r.room_id
    JOIN room_types rt ON r.type_id = rt.type_id
    WHERE res.status = 'Checked-in'
    ORDER BY res.check_out ASC
");
$active_stays = $stay_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - UI Hotels PMS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f8fafc; color: #1e293b; }
        .header { background: #002147; color: white; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f2a900; }
        .header h1 { margin: 0; font-size: 18px; }
        .nav-links a { color: #f2a900; text-decoration: none; margin-left: 20px; font-weight: 600; font-size: 14px; }
        .container { max-width: 1100px; margin: 25px auto; padding: 0 20px; }
        .metric-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .card h3 { margin: 0 0 8px; font-size: 13px; color: #64748b; text-transform: uppercase; }
        .card .count { font-size: 26px; font-weight: bold; color: #002147; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 30px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f1f5f9; color: #475569; }
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; }
        .btn-in { background: #16a34a; color: white; }
        .btn-out { background: #ea580c; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UI HOTELS — FRONT-DESK RECEPTION DASHBOARD</h1>
        <div class="nav-links">
            <span style="color:#cbd5e1;">Role: <strong><?php echo strtoupper($_SESSION['role']); ?></strong></span>
            <a href="reservations.php">Walk-In Search</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="container">
        <div class="metric-cards">
            <div class="card">
                <h3>Total Room Inventory</h3>
                <div class="count"><?php echo $total_rooms; ?> Rooms</div>
            </div>
            <div class="card">
                <h3>Available for Check-In</h3>
                <div class="count" style="color:#16a34a;"><?php echo $available_rooms; ?> Free</div>
            </div>
            <div class="card">
                <h3>Currently Occupied</h3>
                <div class="count" style="color:#dc2626;"><?php echo $occupied_rooms; ?> In-House</div>
            </div>
        </div>

        <h2>Pending Arrivals (Confirmed Bookings)</h2>
        <table>
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Guest Name</th>
                    <th>Phone</th>
                    <th>Room Type</th>
                    <th>Assigned Unit</th>
                    <th>Check-in Dates</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($arrivals)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#64748b;">No pending arrivals recorded.</td></tr>
                <?php else: foreach ($arrivals as $a): ?>
                    <tr>
                        <td>#RES-<?php echo $a['reservation_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($a['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($a['phone']); ?></td>
                        <td><?php echo htmlspecialchars($a['type_name']); ?></td>
                        <td><strong>Room <?php echo htmlspecialchars($a['room_number']); ?></strong></td>
                        <td><?php echo $a['check_in'] . " to " . $a['check_out']; ?></td>
                        <td><a href="dashboard.php?checkin_id=<?php echo $a['reservation_id']; ?>" class="btn btn-in">Check-in Guest</a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>Active Stays (In-House Occupants)</h2>
        <table>
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Guest Name</th>
                    <th>Room</th>
                    <th>Rate/Night</th>
                    <th>Expected Checkout</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($active_stays)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#64748b;">No active occupants in-house.</td></tr>
                <?php else: foreach ($active_stays as $s): ?>
                    <tr>
                        <td>#RES-<?php echo $s['reservation_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
                        <td><strong>Room <?php echo htmlspecialchars($s['room_number']); ?></strong> (<?php echo htmlspecialchars($s['type_name']); ?>)</td>
                        <td>₦<?php echo number_format($s['rate_per_night'], 2); ?></td>
                        <td><?php echo $s['check_out']; ?></td>
                        <td><a href="checkout.php?id=<?php echo $s['reservation_id']; ?>" class="btn btn-out">Check-Out & Settle Bill</a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
