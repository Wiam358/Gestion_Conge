<?php
session_start();
include_once('config.php');
verifySession(); 
// Vérification admin commentée pour le moment
// if(verifyAdmin()==false){
//     header('Location:accueil.php');
// }

// Initialisation des données
$divisions = GetDivision();
$grades = GetGrade();
$message = null;



$reponse = TousPersonnels();

// Filtres de recherche
if(isset($_POST['qualite']) && $_POST['qualite'] != "false") {
    $reponse = InfosPersonnelFromAdmin($_POST['qualite']);
}

if(isset($_POST['scin']) && !empty(trim($_POST['scin']))){
    $reponse = InfosPersonnel2(strtoupper(htmlspecialchars(trim($_POST['scin']))));
}

if(isset($_POST['divisions']) && $_POST['divisions'] != "false") {
    $reponse = InfosPersonnelFromDiv($_POST['divisions']);
}

if(isset($_POST['grades']) && $_POST['grades'] != "false") {
    $reponse = InfosPersonnelFromGrade($_POST['grades']);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Personnel | CCISCS</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #2E8B57;
            --primary-light: #3CB371;
            --primary-lighter: #8FBC8F;
            --primary-lightest: #E8F5E9;
            --dark: #212529;
            --light: #F8F9FA;
        }
        
        body {
            margin-left: 250px;
            background-color: #f5f7fa;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            /* border-top: 4px solid var(--primary); */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .header-title {
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-icon {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .btn-primary-custom {
            background-color: var(--primary);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            background-color: #26734a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 139, 87, 0.3);
        }
        
        .search-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .table-custom {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: var(--primary-lightest);
            --bs-table-hover-bg: rgba(60, 179, 113, 0.1);
        }
        
        .table-custom thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            padding: 12px 16px;
        }
        
        .table-custom tbody td {
            padding: 12px 16px;
            vertical-align: middle;
        }
        
        .badge-role {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .badge-dg { background-color: #6f42c1; color: white; }
        .badge-cd { background-color: #d63384; color: white; }
        .badge-rh { background-color: #fd7e14; color: white; }
        .badge-sal { background-color: #20c997; color: white; }
        
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .search-card .row > div {
                margin-bottom: 1rem;
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
    <?php include_once('header.php'); ?>
    
    <div class="container py-4">
        <div class="dashboard-card p-4 mb-4 animate__animated animate__fadeIn">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="header-title mb-0">
                    <i class="bi bi-people-fill header-icon"></i>
                    MEMBRE D'ORGANISME | CCISCS
                </h2>
                <div>
                    <span class="badge bg-secondary">
                        <i class="bi bi-person-check"></i> <?= $reponse->rowCount() ?> Personnels
                    </span>
                </div>
            </div>
            
            <?php if(isset($message)): ?>
                <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show mb-4">
                    <i class="bi <?= $message['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
                    <?= $message['text'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Carte de recherche -->
            <div class="search-card">
                <form method="post" action="listePersonnels.php">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="scin" class="form-label fw-semibold">Recherche par CIN</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                <input type="text" class="form-control" id="scin" name="scin" placeholder="Saisir CIN">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="div" class="form-label fw-semibold">Département</label>
                            <select class="form-select" id="div" name="divisions">
                                <option value="false">Tous les départements</option>
                                <?php while($valueD = $divisions->fetch()): ?>
                                    <option value="<?= htmlspecialchars(current($valueD)) ?>">
                                        <?= htmlspecialchars(current($valueD)) ?>
                                    </option>
                                <?php endwhile; 
                                $divisions->closeCursor(); ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="grades" class="form-label fw-semibold">Service</label>
                            <select class="form-select" id="grades" name="grades">
                                <option value="false">Tous les services</option>
                                <?php while($valueG = $grades->fetch()): ?>
                                    <option value="<?= htmlspecialchars(current($valueG)) ?>">
                                        <?= htmlspecialchars(current($valueG)) ?>
                                    </option>
                                <?php endwhile; 
                                $grades->closeCursor(); ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tableau des personnels -->
            <div class="table-responsive">
                <table class="table table-custom table-hover table-striped">
                    <thead>
                        <tr>
                            <th>CIN</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Service</th>
                            <th>Département</th>
                            <th>Contact</th>
                            <th>Congés C/P</th>
                            <th>Qualité</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($donnees = $reponse->fetch()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($donnees['cin']) ?></strong></td>
                            <td><?= strtoupper(htmlspecialchars($donnees['nom'])) ?></td>
                            <td><?= htmlspecialchars($donnees['prenom']) ?></td>
                            <td><?= htmlspecialchars($donnees['service']) ?></td>
                            <td><?= htmlspecialchars($donnees['depart']) ?></td>
                            <td>
                                <div><i class="bi bi-telephone me-2"></i> <?= htmlspecialchars($donnees['tel']) ?></div>
                                <div><i class="bi bi-envelope me-2"></i> <?= htmlspecialchars($donnees['email']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($donnees['conges_restants_annee_precedente']) ?>/<?= htmlspecialchars($donnees['conges_annee_en_cours']) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $roleClass = '';
                                switch($donnees['qualite']) {
                                    case 'DG': $roleClass = 'badge-dg'; break;
                                    case 'CD': $roleClass = 'badge-cd'; break;
                                    case 'RH': $roleClass = 'badge-rh'; break;
                                    default: $roleClass = 'badge-sal';
                                }
                                ?>
                                <span class="badge-role <?= $roleClass ?>">
                                    <?= htmlspecialchars($donnees['qualite']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; 
                        $reponse->closeCursor();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>