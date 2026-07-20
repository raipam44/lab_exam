<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$loansFile    = __DIR__ . "/../data/loans.json";
$paymentsFile = __DIR__ . "/../data/payments.json";

/* ---------- helpers ---------- */
function loadJson($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return $content ? json_decode($content, true) : [];
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
}

function nextId($records) {
    if (!$records) return 1;
    return max(array_column($records, 'id')) + 1;
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/* ---------- valid enums ---------- */
$VALID_TYPES   = ['Tuition', 'Books', 'Living Expenses'];
$VALID_STATUSES = ['Pending', 'Approved', 'Disbursed'];

/* ---------- routing ---------- */
$action = $_GET['action'] ?? '';
$loans  = loadJson($loansFile);

switch ($action) {

    case 'list':
        $student_id = (int)($_GET['student_id'] ?? 0);
        if (!$student_id) respond(['error' => 'student_id is required.'], 400);

        // Return ONLY loans belonging to the specified student (FK filter)
        $result = array_values(array_filter($loans, fn($l) => $l['student_id'] == $student_id));
        respond($result);

    case 'add':
        $input      = json_decode(file_get_contents('php://input'), true);
        $student_id = (int)($input['student_id'] ?? 0);
        $amount     = floatval($input['amount'] ?? 0);
        $loan_type  = trim($input['loan_type'] ?? '');
        $status     = trim($input['status'] ?? '');

        if (!$student_id)              respond(['error' => 'student_id is required.'], 400);
        if ($amount <= 0)              respond(['error' => 'Amount must be greater than 0.'], 400);
        if (!in_array($loan_type, $VALID_TYPES))
                                       respond(['error' => 'Invalid loan type.'], 400);
        if (!in_array($status, $VALID_STATUSES))
                                       respond(['error' => 'Invalid status.'], 400);

        $loan = [
            'id'         => nextId($loans),
            'student_id' => $student_id,   // FK → students.id
            'amount'     => round($amount, 2),
            'loan_type'  => $loan_type,
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $loans[] = $loan;
        saveJson($loansFile, $loans);
        respond($loan, 201);

    case 'update_status':
        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id'] ?? 0);
        $status = trim($input['status'] ?? '');

        if (!in_array($status, $VALID_STATUSES)) respond(['error' => 'Invalid status.'], 400);

        foreach ($loans as &$l) {
            if ($l['id'] == $id) { $l['status'] = $status; break; }
        }
        saveJson($loansFile, $loans);
        respond(['success' => true]);

    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);

        // Cascade delete payments for this loan
        $payments = loadJson($paymentsFile);
        $payments = array_filter($payments, fn($p) => $p['loan_id'] != $id);
        saveJson($paymentsFile, $payments);

        $loans = array_filter($loans, fn($l) => $l['id'] != $id);
        saveJson($loansFile, $loans);
        respond(['success' => true]);

    default:
        respond(['error' => 'Unknown action.'], 400);
}
