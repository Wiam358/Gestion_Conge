<?php
session_start();

// Set session configuration
// ini_set('session.gc_maxlifetime', 1440);
// ini_set('session.cookie_lifetime', 1440);

error_log("Session ID: " . session_id());
error_log("Session content: " . print_r($_SESSION, true));

// Include PHPMailer and other configurations
require 'config.php';

// Connexion à la base de données
try {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest', 'root', '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Step 1: Verify CIN and send email
if (isset($_POST['verify_cin'])) {
    $cin = $_POST['cin'];
    
    $req = $bdd->prepare('SELECT cin, email FROM personnel WHERE cin = :cin');
    $req->execute([':cin' => $cin]);
    $user = $req->fetch();
    
    if ($user) {
        $verificationCode = rand(100000, 999999);
        $_SESSION['reset_cin'] = $cin;
        $_SESSION['verification_code'] = $verificationCode;
        $_SESSION['reset_email'] = $user['email'];
        $_SESSION['show_verification_form'] = true;
        
        error_log("Verification code set: " . $verificationCode);
        error_log("Session content after verify_cin: " . print_r($_SESSION, true));
        
        // Send verification code via email using EnvoyerEmail
        $subject = 'Code de vérification pour réinitialisation du mot de passe';
        $message = nl2br("Bonjour,\n\nVotre code de vérification pour réinitialiser votre mot de passe est : <b>$verificationCode</b>\nVeuillez entrer ce code pour continuer.\n\nCeci est un message automatique, merci de ne pas y répondre.");
        
        if (EnvoyerEmail($user['email'], $subject, $message)) {
            echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Code envoyé',
            text: 'Un code de vérification a été envoyé à votre email.',
            icon: 'success',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            window.location.href = 'Connection.php?step=code';
        });
    });
</script>";
        } else {
            echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'Erreur lors de l\'envoi de l\'email.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        });
    });
</script>";
        }
    } else {
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'CIN non trouvé.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        });
    });
</script>";
    }
}

// Étape 2 : Vérification du code
if (isset($_POST['verify_code'])) {
    $enteredCode = $_POST['verification_code'] ?? '';
    error_log("Code entré: " . $enteredCode);
    error_log("Session ID in verify_code: " . session_id());
    error_log("Session content in verify_code: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['verification_code'])) {
        // Supprimer les variables de session et rediriger vers la page de connexion
        unset($_SESSION['reset_cin']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_verified']);
        unset($_SESSION['show_verification_form']);
        
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'Le code de vérification n\'est pas défini. Veuillez recommencer le processus.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            document.getElementById('reset-modal').style.display = 'none';
            window.location.href = 'Connection.php';
        });
    });
</script>";
        // Redirection côté serveur en secours
        header('Location: Connection.php');
        exit;
    }
    
    if (empty($enteredCode)) {
        // Supprimer les variables de session et rediriger vers la page de connexion
        unset($_SESSION['reset_cin']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_verified']);
        unset($_SESSION['show_verification_form']);
        
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'Veuillez entrer un code de vérification.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            document.getElementById('reset-modal').style.display = 'none';
            window.location.href = 'Connection.php';
        });
    });
</script>";
        // Redirection côté serveur en secours
        header('Location: Connection.php');
        exit;
    }
    
    if (trim($enteredCode) == trim($_SESSION['verification_code'])) {
        $_SESSION['code_verified'] = true;
        $_SESSION['show_verification_form'] = false;
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Succès',
            text: 'Code de vérification correct.',
            icon: 'success',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            window.location.href = 'Connection.php?step=password';
        });
    });
</script>";
        // Redirection côté serveur en secours
        header('Location: Connection.php?step=password');
        exit;
    } else {
        // Supprimer les variables de session et rediriger vers la page de connexion
        unset($_SESSION['reset_cin']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_verified']);
        unset($_SESSION['show_verification_form']);
        
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'Code de vérification incorrect. Veuillez recommencer le processus.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            document.getElementById('reset-modal').style.display = 'none';
            window.location.href = 'Connection.php';
        });
    });
</script>";
        // // Redirection côté serveur en secours
        // header('Location: Connection.php');
        // exit;
    }
}

// Step 3: Update password
if (isset($_POST['update_password'])) {
    if (!isset($_SESSION['code_verified']) || !$_SESSION['code_verified']) {
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'Veuillez d\'abord vérifier le code.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        });
    });
</script>";
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
         // Supprimer les variables de session et rediriger vers la page de connexion
        unset($_SESSION['reset_cin']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_verified']);
        unset($_SESSION['show_verification_form']);
        
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Erreur',
            text: 'Les mot de passes ne sont pas identiques. Veuillez recommencer le processus.',
            icon: 'error',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            document.getElementById('reset-modal').style.display = 'none';
            window.location.href = 'Connection.php';
        });
    });
</script>";
    } else {
        $pass_hache = crypt($_POST['new_password'], "x5ncisx5");
        $update = $bdd->prepare('UPDATE personnel SET pass = :pass WHERE cin = :cin');
        $update->execute([
            ':pass' => $pass_hache,
            ':cin' => $_SESSION['reset_cin']
        ]);
        
        // Clear session variables
        unset($_SESSION['reset_cin']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_verified']);
        unset($_SESSION['show_verification_form']);
        
        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Succès',
            text: 'Mot de passe modifié avec succès.',
            icon: 'success',
            confirmButtonColor: '#3CB371'
        }).then(() => {
            document.getElementById('reset-modal').style.display = 'none';
            window.location.href = 'Connection.php';
        });
    });
</script>";
    }
}

// Reset session if requested
if (isset($_GET['reset_session'])) {
    unset($_SESSION['reset_cin']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['code_verified']);
    unset($_SESSION['show_verification_form']);
    exit();
}

// Existing login logic
if (isset($_POST['pass']) && isset($_POST['login'])) {
    $pass_hache = crypt($_POST['pass'], "x5ncisx5");
    
    try {
        $req = $bdd->prepare('SELECT cin, nom, prenom, qualite, depart FROM personnel WHERE login = :login AND pass = :pass');
        $req->execute(array(':login' => $_POST['login'], ':pass' => $pass_hache));
        $resultat = $req->fetch();
        
        if (!$resultat) {
            echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: "Échec de connexion",
            text: "Le Nom d\'utilisateur ou le mot de passe est incorrect",
            icon: "error",
            confirmButtonText: "Réessayer",
        }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "Connection.php";
                        }
                    });
    });
</script>';
            
        } else {
            $_SESSION['cin'] = $resultat['cin'];
            $_SESSION['login'] = $_POST['login'];
            $_SESSION['nom'] = strtoupper($resultat['nom']);
            $_SESSION['prenom'] = $resultat['prenom'];
            $_SESSION['qualite'] = $resultat['qualite'];
            $_SESSION['depart'] = $resultat['depart'];
            header('Location: dashboard.php');
            exit();
        }
    } catch (Exception $e) {
        die('Erreur : ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Identifiez-vous</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <style>
        :root {
            --seagreen-dark: #2E8B57;
            --seagreen-primary: #3CB371;
            --seagreen-light: #8FBC8F;
            --seagreen-lighter: #98FB98;
            --seagreen-bg: #F0FFF0;
        }
        
        /* Nouveau style pour la vidéo d'arrière-plan */
        /* Modifiez ces classes dans votre style existant */

.video-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    object-fit: cover;
    opacity: 0.7; /* Augmenté de 0.3 à 0.7 */
    /* filter: grayscale(30%); Option: garder un léger filtre pour le style */
    mix-blend-mode: multiply;
    background-color: rgba(0, 0, 0, 0.3); /* Couleur de fonçage */
}

body {
    font-family: sans-serif;
    margin: 0;
    padding: 0;
    background-color: rgba(115, 118, 115, 0.7); /* Fond plus transparent */
}

/* Pour améliorer la lisibilité du contenu */
.login-container, 
.welcome-content {
    background-color: rgba(255, 255, 255, 0.85); /* Fond plus opaque */
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3); /* Ombre plus prononcée */
}
         /* Conteneur principal flex */
        .main-container {
            display: flex;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        
        /* Container du formulaire à gauche */
        .login-container {
            width: 45%;
            max-width: 500px;
            margin-left: 5%;
            animation: fadeIn 0.8s ease-out;
            background-color: rgba(99, 91, 91, 0.9);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        /* Container du texte à droite */
        .welcome-container {
            width: 45%;
            max-width: 600px;
            margin-left: 5%;
            padding: 40px;
            color: white;
            position: relative;
            animation: fadeIn 0.8s ease-out;
        }
        
        .welcome-content {
            background-color: rgba(46, 139, 87, 0.85);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .welcome-title {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: white;
            font-weight: 600;
        }
        
        .welcome-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .features-list {
            list-style-type: none;
            padding: 0;
        }
        
        .features-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            font-size: 1rem;
        }
        
        .features-list li:before {
            content: "✓";
            color: #98FB98;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .header {
            margin-bottom: 1rem;
        }
        .login-container {
            width: 45%;
            max-width: 500px;
            margin-left: 5%;
            animation: fadeIn 0.8s ease-out;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        fieldset {
            border: 2px solid var(--seagreen-light);
            border-radius: 15px;
            padding: 2rem;
            background-color: white;
            box-shadow: 0 10px 30px rgba(46, 139, 87, 0.2);
            transition: transform 0.3s ease;
        }
        fieldset:hover {
            transform: translateY(-5px);
        }
        legend {
            padding: 0 10px;
            color: var(--seagreen-dark);
            font-weight: bold;
        }
        legend img {
            vertical-align: middle;
            margin-right: 5px;
            height: 30px;
        }
        form {
            margin-top: 1rem;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--seagreen-light);
            box-shadow: 0 0 0 0.25rem rgba(60, 179, 113, 0.25);
            outline: none;
        }
        input[type="submit"] {
            background-color: var(--seagreen-primary);
            border: none;
            color: white;
            padding: 12px 20px;
            width: 100%;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: var(--seagreen-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
        }
        hr {
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(46, 139, 87, 0), rgba(46, 139, 87, 0.75), rgba(46, 139, 87, 0));
            margin: 1.5rem 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 576px) {
            .container {
                width: 95%;
                margin: 1rem auto;
            }
            fieldset {
                padding: 1.5rem;
            }
        }
        @media (max-width: 992px) {
            .login-container {
                height: 550px;
                width: 550px;
                margin: 1rem auto;
            }
        }
    </style>
</head>
<body>
    <!-- Ajoutez cette vidéo en arrière-plan -->
    <video autoplay muted loop id="bg-video" class="video-background">
        <source src="photos/ccisc.mp4" type="video/mp4">
        <!-- Vous pouvez ajouter d'autres formats pour la compatibilité -->
        <!-- <source src="videos/background.webm" type="video/webm"> -->
        Votre navigateur ne supporte pas les vidéos HTML5.
    </video>
    <div class="main-container">
    <div class="login-container">
        <div class="header">
            <img src="photos/titre.png" style="width:100%;">
        </div>
        <hr>
        <fieldset>
            <legend><img src="cadenas.png"></legend>
            <form method="post" action="Connection.php">
                <input type="text" name="login" placeholder="Nom d'utilisateur" autocomplete="off" required /><br><br>
                <input type="password" name="pass" placeholder="Mot de passe" required /><br><br>
                <input type="submit" value="Connexion" />
            </form>
            <hr>
            <div style="text-align: center; margin-top: 15px;">
                <a href="#" id="forgot-password-link" style="color: #2E8B57; text-decoration: none;">Mot de passe oublié ?</a>
            </div>
        </fieldset>
        
        <?php include_once("footer.html"); ?>
        <br>
    </div>
    <!-- Nouveau container pour le texte (à droite) -->
        <div class="welcome-container">
    <div class="welcome-content">
        <h2 class="welcome-title">Bienvenue sur la Plateforme de Gestion des Congés</h2>
        <p class="welcome-text">
            Accédez à votre espace personnel pour gérer vos demandes de congés en toute simplicité. 
            Notre système sécurisé vous permet de :
        </p>
        
        <ul class="features-list">
            <li>Soumettre et suivre vos demandes de congé en temps réel</li>
            <li>Consulter votre solde de jours disponibles</li>
            <li>Visualiser l'historique complet de vos absences</li>
            <li>Recevoir des notifications sur l'état de vos demandes</li>
            <li>Accéder à vos documents administratifs</li>
        </ul>

        
        
        <div class="contact-info">
            <h3 class="contact-title">Support Technique:</h3>
            <p>
                <strong>Email :</strong> support.drh@cciscs.com
                <strong>Téléphone :</strong> +212 5 23 45 67 89<br>
            </p>
        </div>
    </div>
</div>
    </div>
    
    <!-- Modal de réinitialisation -->
    <div id="reset-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;padding:20px;border-radius:10px;width:90%;max-width:400px">
            <?php if (!isset($_SESSION['reset_cin'])): ?>
                <!-- Étape 1 : Vérification CIN -->
                <h3 style="color:#2E8B57;margin-top:0">Vérification d'identité</h3>
                <form id="verify-cin-form" method="post" action="Connection.php">
                    <input type="text" name="cin" placeholder="Votre CIN" required
                           style="width:100%;padding:10px;margin-bottom:15px;border:1px solid #ddd;border-radius:5px">
                    <div style="display:flex;justify-content:space-between">
                        <button type="button" onclick="closeResetModal()" 
                                style="padding:8px 15px;background:#ccc;border:none;border-radius:5px;cursor:pointer">
                            Annuler
                        </button>
                        <button type="submit" name="verify_cin"
                                style="padding:8px 15px;background:#3CB371;color:white;border:none;border-radius:5px;cursor:pointer">
                            Vérifier
                        </button>
                    </div>
                </form>
            <?php elseif (isset($_GET['step']) && $_GET['step'] === 'code' && isset($_SESSION['show_verification_form']) && $_SESSION['show_verification_form']): ?>
                <!-- Étape 2 : Vérification du code -->
                <h3 style="color:#2E8B57;margin-top:0">Entrez le code de vérification</h3>
                <form id="verify-code-form" method="post" action="Connection.php">
                    <input type="text" name="verification_code" placeholder="Code de vérification" required
                           style="width:100%;padding:10px;margin-bottom:15px;border:1px solid #ddd;border-radius:5px">
                    <div style="display:flex;justify-content:space-between">
                        <button type="button" onclick="closeResetModal()"
                                style="padding:8px 15px;background:#ccc;border:none;border-radius:5px;cursor:pointer">
                            Annuler
                        </button>
                        <button type="submit" name="verify_code"
                                style="padding:8px 15px;background:#3CB371;color:white;border:none;border-radius:5px;cursor:pointer">
                            Vérifier
                        </button>
                    </div>
                </form>
            <?php elseif (isset($_GET['step']) && $_GET['step'] === 'password' && isset($_SESSION['code_verified']) && $_SESSION['code_verified']): ?>
                <!-- Étape 3 : Nouveau mot de passe -->
                <h3 style="color:#2E8B57;margin-top:0">Nouveau mot de passe</h3>
                <form id="update-password-form" method="post" action="Connection.php">
                    <input type="password" name="new_password" placeholder="Nouveau mot de passe" required
                           style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ddd;border-radius:5px">
                    <input type="password" name="confirm_password" placeholder="Confirmez le mot de passe" required
                           style="width:100%;padding:10px;margin-bottom:15px;border:1px solid #ddd;border-radius:5px">
                    <div style="display:flex;justify-content:space-between">
                        <button type="button" onclick="closeResetModal()"
                                style="padding:8px 15px;background:#ccc;border:none;border-radius:5px;cursor:pointer">
                            Annuler
                        </button>
                        <button type="submit" name="update_password"
                                style="padding:8px 15px;background:#3CB371;color:white;border:none;border-radius:5px;cursor:pointer">
                            Enregistrer
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openResetModal() {
            document.getElementById('reset-modal').style.display = 'flex';
            <?php if (!isset($_SESSION['reset_cin'])): ?>
                fetch('Connection.php?reset_session=1', { credentials: 'same-origin' })
                    .then(() => {
                        const forms = document.querySelectorAll('#reset-modal form');
                        forms.forEach(form => form.reset());
                    });
            <?php endif; ?>
        }

        function closeResetModal() {
            document.getElementById('reset-modal').style.display = 'none';
            fetch('Connection.php?reset_session=1', { credentials: 'same-origin' });
        }

        document.getElementById('forgot-password-link').addEventListener('click', function(e) {
            e.preventDefault();
            openResetModal();
        });

        <?php if (isset($_SESSION['reset_cin']) && (isset($_SESSION['show_verification_form']) || isset($_SESSION['code_verified']))): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openResetModal();
        });
        <?php endif; ?>
        // Script pour gérer la lecture de la vidéo
        document.addEventListener('DOMContentLoaded', function() {
            var video = document.getElementById('bg-video');
            try {
                video.play().catch(e => {
                    console.log("La lecture automatique a été bloquée:", e);
                    // Solution de repli si la lecture automatique est bloquée
                    video.muted = true;
                    video.play();
                });
            } catch (e) {
                console.log("Erreur de lecture vidéo:", e);
            }
        });
    </script>
</body>
</html>