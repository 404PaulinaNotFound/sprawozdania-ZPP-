# Etap 5 – System postaci i Panel Mistrza Gry

## Cel etapu
W tym etapie do aplikacji dodano pełny system zarządzania postaciami RPG oraz Panel Mistrza Gry (MG), który pozwala na moderację nowych postaci, zatwierdzanie umiejętności i rozpatrywanie zgłoszeń.

## Co nowego w tym etapie

### Dodane pliki
- `app/src/modules/character_view.php` – szczegółowy widok postaci z kartami: statystyki, ekwipunek, odznaki
- `app/src/modules/character_skills.php` – formularz zgłaszania umiejętności przez gracza
- `app/src/modules/mg_panel.php` – panel MG: akceptacja postaci, zatwierdzanie umiejętności, moderacja

### Zaktualizowane pliki
- `app/src/index.php` – dodano routing do nowych modułów: `character_view`, `characters`, `mg_panel`, `mg_characters`, `mg_skills`, `mg_reports`

## Nowe funkcjonalności

### Karty postaci
- Statystyki: Siła, Zręczność, Inteligencja, Charyzma, Witalnosc
- System poziomów i doświadczenia (XP) oraz Punktów Historii (PH)
- Ekwipunek – dodawanie, zakładanie i zdejmowanie przedmiotów
- Odznaki/Osiągnięcia – nagrody za udział w grze

### Umiejętności
- Gracz może zgłosić nową umiejętność (nazwa, opis, poziom 1–10)
- Umiejętność czeka na zatwierdzenie przez MG
- Po akceptacji gracz otrzymuje powiadomienie

### Panel Mistrza Gry
- Dashboard z licznikami: postacie, umiejętności, misje, zgłoszenia
- Akceptacja lub odrzucanie nowych postaci (z powodem)
- Zatwierdzanie lub odrzucanie umiejętności
- Przegląd i rozpatrywanie zgłoszeń od graczy
- Ochrona CSRF na wszystkich formularzach panelu

## Uruchomienie

```bash
# Upewnij się, że działa Docker z poprzedniego etapu
cd etap5
docker compose up -d --build
```

Następnie zaloguj się na konto admina i przejdź do:
- `?action=characters` – tworzenie postaci (jako gracz)
- `?action=mg_panel` – panel MG (wymaga roli `mg` lub `admin`)

## Powiązanie z poprzednim etapem

Etap 5 rozbudowuje aplikację z Etapu 4. Forum działa nadal – dodajemy warstwę RPG: postacie i moderację MG.

## Stan aplikacji po etapie

- [x] Rejestracja i logowanie (Etap 2–3)
- [x] Forum z wątkami i postami (Etap 4)
- [x] Postacie z kartami, ekwipunkiem i odznakam
- [x] System umiejętności z akceptacją MG
- [x] Panel Mistrza Gry
- [ ] Wydarzenia, misje, wiadomości (Etap 6)
- [ ] Panel admina, bezpieczeństwo (Etap 7)
