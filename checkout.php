<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

$res_id = (int)($_GET['id'] ?? 0);
if ($res_id <= 0) {
    die("Invalid reservation reference. <a href='dashboard.php'>Return to Dashboard</a>");
}

$payment_settled = false;

// Handle Extra Charges (FR9)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_charge'])) {
    $desc = trim($_POST['charge_desc'] ?? '');
    $amt  = (float)($_POST['charge_amount'] ?? 0);
    if (!empty($desc) && $amt > 0) {
        $c_stmt = $pdo->prepare("INSERT INTO charges (reservation_id, description, amount) VALUES (?, ?, ?)");
        $c_stmt->execute([$res_id, $desc, $amt]);
        header("Location: checkout.php?id=" . $res_id);
        exit;
    }
}

// Fetch Reservation Details
$stmt = $pdo->prepare("
    SELECT res.*, g.full_name, g.phone, g.email, r.room_number, rt.type_name, rt.rate_per_night 
    FROM reservations res
    JOIN guests g ON res.guest_id = g.guest_id
    JOIN rooms r ON res.room_id = r.room_id
    JOIN room_types rt ON r.type_id = rt.type_id
    WHERE res.reservation_id = ?
");
$stmt->execute([$res_id]);
$res = $stmt->fetch();

if (!$res) {
    die("Reservation record not found. <a href='dashboard.php'>Return to Dashboard</a>");
}

// Fetch Incidental Charges
$c_list = $pdo->prepare("SELECT * FROM charges WHERE reservation_id = ?");
$c_list->execute([$res_id]);
$charges = $c_list->fetchAll();

// Calculations
$nights        = max(1, (int)((strtotime($res['check_out']) - strtotime($res['check_in'])) / 86400));
$room_charges  = $nights * $res['rate_per_night'];
$extra_total   = 0;
foreach ($charges as $c) {
    $extra_total += $c['amount'];
}
$grand_total   = $room_charges + $extra_total;

// Settle Payment & Release Room (FR8, FR10)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settle_payment'])) {
    $method = $_POST['payment_method'] ?? 'Cash';

    // 1. Insert into Payments Table
    $p_stmt = $pdo->prepare("INSERT INTO payments (reservation_id, amount, method) VALUES (?, ?, ?)");
    $p_stmt->execute([$res_id, $grand_total, $method]);

    // 2. Mark reservation Completed and release room to available
    $u_stmt = $pdo->prepare("
        UPDATE reservations res 
        JOIN rooms r ON res.room_id = r.room_id 
        SET res.status = 'Completed', r.status = 'available' 
        WHERE res.reservation_id = ?
    ");
    $u_stmt->execute([$res_id]);

    $payment_settled = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill & Receipt - UI Hotels</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: #f8fafc;
            margin: 0;
            padding: 25px;
            color: #1e293b;
        }
        .receipt-box {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #002147;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header h2 {
            margin: 0;
            color: #002147;
            font-size: 22px;
        }
        .header p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }
        .status-stamp {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 6px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
            font-size: 14px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            color: #475569;
        }
        .total-banner {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
            color: #002147;
            border-top: 2px dashed #cbd5e1;
            padding-top: 10px;
        }
        .btn {
            display: inline-block;
            text-align: center;
            padding: 11px 18px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-pay {
            background: #16a34a;
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        .btn-pay:hover {
            background: #15803d;
        }
        .btn-print {
            background: #002147;
            color: white;
            width: 100%;
            margin-top: 12px;
        }
        .btn-back {
            background: #64748b;
            color: white;
            margin-top: 10px;
            width: 100%;
        }
        .charge-form {
            background: #f8fafc;
            padding: 14px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            margin: 20px 0;
        }

        /* Hide buttons during print */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-box {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="header">
            <h2>UI HOTELS</h2>
            <p>University of Ibadan Conference Centre Complex, Chapel Road</p>
            <p><strong>GUEST FOLIO & OFFICIAL RECEIPT</strong></p>
            <?php if ($payment_settled || $res['status'] === 'Completed'): ?>
                <div class="status-stamp">PAID & SETTLED</div>
            <?php endif; ?>
        </div>

        <div class="info-row">
            <span><strong>Folio Reference:</strong> #RES-<?php echo $res['reservation_id']; ?></span>
            <span><strong>Issue Date:</strong> <?php echo date('Y-m-d H:i'); ?></span>
        </div>
        <div class="info-row">
            <span><strong>Guest Name:</strong> <?php echo htmlspecialchars($res['full_name']); ?></span>
            <span><strong>Phone:</strong> <?php echo htmlspecialchars($res['phone']); ?></span>
        </div>
        <div class="info-row">
            <span><strong>Assigned Room:</strong> Room <?php echo htmlspecialchars($res['room_number']); ?> (<?php echo htmlspecialchars($res['type_name']); ?>)</span>
            <span><strong>Duration:</strong> <?php echo $nights; ?> Night(s)</span>
        </div>
        <div class="info-row">
            <span><strong>Period of Stay:</strong> <?php echo $res['check_in']; ?> to <?php echo $res['check_out']; ?></span>
            <span><strong>Status:</strong> <?php echo $payment_settled ? 'Completed' : htmlspecialchars($res['status']); ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Rate (₦)</th>
                    <th>Qty</th>
                    <th>Subtotal (₦)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Room Accommodation Charge</td>
                    <td>₦<?php echo number_format($res['rate_per_night'], 2); ?></td>
                    <td><?php echo $nights; ?></td>
                    <td>₦<?php echo number_format($room_charges, 2); ?></td>
                </tr>
                <?php foreach ($charges as $ch): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ch['description']); ?> (Incidental)</td>
                    <td>₦<?php echo number_format($ch['amount'], 2); ?></td>
                    <td>1</td>
                    <td>₦<?php echo number_format($ch['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-banner">
            Total Outstanding Balance: ₦<?php echo number_format($grand_total, 2); ?>
        </div>

        <?php if (!$payment_settled && $res['status'] !== 'Completed'): ?>
            <!-- Add Extra Incidental Charge Form (FR9) -->
            <div class="charge-form no-print">
                <h4 style="margin: 0 0 8px; color: #002147; font-size: 13px;">Add Extra Incidental (Room Service, Laundry)</h4>
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="text" name="charge_desc" required placeholder="e.g. Laundry / Drinks" style="flex:2; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    <input type="number" name="charge_amount" required placeholder="Amount (₦)" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    <button type="submit" name="add_charge" class="btn" style="background:#002147; color:white;">Add</button>
                </form>
            </div>

            <!-- Payment Settlement Form (FR10) -->
            <form method="POST" class="no-print">
                <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:5px;">Select Payment Settlement Method:</label>
                <select name="payment_method" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:4px; font-size:14px;">
                    <option value="Cash">Cash Settlement</option>
                    <option value="POS Transfer">POS Card Terminal</option>
                    <option value="Bank Transfer">Direct Bank Transfer</option>
                </select>
                <button type="submit" name="settle_payment" class="btn btn-pay">Record Payment & Finalize Receipt</button>
            </form>
        <?php else: ?>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-print">Print Official Receipt (PDF)</button>
                <a href="dashboard.php" class="btn btn-back">&larr; Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>