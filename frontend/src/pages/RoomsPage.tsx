import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router'
import { z } from 'zod'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse, Room } from '../types/core'

const roomSchema = z.object({
  name: z.string().min(2, 'Nome obbligatorio'),
  code: z.string().min(2, 'Codice obbligatorio'),
  max_guests: z.coerce.number().int().min(1),
  max_adults: z.coerce.number().int().min(1),
  max_children: z.coerce.number().int().min(0),
  base_price: z.coerce.number().min(0),
})

type RoomInput = z.input<typeof roomSchema>
type RoomOutput = z.output<typeof roomSchema>

const statusStyle: Record<Room['status'], string> = {
  AVAILABLE: 'bg-emerald-100 text-emerald-700',
  RESERVED: 'bg-blue-100 text-blue-700',
  OCCUPIED: 'bg-violet-100 text-violet-700',
  DIRTY: 'bg-amber-100 text-amber-700',
  CLEANING: 'bg-cyan-100 text-cyan-700',
  READY: 'bg-emerald-100 text-emerald-700',
  MAINTENANCE: 'bg-red-100 text-red-700',
  BLOCKED: 'bg-slate-200 text-slate-700',
}

export function RoomsPage() {
  const queryClient = useQueryClient()
  const { property, isLoading: propertyLoading } = useProperty()
  const rooms = useQuery({
    queryKey: ['rooms', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<DataResponse<Room[]>>('/rooms?property_id=' + property!.id),
  })
  const form = useForm<RoomInput, unknown, RoomOutput>({
    resolver: zodResolver(roomSchema),
    defaultValues: { name: '', code: '', max_guests: 2, max_adults: 2, max_children: 0, base_price: 85 },
  })
  const createRoom = useMutation({
    mutationFn: (values: RoomOutput) =>
      api<DataResponse<Room>>('/rooms', {
        method: 'POST',
        body: {
          ...values,
          property_id: property!.id,
          status: 'READY',
          is_active: true,
          amenity_ids: [],
        },
      }),
    onSuccess: () => {
      form.reset()
      void queryClient.invalidateQueries({ queryKey: ['rooms', property?.id] })
    },
  })

  if (propertyLoading) return <p>Caricamento struttura…</p>
  if (!property) return <p>Nessuna struttura assegnata.</p>

  return (
    <div>
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-violet-600">{property.name}</p>
          <h2 className="mt-1 text-3xl font-black">Camere</h2>
        </div>
        <span className="rounded-full bg-slate-200 px-3 py-1 text-sm">{rooms.data?.data.length ?? 0} camere</span>
      </header>

      <section className="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {rooms.data?.data.map((room) => (
          <Link key={room.id} to={'/rooms/' + room.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{room.code}</p>
                <h3 className="mt-1 text-xl font-black">{room.name}</h3>
              </div>
              <span className={'rounded-full px-2.5 py-1 text-xs font-bold ' + statusStyle[room.status]}>{room.status}</span>
            </div>
            <p className="mt-5 text-sm text-slate-500">Fino a {room.max_guests} ospiti</p>
            <p className="mt-1 text-lg font-bold">€{room.base_price}<span className="text-xs font-normal text-slate-400"> / notte</span></p>
          </Link>
        ))}
      </section>

      <section className="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
        <h3 className="text-lg font-black">Aggiungi camera</h3>
        <form className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" onSubmit={form.handleSubmit((values) => createRoom.mutate(values))}>
          <input className="rounded-xl border p-3" placeholder="Nome" {...form.register('name')} />
          <input className="rounded-xl border p-3" placeholder="Codice" {...form.register('code')} />
          <input className="rounded-xl border p-3" type="number" placeholder="Ospiti" {...form.register('max_guests')} />
          <input className="rounded-xl border p-3" type="number" placeholder="Adulti" {...form.register('max_adults')} />
          <input className="rounded-xl border p-3" type="number" placeholder="Bambini" {...form.register('max_children')} />
          <input className="rounded-xl border p-3" type="number" step="0.01" placeholder="Prezzo" {...form.register('base_price')} />
          <button className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white disabled:opacity-50" disabled={createRoom.isPending} type="submit">
            {createRoom.isPending ? 'Salvataggio…' : 'Crea camera'}
          </button>
        </form>
        {Object.keys(form.formState.errors).length ? <p className="mt-3 text-sm text-red-600">Controlla i dati inseriti.</p> : null}
      </section>
    </div>
  )
}
