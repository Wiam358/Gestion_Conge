<?php
session_start();
include_once('config.php');
include_once('header.php');
connexionBdd($bdd);
// Vérification de session et permissions DG
verifySession();
if($_SESSION['qualite'] != 'DG') {
    header('Location: accueil.php');
    exit();
}

// Traitement de la validation
if(isset($_POST['valider'])) {
    $idDemande = $_POST['valider'];
    $req = $bdd->prepare('UPDATE demande SET Etat = 2 WHERE ID = ?');
    $req->execute([$idDemande]);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Demande acceptée',
                showConfirmButton: false,
                timer: 2000
            });
        });
    </script>";

    // $demande = $req->fetch();

    // if ($demande) {
    //     $cin = $demande['CIN'];
    //     $jours = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;

    //     // Déduire le solde uniquement si la demande est acceptée (etat=2)
    //     if ($demande['Etat'] == 2) {
    //         $updateSolde = $bdd->prepare("UPDATE personnel SET conges_annee_en_cours = conges_annee_en_cours - ? WHERE cin = ?");
    //         $updateSolde->execute([$jours, $cin]);
    //     }

    //     // Mettre à jour l'état de la demande
    //     $req = $bdd->prepare('UPDATE demande SET Etat = 2 WHERE ID = ?');
    //     $req->execute([$idDemande]);
    // }
    
    // Redirection pour éviter la resoumission du formulaire
    // header('Location: liste_dem_dg.php');
    // exit();
}

// Traitement du rejet
if(isset($_POST['rejeter'])) {
    $idDemande = $_POST['rejeter'];

    // Récupérer les infos de la demande pour calculer le nombre de jours et savoir à qui elle appartient
    $req = $bdd->prepare("SELECT * FROM demande WHERE ID = ?");
    $req->execute([$idDemande]);
    $demande = $req->fetch();

    if ($demande) {
        $cin = $demande['CIN'];
        $jours = (date_diff(date_create($demande['DateDepart']), date_create($demande['DateRetour'])))->days + 1;

        // Remettre les jours au solde si la demande était directe (etat=0)
        if ($demande['Etat'] == 0) {
            $updateSolde = $bdd->prepare("UPDATE personnel SET conges_annee_en_cours = conges_annee_en_cours + ? WHERE cin = ?");
            $updateSolde->execute([$jours, $cin]);
        }

        // Mettre à jour l'état de la demande
        $updateEtat = $bdd->prepare("UPDATE demande SET Etat = -2 WHERE ID = ?");
        $updateEtat->execute([$idDemande]);
    }
    echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Demande refusée',
                showConfirmButton: false,
                timer: 2000
            });
        });
    </script>";
}



// Traitement des actions sur les demandes
if(isset($_POST['valider'])) {
    $reqDemande = $bdd->prepare('SELECT * FROM demande WHERE ID = ?');
    $reqDemande->execute([$idDemande]);
    $demande = $reqDemande->fetch();
    EtatDemande($_POST['valider'], 2);
    $cin1 = CinFromID($_POST['valider']);
    $emailPer = InfosPersonnel($cin1);
if (!$emailPer || empty($emailPer['email'])) {
    error_log("Email non trouvé pour le CIN $cin1");
} else {
    $subject = "Validation de votre demande de congé";
    $message = "Bonjour,\n\nVotre demande de congé du {$demande['DateDepart']} au {$demande['DateRetour']} a été validée par le Directeur Général.\n\nVous pouvez récupérer votre décision de congé auprès du service RH.\n\nCordialement,";

    if (EnvoyerEmail($emailPer['email'], $subject, nl2br($message))) {
        // Email envoyé
    } else {
        error_log("Erreur d'envoi d'email à " . $emailPer['email']);
    }
}
// Fetch HR email (assuming HR has qualite = 'RH' in personnel table)
        $reqHR = $bdd->prepare("SELECT email FROM personnel WHERE qualite = 'RH' LIMIT 1");
        $reqHR->execute();
        $hr = $reqHR->fetch();
        $hrEmail = $hr['email'] ?? null;

        if ($hrEmail) {
            // Send email to HR to notify about printing the leave decision
            $hrSubject = "Nouvelle demande de congé acceptée - Impression requise";
            $hrMessage = "Bonjour,\n\nUne demande de congé a été acceptée par le Directeur Général.\n\nDétails :\n- Employé : {$emailPer['nom']} {$emailPer['prenom']}\n- Période : {$demande['DateDepart']} au {$demande['DateRetour']}\n\n\nVeuillez préparer et imprimer la décision de congé pour cet employé.\n\nCordialement,";
            if (!EnvoyerEmail($hrEmail, $hrSubject, nl2br($hrMessage))) {
                error_log("Erreur d'envoi d'email à HR : " . $hrEmail);
            }
        } else {
            error_log("Email HR non trouvé dans la base de données");
        }
}

if(isset($_POST['rejeter'])) {
    $reqDemande = $bdd->prepare('SELECT * FROM demande WHERE ID = ?');
    $reqDemande->execute([$idDemande]);
    $demande = $reqDemande->fetch();
    EtatDemande($_POST['rejeter'], -2);
    $cin1 = CinFromID($_POST['rejeter']);
    $emailPer = InfosPersonnel($cin1);
    if (!$emailPer || empty($emailPer['email'])) {
    error_log("Email non trouvé pour le CIN $cin1");
    } else {
    $subject = "Refus de votre demande de congé";
    $message = "Bonjour {$emailPer['prenom']} {$emailPer['nom']},\n\n" .
                       "Nous sommes au regret de vous informer que votre demande de congé pour la période du {$demande['DateDepart']} " .
                       "au {$demande['DateRetour']} ({$jours} jour" . ($jours > 1 ? "s" : "") . ") a été refusée par la Direction Générale.\n\n" .
                       "Nous vous remercions pour votre compréhension.\n\n" .
                       "Cordialement,\n" .
                       "La Direction Générale";

    if (EnvoyerEmail($emailPer['email'], $subject, nl2br($message))) {
        // Email envoyé
    } else {
        error_log("Erreur d'envoi d'email à " . $emailPer['email']);
    }
}
}


// Récupération des demandes selon le filtre
if(isset($_GET['enAttente'])) {
    $reponse = DemandesEnAttentePourDG(); // À implémenter dans config.php
} elseif(isset($_GET['acceptee'])) {
    $reponse = DemandesAccepteesParDG(); // Existe déjà
} elseif(isset($_GET['refusee'])) {
    $reponse = DemandesRefuseesParDG(); // À implémenter dans config.php
} else {
    $reponse = DemandesPourValidationDG(); // Toutes les demandes pour DG
}

// Vérification de session et permissions DG
verifySession();
// if($_SESSION['qualite'] != 'DG') {
//     header('Location: accueil.php');
//     exit();
// }

// Récupération uniquement des demandes acceptées par les chefs
// $reponse = DemandesAccepteesParChefss();

// Fonction à ajouter dans config.php pour récupérer les demandes acceptées par les chefs
/*
function DemandesAccepteesParChefss() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->prepare('SELECT d.* FROM demandes d 
                         JOIN personnel p ON d.CIN = p.cin 
                         WHERE d.Etat = 1 AND p.qualite IN ("CD", "DG")');
    $req->execute();
    return $req;
}
*/
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation DG | CCISCS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            border: none;
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .dg-header {
                padding: 1.5rem;
            }
            
            .table th, .table td {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
        .filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
}

.filter-btn {
    text-decoration: none;
    flex: 1;
    min-width: 200px;
    max-width: 250px;
    padding: 12px;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.filter-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.btn-all {
    background-color: var(--primary-color);
}

.btn-pending {
    background-color: var(--warning-color);
}

.btn-accepted {
    background-color: var(--success-color);
}

.btn-refused {
    background-color: var(--danger-color);
}

.btn-all:hover {
    background-color: var(--primary-dark);
}

.btn-pending:hover {
    background-color: #E0A800;
}

.btn-accepted:hover {
    background-color: #218838;
}

.btn-refused:hover {
    background-color: #C82333;
}

@media (max-width: 768px) {
    .filter-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .filter-btn {
        width: 100%;
        max-width: none;
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
                <i class="bi bi-shield-check dg-icon"></i>
                Validation des demandes - Direction Générale
            </h1>
            <p class="mb-0 mt-2">
                <i class="bi bi-info-circle"></i> Interface réservée au Directeur Général - Demandes pré-validées par les chefs de département
            </p>
        </div>

<div class="filter-buttons animate__animated animate__fadeIn">
    <a href="liste_dem_dg.php" class="filter-btn btn-all <?php echo !isset($_GET['enAttente']) && !isset($_GET['acceptee']) && !isset($_GET['refusee']) ? 'active' : ''; ?>">
        <i class="bi bi-list-ul"></i> Toutes les demandes
    </a>
    <a href="liste_dem_dg.php?enAttente=1" class="filter-btn btn-pending <?php echo isset($_GET['enAttente']) ? 'active' : ''; ?>">
        <i class="bi bi-hourglass"></i> En attente
    </a>
    <a href="liste_dem_dg.php?acceptee=1" class="filter-btn btn-accepted <?php echo isset($_GET['acceptee']) ? 'active' : ''; ?>">
        <i class="bi bi-check-circle"></i> Acceptées 
    </a>
    <a href="liste_dem_dg.php?refusee=1" class="filter-btn btn-refused <?php echo isset($_GET['refusee']) ? 'active' : ''; ?>">
        <i class="bi bi-x-circle"></i> Refusées
    </a>
</div>
        
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
                            <th>Description</th>
                            <th>Pré-validation</th>
                            <th>État</th>
                            <th>Actions</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($donnees = $reponse->fetch()): 
                            $infos = InfosPersonnel($donnees['CIN']);
                            $validateur = InfosPersonnel($donnees['CIN']);
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
                                <div><?= htmlspecialchars($donnees['DateDepart']) ?></div>
                                <div class="small text-muted">au <?= htmlspecialchars($donnees['DateRetour']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= $days ?> jours</span>
                            </td>
                           <td>
                                <?= !empty($donnees['description']) ? htmlspecialchars($donnees['description']) : '---' ?>
                            </td>
                            <td>
    <?php 
    // Afficher qui a validé
    if($donnees['Etat'] == 1 || $donnees['Etat'] == 2 || $donnees['Etat'] == -2 ) {
        echo '<span class="badge bg-warning">Validé par CD</span>';
    } elseif($infos['qualite'] == 'CD' || $infos['qualite'] == 'RH' || $infos['qualite'] == 'DG') {
        echo '<span class="badge bg-info">Demande directe</span>';
    } else {
        echo '<span class="badge bg-secondary">Non validé</span>';
    }
    ?>
</td>

<td>
    <?php 
    // Afficher l'état global
    if($donnees['Etat'] == 1 || (( $infos['qualite'] == 'CD' || $infos['qualite'] == 'RH' || $infos['qualite'] == 'DG') && $donnees['Etat'] == 0)) {
        echo '<span class="badge bg-warning">En attente</span>';
    } elseif($donnees['Etat'] == 2) {
        echo '<span class="badge bg-success">Acceptée</span>';
    }elseif($donnees['Etat'] == -2) {
        echo '<span class="badge bg-danger">refusée</span>';
     }
     else {
        echo '<span class="badge bg-warning">En attente</span>';
    }
    ?>
</td>

<td>
    <div class="action-buttons d-flex gap-2">
    <?php if($donnees['Etat'] != -2 && $donnees['Etat'] != 2): ?>
        <form method="post">
            <input type="hidden" name="valider" value="<?= $donnees['ID'] ?>">
            <button type="button" class="btn btn-success btn-validate" data-id="<?= $donnees['ID'] ?>">
                <i class="bi bi-check"></i>
            </button>
        </form>
        <form method="post">
            <input type="hidden" name="rejeter" value="<?= $donnees['ID'] ?>">
            <button type="button" class="btn btn-danger btn-reject" data-id="<?= $donnees['ID'] ?>">
                <i class="bi bi-x"></i>
            </button>
        </form>
    <?php else: ?>
        <span class="badge bg-secondary">Déjà traitée</span>
    <?php endif; ?>
</div>

    </div>
</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour confirmer les actions
        function confirmAction(action) {
            return confirm(`Êtes-vous sûr de vouloir ${action} cette demande ?`);
        }
    </script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <?php $reponse->closeCursor(); ?>
    <script>
document.querySelectorAll('.btn-validate').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        Swal.fire({
            title: 'Valider cette demande ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, valider',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#28a745'
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `<input type="hidden" name="valider" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

document.querySelectorAll('.btn-reject').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        Swal.fire({
            title: 'Refuser cette demande ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, refuser',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc3545'
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `<input type="hidden" name="rejeter" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>

</body>
</html>