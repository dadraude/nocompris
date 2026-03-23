# Guia tècnica per desenvolupar noves funcionalitats

Aquest document serveix com a guia ràpida per entendre l’arquitectura actual de NoCompris i per afegir funcionalitats noves sense duplicar lògica ni trencar comportaments existents.

## Objectiu

Quan es treballi en una nova funcionalitat, aquest document ha de servir per respondre tres preguntes:

1. on viu avui la lògica relacionada;
2. quina és la font de veritat del comportament;
3. quines proves cal tocar per validar el canvi.

## Stack i punts d’entrada

- Backend: Laravel 12 amb PHP 8.4.
- Autenticació: Laravel Fortify amb entrada per correu i codi temporal.
- UI reactiva: Livewire 4.
- Components visuals: Flux UI.
- Estils: Tailwind CSS v4.
- Tests: Pest 4 amb cobertura principal a `tests/Feature/`.
- Persistència local per defecte: SQLite segons `.env.example`.

Punts d’entrada principals del projecte:

- `routes/web.php`: rutes web principals i separació entre espai normal i espai `master`.
- `routes/settings.php`: perfil, contrasenya, aparença i 2FA.
- `bootstrap/app.php`: registre de routing, middleware i excepcions a Laravel 12.
- `app/Providers/FortifyServiceProvider.php`: configuració de Fortify, rate limits i redireccions post-login.
- `resources/views/pages/`: pàgines Livewire principals en format single-file.
- `resources/views/layouts/app/sidebar.blade.php`: navegació entre dashboard, llistat complet, gestió `master` i configuració.
- `resources/js/app.js`: registre del service worker i estat de càrrega global.
- `public/manifest.webmanifest` i `public/sw.js`: configuració PWA.

## Patró principal de la UI

La major part del comportament d’aplicació viu en pàgines Livewire definides com a components single-file dins de Blade. Això vol dir que la classe Livewire i la plantilla conviuen al mateix fitxer.

Exemples importants:

- `resources/views/pages/⚡shopping-list.blade.php`
- `resources/views/pages/⚡full-shopping-list.blade.php`
- `resources/views/pages/⚡master-access.blade.php`
- `resources/views/pages/settings/⚡profile.blade.php`
- `resources/views/pages/settings/⚡two-factor.blade.php`

Quan s’afegeix una funcionalitat nova relacionada amb una pantalla existent, el primer lloc a revisar és aquest fitxer Livewire. Si la nova funcionalitat és una extensió natural d’una pantalla actual, la convenció del projecte és ampliar aquest component abans de crear estructures noves.

## Model de domini

### `User`

- Pot pertànyer a un `UserGroup`.
- Pot tenir rol `is_master`.
- Pot activar 2FA amb Fortify.

### `UserGroup`

- Agrupa usuaris.
- Defineix l’abast compartit de les botigues.

### `Shop`

- Té un propietari (`user_id`).
- Pot estar compartida amb un grup (`user_group_id`).
- Manté `name`, `color` i `position`.
- La relació `shoppingListItems()` ja retorna els ítems ordenats per `position`.
- No es pot eliminar si té productes pendents visibles per a l’usuari que intenta esborrar-la.

### `ShoppingListItem`

- Pertany a una botiga i a l’usuari creador.
- Té `name`, `quantity`, `quantity_unit`, `visibility`, `purchased`, `purchased_at` i `position`.
- Fa servir l’enum `ShoppingListItemVisibility` per a públic/privat.
- Fa servir l’enum `ShoppingListItemQuantityUnit` per a unitats i per decidir si la quantitat admet decimals.
- Té `SoftDeletes`, així que l’eliminació és suau.
- Considera actius els productes pendents i també els comprats recentment; els comprats fa més de 7 dies deixen de participar a les vistes actives.

## Regles de negoci que no s’han de duplicar

Abans d’afegir lògica nova, comprova si la regla ja existeix en models o policies:

- `app/Models/Shop.php`
  - `scopeVisibleTo()`: decideix quines botigues veu un usuari.
  - `hasVisiblePendingItemsFor()`: controla si es pot esborrar una botiga.
  - `isVisibleTo()`: regla base de visibilitat de botigues.
- `app/Models/ShoppingListItem.php`
  - `scopeVisibleTo()`: decideix quins productes veu un usuari.
  - `scopeRelevantForList()`: manté fora de les vistes actives els comprats antics.
  - `formattedQuantity()`: centralitza el format visible de quantitats i unitats.
  - `updatePurchaseState()`: manté sincronitzats `purchased` i `purchased_at`.
  - `countsTowardActiveList()`: defineix el tall temporal dels comprats recents.
  - `isVisibleTo()`: regla base de visibilitat d’ítems.
- `app/Policies/ShopPolicy.php`
  - bloqueja creació per a `master`;
  - només permet editar i eliminar al propietari;
  - només permet reordenar botigues visibles;
  - només permet eliminar si no hi ha productes pendents visibles.
- `app/Policies/ShoppingListItemPolicy.php`
  - diferencia entre ítems públics i privats;
  - un ítem privat només el pot modificar qui l’ha creat;
  - un ítem públic el pot gestionar qualsevol usuari que vegi la botiga;
  - crear un ítem depèn de tenir accés visible a la botiga.

Si una feature nova depèn d’accés, visibilitat, compra o format de quantitats, aquestes peces són la font de veritat. No convé reproduir aquestes condicions a mà dins la UI si es poden reutilitzar via models, enums o polítiques.

## Invariants funcionals que no s’han de trencar

Quan una feature afecta ordenació, permisos, visibilitat o productes comprats, hi ha contractes de comportament que s’han de mantenir encara que la implementació canviï.

- Reordenar botigues reindexa només les botigues visibles per a l’usuari actual. La implementació principal viu a `resources/views/pages/⚡shopping-list.blade.php`.
- Reordenar ítems reindexa només els ítems visibles dins de la botiga. Un ítem privat ocult no s’ha de moure ni renumerar indirectament.
- Una botiga nova s’afegeix al final de l’ordre visible per a l’usuari que la crea.
- Un ítem nou o recomprat s’afegeix al final de l’ordre actual de la seva botiga.
- Un ítem públic el pot editar qualsevol usuari amb visibilitat sobre la botiga; un ítem privat només el creador.
- Una botiga només es pot eliminar si no té ítems pendents visibles per a l’usuari que la vol esborrar.
- Els productes comprats fa més de 7 dies deixen de comptar als totals i no reapareixen ni tan sols quan es mostren els comprats.
- Quan es mostren comprats, els pendents continuen apareixent abans que els comprats.
- Una botiga sense pendents visibles continua mostrant-se, però en estat atenuat i amb missatge de buit.
- Les suggerències de `Torna a afegir` es construeixen només amb productes visibles comprats recentment, agrupen duplicats pel nom normalitzat i no mostren productes que ja tornen a estar pendents.
- Recomprar un producte crea un ítem nou mantenint nom, quantitat, unitat i visibilitat, però el deixa pendent i sense `purchased_at`.
- El `Llistat complet` és una vista plana, no agrupada, i pot ordenar per posició de botiga o alfabèticament.
- Els filtres de botiga del `Llistat complet` treballen sobre les botigues visibles carregades a la pàgina.
- Els usuaris `master` queden fora del flux normal de compra i no poden crear botigues ni accedir al dashboard normal.

Si una feature toca algun d’aquests contractes, revisa com a mínim `tests/Feature/ShoppingListTest.php` i, segons l’abast del canvi, també `tests/Feature/FullShoppingListTest.php`, `tests/Feature/MasterAccessTest.php`, `tests/Feature/PwaSupportTest.php` o `tests/Feature/Auth/AuthenticationTest.php`.

## Flux d’autenticació

NoCompris no fa servir el login tradicional amb contrasenya com a entrada principal.

Flux actual:

1. l’usuari introdueix el correu;
2. `EmailLoginController` genera i envia un codi temporal;
3. el codi es desa en caché i l’intent pendent a sessió, incloent si s’ha demanat `remember`;
4. si el codi és correcte, l’usuari entra;
5. si té 2FA activat, passa abans pel repte de dos factors;
6. la redirecció final depèn del rol.

Fitxers clau:

- `app/Http/Controllers/Auth/EmailLoginController.php`
- `app/Http/Requests/Auth/SendLoginCodeRequest.php`
- `app/Http/Requests/Auth/VerifyLoginCodeRequest.php`
- `app/Notifications/Auth/LoginCodeNotification.php`
- `app/Providers/FortifyServiceProvider.php`

Decisions importants:

- Fortify està configurat perquè `authenticateUsing()` no faci el flux estàndard amb contrasenya.
- La redirecció després d’entrar depèn del rol:
  - usuari normal -> `dashboard`
  - usuari `master` -> `master.index`
- Els límits de peticions per login, reenviament de codi i verificació viuen a `FortifyServiceProvider`.
- El perfil pot gestionar 2FA des de `routes/settings.php`, subjecte a confirmació de contrasenya quan la feature està activa.

## On implementar cada tipus de feature

### Nova funcionalitat dins la llista de la compra

Normalment tocaràs:

- `resources/views/pages/⚡shopping-list.blade.php`
- `app/Models/Shop.php`
- `app/Models/ShoppingListItem.php`
- `app/Policies/ShopPolicy.php`
- `app/Policies/ShoppingListItemPolicy.php`
- `app/Concerns/ShoppingListValidationRules.php`
- `tests/Feature/ShoppingListTest.php`

Casos típics:

- nous camps d’una botiga o d’un producte;
- canvis en ordre, visibilitat o compra;
- canvis en estadístiques del dashboard;
- ajustos de recompra o del tractament de comprats recents.

### Nova funcionalitat de vista global

Normalment tocaràs:

- `resources/views/pages/⚡full-shopping-list.blade.php`
- `app/Models/ShoppingListItem.php`
- `app/Models/Shop.php`
- `tests/Feature/FullShoppingListTest.php`

Aquí cal mantenir la mateixa lògica de visibilitat i rellevància que al dashboard principal. El `Llistat complet` no ha de crear regles paral·leles.

### Nova funcionalitat de gestió `master`

Normalment tocaràs:

- `resources/views/pages/⚡master-access.blade.php`
- `app/Models/User.php`
- `app/Models/UserGroup.php`
- `app/Http/Middleware/EnsureMaster.php`
- `tests/Feature/MasterAccessTest.php`

Qualsevol canvi en aquesta zona ha de respectar que els usuaris no `master` reben `403` i que els `master` no entren a l’espai de compra normal.

### Nova funcionalitat d’autenticació o perfil

Normalment tocaràs:

- `app/Providers/FortifyServiceProvider.php`
- `app/Http/Controllers/Auth/EmailLoginController.php`
- `resources/views/pages/auth/*.blade.php`
- `resources/views/pages/settings/*.blade.php`
- `tests/Feature/Auth/*.php`
- `tests/Feature/Settings/*.php`

### Nova funcionalitat PWA o de càrrega global

Normalment tocaràs:

- `resources/js/app.js`
- `resources/views/partials/head.blade.php`
- `resources/views/partials/app-loading.blade.php`
- `public/manifest.webmanifest`
- `public/sw.js`
- `public/offline.html`
- `tests/Feature/PwaSupportTest.php`

## Validació i formularis

Les regles reutilitzables viuen en traits:

- `app/Concerns/ShoppingListValidationRules.php`
- `app/Concerns/ProfileValidationRules.php`
- `app/Concerns/PasswordValidationRules.php`

Abans d’afegir validació inline nova, comprova si encaixa en un trait existent. Si és una regla compartible, la convenció del projecte és centralitzar-la.

## Persistència i migracions

Taules de domini actuals:

- `user_groups`
- `users`
- `shops`
- `shopping_list_items`

Regles pràctiques:

- `shops` fa servir `position` per a l’ordre visible i `color` per accentuar la capçalera.
- `shops.user_group_id` pot ser `null`, però quan un usuari normal crea una botiga nova s’assigna al seu grup actual.
- `shopping_list_items.position` governa l’ordre dins de cada botiga.
- `shopping_list_items.quantity_unit` guarda l’estat serialitzat de `ShoppingListItemQuantityUnit`.
- `shopping_list_items.visibility` guarda l’estat serialitzat de `ShoppingListItemVisibility`.
- `shopping_list_items.purchased_at` és la referència temporal per decidir si un comprat encara és rellevant.
- `shopping_list_items.deleted_at` s’utilitza per a eliminació suau.

Si una feature nova introdueix dades noves:

1. afegeix migració;
2. actualitza model i `fillable` o `casts()`;
3. revisa factory;
4. revisa tests existents que creen aquestes entitats.

## Factories i patrons útils per tests

Factories disponibles:

- `UserFactory`
  - `master()`
  - `inGroup()`
  - `withTwoFactor()`
- `ShopFactory`
  - `forGroup()`
- `ShoppingListItemFactory`
  - `asPrivate()`
  - `asWeighted()`
  - `withQuantityUnit()`

Patró recomanat:

- usa `UserGroup::factory()` per construir context compartit;
- crea usuaris en el mateix grup per provar visibilitat;
- crea un altre grup per provar aïllament;
- cobreix sempre el cas públic i el cas privat si toques `ShoppingListItem`;
- si toques quantitats, afegeix com a mínim un cas amb unitat decimal.

## Estratègia de proves

El projecte està orientat a tests funcionals amb Pest. Quan es crea o modifica una feature:

- prioritza tests a `tests/Feature/`;
- afegeix o adapta només els fitxers afectats;
- usa `php artisan test --compact` amb el fitxer o filtre mínim necessari;
- si toques comportament Livewire, la pauta actual és provar-lo amb `Livewire::test(...)`;
- si canvies text o estructura visible, comprova també el render HTTP si la pantalla ja té tests de resposta.

Mapa ràpid:

- `tests/Feature/ShoppingListTest.php`: dashboard, botigues, ítems, recompra i permisos.
- `tests/Feature/FullShoppingListTest.php`: vista global, ordenació, filtres i compra.
- `tests/Feature/MasterAccessTest.php`: panell `master`.
- `tests/Feature/Auth/AuthenticationTest.php`: entrada per correu, remember i redireccions.
- `tests/Feature/Auth/TwoFactorChallengeTest.php`: repte 2FA.
- `tests/Feature/Settings/*.php`: perfil, contrasenya i 2FA.
- `tests/Feature/PwaSupportTest.php`: manifest, service worker i recursos PWA.
- `tests/Feature/DashboardTest.php`: shell principal del dashboard i navegació base.

## UI i navegació

Peces transversals útils:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/app/sidebar.blade.php`
- `resources/views/layouts/app/header.blade.php`
- `resources/views/layouts/auth/*.blade.php`

Regles pràctiques:

- si una feature nova necessita navegació persistent, revisa sidebar i header;
- si és un flux d’autenticació, revisa els layouts d’`auth`;
- la UI actual està pensada per ús directe i compacte, especialment en mòbil;
- el dashboard i el `Llistat complet` comparteixen llenguatge visual i estat de càrrega global;
- qualsevol canvi PWA ha d’anar coordinat amb `resources/js/app.js`, `public/sw.js`, `public/manifest.webmanifest` i `tests/Feature/PwaSupportTest.php`.

## Checklist abans de començar una feature

1. Identificar si afecta espai normal, espai `master`, auth, settings o PWA.
2. Localitzar la pantalla Livewire o controlador responsable.
3. Confirmar la regla d’accés a models i policies.
4. Reutilitzar traits de validació, enums i factories existents.
5. Actualitzar o crear el test funcional mínim.
6. Executar només els tests afectats.

## Checklist abans de donar-la per tancada

1. La lògica de negoci viu a models, policies o classes compartides, no només a la vista.
2. La feature respecta la separació entre usuari normal i usuari `master`.
3. La visibilitat per grup, la distinció públic/privat i el tractament dels comprats recents continuen sent coherents.
4. Els tests afectats passen.
5. Si el canvi toca auth, redireccions o PWA, també s’han revisat els fluxos transversals associats.
