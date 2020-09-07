Advanced Resource Template (module for Omeka S)
===============================================

> This module is based on th pull request [#1614](https://github.com/omeka/omeka-s/pull/1614) for Omeka S.


[Advanced Resource Template] is a module for [Omeka S] that adds new settings to
the resource templates in order to simplify and to improve the edition of
resources:

- auto-completion with existing values,
- locked values,
- language selection and pre-selection,
- autofill multiple fields with external data ([IdRef] and [Geonames]).


Installation
------------

See general end user documentation for [installing a module].

The optional module [Generic] may be installed first.

The module uses an external library, so use the release zip to install it, or
use and init the source.

* From the zip

Download the last release [AdvancedResourceTemplate.zip] from the list of releases
(the master does not contain the dependency), and uncompress it in the `modules`
directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `AdvancedResourceTemplate`, go to the root module, and run:

```sh
composer install --no-dev
```


Usage
-----

### Main usage

Simply update your resource templates with the new options and use them in the
resource forms.

### Autofilling

For the autofilling, you have to set the mappings inside the main settings, then
to select it inside the resource template.

The mapping is a simple text specifying the services and the mappings. it uses
the same format than the modules [Bulk Export], [Bulk Import], and [Bulk Import Files].

For example, if the service returns an xml Marc like for [Colbert], the mapping
can be a list of XPath and properties with some arguments:
```
[idref:person] = IdRef Person
/record/controlfield[@tag="003"] = dcterms:identifier ^^uri
/record/datafield[@tag="900"]/subfield[@code="a"] = dcterms:title
/record/datafield[@tag="200"]/subfield[@code="a"] = foaf:lastName
/record/datafield[@tag="200"]/subfield[@code="b"] = foaf:firstName
/record/datafield[@tag="200"]/subfield[@code="f"] = dcterms:date
/record/datafield[@tag="340"]/subfield[@code="a"] = dcterms:description @fra
```

The first line contains the key and the label of the mapping, that will be
listed in the resource template form. Multiple mapping can be appended for
different services.

You can use the same autofiller with multiple mappings for different purposes:
append a number to the key (`[idref:person #2]`). If the mapping isn’t available,
it will be skipped. Don’t change it once defined, else you will have to check
all resource templates that use it.

For a json service, use the object notation:
```
[geonames]
?username=demo
toponymName = dcterms:title
geonameId = dcterms:identifier ^^uri ~ https://www.geonames.org/{__value__}
adminCodes1.ISO3166_2 = dcterms:identifier
countryName = dcterms:isPartOf
~ = dcterms:spatial ~ Coordonnées : {lat}/{lng}
```

Note that [geonames] requires a user name (that should be the one of your
institution, but it can be "demo", "google", or "johnsmith"). Test it on
[http://api.geonames.org/searchJSON?username=demo].

More largely, you can append any arguments to the query sent to the remote
service: simply append them url encoded on a line beginning with `?`.

It’s also possible to format the values: simply append use `~` to indicate the
pattern to use and `{__value__}` to set the value from the source. For a complex
pattern, you can use any source path between `{` and `}`.


TODO
----

- [ ] Include all suggesters from module [Value Suggest].
- [ ] Create a generic mapper.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitHub.


License
-------

This module is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.

* The library [jQuery-Autocomplete] is published under the license [MIT].


Copyright
---------

* Copyright Daniel Berthereau, 2020 (see [Daniel-KM] on GitHub)
* Library [jQuery-Autocomplete]: Copyright 2012 DevBridge and other contributors

These features are built for the future digital library [Manioc] of the
Université des Antilles and Université de la Guyane, currently managed with
[Greenstone].


[Advanced Resource Template]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate
[Omeka S]: https://omeka.org/s
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[Generic]: https://gitlab.com/Daniel-KM/Omeka-S-module-Generic
[AdvancedResourceTemplate.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate/-/releases
[IdRef]: https://www.idref.fr
[Geonames]: https://www.geonames.org
[Colbert]: https://www.idref.fr/027274527.xml
[geonames]: https://www.geonames.org/export/geonames-search.html
[Value Suggest]: https://github.com/omeka-s-modules/ValueSuggest
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://http://opensource.org/licenses/MIT
[jQuery-Autocomplete]: https://www.devbridge.com/sourcery/components/jquery-autocomplete/
[Manioc]: http://www.manioc.org
[Greenstone]: http://www.greenstone.org
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
