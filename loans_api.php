<?php
/**
 * Loan Management API
 *
 * GET    ?student_id=X -> Retrieve all loans belonging to a specific student
 * POST   -> Add a new loan (student_id, amount, loan_type, status)
 * PUT    -> Update an existing loan (id, amount, loan_type, status)
 * DELETE -> Remove a loan (id)
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
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
$validTypes = ['Tuition', 'Books', 'Living Expenses'];
$validStatuses = ['Pending', 'Approved', 'Disbursed'];

switch ($method) {
    case 'GET':
        // Loans must be scoped to the currently selected student
        if (empty($_GET['student_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "student_id is required"]);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM loans WHERE student_id = ? ORDER BY id DESC");
        $stmt->execute([$_GET['student_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (
            !empty($data['student_id']) &&
            isset($data['amount']) && is_numeric($data['amount']) &&
            !empty($data['loan_type']) && in_array($data['loan_type'], $validTypes) &&
            !empty($data['status']) && in_array($data['status'], $validStatuses)
        ) {
            $stmt = $pdo->prepare("INSERT INTO loans (student_id, amount, loan_type, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['student_id'], $data['amount'], $data['loan_type'], $data['status']]);
            echo json_encode(["message" => "Loan added successfully!"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or missing loan data"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (
            !empty($data['id']) &&
            isset($data['amount']) && is_numeric($data['amount']) &&
            !empty($data['loan_type']) && in_array($data['loan_type'], $validTypes) &&
            !empty($data['status']) && in_array($data['status'], $validStatuses)
        ) {
            $stmt = $pdo->prepare("UPDATE loans SET amount = ?, loan_type = ?, status = ? WHERE id = ?");
            $stmt->execute([$data['amount'], $data['loan_type'], $data['status'], $data['id']]);
            echo json_encode(["message" => "Loan updated successfully!"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid data provided"]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM loans WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(["message" => "Loan deleted successfully!"]);
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
