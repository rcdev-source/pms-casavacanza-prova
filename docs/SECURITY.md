# Sicurezza e privacy

## Controlli applicati

- Password hashate e token Sanctum revocabili.
- Policy RBAC e controllo di appartenenza alla struttura su ogni risorsa.
- Rate limit dedicato al login, alle rotte pubbliche e limite globale API.
- Validazione server-side, query ORM e transazioni sulle operazioni concorrenti.
- Token pre-check-in casuali, hashati, monouso e con scadenza.
- Payload dei documenti di identità cifrati; download autorizzato.
- Audit trail per entità critiche con segreti e dati documento esclusi.
- CSV neutralizzato contro formula injection.
- CORS ristretto a `FRONTEND_URL` e header difensivi HTTP.
- Segreti esclusi dal repository tramite file `.env`.

## Prima della produzione

1. Impostare `APP_ENV=production`, `APP_DEBUG=false` e una nuova `APP_KEY`.
2. Sostituire password database, credenziali demo e indirizzi email.
3. Usare TLS sul reverse proxy e servizi MySQL/Redis non esposti pubblicamente.
4. Configurare posta transazionale, backup cifrati e monitoraggio errori.
5. Limitare i permessi del database e ruotare periodicamente i segreti.
6. Definire informativa, base giuridica e tempi di conservazione GDPR con il titolare.

Lo scheduler elimina token pre-check-in scaduti oltre 30 giorni, notifiche lette oltre 90 giorni e job falliti oltre sette giorni. La cancellazione dei dati degli ospiti deve seguire la politica legale adottata dalla struttura.
