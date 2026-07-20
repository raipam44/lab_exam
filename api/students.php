<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$dataFile = __DIR__ . "/../data/students.json";
$loansFile = __DIR__ . "/../data/loans.json";
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

/* ---------- routing ---------- */
$action = $_GET['action'] ?? '';
$students = loadJson($dataFile);

switch ($action) {

    case 'list':
        respond($students);

    case 'add':
        $input = json_decode(file_get_contents('php://input'), true);
        $name           = trim($input['name'] ?? '');
        $student_number = trim($input['student_number'] ?? '');
        $course         = trim($input['course'] ?? '');

        if (!$name || !$student_number || !$course) {
            respond(['error' => 'All fields are required.'], 400);
        }

        $student = [
            'id'             => nextId($students),
            'name'           => $name,
            'student_number' => $student_number,
            'course'         => $course,
            'created_at'     => date('Y-m-d H:i:s')
        ];
        $students[] = $student;
        saveJson($dataFile, $students);
        respond($student, 201);

    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        // Cascade delete: remove loans & their payments
        $loans = loadJson($loansFile);
        $loanIds = array_column(
            array_filter($loans, fn($l) => $l['student_id'] == $id),
            'id'
        );
        $loans = array_filter($loans, fn($l) => $l['student_id'] != $id);
        saveJson($loansFile, $loans);

        if ($loanIds) {
            $payments = loadJson($paymentsFile);
            $payments = array_filter($payments, fn($p) => !in_array($p['loan_id'], $loanIds));
            saveJson($paymentsFile, $payments);
        }

        $students = array_filter($students, fn($s) => $s['id'] != $id);
        saveJson($dataFile, $students);
        respond(['success' => true]);

    default:
        respond(['error' => 'Unknown action.'], 400);
}
