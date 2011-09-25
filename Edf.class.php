<?php

date_default_timezone_set('Europe/Paris');

// pour que ça marche avant PHP 5.3. Le premier test, c'est au cas où
// un imbécile lit le comm. de ce crétin et décide de l'appliquer :
// http://fr2.php.net/manual/fr/language.constants.predefined.php#105256
// pour rappel une constante magique CHANGE en fonction du contexte, et le
// conseil du comm. crée une véritable constante dont la valeur serra
// toujours la même. On risque donc de se trouver avec un __DIR__ contenant
// une fausse valeur si ce code est appellé avant dans un fichier d'un autre
// répertoire avant PHP 5.3.
if (
  (defined(__DIR__) && __DIR__ != dirname(__FILE__))
  || !defined('__DIR__'))
{
  define('QYYG_EDF_CHEMIN', dirname(__FILE__).'/');
}
// Haaa PHP 5.3
else
{
   define('QYYG_EDF_CHEMIN', __DIR__.'/');
}

require_once(QYYG_EDF_CHEMIN.'GgVisuDataTable.class.php');
require_once(QYYG_EDF_CHEMIN.'EnteteEdf.class.php');
require_once(QYYG_EDF_CHEMIN.'EnteteSignaux.class.php');
require_once(QYYG_EDF_CHEMIN.'Signal.class.php');

/**
 * La classe Edf fournit une API simple pour lire les fichiers EDF en PHP.
 */
class Edf
{
  const CHEMIN_REPERTOIRE_FICHIERS        = 'fichiers/';
  const CHEMIN_REPERTOIRE_FICHIERS_EXPORT = 'fichiers/export/';
  const CHEMIN_REPERTOIRE_FICHIERS_IMPORT = 'fichiers/import/';
  const CHEMIN_REPERTOIRE_FICHIERS_TEMP   = 'fichiers/temp/';
  const EXTENTION_FICHIERS_TEMP           = '.temp';
  const EXTENTION_FICHIERS_TEMP_BINAIRES  = '.bin.temp';
  const EXTENTION_FICHIERS_TEMP_JSON      = '.json.temp';
  const MASQUE_FICHIERS_EDF_MIN           = 'fichiers/import/*.edf';
  const MASQUE_FICHIERS_EDF_MAJ           = 'fichiers/import/*.EDF';
  const MASQUE_FICHIERS_TEMP              = 'fichiers/temp/*.temp';
  const MASQUE_FICHIERS_TEMP_BINAIRES     = 'fichiers/temp/*.bin.temp';
  const MASQUE_FICHIERS_TEMP_JSON         = 'fichiers/temp/*.json.temp';
  
  const FORMAT_EXPORT_CSV_VIRGULE       = 0;
  const FORMAT_EXPORT_CSV_POINT_VIRGULE = 1;
  const FORMAT_EXPORT_CSV_TABULATION    = 2;
  const FORMAT_EXPORT_GG_DATA_TABLE     = 3;
  
  private $nomFichier;
  
  private $traitement;
  
  private $enteteEdf;
  private $enteteSignaux;
  
  private $signaux = array();
  
  /**
   * Crée un nouvel objet `Edf`.
   * @param string $nomFichier <p>
   * Le nom du fichier EDF à lire sans l'extention (ex : si le fichier se
   * nomme "sample.edf", la chaine à fournir est "sample").<br/>
   * De plus, il doit se trouver à la racine du répertoire
   * `Edf::CHEMIN_REPERTOIRE_FICHIERS_IMPORT` et ne dooit donc pas contenir
   * de caractères slash ('/') ou anti-slash ('\').<br/>
   * Les espaces, ainsi que les caractères accentués et certains caractères
   * spéciaux sont fortements déconseillés. Il est donc recommandé de
   * renommer le fichier en conscéquence.
   * </p>
   */
  function __construct ($nomFichier)
  {
    $this->nomFichier = Edf::SupprimeSlashAntiSlash($nomFichier);
    
    $this->SetTraitement('r');
    
    $this->enteteEdf = new EnteteEdf($this->traitement);
    $this->enteteSignaux = new EnteteSignaux(
      $this->traitement,
      $this->enteteEdf->GetNombreSignaux());

    $this->InitSignaux();
  }
  
  /**
   * Ferme le fichier EDF et supprime les fichier temporaires.
   * @todo La suppression des fichiers temporaire ne marche pas
   */
  function __destruct ()
  {
    fclose($this->GetTraitement());
    //Edf::SupprimeFichiersTemporaires(); // Marche pas ???
  }
  
  /**
   * Instancie une classe `Signal` pour tout les signaux contenu dans le
   * fichier EDF en leur fournissant les informations qui leur sont propres
   * grâce à l'objet `EnteteSignaux` ainsi que les noms des fichier
   * temporaires dans lequel seront stockés leurs valeurs.
   */
  private function InitSignaux ()
  {
    for ($i = 0; $i < $this->GetEnteteEdf()->GetNombreSignaux(); $i++)
    {
      $nomSignal = $this->GetEnteteSignaux()->GetNomsSignaux($i);

      $this->signaux[$i] = new Signal(
        // $nom
        $nomSignal,
        // $typeTransducteur
        $this->GetEnteteSignaux()->GetTypesTransducteurs($i),
        // $uniteAnalogique
        $this->GetEnteteSignaux()->GetUnitesAnalogiques($i),
        // $valeurAnalogiqueMin 
        $this->GetEnteteSignaux()->GetValeursAnalogiquesMin($i),
        // $valeurAnalogiqueMax
        $this->GetEnteteSignaux()->GetValeursAnalogiquesMax($i),
        // $valeurNumeriqueMin
        $this->GetEnteteSignaux()->GetValeursNumeriquesMin($i),
        // $valeurNumeriqueMax
        $this->GetEnteteSignaux()->GetValeursNumeriquesMax($i),
        // $prefiltrage
        $this->GetEnteteSignaux()->GetPrefiltrages($i),
        // $nombrePointsParEnregistrement
        $this->GetEnteteSignaux()->GetNombresPointsParEnregistrement($i),
        // &$enteteEdf
        $this->GetEnteteEdf(),
        // $nomFichierTemporaire
        $i
        .'.'
        .substr(
          intval($nomSignal, 36),
          0,
          4));
          
      $this->GetSignaux($i)->SetTraitementFichierTemporaire('w');
    }
    
    $this->GenereTempsSignaux();
  }
  
  /**
   * Lit chaques signaux et les stocke dans des fichier temporaire distinct
   * sans les modifier. Les signaux conservent donc le format binaire propre
   * aux fichiers EDF.
   */
  private function GenereTempsSignaux ()
  {
    for (
      $iEnr = 0;
      $iEnr < $this->GetEnteteEdf()->GetNombreEnregistrements();
      $iEnr++)
    {
      for (
        $iSig = 0;
        $iSig < $this->GetEnteteEdf()->GetNombreSignaux();
        $iSig++)
      {
        // TODO: lever une exception en cas d'echec de lecture
        $signal = fread (
          $this->GetTraitement(),
          $this->GetSignaux($iSig)->GetNombrePointsParEnregistrement() * 2);
        
        // TODO: lever une exception en cas d'echec d'ecriture
        $reussi = fwrite (
          $this->GetSignaux($iSig)->GetTraitementFichierTemporaire(),
          $signal);
      }
    }
  }
  
  // TODO: Doc
  // TODO: Finir
  public function ExporteSignaux (
    $format,
    $signauxVoulu = -1, // array() des signaux sinon c'est tous
    $colonneIndexTemporel = true,
    $debutSeconde = 0,
    $dureeSeconde = -1,
    $analogique = false
    )
  {
    $infosExport = array();
    
    if (!is_array($signauxVoulu))
    {
      $signauxVoulu = $this->GetSignaux();
    }
    
    $finSeconde = Edf::ObtienFinSeconde(
      $signauxVoulu[0]->GetNombreSecondesSignal(),
      $debutSeconde,
      $dureeSeconde);
    
    for ($i = 0; $i < count($signauxVoulu); $i++)
    {
      $signauxVoulu[$i]->SetTraitementFichierTemporaire('r');
      $debutLecture =
        $debutSeconde * $signauxVoulu[$i]->GetOctetsParSeconde();
      
      if ($debutLecture > 0)
      {
        fread(
          $signauxVoulu[$i]->GetTraitementFichierTemporaire(),
          $debutSeconde * $this->GetOctetsParSeconde());
      }
      
      $infosExport[$i] = array(
        'signal'       => $signauxVoulu[$i],
        'debutLecture' => $debutLecture
      );
    }
    unset($debutLecture);
    
    
    
    /***/
    
    for (
      $iEnr = 0;
      $iEnr < $this->enteteEdf->GetNombreEnregistrements();
      $iEnr++)
    {
      for (
        $iSig = 0;
        $iSig < $this->enteteEdf->GetNombreSignaux();
        $iSig++)
      {
        
        $debutLecture =
          $debutSeconde * $this->signaux[$iSig]->GetOctetsParSeconde();
        
        // TODO: lever une exception en cas d'echec de lecture
        $signal = fread (
          $this->traitement,
          $signaux[$iSig]->GetNombrePointsParEnregistrement() * 2);
        
        // TODO: lever une exception en cas d'echec d'ecriture
        $reussi = fwrite (
          $this->signaux[$iSig]->GetTraitementFichierTemporaire(),
          $signal);
      }
    }
  }
  
  /**
  * Supprime tous les slash et les anti-slash de la chaine fournit.
  * @param string $chaine <p>
  * La chaine à traiter.
  * </p>
  * @return string Retourne la chaine fournit sans slash ni anti-slash.
  */
  public static function SupprimeSlashAntiSlash ($chaine)
  {
    return preg_replace('%(/|\\\\)%', '', $chaine);
  }
  
  /**
  * Permet d'obtenir la dernière seconde d'une durée souhaité en s'assurant
  * qu'elle ne dépasse pas la durée de l'enregistrement.
  * @param int $nombreSecondesSignal <p>
  * Nombre de seconde dans un signal.
  * </p>
  * @param int $debutSeconde <p>
  * Début souhaité sur le signal.
  * </p>
  * @param int $dureeSeconde <p>
  * Durée de signal souhaité. Si la durée est strictement inferieur à 1, elle
  * sera interprétée comme la duréemaximal jussqu'à la fin du signal.
  * </p>
  * @return int Retourne l'index temporel en seconde sur le signal par rapport
  * à la durée souhaité.
  */
  public static function ObtienFinSeconde (
    $nombreSecondesSignal,
    $debutSeconde,
    $dureeSeconde = -1)
  {
    $finSeconde = $nombreSecondesSignal;
    
    if (
      $dureeSeconde >  1
      && ($debutSeconde + $dureeSeconde) < $finSeconde)
    {
      $finSeconde = $debutSeconde + $dureeSeconde;
    }
    
    return $finSeconde;
  }
  
  /**
  * Permet de connaitre la date et l'heure d'un index temporel en seconde de
  * la position sur un signal à partire du `DateTime` de début
  * d'enregistrement.
  * @param int $indexTemporel <p>
  * L'index temporel en seconde de la position sur un signal.
  * </p>
  * @param DateTime $dateTimeDebutEnregistrement <p>
  * L'objet `DateTime` qui représente le début de l'enregistrement 
  * (`EnteteEdf::GetCloneDateTimeDebutEnregistrement()`).
  * </p>
  * @return DateTime Retourne la date et l'heure de l'index temporel.
  */
  public static function IndexTemporelVersDateTime (
    $indexTemporel,
    $debutEnregistrement)
  {
    $interval =
      DateInterval::createFromDateString($indexTemporel. ' seconds');
    
    return $debutEnregistrement->add($interval);
  }
  
  /**
   * Converti une valeur numérique du type par défaut du format EDF dans son
   * équivalent analogique.
   * @link http://www.edfplus.info/specs/edffloat.html
   * @param int $valeurNumerique <p>
   * La valeur numérique sous la forme d'un entier de 16 bits signé avec un
   * complément à 2.
   * </p>
   * @param float $valeurAnalogiqueMin <p>
   * La borne basse de la plage de valeurs analogiques.
   * </p>
   * @param float $valeurAnalogiqueMax <p>
   * La borne haute de la plage de valeurs analogiques.
   * </p>
   * @param int $valeurNumeriqueMin <p>
   * La borne basse de la plage de valeur numérique (-32768 max pour un entier
   * de 16 bits signé avec un complément à 2).
   * </p>
   * @param int $valeurNumeriqueMax <p>
   * La borne basse de la plage de valeur numérique (32767 max pour un entier
   * de 16 bits signé avec un complément à 2).
   * </p>
   * @return float Retourne la valeur analogique mise à l'échelle en fonction
   * des bornes fournit. 
   */
  public static function NumeriqueVersAnalogique (
    $valeurNumerique,
    $valeurAnalogiqueMin,
    $valeurAnalogiqueMax,
    $valeurNumeriqueMin = -32768,
    $valeurNumeriqueMax = 32767)
  {
    $variationNumerique = $valeurNumeriqueMax - $valeurNumeriqueMin;
    $variationAnalogique = $valeurAnalogiqueMax - $valeurAnalogiqueMin;
    
    // Le coup de gain je pourrais pas l'expliquer, mais visiblement, ça
    // permet d'avoir des valeurs correct après la virgule, en comparaison
    // des valeurs retourné par [EDFbrowser](http://www.teuniz.net/edfbrowser)
    // lors d'un export ASCII.
    // Merci [Alain-Qyy](https://github.com/Alain-Qyy) pour avoir trouvé
    // l'astuce !
    $valeurNumeriqueGain = $valeurNumerique * 1000000;
    
    // Merci à [clavdam](https://github.com/clavdam) pour la formule !
    $miseEchelle =
      (
        (($valeurNumeriqueGain - $valeurNumeriqueMin)
          / $variationNumerique)
        * $variationAnalogique)
        + $valeurAnalogiqueMin;
    
    $valeurAnalogique = round($miseEchelle / 1000000, 6);
    
    return $valeurAnalogique;
  }
  
  /**
   * Supprime la totalité des fichiers temporaires.
   */
  public static function SupprimeFichiersTemporaires ()
  {
    array_map('unlink', glob(QYYG_EDF_CHEMIN.Edf::MASQUE_FICHIERS_TEMP));
  }

  /**
   * Supprime les fichiers temporaires contenant les signaux au format
   * binaire.
   */
  public static function SupprimeFichiersTemporairesBinaires ()
  {
    array_map('unlink', glob(QYYG_EDF_CHEMIN.Edf::MASQUE_FICHIERS_TEMP_BIN));
  }

  /**
   * Supprime les fichiers temporaire généré pour le cache de la sortie
   * JSON.
   */
  public static function SupprimeFichiersTemporairesJson ()
  {
    array_map('unlink', glob(QYYG_EDF_CHEMIN.Edf::MASQUE_FICHIERS_TEMP_JSON));
  }
  
  /**
   * Ouvre le fichier EDF fournit à l'instantiation de la classe. Si le
   * fichier est déjà ouvert, il est préalablement fermé.
   * @param string $mode <p>
   * Le paramètre mode spécifie le type d'accès désiré au flux. Voir la
   * fonction
   * `fopen` de PHP pour le détail sur les valeurs possibles.
   * </p>
   */
  private function SetTraitement ($mode)
  {
    if (!is_null($this->GetTraitement()))
    {
      fclose($this->GetTraitement());
    }
    
    $this->traitement = fopen($this->GetCheminFichier(), $mode);
  }
  
  /**
   * Récupère le pointeur du fichier EDF traité par la classe.
   * @return ressource Retourne la ressource représentant le pointeur du
   * fichier EDF traité par la classe.
   */
  private function &GetTraitement ()
  {
    return $this->traitement;
  }

  /**
   * Récupère le nom du fichier EDF tel qu'il à été fournit à l'instantiation,
   * de la classe après la suppression des slashs et des anti-slashs.
   * @return string Retourne le nom fichier EDF chargé.
   */
  private function GetNomFichier ()
  {
    return Edf::SupprimeSlashAntiSlash($this->nomFichier);
  }

  /**
   * Récupère le chemin absolut vers le fichier EDF créé à partir du nom
   * fournit à l'instantiation de la classe.
   * @return string Retourne le chemin absolut vers le fichier EDF chargé.
   */
  public function GetCheminFichier ()
  {
    return
      QYYG_EDF_CHEMIN
      .Edf::CHEMIN_REPERTOIRE_FICHIERS_IMPORT
      .$this->GetNomFichier()
      .Edf::GetExtentionFichier($this->GetNomFichier());
  }
  
  /**
   * Récupère la première partie de l'entête du fichier EDF.
   * @return EnteteEdf Retourne un objet contenant les informations au sujet
   * du fichier EDF.
   */
  public function &GetEnteteEdf ()
  {
    return $this->enteteEdf;
  }
  
  /**
   * Récupère la seconde partie de l'entête du fichier EDF contenant les
   * informations sur les signaux.
   * @return EnteteSignaux Retourne un objet contenant les informations au
   * sujet des signaux.
   */
  public function &GetEnteteSignaux ()
  {
    return $this->enteteSignaux;
  }
  
  /**
   * Récupère les signaux du fichier EDF.
   * @param int $i <p>
   * [optionel] L'index du signal souhaité.
   * </p>
   * @return mixed Retourne un tableau d'objets `Signal` si `$i` n'a pas été
   * renseigné, ou l'objet `Signal` requis dans le cas contraire.
   */
  public function &GetSignaux ($i = null)
  {
    if (is_null($i))
    {
      return $this->signaux;
    }
    else
    {
      return $this->signaux[$i];
    }
  }
  
  /**
   * Récupère les infos sur le fichier EDF sous la forme d'un tableau PHP.
   * @return array Retourne un tableau contenant les infos sur le fichier EDF
   */
  public function GetInfos ()
  {
    $retour = array(
      'enteteEdf' => array(
        'dateTimeDebutEnregistrement'      =>
          $this->enteteEdf->GetCloneDateTimeDebutEnregistrement(),
        'dateDebutEnregistrement'          =>
          $this->enteteEdf->GetDateDebutEnregistrement(),
        'dureeEnregistrement'              =>
          $this->enteteEdf->GetDureeEnregistrement(),
        'enregistrementId'                 =>
          $this->enteteEdf->GetEnregistrementId(),
        'heureDebutEnregistrement'         =>
          $this->enteteEdf->GetHeureDebutEnregistrement(),
        'nombreEnregistrements'            =>
          $this->enteteEdf->GetNombreEnregistrements(),
        'nombreOctetsEnteteEnregistrement' =>
          $this->enteteEdf->GetNombreOctetsEnteteEnregistrement(),
        'nombreSignaux'                    =>
          $this->enteteEdf->GetNombreSignaux(),
        'patientId'                        =>
          $this->enteteEdf->GetPatientId(),
        'versionFormatDonnees'             =>
          $this->enteteEdf->GetVersionFormatDonnees()
      ),
      'signaux' => array()
    );
    
    foreach ($this->GetSignaux() as $signal)
    {
      $retour['signaux'][] = array(
        'cheminFichierTemporaire'       =>
          $signal->GetCheminFichierTemporaire(),
        'frequenceEchantillonnage'      =>
          $signal->GetFrequenceEchantillonnage(),
        'nom'                           =>
          $signal->GetNom(),
        'nomFichierTemporaire'          =>
          $signal->GetNomFichierTemporaire(),
        'nombrePointsParEnregistrement' =>
          $signal->GetNombrePointsParEnregistrement(),
        'nombrePointsSignal'            =>
          $signal->GetNombrePointsSignal(),
        'nombreSecondesSignal'          =>
          $signal->GetNombreSecondesSignal(),
        'octetsParSeconde'              =>
          $signal->GetOctetsParSeconde(),
        'prefiltrage'                   =>
          $signal->GetPrefiltrage(),
        'tailleFichierSignal'           =>
          $signal->GetTailleFichierSignal(),
        'typeTransducteur'              =>
          $signal->GetTypeTransducteur(),
        'uniteAnalogique'               =>
          $signal->GetUniteAnalogique(),
        'valeurAnalogiqueMax'           =>
          $signal->GetValeurAnalogiqueMax(),
        'valeurAnalogiqueMin'           =>
          $signal->GetValeurAnalogiqueMin(),
        'valeurNumeriqueMax'            =>
          $signal->GetValeurNumeriqueMax(),
        'valeurNumeriqueMin'            =>
          $signal->GetValeurNumeriqueMin()
      );
    }
    return $retour;
  }
    
  public function GetInfosJson ($formatDate = 'd/m/Y H:i:s')
  {
    $retour = $this->GetInfos();
    $retour['enteteEdf']['dateTimeDebutEnregistrement']->format($formatDate);
    
    return json_encode($retour);
  }
  
  public Static function GetExtentionFichier ($nomFichier)
  {
    $retour = '.edf';
    
    $nomFichierPlusExt =
      $nomFichier
      .$retour;
    
    if (!Edf::TesteExistenceFichier($nomFichierPlusExt))
    {
      $retour = '.EDF';
      
      $nomFichierPlusExt =
        $nomFichier
        .$retour;
        
      if (!Edf::TesteExistenceFichier($nomFichierPlusExt))
      {
        // TODO: Lever une exception
        $retour = false;
      }
    }
    
    return $retour;
  }
  
  public static function TesteExistenceFichier ($nomFichierPlusExt)
  {
    return file_exists(
      QYYG_EDF_CHEMIN
      .Edf::CHEMIN_REPERTOIRE_FICHIERS_IMPORT
      .$nomFichierPlusExt);
  }
  
  public static function GetListeFichiers ()
  {
    $fichiers = glob(QYYG_EDF_CHEMIN.Edf::MASQUE_FICHIERS_EDF_MIN);
    $fichiers = array_merge($fichiers,
      glob(QYYG_EDF_CHEMIN.Edf::MASQUE_FICHIERS_EDF_MAJ));
    
    $retour = false;
    
    if (count($fichiers) > 1)
    {
      $retour = $fichiers;
    }
    
    return $retour;
  }
  
  public static function GetListeFichiersJson ()
  {
    $fichiers = Edf::GetListeFichiers();
    
    $retour = '';
    
    if ($fichiers === false)
    {
      $retour = false;
    }
    else
    {
      foreach($fichiers as $fichier)
      { 
        // Étant donné que le paramètre `suffix` est sensible à la casse, j'ai
        // utilisé cette excellente astuce :
        // http://fr.php.net/manual/fr/function.basename.php#94026
        $info = pathinfo($fichier);
        $valeur = basename($fichier, '.'.$info['extension']);

        $vue = basename($fichier);

        if (!empty($retour))
        {
          $retour .= ', ';
        }

        $retour .= '{"valeur": "'.$valeur.'", ';
        $retour .= '"vue": "'.$vue.'"}';
      }

      $retour = '{"fichiers": ['.$retour.']}';
    }
    
    return $retour;
  }
}