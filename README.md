# PMS Casa Vacanze

Applicazione web completa per gestire una casa vacanze da quattro camere, predisposta per più strutture. Include prenotazioni, disponibilità, ospiti, incassi, pre-check-in, soggiorni, pulizie, manutenzione, notifiche, audit e report.

## Stack e architettura

- Laravel 12 / PHP 8.4, Sanctum, MySQL 8.4, Redis, queue e scheduler
- React 19, TypeScript strict, Vite, Tailwind CSS e TanStack Query
- Nginx e Docker Compose
- Pest, Vitest, ESLint, Pint, typecheck e GitHub Actions

I confini tecnici e il modello dei dati sono descritti in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). Gli endpoint sono elencati in [docs/API.md](docs/API.md).

## Avvio rapido con Docker

Prerequisiti: Git, Docker Engine e Docker Compose v2.

```bash
cp .env.example .env
cp backend/.env.example backend/.env
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose up -d
```

Apri:

- frontend: http://localhost:5173
- API: http://localhost:8080/api/v1
- health check: http://localhost:8080/up

Gli account demo condividono la password `Password123!`:

| Ruolo | Email |
|---|---|
| Amministratore | `admin@example.test` |
| Reception | `reception@example.test` |
| Pulizie | `cleaning@example.test` |
| Manutenzione | `maintenance@example.test` |

Le credenziali demo vanno sostituite prima di un uso reale.

## Comandi di qualità

```bash
docker compose run --rm app vendor/bin/pint --test
docker compose run --rm app php artisan test
docker compose run --rm frontend npm run lint
docker compose run --rm frontend npm run typecheck
docker compose run --rm frontend npm test
docker compose run --rm frontend npm run build
docker compose config --quiet
```

La CI esegue gli stessi controlli su ogni pull request.

## Funzioni incluse

- RBAC per amministrazione, reception, pulizie e manutenzione
- separazione e autorizzazione per struttura
- calendario e disponibilità con prevenzione atomica delle sovrapposizioni
- prezzi stagionali, soggiorno minimo e calcolo importi lato server
- pagamenti, rimborsi, extra e saldo residuo con aritmetica decimale
- link pre-check-in monouso, token hashati e dati documento cifrati
- check-in/check-out con creazione automatica dell'attività di pulizia
- blocchi camera per manutenzione e ripristino dello stato
- dashboard, notifiche asincrone, report economico ed export CSV sicuro
- audit trail delle modifiche critiche e processi automatici di conservazione

Per procedure giornaliere, backup e rilascio consulta [docs/OPERATIONS.md](docs/OPERATIONS.md). Per le misure di sicurezza consulta [docs/SECURITY.md](docs/SECURITY.md).
