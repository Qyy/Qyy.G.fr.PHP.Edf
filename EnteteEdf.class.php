<?php

class EnteteEdf
{
  private $traitement;
  
  private $versionFormatDonnees;             // 8 ascii (0)
  private $patientId;                        // 80 ascii
  private $enregistrementId;                 // 80 ascii
  private $dateDebutEnregistrement;          // 8 ascii (dd.mm.yy)
  private $heureDebutEnregistrement;         // 8 ascii (hh.mm.ss)
  private $nombreOctetsEnteteEnregistrement; // 8 ascii
  // =>                                      // 44 ascii reservés
  private $nombreEnregistrements;            // 8 ascii (-1 si inconnu)
  private $dureeEnregistrement;              // 8 ascii (en secondes)
  private $nombreSignaux;                    // 4 ascii (ns)
  
  private $dateTimeDebutEnregistrement; // = new DateTime();
  
  function __construct ($traitement)
  {
    $this->traitement = $traitement;
    
    $this->SetEnteteEdf();
    
    $this->SetDateTimeDebutEnregistrement();
  }
  
  private function SetEnteteEdf ()
  { 
    $this->versionFormatDonnees =
      trim(fread($this->traitement, 8));
      
    $this->patientId =
      trim(fread($this->traitement, 80));
      
    $this->enregistrementId =
      trim(fread($this->traitement, 80));
      
    $this->dateDebutEnregistrement =
      trim(fread($this->traitement, 8));
      
    $this->heureDebutEnregistrement =
      trim(fread($this->traitement, 8));
      
    $this->nombreOctetsEnteteEnregistrement =
      trim(fread($this->traitement, 8));

    fread($this->traitement, 44);
    
    $this->nombreEnregistrements =
      trim(fread($this->traitement, 8));
      
    $this->dureeEnregistrement =
      trim(fread($this->traitement, 8));
      
    $this->nombreSignaux =
      trim(fread($this->traitement, 4));
  }
  
  private function SetDateTimeDebutEnregistrement ()
  {
    $this->dateTimeDebutEnregistrement =
      DateTime::createFromFormat(
        'd.m.y H.i.s',
        $this->GetDateDebutEnregistrement()
        .' '
        .$this->GetHeureDebutEnregistrement());
  }
  
  public function GetVersionFormatDonnees ()
  {
    return $this->versionFormatDonnees;
  }
  
  public function GetPatientId ()
  {
    return $this->patientId;
  }
  
  public function GetEnregistrementId ()
  {
    return $this->enregistrementId;
  }
  
  public function GetDateDebutEnregistrement ()
  {
    return $this->dateDebutEnregistrement;
  }
  
  public function GetHeureDebutEnregistrement ()
  {
    return $this->heureDebutEnregistrement;
  }
  
  public function GetNombreOctetsEnteteEnregistrement ()
  {
    return $this->nombreOctetsEnteteEnregistrement;
  }
  
  public function GetNombreEnregistrements ()
  {
    return $this->nombreEnregistrements;
  }
  
  public function GetDureeEnregistrement ()
  {
    return $this->dureeEnregistrement;
  }
  
  public function GetNombreSignaux ()
  {
    return $this->nombreSignaux;
  }
  
  public function GetCloneDateTimeDebutEnregistrement ()
  {
    return clone $this->dateTimeDebutEnregistrement;
  }
  
  public function GetInfosEnteteEdf (
    $formatRetour = null,
    $formatDate = 'd/m/Y H:i:s')
  {
    $retour = array(
      'dateTimeDebutEnregistrement'      =>
        $this->GetCloneDateTimeDebutEnregistrement(),
      'dateDebutEnregistrement'          =>
        $this->GetDateDebutEnregistrement(),
      'dureeEnregistrement'              =>
        $this->GetDureeEnregistrement(),
      'enregistrementId'                 =>
        $this->GetEnregistrementId(),
      'heureDebutEnregistrement'         =>
        $this->GetHeureDebutEnregistrement(),
      'nombreEnregistrements'            =>
        $this->GetNombreEnregistrements(),
      'nombreOctetsEnteteEnregistrement' =>
        $this->GetNombreOctetsEnteteEnregistrement(),
      'nombreSignaux'                    =>
        $this->GetNombreSignaux(),
      'patientId'                        =>
        $this->GetPatientId(),
      'versionFormatDonnees'             =>
        $this->GetVersionFormatDonnees()
    );

    if ($formatRetour === Edf::FORMAT_RETOUR_PHP_SERIAL)
    {
      $retour = serialize($retour);
    }
    else if ($formatRetour === EDF::FORMAT_RETOUR_JSON)
    {
      $retour['dateTimeDebutEnregistrement'] =
        $retour['dateTimeDebutEnregistrement']->format(
          $formatDate);
        
      $retour = json_encode($retour);
    }
    else if (
      !is_null($formatRetour)
      && $formatRetour !== Edf::FORMAT_RETOUR_PHP)
    {
      throw new Exception('Format de retour non supporté.', 400);
    }
    
    return $retour;
  }
}