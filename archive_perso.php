<?php
session_start();
include_once('config.php');
verifySession();


// Initialisation des données
$message = null;
connexionBdd($bdd);
// Gestion de la restauration
if(isset($_POST['restaurer'])) {
    try {
        if(RestaurerPersonnel($_POST['restaurer'])) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Personnel restauré avec succès'
            ];
        }
    } catch (Exception $e) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Erreur lors de la restauration: ' . $e->getMessage()
        ];
    }
    header('Location: archive_perso.php');
    exit();
}

// Récupérer tous les personnels archivés
$reponse = $bdd->query('SELECT * FROM personnel_archive ORDER BY date_archivage DESC');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives du Personnel | CCISCS</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Reprenez le même style que listePersonnels.php */
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
        
        .badge-archive {
            background-color: #6c757d;
            color: white;
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
                    <i class="bi bi-archive header-icon"></i>
                    Archives du Personnel
                </h2>
                <div>
                    <span class="badge bg-secondary">
                        <i class="bi bi-person-x"></i> <?= $reponse->rowCount() ?> Personnels archivés
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
            
            <!-- Tableau des personnels archivés -->
            <div class="table-responsive">
                <table class="table table-custom table-hover table-striped">
                    <thead>
                        <tr>
                            <th>CIN</th>
                            <th>Nom Complet</th>
                            <th>Service</th>
                            <th>Département</th>
                            <th>Archivé le</th>
                            <th>Raison</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($donnees = $reponse->fetch()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($donnees['cin']) ?></strong></td>
                            <td><?= strtoupper(htmlspecialchars($donnees['nom'])) ?> <?= htmlspecialchars($donnees['prenom']) ?></td>
                            <td><?= htmlspecialchars($donnees['service']) ?></td>
                            <td><?= htmlspecialchars($donnees['depart']) ?></td>
                            <td>
                                <span class="badge badge-archive">
                                    <?= date('d/m/Y H:i', strtotime($donnees['date_archivage'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($donnees['description']) ?></td>
                            <td>
    <form method="post" action="archive_perso.php" class="d-inline" id="restoreForm-<?= $donnees['cin'] ?>">
        <input type="hidden" name="restaurer" value="<?= $donnees['cin'] ?>">
        <button type="button" 
                class="btn btn-sm btn-outline-success btn-restore"
                data-cin="<?= $donnees['cin'] ?>">
            <i class="bi bi-arrow-counterclockwise"></i> Restaurer
        </button>
    </form>
</td>
                        </tr>
                        <?php endwhile; 
                        $reponse->closeCursor();
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <a href="listePersonnels.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour à la liste du personnel
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons de restauration
    const restoreButtons = document.querySelectorAll('.btn-restore');
    
    restoreButtons.forEach(button => {
        button.addEventListener('click', function() {
            const cin = this.getAttribute('data-cin');
            
            Swal.fire({
                title: 'Confirmer la restauration',
                text: "Êtes-vous sûr de vouloir restaurer ce personnel ?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2E8B57',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, restaurer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`restoreForm-${cin}`).submit();
                }
            });
        });
    });
});
</script>
</body>
</html>