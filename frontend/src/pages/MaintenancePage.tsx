import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse, Room } from '../types/core'
import type { MaintenanceTicket } from '../types/operations'

const schema = z.object({
  room_id: z.string().min(1),
  title: z.string().min(3),
  description: z.string().min(3),
  priority: z.enum(['LOW', 'NORMAL', 'HIGH', 'URGENT']),
  blocks_room: z.boolean(),
})

type FormData = z.infer<typeof schema>

const priorityStyle: Record<MaintenanceTicket['priority'], string> = {
  LOW: 'bg-slate-100 text-slate-700',
  NORMAL: 'bg-blue-100 text-blue-700',
  HIGH: 'bg-amber-100 text-amber-800',
  URGENT: 'bg-red-100 text-red-800',
}

export function MaintenancePage() {
  const { property } = useProperty()
  const queryClient = useQueryClient()
  const tickets = useQuery({
    queryKey: ['maintenance-tickets', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<DataResponse<MaintenanceTicket[]>>('/maintenance-tickets?property_id=' + property!.id),
  })
  const rooms = useQuery({
    queryKey: ['rooms', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<DataResponse<Room[]>>('/rooms?property_id=' + property!.id),
  })
  const form = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { room_id: '', title: '', description: '', priority: 'NORMAL', blocks_room: false },
  })
  const refresh = () => {
    void queryClient.invalidateQueries({ queryKey: ['maintenance-tickets', property?.id] })
    void queryClient.invalidateQueries({ queryKey: ['rooms', property?.id] })
    void queryClient.invalidateQueries({ queryKey: ['availability'] })
  }
  const create = useMutation({
    mutationFn: (values: FormData) => api('/maintenance-tickets', { method: 'POST', body: { ...values, assigned_to: null } }),
    onSuccess: () => {
      form.reset()
      refresh()
    },
  })
  const action = useMutation({
    mutationFn: ({ id, type }: { id: string; type: 'start' | 'resolve' | 'close' }) =>
      api('/maintenance-tickets/' + id + '/' + type, {
        method: 'POST',
        body: type === 'resolve' ? { resolution_notes: 'Intervento completato e camera verificata.' } : {},
      }),
    onSuccess: refresh,
  })

  if (!property) return <p>Caricamento struttura…</p>

  return (
    <div>
      <header>
        <p className="text-sm font-semibold text-violet-600">{property.name}</p>
        <h2 className="mt-1 text-3xl font-black">Manutenzione</h2>
        <p className="mt-2 text-slate-500">Segnalazioni, priorità e blocco automatico delle camere.</p>
      </header>

      <section className="mt-7 grid gap-4 lg:grid-cols-2">
        {tickets.data?.data.map((ticket) => (
          <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" key={ticket.id}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{ticket.room.name}</p>
                <h3 className="mt-1 text-xl font-black">{ticket.title}</h3>
              </div>
              <span className={'rounded-full px-3 py-1 text-xs font-bold ' + priorityStyle[ticket.priority]}>{ticket.priority}</span>
            </div>
            <p className="mt-3 text-sm text-slate-600">{ticket.description}</p>
            <div className="mt-4 flex flex-wrap gap-2 text-xs font-bold">
              <span className="rounded-full bg-slate-100 px-3 py-1">{ticket.status}</span>
              {ticket.blocks_room ? <span className="rounded-full bg-red-100 px-3 py-1 text-red-700">CAMERA BLOCCATA</span> : null}
            </div>
            <div className="mt-5 flex gap-2">
              {ticket.status === 'OPEN' ? (
                <button className="rounded-lg bg-slate-950 px-3 py-2 text-sm font-bold text-white" type="button" onClick={() => action.mutate({ id: ticket.id, type: 'start' })}>Prendi in carico</button>
              ) : null}
              {['OPEN', 'IN_PROGRESS'].includes(ticket.status) ? (
                <button className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white" type="button" onClick={() => action.mutate({ id: ticket.id, type: 'resolve' })}>Risolvi</button>
              ) : null}
              {ticket.status === 'RESOLVED' ? (
                <button className="rounded-lg border px-3 py-2 text-sm font-bold" type="button" onClick={() => action.mutate({ id: ticket.id, type: 'close' })}>Chiudi</button>
              ) : null}
            </div>
          </article>
        ))}
        {!tickets.isLoading && !tickets.data?.data.length ? <p className="text-slate-500">Nessun ticket aperto.</p> : null}
      </section>

      <section className="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
        <h3 className="text-lg font-black">Nuova segnalazione</h3>
        <form className="mt-5 grid gap-3 sm:grid-cols-2" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
          <select className="rounded-xl border bg-white p-3" {...form.register('room_id')}>
            <option value="">Camera</option>
            {rooms.data?.data.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}
          </select>
          <select className="rounded-xl border bg-white p-3" {...form.register('priority')}>
            <option value="LOW">Bassa</option><option value="NORMAL">Normale</option><option value="HIGH">Alta</option><option value="URGENT">Urgente</option>
          </select>
          <input className="rounded-xl border p-3 sm:col-span-2" placeholder="Titolo" {...form.register('title')} />
          <textarea className="min-h-24 rounded-xl border p-3 sm:col-span-2" placeholder="Descrizione problema" {...form.register('description')} />
          <label className="flex items-center gap-2 text-sm font-bold sm:col-span-2">
            <input type="checkbox" {...form.register('blocks_room')} /> Blocca subito la camera nelle disponibilità
          </label>
          <button className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white sm:col-span-2" type="submit">Apri ticket</button>
        </form>
        {create.isError ? <p className="mt-3 text-sm font-semibold text-red-600">{create.error.message}</p> : null}
      </section>
    </div>
  )
}
