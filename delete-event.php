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
    $stmt = $bdd->prepare("DELETE FROM events WHERE id = ? AND created_by = ?");
    $success = $stmt->execute([$data['id'], $_SESSION['cin']]);
    
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}