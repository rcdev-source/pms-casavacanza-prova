import { useQuery } from '@tanstack/react-query'
import { useMemo, useState } from 'react'
import { Link } from 'react-router'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse } from '../types/core'
import type { CalendarData, Reservation } from '../types/reservation'

function isoDate(date: Date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return year + '-' + month + '-' + day
}

function addDays(value: string, amount: number) {
  const date = new Date(value + 'T12:00:00')
  date.setDate(date.getDate() + amount)
  return isoDate(date)
}

function cellStyle(reservation?: Reservation) {
  if (!reservation) return 'bg-white'
  if (reservation.status === 'CANCELLED') return 'bg-red-50 text-red-700'
  if (reservation.status === 'CHECKED_IN' || reservation.status === 'IN_HOUSE') {
    return 'bg-violet-100 text-violet-900'
  }
  return 'bg-blue-100 text-blue-900'
}

export function CalendarPage() {
  const { property } = useProperty()
  const [days, setDays] = useState<7 | 31>(7)
  const [from, setFrom] = useState(() => isoDate(new Date()))
  const to = addDays(from, days)
  const dates = useMemo(
    () => Array.from({ length: days }, (_, index) => addDays(from, index)),
    [days, from],
  )
  const calendar = useQuery({
    queryKey: ['calendar', property?.id, from, to],
    enabled: Boolean(property),
    queryFn: () =>
      api<DataResponse<CalendarData>>(
        '/calendar?' +
          new URLSearchParams({ property_id: property!.id, from, to }).toString(),
      ),
  })

  if (!property) return <p>Caricamento struttura…</p>

  return (
    <div>
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-violet-600">{property.name}</p>
          <h2 className="mt-1 text-3xl font-black">Calendario</h2>
          <p className="mt-2 text-slate-500">Occupazione camere dal vivo.</p>
        </div>
        <Link className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white" to="/reservations/new">
          Nuova prenotazione
        </Link>
      </header>

      <div className="mt-6 flex flex-wrap items-center gap-2">
        <button className="rounded-lg border bg-white px-3 py-2 font-bold" type="button" onClick={() => setFrom(addDays(from, -days))}>
          ←
        </button>
        <button className="rounded-lg border bg-white px-3 py-2 font-bold" type="button" onClick={() => setFrom(isoDate(new Date()))}>
          Oggi
        </button>
        <button className="rounded-lg border bg-white px-3 py-2 font-bold" type="button" onClick={() => setFrom(addDays(from, days))}>
          →
        </button>
        <div className="ml-auto flex rounded-xl border bg-white p-1">
          <button
            className={'rounded-lg px-3 py-1.5 text-sm font-bold ' + (days === 7 ? 'bg-slate-950 text-white' : '')}
            type="button"
            onClick={() => setDays(7)}
          >
            Settimana
          </button>
          <button
            className={'rounded-lg px-3 py-1.5 text-sm font-bold ' + (days === 31 ? 'bg-slate-950 text-white' : '')}
            type="button"
            onClick={() => setDays(31)}
          >
            Mese
          </button>
        </div>
      </div>

      <section className="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        {calendar.isLoading ? <p className="p-6 text-slate-500">Caricamento calendario…</p> : null}
        {calendar.isError ? <p className="p-6 text-red-600">Impossibile caricare il calendario.</p> : null}
        {calendar.data ? (
          <div className="min-w-max">
            <div
              className="grid border-b border-slate-200 bg-slate-50"
              style={{ gridTemplateColumns: '180px repeat(' + dates.length + ', minmax(82px, 1fr))' }}
            >
              <div className="sticky left-0 z-10 bg-slate-50 p-3 text-xs font-bold uppercase text-slate-500">Camera</div>
              {dates.map((date) => (
                <div className="border-l border-slate-200 p-2 text-center text-xs font-bold" key={date}>
                  <span className="block text-slate-400">
                    {new Intl.DateTimeFormat('it-IT', { weekday: 'short' }).format(new Date(date + 'T12:00:00'))}
                  </span>
                  <span className="mt-1 block text-sm text-slate-900">{date.slice(8, 10)}</span>
                </div>
              ))}
            </div>
            {calendar.data.data.rooms.map((room) => (
              <div
                className="grid border-b border-slate-100 last:border-0"
                key={room.id}
                style={{ gridTemplateColumns: '180px repeat(' + dates.length + ', minmax(82px, 1fr))' }}
              >
                <div className="sticky left-0 z-10 bg-white p-3">
                  <p className="font-black">{room.name}</p>
                  <p className="text-xs text-slate-400">{room.code}</p>
                </div>
                {dates.map((date) => {
                  const reservation = room.reservations.find(
                    (item) =>
                      item.check_in_date.slice(0, 10) <= date &&
                      item.check_out_date.slice(0, 10) > date,
                  )
                  const block = room.availability_blocks.find(
                    (item) => item.start_date.slice(0, 10) <= date && item.end_date.slice(0, 10) > date,
                  )

                  return (
                    <div className={'min-h-16 border-l border-slate-100 p-1 ' + (block ? 'bg-slate-200' : cellStyle(reservation))} key={date}>
                      {reservation ? (
                        <Link className="block h-full rounded p-1 text-xs font-bold" title={reservation.booking_code} to={'/reservations/' + reservation.id}>
                          {reservation.primary_guest?.last_name ?? reservation.booking_code}
                        </Link>
                      ) : block ? (
                        <span className="block p-1 text-xs font-bold text-slate-600">{block.reason ?? 'Blocco'}</span>
                      ) : null}
                    </div>
                  )
                })}
              </div>
            ))}
          </div>
        ) : null}
      </section>

      <div className="mt-4 flex flex-wrap gap-4 text-xs font-semibold text-slate-500">
        <span><i className="mr-1 inline-block h-3 w-3 rounded bg-blue-100" /> Prenotata</span>
        <span><i className="mr-1 inline-block h-3 w-3 rounded bg-violet-100" /> In casa</span>
        <span><i className="mr-1 inline-block h-3 w-3 rounded bg-slate-200" /> Bloccata</span>
      </div>
    </div>
  )
}
