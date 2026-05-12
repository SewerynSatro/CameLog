# CameLog – REST API

Wszystkie endpointy zwracają JSON. Autoryzacja sesyjna (cookie `CAMELOG_SID`). Wymagane pola w `application/json` lub `multipart/form-data` (upload).

## Format błędu

```json
{ "error": "Komunikat", "errors": { "pole": "..." } }
```

Kody:
- `400` – błąd żądania
- `401` – brak sesji
- `403` – brak uprawnień
- `404` – brak zasobu
- `422` – walidacja
- `500` – błąd serwera

## Auth

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| POST   | /api/auth/register | Rejestracja `{name, email, password, password_confirm}` |
| POST   | /api/auth/login    | `{email, password}` → `{user}` |
| POST   | /api/auth/logout   | – |
| GET    | /api/auth/me       | Zwraca aktualnego użytkownika |
| PATCH  | /api/auth/profile  | `{name, email, bio?}` |
| PATCH  | /api/auth/password | `{current_password, new_password}` |

## Plants

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/plants                | Filtry: `?search=&species=&location=` |
| POST   | /api/plants                | Body: `{name, species_id?, custom_species_name?, location, planted_at, notes, watering_interval_days, fertilizing_interval_days, care_level, api_recommendations_used}` – tworzy automatyczne taski jeśli `watering_interval_days` ustawione |
| GET    | /api/plants/{id}           | Szczegóły rośliny |
| PUT    | /api/plants/{id}           | Aktualizacja |
| DELETE | /api/plants/{id}           | Kasacja kaskadowa (taski + historia) |
| POST   | /api/plants/{id}/photo     | `multipart/form-data` z polem `photo` (JPG/PNG/WebP, max 5 MB) |
| GET    | /api/plants/{id}/stats     | Liczniki akcji opieki w 30 dniach |

## Species (Perenual proxy)

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/species/search?query=... | Wyszukuje gatunki – zwraca `{source: "perenual"|"mock", results: [...]}` |
| GET    | /api/species/{id}             | Szczegóły gatunku (zewnętrzny lub lokalny) |
| POST   | /api/species/import           | Importuje gatunek do lokalnej bazy. Body: `{external_id, common_name, scientific_name, watering_info, sunlight_info, care_level, ...}` → `{species_id}` |

## Tasks

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/tasks                | Filtry: `?type=&status=&plant_id=&due=today|incoming|overdue` |
| GET    | /api/tasks/today          | Skrót dla `due=today` |
| GET    | /api/tasks/incoming       | – |
| GET    | /api/tasks/overdue        | – |
| GET    | /api/plants/{plantId}/tasks | Taski konkretnej rośliny |
| POST   | /api/plants/{plantId}/tasks | Tworzy task dla rośliny |
| POST   | /api/tasks                | Tworzy dowolny task |
| PATCH  | /api/tasks/{id}           | Edycja |
| PATCH  | /api/tasks/{id}/complete  | Oznacza jako wykonane: dodaje wpis historii, jeżeli `repeat_interval_days` jest ustawione – tworzy następny task |
| PATCH  | /api/tasks/{id}/skip      | Pomija (analogicznie do complete, bez wpisu historii) |
| DELETE | /api/tasks/{id}           | Usuwa task |

## History

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/plants/{plantId}/history | Historia opieki rośliny |
| POST   | /api/plants/{plantId}/history | Ręczne dodanie wpisu `{type, note?, performed_at?}` |

## Notifications

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/notifications              | Lista + `unread_count` |
| GET    | /api/notifications/today        | Filtr |
| GET    | /api/notifications/incoming     | Filtr |
| PATCH  | /api/notifications/{id}/read    | – |
| PATCH  | /api/notifications/read-all     | – |

## Stats

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/stats/overview | `{plants_count, tasks_today, tasks_overdue, tasks_done, tasks_done_week, watering_count, fertilizing_count, type_breakdown[], daily_activity[], top_plant}` |

## Admin (rola `admin`)

| Metoda | Ścieżka | Opis |
| ------ | ------- | ---- |
| GET    | /api/admin/users                | Lista + statystyki |
| PATCH  | /api/admin/users/{id}/block     | – |
| PATCH  | /api/admin/users/{id}/unblock   | – |
| DELETE | /api/admin/users/{id}           | – |

## Przykłady

### Logowanie

```http
POST /api/auth/login
Content-Type: application/json

{ "email": "user@camelog.pl", "password": "user123" }
```

```json
{ "user": { "id": 2, "name": "Anna Nowak", "email": "user@camelog.pl", "role": "user" } }
```

### Tworzenie rośliny z auto-taskiem

```http
POST /api/plants
Content-Type: application/json

{
  "name": "Monstera w salonie",
  "species_id": 1,
  "custom_species_name": "Monstera Deliciosa",
  "location": "Salon",
  "planted_at": "2024-03-15",
  "watering_interval_days": 7,
  "fertilizing_interval_days": 30,
  "care_level": "easy",
  "api_recommendations_used": true
}
```

Backend tworzy:
1. wpis w `plants`,
2. pierwszy task `watering` z `due_date = today + watering_interval_days`,
3. jeśli `fertilizing_interval_days` – task `fertilizing`.

### Wykonanie taska z powtarzaniem

```http
PATCH /api/tasks/3/complete
Content-Type: application/json

{ "note": "Podlałam rano." }
```

Skutek:
- task: `status=done`, `completed_at=now`
- wpis w `care_history`
- nowy task: `due_date = now + repeat_interval_days`, `status=pending`
