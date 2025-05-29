<?php
session_start();
include_once('config.php');
include_once('header.php');

// Vérification de session et des permissions
verifySession();
// if(verifyAdmin() == false) {
//     header('Location: MesDemandes.php');
//     exit();
// }

// Traitement des actions sur les demandes
if(isset($_POST['accepter'])) {
    EtatDemande($_POST['accepter'], 1);
    $cin1 = CinFromID($_POST['accepter']);
    $emailPer = InfosPersonnel($cin1);
if (!$emailPer || empty($emailPer['email'])) {
    error_log("Email non trouvé pour le CIN $cin1");
} else {
    $subject = "Pré-validation de votre demande de congé";
    $message = "Bonjour,\n\nVotre demande de congé a été pré-validée par le Chef de département.\n\nCordialement,";

    if (EnvoyerEmail($emailPer['email'], $subject, nl2br($message))) {
        // Email envoyé
    } else {
        error_log("Erreur d'envoi d'email à " . $emailPer['email']);
    }
}

}

if(isset($_POST['refuser'])) {
    EtatDemande($_POST['refuser'], -1);
    $cin1 = CinFromID($_POST['refuser']);
    $emailPer = InfosPersonnel($cin1);
    if (!$emailPer || empty($emailPer['email'])) {
    error_log("Email non trouvé pour le CIN $cin1");
    } else {
    $subject = "Refus de votre demande de congé";
    $message = "Bonjour,\n\nVotre demande de congé a été refusée par le Chef de département.\n\nCordialement,";

    if (EnvoyerEmail($emailPer['email'], $subject, nl2br($message))) {
        // Email envoyé
    } else {
        error_log("Erreur d'envoi d'email à " . $emailPer['email']);
    }
}
}

if(isset($_POST['pasEncore'])) {
    EtatDemande($_POST['pasEncore'], 0);
    $cin1 = CinFromID($_POST['pasEncore']); // Corrigé: 'pasEnCore' -> 'pasEncore'
    $emailPer = InfosPersonnel($cin1);
    if (!$emailPer || empty($emailPer['email'])) {
    error_log("Email non trouvé pour le CIN $cin1");
} else {
    $subject = "Etat de votre demande de congé";
    $message = "Bonjour,\n\nVotre demande de congé est en cours de traitement.\n\nCordialement,";

    if (EnvoyerEmail($emailPer['email'], $subject, nl2br($message))) {
        // Email envoyé
    } else {
        error_log("Erreur d'envoi d'email à " . $emailPer['email']);
    }
}
}

// Récupérer le département du chef connecté
$cin = $_SESSION['cin'];
$infosChef = InfosPersonnel($cin);
$departementChef = $infosChef['depart'];
$cin_cd = $infosChef['cin'];

// Récupération des demandes selon le filtre
if(isset($_GET['enAttente'])) {
    $reponse = DemandesEnAttenteChefDepartement($departementChef, $cin_cd);
} elseif(isset($_GET['acceptee'])) {
    $reponse = DemandesAccepteeChefDepartement($departementChef);
} elseif(isset($_GET['refusee'])) {
    $reponse = DemandesRefuseeChefDepartement($departementChef);
} else {
    $reponse = LesDemandesChefDepartement($departementChef, $cin_cd);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des demandes | CCISCS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Ajout de SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #3CB371;
            --primary-dark: #2E8B57;
            --primary-light: #8FBC8F;
            --primary-lighter: #98FB98;
            --bg-color: #F8F9FA;
            --card-bg: #FFFFFF;
            --text-color: #495057;
            --text-light: #6C757D;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
            --success-color: #28A745;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin-left: 250px;
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
        
        .demands-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(60, 179, 113, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .demands-card:hover {
            transform: translateY(-5px);
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1rem 0;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            position: sticky;
            top: 0;
            padding: 12px 15px;
            text-align: center;
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
        
        .state-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .state-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #856404;
        }
        
        .state-accepted {
            background-color: rgba(40, 167, 69, 0.2);
            color: #155724;
        }
        
        .state-refused {
            background-color: rgba(220, 53, 69, 0.2);
            color: #721C24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .action-btn i {
            font-size: 1rem;
        }
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                
            }
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
            
            .table td, .table th {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
        .dg-header {
            background: linear-gradient(135deg, var(--primary-dark), #1a4b2e);
            color: white;
            width: 100%;
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
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="dg-header animate__animated animate__fadeIn">
            <h1 class="dg-title">
                <i class="bi bi-shield-check dg-icon"></i>
                Validation des demandes d'équipe
            </h1>
            <p class="mb-0 mt-2">
                <i class="bi bi-info-circle"></i> Interface réservée au Chef de Département : <?= strtoupper(htmlspecialchars($_SESSION['depart'])) ?>
            </p>
        </div>
        
        <div class="filter-buttons animate__animated animate__fadeIn">
            <a href="listeDemandes.php" class="filter-btn btn-all <?php echo !isset($_GET['enAttente']) && !isset($_GET['acceptee']) && !isset($_GET['refusee']) ? 'active' : ''; ?>">
                <i class="bi bi-list-ul"></i> Toutes les demandes
            </a>
            <a href="listeDemandes.php?enAttente=1" class="filter-btn btn-pending <?php echo isset($_GET['enAttente']) ? 'active' : ''; ?>">
                <i class="bi bi-hourglass"></i> En attente
            </a>
            <a href="listeDemandes.php?acceptee=1" class="filter-btn btn-accepted <?php echo isset($_GET['acceptee']) ? 'active' : ''; ?>">
                <i class="bi bi-check-circle"></i> Acceptées
            </a>
            <a href="listeDemandes.php?refusee=1" class="filter-btn btn-refused <?php echo isset($_GET['refusee']) ? 'active' : ''; ?>">
                <i class="bi bi-x-circle"></i> Refusées
            </a>
        </div>
        
        <div class="demands-card animate__animated animate__fadeInUp">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Personnel</th>
                            <th>ID demande</th>
                            <th>Date demande</th>
                            <th>Date départ</th>
                            <th>Date retour</th>
                            <th>Nbr jours</th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($donnees = $reponse->fetch()): 
                            $infos = InfosPersonnel($donnees['CIN']);
                            if (!$infos || !is_array($infos)) {
                                $infos = ['nom' => 'Inconnu', 'prenom' => ''];
                            }
                            
                            $Etat = $donnees['Etat'] ?? '0';
                            if (is_bool($Etat)) {
                                $Etat = $Etat ? '1' : '-1';
                            }
                            
                            $choixE = true; $choixA = true; $choixR = true;
                            
                            switch ($Etat) {
                                case '-1':
                                    $EtatClass = 'state-refused';
                                    $EtatText = 'Refusée par CD';
                                    $choixR = false;
                                    break;
                                case '0':
                                    $EtatClass = 'state-pending';
                                    $EtatText = 'Attente';
                                    $choixE = false;
                                    break;        
                                case '1':
                                    $EtatClass = 'state-accepted';
                                    $EtatText = 'Acceptée par CD';
                                    $choixA = false;
                                    break;
                                case '2':
                                    $EtatClass = 'state-accepted';
                                    $EtatText = 'Acceptée par DG';
                                    $choixR = false;
                                    $choixE = false;
                                    $choixA = false;
                                    break;
                                case '-2':
                                    $EtatClass = 'state-accepted';
                                    $EtatText = 'refusée par DG';
                                    $choixR = false;
                                    $choixE = false;
                                    $choixA = false;
                                    break;
                                default:
                                    $EtatClass = 'state-accepted';
                                    $EtatText = '-';
                                    $choixR = false;
                                    $choixE = false;
                                    $choixA = false;
                                    break;
                            }
                            
                            $days = dateDiff($donnees['DateRetour'], $donnees['DateDepart']) + 1;
                            $path = http_build_query($_GET);
                            $lien = "listeDemandes.php?".$path;
                        ?>
                        <tr>
                            <td><?= strtoupper(htmlspecialchars($infos['nom'])) ?> <?= htmlspecialchars($infos['prenom']) ?></td>
                            <td><?= htmlspecialchars($donnees['ID']) ?></td>
                            <td><?= htmlspecialchars($donnees['DateDemande']) ?></td>
                            <td><?= htmlspecialchars($donnees['DateDepart']) ?></td>
                            <td><?= htmlspecialchars($donnees['DateRetour']) ?></td>
                            <td><?= htmlspecialchars($days) ?></td>
                            <td><span class="state-badge <?= $EtatClass ?>"><?= $EtatText ?></span></td>
                            <td>
                                <div class="d-flex justify-content-between gap-2">
                                    <?php if($choixE): ?>
                                        <form method="post" action="<?= htmlspecialchars($lien) ?>" class="flex-fill form-attente">
                                            <input type="hidden" name="pasEncore" value="<?= htmlspecialchars($donnees['ID']) ?>">
                                            <button type="button" class="action-btn btn btn-warning btn-sm w-100" onclick="confirmAction('mettre en attente', this.form)">
                                                <i class="bi bi-hourglass"></i> Attente
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if($choixA): ?>
                                        <form method="post" action="<?= htmlspecialchars($lien) ?>" class="flex-fill form-accepter">
                                            <input type="hidden" name="accepter" value="<?= htmlspecialchars($donnees['ID']) ?>">
                                            <button type="button" class="action-btn btn btn-success btn-sm w-100" onclick="confirmAction('accepter', this.form)">
                                                <i class="bi bi-check-circle"></i> Accepter
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if($choixR): ?>
                                        <form method="post" action="<?= htmlspecialchars($lien) ?>" class="flex-fill form-refuser">
                                            <input type="hidden" name="refuser" value="<?= htmlspecialchars($donnees['ID']) ?>">
                                            <button type="button" class="action-btn btn btn-danger btn-sm w-100" onclick="confirmAction('refuser', this.form)">
                                                <i class="bi bi-x-circle"></i> Refuser
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ajout de SweetAlert2 JS -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmAction(action, form) {
            Swal.fire({
                title: `Confirmation`,
                text: `Êtes-vous sûr de vouloir ${action} cette demande ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28A745',
                cancelButtonColor: '#DC3545',
                confirmButtonText: 'Oui, confirmer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // Soumettre le formulaire si confirmé
                    // Reload the page after a short delay to allow the form to process
                setTimeout(() => {
                    window.location.reload();
                }, 500); // 500ms delay to ensure the form submission is processed
                }
            });
        }
    </script>
    
    <?php $reponse->closeCursor(); ?>
</body>
</html>