<?php
include 'admin-config.php';
requireAdmin();
requireRole(['owner']);

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

// ── ADD EMPLOYEE ─────────────────────────────────────────────────────────
if ($action === 'add_employee') {
    $name    = trim($_POST['full_name'] ?? '');
    $role    = $_POST['role'] ?? 'staff';
    $pos     = trim($_POST['position'] ?? '');
    $payType = $_POST['pay_type'] ?? 'daily';
    $rate    = floatval($_POST['rate'] ?? 0);
    $userId  = intval($_POST['user_id'] ?? 0) ?: null;

    $validRoles = ['owner','super_admin','admin','manager','staff'];
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Name is required.']); exit; }
    if (!in_array($role, $validRoles, true)) { $role = 'staff'; }
    if (!in_array($payType, ['daily','hourly','monthly'], true)) { $payType = 'daily'; }

    $stmt = $conn->prepare("INSERT INTO employees (user_id, full_name, role, position, pay_type, rate, status) VALUES (?,?,?,?,?,?, 'active')");
    $stmt->bind_param('isssds', $userId, $name, $role, $pos, $payType, $rate);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Employee added.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    }
    exit;
}

// ── UPDATE SINGLE FIELD ──────────────────────────────────────────────────
if ($action === 'update_employee') {
    $id    = intval($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';

    $allowed = ['role','pay_type','rate','status','position','full_name'];
    if (!$id || !in_array($field, $allowed, true)) {
        echo json_encode(['success'=>false,'message'=>'Invalid field.']); exit;
    }
    if ($field === 'role') {
        $validRoles = ['owner','super_admin','admin','manager','staff'];
        if (!in_array($value, $validRoles, true)) { echo json_encode(['success'=>false,'message'=>'Invalid role.']); exit; }
    }
    if ($field === 'pay_type' && !in_array($value, ['daily','hourly','monthly'], true)) {
        echo json_encode(['success'=>false,'message'=>'Invalid pay type.']); exit;
    }
    if ($field === 'rate') $value = floatval($value);

    $stmt = $conn->prepare("UPDATE employees SET `$field` = ? WHERE id = ?");
    $stmt->bind_param('si', $value, $id);
    if ($stmt->execute()) {
        // Keep linked admins.role in sync so admin-panel access level matches
        $emp = $conn->query("SELECT admin_id FROM employees WHERE id=".intval($id))->fetch_assoc();
        if ($field === 'role' && $emp && $emp['admin_id']) {
            $upd = $conn->prepare("UPDATE admins SET role=? WHERE id=?");
            $upd->bind_param('si', $value, $emp['admin_id']);
            $upd->execute();
        }
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    }
    exit;
}

// ── DELETE EMPLOYEE ──────────────────────────────────────────────────────
if ($action === 'delete_employee') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }
    if ($conn->query("DELETE FROM employees WHERE id = $id")) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    }
    exit;
}

// ── GENERATE PAYROLL ─────────────────────────────────────────────────────
if ($action === 'generate_payroll') {
    $empId  = intval($_POST['employee_id'] ?? 0);
    $start  = $_POST['period_start'] ?? '';
    $end    = $_POST['period_end'] ?? '';
    $bonus  = floatval($_POST['bonuses'] ?? 0);
    $deduct = floatval($_POST['deductions'] ?? 0);

    if (!$empId || !$start || !$end) { echo json_encode(['success'=>false,'message'=>'Missing fields.']); exit; }

    $emp = $conn->query("SELECT * FROM employees WHERE id = $empId")->fetch_assoc();
    if (!$emp) { echo json_encode(['success'=>false,'message'=>'Employee not found.']); exit; }

    $startEsc = $conn->real_escape_string($start);
    $endEsc   = $conn->real_escape_string($end);

    // Match attendance rows to this employee via linked user_id (kiosk/site login)
    // or admin_id (admin-panel login), whichever is set.
    if ($emp['user_id']) {
        $where = "a.user_id = " . intval($emp['user_id']) . " AND a.type = 'user'";
    } elseif ($emp['admin_id']) {
        $where = "a.admin_id = " . intval($emp['admin_id']) . " AND a.type = 'admin'";
    } else {
        $where = "1=0"; // not linked to any login yet — nothing to pull
    }

    $rows = $conn->query("
        SELECT * FROM attendance a
        WHERE $where AND a.date BETWEEN '$startEsc' AND '$endEsc'
    ")->fetch_all(MYSQLI_ASSOC);

    $daysPresent = 0; $daysLate = 0; $totalHours = 0; $totalCustomers = 0;
    foreach ($rows as $r) {
        if ($r['clock_in']) $daysPresent++;
        if (($r['punctuality'] ?? '') === 'late') $daysLate++;
        if ($r['clock_in'] && $r['clock_out']) {
            $in = new DateTime($r['clock_in']); $out = new DateTime($r['clock_out']);
            $totalHours += ($out->getTimestamp() - $in->getTimestamp()) / 3600;
        }
        $totalCustomers += (int)($r['customers_served'] ?? 0);
    }
    $totalHours = round($totalHours, 2);

    $rate = (float)$emp['rate'];
    switch ($emp['pay_type']) {
        case 'hourly':  $basePay = $rate * $totalHours; break;
        case 'monthly': $basePay = $rate; break;
        case 'daily':
        default:        $basePay = $rate * $daysPresent; break;
    }
    $netPay = max(0, $basePay + $bonus - $deduct);

    $stmt = $conn->prepare("
        INSERT INTO payroll (employee_id, period_start, period_end, days_present, days_late, total_hours,
                              total_customers_served, base_pay, deductions, bonuses, net_pay, status, generated_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?, 'pending', ?)
    ");
    $ownerId = (int)($_SESSION['admin']['id'] ?? 0);
    $stmt->bind_param('issiididdddi',
        $empId, $start, $end, $daysPresent, $daysLate, $totalHours,
        $totalCustomers, $basePay, $deduct, $bonus, $netPay, $ownerId
    );

    if ($stmt->execute()) {
        echo json_encode(['success'=>true, 'net_pay'=>number_format($netPay,2)]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    }
    exit;
}

// ── ADVANCE PAYROLL STATUS (pending -> approved -> paid) ─────────────────
if ($action === 'advance_payroll') {
    $id = intval($_POST['id'] ?? 0);
    $row = $conn->query("SELECT status FROM payroll WHERE id = $id")->fetch_assoc();
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found.']); exit; }
    $next = $row['status'] === 'pending' ? 'approved' : 'paid';
    if ($conn->query("UPDATE payroll SET status='$next' WHERE id=$id")) {
        echo json_encode(['success'=>true,'status'=>$next]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);
