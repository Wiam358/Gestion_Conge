<?php
session_start();
include_once('config.php');
include_once('header.php');
verifySession();

// Gestion de la marque d'impression
if(isset($_GET['mark_printed'])) {
    $id = (int)$_GET['mark_printed'];
    
    try {
        $stmt = $bdd->prepare("UPDATE demandes 
                              SET est_imprime = 1, 
                                  date_impression = NOW() 
                              WHERE ID = ?");
        $stmt->execute([$id]);
        
        // Journalisation pour débogage
        error_log("Demande $id marquée comme imprimée");
        
        header("Location: ".strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    } catch(PDOException $e) {
        error_log("Erreur SQL: ".$e->getMessage());
        die("Erreur de mise à jour de la base de données");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Décisions de congé à imprimer</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    /* Style identique à votre fichier original */
    :root {
        --primary-color: #3CB371;
        --primary-dark: #2E8B57;
        --primary-light: #8FBC8F;
        --primary-lighter: #98FB98;
        --bg-color: #F8F9FA;
        --card-bg: #FFFFFF;
        --text-color: #495057;
        --warning-color: #FFC107;
        --danger-color: #DC3545;
        --success-color: #28A745;
    }
    
    body {
        margin-left: 250px;
        background-color: var(--bg-color);
        font-family: 'Segoe UI', system-ui, sans-serif;
    }
    
    .dg-header {
        background: linear-gradient(135deg, var(--primary-dark), #1a4b2e);
        color: white;
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .dg-title {
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .dg-icon {
        font-size: 2rem;
    }
    
    .demands-card {
        background: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(60, 179, 113, 0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .table-responsive {
        overflow-x: auto;
        border-radius: 10px;
    }
    
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table th {
        background-color: var(--primary-dark);
        color: white;
        padding: 12px 15px;
        text-align: center;
        position: sticky;
        top: 0;
    }
    
    .table td {
        padding: 12px 15px;
        text-align: center;
        border-bottom: 1px solid #dee2e6;
    }
    
    .table tr:nth-child(even) {
        background-color: rgba(60, 179, 113, 0.05);
    }
    
    .table tr:hover {
        background-color: rgba(60, 179, 113, 0.1);
    }
    
    .badge-dg {
        background-color: #6f42c1;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .action-buttons .btn {
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-validate {
        background-color: var(--success-color);
        border: none;
    }
    .btn-validate:hover {
        background-color: #2E8B57;
        border: none;
    }
    
    .btn-cancel {
        background-color: var(--danger-color);
        border: none;
    }
    
    /* Styles pour les onglets */
    .nav-tabs {
        border-bottom: 2px solid var(--primary-dark);
    }
    
    .nav-tabs .nav-link {
        color: var(--text-color);
        font-weight: 500;
        border: none;
        padding: 12px 20px;
    }
    
    .nav-tabs .nav-link.active {
        color: var(--primary-dark);
        background-color: transparent;
        border-bottom: 3px solid var(--primary-dark);
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: var(--primary-dark);
    }
    
    .tab-content {
        padding: 20px 0;
    }
    
    @media (max-width: 768px) {
        .dg-header {
            padding: 1.5rem;
        }
        
        .table th, .table td {
            padding: 8px 10px;
            font-size: 0.9rem;
        }
        
        .nav-tabs .nav-link {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
    }
    
    /* Style spécifique pour l'impression */
    @media print {
        .no-print {
            display: none !important;
        }
        
        body {
            background-color: white;
            font-size: 12pt;
        }
        
        .table {
            font-size: 10pt;
        }
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
        <div class="dg-header animate__animated animate__fadeIn">
            <h1 class="dg-title">
                <i class="bi bi-printer-fill dg-icon"></i>
                Gestion des décisions de congé
            </h1>
            <p class="mb-0 mt-2">
                <i class="bi bi-info-circle"></i> Interface RH - Demandes validées par la Direction Générale
            </p>
        </div>
        
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="imprimante-tab" data-bs-toggle="tab" data-bs-target="#imprimante" type="button" role="tab" aria-controls="imprimante" aria-selected="true">
                    <i class="bi bi-printer"></i> À imprimer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive" type="button" role="tab" aria-controls="archive" aria-selected="false">
                    <i class="bi bi-archive"></i> Archives
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Onglet Imprimante -->
            <div class="tab-pane fade show active" id="imprimante" role="tabpanel" aria-labelledby="imprimante-tab">
                <div class="demands-card animate__animated animate__fadeInUp">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Personnel</th>
                                    <th>Service</th>
                                    <th>Dates</th>
                                    <th>Jours</th>
                                    <th>Validé par</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                connexionBdd($bdd);
                                $reponse = $bdd->query("SELECT d.* FROM demande d 
                                                     JOIN personnel p ON d.CIN = p.CIN 
                                                     WHERE d.etat = '2' AND d.DateDepart >= NOW()
                                                     ORDER BY d.DateDepart ASC");
                                while($donnees = $reponse->fetch()): 
                                    $infos = InfosPersonnel($donnees['CIN']);
                                    $days = dateDiff($donnees['DateRetour'], $donnees['DateDepart']) + 1;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($donnees['ID']) ?></td>
                                    <td>
                                        <strong><?= strtoupper(htmlspecialchars($infos['nom'])) ?> <?= htmlspecialchars($infos['prenom']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($donnees['CIN']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($infos['service']) ?></td>
                                    <td>
                                        <div><?= date('d/m/Y', strtotime($donnees['DateDepart'])) ?></div>
                                        <div class="small text-muted">au <?= date('d/m/Y', strtotime($donnees['DateRetour'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $days ?> jours</span>
                                    </td>
                                    <td>
                                        Direction Générale
                                        <div class="small text-muted">DG</div>
                                    </td>
                                    <td class="no-print">
                                        <div class="action-buttons d-flex gap-2 justify-content-center">
                                            <button onclick="imprimerDecision(
                                                '<?= $donnees['ID'] ?>',
                                                '<?= htmlspecialchars(addslashes(strtoupper($infos['nom']).' '.$infos['prenom'])) ?>',
                                                '<?= htmlspecialchars($donnees['CIN']) ?>',
                                                '<?= htmlspecialchars($infos['service']) ?>',
                                                '<?= date('d/m/Y', strtotime($donnees['DateDepart'])) ?>',
                                                '<?= date('d/m/Y', strtotime($donnees['DateRetour'])) ?>',
                                                '<?= $days ?>'
                                            )" class="btn btn-validate text-white">
                                                <i class="bi bi-printer"></i> Imprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                
            </div>
            
            <!-- Onglet Archive -->
            <div class="tab-pane fade" id="archive" role="tabpanel" aria-labelledby="archive-tab">
                <div class="demands-card animate__animated animate__fadeInUp">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Personnel</th>
                                    <th>Service</th>
                                    <th>Dates</th>
                                    <th>Jours</th>
                                    
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $archiveReponse = $bdd->query("SELECT d.* FROM demande d 
                                                          JOIN personnel p ON d.CIN = p.CIN 
                                                          WHERE NOW() >= d.DateDepart
                                                          ORDER BY d.ID DESC");
                                while($archiveDonnees = $archiveReponse->fetch()): 
                                    $archiveInfos = InfosPersonnel($archiveDonnees['CIN']);
                                    $archiveDays = dateDiff($archiveDonnees['DateRetour'], $archiveDonnees['DateDepart']) + 1;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($archiveDonnees['ID']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars(strtoupper($archiveInfos['nom'])) ?> <?= htmlspecialchars($archiveInfos['prenom']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($archiveDonnees['CIN']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($archiveInfos['service']) ?></td>
                                    <td>
                                        <div><?= date('d/m/Y', strtotime($archiveDonnees['DateDepart'])) ?></div>
                                        <div class="small text-muted">au <?= date('d/m/Y', strtotime($archiveDonnees['DateRetour'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $archiveDays ?> jours</span>
                                    </td>
                                    
                                    
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="text-center no-print mt-4">
                    <button onclick="imprimerToutesDecisions()" class="btn btn-validate text-white" style="padding: 10px 20px;">
                        <i class="bi bi-printer-fill"></i> Imprimer toutes les décisions
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function imprimerDecision(idDemande, nomComplet, cin, service, dateDepart, dateRetour, jours) {
        const printWindow = window.open('', '_blank');
        const currentDate = new Date().toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
        const managerName = "<?= strtoupper(htmlspecialchars($_SESSION['nom']) ?? '') ?> <?= htmlspecialchars($_SESSION['prenom'] ?? '') ?>";

        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <title>Autorisation de Congé #${idDemande}</title>
                <style>
                    @page {
                        size: A4;
                        margin: 2cm;
                    }
                    body {
                        font-family: 'Arial', sans-serif;
                        line-height: 1.6;
                        color: #333;
                        padding: 0;
                        margin: 0;
                    }
                    .letterhead {
                        border-bottom: 2px solid #2E8B57;
                        padding-bottom: 1rem;
                        margin-bottom: 2rem;
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-end;
                    }
                    .logo {
                        height: 80px;
                    }
                    .company-info {
                        text-align: right;
                    }
                    h1 {
                        color: #2E8B57;
                        text-align: center;
                        margin: 2rem 0;
                        font-size: 1.8rem;
                    }
                    .document-info {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 2rem;
                    }
                    .content {
                        margin: 2rem 0;
                    }
                    .signature-block {
                        margin-top: 4rem;
                        display: flex;
                        justify-content: space-between;
                    }
                    .signature-line {
                        width: 300px;
                        border-top: 1px solid #333;
                        margin-top: 3rem;
                        text-align: center;
                        padding-top: 0.5rem;
                    }
                    .footer {
                        margin-top: 3rem;
                        font-size: 0.8rem;
                        color: #666;
                        text-align: center;
                    }
                    .request-details {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 2rem 0;
                    }
                    .request-details th, .request-details td {
                        border: 1px solid #ddd;
                        padding: 12px;
                        text-align: left;
                    }
                    .request-details th {
                        background-color: #f5f5f5;
                    }
                    .stamp {
                        position: absolute;
                        right: 50px;
                        opacity: 0.2;
                        font-size: 5rem;
                        color: #2E8B57;
                        transform: rotate(-15deg);
                    }
                </style>
            </head>
            <body>
                <div class="letterhead">
                    <div>
                        <h2 style="margin: 0;">${nomComplet}</h2>
                        <p style="margin: 0;">Demande de congé #${idDemande}</p>
                    </div>
                    <div class="company-info">
                        <p style="margin: 0;">Chambre de commerce</p>
                        <p style="margin: 0;">Bd Mohammed 5</p>
                        <p style="margin: 0;">Casablanca, Maroc</p>
                    </div>
                </div>

                <div class="document-info">
                    <div>
                        <p><strong>Date d'émission:</strong> ${currentDate}</p>
                    </div>
                    <div>
                        <p><strong>Référence:</strong> ${idDemande}-${new Date().getFullYear()}</p>
                    </div>
                </div>

                <h1>AUTORISATION DE CONGÉ</h1>

                <div class="content">
                    <p>Je soussigné, <strong>${managerName}</strong>, en ma qualité de Responsable des Ressources Humaines,</p>
                    
                    <p>autorise par la présente <strong>${nomComplet}</strong> (Matricule: ${cin}) à prendre un congé selon les modalités suivantes :</p>
                    
                    <table class="request-details">
                        <tr>
                            <th width="30%">Période de congé</th>
                            <td>Du ${dateDepart} au ${dateRetour} (${jours} jours)</td>
                        </tr>
                        <tr>
                            <th>Service</th>
                            <td>${service}</td>
                        </tr>
                    </table>
                    
                    <p>Cette autorisation est valable sous réserve des dispositions du règlement intérieur et de la convention collective applicable.</p>
                </div>

                <div class="signature-block">
                    <div>
                        <p>Fait à <strong>Casablanca</strong>, le ${currentDate}</p>
                        <div class="signature-line">
                            Signature
                        </div>
                    </div>
                    <div>
                        <p>Pour accord,</p>
                        <div class="signature-line">
                            Signature du responsable
                        </div>
                    </div>
                </div>

                <div class="stamp">
                    CCISCS
                </div>

                <div class="footer">
                    <p>Document généré automatiquement - Ne pas modifier - Version du ${currentDate}</p>
                    <p>CCISCS - Service des Ressources Humaines</p>
                </div>
            </body>
            </html>
        `);

        printWindow.document.close();
        
        // Détection de l'impression
        printWindow.onafterprint = function() {
        // Envoyer la requête pour marquer comme imprimé
        fetch(`?mark_printed=${idDemande}`)
            .then(response => {
                if(response.ok) {
                    // Rafraîchir toute la page après un court délai
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            })
            .catch(error => console.error('Error:', error));
            
        printWindow.close();
    };
    
    setTimeout(() => {
        printWindow.print();
    }, 500);
}
    
    function imprimerToutesDecisions() {
    // Créer une nouvelle fenêtre
    const printWindow = window.open('', '_blank');
    
    // Récupérer le contenu du tableau ARCHIVE
    const tableau = document.querySelector('#archive .table-responsive').outerHTML;
    
    // Écrire le contenu dans la nouvelle fenêtre
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Archives des décisions de congé</title>
            <style>
                body { font-family: Arial; margin: 1cm; }
                table { width: 100%; border-collapse: collapse; }
                th { background-color: #2E8B57; color: white; padding: 10px; text-align: center; }
                td { padding: 8px; text-align: center; border-bottom: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .badge { padding: 3px 8px; border-radius: 10px; font-weight: 500; }
                .text-muted { color: #6c757d; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <h2 style="text-align:center;color:#2E8B57;">Archives des décisions de congé</h2>
            <p style="text-align:center;color:#555;">Document généré le ${new Date().toLocaleDateString('fr-FR')}</p>
            ${tableau}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Lancer l'impression après un court délai
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
    </script>
    
</body>
</html>
<?php 
if(isset($reponse)) {
    $reponse->closeCursor();
}
if(isset($archiveReponse)) {
    $archiveReponse->closeCursor();
}
?>