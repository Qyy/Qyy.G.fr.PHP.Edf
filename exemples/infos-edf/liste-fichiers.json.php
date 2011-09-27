<?php

require_once('../../Edf.class.PHP');

$retour = Edf::GetListeFichiersJson();

if ($retour === false)
{
  $erreur = 404;
  $message =
    'Aucun fichier EDF trouvé dans le répertoire "'
    .Qyy_G_en_Utils::RelativePathFromPosixToEnv(
      Edf::CHEMIN_POSIX_REPERTOIRE_FICHIERS_IMPORT)
    .'".';
  include('../inc/erreur.inc.php');
}

header('Content-type: application/json; charset=utf-8');
echo $retour;