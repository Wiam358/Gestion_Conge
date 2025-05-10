<?php session_start();
include_once('config.php');
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
                    title: "Valide!",
                    text: "Modification réussie",
                    icon: "success",
                    confirmButtonText: "OK",
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
                        window.location.href = "index.php";
                    }
                });
            </script>
        </body>
        </html>';
}
else{
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
                    title: "Erreur!",
                    text: "Les mots de passes ne sont pas identiques",
                    icon: "error",
                    confirmButtonText: "Réessayez !",
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
                        window.location.href = "index.php";
                    }
                });
            </script>
        </body>
        </html>';
} 
}
else {
echo " <script language=\"JavaScript\"> alert(\"Veuillez remplir tous les champs ...\") </script>";
}
}
if(isset($_SESSION['cin'])){
$reponse=InfosPersonnel($_SESSION['cin']);
}
?>

