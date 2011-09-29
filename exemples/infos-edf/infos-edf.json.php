<?php

require_once('../../Edf.class.PHP');

if (!isset($_REQUEST['fichier'])
  || empty($_REQUEST['fichier']))
{
  $erreur = 400;
  $message = 'Le parametre "fichier" est malforme';
  include('../inc/erreur.inc.php');
}

try
{
  $nomFichierPlusExt =
    $_REQUEST['fichier'].Edf::GetExtentionFichier($_REQUEST['fichier']);
}
catch (Exception $exGetExtentionFichier)
{
  $erreur = $exGetExtentionFichier->getCode();
  $message =
    'Exception : '
    .$exGetExtentionFichier->getMessage();
  include('../inc/erreur.inc.php');
}

try
{
  $edf = new Edf($_REQUEST['fichier']);
}
catch (Exception $exNewEdf)
{
  $erreur = $exNewEdf->getCode();
  $message =
    'Exception : '
    .$exNewEdf->getMessage();
  include('../inc/erreur.inc.php');
}

try
{
  $retour = $edf->GetInfos();
}
catch (Exception $exGetInfos)
{
  $erreur = $exGetInfos->getCode();
  $message =
    'Exception : '
    .$exGetInfos->getMessage();
  include('../inc/erreur.inc.php');
}

// TODO: retour JSON !!!
header('Content-type: text/plain; charset=utf-8');
var_dump($retour);

//header('Content-type: application/json; charset=utf-8');