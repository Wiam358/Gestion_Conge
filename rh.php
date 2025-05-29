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

// Gestion des paramètres de recherche, filtrage et onglet actif
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : '';
$mois = isset($_GET['mois']) ? (int)$_GET['mois'] : '';
$departement = isset($_GET['departement']) ? trim($_GET['departement']) : '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'imprimante'; // Par défaut : onglet "À imprimer"
connexionBdd($bdd);
// Récupérer les années disponibles
$anneesStmt = $bdd->query("SELECT DISTINCT YEAR(DateDepart) AS annee FROM demande WHERE DateDepart IS NOT NULL ORDER BY annee DESC");
$annees = $anneesStmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les départements disponibles
$deptsStmt = $bdd->query("SELECT DISTINCT service FROM personnel WHERE service IS NOT NULL ORDER BY service");
$departements = $deptsStmt->fetchAll(PDO::FETCH_COLUMN);

// Liste statique des mois
$moisListe = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Décisions de congé à imprimer</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Ajout de html2pdf.js pour générer les PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
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
    
    .search-form {
        margin-bottom: 1.5rem;
    }
    
    .search-form .input-group {
        width: 550px;
    }
    
    .filter-group {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }


    .filter-group select {
        max-width: 200px;
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
        
        .search-form .input-group,
        .filter-group select {
            max-width: 100%;
        }
    }
    
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
    .btn-reset {
    background-color: var(--warning-color);
    border: none;
}
.btn-reset:hover {
    background-color: #e0a800;
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
                <button class="nav-link <?= $tab === 'imprimante' ? 'active' : '' ?>" id="imprimante-tab" data-bs-toggle="tab" data-bs-target="#imprimante" type="button" role="tab" aria-controls="imprimante" aria-selected="<?= $tab === 'imprimante' ? 'true' : 'false' ?>">
                    <i class="bi bi-printer"></i> À imprimer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 'archive' ? 'active' : '' ?>" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive" type="button" role="tab" aria-controls="archive" aria-selected="<?= $tab === 'archive' ? 'true' : 'false' ?>">
                    <i class="bi bi-archive"></i> Archives
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Onglet Imprimante -->
            <div class="tab-pane fade <?= $tab === 'imprimante' ? 'show active' : '' ?>" id="imprimante" role="tabpanel" aria-labelledby="imprimante-tab">
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
            <div class="tab-pane fade <?= $tab === 'archive' ? 'show active' : '' ?>" id="archive" role="tabpanel" aria-labelledby="archive-tab">
                <div class="demands-card animate__animated animate__fadeInUp">
                    <!-- Formulaire de recherche et filtres -->
                    <form class="search-form" method="GET" action="" id="archive-search-form">
    <input type="hidden" name="tab" value="archive">
    <div class="filter-group">
        <select name="annee" class="form-select">
            <option value="">Toutes les années</option>
            <?php foreach ($annees as $an): ?>
                <option value="<?= $an ?>" <?= $annee == $an ? 'selected' : '' ?>><?= $an ?></option>
            <?php endforeach; ?>
        </select>
        <select name="mois" class="form-select">
            <option value="">Tous les mois</option>
            <?php foreach ($moisListe as $num => $nom): ?>
                <option value="<?= $num ?>" <?= $mois == $num ? 'selected' : '' ?>><?= $nom ?></option>
            <?php endforeach; ?>
        </select>
        <select name="departement" class="form-select">
            <option value="">Tous les départements</option>
            <?php foreach ($departements as $dept): ?>
                <option value="<?= htmlspecialchars($dept) ?>" <?= $departement == $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Rechercher par nom, CIN ou service" value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-validate text-white" type="submit">
                <i class="bi bi-search"></i> Rechercher
            </button>
            <button class="btn btn-reset text-white" type="reset" onclick="resetForm()">
                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
            </button>
        </div>
    </div>
</form>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Personnel</th>
                                    <th>Service</th>
                                    <th>Dates</th>
                                    <th>Jours</th>
                                    <th class="no-print">Décision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Requête SQL avec recherche et filtres
                                $sql = "SELECT d.* FROM demande d 
                                        JOIN personnel p ON d.CIN = p.CIN 
                                        WHERE NOW() >= d.DateDepart";
                                $params = [];
                                if ($search) {
                                    $sql .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.CIN LIKE :search OR p.service LIKE :search)";
                                    $params[':search'] = '%' . $search . '%';
                                }
                                if ($annee) {
                                    $sql .= " AND YEAR(d.DateDepart) = :annee";
                                    $params[':annee'] = $annee;
                                }
                                if ($mois) {
                                    $sql .= " AND MONTH(d.DateDepart) = :mois";
                                    $params[':mois'] = $mois;
                                }
                                if ($departement) {
                                    $sql .= " AND p.service = :departement";
                                    $params[':departement'] = $departement;
                                }
                                $sql .= " ORDER BY d.ID DESC";
                                
                                $archiveReponse = $bdd->prepare($sql);
                                $archiveReponse->execute($params);
                                
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
                                    <td class="no-print">
                                        <div class="action-buttons d-flex gap-2 justify-content-center">
                                            <button onclick="telechargerDecision(
                                                '<?= $archiveDonnees['ID'] ?>',
                                                '<?= htmlspecialchars(addslashes(strtoupper($archiveInfos['nom']).' '.$archiveInfos['prenom'])) ?>',
                                                '<?= htmlspecialchars($archiveDonnees['CIN']) ?>',
                                                '<?= htmlspecialchars($archiveInfos['service']) ?>',
                                                '<?= date('d/m/Y', strtotime($archiveDonnees['DateDepart'])) ?>',
                                                '<?= date('d/m/Y', strtotime($archiveDonnees['DateRetour'])) ?>',
                                                '<?= $archiveDays ?>'
                                            )" class="btn btn-validate text-white">
                                                <i class="bi bi-download"></i> Télécharger PDF
                                            </button>
                                        </div>
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
        
        printWindow.onafterprint = function() {
            fetch(`?mark_printed=${idDemande}`)
                .then(response => {
                    if(response.ok) {
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
    
    function telechargerDecision(idDemande, nomComplet, cin, service, dateDepart, dateRetour, jours) {
        const currentDate = new Date().toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
        const managerName = "<?= strtoupper(htmlspecialchars($_SESSION['nom']) ?? '') ?> <?= htmlspecialchars($_SESSION['prenom'] ?? '') ?>";

        const element = document.createElement('div');
        element.innerHTML = `
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
        `;

        const options = {
            margin: 0,
            filename: `Decision_Conge_${idDemande}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().from(element).set(options).save();
    }
    
    function imprimerToutesDecisions() {
        const printWindow = window.open('', '_blank');
        const tableau = document.querySelector('#archive .table-responsive').outerHTML;
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Archives des décisions</title>
                <style>
                    body { font-family: Arial; margin: 1cm; font-weight: ; }
                    table { width: 100%; border-collapse: collapse; width: ; }
                    th { background-color: #2E8b57; padding: ; color: white; }
                    td { padding: 8px; text-align: center; border-bottom: 1px solid #ddd; padding: ; }
                    tr:nth-child(even) { background-color: #f9f9f9f; }
                    .badge { padding: 3px 8px; padding: ; border-radius: 10px; font-weight: 500; }
                    .text-muted { color: #6c757d; font-size: 0.9em; }
                </style>
            </head>
            <body>
                <h2 style="text-align:center;color:#2E8B57;">Archives des décisions stratégiques de congé à la Chambre</h2>
                <p style="text-align: center;color:#555;">Document généré le ${new Date().toLocaleDateString('fr-FR')}</p>
                ${tableau}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    function resetForm() {
    const form = document.getElementById('archive-search-form');
    form.reset();
    // Soumettre le formulaire avec uniquement tab=archive
    window.location.href = '?tab=archive';
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
if(isset($anneesStmt)) {
    $anneesStmt->closeCursor();
}
if(isset($deptsStmt)) {
    $deptsStmt->closeCursor();
}
?>