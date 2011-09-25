<?php

class EnteteSignaux
{
  private $traitement;
  
  private $tailleEnteteSignaux;

  private $nombreSignaux;                            // 4 ascii (ns)
  private $nomsSignaux = array();                    // ns * 16 ascii
  private $typesTransducteurs = array();             // ns * 80 ascii
  private $unitesAnalogiques = array();              // ns * 8  ascii
  private $valeursAnalogiquesMin = array();          // ns * 8  ascii
  private $valeursAnalogiquesMax = array();          // ns * 8  ascii
  private $valeursNumeriquesMin = array();           // ns * 8  ascii
  private $valeursNumeriquesMax = array();           // ns * 8  ascii
  private $prefiltrages = array();                   // ns * 80 ascii
  private $nombresPointsParEnregistrement = array(); // ns * 8  ascii
  // =>                                              // ns * 32 ascii reservés
  
  function __construct (&$traitement, $nombreSignaux)
  {
    $this->traitement = $traitement;
    $this->nombreSignaux = $nombreSignaux;
    $this->SetTailleEnteteSignaux();
    $this->SetEntetSignaux();
  }

  private function SetTailleEnteteSignaux ()
  {
    $this->tailleEnteteSignaux  = $this->nombreSignaux * 16;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 80;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 8;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 8;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 8;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 8;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 8;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 80;
    $this->tailleEnteteSignaux += $this->nombreSignaux * 8;

    $this->tailleEnteteSignaux += $this->nombreSignaux * 32;
  }

  private function SetEntetSignaux ()
  {
    $this->SetTableau ('nomsSignaux',                    16);
    $this->SetTableau ('typesTransducteurs',             80);
    $this->SetTableau ('unitesAnalogiques',              8);
    $this->SetTableau ('valeursAnalogiquesMin',          8);
    $this->SetTableau ('valeursAnalogiquesMax',          8);
    $this->SetTableau ('valeursNumeriquesMin',           8);
    $this->SetTableau ('valeursNumeriquesMax',           8);
    $this->SetTableau ('prefiltrages',                   80);
    $this->SetTableau ('nombresPointsParEnregistrement', 8);
    fread($this->traitement, 32 * $this->nombreSignaux);
  }

  private function SetTableau ($nomTableau, $nombreOctets)
  {
    for ($i = 0; $i < $this->nombreSignaux; $i++)
    {
      $this->{$nomTableau}[$i] =
        trim(fread($this->traitement, $nombreOctets));
    }
  }
  
  public function GetTailleEnteteSignaux ()
  {
    return $this->taileEnteteSignaux;
  }

  public function GetNombreSignaux ()
  {
    return $this->nombreSignaux;
  }
  
  public function GetNomsSignaux ($i = null)
  {
    if (is_null($i))
    {
      return $this->nomsSignaux;
    }
    else
    {
      return $this->nomsSignaux[$i];
    }
  }
  
  public function GetTypesTransducteurs ($i = null)
  {
    if (is_null($i))
    {
      return $this->typesTransducteurs;
    }
    else
    {
      return $this->typesTransducteurs[$i];
    }
  }
  
  public function GetUnitesAnalogiques ($i = null)
  {
    if (is_null($i))
    {
      return $this->unitesAnalogiques;
    }
    else
    {
      return $this->unitesAnalogiques[$i];
    }
  }
  
  public function GetValeursAnalogiquesMin ($i = null)
  {
    if (is_null($i))
    {
      return $this->valeursAnalogiquesMin;
    }
    else
    {
      return $this->valeursAnalogiquesMin[$i];
    }
  }
  
  public function GetValeursAnalogiquesMax ($i = null)
  {
    if (is_null($i))
    {
      return $this->valeursAnalogiquesMax;
    }
    else
    {
      return $this->valeursAnalogiquesMax[$i];
    }
  }
  
  public function GetValeursNumeriquesMin ($i = null)
  {
    if (is_null($i))
    {
      return $this->valeursNumeriquesMin;
    }
    else
    {
      return $this->valeursNumeriquesMin[$i];
    }
  }
  
  public function GetValeursNumeriquesMax ($i = null)
  {
    if (is_null($i))
    {
      return $this->valeursNumeriquesMax;
    }
    else
    {
      return $this->valeursNumeriquesMax[$i];
    }
  }
  
  public function GetPrefiltrages ($i = null)
  {
    if (is_null($i))
    {
      return $this->prefiltrages;
    }
    else
    {
      return $this->prefiltrages[$i];
    }
  }
  
  public function GetNombresPointsParEnregistrement ($i = null)
  {
    if (is_null($i))
    {
      return $this->nombresPointsParEnregistrement;
    }
    else
    {
      return $this->nombresPointsParEnregistrement[$i];
    }
  }
}