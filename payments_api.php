<?php
/**
 * Payment Management API
 *
 * GET    ?loan_id=X -> Retrieve all payments for a loan, plus loan amount,
 *                      total paid, and remaining balance
 * POST   -> Add a new payment (loan_id, amount, payment_date, payment_method)
 * DELETE -> Remove a payment (id)
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$host = "db";
$user = "root";
$pass = "rootpassword";
$dbname = "school_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$validMethods = ['Cash', 'Bank Transfer', 'Online Payment'];

switch ($method) {
    case 'GET':
        if (empty($_GET['loan_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "loan_id is required"]);
            break;
        }
        $loanId = $_GET['loan_id'];

        // Get the loan amount so we can compute remaining balance
        $loanStmt = $pdo->prepare("SELECT amount FROM loans WHERE id = ?");
        $loanStmt->execute([$loanId]);
        $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);

        if (!$loan) {
            http_response_code(404);
            echo json_encode(["error" => "Loan not found"]);
            break;
        }

        $stmt = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY payment_date DESC, id DESC");
        $stmt->execute([$loanId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPaid = 0;
        foreach ($payments as $p) {
            $totalPaid += (float)$p['amount'];
        }
        $remaining = (float)$loan['amount'] - $totalPaid;

        echo json_encode([
            "payments" => $payments,
            "loan_amount" => (float)$loan['amount'],
            "total_paid" => $totalPaid,
            "remaining_balance" => $remaining
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (
            !empty($data['loan_id']) &&
            isset($data['amount']) && is_numeric($data['amount']) &&
            !empty($data['payment_date']) &&
            !empty($data['payment_method']) && in_array($data['payment_method'], $validMethods)
        ) {
            $stmt = $pdo->prepare("INSERT INTO payments (loan_id, amount, payment_date, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['loan_id'], $data['amount'], $data['payment_date'], $data['payment_method']]);
            echo json_encode(["message" => "Payment recorded successfully!"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or missing payment data"]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(["message" => "Payment deleted successfully!"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID required"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
