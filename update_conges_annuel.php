<?php
connexionBdd($bdd);

// Réinitialiser les congés pour la nouvelle année
$req = $bdd->prepare("
    UPDATE personnel 
    SET 
        conges_restants_annee_precedente = LEAST(conges_annee_en_cours, 22), // Maximum 22 jours reportables
        conges_annee_en_cours = 22
");
$req->execute();
?>