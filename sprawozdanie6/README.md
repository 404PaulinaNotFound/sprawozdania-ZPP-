# Etap 6 – Funkcje społecznościowe i treści świata

## Cel etapu

W tym etapie rozbudowano aplikację o pełen zestaw funkcji społecznościowych: system wydarzeń fabularnych, wiadomości prywatne, powiadomienia, misje, ranking graczy i postaci, wiedzę o świecie (lore + FAQ), historię świata oraz system zgłoszeń do moderacji. Po tym etapie gracze mają pełne narzędzia do komunikacji i interakcji w ramach gry.

## Dodane pliki

| Plik | Opis |
|------|------|
| `app/src/modules/events.php` | Kalendarz wydarzeń fabularnych – tworzenie (MG), przeglądanie i zapisywanie postaci na eventy |
| `app/src/modules/missions.php` | System misji – tworzenie, akceptacja, przypisywanie do postaci i punktowanie |
| `app/src/modules/messages.php` | Wiadomości prywatne między graczami – skrzynka odbiorcza, wysyłanie |
| `app/src/modules/notifications.php` | Powiadomienia systemowe – nowe posty, akceptacje, odpowiedzi |
| `app/src/modules/leaderboard.php` | Publiczny ranking – top postaci (XP), top graczy (aktywność), lista odznak |
| `app/src/modules/lore.php` | Wiedza o świecie (lore) i FAQ – przeglądanie i zarządzanie przez MG |
| `app/src/modules/world_logs.php` | Logi światowe – chronologiczna historia ważnych wydarzeń fabularnych |
| `app/src/modules/report_system.php` | System zgłaszania treści do moderacji |

## Nowe funkcjonalności

### Kalendarz wydarzeń (`events.php`)
- MG/Admin może tworzyć eventy z typem (wojna, misja, turniej, festiwal)
- Gracze zapisują swoje zatwierdzone postacie na eventy
- Automatyczny limit uczestników, statusy: `upcoming`, `active`, `completed`
- Wyświetlanie ilości uczestników i dat trwania

### Misje (`missions.php`)
- MG tworzy misje i przypisuje je do postaci
- Akceptacja i punktowanie ukończonych misji
- Widok listy misji dla gracza

### Wiadomości prywatne (`messages.php`)
- Skrzynka odbiorcza z oznaczeniem przeczytane/nieprzeczytane
- Formularz nowej wiadomości do wybranego gracza
- Licznik nieprzeczytanych w nawigacji

### Powiadomienia (`notifications.php`)
- Automatyczne generowanie powiadomień przy akceptacji postaci, odpowiedziach na posty, nowych wiadomościach
- Oznaczanie jako przeczytane
- Wyświetlanie badge z liczbą nieprzeczytanych

### Ranking (`leaderboard.php`)
- **Zakładka Postaci** – Top 50 wg doświadczenia (XP), poziom, Punkty Historii, odznaki
- **Zakładka Gracze** – Top 50 wg liczby postów, wątków i postaci
- **Zakładka Odznaki** – wszystkie dostępne odznaki pogrupowane kategoriami (Walka, Społeczność, Eksploracja, Specjalne)

### Lore i FAQ (`lore.php`)
- MG dodaje strony lore z kategoriami (Historia, Geografia, itp.)
- Możliwość ukrycia stron przed graczami (tylko MG)
- Sekcja FAQ z pytaniami w układzie accordion

### Historia świata (`world_logs.php`)
- Chronologiczna oś czasu ważnych wydarzeń fabularnych
- MG dodaje wpisy z datą wydarzeń w fabule
- Publiczny widok dla wszystkich graczy

### System zgłoszeń (`report_system.php`)
- Gracz może zgłosić dowolną treść (post, wątek, użytkownik) do moderacji
- Zgłoszenie trafia do kolejki w panelu admina/MG
- Obsługa różnych typów zgłoszeń przez jeden formularz

## Stan aplikacji po etapie 6

Aplikacja posiada teraz **kompletny zestaw modułów** poza panelem administratora:

```
✅ Autoryzacja (rejestracja, logowanie, role)
✅ Forum (kategorie, wątki, posty, cytowanie, wyszukiwanie)
✅ Postacie (karty, statystyki, ekwipunek, odznaki)
✅ Panel MG (akceptacje, misje, XP, eventy)
✅ Wydarzenia fabularne + Misje
✅ Wiadomości prywatne + Powiadomienia
✅ Ranking + Lore + Historia świata
✅ System zgłoszeń do moderacji
❌ Panel Administratora (→ Etap 7)
❌ Audyt bezpieczeństwa (→ Etap 7)
```

## Uruchomienie

Przed uruchomieniem upewnij się, że masz gotowy etap 5 (baza danych, postacie, panel MG).

```bash
cd etap6
docker compose up -d --build
# Aplikacja: http://localhost:8000
```

## Baza danych – nowe tabele (względem etapu 5)

| Tabela | Opis |
|--------|------|
| `events` | Definicje wydarzeń fabularnych |
| `event_participants` | Zapisy postaci na eventy |
| `missions` | Misje tworzone przez MG |
| `messages` | Wiadomości prywatne między użytkownikami |
| `notifications` | Powiadomienia systemowe |
| `achievements` | Definicje odznak |
| `character_achievements` | Odznaki przypisane do postaci |
| `lore_pages` | Strony wiedzy o świecie |
| `faq_entries` | Wpisy FAQ |
| `world_logs` | Historia świata |
| `reports` | Zgłoszenia do moderacji |

## Co dalej?

Etap 7 zamknie projekt: panel administratora (zarządzanie użytkownikami, forami, statystykami), motyw CSS Middle-Earth oraz pełny audyt bezpieczeństwa (OWASP Top 10).
