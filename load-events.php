<?php
session_start();
include('config.php');
connexionBdd($bdd);

$user_cin = $_SESSION['cin']; // ou autre identifiant
$stmt = $bdd->query("SELECT id, title, start_datetime AS start, end_datetime AS end, background_color, text_color FROM events");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($events);