<?php

require_once('../../Edf.class.PHP');

if (!isset($_REQUEST['fichier'])
  || empty($_REQUEST['fichier']))
{
  $erreur = 400;
  $message = 'Le parametre "fichier" est malforme';
  include('../inc/erreur.inc.php');
}

// TODO: `try` / `catch` quans le TODO exception de `GetExtentionFichier`
// sera fait.
$nomFichierPlusExt =
  $_REQUEST['fichier'].Edf::GetExtentionFichier($_REQUEST['fichier']);
  //$_REQUEST['fichier'].'.edf';

//var_dump($nomFichierPlusExt);

if (!Edf::TesteExistenceFichier($nomFichierPlusExt))
{
  $erreur = 404;
  $message =
    'Ni le fichier '.$_REQUEST['fichier']
    .'.edf ni le fichier '.$_REQUEST['fichier']
    .".EDF n'existent.";
  include('../inc/erreur.inc.php');
}

$edf = new Edf($_REQUEST['fichier']);

// TODO: retour JSON !!!
header('Content-type: text/plain; charset=utf-8');
var_dump($edf->GetInfos());

//header('Content-type: application/json; charset=utf-8');