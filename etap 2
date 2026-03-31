# Etap 1 – Inicjalizacja projektu i konfiguracja środowiska

## Cel etapu

Pierwszy etap prac obejmował założenie struktury projektu, konfigurację środowiska uruchomieniowego (Docker) oraz przygotowanie plików konfiguracyjnych. Na tym etapie aplikacja nie posiada jeszcze żadnej logiki — tworzymy szkielet, na którym będą budowane kolejne moduły.

## Zakres prac

- Utworzenie struktury katalogów projektu (`app/`, `db/`, `app/src/`, `app/src/modules/`)
- Napisanie `docker-compose.yml` definiującego dwa serwisy: `app` (PHP 8.2 + Apache) i `db` (MySQL)
- Napisanie `Dockerfile` dla kontenera aplikacji
- Przygotowanie pliku `.env.example` z przykładową konfiguracją
- Zainicjowanie `composer.json` z zależnością PHPMailer

## Plik `.env.example`

Plik zawiera zmienne środowiskowe przekazywane do kontenerów Docker. Obejmuje dane połączenia z bazą danych (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`), dane SMTP do wysyłki emaili przez Mailtrap oraz ogólne ustawienia aplikacji (`APP_URL`, `APP_NAME`).

## Plik `docker-compose.yml`

Definiuje dwa serwisy:
- **app** – obraz PHP 8.2 z Apache, port `8000:80`, zmienne środowiskowe z `.env`
- **db** – obraz MySQL z wolumenem `db_data` i automatycznym uruchomieniem skryptu `init.sql`

## Plik `app/dockerfile`

Instaluje zależności systemowe (`git`, `unzip`, `libzip-dev`), rozszerzenia PHP (`pdo_mysql`, `mysqli`, `zip`), włącza moduł Apache `rewrite`, instaluje Composer i kopiuje kod źródłowy do `/var/www/html/`.

## Plik `composer.json`

Zarządza zależnościami PHP. Jedyna zewnętrzna biblioteka to `phpmailer/phpmailer ^6.9` do obsługi wysyłki emaili.

## Struktura projektu po etapie 1

```
PBF/
├── app/
│   ├── dockerfile
│   └── src/
│       └── composer.json
├── db/
├── docker-compose.yml
└── .env.example
```

## Jak uruchomić

```bash
cp .env.example .env
docker compose up -d --build
```

Aplikacja dostępna pod: `http://localhost:8000` (na tym etapie — pusta strona Apache, brak logiki).

## Wynik etapu

Środowisko uruchomieniowe jest gotowe. Kontenery `app` i `db` startują poprawnie. Kolejny etap to zaprojektowanie i wdrożenie schematu bazy danych.
