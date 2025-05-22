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
                       AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                       GROUP BY p.depart');
$congesParDept = $reqDept->fetchAll();

// 2. Congés par type
$reqType = $bdd->query('SELECT Type, COUNT(*) as nb 
                        FROM demande 
                        WHERE Etat = 2
                        GROUP BY Type');
$congesParType = $reqType->fetchAll();

// 3. Congés en cours (pour la table)
$reqEnCours = $bdd->query("SELECT p.nom, p.prenom, p.depart, d.DateDepart, d.DateRetour, d.type
                           FROM demande d
                           JOIN personnel p ON d.CIN = p.cin
                           WHERE d.Etat = 2
                           AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                           ORDER BY d.DateDepart");
$congesEnCours = $reqEnCours->fetchAll();

// 4. Tous les congés acceptés (pour le Gantt)
$reqAllConges = $bdd->query("SELECT p.nom, p.prenom, p.depart, d.DateDepart, d.DateRetour, d.type
                             FROM demande d
                             JOIN personnel p ON d.CIN = p.cin
                             WHERE d.Etat = 2
                             ORDER BY p.depart, p.nom, p.prenom");
$allConges = $reqAllConges->fetchAll();

// Préparer les données pour le diagramme de Gantt
$ganttData = [];
foreach ($allConges as $conge) {
    $ganttData[] = [
        'employee' => htmlspecialchars($conge['prenom'] . ' ' . strtoupper($conge['nom'])),
        'department' => htmlspecialchars($conge['depart']),
        'startDate' => $conge['DateDepart'],
        'endDate' => $conge['DateRetour'],
        'type' => $conge['type']
    ];
}

// Convertir en JSON pour JavaScript
$ganttDataJson = json_encode($ganttData);
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
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
            margin: 0;
            padding: 0;
            min-height: 100vh;
            margin-left: 250px;
        }
        .dashboard-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(46, 139, 87, 0.1);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        .card-title {
            color: #2E8B57;
            font-weight: 600;
        }
        .gantt-container {
            height: 600px;
            overflow-x: auto;
            overflow-y: hidden;
        }
        .gantt-chart {
            min-width: 100%;
            min-height: 600px;
        }
        /* Autres styles existants... */
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

        <!-- Diagramme de Gantt -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-calendar-range"></i> Planning des congés</h3>
            </div>
            <div class="card-body">
                <div class="gantt-container">
                    <canvas id="ganttChart" class="gantt-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Liste des congés en cours -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title"><h4><i class="bi bi-list-ul"></i> Congés en cours dans l'organisme</h4></div>
            </div>
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
                                    <th>Département</th>
                                    <th>Type</th>
                                    <th>Période</th>
                                    <th>Jours restants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($congesEnCours as $conge): 
                                    $jours_restants = (strtotime($conge['DateRetour']) - time()) / (60 * 60 * 24);
                                    $jours_restants = max(0, ceil($jours_restants));
                                ?>
                                <tr class="<?= $jours_restants <= 3 ? 'conge-urgent' : '' ?>">
                                    <td><?= htmlspecialchars($conge['prenom'].' '.strtoupper($conge['nom'])) ?></td>
                                    <td><?= htmlspecialchars($conge['depart']) ?></td>
                                    <td>
                                        <span class="badge badge-conge bg-<?= 
                                            $conge['type'] == 'annuel' ? 'primary' : 
                                            ($conge['type'] == 'maladie' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= htmlspecialchars($conge['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($conge['DateDepart']) ?> au <?= htmlspecialchars($conge['DateRetour']) ?></td>
                                    <td class="fw-bold <?= $jours_restants <= 3 ? 'text-danger' : 'text-success' ?>">
                                        <?= $jours_restants ?> jours
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
        // Graphique 1 : Congés par département
        const ctxDept = document.getElementById('chartDept').getContext('2d');
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(fn($d) => "'".addslashes($d['depart'])."'", $congesParDept)) ?>],
                datasets: [{
                    label: 'Congés par département',
                    data: [<?= implode(',', array_column($congesParDept, 'nb_conges')) ?>],
                    backgroundColor: ['#2E8B57', '#3CB371', '#8FBC8F', '#98FB98', '#20B2AA'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Graphique 2 : Congés par type
        const ctxType = document.getElementById('chartType').getContext('2d');
        new Chart(ctxType, {
            type: 'doughnut',
            data: {
                labels: [<?= implode(',', array_map(fn($t) => "'".addslashes($t['Type'])."'", $congesParType)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($congesParType, 'nb')) ?>],
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Graphique 3 : Diagramme de Gantt
        const ganttData = <?= $ganttDataJson ?>;
        
        // Préparer les données pour Chart.js
        const employees = [...new Set(ganttData.map(item => item.employee))];
        const datasets = employees.map(employee => {
            const employeeConges = ganttData.filter(item => item.employee === employee);
            return {
                label: employee,
                data: employeeConges.map(conge => ({
                    x: conge.startDate,
                    y: employees.indexOf(employee),
                    x2: conge.endDate,
                    department: conge.department,
                    type: conge.type
                })),
                backgroundColor: getColorByType(employeeConges[0].type),
                borderColor: '#2F4F4F',
                borderWidth: 1,
                borderSkipped: false,
                barPercentage: 0.8,
                categoryPercentage: 0.8
            };
        });

        // Créer le graphique Gantt
        const ctxGantt = document.getElementById('ganttChart').getContext('2d');
        new Chart(ctxGantt, {
            type: 'bar',
            data: {
                labels: employees,
                datasets: datasets
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'dd MMM yyyy',
                            displayFormats: {
                                day: 'dd MMM'
                            }
                        },
                        min: '<?= date('Y-m-d', strtotime('-1 month')) ?>',
                        max: '<?= date('Y-m-d', strtotime('+6 months')) ?>',
                        position: 'top'
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            autoSkip: false
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].dataset.label;
                            },
                            label: function(context) {
                                const data = context.raw;
                                return [
                                    `Département: ${data.department}`,
                                    `Type: ${data.type}`,
                                    `Du ${formatDate(data.x)} au ${formatDate(data.x2)}`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Fonction utilitaire pour obtenir une couleur selon le type de congé
        function getColorByType(type) {
            const colors = {
                'annuel': 'rgba(46, 139, 87, 0.7)',
                'maladie': 'rgba(220, 53, 69, 0.7)',
                'maternite': 'rgba(255, 193, 7, 0.7)',
                'paternite': 'rgba(13, 110, 253, 0.7)',
                'exceptionnel': 'rgba(111, 66, 193, 0.7)'
            };
            return colors[type] || 'rgba(108, 117, 125, 0.7)';
        }

        // Fonction pour formater les dates
        function formatDate(dateString) {
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('fr-FR', options);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>