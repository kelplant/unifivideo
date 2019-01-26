# éunifivideo [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kelplant/unifivideo/badges/quality-score.png?b=beta)](https://scrutinizer-ci.com/g/kelplant/unifivideo/?branch=beta)[![Build Status](https://scrutinizer-ci.com/g/kelplant/unifivideo/badges/build.png?b=beta)](https://scrutinizer-ci.com/g/kelplant/unifivideo/build-status/beta)[![Code Intelligence Status](https://scrutinizer-ci.com/g/kelplant/unifivideo/badges/code-intelligence.svg?b=beta)](https://scrutinizer-ci.com/code-intelligence)

Description
===========

Ce plugin permet l’exploitation des Caméras Ubiquiti au travers du servers unifi video et de son API.
Il permettra de pouvoir gérer les options de privacy et d'enregistrement.

Introduction
============

Pour fonctionner le plugin nécéssite de fonctionner avec le serveur NVR Ubiquiti, il est impossible de communiquer directement avec les caméras au travers du mode StandAlone.

Le scan automatique ne fonctionne que dans le cas de la configuration d'un port sécurité de type SSL.
Si un port non sécurisé (SSL) est utilisé il faudra ajouter manuellement les caméras et toutes les fonctionalités ne seront pas disponibles

> **Tip**
>
> le plugin fonctionne aussi bien en liaison avec un serveur interne qu'avec un serveur externe, une adresse IP ou un FQDN

Attention, pensez à bien définir les filtres de privacy dans l'interface Unifi Video du NVR

![configuration09](images/configuration09.PNG)

Configuration
=============
Après le téléchargement du plugin, vous n'avez plus qu'a l'activer et à remplir les Informations de configurations suivantes :

![configuration01](images/configuration01.PNG)

- Adresse IP du serveur : Est l'adresse IP ou le FQDN de votre serveur (sans http)
- Port du serveur : Port utilisé pour accéder au serveur (Si le port indiqué n'est pas un port sécursé SSL l'autodetection en fonctionnera pas)
- API key : Elle est obtenue dans l'interface d'administration Ubiquiti (voir capture ci dessous

![configuration02](images/configuration02.PNG)

Utilisation
===========

Ajout Automatique des Caméras
-----------------------------
Une fois la configuration effectuée vous pouvez si vous le souhaitez lancer la remontée automatique des caméras.
Pour cela il suffit de cliquer sur :

![configuration32](images/configuration03.PNG)

Quelques secondes plus tard vous devriez voir apparaitre l'ensemble de vos caméras correctement configurées

![configuration06](images/configuration06.PNG)


Ajout Manuel des Caméras
-----------------------------
Pour ajouter une caméra manuellement il va falloi aller récupérer la clé de la caméra dans l'IP
Pour cela ouvrez votre interface unifi video et selectionner la caméra que vous souhaitez manager.
Dans l'onglet vidéo, cherchez la partie RTMP ou RTSP service et activer la si ce n'est pas fait (vous pouvez desactiver ensuite l'option)
Nous allons nous intéresser au champ URL et plus particulière à ce qui se trouve derrière /live/
Nous prendrons donc la chaine en  nous arretant ensuite avant le _ qui se trouve à la fin ce qui dans notre exemple nous donne :
dh45ksdhmln5k3s

![configuration08](images/configuration08.PNG)

Une fois cette information récupérée vous pouvez commencer à créer votre nouvelle caméra en cliquant sur le bouton "Ajouter"

![configuration04](images/configuration04.PNG)

Il ne vous reste plus qu'a compléter les informations de la caméra

- Nom de l'équipement : Nom dans Jeedom
- Objet parent : Objet auqel la caméra est rattachée
- Camera name : Nom de la caméra dans le NVR unifi
- Camera Key : Clé recupérée précédement

![configuration07](images/configuration07.PNG)

Présentation du Widget
----------------------
Le Widget proposé en standard avec les cameras se présente comme suit :

![configuration010](images/configuration10.PNG)

Vous y retrouver la dernière capture d\'écran disponible (l\'ensemble des captures étant sauvegardé dans le repertoire /plugins/unifivideo/captures/{Nom de la Camera}).
A partir de cette interface il est possible :
- d\'arrêter les enregistrements ![configuration011](images/configuration11.PNG)
- de remettre les enregistrements dans le dernier mode activé ![configuration012](images/configuration12.PNG)
- d\'activer le filtre de confientialité ![configuration015](images/configuration15.PNG)
- de désactiver le filtre de confientialité ![configuration016](images/configuration16.PNG)
- de règler le volume de la caméra ![configuration013](images/configuration13.PNG)
- de prendre une capture d\'écran ![configuration014](images/configuration14.PNG)