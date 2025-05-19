<?php
session_start();
include('config.php');
connexionBdd($bdd);

// Vérifier si l'utilisateur est connecté
$user_cin = isset($_SESSION['cin']) ? $_SESSION['cin'] : null;

if ($user_cin) {
    // Si utilisateur connecté, charger ses événements privés + tous les publics
    $stmt = $bdd->prepare("SELECT 
        id, 
        title, 
        description, 
        start_datetime AS start, 
        end_datetime AS end, 
        event_type,
        visibility,
        background_color AS backgroundColor,
        text_color AS textColor
        FROM events 
        WHERE visibility = 'public' OR created_by = ?");
    $stmt->execute([$user_cin]);
} else {
    // Si utilisateur non connecté, charger seulement les événements publics
    $stmt = $bdd->query("SELECT 
        id, 
        title, 
        description, 
        start_datetime AS start, 
        end_datetime AS end, 
        event_type,
        visibility,
        background_color AS backgroundColor,
        text_color AS textColor
        FROM events 
        WHERE visibility = 'public'");
}

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($events);