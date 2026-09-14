import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse } from '../types/core'
import type { Reservation, ReservationStatus } from '../types/reservation'

const statusLabel: Record<ReservationStatus, string> = {
  DRAFT: 'Bozza',
  REQUESTED: 'Richiesta',
  CONFIRMED: 'Confermata',
  PRE_CHECKIN: 'Pre check-in',
  CHECKED_IN: 'Arrivato',
  IN_HOUSE: 'In casa',
  CHECKED_OUT: 'Partito',
  COMPLETED: 'Completata',
  CANCELLED: 'Annullata',
  NO_SHOW: 'No show',
}

const statusStyle: Record<ReservationStatus, string> = {
  DRAFT: 'bg-slate-100 text-slate-700',
  REQUESTED: 'bg-amber-100 text-amber-800',
  CONFIRMED: 'bg-blue-100 text-blue-800',
  PRE_CHECKIN: 'bg-cyan-100 text-cyan-800',
  CHECKED_IN: 'bg-violet-100 text-violet-800',
  IN_HOUSE: 'bg-violet-100 text-violet-800',
  CHECKED_OUT: 'bg-emerald-100 text-emerald-800',
  COMPLETED: 'bg-emerald-100 text-emerald-800',
  CANCELLED: 'bg-red-100 text-red-800',
  NO_SHOW: 'bg-orange-100 text-orange-800',
}

function formatDate(value: string) {
  return new Intl.DateTimeFormat('it-IT', { day: '2-digit', month: 'short', year: 'numeric' }).format(
    new Date(value),
  )
}

export function ReservationsPage() {
  const { property } = useProperty()
  const reservations = useQuery({
    queryKey: ['reservations', property?.id],
    enabled: Boolean(property),
    queryFn: () =>
      api<DataResponse<Reservation[]>>('/reservations?property_id=' + property!.id),
  })

  if (!property) return <p>Caricamento struttura…</p>

  return (
    <div>
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-violet-600">{property.name}</p>
          <h2 className="mt-1 text-3xl font-black">Prenotazioni</h2>
          <p className="mt-2 text-slate-500">Soggiorni, ospiti e importi in un’unica vista.</p>
        </div>
        <Link className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white" to="/reservations/new">
          Nuova prenotazione
        </Link>
      </header>

      <section className="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        {reservations.isLoading ? <p className="p-6 text-slate-500">Caricamento…</p> : null}
        {reservations.isError ? <p className="p-6 text-red-600">Impossibile caricare le prenotazioni.</p> : null}
        {reservations.data?.data.map((reservation) => (
          <Link
            className="grid gap-3 border-b border-slate-100 p-5 transition last:border-0 hover:bg-slate-50 md:grid-cols-[1.2fr_1fr_1fr_auto] md:items-center"
            key={reservation.id}
            to={'/reservations/' + reservation.id}
          >
            <div>
              <p className="text-xs font-bold uppercase tracking-wider text-slate-400">
                {reservation.booking_code}
              </p>
              <p className="mt-1 font-black">
                {reservation.primary_guest?.last_name} {reservation.primary_guest?.first_name}
              </p>
            </div>
            <div className="text-sm">
              <p className="font-semibold">{reservation.room?.name ?? 'Camera'}</p>
              <p className="text-slate-500">
                {formatDate(reservation.check_in_date)} → {formatDate(reservation.check_out_date)}
              </p>
            </div>
            <p className="font-black">
              {reservation.currency} {reservation.total}
            </p>
            <span className={'w-fit rounded-full px-3 py-1 text-xs font-bold ' + statusStyle[reservation.status]}>
              {statusLabel[reservation.status]}
            </span>
          </Link>
        ))}
        {!reservations.isLoading && !reservations.data?.data.length ? (
          <p className="p-6 text-slate-500">Nessuna prenotazione registrata.</p>
        ) : null}
      </section>
    </div>
  )
}
