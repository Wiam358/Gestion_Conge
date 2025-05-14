<?php
session_start();
include_once('config.php');
include_once('header.php');
verifySession();

// Vérifier que l'utilisateur est bien DG
if($_SESSION['qualite'] != 'DG') {
    header('Location: accueil.php');
    exit();
}
connexionBdd($bdd);

// Récupérer les paramètres de filtrage
$etat = isset($_GET['etat']) ? $_GET['etat'] : '';
$departement = isset($_GET['departement']) ? $_GET['departement'] : '';
$annee = isset($_GET['annee']) ? $_GET['annee'] : '';
$mois = isset($_GET['mois']) ? $_GET['mois'] : '';

// Construire la requête de base
$query = "SELECT d.*, p.nom, p.prenom, p.depart 
          FROM demande d
          JOIN personnel p ON d.CIN = p.CIN
          WHERE d.Etat IN (2, -2)";

// Ajouter les filtres selon les paramètres
if (!empty($etat)) {
    $query .= " AND d.Etat = " . ($etat == 'accepte' ? 2 : -2);
}

if (!empty($departement)) {
    $query .= " AND p.depart = " . $bdd->quote($departement);
}

if (!empty($annee)) {
    $query .= " AND YEAR(d.DateDemande) = " . intval($annee);
}

if (!empty($mois)) {
    $query .= " AND MONTH(d.DateDemande) = " . intval($mois);
}

$query .= " ORDER BY d.DateDepart DESC";

$reponse = $bdd->query($query);

// Récupérer les années et départements disponibles pour les filtres
$query_annees = "SELECT DISTINCT YEAR(DateDemande) as annee FROM demande ORDER BY annee DESC";
$annees = $bdd->query($query_annees);

$query_departements = "SELECT DISTINCT depart FROM personnel ORDER BY depart";
$departements = $bdd->query($query_departements);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Archives des demandes | CCISCS</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    :root {
        --primary-color: #3CB371;
        --primary-dark: #2E8B57;
        --bg-color: #F8F9FA;
        --card-bg: #FFFFFF;
    }
    
    body {
        margin-left: 250px;
        background-color: var(--bg-color);
    }
    
    .archive-card {
        background: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(60, 179, 113, 0.1);
        padding: 2rem;
    }
    
    .table th {
        background-color: var(--primary-dark);
        color: white;
    }
    
    .badge-accepted {
        background-color: #28a745;
    }
    
    .badge-rejected {
        background-color: #dc3545;
    }
    
    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    h3{
        color: var(--primary-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
    }
    
    @media (max-width: 992px) {
        body {
            margin-left: 0;
        }
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .archive-card, .archive-card * {
            visibility: visible;
        }
        .archive-card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 15px;
            box-shadow: none;
        }
        .no-print {
            display: none !important;
        }
        .filter-section {
            display: none !important;
        }
    }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="archive-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="bi bi-archive-fill"></i> ARCHIVE DES DEMANDES</h3>
                <span class="badge bg-secondary">
                    <?= $reponse->rowCount() ?> demandes
                </span>
            </div>

            <!-- Section des filtres -->
            <form method="get" class="filter-section mb-4 no-print">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="etat" class="form-label">État de la demande</label>
                        <select id="etat" name="etat" class="form-select">
                            <option value="">Tous les états</option>
                            <option value="accepte" <?= $etat == 'accepte' ? 'selected' : '' ?>>Accepté</option>
                            <option value="rejete" <?= $etat == 'rejete' ? 'selected' : '' ?>>Refusé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="departement" class="form-label">Département</label>
                        <select id="departement" name="departement" class="form-select">
                            <option value="">Tous les départements</option>
                            <?php while($d = $departements->fetch()): ?>
                                <option value="<?= htmlspecialchars($d['depart']) ?>" <?= $departement == $d['depart'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['depart']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="annee" class="form-label">Année</label>
                        <select id="annee" name="annee" class="form-select">
                            <option value="">Toutes années</option>
                            <?php while($a = $annees->fetch()): ?>
                                <option value="<?= $a['annee'] ?>" <?= $annee == $a['annee'] ? 'selected' : '' ?>>
                                    <?= $a['annee'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="mois" class="form-label">Mois</label>
                        <select id="mois" name="mois" class="form-select">
                            <option value="">Tous les mois</option>
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $mois == $m ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-danger flex-grow-1">
                                <i class="bi bi-funnel"></i> Filtrer
                            </button>
                            <a href="archive_dg.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="window.print()">
                                <i class="bi bi-printer"></i> Imprimer
                            </button>
                        </div>
                    </div>
                </div>
                
            </form>

            <div class="d-flex justify-content-between no-print mb-3">
                <div class="text-muted">
                    <?php 
                    $filters = [];
                    if (!empty($etat)) $filters[] = "État: " . ($etat == 'accepte' ? 'Accepté' : 'Refusé');
                    if (!empty($departement)) $filters[] = "Département: $departement";
                    if (!empty($annee)) $filters[] = "Année: $annee";
                    if (!empty($mois)) $filters[] = "Mois: " . date('F', mktime(0, 0, 0, $mois, 1));
                    
                    if (!empty($filters)) {
                        echo '<i class="bi bi-info-circle"></i> Filtres appliqués: ' . implode(', ', $filters);
                    }
                    ?>
                </div>
                
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Personnel</th>
                            <th>Département</th>
                            <th>Période</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Date demande</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($donnee = $reponse->fetch()): 
                            $days = dateDiff($donnee['DateRetour'], $donnee['DateDepart']) + 1;
                        ?>
                        <tr>
                            <td><?= $donnee['ID'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($donnee['nom']) ?> <?= htmlspecialchars($donnee['prenom']) ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($donnee['CIN']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($donnee['depart']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($donnee['DateDepart'])) ?>
                                <div class="small">au <?= date('d/m/Y', strtotime($donnee['DateRetour'])) ?></div>
                                <div class="small">(<?= $days ?> jours)</div>
                            </td>
                            <td><?= htmlspecialchars($donnee['type']) ?? '---' ?></td>
                            <td>
                                <?php if($donnee['Etat'] == 2): ?>
                                    <span class="badge badge-accepted text-white"><i class="bi bi-check-circle"></i> Accepté</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected text-white"><i class="bi bi-x-circle"></i> Refusé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($donnee['DateDemande'])) ?>
                            </td>
                            <td>
                                <?= !empty($donnee['description']) ? htmlspecialchars($donnee['description']) : '---' ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$reponse->closeCursor();
$annees->closeCursor();
$departements->closeCursor();
?>