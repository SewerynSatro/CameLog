# CameLog – architektura

## Warstwy

```
[ Przeglądarka / PWA ]
        │  fetch (cookie sesji)
        ▼
[ public/index.php ]  ← Front Controller
        │
        ├──> /api/* ──> Router → Middleware → Controller → Service → Repository → PDO
        │
        └──> /        ──> serwowanie SPA (public/index.html)
```

## Backend

- **Front Controller**: `public/index.php`. Autoloader PSR-4 dla `App\\`. CORS, sesja, dispatch.
- **Router** (`app/Core/Router.php`): regex z `{param}`, lista middleware, dispatch.
- **Auth**: sesja PHP, hasło `password_hash` (bcrypt). `AuthMiddleware` weryfikuje sesję i sprawdza `status != blocked`. `AdminMiddleware` wymaga roli `admin`.
- **Validator** (`app/Core/Validator.php`): proste reguły `required|email|min|max|in`.
- **Repository pattern**: każda tabela ma swoje `*Repository` z metodami CRUD i wyspecjalizowanymi zapytaniami (np. `TaskRepository::statsForUser`, `NotificationRepository::refreshTaskNotifications`).
- **Service layer**: logika biznesowa ponad repozytoriami (np. `TaskService::complete` zapisuje historię i tworzy następny task; `PlantService::create` tworzy automatyczne taski).
- **`SpeciesApiService`**: integracja z Perenual API. Brak klucza → tryb mock z 7 popularnymi gatunkami. Klucz nigdy nie opuszcza backendu.

## Frontend (SPA)

- `public/index.html` – shell.
- `assets/js/api.js` – fetch helper, `same-origin` cookie, JSON / FormData.
- `assets/js/router.js` – History API, regex routing, ochrona auth/admin.
- `assets/js/ui.js` – ikony SVG inline, toast, modal `confirm()`, render shell (sidebar + topbar + bottom-nav), helpery (`taskTypeLabel`, `formatRelative`).
- Widoki: `auth.js`, `plants.js`, `plant-form.js`, `tasks.js`, `notifications.js`, `stats.js`, `admin.js`.
- `app.js` – Dashboard inline + rejestracja tras + bootstrap.

## PWA

- Manifest: nazwy, ikony 72-512, kolor motywu.
- Service Worker (`/service-worker.js`):
  - **install** – cache asset list (HTML, CSS, JS, ikony).
  - **activate** – usunięcie nieaktualnych cache.
  - **fetch** – dispatcher:
    - `/api/*` → network-first
    - `mode === 'navigate'` → network-first, fallback `/offline.html`
    - statyka → cache-first
- `/offline.html` – samodzielnie ostylowany, automatyczny reload po wznowieniu połączenia.

## Bezpieczeństwo

- Sesje PHP, ciasteczko HttpOnly.
- Hasła: `password_hash` (BCRYPT, koszt domyślny 10), weryfikacja `password_verify`.
- PDO z prepared statements wszędzie.
- `AdminController::block/destroy` blokuje operacje na własnym koncie.
- Upload: walidacja MIME + rozmiar (5 MB), losowe nazwy plików (`bin2hex(random_bytes())`).
- Klucz Perenual API tylko w `.env`, czytany przez `SpeciesApiService`.

## Logika domenowa – kluczowe ścieżki

### Dodanie rośliny (z auto-taskiem)

1. `PlantController::store` → `PlantService::create($userId, $data)`
2. INSERT do `plants`
3. Jeśli `watering_interval_days` → INSERT task `watering` (`due = today + interval`)
4. Jeśli `fertilizing_interval_days` → INSERT task `fertilizing`
5. Zwrot rośliny.

### Wykonanie taska

1. `TaskController::complete` → `TaskService::complete($taskId, $userId, $note)`
2. UPDATE `care_tasks` (status=done, completed_at=now)
3. INSERT `care_history` (kopia typu + ewentualna notatka)
4. Jeżeli `repeat_interval_days` ustawione: INSERT nowy task `pending` z `due = now + repeat`
5. Po stronie frontu po sukcesie wywołujemy `Router.resolve()` aby odświeżyć widok.

### Powiadomienia

1. `NotificationController::index` → `ReminderService::listForUser`
2. `ReminderService` najpierw odświeża powiadomienia z aktualnych tasków:
   - taski `pending` z `due_date < today` → typ `overdue`
   - taski `pending` z `due_date = today` → typ `today`
   - taski `pending` z `due_date in (today, today+3]` → typ `incoming`
3. Stare powiadomienia tej samej kategorii, których task już nie jest w stanie pending, usuwa.
4. Powiadomienia systemowe (`type=system`) zostają nietknięte.

## Skalowalność

- Indeksy na `users(status, role)`, `plants(user_id, health_status)`, `care_tasks(user_id, status)`, `care_tasks(due_date)`, `care_history(plant_id, performed_at)`.
- Sesja w plikach (domyślnie); dla skali >1 instancji można podpiąć Redis przez `session_handler`.
- API jest stateless po stronie aplikacyjnej (poza sesją w cookie) – łatwo umieścić za reverse proxy (Nginx / load balancer).
