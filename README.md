# NoCompris

NoCompris és una aplicació de llista de la compra compartida pensada per a famílies, pisos i equips petits. Està enfocada a mantenir una compra setmanal clara, ràpida i fàcil de consultar des del mòbil o des de l’escriptori.

## Què hi trobaràs avui

- Dashboard principal per botigues amb comptadors de productes i botigues pendents.
- Botigues compartides per grup, amb color propi i ordre reordenable.
- Productes amb quantitat i unitat (`u`, `kg`, `g`, `l`, `cl`), visibilitat pública o privada i marcatge de compra.
- Suggeriments de `Torna a afegir` a partir de productes comprats recentment.
- `Llistat complet` amb ordenació per botiga o alfabètica, filtre per botigues i opció de mostrar comprats.
- Un espai diferenciat per a persones usuàries normals i per a usuaris `master`.
- Accés per correu amb codi temporal, record de sessió i compatibilitat amb 2FA.
- Suport PWA per utilitzar l’app des del mòbil amb una experiència propera a una aplicació instal·lada.

## Flux principal d’ús

L’accés i l’ús diari de NoCompris segueixen aquest flux bàsic:

1. Introdueixes el teu correu electrònic per iniciar sessió.
2. Reps un codi de verificació per correu i el fas servir per entrar.
3. Si tens activada la verificació en dos passos, completes també aquest segon pas.
4. Un cop dins, entres al dashboard de compra o al panell `master`, segons el teu rol.
5. Des del dashboard pots gestionar botigues i productes; des del `Llistat complet` pots revisar tota la compra d’un cop.

## Rols dins de l’aplicació

### Usuari normal

Una persona usuària normal fa servir l’app per gestionar la seva compra del dia a dia:

- crea i edita botigues;
- afegeix productes a cada botiga amb quantitat, unitat i visibilitat;
- marca productes com a comprats;
- recupera productes comprats recentment amb l’acció `Torna a afegir`;
- consulta el llistat complet de productes visibles.

### Usuari `master`

Una persona usuària `master` accedeix a un panell específic per gestionar l’accés:

- crea grups;
- dona d’alta usuaris;
- assigna usuaris a grups;
- revisa qui té rol `master`.

## Funcionament bàsic de la llista

NoCompris organitza la compra amb una lògica simple:

- les botigues es comparteixen a nivell de grup;
- cada producte pot ser públic o privat;
- només veus els productes que et corresponen segons el teu grup i la seva visibilitat;
- els productes visibles es poden marcar com a comprats o tornar a afegir a la llista;
- els productes comprats fa més d’una setmana deixen de comptar com a actius i desapareixen de les vistes de treball;
- una botiga sense pendents continua visible, però en un estat més discret per poder recuperar productes recents;
- hi ha una vista global que permet ordenar els productes per botiga o alfabèticament i filtrar per botigues.

## Accés des del mòbil

NoCompris es pot utilitzar còmodament des del mòbil i disposa de suport PWA, amb manifest, service worker i splash screen. Això permet obrir-la amb una experiència pròxima a la d’una aplicació instal·lada, especialment útil quan estàs comprant i necessites consultar o marcar productes ràpidament.

## Configuració i perfil

Cada usuari disposa d’un espai de configuració per actualitzar el perfil, canviar la contrasenya, gestionar l’autenticació en dos factors i ajustar preferències disponibles a l’app.

## Documentació relacionada

- [Guia tècnica](TECHNICAL_GUIDE.md) per entendre l’arquitectura i on tocar cada canvi.
- [Especificació funcional de la llista](docs/llista-compra-especificacio.md) amb el comportament actual de la compra per botigues.
