<?php
include 'admin-config.php';
requireAdmin();

header('Content-Type: application/json');

$conn->query("
    CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        leave_type ENUM('sick','vacation','emergency','other') NOT NULL DEFAULT 'sick',
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        reason TEXT DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS overtime_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        date DATE NOT NULL,
        hours DECIMAL(4,2) NOT NULL,
        reason TEXT DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$myAdminId = (int)($_SESSION['admin']['id'] ?? 0);
$myRole    = $_SESSION['admin']['role'] ?? 'staff';
$isOwner   = $myRole === 'owner';
$action    = $_POST['action'] ?? '';

// ── SUBMIT LEAVE ─────────────────────────────────────────────────────────
if ($action === 'submit_leave') {
    $type = $_POST['leave_type'] ?? 'sick';
    $from = $_POST['date_from'] ?? '';
    $to   = $_POST['date_to'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $validTypes = ['sick','vacation','emergency','other'];

    if (!in_array($type, $validTypes, true)) $type = 'sick';
    if (!$from || !$to) { echo json_encode(['success'=>false,'message'=>'Please select a date range.']); exit; }
    if (strtotime($to) < strtotime($from)) { echo json_encode(['success'=>false,'message'=>'End date cannot be before start date.']); exit; }

    $stmt = $conn->prepare("INSERT INTO leave_requests (admin_id, leave_type, date_from, date_to, reason) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $myAdminId, $type, $from, $to, $reason);
    if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'Leave request submitted for owner review.']);
    else echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    exit;
}

// ── SUBMIT OVERTIME ──────────────────────────────────────────────────────
if ($action === 'submit_overtime') {
    $date = $_POST['date'] ?? '';
    $hours = (float)($_POST['hours'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if (!$date) { echo json_encode(['success'=>false,'message'=>'Please select a date.']); exit; }
    if ($hours <= 0 || $hours > 12) { echo json_encode(['success'=>false,'message'=>'Please enter a valid number of overtime hours (up to 12).']); exit; }

    $stmt = $conn->prepare("INSERT INTO overtime_requests (admin_id, date, hours, reason) VALUES (?,?,?,?)");
    $stmt->bind_param('isds', $myAdminId, $date, $hours, $reason);
    if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'Overtime request submitted for owner review.']);
    else echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    exit;
}

// ── CANCEL OWN PENDING REQUEST ───────────────────────────────────────────
if ($action === 'cancel_leave' || $action === 'cancel_overtime') {
    $id = (int)($_POST['id'] ?? 0);
    $table = $action === 'cancel_leave' ? 'leave_requests' : 'overtime_requests';
    $row = $conn->query("SELECT admin_id, status FROM $table WHERE id = $id LIMIT 1")->fetch_assoc();
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Request not found.']); exit; }
    if ((int)$row['admin_id'] !== $myAdminId && !$isOwner) { echo json_encode(['success'=>false,'message'=>'Not your request.']); exit; }
    if ($row['status'] !== 'pending') { echo json_encode(['success'=>false,'message'=>'Only pending requests can be cancelled.']); exit; }
    $conn->query("DELETE FROM $table WHERE id = $id");
    echo json_encode(['success'=>true,'message'=>'Request cancelled.']);
    exit;
}

// ── OWNER: REVIEW (APPROVE / REJECT) ─────────────────────────────────────
if ($action === 'review_leave' || $action === 'review_overtime') {
    if (!$isOwner) { echo json_encode(['success'=>false,'message'=>'Only the Owner can review requests.']); exit; }
    $id = (int)($_POST['id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    if (!in_array($decision, ['approved','rejected'], true)) { echo json_encode(['success'=>false,'message'=>'Invalid decision.']); exit; }

    $table = $action === 'review_leave' ? 'leave_requests' : 'overtime_requests';
    $stmt = $conn->prepare("UPDATE $table SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('sii', $decision, $myAdminId, $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success'=>true,'message'=>'Request '.$decision.'.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Could not update — it may have already been reviewed.']);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);
