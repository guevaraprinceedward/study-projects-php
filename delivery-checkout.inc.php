<?php
/**
 * delivery-checkout.inc.php — validates the delivery date & time.
 * Uses DLV_OPEN / DLV_CLOSE / DLV_MIN_LEAD_MIN / DLV_MAX_DAYS_AHEAD from delivery-lib.php.
 */
include_once 'delivery-lib.php';

function dlvResolveSchedule(string $date, string $time): array {
    $date = trim($date); $time = trim($time);
    if (preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $time, $m)) $time = $m[1];
    $when = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    if (!$when || $when->format('Y-m-d H:i') !== $date . ' ' . $time) {
        return ['ok' => false, 'error' => 'Please choose the delivery date and time.'];
    }
    if ($time < DLV_OPEN || $time > DLV_CLOSE) {
        return ['ok' => false, 'error' => 'Delivery is available from ' . date('g:i A', strtotime(DLV_OPEN)) . ' to ' . date('g:i A', strtotime(DLV_CLOSE)) . '.'];
    }
    if ($when->getTimestamp() < time() + DLV_MIN_LEAD_MIN * 60) {
        return ['ok' => false, 'error' => 'Please choose a delivery time at least ' . DLV_MIN_LEAD_MIN . ' minutes from now.'];
    }
    if ($when->getTimestamp() > strtotime('+' . DLV_MAX_DAYS_AHEAD . ' days')) {
        return ['ok' => false, 'error' => 'Delivery can be scheduled up to ' . DLV_MAX_DAYS_AHEAD . ' days ahead.'];
    }
    return ['ok' => true, 'scheduled_for' => $when->format('Y-m-d H:i:00')];
}