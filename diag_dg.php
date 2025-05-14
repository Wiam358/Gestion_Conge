<?php
session_start();
include_once('config.php');
include_once('header.php');

// Vérification DG
verifySession();
if($_SESSION['qualite'] != 'DG') {
    header('Location: accueil.php');
    exit();
}

// Récupérer les données pour les graphiques
$bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');

// 1. Congés par département
$reqDept = $bdd->query('SELECT p.depart, COUNT(d.ID) as nb_conges 
                       FROM demande d
                       JOIN personnel p ON d.CIN = p.cin
                       WHERE d.Etat = 2
                       GROUP BY p.depart');
$congesParDept = $reqDept->fetchAll();

// 2. Congés par type
$reqType = $bdd->query('SELECT Type, COUNT(*) as nb 
                        FROM demande 
                        WHERE Etat = 2
                        GROUP BY Type');
$congesParType = $reqType->fetchAll();

// 3. Congés en cours
$reqEnCours = $bdd->query("SELECT p.nom, p.prenom, p.depart, d.DateDepart, d.DateRetour 
                           FROM demande d
                           JOIN personnel p ON d.CIN = p.cin
                           WHERE d.Etat = 2
                           AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                           ORDER BY d.DateDepart");
$congesEnCours = $reqEnCours->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Visualisation | DG</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
        background-color: var(--seagreen-bg);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        /* display: flex; */
        flex-direction: column;
        margin-left: 250px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            height: 100%;
        }
        .card-title {
            color: #2E8B57;
            font-weight: 600;
        }
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        
        <!-- Première ligne : Graphiques -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="bi bi-building"></i> Par département</h3>
                    <br><br><br><br>
                    <canvas id="chartDept"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="bi bi-tags"></i> Par type de congé</h3>
                    <canvas id="chartType"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Deuxième ligne : Liste des congés en cours -->
        <div class="dashboard-card">
            <h3 class="card-title"><i class="bi bi-clock-history"></i> Congés en cours</h3>
            <br>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Département</th>
                            <th>Date Départ</th>
                            <th>Date Retour</th>
                            <th>Durée</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($congesEnCours as $conge): 
                            $jours = (strtotime($conge['DateRetour']) - strtotime($conge['DateDepart'])) / (60 * 60 * 24) + 1;
                        ?>
                        <tr>
                            <td><?= strtoupper(htmlspecialchars($conge['nom'])) ?></td>
                            <td><?= htmlspecialchars($conge['prenom']) ?></td>
                            <td><?= htmlspecialchars($conge['depart']) ?></td>
                            <td><?= htmlspecialchars($conge['DateDepart']) ?></td>
                            <td><?= htmlspecialchars($conge['DateRetour']) ?></td>
                            <td><?= $jours ?> jours</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Graphique 1 : Congés par département
        const ctxDept = document.getElementById('chartDept').getContext('2d');
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(fn($d) => "'".$d['depart']."'", $congesParDept)) ?>],
                datasets: [{
                    label: 'Congés par département',
                    data: [<?= implode(',', array_column($congesParDept, 'nb_conges')) ?>],
                    backgroundColor: [
                        '#2E8B57', '#3CB371', '#8FBC8F', '#98FB98', '#20B2AA'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Graphique 2 : Congés par type
        const ctxType = document.getElementById('chartType').getContext('2d');
        new Chart(ctxType, {
            type: 'doughnut',
            data: {
                labels: [<?= implode(',', array_map(fn($t) => "'".$t['Type']."'", $congesParType)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($congesParType, 'nb')) ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                    ],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>

    <?php include_once("footer.html"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>