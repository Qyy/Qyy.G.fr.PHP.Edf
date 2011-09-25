window.addEvent('domready', function() {
  
  MajListeFichiers();
  
  $('listeFichiers').addEvent('change', function()
  {
    $('infosFichier').empty();
    
    if ($('listeFichiers').get('value') !== 'rien')
    {
      MajInfoFichier($('listeFichiers').get('value'));
    }
  });
  
  function MajInfoFichier (fichier)
  {
    // TODO requete JSON
    var requete = new Request({
      url: 'infos-edf.json.php?fichier=' + fichier,
      method: 'get',
      onSuccess: function (responseText)
      {
        var h1 = new Element('h1',
        {
          'html': 'Informations sur le fichier'
        });
        var pre = new Element('pre',
        {
          'html': responseText
        });
        $('infosFichier').adopt(h1, pre);
      },
      onFailure: function(xhr)
      {
        alert(xhr.responseText);
      }
    });
    
    requete.send();
  }
  
  function MajListeFichiers ()
  {
    var requete = new Request.JSON({
      url: 'liste-fichiers.json.php',
      onSuccess: function(reponseJSON, reponseText)
      {
        reponseJSON.fichiers.each(ajouteFichierListeFichiers);
      },
      onFailure: function(xhr)
      {
        alert(xhr.responseText);
      }
    }).get();
  }
  
  function ajouteFichierListeFichiers(fichier, index, fichiers)
  {
    $('listeFichiers').grab(
      new Element('option',
      {
        'value': fichier.valeur,
        'html' : fichier.vue
      }));
  }
});