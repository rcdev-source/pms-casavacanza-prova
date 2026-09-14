# Operazioni

## Flusso giornaliero

1. Reception controlla dashboard, arrivi, partenze e saldi.
2. Per ogni prenotazione invia il link di pre-check-in o registra i dati in sede.
3. Registra gli incassi e completa check-in e check-out.
4. Il checkout apre automaticamente una pulizia e imposta la camera `DIRTY`.
5. Il personale pulizie avvia e completa l'attività; la camera torna `READY`.
6. La manutenzione prende in carico i guasti; quelli urgenti bloccano la camera.
7. Amministrazione consulta Report ed esporta il CSV per il periodo scelto.

## Backup

Eseguire almeno un dump MySQL giornaliero cifrato, conservarlo fuori host e applicare una rotazione documentata. Salvare anche gli eventuali file privati. Verificare ogni mese un ripristino completo in ambiente isolato; un backup non provato non è considerato valido.

## Rilascio

```bash
git pull --ff-only
docker compose build
docker compose run --rm app composer install --no-dev --optimize-autoloader
docker compose run --rm app php artisan migrate --force
docker compose run --rm frontend npm install
docker compose run --rm frontend npm run build
docker compose up -d
docker compose run --rm app php artisan optimize
```

Controllare `/up`, i log, il worker Redis e lo scheduler. Prima delle migrazioni acquisire un backup. Per rollback applicativo distribuire l'immagine precedente; evitare rollback automatici delle migrazioni senza una procedura verificata.

## Diagnostica

```bash
docker compose ps
docker compose logs --tail=200 app nginx queue scheduler
docker compose run --rm app php artisan about
docker compose run --rm app php artisan queue:failed
```
