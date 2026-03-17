# Guia tècnica per desenvolupar noves funcionalitats

Aquest document serveix com a guia ràpida per entendre l’arquitectura actual de NoCompris i per afegir funcionalitats noves sense duplicar lògica ni trencar comportaments existents.

## Objectiu

Quan es treballi en una nova funcionalitat, aquest document ha de servir per respondre tres preguntes:

1. on viu avui la lògica relacionada;
2. quina és la font de veritat del comportament;
3. quines proves cal tocar per validar el canvi.

## Stack i estructura base

- Backend: Laravel 12 amb PHP 8.4.
- Autenticació: Laravel Fortify amb flux d’entrada personalitzat per correu i codi.
- UI reactiva: Livewire 4.
- Components visuals: Flux UI.
- Estils: Tailwind CSS v4.
- Tests: Pest 4 amb `RefreshDatabase` als tests de `Feature`.
- Persistència per defecte: SQLite en local segons `.env.example`.

Punts d’entrada principals del projecte:

- `routes/web.php`: rutes web principals i separació entre espai normal i espai `master`.
- `routes/settings.php`: configuració i preferències de l’usuari.
- `bootstrap/app.php`: registre de routing, middleware i excepcions a Laravel 12.
- `app/Providers/FortifyServiceProvider.php`: configuració de Fortify, vistes, rate limits i redireccions post-login.
- `resources/views/pages/`: pàgines Livewire principals.
- `app/Models/`, `app/Policies/`, `app/Concerns/`: domini, permisos i regles compartides.
- `tests/Feature/`: cobertura funcional principal.

## Patró principal de la UI

La major part del comportament d’aplicació viu en pàgines Livewire definides com a components "single-file" dins de Blade. Això vol dir que la classe Livewire i la plantilla conviuen al mateix fitxer.

Exemples importants:

- `resources/views/pages/⚡shopping-list.blade.php`
- `resources/views/pages/⚡full-shopping-list.blade.php`
- `resources/views/pages/⚡master-access.blade.php`

Quan s’afegeix una funcionalitat nova relacionada amb una pantalla existent, el primer lloc a revisar és aquest fitxer Livewire. Si la nova funcionalitat és una extensió natural d’una pantalla actual, la convenció del projecte és ampliar aquest component abans de crear estructures noves.

## Model de domini

### `User`

- Pot pertànyer a un `UserGroup`.
- Pot tenir rol `is_master`.
- Pot tenir 2FA activat amb Fortify.

### `UserGroup`

- Agrupa usuaris.
- També defineix l’abast compartit de les botigues.

### `Shop`

- Té un propietari (`user_id`).
- Pot estar compartida amb un grup (`user_group_id`).
- Es mostra segons visibilitat per usuari.
- Manté ordre propi amb `position`.
- No es pot eliminar si té productes pendents visibles per a l’usuari que intenta esborrar-la.

### `ShoppingListItem`

- Pertany a una botiga i a l’usuari creador.
- Té `quantity`, `visibility`, `purchased` i `position`.
- La `visibility` es governa amb l’enum `ShoppingListItemVisibility`.

## Regles de negoci que no s’han de duplicar

Abans d’afegir lògica nova, comprova si la regla ja existeix en models o policies:

- `app/Models/Shop.php`
  - `scopeVisibleTo()`: decideix quines botigues veu un usuari.
  - `hasVisiblePendingItemsFor()`: controla si es pot esborrar una botiga.
  - `isVisibleTo()`: regla base de visibilitat de botigues.
- `app/Models/ShoppingListItem.php`
  - `scopeVisibleTo()`: decideix quins productes veu un usuari.
  - `isVisibleTo()`: regla base de visibilitat d’ítems.
- `app/Policies/ShopPolicy.php`
  - bloqueja creació per a `master`;
  - només permet editar i eliminar al propietari;
  - només permet eliminar si no hi ha productes pendents visibles.
- `app/Policies/ShoppingListItemPolicy.php`
  - diferencia entre ítems públics i privats;
  - un ítem privat només el pot modificar qui l’ha creat;
  - un ítem públic el pot gestionar qualsevol usuari que vegi la botiga.

Si una feature nova depèn d’accés o visibilitat, aquestes peces són la font de veritat. No convé reproduir aquestes condicions a mà dins la UI si es poden reutilitzar via polítiques o scopes.

## Invariants funcionals que no s’han de trencar

Quan una feature afecta ordenació, permisos o visibilitat, hi ha contractes de comportament que s’han de mantenir encara que la implementació canviï.

- Reordenar botigues reindexa només les botigues visibles per a l’usuari actual. La implementació principal viu a `resources/views/pages/⚡shopping-list.blade.php`.
- Reordenar ítems reindexa només els ítems visibles dins de la botiga. Un ítem privat ocult no s’ha de moure ni renumerar indirectament. Aquest comportament es valida a `tests/Feature/ShoppingListTest.php`.
- Una botiga nova s’afegeix al final de l’ordre visible per a l’usuari que la crea. La lògica actual viu a `resources/views/pages/⚡shopping-list.blade.php`.
- Un ítem nou s’afegeix al final de l’ordre actual de la seva botiga. La lògica actual viu a `resources/views/pages/⚡shopping-list.blade.php`.
- Un ítem públic el pot editar qualsevol usuari amb visibilitat sobre la botiga; un ítem privat només el creador. La font de veritat és `app/Policies/ShoppingListItemPolicy.php`.
- Una botiga només es pot eliminar si no té ítems pendents visibles per a l’usuari que la vol esborrar. La font de veritat és `app/Models/Shop.php`.
- Els usuaris `master` queden fora del flux normal de compra i no poden crear botigues des d’aquesta UI. Aquest comportament queda cobert pels fluxos de `tests/Feature/ShoppingListTest.php`, `tests/Feature/MasterAccessTest.php` i `tests/Feature/Auth/AuthenticationTest.php`.

Si una feature toca algun d’aquests contractes, revisa com a mínim `tests/Feature/ShoppingListTest.php` i, segons l’abast del canvi, també `tests/Feature/FullShoppingListTest.php`, `tests/Feature/MasterAccessTest.php` o `tests/Feature/Auth/AuthenticationTest.php`. Per a visibilitat i permisos, `app/Models/Shop.php` i `app/Policies/ShoppingListItemPolicy.php` continuen sent la font de veritat.

## Flux d’autenticació

NoCompris no fa servir el login tradicional amb contrasenya com a entrada principal.

Flux actual:

1. l’usuari introdueix el correu;
2. `EmailLoginController` genera i envia un codi temporal;
3. el codi es desa en caché i l’intent pendent a sessió;
4. si el codi és correcte, l’usuari entra;
5. si té 2FA activat, passa abans pel repte de dos factors.

Fitxers clau:

- `app/Http/Controllers/Auth/EmailLoginController.php`
- `app/Http/Requests/Auth/SendLoginCodeRequest.php`
- `app/Http/Requests/Auth/VerifyLoginCodeRequest.php`
- `app/Notifications/Auth/LoginCodeNotification.php`
- `app/Providers/FortifyServiceProvider.php`

Decisions importants:

- Fortify està configurat perquè `authenticateUsing()` no faci el flux estàndard.
- La redirecció després d’entrar depèn del rol:
  - usuari normal -> `dashboard`
  - usuari `master` -> `master.index`
- Els límits de peticions per login, reenviament de codi i verificació viuen a `FortifyServiceProvider`.

Si una funcionalitat nova afecta accés, verificació o redireccions, aquest és el punt correcte d’entrada.

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
- noves accions sobre productes;
- canvis en ordre, visibilitat o compra;
- restriccions noves de negoci.

### Nova funcionalitat de vista global

Normalment tocaràs:

- `resources/views/pages/⚡full-shopping-list.blade.php`
- `app/Models/ShoppingListItem.php`
- `tests/Feature/FullShoppingListTest.php`

Aquí cal mantenir la coherència amb la mateixa lògica de visibilitat del dashboard principal.

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

- `shops` i `shopping_list_items` fan servir `position` per a l’ordre.
- `shopping_list_items.visibility` guarda l’estat serialitzat de l’enum.
- `shops.user_group_id` pot ser `null`, però quan un usuari normal crea una botiga nova s’assigna al seu grup actual.

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

Patró recomanat:

- usa `UserGroup::factory()` per construir context compartit;
- crea usuaris en el mateix grup per provar visibilitat;
- crea un altre grup per provar aïllament;
- cobreix sempre el cas públic i el cas privat si toques `ShoppingListItem`.

## Estratègia de proves

El projecte ja està orientat a tests funcionals amb Pest. Quan es crea o modifica una feature:

- prioritza tests a `tests/Feature/`;
- afegeix o adapta només els fitxers afectats;
- usa `php artisan test --compact` amb el fitxer o filtre mínim necessari;
- si toques comportament Livewire, la pauta actual és provar-lo amb `Livewire::test(...)`;
- si canvies text o estructura visible, comprova també el render HTTP si la pantalla ja té tests de resposta.

Mapa ràpid:

- `tests/Feature/ShoppingListTest.php`: dashboard i lògica principal de compra.
- `tests/Feature/FullShoppingListTest.php`: vista global.
- `tests/Feature/MasterAccessTest.php`: panell `master`.
- `tests/Feature/Auth/AuthenticationTest.php`: entrada per correu i redireccions.
- `tests/Feature/Auth/TwoFactorChallengeTest.php`: repte 2FA.
- `tests/Feature/Settings/*.php`: perfil, contrasenya i 2FA.
- `tests/Feature/PwaSupportTest.php`: manifest, service worker i recursos PWA.

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
- ja existeix registre del service worker a `resources/js/app.js`, així que qualsevol canvi PWA ha d’anar coordinat amb `public/sw.js`, `public/manifest.webmanifest` i `tests/Feature/PwaSupportTest.php`.

## Checklist abans de començar una feature

1. Identificar si afecta espai normal, espai `master`, auth o settings.
2. Localitzar la pantalla Livewire o controlador responsable.
3. Confirmar la regla d’accés a models i policies.
4. Reutilitzar traits de validació i factories existents.
5. Actualitzar o crear el test funcional mínim.
6. Executar només els tests afectats.

## Checklist abans de donar-la per tancada

1. La lògica de negoci viu a models, policies o classes compartides, no només a la vista.
2. La feature respecta la separació entre usuari normal i usuari `master`.
3. La visibilitat per grup i per tipus d’ítem continua sent coherent.
4. Els tests afectats passen.
5. Si el canvi toca auth, redireccions o PWA, també s’han revisat els fluxos transversals associats.

## Notes finals

Si en el futur el projecte creix, aquesta guia es pot dividir en documents més específics per:

- arquitectura i domini;
- autenticació;
- UI i components Livewire;
- estratègia de testing.

Mentrestant, aquest fitxer ha de ser la referència tècnica curta per orientar qualsevol funcionalitat nova.
