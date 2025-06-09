<?php
session_start();
include('config.php');
header('Content-Type: application/json; charset=utf-8');

try {
    // Initialiser la connexion à la base de données
    connexionBdd($bdd);

    $data = json_decode(file_get_contents("php://input"), true);
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
        throw new Exception('Non autorisé - vous n\'êtes pas le créateur de cet événement');
    }

    // Validation et sanitisation des données
    $title = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_STRING);
    $event_type = filter_var($data['event_type'], FILTER_SANITIZE_STRING);
    $visibility = filter_var($data['visibility'], FILTER_SANITIZE_STRING);
    $start = filter_var($data['start'], FILTER_SANITIZE_STRING);
    $end = filter_var($data['end'], FILTER_SANITIZE_STRING);

    if (empty($title) || empty($start) || empty($end) || empty($event_type)) {
        throw new Exception('Données manquantes');
    }

    // Mettre à jour les couleurs en fonction du type d'événement
    $colors = [
        'Conférence' => ['#3cb371', '#ffffff'],
        'formation' => ['#ffa500', '#000000'],
        'réunion' => ['#9370db', '#ffffff'],
        'autre' => ['#3788d8', '#ffffff']
    ];

    $bgColor = $colors[$event_type][0] ?? '#3788d8';
    $textColor = $colors[$event_type][1] ?? '#ffffff';

    // Mise à jour de l'événement
    $stmt = $bdd->prepare("UPDATE events SET 
        title = ?,
        description = ?,
        start_datetime = ?,
        end_datetime = ?,
        event_type = ?,
        visibility = ?,
        background_color = ?,
        text_color = ?,
        updated_at = NOW()
        WHERE id = ?");

    $success = $stmt->execute([
        $title,
        $description,
        $start,
        $end,
        $event_type,
        $visibility,
        $bgColor,
        $textColor,
        $eventId
    ]);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Événement mis à jour avec succès' : 'Échec de la mise à jour'
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