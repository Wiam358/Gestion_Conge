<?php
session_start();
include_once('config.php');
include_once('header.php');

// Vérification DG
verifySession();
// if($_SESSION['qualite'] != 'DG') {
//     header('Location: accueil.php');
//     exit();
// }

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
                        AND CURDATE() BETWEEN DateDepart AND DateRetour
                        GROUP BY Type');
$congesParType = $reqType->fetchAll();

// 3. Congés en cours
$reqEnCours = $bdd->query("SELECT p.nom, p.prenom, p.depart, d.DateDepart, d.DateRetour , d.type
                           FROM demande d
                           JOIN personnel p ON d.CIN = p.cin
                           WHERE d.Etat = 2
                           AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                           ORDER BY d.DateDepart");
$congesEnCours = $reqEnCours->fetchAll();

// 4. Tous les congés approuvés de l'année (pour le Gantt)
$currentYear = date('Y');
$reqGantt = $bdd->prepare("SELECT p.nom, p.prenom, p.depart, d.DateDepart, d.DateRetour, d.type
                         FROM demande d
                         JOIN personnel p ON d.CIN = p.cin
                         WHERE d.Etat = 2
                         AND YEAR(d.DateDepart) = :currentYear
                         ORDER BY d.DateDepart");
$reqGantt->execute(['currentYear' => $currentYear]);
$congesGantt = $reqGantt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Visualisation | DG</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
    <!-- html2canvas pour le screenshot -->
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<!-- jsPDF pour générer le PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- SweetAlert pour les notifications -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
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
        margin: 0;
        padding: 0;
        min-height: 100vh;
        /* display: flex; */
        flex-direction: column;
        margin-left: 250px;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(46, 139, 87, 0.1);
            margin-bottom: 30px;
            height:500px;
            transition: transform 0.3s ease;
        }
        .card-title {
            color: #2E8B57;
            font-weight: 600;
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
            .dashboard-card{
                height: 300px;
            }
        }
/* Styles pour l'impression */
@media print {
    body * {
        visibility: hidden;
    }
    #gantt-container, #gantt-container * {
        visibility: visible;
    }
    #gantt-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
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
        
        <!-- Diagramme de Gantt -->
<div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="bi bi-calendar-range"></i> Calendrier des congés <?= $currentYear ?></h3>
        <button id="printGanttBtn" class="btn btn-success">
            <i class="bi bi-printer"></i> Exporter en PDF
        </button>
    </div>
    <div class="card-body">
        <div id="gantt-container"></div>
    </div>
</div>
<br><br><br><br><br><br>

<!-- Script Frappe Gantt -->
    <script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
    <script>
        // Préparation des données pour le Gantt
        const tasks = [
            <?php foreach($congesGantt as $conge): ?>{
                id: '<?= uniqid() ?>',
                name: '<?= htmlspecialchars($conge['prenom'].' '.strtoupper($conge['nom'])) ?>',
                start: '<?= $conge['DateDepart'] ?>',
                end: '<?= $conge['DateRetour'] ?>',
                progress: 100,
                dependencies: '',
                custom_class: '<?= $conge['type'] ?>',
                depart: '<?= htmlspecialchars($conge['depart']) ?>'
            },
            <?php endforeach; ?>
        ];

        // Initialisation du Gantt avec la vue sur l'année courante
        const gantt = new Gantt("#gantt-container", tasks, {
            header_height: 50,
            column_width: 30,
            step: 24,
            view_modes: ['Day', 'Week', 'Month', 'Year'],
            view_mode: 'Month',
            date_format: 'YYYY-MM-DD',
            custom_popup_html: function(task) {
                return `
                    <div class="details-container">
                        <h5>${task.name}</h5>
                        <p>Département: ${task.depart}</p>
                        <p>Type: ${task.custom_class}</p>
                        <p>Du ${new Date(task.start).toLocaleDateString()} au ${new Date(task.end).toLocaleDateString()}</p>
                    </div>
                `;
            },
            on_date_change: function(task, start, end) {
                // Optionnel: pour rendre les barres déplaçables si besoin
                console.log(task, start, end);
            }
        });
        
        // Définir la plage visible comme l'année courante
        const yearStart = new Date(<?= $currentYear ?>, 0, 1);
        const yearEnd = new Date(<?= $currentYear ?>, 11, 31);
        gantt.change_view_mode('Month');
        gantt.setup_date_values();
    </script>

<style>
        #gantt-container {
            width: 100%;
            height: 600px;
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .gantt .bar.annuel { fill: #3CB371; }
        .gantt .bar.maladie { fill: #FF6384; }
        .gantt .bar.maternite { fill: #9966FF; }
        .gantt .bar.autre { fill: #FFCE56; }
        
        .gantt-container {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
    </style>
        <!-- Deuxième ligne : Liste des congés en cours -->
        <!-- Liste des congés -->
<div class="dashboard-card">
    <div class="card-header">
        <div class="card-title"><h4><i class="bi bi-list-ul"></i> <b>Congés en cours dans l'organisme</b></h4></div>
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
                            <th>Département</th>
                            <th>Type</th>
                            <th>Période</th>
                            <th>Jours restants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($congesEnCours as $conge): 
                            $jours_restants = (strtotime($conge['DateRetour']) - time()) / (60 * 60 * 24);
                            $jours_restants = max(0, ceil($jours_restants)); // évite les valeurs négatives
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.getElementById('printGanttBtn').addEventListener('click', async function() {
    const { jsPDF } = window.jspdf;
    const ganttContainer = document.getElementById('gantt-container');
    
    // Afficher un loader
    Swal.fire({
        title: 'Préparation du PDF',
        html: 'Génération du diagramme en cours...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        // 1. Capturer le contenu
        const canvas = await html2canvas(ganttContainer, {
            scale: 2, // Qualité x2
            logging: false,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#FFFFFF'
        });

        // 2. Créer le PDF
        const pdf = new jsPDF('landscape');
        const imgData = canvas.toDataURL('image/png');
        
        // Calculer les dimensions pour remplir la page
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const imgRatio = canvas.width / canvas.height;
        let imgWidth = pdfWidth;
        let imgHeight = pdfWidth / imgRatio;
        
        if (imgHeight > pdfHeight) {
            imgHeight = pdfHeight;
            imgWidth = imgHeight * imgRatio;
        }

        // Centrer l'image
        const x = (pdfWidth - imgWidth) / 2;
        const y = (pdfHeight - imgHeight) / 2;
        
        pdf.addImage(imgData, 'PNG', x, y, imgWidth, imgHeight);
        
        // 3. Télécharger ou imprimer
        Swal.fire({
            title: 'Export réussi',
            html: 'Voulez-vous <b>imprimer</b> ou <b>télécharger</b> le PDF ?',
            icon: 'success',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-printer"></i> Imprimer',
            denyButtonText: '<i class="bi bi-download"></i> Télécharger',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                pdf.autoPrint();
                pdf.output('dataurlnewwindow');
            } else if (result.isDenied) {
                pdf.save(`calendrier-conges-${new Date().toISOString().split('T')[0]}.pdf`);
            }
        });

    } catch (error) {
        Swal.fire({
            title: 'Erreur',
            text: 'Une erreur est survenue lors de la génération du PDF',
            icon: 'error'
        });
        console.error('Erreur génération PDF:', error);
    }
});
// Avant pdf.addImage()
pdf.setFontSize(20);
pdf.setTextColor(46, 139, 87); // Couleur #2E8B57
pdf.text('Calendrier des Congés ' + new Date().getFullYear(), pdfWidth/2, 15, { align: 'center' });

// Décaler l'image vers le bas
pdf.addImage(imgData, 'PNG', x, y + 20, imgWidth, imgHeight);
pdf.setFontSize(60);
pdf.setTextColor(230, 230, 230); // Gris clair
pdf.setGState(new pdf.GState({ opacity: 0.3 }));
pdf.text('CONFIDENTIEL', pdfWidth/2, pdfHeight/2, {
    angle: 45,
    align: 'center'
});
pdf.setGState(new pdf.GState({ opacity: 1 }));
</script>
</body>
</html>