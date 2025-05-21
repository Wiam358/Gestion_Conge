<?php
session_start();

include_once('config.php');
verifySession();

// Gestion de la demande d'annulation après validation
if (isset($_POST['action']) && $_POST['action'] == 'demander_annulation' && isset($_POST['id'])) {
    $id = $_POST['id'];
    connexionBdd($bdd);

    // Vérifier que la demande appartient à l'utilisateur et est validée (état=2)
    $stmt = $bdd->prepare("SELECT * FROM demande WHERE ID = ? AND CIN = ? AND Etat = 2");
    $stmt->execute([$id, $_SESSION['cin']]);
    $demande = $stmt->fetch();

    if ($demande && strtotime($demande['DateDepart']) > time()) {
        // Créer une demande d'annulation dans une nouvelle table ou mettre à jour l'état
        try {
            $insert = $bdd->prepare("INSERT INTO annulation_demandes (demande_id, cin, date_demande, statut) 
                                   VALUES (?, ?, NOW(), 'en_attente')");
            if ($insert->execute([$id, $_SESSION['cin']])) {
                $_SESSION['message'] = "Demande annulation envoyée au DG";
            } else {
                $_SESSION['error'] = "Erreur lors de la demande d'annulation";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur technique: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Demande non trouvée, non validée ou déjà commencée";
    }

    header('Location: MesDemandes.php');
    exit();
}

// Gestion de la suppression
if (isset($_POST['action']) && $_POST['action'] == 'supprimer' && isset($_POST['id'])) {
    $id = $_POST['id'];
    connexionBdd($bdd);

    // Vérifier que la demande appartient à l'utilisateur
    $stmt = $bdd->prepare("SELECT * FROM demande WHERE ID = ? AND CIN = ?");
    $stmt->execute([$id, $_SESSION['cin']]);
    $demande = $stmt->fetch();

    if ($demande && $demande['Etat'] == 0) {
        // Récupération de la durée de la demande
        $diffDays = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;
        $cin = $_SESSION['cin'];

        // Récupération des soldes actuels
        $solde = $bdd->prepare("SELECT conges_restants_annee_precedente, conges_annee_en_cours FROM personnel WHERE cin = ?");
        $solde->execute([$cin]);
        $conges = $solde->fetch();

        $precedent = $conges['conges_restants_annee_precedente'];
        $actuel = $conges['conges_annee_en_cours'];

        // Rendre les jours (priorité à l'année en cours sans dépasser 30 jours par exemple)
        if ($actuel + $diffDays <= 30) {
            $actuel += $diffDays;
        } else {
            $reste = ($actuel + $diffDays) - 30;
            $actuel = 30;
            $precedent += $reste;
        }

        // Mise à jour des soldes
        $update = $bdd->prepare("UPDATE personnel SET conges_restants_annee_precedente = ?, conges_annee_en_cours = ? WHERE cin = ?");
        $update->execute([$precedent, $actuel, $cin]);

        // Suppression de la demande
        $delete = $bdd->prepare("DELETE FROM demande WHERE ID = ?");
        if ($delete->execute([$id])) {
            $_SESSION['message'] = "Demande annulée avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de l'annulation";
        }
    } else {
        $_SESSION['error'] = "Demande non trouvée ou non annulable";
    }

    header('Location: MesDemandes.php');
    exit();
}

// Gestion de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $datedepart = $_POST['datedepart'];
    $dateretour = $_POST['dateretour'];

    connexionBdd($bdd);

    // 1. Vérifier que la demande existe et appartient à l'utilisateur
    $stmt = $bdd->prepare("SELECT * FROM demande WHERE ID = ? AND CIN = ?");
    $stmt->execute([$id, $_SESSION['cin']]);
    $demande = $stmt->fetch();

    if ($demande && $demande['Etat'] == 0) { // Seulement si en attente
        $cin = $_SESSION['cin'];

        // 2. Récupérer l'ancienne durée
        $ancienDiff = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;

        // 3. Nouvelle durée
        $nouveauDiff = dateDiff($dateretour, $datedepart) + 1;

        // 4. Récupération des soldes
        $req = $bdd->prepare("SELECT conges_restants_annee_precedente, conges_annee_en_cours FROM personnel WHERE cin = ?");
        $req->execute([$cin]);
        $conges = $req->fetch();

        $precedent = $conges['conges_restants_annee_precedente'];
        $actuel = $conges['conges_annee_en_cours'];

        // 5. Rendre l'ancien solde (remboursement)
        if ($actuel + $ancienDiff <= 30) {
            $actuel += $ancienDiff;
        } else {
            $reste = ($actuel + $ancienDiff) - 30;
            $actuel = 30;
            $precedent += $reste;
        }

        // 6. Vérifier que le nouveau solde est suffisant
        if (($precedent + $actuel) < $nouveauDiff) {
            $_SESSION['error'] = "Durée modifiée dépasse le solde disponible.";
            header('Location: MesDemandes.php');
            exit;
        }

        // 7. Appliquer la nouvelle durée (réduction des soldes)
        if ($precedent >= $nouveauDiff) {
            $precedent -= $nouveauDiff;
        } else {
            $reste = $nouveauDiff - $precedent;
            $precedent = 0;
            $actuel -= $reste;
        }

        // 8. Mettre à jour les soldes
        $req = $bdd->prepare("UPDATE personnel SET conges_restants_annee_precedente = ?, conges_annee_en_cours = ? WHERE cin = ?");
        $req->execute([$precedent, $actuel, $cin]);

        // 9. Mettre à jour la demande
        $update = $bdd->prepare("UPDATE demande SET type = ?, DateDepart = ?, DateRetour = ? WHERE ID = ?");
        $success = $update->execute([$type, $datedepart, $dateretour, $id]);

        if ($success) {
            $_SESSION['message'] = "Demande modifiée avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la modification.";
        }
    } else {
        $_SESSION['error'] = "Demande non trouvée ou non modifiable.";
    }

    header('Location: MesDemandes.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Demandes | CCISCS</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style type="text/css">
        :root {
            --seagreen-dark: #2E8B57;
            --seagreen-primary: #3CB371;
            --seagreen-light: #8FBC8F;
            --seagreen-lighter: #98FB98;
            --seagreen-bg: #F0FFF0;
        }

        body {
            background-color: var(--seagreen-bg);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            flex-direction: column;
            margin-left: 250px;
        }

        .nav-tabs {
            border-bottom: 2px solid var(--seagreen-light);
        }

        .nav-tabs .nav-link {
            color: var(--seagreen-dark);
            border: none;
            padding: 12px 20px;
            font-weight: 600;
        }

        .nav-tabs .nav-link.active {
            color: var(--seagreen-primary);
            background-color: transparent;
            border-bottom: 3px solid var(--seagreen-primary);
        }

        .nav-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid var(--seagreen-light);
        }

        .tab-content {
            background-color: white;
            border-radius: 0 0 15px 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }

        .responsive-table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 600px;
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid var(--seagreen-light);
        }

        th {
            background-color: var(--seagreen-primary);
            color: white;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: rgba(240, 255, 240, 0.5);
        }

        tr:hover {
            background-color: var(--seagreen-lighter);
        }

        .btn-primary {
            background-color: rgb(241, 169, 14);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-danger {
            background-color: rgb(244, 4, 4);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-danger:hover {
            background-color: rgb(144, 3, 3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 7, 7, 0.88);
        }

        .btn-primary:hover {
            background-color: rgb(180, 125, 7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(130, 80, 4, 0.88);
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-approved {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-rejected {
            background-color: #F8D7DA;
            color: #721C24;
        }

        @media (max-width: 992px) {
            body {
                margin-left: 0;
            }
            
            .responsive-table-container {
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .nav-tabs .nav-link {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 8px 6px;
                font-size: 0.9rem;
            }
            
            .btn-action {
                padding: 6px 8px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 576px) {
            .nav-tabs .nav-link {
                padding: 8px 10px;
                font-size: 0.8rem;
            }
            
            .mobile-hide {
                display: none;
            }
            
            .status {
                font-size: 0.8rem;
                padding: 4px 8px;
            }
        }

        .demands-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(60, 179, 113, 0.1);
            padding: 1rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .demands-card:hover {
            transform: translateY(-5px);
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

        .btn-warning {
            background-color: #ffc107;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(222, 170, 12, 0.4);
        }
    </style>
</head>
<body>
    <?php include_once('header.php'); ?>
    
    <div class="container mt-4">
        <ul class="nav nav-tabs" id="demandesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="suivi-tab" data-bs-toggle="tab" data-bs-target="#suivi" type="button" role="tab">
                    <i class="bi bi-hourglass"></i> Suivi des demandes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historique-tab" data-bs-toggle="tab" data-bs-target="#historique" type="button" role="tab">
                    <i class="bi bi-clock-history"></i> Historique
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="demandesTabsContent">
            <!-- Onglet Suivi -->
            <div class="tab-pane fade show active" id="suivi" role="tabpanel">
                <div class="responsive-table-container">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Jours</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            connexionBdd($bdd);
                            $reqPending = $bdd->prepare("SELECT * FROM demande WHERE CIN = ? AND Etat = 0 ORDER BY DateDemande DESC");
                            $reqPending->execute([$_SESSION['cin']]);
                            
                            $types = [];
                            try {
                                $query = $bdd->query('SELECT DISTINCT type FROM demande ORDER BY type');
                                $types = $query->fetchAll(PDO::FETCH_COLUMN);
                            } catch (Exception $e) {
                                // En cas d'erreur, on garde le tableau vide
                            }

                            while($demande = $reqPending->fetch()) {
                                $days = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;
                                echo '<tr>
                                        <td>'.$demande['ID'].'</td>
                                        <td class="mobile-hide">'.$demande['type'].'</td>
                                        <td>'.date('d/m/y', strtotime($demande['DateDepart'])).'</td>
                                        <td>'.date('d/m/y', strtotime($demande['DateRetour'])).'</td>
                                        <td>'.$days.'</td>
                                        <td>';
                                
                                $id = $demande['ID'];
                                echo <<<HTML
                                <button type="button" class="btn-action btn-sm btn-primary btn-modifier" 
                                        data-bs-toggle="modal" data-bs-target="#modalModifier"
                                        data-id="$id"
                                        data-type="{$demande['type']}"
                                        data-datedepart="{$demande['DateDepart']}"
                                        data-dateretour="{$demande['DateRetour']}">
                                    <i class="bi bi-pencil-square"></i> <span class="mobile-hide">Modifier</span>
                                </button>
                                
                                <form method="post" action="MesDemandes.php" class="d-inline delete-form">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id" value="$id">
                                    <button type="submit" class="btn-action btn-sm btn-danger btn-delete">
                                        <i class="bi bi-x-circle"></i> <span class="mobile-hide">Annuler</span>
                                    </button>
                                </form>
                                HTML;
                                echo '</td></tr>';
                            }
                            
                            if($reqPending->rowCount() == 0) {
                                echo '<tr><td colspan="6" class="text-center">Aucune demande en attente</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Onglet Historique -->
            <div class="tab-pane fade" id="historique" role="tabpanel">
                <div class="demands-card">
                    <div class="responsive-table-container">
                        <table class="table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="mobile-hide">Type</th>
                                    <th class="mobile-hide">Demandé</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Jours</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $reqHistory = $bdd->prepare("SELECT * FROM demande WHERE CIN = ? AND Etat != 0 ORDER BY DateDemande DESC");
                                $reqHistory->execute([$_SESSION['cin']]);

                                while($demande = $reqHistory->fetch()) {
                                    $Etat = $demande['Etat'];
                                    switch ($Etat) {
                                        case '-2':
                                            $EtatClass = 'state-refused';
                                            $EtatText = 'Refusée par DG';
                                            break;
                                        case '-1':
                                            $EtatClass = 'state-refused';
                                            $EtatText = 'Refusée par CD';
                                            break;
                                        case '1':
                                            $EtatClass = 'state-accepted';
                                            $EtatText = 'Acceptée par CD';
                                            break;
                                        case '2': 
                                            $EtatClass = 'state-accepted';
                                            $EtatText = 'Acceptée par DG';
                                            break;
                                        default:
                                            $EtatClass = 'state-refused';
                                            $EtatText = 'Inconnu';
                                            break;
                                    }

                                    $days = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;
                                    echo '<tr>
                                            <td>'.$demande['ID'].'</td>
                                            <td class="mobile-hide">'.$demande['type'].'</td>
                                            <td class="mobile-hide">'.$demande['DateDemande'].'</td>
                                            <td>'.date('d/m/y', strtotime($demande['DateDepart'])).'</td>
                                            <td>'.date('d/m/y', strtotime($demande['DateRetour'])).'</td>
                                            <td>'.$days.'</td>
                                            <td>
                                                <span class="state-badge '.$EtatClass.'">'.$EtatText.'</span>';

                                    if ($Etat == 2 && strtotime($demande['DateDepart']) > time()) {
                                        $checkAnnulation = $bdd->prepare("SELECT id FROM annulation_demandes 
                                                                        WHERE demande_id = ? AND statut = 'en_attente'");
                                        $checkAnnulation->execute([$demande['ID']]);
                                        
                                        if ($checkAnnulation->rowCount() == 0) {
                                            echo '<form method="post" action="MesDemandes.php" class="d-inline cancel-form">
                                                    <input type="hidden" name="action" value="demander_annulation">
                                                    <input type="hidden" name="id" value="'.$demande['ID'].'">
                                                    <button type="submit" class="btn-action btn-sm btn-warning btn-cancel">
                                                        <i class="bi bi-x-circle"></i> <span class="mobile-hide">Annuler</span>
                                                    </button>
                                                  </form>';
                                        } else {
                                            echo '<span class="badge bg-info text-dark ms-2"><i class="bi bi-hourglass"></i> En attente</span>';
                                        }
                                    }

                                    echo '</td></tr>';
                                }
                                
                                if($reqHistory->rowCount() == 0) {
                                    echo '<tr><td colspan="7" class="text-center">Aucune demande dans l\'historique</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de modification -->
    <div class="modal fade" id="modalModifier" tabindex="-1" aria-labelledby="modalModifierLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalModifierLabel">Modifier la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formModifier" method="post" action="MesDemandes.php">
                    <div class="modal-body">
                        <input type="hidden" id="modifier_id" name="id">

                        <div class="mb-3">
                            <label for="modifier_type" class="form-label">Type de demande</label>
                            <select class="form-select" id="modifier_type" name="type" required>
                                <?php foreach($types as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modifier_datedepart" class="form-label">Date de départ</label>
                            <input type="date" class="form-control" id="modifier_datedepart" name="datedepart" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modifier_dateretour" class="form-label">Date de retour</label>
                            <input type="date" class="form-control" id="modifier_dateretour" name="dateretour" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display session messages with SweetAlert2
            <?php if (isset($_SESSION['message'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: '<?php echo htmlspecialchars($_SESSION['message']); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: '<?php echo htmlspecialchars($_SESSION['error']); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Handle delete confirmation
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirmer la suppression',
                        text: 'Voulez-vous vraiment annuler cette demande ?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Oui, annuler',
                        cancelButtonText: 'Non'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Handle cancellation request confirmation
            document.querySelectorAll('.cancel-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirmer la demande d\'annulation',
                        text: 'Voulez-vous vraiment demander l\'annulation de ce congé validé ?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Oui, demander',
                        cancelButtonText: 'Non'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Modal modification handling
            var modalModifier = document.getElementById('modalModifier');
            modalModifier.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var type = button.getAttribute('data-type');
                var datedepart = button.getAttribute('data-datedepart');
                var dateretour = button.getAttribute('data-dateretour');
                
                document.getElementById('modifier_id').value = id;
                document.getElementById('modifier_type').value = type;
                document.getElementById('modifier_datedepart').value = datedepart;
                document.getElementById('modifier_dateretour').value = dateretour;
            });

            // Form submission validation
            document.getElementById('formModifier').addEventListener('submit', function(e) {
                var datedepart = new Date(document.getElementById('modifier_datedepart').value);
                var dateretour = new Date(document.getElementById('modifier_dateretour').value);
                
                if (dateretour < datedepart) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur de dates',
                        text: 'La date de retour doit être postérieure à la date de départ.',
                    });
                }
            });

            // Weekend validation
            const dateDepartInput = document.querySelector('input[name="datedepart"]');
            const dateRetourInput = document.querySelector('input[name="dateretour"]');

            function isWeekend(dateStr) {
                const date = new Date(dateStr);
                const day = date.getDay();
                return day === 0 || day === 6; // 0 = Sunday, 6 = Saturday
            }

            function checkAndAlert(input) {
                const dateVal = input.value;
                if (dateVal && isWeekend(dateVal)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date invalide',
                        text: 'La date sélectionnée tombe un weekend. Veuillez choisir un jour ouvrable.',
                    }).then(() => {
                        input.value = ''; // Reset input
                    });
                }
            }

            if (dateDepartInput) {
                dateDepartInput.addEventListener('change', function() {
                    checkAndAlert(this);
                });
            }

            if (dateRetourInput) {
                dateRetourInput.addEventListener('change', function() {
                    checkAndAlert(this);
                });
            }
        });
    </script>
</body>
</html>