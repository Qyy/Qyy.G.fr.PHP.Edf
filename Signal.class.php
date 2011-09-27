<?php

class Signal
{
  private $traitementFichierTemporaire;
  
  private $nom;
  private $typeTransducteur;
  private $uniteAnalogique;
  private $valeurAnalogiqueMin;
  private $valeurAnalogiqueMax;
  private $valeurNumeriqueMin;
  private $valeurNumeriqueMax;
  private $prefiltrage;
  private $nombrePointsParEnregistrement;
  private $enteteEdf;
  private $nomFichierTemporaire;
  
  private $frequenceEchantillonnage;
  
  private $octetsParSeconde;
  
  private $tailleFichierSignal;
  private $nombrePointsSignal;
  private $nombreSecondesSignal;

  private $fichiersCacheJson = array();

  function __construct (
    $nom,
    $typeTransducteur,
    $uniteAnalogique,
    $valeurAnalogiqueMin,
    $valeurAnalogiqueMax,
    $valeurNumeriqueMin,
    $valeurNumeriqueMax,
    $prefiltrage,
    $nombrePointsParEnregistrement,
    &$enteteEdf,
    $nomFichierTemporaire)
  {
    $this->nom                  = $nom;
    $this->typeTransducteur     = $typeTransducteur;
    $this->uniteAnalogique      = $uniteAnalogique;
    $this->valeurAnalogiqueMin  = $valeurAnalogiqueMin;
    $this->valeurAnalogiqueMax  = $valeurAnalogiqueMax;
    $this->valeurNumeriqueMin   = $valeurNumeriqueMin;
    $this->valeurNumeriqueMax   = $valeurNumeriqueMax;
    $this->prefiltrage          = $prefiltrage;
    $this->nombrePointsParEnregistrement =
      $nombrePointsParEnregistrement;
    $this->enteteEdf            = $enteteEdf;
    $this->nomFichierTemporaire = $nomFichierTemporaire;
    
    $frequenceEchantillonnage = 
      $nombrePointsParEnregistrement / $enteteEdf->GetDureeEnregistrement();
      
    $this->frequenceEchantillonnage = $frequenceEchantillonnage;
    
    $this->octetsParSeconde = 
      $frequenceEchantillonnage * 2;
    
    //$this->SetTraitementFichierTemporaire('w');
  }
  
  function __destruct ()
  {
    fclose($this->GetTraitementFichierTemporaire());
  }
  
  private function InitInfosFichier ()
  {
    // http://fr.php.net/manual/fr/function.filesize.php
    // Comme le type entier de PHP est signé et que de nombreuses
    // plates-formes utilisent des entiers de 32 bits, filesize() peut 
    // retourner des résultats étranges pour les fichiers de taille
    // supérieure à 2 Go. Pour les fichiers entre 2 et 4 Go, cela peut être
    // contourné avec sprintf("%u", filesize($file)).
    $tailleFichierSignal  = 
      sprintf("%u", filesize($this->GetCheminFichierTemporaire()));
    $nombrePointsSignal   = $tailleFichierSignal / 2;
    $nombreSecondesSignal = 
      $nombrePointsSignal / $this->GetFrequenceEchantillonnage();
      
    $this->tailleFichierSignal  = $tailleFichierSignal;
    $this->nombrePointsSignal   = $nombrePointsSignal;
    $this->nombreSecondesSignal = $nombreSecondesSignal;
    
  }

  public function GetSignalVisualizationDataTable (
    $debutSeconde = 0,
    $dureeSeconde = -1,
    $analogique = false)
  {
    $finSeconde = Edf::ObtienFinSeconde(
      $this->GetNombreSecondesSignal(),
      $debutSeconde,
      $dureeSeconde);
    
    $clefCache =
      $debutSeconde.'.'.$finSeconde.'.'.intval($analogique).'.gvdt';
    
    $nomFichierCache =
      $this->GetNomFichierTemporaire()
      .'.'
      .$clefCache;
    
    $retour = 'dtc'; // Si on se retrouve avec ça, y'a un problème…
    
    if (file_exists($this->GetCheminFichierCache($nomFichierCache)))
    {
      $retour =
        file_get_contents($this->GetCheminFichierCache($nomFichierCache));
    }
    else
    {    
      $this->SetTraitementFichierTemporaire('r');
    
      $debutLecture = $debutSeconde * $this->GetOctetsParSeconde();
    
      if ($debutLecture > 0)
      {
        fread(
          $this->GetTraitementFichierTemporaire(),
          $debutSeconde * $this->GetOctetsParSeconde());
      }
    
      $dataTable = new GgVisuDataTable ();
      
      $dataTable->AddColumn(
        GgVisuDataTable::TYPE_STRING,
        'Temps',
        'temps');
        
      $dataTable->AddColumn(
        GgVisuDataTable::TYPE_NUMBER,
        $this->GetNom(),
        'courbe');
      
      for ($i = $debutSeconde; $i <= $finSeconde; $i++) {
    
        $compteur = 0;
        
        if ($i > 0)
        {
          $indexTemporelDateTime =
            Edf::IndexTemporelVersDateTime(
              $i,
              $this->GetEnteteEdf()->GetCloneDateTimeDebutEnregistrement());
        }
        else
        {
          $indexTemporelDateTime =
            $this->GetEnteteEdf()->GetCloneDateTimeDebutEnregistrement();
        }
        
        $labelDateTime = $indexTemporelDateTime->format('H:i:s');

        while (
          !feof($this->GetTraitementFichierTemporaire())
          && $compteur < $this->GetFrequenceEchantillonnage())
        {
          $point = unpack(
            's',
            fread($this->GetTraitementFichierTemporaire(), 2));
          $point = $point[1];
      
          if ($analogique)
          {
            $point = Edf::NumeriqueVersAnalogique (
              $point,
              $this->GetValeurAnalogiqueMin(),
              $this->GetValeurAnalogiqueMax(),
              $this->GetValeurNumeriqueMin(),
              $this->GetValeurNumeriqueMax());
          }

          $temps = $i
            + Edf::NumeriqueVersAnalogique (
                $compteur,
                0,
                1,
                0,
                $this->GetFrequenceEchantillonnage() - 1);
          

            
          $dataTable->AddRowVals(
            false,
            '"'.$temps.'"',
            $labelDateTime);
            
          $dataTable->AddRowVals(
            true,
            $point);
        
          $compteur++;
        }
      }
      
      $retour = $dataTable->Get();
      
      // TODO: Lever une exception si ça marche pas
      file_put_contents(
        $this->GetCheminFichierCache($nomFichierCache),
        $retour); 
    }
    
    return $retour;
  }
  
  public function GetSignalJson (
    $debutSeconde = 0,
    $finSeconde = -1,
    $analogique = false)
  {
    if ($finSeconde === -1 || $finSeconde > $this->GetNombreSecondesSignal())
    {
      $finSeconde = $this->GetNombreSecondesSignal();
    }
    
    $clefCache =
      $debutSeconde.'.'.$finSeconde.'.'.intval($analogique);
    
    $nomFichierCache =
      $this->GetNomFichierTemporaire()
      .'.'
      .$clefCache;
    
    $retour = 'dtc'; // Si on se retrouve avec ça, y'a un problème…
    
    if (file_exists($this->GetCheminFichierCache($nomFichierCache)))
    {
      $retour =
        file_get_contents($this->GetCheminFichierCache($nomFichierCache));
    }
    else
    {    
      $this->SetTraitementFichierTemporaire('r');
    
      $debutLecture = $debutSeconde * $this->GetOctetsParSeconde();
    
      if ($debutLecture > 0)
      {
        fread(
          $this->GetTraitementFichierTemporaire(),
          $debutSeconde * $this->GetOctetsParSeconde());
      }
    
      $retour = '[';
    
      for ($i = $debutSeconde; $i < $finSeconde; $i++) {
    
        $compteur = 0;
      
        while (
          !feof($this->GetTraitementFichierTemporaire())
          && $compteur < $this->GetFrequenceEchantillonnage())
        {
          $point = unpack(
            's',
            fread($this->GetTraitementFichierTemporaire(), 2));
          $point = $point[1];
      
          if ($analogique)
          {
            $point = Edf::NumeriqueVersAnalogique (
              $point,
              $this->GetValeurAnalogiqueMin(),
              $this->GetValeurAnalogiqueMax(),
              $this->GetValeurNumeriqueMin(),
              $this->GetValeurNumeriqueMax());
          }
      
          $retour .= $point;
        
          if (
            !feof($this->GetTraitementFichierTemporaire())
            && (
              $i < $finSeconde - 1
              || $compteur < $this->GetFrequenceEchantillonnage() - 1))
          {
            $retour .= ', ';
          }
        
          $compteur++;
        }
      }
    
      $retour .= ']';
      
      // TODO: Lever une exception si ça marche pas
      file_put_contents(
        $this->GetCheminFichierCache($nomFichierCache),
        $retour); 
    }
    
    return $retour;
  }
  
  public function SetTraitementFichierTemporaire ($mode)
  {
    if (!is_null($this->GetTraitementFichierTemporaire()))
    {
      fclose($this->GetTraitementFichierTemporaire());
    }
    
    $this->traitementFichierTemporaire =
      fopen(
        $this->GetCheminFichierTemporaire(),
        $mode);
  }
  
  public function GetNom ()
  {
    return $this->nom;
  }
  
  public function GetTypeTransducteur ()
  {
    return $this->typeTransducteur;
  }
  
  public function GetUniteAnalogique ()
  {
    return $this->uniteAnalogique;
  }
  
  public function GetValeurAnalogiqueMin ()
  {
    return $this->valeurAnalogiqueMin;
  }
  
  public function GetValeurAnalogiqueMax ()
  {
    return $this->valeurAnalogiqueMax;
  }
  
  public function GetValeurNumeriqueMin ()
  {
    return $this->valeurNumeriqueMin;
  }
  
  public function GetValeurNumeriqueMax ()
  {
    return $this->valeurNumeriqueMax;
  }
  
  public function GetPrefiltrage ()
  {
    return $this->prefiltrage;
  }
  
  public function GetNombrePointsParEnregistrement ()
  {
    return $this->nombrePointsParEnregistrement;
  }  

  public function GetNomFichierTemporaire ()
  {
    return $this->nomFichierTemporaire;
  }

  public function &GetTraitementFichierTemporaire ()
  {
    return $this->traitementFichierTemporaire;
  }

  public function GetFrequenceEchantillonnage ()
  {
    return $this->frequenceEchantillonnage;
  }

  public function GetOctetsParSeconde ()
  {
    return $this->octetsParSeconde;
  }
  
  public function GetTailleFichierSignal ()
  {
    if (is_null($this->tailleFichierSignal))
    {
      $this->InitInfosFichier();
    }
      
    return $this->tailleFichierSignal;
  }

  public function GetNombrePointsSignal ()
  {
    if (is_null($this->tailleFichierSignal))
    {
      $this->InitInfosFichier();
    }
    
    return $this->nombrePointsSignal;
  }

  public function GetNombreSecondesSignal ()
  {
    if (is_null($this->tailleFichierSignal))
    {
      $this->InitInfosFichier();
    }
    
    return $this->nombreSecondesSignal;
  }
  
  public function &GetEnteteEdf ()
  {
    return $this->enteteEdf;
  }
  
  public function GetCheminFichierTemporaire ()
  {
    return
      QYYG_EDF_CHEMIN
      .Edf::CHEMIN_POSIX_REPERTOIRE_FICHIERS_TEMP
      .$this->nomFichierTemporaire
      .Edf::EXTENTION_FICHIERS_TEMP_BINAIRES;
  }
  
  private function GetCheminFichierCache ($nomFichierCache)
  {
    return
      QYYG_EDF_CHEMIN
      .Edf::CHEMIN_POSIX_REPERTOIRE_FICHIERS_TEMP
      .$nomFichierCache
      .Edf::EXTENTION_FICHIERS_TEMP_JSON;
  }
}