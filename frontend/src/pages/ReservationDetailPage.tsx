import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useParams } from 'react-router'
import { ReservationOperations } from '../components/ReservationOperations'
import { api } from '../services/api'
import type { DataResponse } from '../types/core'
import type { Reservation } from '../types/reservation'

function formatDate(value: string) {
  return new Intl.DateTimeFormat('it-IT', { dateStyle: 'long' }).format(new Date(value))
}

export function ReservationDetailPage() {
  const { id } = useParams()
  const queryClient = useQueryClient()
  const [confirmCancel, setConfirmCancel] = useState(false)
  const reservation = useQuery({
    queryKey: ['reservation', id],
    queryFn: () => api<DataResponse<Reservation>>('/reservations/' + id),
  })
  const cancel = useMutation({
    mutationFn: () =>
      api<DataResponse<Reservation>>('/reservations/' + id, {
        method: 'DELETE',
        body: { reason: 'Annullata da pannello PMS' },
      }),
    onSuccess: () => {
      setConfirmCancel(false)
      void queryClient.invalidateQueries({ queryKey: ['reservation', id] })
      void queryClient.invalidateQueries({ queryKey: ['reservations'] })
    },
  })

  if (reservation.isLoading) return <p>Caricamento…</p>
  if (!reservation.data) return <p>Prenotazione non trovata.</p>
  const item = reservation.data.data
  const canEdit = ['DRAFT', 'REQUESTED', 'CONFIRMED', 'PRE_CHECKIN'].includes(item.status)
  const canCancel = ['DRAFT', 'REQUESTED', 'CONFIRMED', 'PRE_CHECKIN'].includes(item.status)

  return (
    <div className="max-w-5xl">
      <Link className="text-sm font-semibold text-violet-600" to="/reservations">
        ← Prenotazioni
      </Link>
      <header className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-sm font-bold uppercase tracking-wider text-slate-400">{item.booking_code}</p>
          <h2 className="mt-1 text-3xl font-black">
            {item.primary_guest?.last_name} {item.primary_guest?.first_name}
          </h2>
          <p className="mt-2 text-slate-500">{item.room?.name} · {item.status}</p>
        </div>
        {canEdit ? (
          <Link className="rounded-xl border border-slate-300 bg-white px-4 py-3 font-bold" to={'/reservations/' + item.id + '/edit'}>
            Modifica
          </Link>
        ) : null}
      </header>

      <div className="mt-7 grid gap-5 md:grid-cols-2">
        <section className="rounded-2xl border border-slate-200 bg-white p-6">
          <h3 className="text-lg font-black">Soggiorno</h3>
          <dl className="mt-5 grid grid-cols-2 gap-4 text-sm">
            <div><dt className="text-slate-500">Arrivo</dt><dd className="mt-1 font-bold">{formatDate(item.check_in_date)}</dd></div>
            <div><dt className="text-slate-500">Partenza</dt><dd className="mt-1 font-bold">{formatDate(item.check_out_date)}</dd></div>
            <div><dt className="text-slate-500">Adulti</dt><dd className="mt-1 font-bold">{item.adults}</dd></div>
            <div><dt className="text-slate-500">Bambini</dt><dd className="mt-1 font-bold">{item.children}</dd></div>
            <div><dt className="text-slate-500">Provenienza</dt><dd className="mt-1 font-bold">{item.source}</dd></div>
          </dl>
        </section>

        <section className="rounded-2xl bg-slate-950 p-6 text-white">
          <h3 className="text-lg font-black">Riepilogo economico</h3>
          <dl className="mt-5 grid gap-3 text-sm">
            <div className="flex justify-between"><dt className="text-slate-400">Soggiorno</dt><dd>{item.currency} {item.subtotal}</dd></div>
            <div className="flex justify-between"><dt className="text-slate-400">Sconto</dt><dd>- {item.currency} {item.discount}</dd></div>
            <div className="flex justify-between"><dt className="text-slate-400">Imposte</dt><dd>{item.currency} {item.taxes}</dd></div>
            <div className="flex justify-between"><dt className="text-slate-400">Extra</dt><dd>{item.currency} {item.extras_total}</dd></div>
            <div className="flex justify-between border-t border-slate-700 pt-3 text-lg font-black"><dt>Totale</dt><dd>{item.currency} {item.total}</dd></div>
            <div className="flex justify-between text-violet-300"><dt>Saldo dovuto</dt><dd className="font-black">{item.currency} {item.balance_due}</dd></div>
          </dl>
        </section>
      </div>

      {item.notes ? (
        <section className="mt-5 rounded-2xl border border-slate-200 bg-white p-6">
          <h3 className="font-black">Note</h3>
          <p className="mt-2 whitespace-pre-wrap text-slate-600">{item.notes}</p>
        </section>
      ) : null}

      <ReservationOperations
        reservation={item}
        onChanged={() => {
          void queryClient.invalidateQueries({ queryKey: ['reservation', id] })
          void queryClient.invalidateQueries({ queryKey: ['reservations'] })
          void queryClient.invalidateQueries({ queryKey: ['calendar'] })
        }}
      />

      {canCancel ? (
        <section className="mt-7">
          {!confirmCancel ? (
            <button className="text-sm font-bold text-red-600" type="button" onClick={() => setConfirmCancel(true)}>
              Annulla prenotazione
            </button>
          ) : (
            <div className="flex flex-wrap items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
              <p className="mr-auto text-sm font-semibold text-red-900">Confermi l’annullamento?</p>
              <button className="rounded-lg px-3 py-2 text-sm font-bold" type="button" onClick={() => setConfirmCancel(false)}>No</button>
              <button className="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white" type="button" onClick={() => cancel.mutate()}>
                Sì, annulla
              </button>
            </div>
          )}
        </section>
      ) : null}
    </div>
  )
}
