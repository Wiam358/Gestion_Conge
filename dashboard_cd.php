<?php 
session_start(); 
include_once 'connexion.php';
connexionBdd($bdd);

// Récupérer le département du chef
$reqDept = $bdd->prepare("SELECT depart FROM personnel WHERE cin = ?");
$reqDept->execute([$_SESSION['cin']]);
$depart = $reqDept->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord CD | CCISCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2e8b57;
            --primary-light: #3cb371;
            --text-color: #333;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: white;
            border-left: 5px solid var(--primary-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .stat-icon {
            font-size: 1.8rem;
            color: var(--primary-light);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>
    <div class="dashboard-container">
        <h2 class="mb-4 fw-bold" style="color: var(--primary-color);">Tableau de Bord - Chef de Département</h2>

        <div class="stats-container">
            <!-- Demandes en attente de son équipe -->
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value">
                    <?php
                    $req = $bdd->prepare("SELECT COUNT(*) FROM demande d 
                        JOIN personnel p ON d.cin = p.cin 
                        WHERE p.depart = ? AND d.Etat = 0");
                    $req->execute([$depart]);
                    echo $req->fetchColumn();
                    ?>
                </div>
                <div class="stat-label">Demandes en attente de l’équipe</div>
            </div>

            <!-- Demandes approuvées ce mois-ci -->
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value">
                    <?php
                    $req = $bdd->prepare("SELECT COUNT(*) FROM demande d 
                        JOIN personnel p ON d.cin = p.cin 
                        WHERE p.depart = ? AND d.Etat = 1 AND MONTH(d.DateDemande) = MONTH(CURDATE()) AND YEAR(d.DateDemande) = YEAR(CURDATE())");
                    $req->execute([$depart]);
                    echo $req->fetchColumn();
                    ?>
                </div>
                <div class="stat-label">Approuvées ce mois</div>
            </div>

            <!-- Demandes refusées ce mois-ci -->
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div class="stat-value">
                    <?php
                    $req = $bdd->prepare("SELECT COUNT(*) FROM demande d 
                        JOIN personnel p ON d.cin = p.cin 
                        WHERE p.depart = ? AND d.Etat = -1 AND MONTH(d.DateDemande) = MONTH(CURDATE()) AND YEAR(d.DateDemande) = YEAR(CURDATE())");
                    $req->execute([$depart]);
                    echo $req->fetchColumn();
                    ?>
                </div>
                <div class="stat-label">Refusées ce mois</div>
            </div>

            <!-- Nombre de membres dans son équipe -->
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-value">
                    <?php
                    $req = $bdd->prepare("SELECT COUNT(*) FROM personnel WHERE depart = ? AND qualite = 'SAL'");
                    $req->execute([$depart]);
                    echo $req->fetchColumn();
                    ?>
                </div>
                <div class="stat-label">Membres de l’équipe</div>
            </div>

            <!-- Solde de congés du CD -->
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-calendar-plus"></i></div>
                <div class="stat-value">
                    <?php
                    $req = $bdd->prepare("SELECT conges_annee_en_cours, conges_restants_annee_precedente FROM personnel WHERE cin = ?");
                    $req->execute([$_SESSION['cin']]);
                    $conges = $req->fetch();
                    $total = $conges['conges_annee_en_cours'] + $conges['conges_restants_annee_precedente'];
                    echo $total;
                    ?>
                </div>
                <div class="stat-label">Votre solde de congés</div>
            </div>
        </div>
    </div>
</body>
</html>
