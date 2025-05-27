<?php
session_start();
include_once('config.php');
verifySession();
verifyDG(); // Vérification que l'utilisateur est bien DG

connexionBdd($bdd);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $action = $_POST['action'];
        
        // Récupérer la demande d'annulation et la demande originale
        $stmt = $bdd->prepare("
            SELECT a.*, d.* 
            FROM annulation_demandes a
            JOIN demande d ON a.demande_id = d.ID
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if ($data) {
            if ($action === 'accepter') {
                try {
                    $bdd->beginTransaction();
                    
                    // 1. Calculer la durée du congé
                    $diffDays = dateDiff($data['DateRetour'], $data['DateDepart']) + 1;
                    $cin = $data['cin'];
                    
                    // 2. Récupérer les soldes actuels
                    $solde = $bdd->prepare("SELECT conges_restants_annee_precedente, conges_annee_en_cours FROM personnel WHERE cin = ?");
                    $solde->execute([$cin]);
                    $conges = $solde->fetch();
                    
                    $precedent = $conges['conges_restants_annee_precedente'];
                    $actuel = $conges['conges_annee_en_cours'];
                    
                    // 3. Recréditer les jours (priorité à l'année en cours sans dépasser 30 jours)
                    if ($actuel + $diffDays <= 30) {
                        $actuel += $diffDays;
                    } else {
                        $reste = ($actuel + $diffDays) - 30;
                        $actuel = 30;
                        $precedent += $reste;
                    }
                    
                    // 4. Mise à jour des soldes
                    // $update = $bdd->prepare("UPDATE personnel SET conges_restants_annee_precedente = ?, conges_annee_en_cours = ? WHERE cin = ?");
                    // $update->execute([$precedent, $actuel, $cin]);
                    
                    // 5. Mettre à jour le statut de la demande d'annulation
                    $bdd->prepare("UPDATE annulation_demandes SET statut = 'acceptee' WHERE id = ?")->execute([$id]);
                    
                    // 6. Mettre à jour l'état de la demande originale (0 = annulée)
                    $bdd->prepare("UPDATE demande SET Etat = 0 WHERE ID = ?")->execute([$data['demande_id']]);
                    
                    $bdd->commit();
                    $_SESSION['message'] = "Annulation acceptée";
                } catch (Exception $e) {
                    $bdd->rollBack();
                    $_SESSION['error'] = "Erreur: " . $e->getMessage();
                }
            } elseif ($action === 'refuser') {
                $bdd->prepare("UPDATE annulation_demandes SET statut = 'refusee' WHERE id = ?")->execute([$id]);
                $_SESSION['message'] = "Demande annulation refusée";
            }
        }
        header('Location: gestion_annulations.php');
        exit();
    }
}

// Récupérer les demandes en attente (le reste de votre code existant)
$demandes = $bdd->query("
    SELECT a.*, d.type, d.DateDepart, d.DateRetour, p.nom, p.prenom 
    FROM annulation_demandes a
    JOIN demande d ON a.demande_id = d.ID
    JOIN personnel p ON a.cin = p.cin
    WHERE a.statut = 'en_attente'
    ORDER BY a.date_demande DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Gestion Annulation | DG</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --primary-color: #2E8B57;
            --secondary-color: #3CB371;
            --success-color: #2ecc71;
            --danger-color: rgb(219, 22, 22);
            --light-bg: #006400;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            margin-left: 250px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin: 1rem;
        }
        
        .page-header {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
        }
        
        .page-header i {
            margin-right: 10px;
            font-size: 1.6rem;
            color: var(--primary-color);
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            min-width: 700px;
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .table thead {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .table th {
            padding: 0.75rem;
            font-weight: 500;
            text-align: center;
        }
        
        .table td {
            padding: 0.5rem;
            vertical-align: middle;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            transition: all 0.3s;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            transition: all 0.3s;
        }
        
        .action-btn {
            min-width: 100px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 4px;
            box-sizing: border-box;
            touch-action: manipulation;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-1px);
        }
        
        .btn-danger:hover {
            background-color: rgb(199, 23, 23);
            border-color: rgb(131, 6, 6);
            transform: translateY(-1px);
        }
        
        .badge-status {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 50rem;
        }
        
        .badge-waiting {
            background-color: #f39c12;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }
        
        .empty-state i {
            font-size: 2.5rem;
            color: #bdc3c7;
            margin-bottom: 0.75rem;
        }
        
        .empty-state h3 {
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }
        
        .empty-state p {
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .period-cell {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            body {
                margin-left: 0;
            }
            
            .main-container {
                margin: 0.5rem;
                padding: 1rem;
                width: 740px;
            }
            
            .page-header {
                font-size: 1.25rem;
            }
            
            .page-header i {
                font-size: 1.4rem;
            }
        }
        
        @media (max-width: 768px) {
            .table th, .table td {
                padding: 0.4rem;
                font-size: 0.8rem;
            }
            
            .action-btn {
                min-width: 100%;
                margin-bottom: 0.5rem;
                font-size: 0.8rem;
                padding: 0.35rem;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            
            .mobile-hide {
                display: none;
            }
            
            .period-cell i {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                font-size: 1.1rem;
            }
            
            .page-header i {
                font-size: 1.2rem;
            }
            
            .table th, .table td {
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 0.3em 0.5em;
            }
            
            .empty-state i {
                font-size: 2rem;
            }
            
            .empty-state h3 {
                font-size: 1.1rem;
            }
            
            .empty-state p {
                font-size: 0.85rem;
            }
            
            .action-btn {
                font-size: 0.75rem;
                padding: 0.3rem;
            }
        }
    </style>
</head>
<body>
    <?php include_once('header.php'); ?>
    
    <div class="container py-3">
        <div class="main-container">
            <h2 class="page-header">
                <i class="bi bi-x-circle-fill"></i> <b>DEMANDES D'ANNULATION</b>
            </h2>
            
            <?php if (empty($demandes)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Aucune demande en attente</h3>
                    <p>Il n'y a actuellement aucune demande d'annulation à traiter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employé</th>
                                <th class="mobile-hide">Type</th>
                                <th>Période</th>
                                <th>Jours</th>
                                <th class="mobile-hide">Demandé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demandes as $demande): ?>
                                <?php
                                $jours = (strtotime($demande['DateRetour']) - strtotime($demande['DateDepart'])) / (60 * 60 * 24) + 1;
                                ?>
                                <tr>
                                    <td><?= $demande['demande_id'] ?></td>
                                    <td><?= htmlspecialchars($demande['prenom'].' '.strtoupper($demande['nom'])) ?></td>
                                    <td class="mobile-hide"><?= htmlspecialchars($demande['type']) ?></td>
                                    <td class="period-cell">
                                        <?= date('d/m/y', strtotime($demande['DateDepart'])) ?> 
                                        <i class="bi bi-arrow-right"></i>
                                        <?= date('d/m/y', strtotime($demande['DateRetour'])) ?>
                                    </td>
                                    <td><span class="badge bg-primary rounded-pill"><?= $jours ?></span></td>
                                    <td class="mobile-hide"><?= date('d/m/y H:i', strtotime($demande['date_demande'])) ?></td>
                                    <td class="action-buttons">
                                        <form method="post" class="d-inline accept-form">
                                            <input type="hidden" name="id" value="<?= $demande['id'] ?>">
                                            <input type="hidden" name="action" value="accepter">
                                            <button type="submit" class="btn btn-success action-btn btn-accept">
                                                <i class="bi bi-check-circle"></i> Accepter
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline refuse-form">
                                            <input type="hidden" name="id" value="<?= $demande['id'] ?>">
                                            <input type="hidden" name="action" value="refuser">
                                            <button type="submit" class="btn btn-danger action-btn btn-refuse">
                                                <i class="bi bi-x-circle"></i> Refuser
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les boutons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
            
            // Gestion des messages de session
            <?php if (isset($_SESSION['message'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: '<?= htmlspecialchars($_SESSION['message']) ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: '<?= htmlspecialchars($_SESSION['error']) ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Handle accept confirmation
            document.querySelectorAll('.accept-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirmer l\'acceptation',
                        text: 'Voulez-vous vraiment accepter cette annulation ?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2ecc71',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Oui, accepter',
                        cancelButtonText: 'Non'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Handle refuse confirmation
            document.querySelectorAll('.refuse-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirmer le refus',
                        text: 'Voulez-vous vraiment refuser cette annulation ?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Oui, refuser',
                        cancelButtonText: 'Non'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>