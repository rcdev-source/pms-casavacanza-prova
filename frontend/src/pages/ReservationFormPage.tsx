import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router'
import { useProperty } from '../hooks/useProperty'
import {
  reservationSchema,
  type ReservationInput,
  type ReservationOutput,
} from '../schemas/reservation'
import { ApiError, api } from '../services/api'
import type { DataResponse, Guest, PaginatedResponse, Room } from '../types/core'
import type { AvailableRoom, Reservation } from '../types/reservation'

function isoDate(date: Date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return year + '-' + month + '-' + day
}

function dateAfter(days: number) {
  const date = new Date()
  date.setDate(date.getDate() + days)
  return isoDate(date)
}

export function ReservationFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { property } = useProperty()
  const editing = Boolean(id)
  const form = useForm<ReservationInput, unknown, ReservationOutput>({
    resolver: zodResolver(reservationSchema),
    defaultValues: {
      room_id: '',
      primary_guest_id: '',
      check_in_date: dateAfter(1),
      check_out_date: dateAfter(2),
      adults: 1,
      children: 0,
      source: 'DIRECT',
      notes: '',
    },
  })
  const existing = useQuery({
    queryKey: ['reservation', id],
    enabled: editing,
    queryFn: () => api<DataResponse<Reservation>>('/reservations/' + id),
  })
  const rooms = useQuery({
    queryKey: ['rooms', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<DataResponse<Room[]>>('/rooms?property_id=' + property!.id),
  })
  const guests = useQuery({
    queryKey: ['guests', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<PaginatedResponse<Guest>>('/guests?property_id=' + property!.id),
  })

  useEffect(() => {
    const reservation = existing.data?.data
    if (!reservation) return
    form.reset({
      room_id: reservation.room_id,
      primary_guest_id: reservation.primary_guest_id,
      check_in_date: reservation.check_in_date.slice(0, 10),
      check_out_date: reservation.check_out_date.slice(0, 10),
      adults: reservation.adults,
      children: reservation.children,
      source: reservation.source,
      notes: reservation.notes ?? '',
    })
  }, [existing.data, form])

  const watched = form.watch()
  const canCheck =
    Boolean(property) &&
    Boolean(watched.check_in_date) &&
    Boolean(watched.check_out_date) &&
    watched.check_out_date > watched.check_in_date
  const availability = useQuery({
    queryKey: [
      'availability',
      property?.id,
      watched.check_in_date,
      watched.check_out_date,
      watched.adults,
      watched.children,
    ],
    enabled: canCheck,
    queryFn: () => {
      const query = new URLSearchParams({
        property_id: property!.id,
        check_in_date: watched.check_in_date,
        check_out_date: watched.check_out_date,
        adults: String(watched.adults),
        children: String(watched.children),
      })
      return api<DataResponse<AvailableRoom[]>>('/availability?' + query.toString())
    },
  })
  const save = useMutation({
    mutationFn: (values: ReservationOutput) =>
      api<DataResponse<Reservation>>(editing ? '/reservations/' + id : '/reservations', {
        method: editing ? 'PUT' : 'POST',
        body: values,
      }),
    onSuccess: (response) => navigate('/reservations/' + response.data.id),
  })
  const error = save.error instanceof ApiError ? save.error : null

  if (!property) return <p>Caricamento struttura…</p>

  return (
    <div className="max-w-5xl">
      <Link className="text-sm font-semibold text-violet-600" to="/reservations">
        ← Prenotazioni
      </Link>
      <h2 className="mt-3 text-3xl font-black">{editing ? 'Modifica prenotazione' : 'Nuova prenotazione'}</h2>

      <form
        className="mt-7 grid gap-6 lg:grid-cols-[1fr_360px]"
        onSubmit={form.handleSubmit((values) => save.mutate(values))}
      >
        <section className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 sm:grid-cols-2">
          <label className="grid gap-2 text-sm font-semibold">
            Check-in
            <input className="rounded-xl border p-3 font-normal" type="date" {...form.register('check_in_date')} />
          </label>
          <label className="grid gap-2 text-sm font-semibold">
            Check-out
            <input className="rounded-xl border p-3 font-normal" type="date" {...form.register('check_out_date')} />
          </label>
          <label className="grid gap-2 text-sm font-semibold">
            Adulti
            <input className="rounded-xl border p-3 font-normal" min="1" type="number" {...form.register('adults')} />
          </label>
          <label className="grid gap-2 text-sm font-semibold">
            Bambini
            <input className="rounded-xl border p-3 font-normal" min="0" type="number" {...form.register('children')} />
          </label>
          <label className="grid gap-2 text-sm font-semibold sm:col-span-2">
            Camera
            <select className="rounded-xl border bg-white p-3 font-normal" {...form.register('room_id')}>
              <option value="">Seleziona</option>
              {rooms.data?.data.map((room) => (
                <option key={room.id} value={room.id}>{room.name} · EUR {room.base_price}</option>
              ))}
            </select>
          </label>
          <label className="grid gap-2 text-sm font-semibold sm:col-span-2">
            Ospite principale
            <select className="rounded-xl border bg-white p-3 font-normal" {...form.register('primary_guest_id')}>
              <option value="">Seleziona</option>
              {guests.data?.data.map((guest) => (
                <option key={guest.id} value={guest.id}>{guest.last_name} {guest.first_name}</option>
              ))}
            </select>
          </label>
          <label className="grid gap-2 text-sm font-semibold">
            Provenienza
            <select className="rounded-xl border bg-white p-3 font-normal" {...form.register('source')}>
              <option value="DIRECT">Diretta</option>
              <option value="WEBSITE">Sito web</option>
              <option value="PHONE">Telefono</option>
              <option value="EMAIL">Email</option>
              <option value="BOOKING">Booking.com</option>
              <option value="AIRBNB">Airbnb</option>
              <option value="OTHER">Altro</option>
            </select>
          </label>
          <label className="grid gap-2 text-sm font-semibold sm:col-span-2">
            Note
            <textarea className="min-h-28 rounded-xl border p-3 font-normal" {...form.register('notes')} />
          </label>

          {Object.keys(form.formState.errors).length ? (
            <p className="text-sm font-semibold text-red-600 sm:col-span-2">
              Controlla date, camera, ospite e capienza.
            </p>
          ) : null}
          {error ? (
            <p className="text-sm font-semibold text-red-600 sm:col-span-2">{error.message}</p>
          ) : null}
          <button
            className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white disabled:opacity-50 sm:col-span-2"
            disabled={save.isPending}
            type="submit"
          >
            {save.isPending ? 'Salvataggio…' : editing ? 'Salva modifiche' : 'Conferma prenotazione'}
          </button>
        </section>

        <aside className="h-fit rounded-2xl bg-slate-950 p-6 text-white">
          <p className="text-xs font-bold uppercase tracking-[0.2em] text-violet-300">Disponibilità live</p>
          <h3 className="mt-2 text-xl font-black">Camere libere</h3>
          <div className="mt-5 grid gap-3">
            {availability.data?.data.map((room) => (
              <button
                className="rounded-xl border border-slate-700 p-4 text-left transition hover:border-violet-400"
                key={room.id}
                onClick={() => form.setValue('room_id', room.id, { shouldValidate: true })}
                type="button"
              >
                <span className="block font-bold">{room.name}</span>
                <span className="mt-1 block text-sm text-slate-300">
                  {room.pricing.nights} notti · EUR {room.pricing.subtotal}
                </span>
              </button>
            ))}
            {availability.isFetching ? <p className="text-sm text-slate-400">Verifica in corso…</p> : null}
            {canCheck && !availability.isFetching && !availability.data?.data.length ? (
              <p className="text-sm text-slate-400">Nessuna camera disponibile per questi criteri.</p>
            ) : null}
          </div>
        </aside>
      </form>
    </div>
  )
}
