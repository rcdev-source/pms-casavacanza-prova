import { useQuery } from '@tanstack/react-query'
import { Link, useParams } from 'react-router'
import { api } from '../services/api'
import type { DataResponse, Room } from '../types/core'

export function RoomDetailPage() {
  const { id } = useParams()
  const room = useQuery({
    queryKey: ['room', id],
    queryFn: () => api<DataResponse<Room>>('/rooms/' + id),
    enabled: Boolean(id),
  })

  if (room.isLoading) return <p>Caricamento camera…</p>
  if (!room.data) return <p>Camera non trovata.</p>

  const item = room.data.data

  return (
    <div>
      <Link to="/rooms" className="text-sm font-semibold text-violet-600">← Torna alle camere</Link>
      <section className="mt-5 rounded-3xl border border-slate-200 bg-white p-7">
        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">{item.code}</p>
        <h2 className="mt-2 text-3xl font-black">{item.name}</h2>
        <p className="mt-3 text-slate-500">{item.description ?? 'Nessuna descrizione.'}</p>
        <dl className="mt-8 grid gap-5 sm:grid-cols-3">
          <div><dt className="text-sm text-slate-500">Stato operativo</dt><dd className="mt-1 font-bold">{item.status}</dd></div>
          <div><dt className="text-sm text-slate-500">Capienza</dt><dd className="mt-1 font-bold">{item.max_guests} ospiti</dd></div>
          <div><dt className="text-sm text-slate-500">Prezzo base</dt><dd className="mt-1 font-bold">€{item.base_price}</dd></div>
        </dl>
        <div className="mt-8 flex flex-wrap gap-2">
          {item.amenities.map((amenity) => <span key={amenity.id} className="rounded-full bg-slate-100 px-3 py-1 text-sm">{amenity.name}</span>)}
        </div>
      </section>
    </div>
  )
}
