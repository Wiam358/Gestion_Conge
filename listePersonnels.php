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

// Gestion des actions
if(isset($_POST['supprimer'])){
    SupprimerPersonnel($_POST['supprimer']);
    $message = ["type" => "success", "text" => "Utilisateur supprimé avec succès"];
}

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

// Gestion de la modification
if(isset($_POST['newValue'])){
    try {
        $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
        
        if(isset($_POST['cin']) && isset($_POST['nom']) && isset($_POST['prenom']) && isset($_POST['service']) && 
           isset($_POST['depart']) && isset($_POST['tel']) && isset($_POST['adresse']) && 
           isset($_POST['email']) && isset($_POST['jtot']) && isset($_POST['jrest']) && isset($_POST['qualite'])) {
            
            $req = $bdd->prepare('UPDATE personnel SET cin=:pcin, nom=:pnom, prenom=:pprenom, service=:pgrade, 
                                 depart=:pdivision, tel=:ptel, adresse=:padresse, 
                                 qualite=:pqualite, email=:pemail, conges_restants_annee_precedente=:pjoursrest,  conges_annee_en_cours=:pjourstot
                                 WHERE cin=:old_cin');
            
            $req->execute([
                ':pcin' => strtoupper(htmlspecialchars(trim($_POST['cin']))),
                ':pnom' => ucfirst(strtolower(htmlspecialchars(trim($_POST['nom'])))),
                ':pprenom' => ucfirst(strtolower(htmlspecialchars(trim($_POST['prenom'])))),
                ':pgrade' => ucfirst(strtolower(htmlspecialchars(trim($_POST['service'])))),
                ':pdivision' => ucfirst(strtolower(htmlspecialchars(trim($_POST['depart'])))),
                ':ptel' => htmlspecialchars($_POST['tel']),
                ':padresse' => htmlspecialchars($_POST['adresse']),
                ':pjourstot' => htmlspecialchars($_POST['jtot']),
                ':pjoursrest' => htmlspecialchars($_POST['jrest']),
                ':pqualite' => $_POST['qualite'],
                ':pemail' => $_POST['email'],
                ':old_cin' => $_POST['modifierPers']
            ]);
            
            $message = ["type" => "success", "text" => "Modification effectuée avec succès"];
            header('location:listePersonnels.php');
        } else {
            $message = ["type" => "danger", "text" => "Veuillez remplir tous les champs"];
        }
    } catch (Exception $e) {
        $message = ["type" => "danger", "text" => "Erreur : " . $e->getMessage()];
    }
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
            border-top: 4px solid var(--primary);
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
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .modal-header-custom {
            background-color: var(--primary);
            color: white;
            border-bottom: none;
            padding: 1.2rem 1.5rem;
        }
        
        .modal-title-custom {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .search-card .row > div {
                margin-bottom: 1rem;
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
                    Gestion du Personnel
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
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($donnees = $reponse->fetch()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($donnees['cin']) ?></strong></td>
                            <td><?= htmlspecialchars($donnees['nom']) ?></td>
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
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary action-btn" 
                                            data-bs-toggle="modal" data-bs-target="#modifierModal"
                                            data-cin="<?= $donnees['cin'] ?>"
                                            data-nom="<?= $donnees['nom'] ?>"
                                            data-prenom="<?= $donnees['prenom'] ?>"
                                            data-service="<?= $donnees['service'] ?>"
                                            data-depart="<?= $donnees['depart'] ?>"
                                            data-login="<?= $donnees['login'] ?>"
                                            data-tel="<?= $donnees['tel'] ?>"
                                            data-adresse="<?= $donnees['adresse'] ?>"
                                            data-email="<?= $donnees['email'] ?>"
                                            data-jtot="<?= $donnees['jourstot'] ?>"
                                            data-jrest="<?= $donnees['joursrest'] ?>"
                                            data-qualite="<?= $donnees['qualite'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <form method="post" action="listePersonnels.php" class="d-inline">
                                        <button type="submit" name="supprimer" value="<?= $donnees['cin'] ?>" 
                                                class="btn btn-sm btn-outline-danger action-btn"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce personnel ?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
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

    <!-- Modal de modification -->
    <div class="modal fade" id="modifierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header-custom">
                    <h5 class="modal-title-custom" id="modifierModalLabel">
                        <i class="bi bi-person-gear"></i> Modifier un personnel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="post" action="listePersonnels.php">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nom</label>
                                <input type="text" name="nom" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Prénom</label>
                                <input type="text" name="prenom" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CIN</label>
                                <input type="text" name="cin" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service</label>
                                <input type="text" name="service" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Département</label>
                                <input type="text" name="depart" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="tel" name="tel" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Adresse</label>
                                <input type="text" name="adresse" class="form-control" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Solde d'année Courante</label>
                                <input type="number" name="jtot" class="form-control" min="0" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Solde Reporté</label>
                                <input type="number" name="jrest" class="form-control" min="0" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Rôle</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="qualite" id="ch1" value="DG" required>
                                        <label class="form-check-label" for="ch1">
                                            <span class="badge-role badge-dg">DG</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="qualite" id="ch2" value="CD" required>
                                        <label class="form-check-label" for="ch2">
                                            <span class="badge-role badge-cd">CD</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="qualite" id="ch3" value="SAL" required>
                                        <label class="form-check-label" for="ch3">
                                            <span class="badge-role badge-sal">SAL</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="qualite" id="ch4" value="RH" required>
                                        <label class="form-check-label" for="ch4">
                                            <span class="badge-role badge-rh">RH</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="modifierPers" id="modifierPers">
                        <input type="hidden" name="newValue" value="1">
                        
                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="bi bi-check-circle"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remplir le modal avec les données du personnel
        document.getElementById('modifierModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const modal = this;
            
            // Remplir les champs
            document.getElementById('modifierPers').value = button.getAttribute('data-cin');
            modal.querySelector('input[name="nom"]').value = button.getAttribute('data-nom');
            modal.querySelector('input[name="prenom"]').value = button.getAttribute('data-prenom');
            modal.querySelector('input[name="cin"]').value = button.getAttribute('data-cin');
            modal.querySelector('input[name="service"]').value = button.getAttribute('data-service');
            modal.querySelector('input[name="depart"]').value = button.getAttribute('data-depart');
            modal.querySelector('input[name="tel"]').value = button.getAttribute('data-tel');
            modal.querySelector('input[name="adresse"]').value = button.getAttribute('data-adresse');
            modal.querySelector('input[name="email"]').value = button.getAttribute('data-email');
            modal.querySelector('input[name="jtot"]').value = button.getAttribute('data-jtot');
            modal.querySelector('input[name="jrest"]').value = button.getAttribute('data-jrest');
            
            // Cocher le bon rôle
            const qualite = button.getAttribute('data-qualite');
            modal.querySelector(`input[name="qualite"][value="${qualite}"]`).checked = true;
        });
    </script>
</body>
</html>