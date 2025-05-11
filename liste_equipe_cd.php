<?php
session_start();
include_once('config.php');
include_once('header.php');

// Vérification de session et permissions CD
verifySession();
if($_SESSION['qualite'] != 'CD') {
    header('Location: accueil.php');
    exit();
}

// Récupérer uniquement les membres du département du CD
$bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
$req = $bdd->prepare('SELECT * FROM personnel 
                     WHERE depart = :departement 
                     AND qualite NOT IN ("CD", "RH", "DG")
                     ORDER BY nom, prenom');
$req->execute([':departement' => $_SESSION['depart']]);
$membresEquipe = $req->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Équipe | <?= $_SESSION['depart'] ?></title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
   
    <style>
        .equipe-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        body {
        background-color: var(--seagreen-bg);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        /* display: flex; */
        flex-direction: column;
        margin-left: 250px;
    }
    table{
        border-radius: 5px;
    }
        .table th {
            background-color: #2E8B57;
            color: white;
        }
        .badge-equipe {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
        }
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                
            }
        }
        h2{
            color: seagreen;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-fill"></i> Mon Équipe - <?= htmlspecialchars($_SESSION['depart']) ?></h2>
            <span class="badge bg-primary"><?= count($membresEquipe) ?> membres</span>
        </div>

        <div class="equipe-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>CIN</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Service</th>
                            <th>Qualité</th>
                            <th>Congés</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($membresEquipe as $membre): ?>
                        <tr>
                            <td><?= htmlspecialchars($membre['nom']) ?></td>
                            <td><?= htmlspecialchars($membre['prenom']) ?></td>
                            <td><?= htmlspecialchars($membre['cin']) ?></td>
                            <td><?= htmlspecialchars($membre['email']) ?></td>
                            <td><?= htmlspecialchars($membre['tel']) ?></td>
                            <td>
                                <?= htmlspecialchars($membre['service']) ?>
                                
                            </td>
                            <td><?php if($membre['qualite']): ?>
                                    <span class="badge-equipe"><?= htmlspecialchars($membre['qualite']) ?></span>
                                <?php endif; ?></td>
                            <td>
                                <?= htmlspecialchars($membre['conges_restants_annee_precedente']) ?>/
                                <?= htmlspecialchars($membre['conges_annee_en_cours']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>