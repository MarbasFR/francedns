# francedns
FranceDNS price Importer

[Voir les captures d'écran](#capture-décran)

### V1.0
* Récupération des domaines depuis la page web de FranceDNS
* Récupération des prix depuis l'API de FranceDNS
* Import des domaines dans la liste des domaines à vendre de WHMCS
* Appliquer une marge sur les prix de FranceDNS
* Arrondir les prix à .99.
  * Exemple 10.**25** € devient 10.**99** €

---

Interface
--------
Dans l'interface administrateur vous pouvez :
* Importer un nouveau domaine dans WHMCS parmis les domaines géré par FranceDNS
* Importer un prix TTC depuis FranceDNS
* Appliquer une marge sur le tarif TTC de FranceDNS
* Appliquer un arrondi à .99

Configuration
--------
Dans la configuration vous pouvez choisir la marge par défaut à applique sur les tarifs de FranceDNS


Capture d'écran
---------------
[![Configuration](/admin-addon.jpg?raw=true "Interface de configuration")](#configuration)
[![Interface](/addon.jpg?raw=true "Interface d'utilisation")](#interface)
