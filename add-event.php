<?php
session_start();
include('config.php');
header('Content-Type: application/json; charset=utf-8');

try {
    // Initialiser la connexion à la base de données
    connexionBdd($bdd);

    $data = json_decode(file_get_contents("php://input"), true);

    // Validation et sanitisation des données
    if (empty($data['title']) || empty($data['start']) || empty($data['end']) || empty($data['event_type'])) {
        throw new Exception('Données manquantes');
    }

    $title = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $start = filter_var($data['start'], FILTER_SANITIZE_STRING);
    $end = filter_var($data['end'], FILTER_SANITIZE_STRING);
    $event_type = filter_var($data['event_type'], FILTER_SANITIZE_STRING);
    $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_STRING);
    $visibility = filter_var($data['visibility'] ?? 'public', FILTER_SANITIZE_STRING);
    $created_by = $_SESSION['cin'] ?? null;

    if (!$created_by) {
        throw new Exception('Utilisateur non connecté');
    }

    // Définition des couleurs (devrait être dans config.php pour réutilisation)
    $colors = [
        'congé' => ['#3cb371', '#ffffff'],
        'formation' => ['#ffa500', '#000000'],
        'réunion' => ['#9370db', '#ffffff'],
        'autre' => ['#3788d8', '#ffffff']
    ];

    $bgColor = $colors[$event_type][0] ?? '#3788d8';
    $textColor = $colors[$event_type][1] ?? '#ffffff';

    $stmt = $bdd->prepare("INSERT INTO events 
        (title, description, start_datetime, end_datetime, event_type, created_by, background_color, text_color, created_at, updated_at, visibility)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");

    $success = $stmt->execute([$title, $description, $start, $end, $event_type, $created_by, $bgColor, $textColor, $visibility]);

    if ($success) {
        $eventId = $bdd->lastInsertId();
        echo json_encode([
            'success' => true,
            'event' => [
                'id' => $eventId,
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'description' => $description,
                'event_type' => $event_type,
                'visibility' => $visibility,
                'backgroundColor' => $bgColor,
                'textColor' => $textColor,
                'created_by' => $created_by
            ]
        ]);
    } else {
        throw new Exception('Échec de l\'insertion en base de données');
    }

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