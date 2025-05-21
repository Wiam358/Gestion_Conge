<?php




session_start();

// Connexion à la base de données
try {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}


// Vérification du CIN
if(isset($_POST['verify_cin'])) {
    $cin = $_POST['cin'];
    
    $req = $bdd->prepare('SELECT cin FROM personnel WHERE cin = :cin');
    $req->execute([':cin' => $cin]);
    
    if($req->fetch()) {
        $_SESSION['reset_cin'] = $cin;
        // Pas de redirection, on reste sur la même page avec le modal mis à jour
    } else {
        echo "<script>
            Swal.fire({
                title: 'Erreur',
                text: 'Le CIN n\'a pas été trouvé',
                icon: 'error',
                confirmButtonColor: '#3CB371'
            });
        </script>";
    }
}

// Mise à jour du mot de passe
if(isset($_POST['update_password'])) {
    if($_POST['new_password'] !== $_POST['confirm_password']) {
        echo "<script>
            Swal.fire({
                title: 'Erreur',
                text: 'Les mots de passe ne correspondent pas',
                icon: 'error',
                confirmButtonColor: '#3CB371'
            });
        </script>";
    } else {
        $pass_hache = crypt($_POST['new_password'], "x5ncisx5");
        $update = $bdd->prepare('UPDATE personnel SET pass = :pass WHERE cin = :cin');
        $update->execute([
            ':pass' => $pass_hache,
            ':cin' => $_SESSION['reset_cin']
        ]);
        
        unset($_SESSION['reset_cin']);
        echo "<script>
            Swal.fire({
                title: 'Succès',
                text: 'Votre mot de passe a été mis à jour',
                icon: 'success',
                confirmButtonColor: '#3CB371'
            }).then(() => {
                document.getElementById('reset-modal').style.display = 'none';
            });
        </script>";
    }
}





if(isset($_POST['pass']) && isset($_POST['login'])) {
    $pass_hache = crypt($_POST['pass'], "x5ncisx5");
    
    try {
        $bdd = new PDO('mysql:host=localhost;dbname=congetest', 'root', '');
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $req = $bdd->prepare('SELECT cin, nom, prenom, qualite, depart FROM personnel WHERE login = :login AND pass = :pass');
        $req->execute(array(':login' => $_POST['login'], ':pass' => $pass_hache));
        $resultat = $req->fetch();
        
        if(!$resultat) {
            // Affichage de l'alerte SweetAlert
            echo '<!DOCTYPE html>
        <html>
        <head>
            <link rel="icon" type="image/png" href="photos/cciscs.png" />
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                :root {
                    --seagreen-dark: #2E8B57;
                    --seagreen-primary: #3CB371;
                    --seagreen-light: #8FBC8F;
                    --seagreen-lighter: #98FB98;
                    --seagreen-bg: #F0FFF0;
                }
                
                .swal2-popup {
                    background: var(--seagreen-bg) !important;
                    border: 2px solid var(--seagreen-light) !important;
                    border-radius: 15px !important;
                }
                
                .swal2-title {
                    color: var(--seagreen-dark) !important;
                    font-weight: 600 !important;
                }
                
                .swal2-content {
                    color: #333 !important;
                }
                
                .swal2-confirm {
                    background-color: var(--seagreen-primary) !important;
                    border: none !important;
                    box-shadow: none !important;
                    transition: all 0.3s ease !important;
                }
                
                .swal2-confirm:hover {
                    background-color: var(--seagreen-dark) !important;
                    transform: translateY(-2px) !important;
                    box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3) !important;
                }
                
                .swal2-backdrop-show {
                    background: rgba(143, 188, 143, 0.5) !important;
                }
                
                .swal2-icon.swal2-error {
                    color: var(--seagreen-dark) !important;
                    border-color: var(--seagreen-dark) !important;
                }
                
                .swal2-icon.swal2-error [class^="swal2-x-mark-line"] {
                    background-color: var(--seagreen-dark) !important;
                }
            </style>
        </head>
        <body>
            <script>
                Swal.fire({
                    title: "Échec de connexion",
                    text: "Le Nom d\'utilisateur ou le mot de passe est incorrect",
                    icon: "error",
                    confirmButtonText: "Réessayer",
                    confirmButtonColor: "#3CB371",
                    background: "#F0FFF0",
                    backdrop: `
                        rgba(143, 188, 143, 0.4)
                        url("cadenas.png")
                        center left
                        no-repeat
                    `,
                    customClass: {
                        container: "swal2-seagreen-theme"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "Connection.php";
                    }
                });
            </script>
        </body>
        </html>';
        exit();
    } else {
            // Suite du traitement si connexion réussie
            session_start();
            $_SESSION['cin'] = $resultat['cin'];
            $_SESSION['login'] = $_POST['login'];
            $_SESSION['nom'] = strtoupper($resultat['nom']);
            $_SESSION['prenom'] = $resultat['prenom'];
            $_SESSION['qualite'] = $resultat['qualite'];
            $_SESSION['depart']= $resultat['depart'];
            header('Location: dashboard.php');
            exit();
        }
    } catch (Exception $e) {
        die('Erreur : ' . $e->getMessage());
    }
}
?>

<!-- Le reste de votre formulaire de connexion HTML ici -->

<html>
<head>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<title> Identifiez-vous</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
	
	<style>
        :root {
            --seagreen-dark: #2E8B57;
            --seagreen-primary: #3CB371;
            --seagreen-light: #8FBC8F;
            --seagreen-lighter: #98FB98;
            --seagreen-bg: #F0FFF0;
        }
        
        body {
            background-color: var(--seagreen-bg);
            font-family: sans-serif;
            margin: 0;
            padding: 0;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(143, 188, 143, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(143, 188, 143, 0.1) 0%, transparent 20%);
        }
        
        .container {
            max-width: 800px;
            width: 90%;
            margin: 2rem auto;
            animation: fadeIn 0.8s ease-out;
        }
        
        .header {
            margin-bottom: 1rem;
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
			height:30px;
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
        
        /* Responsive */
        @media (max-width: 576px) {
            .container {
                width: 95%;
                margin: 1rem auto;
            }
            
            fieldset {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
	<div class="container">
<div class="header">
<img src="photos/titre.png" style="width:100%;">
</div><hr>
<fieldset><legend><img src="cadenas.png"></legend>
<form method="post" action="Connection.php">
	<input type="text" name="login" placeholder="Nom d'utilisateur" autocomplete="off" required /><br><br>
	<input type="password" name="pass" placeholder="Mot de passe "  required /><br><br>
	<input type="submit" Value="Connexion" />
</form>
    <hr>
        <div style="text-align: center; margin-top: 15px;">
            <a href="#" id="forgot-password-link" style="color: #2E8B57; text-decoration: none;">Mot de passe oublié ?</a>
        </div>
    </fieldset>
    
    <br>
<?php include_once("footer.html");?>
<br>
 </div>   
<!-- Modal de réinitialisation -->
<div id="reset-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:white;padding:20px;border-radius:10px;width:90%;max-width:400px">
        <?php if(!isset($_SESSION['reset_cin'])): ?>
        <!-- Étape 1 : Vérification CIN -->
        <h3 style="color:#2E8B57;margin-top:0">Vérification d'identité</h3>
        <form id="verify-cin-form" method="post">
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
        
        <?php else: ?>
        <!-- Étape 2 : Nouveau mot de passe -->
        <h3 style="color:#2E8B57;margin-top:0">Nouveau mot de passe</h3>
        <form id="update-password-form" method="post">
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
// Fonctions pour gérer le modal
function openResetModal() {
    document.getElementById('reset-modal').style.display = 'flex';
    // Réinitialiser le formulaire à l'ouverture
    fetch('Connection.php?reset_modal=1')
        .then(() => {
            const forms = document.querySelectorAll('#reset-modal form');
            forms.forEach(form => form.reset());
        });
}

function closeResetModal() {
    document.getElementById('reset-modal').style.display = 'none';
    // Réinitialiser la session côté serveur
    fetch('Connection.php?reset_session=1');
}

// Gestion du lien "Mot de passe oublié"
document.getElementById('forgot-password-link').addEventListener('click', function(e) {
    e.preventDefault();
    openResetModal();
});

// Rafraîchir le modal après soumission du CIN
<?php if(isset($_SESSION['reset_cin'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    openResetModal();
});
<?php endif; ?>
</script>

</body>
</html>
