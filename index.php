<?php
session_start();
include_once('config.php');
connexionBdd($bdd);
verifySession();
if(isset($_POST['newValue'])){
try
{
$bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
}catch (Exception $e)
{die('Erreur : ' . $e->getMessage());}

if(  isset($_POST['pass']) & isset($_POST['pass2']) & isset($_POST['login']) & isset($_POST['tel']) & isset($_POST['adresse']) & isset($_POST['email']) )
{
	if ($_POST['pass']==$_POST['pass2']) {
		$pass_hache=crypt($_POST['pass'],"x5ncisx5");
$req = $bdd->prepare('UPDATE  personnel set tel=:ptel,adresse=:padresse,login=:plogin,pass=:ppass,email=:pemail WHERE cin=\'' . $_SESSION['cin']. '\'');
$req->execute(array(
':ptel' => htmlspecialchars($_POST['tel']),
':padresse' => ucfirst(strtolower(htmlspecialchars(trim($_POST['adresse'])))),
':plogin' => htmlspecialchars(trim($_POST['login'])),
':ppass' => $pass_hache,
':pemail' => htmlspecialchars(trim($_POST['email']))
));
// Après une modification réussie
$_SESSION['success_message'] = "Vos informations ont été modifiées avec succès.";
header("Location: index.php"); // ou index.php si c’est ta page d’accueil
exit();


}
else{
	$_SESSION['error_message'] = "Échec de la modification. Veuillez réessayer.";
header("Location: index.php");
exit();

} 
}
}
if(isset($_SESSION['cin'])){
$reponse=InfosPersonnel($_SESSION['cin']);
}
?>
<html>
<head>
    <title> Mon Profil | <?= $_SESSION['nom'] ?> <?= $_SESSION['prenom'] ?></title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --seagreen-dark: #2E8B57;
            --seagreen-primary: #3CB371;
            --seagreen-light: #8FBC8F;
            --seagreen-lighter: #98FB98;
            --seagreen-bg: #F0FFF0;
            --gradient-start: #3CB371;
            --gradient-end: #2E8B57;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-left: 250px;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 15px;
        }
        
        .profile-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .info-badge {
            background-color: var(--seagreen-lighter);
            color: var(--seagreen-dark);
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-table {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(240, 255, 240, 0.5);
        }
        
        .info-table th {
            width: 30%;
            font-weight: 600;
            color: var(--seagreen-dark);
            vertical-align: middle;
            padding-left: 1.5rem;
        }
        
        .info-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .edit-btn {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 139, 87, 0.4);
        }
        
        .modal-content {
            border-radius: 16px;
            overflow: hidden;
            border: none;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-control:focus {
            border-color: var(--seagreen-primary);
            box-shadow: 0 0 0 0.25rem rgba(60, 179, 113, 0.25);
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1199px) {
            body {
                padding-left: 0;
            }
            
            .profile-container {
                margin-top: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .info-table th, 
            .info-table td {
                padding: 0.75rem;
                font-size: 0.95rem;
            }
            
            .info-table th {
                width: 35%;
                padding-left: 1rem;
            }
            
            .card-header h5 {
                font-size: 1.25rem;
            }
            
            .profile-container {
                max-width: 95%;
            }
            
            .d-flex.flex-column.flex-md-row {
                flex-direction: column !important;
                text-align: center;
            }
            
            .me-md-4 {
                margin-right: 0 !important;
                margin-bottom: 1.5rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .profile-container {
                padding: 0 10px;
            }
            
            .info-table {
                display: block;
                width: 100%;
                overflow-x: auto;
            }
            
            .info-table th {
                width: 40%;
                display: table-cell;
            }
            
            .edit-btn {
                width: 100%;
                justify-content: center;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .row.g-3 > .col-md-6 {
                width: 100%;
            }
            
            .row > .col-md-6 {
                width: 100%;
            }
        }
        @media (max-width: 600px) {
            body {
                margin-left: 0;
                
            }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Style minimaliste pour modal compact */
        #modifierInfosModal .modal-dialog {
            max-width: 450px;
            margin: 0.5rem auto;
        }

        #modifierInfosModal .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* En-tête compact */
        #modifierInfosModal .modal-header {
            background: #3CB371;
            color: white;
            padding: 0.8rem 1.2rem;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
        }

        #modifierInfosModal .modal-title {
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #modifierInfosModal .btn-close {
            filter: invert(1);
            opacity: 0.8;
            padding: 0.5rem;
            margin: -0.5rem -0.5rem -0.5rem auto;
        }

        /* Corps fluide */
        #modifierInfosModal .modal-body {
            padding: 1.2rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        #modifierInfosModal .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #555;
            margin-bottom: 0.3rem;
        }

        #modifierInfosModal .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        #modifierInfosModal .form-control:focus {
            border-color: #3CB371;
            box-shadow: 0 0 0 2px rgba(60, 179, 113, 0.2);
        }

        /* Positionnement icônes mot de passe */
        #modifierInfosModal .position-relative {
            position: relative;
        }

        #modifierInfosModal .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            color: #999;
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* Pied de page minimal */
        #modifierInfosModal .modal-footer {
            padding: 0.8rem 1.2rem;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
            border-radius: 0 0 10px 10px;
        }

        #modifierInfosModal .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        #modifierInfosModal .btn-outline-secondary {
            border-color: #ddd;
        }

        #modifierInfosModal .btn-primary {
            background: #3CB371;
            border: none;
        }

        #modifierInfosModal .btn-primary:hover {
            background: #2E8B57;
        }

        /* Animation fluide */
        .modal.fade .modal-dialog {
            transition: transform 0.15s ease-out;
            transform: translateY(-10px);
        }

        .modal.show .modal-dialog {
            transform: none;
        }

        /* Scrollbar discrète */
        #modifierInfosModal .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        #modifierInfosModal .modal-body::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 3px;
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
    
    <div class="profile-container animate__animated animate__fadeIn">
        <?php if(isset($_SESSION['cin']) && isset($_SESSION['login'])): ?>
            <?php $infos = InfosPersonnel($_SESSION['cin']); ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card profile-card mb-5 animate-fade-in">
                        <div class="card-header text-center">
                            <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>MON PROFIL</h5>
                        </div>
                        
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row align-items-center mb-4">
                                <div class="me-md-4 mb-3 mb-md-0">
                                    <div class="bg-white p-3 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                        <i class="bi bi-person-fill text-seagreen" style="font-size: 3rem;"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 text-center text-md-start">
                                    <h4 class="mb-1"><?= $_SESSION['nom'] ?> <?= $infos['prenom'] ?></h4>
                                    <p class="text-muted mb-2"><?= $infos['service'] ?> • <?= $infos['depart'] ?></p>
                                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                                        <span class="info-badge">
                                            <i class="bi bi-person-vcard"></i> <?= $_SESSION['cin'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table info-table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><i class="bi bi-person-fill me-2"></i>Nom d'utilisateur</th>
                                            <td><?= $infos['login'] ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="bi bi-telephone-fill me-2"></i>Téléphone</th>
                                            <td><?= $infos['tel'] ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="bi bi-envelope-fill me-2"></i>Email</th>
                                            <td><?= $infos['email'] ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><i class="bi bi-geo-alt-fill me-2"></i>Adresse</th>
                                            <td><?= $infos['adresse'] ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#modifierInfosModal">
                                    <i class="fas fa-edit me-1"></i> Modifier mes informations
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal de modification -->
            <div class="modal fade" id="modifierInfosModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-gradient text-white">
                            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier mon profil</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="index.php">
                            <div class="modal-body">
                                <?php if(isset($message)): ?>
                                    <div class="alert alert-success"><?= $message ?></div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="login" class="form-label">Nom d'utilisateur</label>
                                    <input type="text" class="form-control form-control-lg" name="login" id="login" value="<?= $infos['login'] ?>" required>
                                </div>
                                
                                <div class="row">
                                    <!-- Colonne Mot de passe -->
                                    <div class="col-md-6 mb-3 position-relative">
                                        <label for="password" class="form-label">Mot de passe</label>
                                        <input type="password" class="form-control form-control-lg" name="pass" id="password" required>
                                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                    </div>
                                    
                                    <!-- Colonne Confirmation -->
                                    <div class="col-md-6 mb-3 position-relative">
                                        <label for="confirmPassword" class="form-label">Confirmation</label>
                                        <input type="password" class="form-control form-control-lg" name="pass2" id="confirmPassword" required>
                                        <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control form-control-lg" name="tel" id="telephone" value="<?= $infos['tel'] ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control form-control-lg" name="email" id="email" value="<?= $infos['email'] ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3 mt-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control form-control-lg" name="adresse" id="adresse" value="<?= $infos['adresse'] ?>" required>
                                </div>
                                
                                <input type="hidden" name="newValue" value="1">
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check-circle-fill me-1"></i> Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <?php header('Location: Connection.php'); ?>
        <?php endif; ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
    <?php if (isset($_SESSION['success_message'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Succès',
        text: '<?= $_SESSION['success_message'] ?>',
        confirmButtonColor: '#3085d6'
    });
</script>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: '<?= $_SESSION['error_message'] ?>',
        confirmButtonColor: '#d33'
    });
</script>
<?php unset($_SESSION['error_message']); endif; ?>


</body>
</html>