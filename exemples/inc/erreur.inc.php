<?php

if (!isset($message))
{
  $message = "Non precise";
}

if (!isset($erreur) || !ctype_digit($erreur))
{
  $erreur = 500;
  $message .= " - Code d'erreur non precisé";
}

header("Content-Type: text/html; charset=utf-8", true, $erreur);
echo "erreur $erreur : $message";
exit ();