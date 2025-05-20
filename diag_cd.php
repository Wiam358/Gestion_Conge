<?php
session_start();
include_once('config.php');
include_once('header.php');

verifySession();
$serviceCD = $_SESSION['depart'] ?? null;

if(!$serviceCD || $_SESSION['qualite'] != 'CD') {
    header('Location: accueil.php');
    exit();
}

try {
    connexionBdd($bdd);
    // 1. Données pour le diagramme circulaire
    $reqTypes = $bdd->prepare("SELECT 
                             type, 
                             COUNT(*) as nombre
                             FROM demande d
                             JOIN personnel p ON d.CIN = p.cin
                             WHERE p.depart = ?
                             AND d.etat = 2
                             AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                             GROUP BY type");
    $reqTypes->execute([$serviceCD]);
    $typesConges = $reqTypes->fetchAll();

    // 2. Données pour l'histogramme
    $reqMois = $bdd->prepare("SELECT 
                            MONTH(DateDepart) as mois,
                            COUNT(*) as nombre
                            FROM demande d
                            JOIN personnel p ON d.CIN = p.cin
                            WHERE p.depart = ?
                            AND d.etat = 2
                            AND YEAR(DateDepart) = YEAR(CURDATE())
                            GROUP BY MONTH(DateDepart)");
    $reqMois->execute([$serviceCD]);
    $congesParMois = $reqMois->fetchAll();

    // 3. Liste des congés en cours
    $reqListe = $bdd->prepare("SELECT 
                              p.nom, p.prenom,
                              d.type,
                              DATE_FORMAT(d.DateDepart, '%d/%m/%Y') as debut,
                              DATE_FORMAT(d.DateRetour, '%d/%m/%Y') as fin,
                              GREATEST(0, DATEDIFF(d.DateRetour, CURDATE()) + 1) as jours_restants
                              FROM personnel p
                              INNER JOIN demande d ON p.cin = d.CIN
                              WHERE p.depart = ?
                              AND d.etat = 2
                              AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                              ORDER BY ID ASC");
    $reqListe->execute([$serviceCD]);
    $congesEnCours = $reqListe->fetchAll();

} catch(PDOException $e) {
    error_log("ERREUR SQL: " . $e->getMessage());
    $errorMsg = "Erreur de chargement des données";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Visualisation | CD</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --seagreen-dark: #2E8B57;
            --seagreen-primary: #3CB371;
            --seagreen-light: #8FBC8F;
            --seagreen-lighter: #98FB98;
            --seagreen-bg: #F5FFFA;
            --seagreen-text: #2F4F4F;
        }
        
        body {
            background-color: var(--seagreen-bg);
            color: var(--seagreen-text);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin-left: 250px;
        }
        
        .dashboard-container {
            max-width: 95%;
            padding: 0 20px;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(46, 139, 87, 0.1);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--seagreen-dark), var(--seagreen-primary));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .chart-container {
            height: 350px;
            position: relative;
            margin: 15px 0;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--seagreen-primary);
            color: white;
            border: none;
            padding: 12px 15px;
        }
        
        .table tbody tr {
            transition: background-color 0.2s;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(60, 179, 113, 0.08);
        }
        
        .badge-conge {
            font-size: 0.85em;
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 500;
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }
        
        .conge-urgent {
            background-color: rgba(255, 193, 7, 0.15);
            position: relative;
        }
        
        .conge-urgent::after {
            content: "⚠ Urgent";
            position: absolute;
            right: 15px;
            color: #DC3545;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container py-4">
        <h2 class="mb-4 text-center"><i class="bi bi"></i> Tableau de bord des congés - <?= htmlspecialchars($serviceCD) ?></h2>
        
        <!-- Première ligne : Graphiques -->
        <div class="row">
            <!-- Diagramme circulaire -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h4><i class="bi bi-pie-chart"></i> Répartition par type de congé</h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($typesConges)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Aucun congé en cours dans votre équipe
                            </div>
                        <?php else: ?>
                            <div class="chart-container">
                                <canvas id="chartTypes"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Histogramme -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h4><i class="bi bi-bar-chart"></i> Congés par mois (<?= date('Y') ?>)</h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartMois"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des congés -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="bi bi-list-ul"></i> Congés en cours dans votre équipe</h4>
            </div>
            <br>
            <div class="card-body">
                <?php if(empty($congesEnCours)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Aucun membre n'est actuellement en congé
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Période</th>
                                    <th>Jours restants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($congesEnCours as $conge): ?>
                                <tr class="<?= $conge['jours_restants'] <= 3 ? 'conge-urgent' : '' ?>">
                                    <td><?= htmlspecialchars($conge['prenom'].' '.strtoupper($conge['nom'])) ?></td>
                                    <td>
                                        <span class="badge badge-conge bg-<?= 
                                            $conge['type'] == 'annuel' ? 'primary' : 
                                            ($conge['type'] == 'maladie' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= htmlspecialchars($conge['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= $conge['debut'] ?> au <?= $conge['fin'] ?></td>
                                    <td class="fw-bold <?= $conge['jours_restants'] <= 3 ? 'text-danger' : 'text-success' ?>">
                                        <?= $conge['jours_restants'] ?> jours
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Diagramme circulaire
    <?php if(!empty($typesConges)): ?>
    const ctxTypes = document.getElementById('chartTypes').getContext('2d');
    new Chart(ctxTypes, {
        type: 'doughnut',
        data: {
            labels: [<?= implode(',', array_map(function($t) { 
                return "'".htmlspecialchars($t['type'])."'"; 
            }, $typesConges)) ?>],
            datasets: [{
                data: [<?= implode(',', array_column($typesConges, 'nombre')) ?>],
                backgroundColor: [
                    '#36a2eb', '#ff6384', '#4bc0c0', '#ffcd56', '#9966ff',
                    '#17a2b8', '#6f42c1', '#e83e8c', '#20c997', '#fd7e14'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${context.label}: ${context.raw} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    // Histogramme
    const moisNoms = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
    const dataMois = Array(12).fill(0);
    <?php foreach($congesParMois as $mois): ?>
        dataMois[<?= $mois['mois']-1 ?>] = <?= $mois['nombre'] ?>;
    <?php endforeach; ?>
    
    const ctxMois = document.getElementById('chartMois').getContext('2d');
    new Chart(ctxMois, {
        type: 'bar',
        data: {
            labels: moisNoms,
            datasets: [{
                label: 'Congés',
                data: dataMois,
                backgroundColor: 'rgba(60, 179, 113, 0.7)',
                borderColor: 'rgba(46, 139, 87, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Nombre de congés',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>