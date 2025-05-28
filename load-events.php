<?php
session_start();
include('config.php');

try {
    // Initialiser la connexion à la base de données
    connexionBdd($bdd);

    $user_cin = isset($_SESSION['cin']) ? $_SESSION['cin'] : null;

    if ($user_cin) {
        // Charger les événements publics et ceux créés par l'utilisateur
        $stmt = $bdd->prepare("SELECT 
            id, 
            title, 
            description, 
            start_datetime AS start, 
            end_datetime AS end, 
            event_type,
            visibility,
            created_by,
            background_color AS backgroundColor,
            text_color AS textColor
            FROM events 
            WHERE visibility = 'public' OR created_by = ?");
        $stmt->execute([$user_cin]);
    } else {
        // Charger uniquement les événements publics
        $stmt = $bdd->query("SELECT 
            id, 
            title, 
            description, 
            start_datetime AS start, 
            end_datetime AS end, 
            event_type,
            visibility,
            created_by,
            background_color AS backgroundColor,
            text_color AS textColor
            FROM events 
            WHERE visibility = 'public'");
    }

    $events = $stmt->fetchAll();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($events);

} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données : ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>