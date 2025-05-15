<?php
session_start();
include('config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
connexionBdd($bdd);

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $title = $data['title'];
    $start = $data['start'];
    $end = $data['end'];
    $event_type = $data['event_type'] ?? 'autre';
    $description = $data['description'] ?? '';
    $created_by = $_SESSION['cin']; // à adapter selon session
    $bgColor = '#3788d8';
    $textColor = '#ffffff';

    $stmt = $bdd->prepare("INSERT INTO events (title, description, start_datetime, end_datetime, event_type, created_by, background_color, text_color, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

    $success = $stmt->execute([$title, $description, $start, $end, $event_type, $created_by, $bgColor, $textColor]);

    echo json_encode(['success' => $success]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur PDO : ' . $e->getMessage()
    ]);
}
