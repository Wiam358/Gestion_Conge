<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | CCISCS</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <!-- Animation Libraries -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>
    <!-- Framework CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    
    <!-- Police Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="photos/cciscs.png">
    
    <style>
        :root {
            --primary-color: #2e8b57;
            --primary-light: #3cb371;
            --primary-dark: #1e6b47;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .main-container {
            margin-left: 250px;
            flex: 1;
            
            background-color: var(--secondary-color);
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-icon {
            font-size: 1.75rem;
            color: var(--primary-light);
            margin-bottom: 0.75rem;
        }
        
        .stat-value {
            font-size: 2.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0.5rem 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        /* .calendar-responsive-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
        }
        
        #calendar {
            width: 100%;
            min-width: 300px;
        } */
        
        .alert-card {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .fc-header-toolbar {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .fc-toolbar-chunk {
                margin-bottom: 0.5rem;
            }
        }
        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                
            }
        }
        /* Animation Styles */
[data-aos] {
    transition-timing-function: cubic-bezier(0.22, 0.61, 0.36, 1);
}

.stat-card {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s ease-out;
}

.stat-card.animated {
    opacity: 1;
    transform: translateY(0);
}

/* .calendar-responsive-container {
    opacity: 0;
    transition: all 0.8s ease;
    transform: scale(0.95);
}

.calendar-responsive-container.animated {
    opacity: 1;
    transform: scale(1);
} */

/* Hover effects */
.stat-card:hover .stat-icon {
    animation: bounce 0.8s ease;
}

.stat-card:hover .stat-value {
    color: var(--primary-light);
    transform: scale(1.05);
}

.stat-value {
    transition: all 0.3s ease;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Loading animation */
.loading-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 3px;
    background: var(--primary-color);
    z-index: 1000;
    transition: width 0.4s ease;
}




    </style>
</head>
<body>
    <div class="loading-bar"></div>
<div class="calendar-loading" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 100;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
    <?php 
    
    include_once 'config.php';
    connexionBdd($bdd);
    
    // Récupération du rôle de l'utilisateur
    $req = $bdd->prepare("SELECT qualite FROM personnel WHERE cin = ?");
    $req->execute([$_SESSION['cin']]);
    $role = $req->fetchColumn();
    ?>
    
    <div class="main-container">
        <?php include_once 'header.php'; ?>
        <br>
        <div class="dashboard-container">
            <h2 class="mb-4 fw-bold" style="color: var(--primary-color);"><i class="bi bi-grid-fill"></i> Tableau de Bord</h2>
            
            <?php if ($role === 'SAL'): ?>
                <!-- Dashboard Salarié -->
                <div class="stats-container">
                    <!-- Carte 1: Demandes en attente -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = 0 AND cin = ?");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Demandes en attente</div>
                    </div>
                    
                    <!-- Carte 2: Demandes approuvées -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = 2 AND cin = ? AND YEAR(DateDemande) = YEAR(CURRENT_DATE())");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Approuvées cette année</div>
                    </div>
                    
                    <!-- Carte 3: Demandes refusées -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = -2 AND cin = ? AND YEAR(DateDemande) = YEAR(CURRENT_DATE())");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Refusées cette année</div>
                    </div>
                    
                    <!-- Carte 4: Solde de congés -->
                    <div>
                     <?php 
                        connexionBdd($bdd);
                        $req = $bdd->prepare("
                                                SELECT 
                                                    conges_restants_annee_precedente,
                                                    conges_annee_en_cours,
                                                    (conges_restants_annee_precedente + conges_annee_en_cours) AS total_conges_disponibles
                                                FROM personnel 
                                                WHERE cin = ?
                                            ");
                                            $req->execute([$_SESSION['cin']]);
                                            $conges = $req->fetch();
                                            echo '<div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-calendar-plus"></i></div>
                            <div class="stat-value">'.$conges['total_conges_disponibles'].'</div>
                            <div class="stat-label">Jours de congé disponibles</div>
                            <div class="stat-subtext">
                                <small>'.$conges['conges_annee_en_cours'].' jours cette année + '.$conges['conges_restants_annee_precedente'].' jours reportés</small>
                            </div>
                        </div>';
                                            ?>
                                        
                                </div>
                </div>
                
            <?php elseif ($role === 'CD'): ?>
                <!-- Dashboard Chef de Département -->
                <div class="stats-container">
                    <!-- Carte 1: Demandes en attente de validation -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande d 
                                                 JOIN personnel p ON d.cin = p.cin 
                                                 WHERE d.Etat = 0 AND p.qualite NOT IN ('CD', 'RH', 'DG') AND p.depart = 
                                                 (SELECT depart FROM personnel WHERE cin = ?)");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Demandes à valider</div>
                    </div>
                    
                    <!-- Carte 2: Demandes approuvées ce mois
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande d 
                                                 JOIN personnel p ON d.cin = p.cin 
                                                 WHERE d.Etat = 1 AND p.depart = 
                                                 (SELECT depart FROM personnel WHERE cin = ?)
                                                 AND YEAR(d.DateDemande) = YEAR(CURRENT_DATE())");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Validées ce mois</div>
                    </div> -->
                    
                    <!-- Carte 3: Membres de l'équipe -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM personnel 
                                                 WHERE depart = 
                                                 (SELECT depart FROM personnel WHERE cin = ? )");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Membres d'équipe</div>
                    </div>
                    <!-- Carte 2: Demandes en attente CD -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = 0 AND cin = ?");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Mes demandes en attente</div>
                    </div>
                    
                    <!-- Carte 4: Solde de congés du CD -->
                    
                        <?php 
                        connexionBdd($bdd);
                        $req = $bdd->prepare("
                                                SELECT 
                                                    conges_restants_annee_precedente,
                                                    conges_annee_en_cours,
                                                    (conges_restants_annee_precedente + conges_annee_en_cours) AS total_conges_disponibles
                                                FROM personnel 
                                                WHERE cin = ?
                                            ");
                                            $req->execute([$_SESSION['cin']]);
                                            $conges = $req->fetch();
                                            echo '<div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-calendar-plus"></i></div>
                            <div class="stat-value">'.$conges['total_conges_disponibles'].'</div>
                            <div class="stat-label">Jours de congé disponibles</div>
                            <div class="stat-subtext">
                                <small>'.$conges['conges_annee_en_cours'].' jours cette année + '.$conges['conges_restants_annee_precedente'].' jours reportés</small>
                            </div>
                        </div>';
                                            ?>
                    
                </div>
                
                <!-- Section Alertes pour le CD -->
                <div class="stats-container">
                    <?php
                    // Requête pour détecter les conflits dans le département
                    $req = $bdd->prepare("SELECT COUNT(*) FROM (
                                        SELECT p.nom, p.prenom, d.DateDepart, d.DateRetour
                                        FROM demande d 
                                        JOIN personnel p ON d.cin = p.cin 
                                        WHERE d.Etat = 2 AND p.depart = 
                                        (SELECT depart FROM personnel WHERE cin = ?)
                                        AND d.DateRetour >= CURRENT_DATE()
                                        GROUP BY d.DateDepart, d.DateRetour 
                                        HAVING COUNT(*) > 2) AS conflits");
                    $req->execute([$_SESSION['cin']]);
                    $conflits = $req->fetchColumn();
                    
                    if ($conflits > 0): ?>
                        <div class="stat-card alert-card">
                            <div class="stat-icon"><i class="bi bi-exclamation-triangle text-warning"></i></div>
                            <div class="stat-value"><?= $conflits ?></div>
                            <div class="stat-label">Conflits de congés détectés</div>
                            <div class="stat-subtext">
                                <small>Plusieurs employés absents en même temps</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($role === 'DG'): ?>
                <!-- Dashboard Directeur Général -->
                <div class="stats-container">
                    <!-- Carte 1: Vue globale des congés validés -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = 2 
                                                 AND YEAR(DateDemande) = YEAR(CURRENT_DATE())");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Congés validés cette année</div>
                    </div>
                    
                    <!-- Carte 2: Vue globale des congés refusés -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = -2 
                                                 AND YEAR(DateDemande) = YEAR(CURRENT_DATE())");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Congés refusés cette année</div>
                    </div>
                    
                    <!-- Carte 3: Alertes départements -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(DISTINCT depart) FROM 
                                                (SELECT p.depart, d.DateDepart, d.DateRetour 
                                                 FROM demande d JOIN personnel p ON d.cin = p.cin 
                                                 WHERE d.Etat = 2 AND d.DateRetour >= CURRENT_DATE()
                                                 GROUP BY p.depart, d.DateDepart, d.DateRetour 
                                                 HAVING COUNT(*) > 2) AS alerts");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Départements en alerte</div>
                    </div>
                    
                    <!-- Carte 4: Solde de congés du DG -->
                    <?php 
                        connexionBdd($bdd);
                        $req = $bdd->prepare("
                                                SELECT 
                                                    conges_restants_annee_precedente,
                                                    conges_annee_en_cours,
                                                    (conges_restants_annee_precedente + conges_annee_en_cours) AS total_conges_disponibles
                                                FROM personnel 
                                                WHERE cin = ?
                                            ");
                                            $req->execute([$_SESSION['cin']]);
                                            $conges = $req->fetch();
                                            echo '<div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-calendar-plus"></i></div>
                            <div class="stat-value">'.$conges['total_conges_disponibles'].'</div>
                            <div class="stat-label">Jours de congé disponibles</div>
                            <div class="stat-subtext">
                                <small>'.$conges['conges_annee_en_cours'].' jours cette année + '.$conges['conges_restants_annee_precedente'].' jours reportés</small>
                            </div>
                        </div>';
                                            ?>
                </div>
                
                <!-- Section Détails par Département -->
<h2 class="mb-4 fw-bold" style="color: var(--primary-color);"><i class="bi bi-graph-up-arrow"></i> Statistiques par département</h2>

<?php
$qualite = $_SESSION['qualite'];
$departSession = $_SESSION['depart'];

// Modification: CD peut voir les stats (suppression de la condition || $qualite === 'CD')
if ($qualite === 'DG' || $qualite === 'CD'):

    // Requête avec filtre si Chef de Département
    $sql = "SELECT 
    p.depart,
    
    -- Demandes validées (Etat = 2)
    SUM(CASE WHEN d.Etat = 2 THEN 1 ELSE 0 END) AS valides,

    -- Demandes refusées (Etat = -2)
    SUM(CASE WHEN d.Etat = -2 THEN 1 ELSE 0 END) AS refuses,

-- En attente : SAL=état 1, autres=état 0
    SUM(CASE WHEN (
        (p.qualite = 'SAL' AND d.Etat = 1) OR 
        (p.qualite IN ('DG', 'CD', 'RH') AND d.Etat = 0)
    ) THEN 1 ELSE 0 END) AS en_attente,

    -- Alertes : + de 2 demandes validées pour la même date de départ dans le même département
    COUNT(DISTINCT CASE 
        WHEN EXISTS (
            SELECT 1
            FROM demande d2
            JOIN personnel p2 ON d2.cin = p2.cin
            WHERE p2.depart = p.depart
              AND d2.Etat = 2
              AND d2.DateDepart = d.DateDepart
              AND d2.DateRetour >= CURRENT_DATE()
            GROUP BY d2.DateDepart
            HAVING COUNT(*) > 2
        ) THEN d.id
    END) AS alertes

FROM personnel p
LEFT JOIN demande d ON p.cin = d.cin
WHERE 1=1 ";

    // Si c'est un chef de département, filtrer par son département
    if ($qualite === 'CD') {
        $sql .= " AND p.depart = :depart ";
    }

    $sql .= " GROUP BY p.depart";

    $req = $bdd->prepare($sql);

    if ($qualite === 'CD') {
        $req->bindParam(':depart', $departSession);
    }

    $req->execute();
?>

<div class="table-responsive mb-4">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Département</th>
                <th>Congés validés</th>
                <th>Congés refusés</th>
                <th>En attente</th>
                <th>Alertes</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $req->fetch()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['depart']) ?></td>
                    <td><?= $row['valides'] ?></td>
                    <td><?= $row['refuses'] ?></td>
                    <td><?= $row['en_attente'] ?></td>
                    <td>
                        <?php if ($row['alertes'] > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $row['alertes'] ?> conflit(s)</span>
                        <?php else: ?>
                            <span class="badge bg-success">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php elseif ($qualite === 'SAL'): ?>
    <div class="alert alert-info">Cette section est réservée aux chefs de département et au directeur général.</div>
<?php endif; ?>

                
            <?php elseif ($role === 'RH'): ?>
                <!-- Dashboard Ressources Humaines -->
                <div class="stats-container">
                    <!-- Carte 1: Demandes acceptées -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = 2 and DateDepart >= NOW()");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Décision(s) de congé en attente</div>
                    </div>
                    
                    <!-- Carte 2: Demandes en attente RH -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = 0 AND cin = ?");
                            $req->execute([$_SESSION['cin']]);
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Demandes en attente</div>
                    </div>
                    
                    <!-- Carte 3: Demandes refusées RH
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT COUNT(*) FROM demande WHERE Etat = -2");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Refusées par RH</div>
                    </div> -->
                    <div>
                     <?php 
                        connexionBdd($bdd);
                        $req = $bdd->prepare("
                                                SELECT 
                                                    conges_restants_annee_precedente,
                                                    conges_annee_en_cours,
                                                    (conges_restants_annee_precedente + conges_annee_en_cours) AS total_conges_disponibles
                                                FROM personnel 
                                                WHERE cin = ?
                                            ");
                                            $req->execute([$_SESSION['cin']]);
                                            $conges = $req->fetch();
                                            echo '<div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-calendar-plus"></i></div>
                            <div class="stat-value">'.$conges['total_conges_disponibles'].'</div>
                            <div class="stat-label">Jours de congé disponibles</div>
                            <div class="stat-subtext">
                                <small>'.$conges['conges_annee_en_cours'].' jours cette année + '.$conges['conges_restants_annee_precedente'].' jours reportés</small>
                            </div>
                        </div>';
                                            ?>
                                        
                                </div>
                    
                    <!-- Carte 4: Solde moyen de congés -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-calendar-plus"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT FLOOR(AVG(conges_restants_annee_precedente + conges_annee_en_cours)) FROM personnel");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Jours de congé moyens</div>
                    </div>
                </div>
                
                <!-- Section Analyse des Congés -->
                <h2 class="mb-4 fw-bold" style="color: var(--primary-color);">Analyse des Congés</h2>
                <div class="stats-container">
                    <!-- Carte 1: Taux d'acceptation -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-percent"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT 
                                                ROUND(SUM(CASE WHEN Etat = 2 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) 
                                                FROM demande WHERE Etat != 0");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Taux d'acceptation</div>
                    </div>
                    
                    <!-- Carte 2: Mois le plus chargé -->
                    <div class="stat-card">
    <div class="stat-icon"><i class="bi bi-calendar-month"></i></div>
    <div class="stat-value">
        <?php 
        // Récupérer le numéro du mois le plus chargé
        $req = $bdd->prepare("SELECT mois FROM 
                            (SELECT MONTH(DateDepart) as mois, COUNT(*) as nb 
                             FROM demande 
                             WHERE Etat = 2 AND YEAR(DateDepart) = YEAR(CURRENT_DATE()) 
                             GROUP BY mois 
                             ORDER BY nb DESC 
                             LIMIT 1) as top_mois");
        $req->execute();
        $moisNum = $req->fetchColumn();

        // Tableau de traduction des mois en français
        $moisFrancais = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
            4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        // Afficher le mois en français
        echo $moisFrancais[(int)$moisNum] ?? 'Inconnu';
        ?>
    </div>
    <div class="stat-label">Mois le plus chargé</div>
</div>

                    
                    <!-- Carte 3: Département avec le plus de demandes -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-building"></i></div>
                        <div class="stat-value">
                            <?php 
                            $req = $bdd->prepare("SELECT p.depart FROM demande d 
                                                 JOIN personnel p ON d.cin = p.cin 
                                                 WHERE d.Etat = 2 
                                                 GROUP BY p.depart 
                                                 ORDER BY COUNT(*) DESC 
                                                 LIMIT 1");
                            $req->execute();
                            echo $req->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Département le plus actif</div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="calendrier">
                <?php include('calendar.php'); ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/fr.min.js"></script>

    <script>
    // Initialiser AOS (Animate On Scroll)
    AOS.init({
        duration: 800,
        once: true,
        easing: 'ease-out-quad'
    });

    // GSAP Animations
    document.addEventListener('DOMContentLoaded', function() {
        // Loading animation
        gsap.to('.loading-bar', {
            width: '100%',
            duration: 0.8,
            onComplete: function() {
                gsap.to('.loading-bar', {
                    opacity: 0,
                    duration: 0.3
                });
            }
        });

        // Animate stat cards sequentially
        gsap.utils.toArray('.stat-card').forEach((card, i) => {
            gsap.to(card, {
                opacity: 1,
                y: 0,
                duration: 0.6,
                delay: i * 0.15,
                ease: "back.out(1.2)",
                onComplete: function() {
                    card.classList.add('animated');
                }
            });
        });

        // Animate calendar container
        gsap.to('.calendar-responsive-container', {
            opacity: 1,
            scale: 1,
            duration: 0.8,
            delay: 0.4,
            ease: "elastic.out(1, 0.5)",
            onComplete: function() {
                document.querySelector('.calendar-responsive-container').classList.add('animated');
            }
        });

        // Hover effects with GSAP
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    y: -5,
                    boxShadow: '0 10px 20px rgba(0,0,0,0.15)',
                    duration: 0.3
                });
            });
            
            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    y: 0,
                    boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                    duration: 0.3
                });
            });
        });

        // Animate numbers counting up
        document.querySelectorAll('.stat-value').forEach(valueElement => {
            const target = parseInt(valueElement.textContent);
            if (!isNaN(target)) {
                let start = 0;
                const duration = 2000;
                const increment = target / (duration / 16);
                
                const updateCount = () => {
                    start += increment;
                    if (start < target) {
                        valueElement.textContent = Math.ceil(start);
                        requestAnimationFrame(updateCount);
                    } else {
                        valueElement.textContent = target;
                    }
                };
                
                setTimeout(updateCount, 300);
            }
        });
    });
//     
</script>
</body>
</html>
