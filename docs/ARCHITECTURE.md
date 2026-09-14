# Architettura

## Componenti

Il browser React comunica esclusivamente con l'API JSON Laravel tramite HTTPS in produzione. Laravel applica autenticazione Sanctum, policy per ruolo e struttura, validazione e transazioni. MySQL conserva i dati operativi; Redis gestisce cache e code. Un worker elabora le notifiche e lo scheduler esegue le attività periodiche.

## Domini

| Dominio | Responsabilità |
|---|---|
| Accesso | utenti, ruoli, token e assegnazione alle strutture |
| Inventario | strutture, camere, dotazioni e stato operativo |
| Vendite | ospiti, prenotazioni, disponibilità e regole tariffarie |
| Soggiorno | pre-check-in, documenti cifrati, check-in e check-out |
| Economico | servizi, pagamenti, rimborsi, saldi e report |
| Operazioni | pulizie, manutenzione, blocchi e notifiche |
| Controllo | audit trail, retention, health check e CI |

## Regole invarianti

- Gli intervalli soggiorno sono semiaperti: `[check-in, check-out)`.
- Una camera viene bloccata in transazione prima della verifica di sovrapposizione.
- Prezzi, totali e saldo sono calcolati dal server con precisione decimale.
- Ogni accesso a dati di una struttura passa da una policy.
- Il checkout crea una pulizia; la sequenza camera è `DIRTY → CLEANING → READY`.
- Un guasto urgente blocca la disponibilità fino alla risoluzione.
- I token pubblici sono monouso, a scadenza e memorizzati solo come hash.

## Scalabilità

Le entità operative includono `property_id`; API e indici sono già organizzati per struttura. Worker e scheduler sono processi separati e replicabili. L'applicazione rimane stateless salvo MySQL e Redis.
