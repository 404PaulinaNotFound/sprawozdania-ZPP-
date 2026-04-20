# Etap 4 – Router aplikacji i system forum

## Cel etapu

Czwarty etap obejmował implementację głównego routera aplikacji (`index.php`) oraz pełnego modułu forum. Forum jest sercem systemu PBF — to tu gracze prowadzą rozgrywkę tekstową.

## Zakres prac

- `index.php` – główny router, layout HTML, menu nawigacyjne, obsługa rejestracji/logowania
- `modules/forum_complete.php` – kompletny system forum
- `modules/threads.php` – lista wątków
- `modules/thread_view.php` – widok pojedynczego wątku z postami
- `middle_earth_theme.css` – motyw graficzny (Bootstrap 5 + custom CSS)

## Router `index.php`

Główny plik aplikacji pełni rolę routera. Pobiera parametr `?action=` z URL i włącza odpowiedni moduł. Zarządza sesją, weryfikuje tokeny CSRF dla POST, obsługuje rejestracię, logowanie i wylogowanie bezpośrednio.

### Obsługiwane akcje

| Akcja | Opis |
|-------|------|
| `(brak)` | Strona główna z listą for i ogłoszeniami |
| `login` / `logout` | Logowanie i wylogowanie |
| `register` | Rejestracja z weryfikacją email |
| `verify` | Weryfikacja adresu email tokenem |
| `forgot_password` / `reset_password` | Reset hasła |
| `forum` | Lista kategorii i forów |
| `threads` | Lista wątków w forum |
| `thread_view` | Widok wątku z postami |
| `forum_search` | Wyszukiwanie pełnotekstowe |
| `profile` | Profil użytkownika |
| `characters` | Zarządzanie postaciami |
| `mg_panel` | Panel Mistrza Gry |
| `admin_panel` | Panel Administratora |
| `events` / `missions` | Wydarzenia i misje |
| `leaderboard` | Ranking |
| `messages` / `notifications` | Komunikacja |
| `lore` | Wiedza o świecie |

## Moduł forum (`forum_complete.php`)

### Wyszukiwanie (`forum_search`)

Używa LIKE search na tytule wątków i treści postów. Wyniki są sortowane po `updated_at DESC`. Formularz wysyła zapytanie GET z parametrem `q`.

### Lista wątków (`threads`)

Pobiera wątki z danego forum (parametr `forum_id`). Wyświetla liczbę postów, datę ostatniego posta, przypinanie i blokowanie. Umożliwia tworzenie nowego wątku przez modal Bootstrap (tytuł + treść + tagi).

### Widok wątku (`thread_view`)

Główna funkcjonalność forum:
- Wyświetla wszystkie posty chronologicznie z datą, autorem i odznaką roli
- Obsługuje **cytowanie postów** (pole `quoted_post_id`, podgląd cytatu przed odpowiedzią)
- **Edycja postów** z polem powodu edycji i historią zmian (`edited`, `edit_reason`)
- Formularz odpowiedzi dostępny tylko dla zalogowanych użytkowników
- Zablokowane wątki wyświetlają alert, nie pokazują formularza
- Zwiększanie licznika `view_count` przy każdym wejściu

## Bezpieczeństwo na tym etapie

- Każdy POST weryfikuje token CSRF
- Treść postów jest sanityzowana przez `sanitize()` (XSS protection)
- Edycja posta wymaga sprawdzenia `author_id == user.id` lub roli admina
- Prepared statements dla wszystkich zapytań SQL

## Pliki w tym etapie

- `app/src/index.php` (router + layout)
- `app/src/modules/forum_complete.php`
- `app/src/modules/threads.php`
- `app/src/modules/thread_view.php`
- `app/src/middle_earth_theme.css`

## Wynik etapu

Aplikacja jest już w pełni działająca jako forum. Użytkownik może się zarejestrować, zalogować, tworzyć wątki i pisać posty. Kolejny etap: moduł postaci i panel Mistrza Gry.
