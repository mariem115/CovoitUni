---
name: CovoitUni App Flow
overview: "Restructure the CovoitUni app flow: guest-only home page with redirect for logged-in users, role radio buttons on login (session-based via DB overwrite), university dropdown on registration, and strict role-based access control."
todos:
  - id: home-redirect
    content: "HomeController: redirect logged-in users to their dashboard from /"
    status: pending
  - id: login-radio
    content: "Login template: add passager/conducteur radio buttons"
    status: pending
  - id: login-handler
    content: "LoginSuccessHandler: read _role POST param, update user roles in DB"
    status: pending
  - id: university-data
    content: "LocationData: add Tunisian universities list and getUniversitiesChoiceMap()"
    status: pending
  - id: registration-university
    content: "RegistrationFormType + ProfileType: change university to ChoiceType dropdown"
    status: pending
  - id: security-roles
    content: "security.yaml: tighten access_control by ROLE_CONDUCTEUR / ROLE_PASSAGER"
    status: pending
  - id: nav-roles
    content: "base.html.twig: show role-appropriate nav links"
    status: pending
isProject: false
---

# CovoitUni App Flow Restructure

## Overview

5 focused changes to implement the full app flow as described. The role chosen at login is written to the user's DB record each time they log in — this is the cleanest way to make Symfony's security system (access_control, IsGranted, voters) work correctly while still allowing role switching on each login.

---

## 1. Home page — redirect logged-in users

**File:** `[src/Controller/HomeController.php](src/Controller/HomeController.php)`

Add a check at the top of `index()`: if the user is already authenticated, redirect to their dashboard.

```php
if ($this->getUser()) {
    return $this->redirectToRoute('app_profile_my'); // LoginSuccessHandler handles the correct target
}
```

The home page already has marketing content (hero, stats, recent trips, how-it-works). No template changes needed.

---

## 2. Login page — role radio buttons

**File:** `[templates/auth/login.html.twig](templates/auth/login.html.twig)`

Add two radio buttons inside the login form, **only shown when the user is not admin** (we can't know before they log in, so they are always shown but the admin just ignores them):

```html
<div class="mb-3">
  <label class="form-label">Je me connecte en tant que :</label>
  <div class="form-check">
    <input
      class="form-check-input"
      type="radio"
      name="_role"
      value="passager"
      id="rolePassager"
      checked
    />
    <label class="form-check-label" for="rolePassager">Étudiant passager</label>
  </div>
  <div class="form-check">
    <input
      class="form-check-input"
      type="radio"
      name="_role"
      value="conducteur"
      id="roleConducteur"
    />
    <label class="form-check-label" for="roleConducteur"
      >Étudiant conducteur</label
    >
  </div>
</div>
```

**File:** `[src/Security/LoginSuccessHandler.php](src/Security/LoginSuccessHandler.php)`

Inject `EntityManagerInterface`. After authentication:

- If user has `ROLE_ADMIN` → skip role update, redirect to `/admin`
- Otherwise → read `$request->request->getString('_role')`, set `ROLE_PASSAGER` or `ROLE_CONDUCTEUR` on the user, flush to DB, then redirect

```php
$intended = $request->request->getString('_role', 'passager');
$newRole = $intended === 'conducteur' ? 'ROLE_CONDUCTEUR' : 'ROLE_PASSAGER';
$user->setRoles(['ROLE_USER', $newRole]);
$this->entityManager->flush();
```

---

## 3. Registration — university dropdown

**Files:** `[src/Form/RegistrationFormType.php](src/Form/RegistrationFormType.php)`, `[src/Form/ProfileType.php](src/Form/ProfileType.php)`, `[src/Service/LocationData.php](src/Service/LocationData.php)`

Add a `UNIVERSITIES` constant to `LocationData` with all Tunisian universities (grouped by region, similar to `VILLES`).

Change the `university` field in **both** forms from `TextType` to `ChoiceType`:

```php
->add('university', ChoiceType::class, [
    'label' => 'Université',
    'choices' => LocationData::getUniversitiesChoiceMap(),
    'placeholder' => '-- Choisir votre université --',
    'required' => false,
])
```

---

## 4. Role-based access control

**File:** `[config/packages/security.yaml](config/packages/security.yaml)`

Update `access_control` to restrict routes by role:

```yaml
- { path: ^/trajets/nouveau, roles: ROLE_CONDUCTEUR }
- { path: ^/mes-trajets, roles: ROLE_CONDUCTEUR }
- { path: ^/reserver, roles: ROLE_PASSAGER }
- { path: ^/mes-reservations, roles: ROLE_PASSAGER }
- { path: ^/noter, roles: ROLE_PASSAGER }
```

---

## 5. Navigation — role-aware links

**File:** `[templates/base.html.twig](templates/base.html.twig)`

Wrap nav links in role checks so each user type only sees relevant actions:

- Conducteur sees: "Mes trajets", "Proposer un trajet"
- Passager sees: "Rechercher un trajet", "Mes réservations"
- Both see: "Mon profil"
- Guest sees: "Connexion", "S'inscrire"

```twig
{% if is_granted('ROLE_CONDUCTEUR') %}
  <a href="{{ path('app_trip_new') }}">Proposer un trajet</a>
  <a href="{{ path('app_profile_trips') }}">Mes trajets</a>
{% elseif is_granted('ROLE_PASSAGER') %}
  <a href="{{ path('app_trip_index') }}">Rechercher un trajet</a>
  <a href="{{ path('app_reservation_my') }}">Mes réservations</a>
{% endif %}
```

---

## Summary of files to change

- `src/Controller/HomeController.php` — redirect logged-in users
- `src/Security/LoginSuccessHandler.php` — read `_role`, update user roles
- `templates/auth/login.html.twig` — add radio buttons
- `src/Form/RegistrationFormType.php` — university dropdown
- `src/Form/ProfileType.php` — university dropdown
- `src/Service/LocationData.php` — add universities list
- `config/packages/security.yaml` — tighten access_control by role
- `templates/base.html.twig` — role-aware navigation
