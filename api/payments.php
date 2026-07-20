<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$paymentsFile = __DIR__ . "/../data/payments.json";
$loansFile    = __DIR__ . "/../data/loans.json";

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
$VALID_METHODS = ['Cash', 'Bank Transfer', 'Online Payment'];

/* ---------- routing ---------- */
$action   = $_GET['action'] ?? '';
$payments = loadJson($paymentsFile);

switch ($action) {

    case 'list':
        $loan_id = (int)($_GET['loan_id'] ?? 0);
        if (!$loan_id) respond(['error' => 'loan_id is required.'], 400);

        // Return ONLY payments belonging to the specified loan (FK filter)
        $result = array_values(array_filter($payments, fn($p) => $p['loan_id'] == $loan_id));

        // Compute total paid and remaining balance
        $loans       = loadJson($loansFile);
        $loan        = current(array_filter($loans, fn($l) => $l['id'] == $loan_id));
        $loanAmount  = $loan ? floatval($loan['amount']) : 0;
        $totalPaid   = array_sum(array_column($result, 'payment_amount'));
        $remaining   = $loanAmount - $totalPaid;

        respond([
            'payments'       => $result,
            'loan_amount'    => round($loanAmount, 2),
            'total_paid'     => round($totalPaid, 2),
            'remaining'      => round($remaining, 2)
        ]);

    case 'add':
        $input          = json_decode(file_get_contents('php://input'), true);
        $loan_id        = (int)($input['loan_id'] ?? 0);
        $payment_amount = floatval($input['payment_amount'] ?? 0);
        $payment_date   = trim($input['payment_date'] ?? '');
        $payment_method = trim($input['payment_method'] ?? '');

        if (!$loan_id)                          respond(['error' => 'loan_id is required.'], 400);
        if ($payment_amount <= 0)               respond(['error' => 'Payment amount must be greater than 0.'], 400);
        if (!$payment_date)                     respond(['error' => 'Payment date is required.'], 400);
        if (!in_array($payment_method, $VALID_METHODS))
                                                respond(['error' => 'Invalid payment method.'], 400);

        // Validate date format
        $dateObj = DateTime::createFromFormat('Y-m-d', $payment_date);
        if (!$dateObj) respond(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);

        // Verify loan exists (FK integrity check)
        $loans = loadJson($loansFile);
        $loan  = current(array_filter($loans, fn($l) => $l['id'] == $loan_id));
        if (!$loan) respond(['error' => 'Loan not found.'], 404);

        $payment = [
            'id'             => nextId($payments),
            'loan_id'        => $loan_id,           // FK → loans.id
            'payment_amount' => round($payment_amount, 2),
            'payment_date'   => $payment_date,
            'payment_method' => $payment_method,
            'created_at'     => date('Y-m-d H:i:s')
        ];
        $payments[] = $payment;
        saveJson($paymentsFile, $payments);

        // Return payment with updated balance
        $allForLoan  = array_filter($payments, fn($p) => $p['loan_id'] == $loan_id);
        $totalPaid   = array_sum(array_column($allForLoan, 'payment_amount'));
        $remaining   = floatval($loan['amount']) - $totalPaid;

        respond(array_merge($payment, [
            'total_paid' => round($totalPaid, 2),
            'remaining'  => round($remaining, 2)
        ]), 201);

    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);

        $payments = array_filter($payments, fn($p) => $p['id'] != $id);
        saveJson($paymentsFile, $payments);
        respond(['success' => true]);

    default:
        respond(['error' => 'Unknown action.'], 400);
}
