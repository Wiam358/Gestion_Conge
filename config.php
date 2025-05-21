<?php
/**
 * Sends an email using PHPMailer with Gmail SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message HTML email content
 * @param array $cc Optional CC recipients (array of emails)
 * @param array $bcc Optional BCC recipients (array of emails)
 * @return bool True if sent successfully, false otherwise
 */
function EnvoyerEmail($to, $subject, $message, $cc = [], $bcc = []) {
    // Load PHPMailer through Composer's autoloader
    require __DIR__ . '/vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings (Gmail SMTP)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->Username = 'wiamaithfid@gmail.com'; // Replace with your email
        $mail->Password = 'ejeh iltn cgfg hpri'; // Replace with app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64'; // Better for special characters

        // Recipients
        $mail->setFrom('wiamaithfid@gmail.com', 'wiam');
        $mail->addAddress($to); // Primary recipient

        // Add CC recipients if any
        foreach ($cc as $email) {
            $mail->addCC($email);
        }

        // Add BCC recipients if any
        foreach ($bcc as $email) {
            $mail->addBCC($email);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message); // Plain text fallback
        $mail->XMailer = ' '; // Remove X-Mailer header for privacy

        return $mail->send();
    } catch (Exception $e) {
        error_log(sprintf(
            'Email failed to %s: %s',
            $to,
            $mail->ErrorInfo
        ));
        return false;
    }
}
function deconnexion(){
// Détruit toutes les variables de session
$_SESSION = array();
 
// Si vous voulez détruire complètement la session, effacez également
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,$params["path"], $params["domain"],$params["secure"], $params["httponly"]);
}

//  on détruit la session. et redirection vers la page de connectez_vous
header('Location:Connection.php');
exit();
}

function dateDiff($date1, $date2){

$date1  = strtotime($date1);
$date2 = strtotime($date2);
    $diff = abs($date1 - $date2); // abs pour avoir la valeur absolute, ainsi éviter d'avoir une différence négative
    $retour = array();
 
    $tmp = $diff;
    $retour['second'] = $tmp % 60;
 
    $tmp = floor( ($tmp - $retour['second']) /60 );
    $retour['minute'] = $tmp % 60;
    $tmp = floor( ($tmp - $retour['minute'])/60 );
    $retour['hour'] = $tmp % 24;
    $tmp = floor( ($tmp - $retour['hour'])  / 24 );
    $retour['day'] = $tmp; 
    return $retour['day'];
}


function connexionBdd(&$bdd){
try
{
// Changer les paramètres de connexion à votre base de données  ( la ligne 45 )
$username="root";
$password=""; 
$bdd = new PDO('mysql:host=localhost;dbname=congetest',$username,$password,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}catch (Exception $e)
{die('Erreur : ' . $e->getMessage());}
}

function verifySession(){
if(!isset($_SESSION['cin']) or !isset($_SESSION['login']))
{  deconnexion();}}

// function demander($cin,$dateRetour,$dateDepart,$type,$description){

// connexionBdd($bdd);
// $req= $bdd->prepare('SELECT joursrest FROM personnel WHERE cin = :cin');
// $req->execute(array(':cin' => $cin));
// $result=$req->fetch();
// $diffDays=dateDiff($dateRetour,$dateDepart)+1;
// if (($diffDays <= $result['joursrest']) && $diffDays !=0)
// { $dateNow=date('Y-m-d');
//     $etat=0;
// 	$req = $bdd->prepare('INSERT INTO demande(CIN,DateDepart,DateRetour,DateDemande,Etat,type,description) VALUES(:CIN,:DateDepart,:DateRetour,:DateDemande,:Etat,:type,:description)');
//     $req->execute(array(
// ':CIN' => $cin,
// ':DateDepart' => $dateDepart,
// ':DateRetour' => $dateRetour,
// ':DateDemande' =>$dateNow,
// ':Etat' => 0,
// ':type' => $type,
// ':description' => $description));
// $result['joursrest']=$result['joursrest']-$diffDays;
// $req = $bdd->prepare('UPDATE personnel SET joursrest= :newval WHERE cin = :cin');
// $req->execute(array(
// ':newval' => $result['joursrest'],
// ':cin' => $cin));
// $to="wiamaithfid@gmail.com";
// $subject="Demande de congé " ;
// $message="Bonjour, \n Vous avez une nouvelle demande en attente d'approbation! \nPour traiter cette demande, veuillez cliquer sur le lien ci-dessous : \n \n \n \n  http://localhost/Test2_conge/listeDemandes.php \n Ceci est un message automatique, merci de ne pas y répondre.s";
// $message=nl2br($message);
// EnvoyerEmail($to,$subject,$message);
// $message='La demande a bien été envoyée ! ';
// return $message;
// }elseif ($diffDays > $result['joursrest']){
// $message='Vous avez dépassé le nombre de jours permis!';
// return $message;    }
// elseif($diffDays ==0){
// $message='Veuillez remplir les deux champs';
// return $message;    
// }
// }



function demander($cin, $dateRetour, $dateDepart, $type, $description) {
    connexionBdd($bdd);

    // 1. Récupérer les soldes séparés
    $req = $bdd->prepare('SELECT conges_restants_annee_precedente, conges_annee_en_cours FROM personnel WHERE cin = :cin');
    $req->execute(array(':cin' => $cin));
    $result = $req->fetch();

    // 2. Calcul des jours demandés
    $diffDays = dateDiff($dateRetour, $dateDepart) + 1;

    if ($diffDays == 0) {
        return 'Veuillez remplir les deux champs';
    }

    $totalCongesDispo = $result['conges_restants_annee_precedente'] + $result['conges_annee_en_cours'];

    if ($diffDays > $totalCongesDispo) {
        return 'Vous avez dépassé le nombre de jours permis!';
    }

    // 3. Calcul des jours à déduire
    $congesPrecedent = $result['conges_restants_annee_precedente'];
    $congesActuel = $result['conges_annee_en_cours'];

    if ($congesPrecedent >= $diffDays) {
        $congesPrecedent -= $diffDays;
    } else {
        $reste = $diffDays - $congesPrecedent;
        $congesPrecedent = 0;
        $congesActuel -= $reste;
    }

    // 4. Enregistrement de la demande
    $dateNow = date('Y-m-d');
    $etat = 0;

    $req = $bdd->prepare('INSERT INTO demande(CIN, DateDepart, DateRetour, DateDemande, Etat, type, description) VALUES(:CIN, :DateDepart, :DateRetour, :DateDemande, :Etat, :type, :description)');
    $req->execute(array(
        ':CIN' => $cin,
        ':DateDepart' => $dateDepart,
        ':DateRetour' => $dateRetour,
        ':DateDemande' => $dateNow,
        ':Etat' => $etat,
        ':type' => $type,
        ':description' =>$description
    ));

    // 5. Mise à jour des congés
    $req = $bdd->prepare('UPDATE personnel SET conges_restants_annee_precedente = :precedent, conges_annee_en_cours = :actuel WHERE cin = :cin');
    $req->execute(array(
        ':precedent' => $congesPrecedent,
        ':actuel' => $congesActuel,
        ':cin' => $cin
    ));

    // 6. Notification
    $to = "wiamaithfid@gmail.com";
    $subject = "Demande de congé";
    $message = "Une nouvelle demande en attente d'approbation \n Visiter : http://localhost/Test_conge2/listeDemandes.php";
    $message = nl2br($message);
    EnvoyerEmail($to, $subject, $message);

    return 'La demande a bien été envoyée !';
}

function ModifierDemande($id, $type, $description, $dateDepart, $dateRetour) {
    connexionBdd($bdd);
    try {
        $req = $bdd->prepare('UPDATE demande SET type = ?, description = ?, DateDepart = ?, DateRetour = ? WHERE ID = ?');
        $req->execute(array($type, $description, $dateDepart, $dateRetour, $id));
        return true;
    } catch(Exception $e) {
        return false;
    }
}



// Fonctions de requête (correctes)
function DemandesEnCours($cin) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Demandes WHERE CIN = ? AND Etat = 0 ORDER BY DateDemande DESC");
    $stmt->execute([$cin]);
    return $stmt;
}

function HistoriqueDemandes($cin) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Demandes WHERE CIN = ? AND Etat != 0 ORDER BY DateDecision DESC");
    $stmt->execute([$cin]);
    return $stmt;
}

// Fonctions d'affichage CORRIGÉES
// function afficherLigneDemande($demande) {
//     $days = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;
//     $dateDepart = date('d/m/y', strtotime($demande['DateDepart']));
//     $dateRetour = date('d/m/y', strtotime($demande['DateRetour']));
    
//     return <<<HTML
//     <tr>
//         <td>{$demande['ID']}</td>
//         <td class="mobile-hide">{$demande['type']}</td>
//         <td>$dateDepart</td>
//         <td>$dateRetour</td>
//         <td>$days</td>
//         <td>
//             <button class="btn-edit btn-sm" onclick="loadEditData({$demande['ID']}, '{$demande['type']}', '{$demande['DateDepart']}', '{$demande['DateRetour']}', `{$demande['Description'] ?? ''}`)">
//                 <i class="bi bi-pencil-square"></i>
//             </button>
//             <form method="post" action="MesDemandes.php" class="d-inline">
//                 <input type="hidden" name="supprimer" value="{$demande['ID']}">
//                 <button type="submit" class="btn-action btn-sm" onclick="return confirm('Voulez-vous vraiment annuler cette demande ?')">
//                     <i class="bi bi-x-circle"></i>
//                 </button>
//             </form>
//         </td>
//     </tr>
//     HTML;
// }

function afficherLigneHistorique($demande) {
    $statut = $demande['Etat'] == 1 ? 'Acceptée' : 'Refusée';
    $classeStatut = $demande['Etat'] == 1 ? 'status-approved' : 'status-rejected';
    $dateDepart = date('d/m/y', strtotime($demande['DateDepart']));
    $dateRetour = date('d/m/y', strtotime($demande['DateRetour']));
    $dateDecision = date('d/m/y H:i', strtotime($demande['DateDecision']));
    $annee = date('Y', strtotime($demande['DateDemande']));
    
    return <<<HTML
    <tr data-type="{$demande['type']}" data-statut="{$demande['Etat']}" data-annee="$annee">
        <td>{$demande['ID']}</td>
        <td class="mobile-hide">{$demande['type']}</td>
        <td>$dateDepart</td>
        <td>$dateRetour</td>
        <td><span class="status $classeStatut">$statut</span></td>
        <td class="mobile-hide">$dateDecision</td>
    </tr>
    HTML;
}




 function MesDemandes($cin){

connexionBdd($bdd);
$req= $bdd->query('SELECT * FROM demande WHERE CIN=\'' . $cin. '\' ORDER BY ID DESC');
//$req= $bdd->prepare('SELECT * FROM demande WHERE CIN = ? ORDER BY ID DESC');
//$reponse=$req->execute(array($cin));
return $req ;
}
function LesDemandesChefDepartement($departement, $cin_cd) {
    connexionBdd($bdd);
    $req = $bdd->prepare("SELECT d.* 
                         FROM demande d
                         JOIN personnel p ON d.CIN = p.cin
                         WHERE p.depart = ? 
                         AND p.qualite NOT IN ('CD', 'RH', 'DG')
                         AND d.DateDepart > NOW()
                         AND d.CIN != ?
                         ORDER BY d.DateDemande DESC");
    $req->execute([$departement, $cin_cd]);
    return $req;
}

function DemandesEnAttenteChefDepartement($departement, $cin_cd) {
    connexionBdd($bdd);
    $req = $bdd->prepare("SELECT d.* 
                         FROM demande d
                         JOIN personnel p ON d.CIN = p.cin
                         WHERE d.Etat = 0 
                         AND p.depart = ?
                         AND p.qualite NOT IN ('CD', 'RH', 'DG')
                         AND d.CIN != ?
                         ORDER BY d.DateDemande DESC");
    $req->execute([$departement, $cin_cd]);
    return $req;
}

function DemandesAccepteeChefDepartement($departement) {
    connexionBdd($bdd);
    $req = $bdd->prepare("
        SELECT * FROM demande 
        WHERE Etat = 1 
        AND DateDepart > NOW()
        AND CIN IN (
            SELECT CIN FROM personnel WHERE depart = ?
        )
        ORDER BY DateDemande DESC
    ");
    $req->execute([$departement]);
    return $req;
}

function DemandesRefuseeChefDepartement($departement) {
    connexionBdd($bdd);
    $req = $bdd->prepare("
        SELECT * FROM demande 
        WHERE Etat = -1
        AND DateDepart > NOW() 
        AND CIN IN (
            SELECT CIN FROM personnel WHERE depart = ?
        )
        ORDER BY DateDemande DESC
    ");
    $req->execute([$departement]);
    return $req;
}



function DemandesEnAttentePourDG() {
    connexionBdd($bdd);
    $req = $bdd->prepare('SELECT d.*, p.qualite 
                         FROM demande d 
                         JOIN personnel p ON d.CIN = p.cin 
                         WHERE d.Etat = 1 
                         OR (d.Etat = 0 AND p.qualite IN ("CD", "RH", "DG")) 
                         ORDER BY d.ID DESC');
    $req->execute();
    return $req;
}


function DemandesRefuseesParDG() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->prepare('SELECT d.*, p.qualite 
                         FROM demande d 
                         JOIN personnel p ON d.CIN = p.cin 
                         WHERE d.Etat = -2 
                         AND d.DateDepart > NOW()
                         ORDER BY d.ID DESC');
    $req->execute();
    return $req;
}


function DemandesPourValidationDG() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->prepare('SELECT d.*, p.qualite 
                          FROM demande d 
                          JOIN personnel p ON d.CIN = p.cin 
                          WHERE d.Etat IN (2, -2, 1) AND d.DateDepart > NOW()
                                OR (d.Etat = 0 AND p.qualite IN ("CD", "DG", "RH") AND d.DateDepart > NOW())
                          ORDER BY d.ID DESC');
    $req->execute();
    return $req;
}



function getChefDeDepartement($service) {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->prepare('SELECT nom, prenom, qualite FROM personnel WHERE service = ? AND qualite = "CD" LIMIT 1');
    $req->execute([$service]);
    return $req->fetch();
}

function DemandesAccepteesParChefss() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->prepare('SELECT d.* FROM demande d 
                         JOIN personnel p ON d.CIN = p.cin 
                         WHERE d.Etat = 1 AND p.qualite IN ("CD", "DG")');
    $req->execute();
    return $req;
}
function EtatDemande($id, $val) {
    connexionBdd($bdd);

    // Ancien état de la demande
    $ancienEtat = InfosDemandes($id); // Devrait retourner -1, 0 ou 1 ou 2

    // Mise à jour de l'état de la demande
    $req = $bdd->prepare('UPDATE demande SET Etat = :petat WHERE ID = :pid');
    $req->execute(array(
        ':petat' => $val,
        ':pid' => $id
    ));

    // Récupération des dates pour calculer la durée du congé
    $req = $bdd->query('SELECT DateDepart, DateRetour FROM demande WHERE ID = ' . intval($id));
    $demande = $req->fetch();
    $diffDays = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;

    // CIN de l'employé
    $cin = CinFromID($id);

    // Récupération des soldes de congés
    $req = $bdd->prepare('SELECT conges_restants_annee_precedente, conges_annee_en_cours FROM personnel WHERE cin = :cin');
    $req->execute(array(':cin' => $cin));
    $conges = $req->fetch();

    $precedent = $conges['conges_restants_annee_precedente'];
    $actuel = $conges['conges_annee_en_cours'];

    // 1. Refus de la demande → rendre les jours
    if ($val == -1) {
        if ($actuel + $diffDays <= 30) {
            $actuel += $diffDays;
        } else {
            $reste = ($actuel + $diffDays) - 30;
            $actuel = 30;
            $precedent += $reste;
        }
    }

    // 2. Acceptation après refus → retirer les jours à nouveau
    elseif (($val == 1 || $val == 0) && $ancienEtat == -1) {
        if ($precedent >= $diffDays) {
            $precedent -= $diffDays;
        } else {
            $reste = $diffDays - $precedent;
            $precedent = 0;
            $actuel -= $reste;
        }
    }

    // 3. Acceptation par DG → retirer les jours si ce n’était pas déjà fait
    elseif ($val == 2 && ($ancienEtat == 0 || $ancienEtat == 1)) {
        if ($precedent >= $diffDays) {
            $precedent -= $diffDays;
        } else {
            $reste = $diffDays - $precedent;
            $precedent = 0;
            $actuel -= $reste;
        }
    }

    // 4. Mise à jour des soldes
    $req = $bdd->prepare('UPDATE personnel SET 
        conges_restants_annee_precedente = :precedent,
        conges_annee_en_cours = :actuel
        WHERE cin = :cin
    ');
    $req->execute(array(
        ':precedent' => $precedent,
        ':actuel' => $actuel,
        ':cin' => $cin
    ));
}

function SupprimerDemande($id) {
    connexionBdd($bdd);

    // 1. Récupération des dates de congé
    $req = $bdd->prepare('SELECT DateDepart, DateRetour FROM demande WHERE ID = :id');
    $req->execute([':id' => $id]);
    $demande = $req->fetch();

    if (!$demande) {
        return "Demande introuvable.";
    }

    $diffDays = dateDiff($demande['DateRetour'], $demande['DateDepart']) + 1;

    // 2. CIN de l'employé
    $cin = CinFromID($id);

    // 3. Récupération des soldes actuels
    $req = $bdd->prepare('SELECT conges_restants_annee_precedente, conges_annee_en_cours FROM personnel WHERE cin = :cin');
    $req->execute([':cin' => $cin]);
    $conges = $req->fetch();

    $precedent = $conges['conges_restants_annee_precedente'];
    $actuel = $conges['conges_annee_en_cours'];

    // 4. Rendre les jours supprimés : d'abord à l'année en cours
    if ($actuel + $diffDays <= 30) {
        $actuel += $diffDays;
    } else {
        $reste = ($actuel + $diffDays) - 30;
        $actuel = 22;
        $precedent += $reste;
    }

    // 5. Mise à jour des soldes dans la base
    $req = $bdd->prepare('UPDATE personnel SET 
        conges_restants_annee_precedente = :precedent,
        conges_annee_en_cours = :actuel
        WHERE cin = :cin
    ');
    $req->execute([
        ':precedent' => $precedent,
        ':actuel' => $actuel,
        ':cin' => $cin
    ]);

    // 6. Suppression de la demande
    $req = $bdd->prepare('DELETE FROM demande WHERE ID = :pid');
    $req->execute([':pid' => $id]);
}


// function verifyAdmin(){
// if(isset($_SESSION['qualite'])){
// 	if($_SESSION['qualite']=='SAL') 
//        { return false;}
//    else{return true ;}}
// else{ 
// return false;}
// }

function TousPersonnels(){

connexionBdd($bdd);
$req=$bdd->query('SELECT * FROM personnel ORDER BY nom ASC');
return $req; 
}
function InfosPersonnel($id){

connexionBdd($bdd);
$req=$bdd->query('SELECT * FROM personnel WHERE cin=\'' . strtoupper(htmlspecialchars(trim($id))). '\' ');
$rep=$req->fetch();
return $rep; 
}

function InfosPersonnel2($id){

connexionBdd($bdd);
$req=$bdd->query('SELECT * FROM personnel WHERE cin=\'' .strtoupper(htmlspecialchars(trim($id))). '\'');
return $req;    
}

function InfosPersonnelFromDiv($div){

connexionBdd($bdd);
$req=$bdd->query('SELECT * FROM personnel WHERE depart=\'' .$div. '\'');
return $req;   
}

function InfosPersonnelFromGrade($grade){

connexionBdd($bdd);
$req=$bdd->query('SELECT * FROM personnel WHERE service=\'' .$grade. '\'');
return $req;   
}
function InfosPersonnelFromAdmin($admin){

connexionBdd($bdd);
$req=$bdd->query('SELECT * FROM personnel WHERE qualite=\'' .htmlspecialchars(trim($admin)). '\'');
return $req;   
}
function SupprimerPersonnel($id){

connexionBdd($bdd);
$req = $bdd->prepare('DELETE FROM personnel WHERE cin = :pcin');
$req->execute(array(':pcin' => $id));
}

function CinFromID($id){

connexionBdd($bdd);
$req=$bdd->query('SELECT CIN FROM demande WHERE ID=\'' . $id. '\' ');
$rep=$req->fetch();
return $rep['CIN'];
}

function getCongesRestantsAnneePrecedente($cin) {
    connexionBdd($bdd);
    $req = $bdd->prepare("SELECT conges_restants_annee_precedente FROM personnel WHERE cin = ?");
    $req->execute([$cin]);
    return $req->fetchColumn() ?? 0;
}

function getCongesAnneeEnCours($cin) {
    connexionBdd($bdd);
    $req = $bdd->prepare("SELECT conges_annee_en_cours FROM personnel WHERE cin = ?");
    $req->execute([$cin]);
    return $req->fetchColumn() ?? 0;
}

function DemandesEnAttente(){

connexionBdd($bdd);
$req= $bdd->query('SELECT * FROM demande WHERE Etat=0 ORDER BY ID DESC');
return $req ;
}

function DemandesAcceptee()
{

connexionBdd($bdd); 
$req= $bdd->query('SELECT * FROM demande WHERE Etat=1 ORDER BY ID DESC ');
return $req ;
}

function DemandesRefusee()
{

connexionBdd($bdd);
$req= $bdd->query('SELECT * FROM demande WHERE Etat= -1 ORDER BY ID DESC');
return $req ;

}

function notificationsCD() {
    $cin = $_SESSION['cin'];
    $infosChef = InfosPersonnel($cin);
    
    if (!isset($infosChef['depart'])) {
        return 0;
    }

    $departementChef = $infosChef['depart'];
   
    connexionBdd($bdd);

    $sql = "SELECT COUNT(*) AS NbenAttente 
            FROM demande d
            INNER JOIN personnel p ON d.CIN = p.cin
            WHERE d.Etat = 0 
            AND p.depart = :departement
            AND p.qualite NOT IN ('CD', 'RH', 'DG')
            AND d.CIN != :cin_chef";

    $req = $bdd->prepare($sql);
    $req->execute([
        ':departement' => $departementChef,
        ':cin_chef' => $cin
    ]);

    $rep = $req->fetch();
    return $rep['NbenAttente'];
}

function notificationsDG() {
    connexionBdd($bdd);

    $sql = "SELECT COUNT(*) AS NbenAttente 
            FROM demande d
            JOIN personnel p ON d.CIN = p.cin
            WHERE d.Etat = 1 
               OR (d.Etat = 0 AND p.qualite IN ('CD', 'RH', 'DG'))";

    $req = $bdd->prepare($sql);
    $req->execute();
    $rep = $req->fetch();
    return $rep['NbenAttente'];
}





// function notif(){
// connexionBdd($bdd);
// $req= $bdd->query('SELECT joursrest FROM personnel' );
// $rep=$req->fetch();
// return $rep['joursrest'];
// }

function DemandesAccepteesParDG() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->prepare('SELECT d.*, p.nom, p.prenom, p.qualite, p.service 
                         FROM demande d 
                         JOIN personnel p ON d.CIN = p.cin 
                         WHERE d.Etat = 2
                         AND d.DateDepart > NOW()
                         ORDER BY d.ID DESC');
    $req->execute();
    return $req;
}

function InfosDemandes($id){

connexionBdd($bdd);
$req=$bdd->query('SELECT Etat FROM demande WHERE ID=\'' . $id. '\'');
$rep=$req->fetch();
return $rep['Etat'];
}
function GetDivision(){

connexionBdd($bdd);
$req=$bdd->query('SELECT  distinct depart FROM personnel ');
return $req;
}

function GetGrade(){

connexionBdd($bdd);
$req=$bdd->query('SELECT  distinct service FROM personnel ');
return $req;
}

function getCongesParDepartement() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->query('SELECT p.depart, COUNT(d.ID) as nb_conges 
                       FROM demande d
                       JOIN personnel p ON d.CIN = p.cin
                       WHERE d.Etat = 2
                       GROUP BY p.depart');
    return $req->fetchAll();
}

function getCongesParType() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->query('SELECT Type, COUNT(*) as nb 
                        FROM demande 
                        WHERE Etat = 2
                        GROUP BY Type');
    return $req->fetchAll();
}

function getCongesEnCours() {
    $bdd = new PDO('mysql:host=localhost;dbname=congetest','root','');
    $req = $bdd->query("SELECT p.nom, p.prenom, p.depart, d.DateDepart, d.DateRetour 
                       FROM demande d
                       JOIN personnel p ON d.CIN = p.cin
                       WHERE d.Etat = 2
                       AND CURDATE() BETWEEN d.DateDepart AND d.DateRetour
                       ORDER BY d.DateDepart");
    return $req->fetchAll();
}
function InfosPersonnelFromDivAndCIN($depart, $cin) {
    global $connexion;
    $req = $connexion->prepare("SELECT * FROM personnel WHERE depart = :depart AND cin = :cin");
    $req->execute([
        'depart' => $depart,
        'cin' => $cin
    ]);
    return $req;
}


function verifyDG() {
    if ($_SESSION['qualite'] !== 'DG') { // Adaptez selon votre système de rôles
        header('Location: index.php');
        exit();
    }
}


?>

