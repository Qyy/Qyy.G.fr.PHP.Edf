# Lecture des fichiers EDF en PHP

+ **NE PAS ÉXÉCUTER SUR LE WEB !**  
+ **NE PAS ÉXÉCUTER SUR UNE MACHINE DE PRODUCTION !**  
+ **À N'UTILISER QUE EN LOCAL !**  
+ **GARDER UNE SAUVEGARDE DES FICHIER EDF AVANT DE LES MANIPULER AVEC CE LOGICIEL !**  
+ **NE PAS UTILISER POUR POSER UN DIAGNOSTIQUE !**

Ce logiciel est une librairie PHP servant à lire [les fichiers EDF][edf]. Ce
logiciel est juste une "preuve de concept" et ne doit pas être utilisé dans un
environnement de production.

Conçu pour _PHP 5.3_.

Testé sous MacOS X Lion

## Configurations spécifiques

**⇒ Dans le php.ini :**

+ augmenter la valeur `max_execution_time` à un minimum de `300` ;
+ passer la valeur `memory_limit` à `-1` ;
+ eventuellementm passer la valeur `default_charset` à `"utf-8"`.

**⇒ Créer les répertoires suivants avec les droits spécifiés pour le process qui exécute les scripts _PHP_ :**

+ `fichiers/export` : écriture ;
+ `fichiers/import` : lecture ;
+ `fichiers/temp` : lecture, écriture.

## Mémo à moi-même

[Ajout du sous module][gh-submodules] :

    git submodule add git://github.com/G-Qyy/Qyy.G.en.PHP.Utils.git Qyy.G.en.PHP.Utils

[Mise à jour du sous module][cj-git-submodules] :

    $ git submodule init
    $ git submodule update
    $ cd Qyy.G.en.PHP.Utils
    $ git status
    $ git checkout master
    $ git status

[cj-git-submodules]: http://chrisjean.com/2009/04/20/git-submodules-adding-using-removing-and-updating/ "Chris Jean — Git Submodules: Adding, Using, Removing, Updating"
[edf]: http://www.edfplus.info "European Data Format"
[gh-submodules]: http://help.github.com/submodules/ "github:help — Working with submodules"