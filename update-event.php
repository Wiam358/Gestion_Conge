<?php
session_start();
include('config.php');
header('Content-Type: application/json');

connexionBdd($bdd);
if (!isset($_SESSION['cin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $bdd->prepare("UPDATE events SET 
        title = ?, 
        description = ?, 
        start_datetime = ?, 
        end_datetime = ?, 
        event_type = ?, 
        visibility = ?,
        updated_at = NOW()
        WHERE id = ? AND created_by = ?");
    
    $success = $stmt->execute([
        $data['title'],
        $data['description'],
        $data['start'],
        $data['end'],
        $data['event_type'],
        $data['visibility'],
        $data['id'],
        $_SESSION['cin']
    ]);

    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}