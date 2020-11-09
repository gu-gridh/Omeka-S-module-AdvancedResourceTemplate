Modèles de ressource avancés (module pour Omeka S)
==================================================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better.__

See [English readme].

[Advanced Resource Template] est un module pour [Omeka S] qui ajoute de
nouvelles options aux modèles de ressources afin de faciliter et d’améliorer
l’édition des ressources :

- auto-completion avec des valeurs existantes,
- valeurs vérifiées,
- champs multiples avec la même propriété,
- sélection de la langue au niveau du modèle et pré-sélection de la langue,
- création d’une nouvelle ressource durant l’édition d’une ressource,
- remplissage automatique de plusieurs champs avec des données externes ([IdRef],
  [Geonames] et services json ou xml).


Installation
------------

Consulter la documentation utilisateur pour [installer un module].

Le module optionel [Generic] peut être installé en premier.

Le module utilise une bibliothèque externe : utilisez le zip pour installer le
module ou utilisez et initialisez la source.

* À partir du zip

Télécharger la dernière livraison [AdvancedResourceTemplate.zip] depuis la liste
des livraisons (la source principale ne contient pas la dépendance) et
décompresser le dans le dossier `modules`.

* Depuis la source et pour le développement

Si le module est installé depuis la source, renommez le nom du dossier du module
en `AdvancedResourceTemplate`, puis allez à la racine du module et lancez :

```sh
composer install --no-dev
```


Utilisation
-----------

### Usage principal

Mettez simplement à jour vos modèles de ressources avec les nouvelles options et
utilisezèles dans les formulaires de ressources.

### Remplissage automatique

Pour le remplissage automatique, définissez les schémas de correspondance dans
les paramètres généraux, puis sélectionnez les dans les modèles de ressource.

Le schéma de correspondance est un simple texte spécifiant les services et les
correspondance. Elle utilise le même format que les modules [Export en lot],
[Import en lot] et [Import de fichiers en lot].

#### Services intégrés

Par exemple, si le service renvoie un xml Marc comme pour [Colbert], le schéma
peut être une liste de XPath et de propriétés avec quelques arguments :
```
[idref:person] = IdRef Personne
/record/controlfield[@tag="003"] = dcterms:identifier ^^uri
/record/datafield[@tag="900"]/subfield[@code="a"] = dcterms:title
/record/datafield[@tag="200"]/subfield[@code="a"] = foaf:lastName
/record/datafield[@tag="200"]/subfield[@code="b"] = foaf:firstName
/record/datafield[@tag="200"]/subfield[@code="f"] = dcterms:date
/record/datafield[@tag="340"]/subfield[@code="a"] = dcterms:description @fra
```

La première ligne contient la clé et le libellé du schéma, qui seront énumérées
dans le formulaire du modèle de ressource. Plusieurs schémas peuvent être
ajoutées pour différents services.

Vous pouvez utiliser le même remplissuers avec plusieurs schémas à des fins
différentes : ajouter un numéro à la clé (`[idref:person #2]`). Si le schéma
n’est pas disponible, il sera ignoré. Ne le modifiez pas une fois définie, sinon
vous devrez vérifier tous les modèles de ressources qui l’utilisent.

Pour un service json, utilisez la notation objet :
```
[geonames]
?username=demo
toponymName = dcterms:title
geonameId = dcterms:identifier ^^uri ~ https://www.geonames.org/{__value__}
adminCodes1.ISO3166_2 = dcterms:identifier ~ ISO 3166-2: {__value__}
countryName = dcterms:isPartOf
~ = dcterms:spatial ~ Coordonnées : {lat}/{lng}
```

Notez que [geonames] nécessite un nom d’utilisateur (qui doit être le votre,
mais il peut s’agir de "demo", "google" ou "johnsmith"). Testez le sur
https://api.geonames.org/searchJSON?username=demo.

Plus largement, vous pouvez ajouter tout argument à la requête envoyée au
service à distance : il suffit de les ajouter au format url encodée sur une
ligne commençant par "?".

Il est également possible de formater les valeurs : il suffit d’ajouter `~` pour
indiquer le format à utiliser et `{__value__}` pour préciser la valeur à partir
de la source. Pour un schéma complexe, vous pouvez utiliser tout chemin de la
source entre `{` et `}`.

Pour un modèle plus complexe, vous pouvez utiliser des [filtres Twig] avec la
valeur. Par exemple, pour convertir une date "17890804" en une norme [ISO 8601],
avec la date numérique `1789-08-04`, vous pouvez utiliser :
```
/record/datafield[@tag="103"]/subfield[@code="b"] = dcterms:valid ^^numeric:timestamp ~ {{ value|trim|slice(1,4) }}-{{ value|trim|slice(5,2) }}-{{ value|trim|slice(7,2) }}
```

Le filtre Twig commence avec deux `{` et un espace et finit avec un espace et
deux `}`. Il ne fonctionne qu’avec la valeur `value` actuelle.

#### Autres services

Si vous souhaitez inclure un service qui n’est pas pris en charge actuellement,
vous pouvez choisir les remplisseurs `generic:json` ou `generic:xml`. Deux
paramètres obligatoires et deux paramètres facultatifs doivent être ajoutés sur
quatre lignes distinctes :
- l’url complète du service,
  Notez que le protocole peut devoir être "http" et non "https" sur certains
  serveurs (celui où Omeka est installé), car la requête est faite par Omeka
  lui-même, et non par le navigateur. De ce fait, pour utiliser les "https"
  recommandés, vous devrez peut-être [configurer les clés] `slcapath` et `sslcafile`
  dans le fichier Omeka `config/local.config.php`.
- la requête avec le joker `{query}`, commençant par un `?`,
- le chemin à la liste des résultats, lorsqu’il n’est pas en racine, afin de
  pouvoir réaliser une boucle, indiqué par `{list}`,
- le chemin vers la valeur à utiliser comme libellé pour chaque résultat,
  indiqué par `{__label__}`. S’il est absent, le premier champ sera utilisé.


Par exemple, vous pouvez interroger un autre service Omeka S (essayez avec
"archives"), ou les services ci-dessus :
```
[generic:json #Mall History] Omeka S demo Mall History
http://dev.omeka.org/omeka-s-sandbox/api/items?site_id=4
?fulltext_search={query}
o:title = {__label__}
dcterms:title.0.@value = dcterms:title
dcterms:date.0.@value = dcterms:date
o:id = dcterms:identifier ^^uri ~ https://dev.omeka.org/omeka-s-sandbox/s/mallhistory/item/{__value__}

[generic:json #geonames] = Geonames générique
http://api.geonames.org/searchJSON
?username=johnsmith&q={query}
geonames = {list}
toponymName = dcterms:title
geonameId = dcterms:identifier ^^uri ~ https://www.geonames.org/{__value__}
adminCodes1.ISO3166_2 = dcterms:identifier ~ ISO 3166-2: {__value__}
countryName = dcterms:isPartOf
~ = dcterms:spatial ~ Coordinates: {lat}/{lng}

[generic:xml #IdRef Person] = IdRef Personne
https://www.idref.fr/Sru/Solr
?version=2.2&rows=30&q=persname_t%3A{query}
/doc/str[@name="affcourt_z"] = {__label__}
/response/result/doc = {list}
/doc/arr[@name="affcourt_r"]/str = dcterms:title
/doc/arr[@name="nom_t"] = foaf:lastName
/doc/arr[@name="prenom_t"] = foaf:firstName
/doc/date[@name="datenaissance_dt"] = dcterms:date ^^numeric:timestamp
/doc/str[@name="ppn_z"] = dcterms:identifier ^^uri ~ https://idref.fr/{__value__}
```


TODO
----

- [ ] Remplacer `{__value__}` et `{__label__}` par `{value}` et `{label}`.
- [ ] Inclure tous les suggesteurs du module [Value Suggest].
- [ ] Limiter l’autocomplétion aux ressources choisies.
- [ ] Autocompléter avec des ressources, pas des valeurs.
- [x] Utiliser twig pour des formats plus complexes.
- [x] Créer une option de correspondance générique.


Attention
---------

Utilisez-le à vos propres risques.

Il est toujours recommandé de sauvegarder vos fichiers et vos bases de données
et de vérifier vos archives régulièrement afin de pouvoir les reconstituer si
nécessaire.

Dépannage
---------

Voir les problèmes en ligne sur la page des [questions du module] du GitLab.


Licence
-------

Ce module est publié sous la licence [CeCILL v2.1], compatible avec [GNU/GPL] et
approuvée par la [FSF] et l’[OSI].

Ce logiciel est régi par la licence CeCILL de droit français et respecte les
règles de distribution des logiciels libres. Vous pouvez utiliser, modifier
et/ou redistribuer le logiciel selon les termes de la licence CeCILL telle que
diffusée par le CEA, le CNRS et l’INRIA à l’URL suivante "http://www.cecill.info".

En contrepartie de l’accès au code source et des droits de copie, de
modification et de redistribution accordée par la licence, les utilisateurs ne
bénéficient que d’une garantie limitée et l’auteur du logiciel, le détenteur des
droits patrimoniaux, et les concédants successifs n’ont qu’une responsabilité
limitée.

À cet égard, l’attention de l’utilisateur est attirée sur les risques liés au
chargement, à l’utilisation, à la modification et/ou au développement ou à la
reproduction du logiciel par l’utilisateur compte tenu de son statut spécifique
de logiciel libre, qui peut signifier qu’il est compliqué à manipuler, et qui
signifie donc aussi qu’il est réservé aux développeurs et aux professionnels
expérimentés ayant des connaissances informatiques approfondies. Les
utilisateurs sont donc encouragés à charger et à tester l’adéquation du logiciel
à leurs besoins dans des conditions permettant d’assurer la sécurité de leurs
systèmes et/ou de leurs données et, plus généralement, à l’utiliser et à
l’exploiter dans les mêmes conditions en matière de sécurité.

Le fait que vous lisez actuellement ce document signifie que vous avez eu des
connaissances de la licence CeCILL et que vous en acceptez les termes.

* La bibliothèque [jQuery-Autocomplete] est publiée sous licence [MIT].


Copyright
---------

* Copyright Daniel Berthereau, 2020 (see [Daniel-KM] on GitLab)
* Library [jQuery-Autocomplete]: Copyright 2012 DevBridge et autres contributeurs

Ces fonctionnalités sont destinées à la future bibliothèque numérique [Manioc]
de l’Université des Antilles et Université de la Guyane, actuellement gérée avec
[Greenstone].


[Advanced Resource Template]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate
[Omeka S]: https://omeka.org/s
[installer un module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[Generic]: https://gitlab.com/Daniel-KM/Omeka-S-module-Generic
[AdvancedResourceTemplate.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate/-/releases
[IdRef]: https://www.idref.fr
[Geonames]: https://www.geonames.org
[Colbert]: https://www.idref.fr/027274527.xml
[geonames]: https://www.geonames.org/export/geonames-search.html
[filtres Twig]: https://twig.symfony.com/doc/3.x
[ISO 8601]: https://www.iso.org/iso-8601-date-and-time-format.html
[Export en lot]: https://gitlab.com/Daniel-KM/Omeka-S-module-BulkExport
[Import en lot]: https://gitlab.com/Daniel-KM/Omeka-S-module-BulkImport
[Import de fichiers en lot]: https://gitlab.com/Daniel-KM/Omeka-S-module-BulkImportFiles
[Value Suggest]: https://github.com/omeka-s-modules/ValueSuggest
[questions du module]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://http://opensource.org/licenses/MIT
[jQuery-Autocomplete]: https://www.devbridge.com/sourcery/components/jquery-autocomplete/
[Manioc]: http://www.manioc.org
[Greenstone]: http://www.greenstone.org
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
