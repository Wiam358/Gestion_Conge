<?php
session_start();
include_once('config.php');
verifySession(); 
$message = false;
// Récupérer les soldes de congés de l'employé
$solde_annee_precedente = getCongesRestantsAnneePrecedente($_SESSION['cin']);
$solde_annee_courante = getCongesAnneeEnCours($_SESSION['cin']);

// Calculer le total disponible
$total_conges_disponibles = $solde_annee_precedente + $solde_annee_courante;

if (isset($_POST['datedepart']) && isset($_POST['dateretour'])) {
    try {
        // Récupérer les soldes de congés
        $solde_annee_precedente = getCongesRestantsAnneePrecedente($_SESSION['cin']);
        $solde_annee_courante = getCongesAnneeEnCours($_SESSION['cin']);
        $total_conges_disponibles = $solde_annee_precedente + $solde_annee_courante;

        $dateCourante = new DateTime();
        $dateDepart = new DateTime($_POST['datedepart']);
        $dateRetour = new DateTime($_POST['dateretour']);
        $joursDemandes = $dateDepart->diff($dateRetour)->days + 1;
        
        // Validation des dates et des soldes
        if ($dateDepart <= $dateCourante) {
            $message = "Erreur : La date début doit être dans le futur";
        } 
        elseif ($dateRetour <= $dateCourante) {
            $message = "Erreur : La date fin doit être dans le futur";
        }
        elseif ($dateDepart >= $dateRetour) {
            $message = "Erreur : La date début doit être inférieure à la date de fin";
        }
        elseif ($joursDemandes > $total_conges_disponibles) {
            $message = "Erreur : Solde insuffisant (Demande: $joursDemandes jours | Disponible: $total_conges_disponibles jours)";
        }
        else {
            // Vérifier si on utilise le solde de l'année précédente
            if ($solde_annee_precedente > 0) {
                $jours_utilises_annee_precedente = min($joursDemandes, $solde_annee_precedente);
                $jours_utilises_annee_courante = $joursDemandes - $jours_utilises_annee_precedente;
            } else {
                $jours_utilises_annee_precedente = 0;
                $jours_utilises_annee_courante = $joursDemandes;
            }
            
            $message = demander(
                $_SESSION['cin'], 
                $_POST['dateretour'], 
                $_POST['datedepart'], 
                $_POST['type'], 
                $_POST['description'],
                $jours_utilises_annee_precedente,
                $jours_utilises_annee_courante
            );
            header('Location: MesDemandes.php');
        }
    } catch (Exception $e) {
        $message = "Erreur : Format de date invalide - " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demander un congé | CCISCS</title>
    <link rel="icon" type="image/png" href="photos/cciscs.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #3CB371;
            --primary-dark: #2E8B57;
            --primary-light: #8FBC8F;
            --primary-lighter: #98FB98;
            --bg-color: #F8F9FA;
            --card-bg: #FFFFFF;
            --text-color: #495057;
            --text-light: #6C757D;
        }
        
        body {
            margin-left: 250px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .conge-card {
            max-width: 650px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(60, 179, 113, 0.15);
            border: none;
            transition: transform 0.3s ease;
        }
        
        .conge-card:hover {
            transform: translateY(-5px);
        }
        
        .card-title {
            color: var(--primary-dark);
            font-weight: 700;
            position: relative;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(60, 179, 113, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 139, 87, 0.3);
        }
        
        .alert-message {
            border-radius: 8px;
            border-left: 4px solid var(--primary-dark);
            padding: 15px;
            margin-bottom: 25px;
        }
        
        .date-inputs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .date-inputs {
                flex-direction: column;
                gap: 15px;
            }
            
            .conge-card {
                padding: 1.5rem;
                margin: 1rem auto;
            }
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                
            }
        }
    </style>
</head>    
<body>
    <?php include_once('header.php');?>
    
    <div class="container">
        <div class="conge-card animate__animated animate__fadeIn">
            <h2 class="card-title">
                <i class="bi bi-send-plus" style="font-size: 1.5rem;"></i>
                Nouvelle demande de congé
            </h2>
            <br>
            <?php if($message): ?>
                <div class="alert-message alert alert-<?php echo strpos($message, 'Erreur') === false ? 'success' : 'danger'; ?>">
                    <i class="bi <?php echo strpos($message, 'Erreur') === false ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="demander.php">
                <div class="date-inputs">
                    <div class="w-100">
                        <label for="datedepart" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="datedepart" name="datedepart" required>
                    </div>
                    
                    <div class="w-100">
                        <label for="dateretour" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="dateretour" name="dateretour" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="type" class="form-label">Type de congé</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="" selected disabled>-- Sélectionnez un type --</option>
                        <option value="Annuel">Annuel</option>
                        <option value="Maladie">Maladie</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="form-label">Description (optionnel)</label>
                    <textarea class="form-control" id="description" name="description" placeholder="Décrivez brièvement la raison de votre demande..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-send-fill"></i> Envoyer la demande
                </button>
            </form>
        </div>
    </div>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const dateDepart = document.getElementById('datedepart');
    const dateRetour = document.getElementById('dateretour');
    
    dateDepart.min = today;
    dateRetour.min = today;
    
    // Calcul automatique de la durée quand les dates changent
    [dateDepart, dateRetour].forEach(el => {
        el.addEventListener('change', function() {
            if(dateDepart.value && dateRetour.value) {
                const diffTime = Math.abs(new Date(dateRetour.value) - new Date(dateDepart.value));
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                // Afficher un avertissement si la durée dépasse le solde
                if(diffDays > <?= $total_conges_disponibles ?>) {
                    alert(`Attention: Vous demandez ${diffDays} jours alors que vous n'avez que <?= $total_conges_disponibles ?> jours disponibles.`);
                }
            }
        });
    });
});

function isWeekend(dateStr) {
    const date = new Date(dateStr);
    const day = date.getDay(); // 0 = Sunday, 6 = Saturday
    return day === 0 || day === 6;
}

document.getElementById('datedepart').addEventListener('change', function () {
    if (isWeekend(this.value)) {
        Swal.fire({
            icon: 'warning',
            title: 'Jour non autorisé',
            text: 'Vous ne pouvez pas sélectionner un samedi ou un dimanche comme date de début.',
        });
        this.value = '';
    }
});

document.getElementById('dateretour').addEventListener('change', function () {
    if (isWeekend(this.value)) {
        Swal.fire({
            icon: 'warning',
            title: 'Jour non autorisé',
            text: 'Vous ne pouvez pas sélectionner un samedi ou un dimanche comme date de fin.',
        });
        this.value = '';
    }
});

    </script>
</body>
</html>