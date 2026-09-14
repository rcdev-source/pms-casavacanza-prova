# API v1

Base URL locale: `http://localhost:8080/api/v1`. Le rotte private richiedono `Authorization: Bearer <token>`.

| Area | Metodo e percorso |
|---|---|
| Accesso | `POST /auth/login`, `GET /auth/me`, `DELETE /auth/logout` |
| Strutture | `GET/POST /properties`, `GET/PUT /properties/{id}` |
| Camere | `GET/POST /rooms`, `GET/PUT /rooms/{id}` |
| Ospiti | `GET/POST /guests`, `GET/PUT /guests/{id}` |
| Documenti | `POST /guests/{id}/documents`, `GET /guest-documents/{id}/download` |
| Disponibilità | `GET /availability`, `GET /calendar` |
| Prenotazioni | CRUD `/reservations` |
| Prezzi e blocchi | CRUD `/pricing-rules`, `/availability-blocks` |
| Economico | pagamenti, rimborsi e servizi sotto `/reservations/{id}` |
| Soggiorno | link pre-check-in, `check-in` e `check-out` |
| Pulizie | elenco, creazione, dettaglio, `start` e `complete` |
| Manutenzione | elenco, creazione, dettaglio, `start`, `resolve`, `close` |
| Dashboard | `GET /dashboard`, notifiche e marcatura lettura |
| Report | `GET /reports/financial`, `GET /reports/reservations.csv` |
| Pubblico | `GET /public/availability`, pre-check-in tramite token |

Le risposte JSON usano `data`; gli errori di validazione restituiscono HTTP 422 e `errors`. Conflitti di disponibilità restituiscono HTTP 409 e codice `ROOM_NOT_AVAILABLE`. I report richiedono `property_id`, `from` e `to` nel formato `YYYY-MM-DD`.
