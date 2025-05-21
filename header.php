<style>
:root {
    --seagreen: #2E8B57;
    --seagreen-dark: #246b45;
    --seagreen-light: #3CB371;
    --seagreen-lighter: #E8F5E9;
    --text-dark: #212529;
}

/* Sidebar styling */
.sidebar {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    z-index: 1000;
    overflow-y: auto;
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 1rem;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.sidebar .img-fluid {
    max-width: 100%;
    transition: transform 0.3s ease;
}

.sidebar .img-fluid:hover {
    transform: scale(1.03);
}

.sidebar-nav {
    padding: 1rem 0;
}

.sidebar-nav .nav-link {
    color: var(--seagreen-dark);
    font-weight: 600;
    transition: color 0.3s ease, background-color 0.3s ease;
    border-radius: 6px;
    padding: 10px 15px;
    margin: 5px 15px;
    display: flex;
    align-items: center;
}

.sidebar-nav .nav-link i {
    margin-right: 10px;
    font-size: 1.1rem;
}

.sidebar-nav .nav-link:hover,
.sidebar-nav .nav-link.active {
    color: white;
    background-color: var(--seagreen);
}

/* Déconnexion bouton */
.sidebar-nav .nav-link.text-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545 !important;
}

.sidebar-nav .nav-link.text-danger:hover {
    background-color: #dc3545 !important;
    color: white !important;
}

/* Notification banner */
.notification-container {
    margin-left: 250px;
    padding: 1rem;
}

.alert {
    background: var(--seagreen-lighter);
    color: var(--text-dark);
    border-left: 6px solid var(--seagreen-dark);
    font-weight: 500;
    font-size: 0.95rem;
}

.alert a {
    color: var(--seagreen-dark);
    font-weight: 600;
    text-decoration: underline;
}

.alert a:hover {
    color: var(--seagreen);
}

/* Hamburger menu button */
.menu-toggle {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    display: none;
    background: var(--seagreen);
    color: white;
    border: none;
    border-radius: 5px;
    padding: 5px 10px;
    font-size: 1.5rem;
}

/* Responsive styles - Version corrigée */
@media (max-width: 992px) {
    .sidebar {
        padding-top:45px ;
        transform: translateX(-100%);
        z-index: 1050; /* Au-dessus du header */
    }
    .profile-greeting{
        padding-left: 45px;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    
    .notification-container {
        margin-left: 0;
    }
    
    .menu-toggle {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1051; /* Au-dessus du sidebar */
    }

    .header-container {
        position: relative;
        z-index: 1049; /* Juste en dessous du menu */
        padding-top: 60px; /* Espace pour le bouton */
    }

    .user-profile-card {
        margin-left: 0;
        width: 100%;
    }

    .main-content {
        margin-left: 0;
        padding-top: 20px; /* Compensation pour le header */
    }
}

/* Animation pour éviter le flash au chargement */
@media (max-width: 992px) {
    .sidebar {
        transition: transform 0.3s ease, z-index 0.3s ease 0.3s;
    }
    .sidebar.open {
        transition: transform 0.3s ease;
    }
}

/* Main content shift */
.main-content {
    margin-left: 250px;
    transition: margin 0.3s ease;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
}
</style>

<!-- Hamburger menu button -->
<button class="menu-toggle" id="menuToggle">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="photos/titre.png" alt="Logo CCISCS" class="img-fluid">
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-grid-fill"></i> Tableau de Bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-person-fill"></i> Mon Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="demander.php">
                    <i class="bi bi-send-fill"></i> Demander congé
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="MesDemandes.php">
                    <i class="bi bi-journal-text"></i> Mes demandes
                </a>
            </li>

            <?php include_once('config.php');
            if (isset($_SESSION['qualite'])) {
                if ($_SESSION['qualite'] === 'DG') {
                    echo '<li class="nav-item"><a class="nav-link" href="liste_dem_dg.php"><i class="bi bi-list"></i> Liste des demandes</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="gestion_annulations.php"><i class="bi bi-x-circle-fill"></i> Annulations</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="liste_perso_dg.php"><i class="bi bi-people-fill"></i> Les personnels </a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="archive_dg.php"><i class="bi bi-archive-fill"></i> Archive </a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="diag_dg.php"><i class="bi bi-bar-chart-fill"></i> Visualisation </a></li>';
                }
                if ($_SESSION['qualite'] === 'CD') {
                    echo '<li class="nav-item"><a class="nav-link" href="listeDemandes.php"><i class="bi bi-list"></i> Liste des demandes</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="liste_equipe_cd.php"><i class="bi bi-people-fill"></i> Mon équipe </a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="archive_cd.php"><i class="bi bi-archive-fill"></i> Archive </a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="diag_cd.php"><i class="bi bi-bar-chart-fill"></i> Visualisation </a></li>';

                }
                if ($_SESSION['qualite'] === 'RH') {
                    echo '<li class="nav-item"><a class="nav-link" href="listePersonnels.php"><i class="bi bi-list"></i> Personnels</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="Inscription.php"><i class="bi bi-person-fill-add"></i> Inscription</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="rh.php"><i class="bi bi-printer-fill"></i> Imprimante</a></li>';
                }
            }
            ?>

            <li class="nav-item">
                <a class="nav-link text-danger fw-bold" href="deconnexion.php">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </li>
        </ul>
    </nav>
    
</aside>

<!-- User Profile & Notification Card -->
<div class="user-profile-container">
    <div class="user-profile-card">
        <div class="profile-header">
            <div class="profile-greeting">
                <i class="bi bi-person-circle fs-3 text-success"></i>
                <div>
                    <h6 class="mb-0">Bonjour, <span class="text-success"><?= htmlspecialchars($_SESSION['prenom'] ?? '') ?> <?= htmlspecialchars($_SESSION['nom'] ?? '') ?></span></h6>
                </div>
            </div>
            
            <?php if(isset($_SESSION['qualite'])): ?>
            <span class="badge bg-success-light text-success">
                <?= htmlspecialchars(match($_SESSION['qualite']) {
                    'DG' => 'Directeur Général',
                    'CD' => 'Chef de Département',
                    'RH' => 'Ressources Humaines',
                    default => 'Collaborateur'
                }) ?>
            </span>
            <?php endif; ?>
        </div>

        <?php 
        if(isset($_SESSION['qualite']) && 
           in_array($_SESSION['qualite'], ['DG', 'CD']) && 
           ($nbDemandes = ($_SESSION['qualite'] === 'CD' ? notificationsCD() : notificationsDG())) > 0): ?>
            <div class="notification-alert">
                <i class="bi bi-bell-fill text-warning"></i>
                <div>
                    <small>Nouvelles demandes</small>
                    <a href="<?= $_SESSION['qualite'] === 'CD' ? 'listeDemandes.php?enAttente=1' : 'liste_dem_dg.php?enAttente=1' ?>" 
                       class="d-block fw-bold text-success">
                        <?= $nbDemandes ?> demande(s) en attente
                        <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.user-profile-container {
    position: sticky;
    top: 0px; /* Espace depuis le haut */
    z-index: 1000;
}

.user-profile-card {
    background: white;

    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 1.25rem;
    border: 1px solid #eaeaea;
}

.profile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.profile-greeting {
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-greeting h6 {
    font-weight: 600;
}

.badge.bg-success-light {
    background-color: rgba(46, 139, 87, 0.1);
    padding: 5px 10px;
    font-size: 0.75rem;
}

.notification-alert {
    background: rgba(255, 193, 7, 0.1);
    border-left: 3px solid #ffc107;
    padding: 0.75rem;
    border-radius: 5px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.notification-alert i {
    font-size: 1.25rem;
}

.notification-alert a {
    text-decoration: none;
    transition: color 0.2s;
}

.notification-alert a:hover {
    color: var(--seagreen-dark) !important;
}

.notification-alert small {
    font-size: 0.75rem;
    color: #6c757d;
}
</style>

<!-- JavaScript for toggle functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
    });
});
</script>