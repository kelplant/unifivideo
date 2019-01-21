# unifivideo [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kelplant/unifivideo/badges/quality-score.png?b=stable)](https://scrutinizer-ci.com/g/kelplant/unifivideo/?branch=stable)[![Build Status](https://scrutinizer-ci.com/g/kelplant/unifivideo/badges/build.png?b=stable)](https://scrutinizer-ci.com/g/kelplant/unifivideo/build-status/stable)[![Code Intelligence Status](https://scrutinizer-ci.com/g/kelplant/unifivideo/badges/code-intelligence.svg?b=stable)](https://scrutinizer-ci.com/code-intelligence)

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

Ajout Manuel des Caméras
-----------------------------
