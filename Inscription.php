<?php
session_start();
require('config.php');
verifySession();

$message = null;

try {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
// Récupérer la liste des départements depuis la base de données


if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
    if($_POST['pass'] === $_POST['pass2']) {
        $pass_hache = crypt($_POST['pass'], "x5ncisx5");
        
        $req = $bdd->prepare('INSERT INTO personnel(cin,nom,prenom,service,depart,tel,adresse,login,pass,qualite,email,conges_restants_annee_precedente,conges_annee_en_cours) 
                             VALUES(:cin,:nom,:prenom,:service,:depart,:tel,:adresse,:login,:pass,:qualite,:email,:jourrest,:jourstot)');
        $req->execute([
            ':cin' => strtoupper(htmlspecialchars(trim($_POST['cin']))),
            ':nom' => ucwords(strtolower(htmlspecialchars(trim($_POST['nom'])))),
            ':prenom' => ucwords(strtolower(htmlspecialchars(trim($_POST['prenom'])))),
            ':service' => ucwords(strtolower(htmlspecialchars(trim($_POST['service'])))),
            ':depart' => ($_POST['depart'] === 'autre') ? 
                        ucwords(strtolower(htmlspecialchars(trim($_POST['autreDepart'])))) : 
                        ucwords(strtolower(htmlspecialchars(trim($_POST['depart'])))),
            ':tel' => htmlspecialchars($_POST['tel']),
            ':adresse' => ucwords(strtolower(htmlspecialchars(trim($_POST['adresse'])))),
            ':jourstot' => htmlspecialchars($_POST['jtot']),
            ':jourrest' => htmlspecialchars($_POST['jrest']),
            ':login' => htmlspecialchars($_POST['login']),
            ':pass' => $pass_hache,
            ':qualite' => $_POST['qualite'],
            ':email' => strtolower(htmlspecialchars(trim($_POST['email'])))
        ]);
        
        $message = [
            'type' => 'success',
            'text' => 'Le personnel a été ajouté avec succès !'
        ];
        header('location:listePersonnels.php');
    } else {
        $message = [
            'type' => 'danger',
            'text' => 'Les mots de passe ne correspondent pas'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un personnel | Gestion Congés</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
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
        
        .form-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    border-top: 4px solid var(--primary);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    /* Nouvelles propriétés pour le centrage */
    margin: 0 auto; /* Centre horizontalement */
    max-width: 800px; /* Définit une largeur maximale */
    width: 100%; /* S'adapte à la largeur du conteneur parent */
}
        
        .form-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-title {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-icon {
            font-size: 1.8rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(60, 179, 113, 0.25);
        }
        
        .btn-primary-custom {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-primary-custom:hover {
            background-color: #26734a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 139, 87, 0.3);
            color: white;
        }
        
        .role-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .role-dg { background-color: #6f42c1; color: white; }
        .role-cd { background-color: #d63384; color: white; }
        .role-rh { background-color: #fd7e14; color: white; }
        .role-sal { background-color: #20c997; color: white; }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .form-header {
                padding: 1rem;
            }
            
            .form-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include_once('header.php'); ?>
    
    <div class="container py-4">
        <div class="form-card animate__animated animate__fadeIn">
            <div class="form-header">
                <h2 class="form-title mb-0">
                    <i class="bi bi-person-plus form-icon"></i>
                    Ajouter un nouveau personnel
                </h2>
            </div>
            
            <div class="p-4">
                <?php if(isset($message)): ?>
                    <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show mb-4">
                        <i class="bi <?= $message['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
                        <?= $message['text'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="Inscription.php" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- Colonne 1 -->
                        
                            <div class="col-md-4">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                                <div class="invalid-feedback">Veuillez saisir le nom</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                                <div class="invalid-feedback">Veuillez saisir le prénom</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="cin" class="form-label">CIN</label>
                                <input type="text" class="form-control" id="cin" name="cin" required>
                                <div class="invalid-feedback">Veuillez saisir le CIN</div>
                            </div>
                    </div>
                    <br>
                    <div class="row g-3">
                            <div class="col-md-4">
                                <label for="service" class="form-label">Service</label>
                                <input type="text" class="form-control" id="service" name="service" required>
                                <div class="invalid-feedback">Veuillez saisir le service</div>
                            </div>

                            <div class="col-md-4">
                                <label for="depart" class="form-label">Département</label>
                                <select style="height: 50px;" class="form-select" id="depart" name="depart" required>
                                    <option value="" selected disabled>Choisir</option>
                                <option value="SERVICES AUX RESSORTISSANTS">SERVICES AUX RESSORTISSANTS</option>
                                <option value="APPUI ET PROMOTION">APPUI ET PROMOTION</option>
                                <option value="ADMINISTRATIF ET FINANCIER (PI)">ADMINISTRATIF ET FINANCIER (PI)</option>
                                <option value="STRATÉGIE ET RELATIONS INSTITUTIONNELLES">STRATÉGIE ET RELATIONS INSTITUTIONNELLES</option>
                                    <option value="autre">Autre (préciser)</option>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner un département</div>
                            </div>
                            <div class="col-md-4" id="autreDepartContainer" style="display: none;">
                                <label for="autreDepart" class="form-label">Précisez le département</label>
                                <input type="text" class="form-control" id="autreDepart" name="autreDepart">
                            </div>
                    </div>
                    <br>
                    <div class="row g-3">
                            <div class="col-md-4">
                                <label for="login" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="login" name="login" required>
                                <div class="invalid-feedback">Veuillez saisir un nom d'utilisateur</div>
                            </div>
                            
                            <div class="col-md-4 position-relative">
                                <label for="pass" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="pass" name="pass" required>
                                <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                                <div class="invalid-feedback">Veuillez saisir un mot de passe</div>
                            </div>
                            
                            <div class="col-md-4 position-relative">
                                <label for="pass2" class="form-label">Confirmation</label>
                                <input type="password" class="form-control" id="pass2" name="pass2" required>
                                <i class="bi bi-eye password-toggle" id="toggleConfirmPassword"></i>
                                <div class="invalid-feedback">Veuillez confirmer le mot de passe</div>
                            </div> 
                            <br>        
                        </div>
                        
                    <div class="row g-3">
                            <div class="col-md-4">
                                <label for="tel" class="form-label">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="tel" class="form-control" id="tel" name="tel" 
                                           pattern="^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$" required>
                                </div>
                                <div class="invalid-feedback">Veuillez saisir un numéro valide</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="invalid-feedback">Veuillez saisir un email valide</div>
                            </div>
                        
                            <div class="col-md-4">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" required>
                                <div class="invalid-feedback">Veuillez saisir l'adresse</div>
                            </div>
                        </div>   
                        <br> 
                        <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="jtot" class="form-label">Solde Année Courante</label>
                                    <input type="number" class="form-control" id="jtot" name="jtot" min="0" max="30" required>
                                    <div class="invalid-feedback">Veuillez saisir un nombre valide</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="jrest" class="form-label">Solde Reporté</label>
                                    <input type="number" class="form-control" id="jrest" name="jrest" min="0" max="30" required>
                                    <div class="invalid-feedback">Veuillez saisir un nombre valide</div>
                                </div>

                                <div class="col-md-4">
                            <label class="form-label">Qualité</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="qualite" id="ch1" value="DG" required>
                                    <label class="form-check-label" for="ch1">
                                        <span class="role-badge role-dg">DG</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="qualite" id="ch2" value="CD" required>
                                    <label class="form-check-label" for="ch2">
                                        <span class="role-badge role-cd">CD</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="qualite" id="ch3" value="SAL" required>
                                    <label class="form-check-label" for="ch3">
                                        <span class="role-badge role-sal">SAL</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="qualite" id="ch4" value="RH" required>
                                    <label class="form-check-label" for="ch4">
                                        <span class="role-badge role-rh">RH</span>
                                    </label>
                                </div>
                                <div class="invalid-feedback d-block">Veuillez sélectionner une qualité</div>
                            </div>
                            
                        </div>

                        <!-- Bouton de soumission -->
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary-custom px-5 py-3">
                                <i class="bi bi-person-plus me-2"></i> Enregistrer le personnel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de la visibilité des mots de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('pass');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('pass2');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye-slash');
        });
        
        // Validation du formulaire
        (function() {
            'use strict';
            
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        // Gestion du champ "Autre" pour le département
document.getElementById('depart').addEventListener('change', function() {
    const autreContainer = document.getElementById('autreDepartContainer');
    if (this.value === 'autre') {
        autreContainer.style.display = 'block';
        document.getElementById('autreDepart').setAttribute('required', 'required');
    } else {
        autreContainer.style.display = 'none';
        document.getElementById('autreDepart').removeAttribute('required');
    }
});

    </script>
    <script>
    document.getElementById('depart').addEventListener('change', function () {
        const autreContainer = document.getElementById('autreDepartContainer');
        autreContainer.style.display = (this.value === 'autre') ? 'block' : 'none';
    });
</script>

</body>
</html>