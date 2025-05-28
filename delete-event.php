<?php
session_start();
include('config.php');
header('Content-Type: application/json; charset=utf-8');

try {
    // Initialiser la connexion à la base de données
    connexionBdd($bdd);

    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('Données invalides');
    }

    $eventId = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
    $currentUser = $_SESSION['cin'] ?? null;

    if (!$currentUser) {
        throw new Exception('Non autorisé - utilisateur non connecté');
    }

    // Vérifier que l'utilisateur est le créateur
    $checkStmt = $bdd->prepare("SELECT created_by FROM events WHERE id = ?");
    $checkStmt->execute([$eventId]);
    $event = $checkStmt->fetch();

    if (!$event || $event['created_by'] !== $currentUser) {
        throw new Exception('Non autorisé - vous n\'êtes pas le créateur');
    }

    // Suppression
    $stmt = $bdd->prepare("DELETE FROM events WHERE id = ?");
    $success = $stmt->execute([$eventId]);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Événement supprimé' : 'Échec de la suppression'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur PDO : ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>