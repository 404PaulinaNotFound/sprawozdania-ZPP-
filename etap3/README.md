# Etap 3 – Schemat bazy danych i system użytkowników

## Cel etapu

Trzeci etap polegał na zaprojektowaniu i wdrożeniu pełnego schematu bazy danych MySQL oraz stworzeniu modułu obsługi użytkowników (rejestracja, logowanie, role). Baza danych jest fundamentem całej aplikacji — wszystkie kolejne moduły będą z niej korzystać.

## Zakres prac

- Zaprojektowanie struktury tabel w `db/init.sql`
- Implementacja tabel: `users`, `characters`, `character_skills`, `character_inventory`
- Implementacja tabel forum: `categories`, `forums`, `threads`, `thread_tags`, `posts`
- Implementacja tabel komunikacji: `private_messages`, `notifications`, `reports`, `activity_logs`
- Implementacja tabel RPG: `events`, `event_participants`, `world_logs`, `missions`, `mission_participants`
- Implementacja tabel rankingów: `achievements`, `character_achievements`
- Implementacja tabel dokumentacji: `lore_pages`, `faq_entries`, `proposals`
- Dodanie danych początkowych (użytkownicy testowi, kategorie, odznaki, FAQ)

## Tabela `users` – kluczowe pola

| Pole | Typ | Opis |
|------|-----|------|
| `id` | INT AUTO_INCREMENT | Klucz główny |
| `username` | VARCHAR(50) UNIQUE | Nazwa użytkownika |
| `email` | VARCHAR(100) UNIQUE | Adres e-mail |
| `password` | VARCHAR(255) | Hash bcrypt |
| `role` | ENUM('player','mg','admin') | Rola użytkownika |
| `approved` | BOOLEAN | Akceptacja przez admina |
| `email_verified` | BOOLEAN | Weryfikacja emaila |
| `verification_token` | VARCHAR(64) | Token do weryfikacji |
| `reset_token` | VARCHAR(64) | Token do resetu hasła |
| `last_activity` | TIMESTAMP | Ostatnia aktywność (online status) |

## Tabela `characters` – mechanika RPG

Każdy użytkownik może posiadać wiele postaci. Postaci mają statystyki RPG (Siła, Zręczność, Inteligencja, Charyzma, Witalnosc), system poziomów (level, experience), Punkty Historii (history_points) oraz status (active/inactive/dead). Postać wymaga akceptacji przez Mistrza Gry (`approved_by_mg`).

## Architektura forum

Struktura forum jest czteropoziomowa:
```
Categories (kategorie)
  └── Forums (fora)
        └── Threads (wątki)
              └── Posts (posty)
```
Każdy poziom może mieć ograniczenia dostępu (`access_role`). Wyszukiwanie pełnotekstowe jest włączone przez indeksy FULLTEXT na polach `title` (threads) i `content` (posts).

## Dane początkowe

Skrypt tworzy:
- 3 użytkowników testowych: `admin`, `mg`, `testplayer` (hasło: `$$$`)
- 4 kategorie forum (Ogólne, Fabuła, Panel MG, Administracja)
- 5 forów
- 4 odznaki/osiągnięcia
- 3 wpisy FAQ

## Pliki w tym etapie

- `db/init.sql` – pełny schemat bazy danych z danymi początkowymi
- `db/dockerfile` – obraz Docker dla MySQL
- `db/my.cnf` – konfiguracja MySQL

## Wynik etapu

Baza danych jest w pełni zdefiniowana i gotowa. Kolejny etap to napisanie warstwy PHP — konfiguracji połączenia, funkcji pomocniczych i routera.
