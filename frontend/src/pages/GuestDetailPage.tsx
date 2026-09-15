import { useQuery } from '@tanstack/react-query'
import { Link, useParams } from 'react-router'
import { api } from '../services/api'
import type { DataResponse, Guest } from '../types/core'

type GuestDetail = Guest & {
  documents: Array<{ id: string; document_type: string; expiry_date: string | null }>
}

export function GuestDetailPage() {
  const { id } = useParams()
  const guest = useQuery({
    queryKey: ['guest', id],
    queryFn: () => api<DataResponse<GuestDetail>>('/guests/' + id),
    enabled: Boolean(id),
  })

  if (guest.isLoading) return <p>Caricamento ospite…</p>
  if (!guest.data) return <p>Ospite non trovato.</p>

  const item = guest.data.data

  return (
    <div>
      <Link to="/guests" className="text-sm font-semibold text-violet-600">← Torna agli ospiti</Link>
      <section className="mt-5 rounded-3xl border border-slate-200 bg-white p-7">
        <h2 className="text-3xl font-black">{item.first_name} {item.last_name}</h2>
        <p className="mt-3 text-slate-500">{item.email || 'Email non presente'} · {item.phone || 'Telefono non presente'}</p>
        <h3 className="mt-8 font-black">Documenti privati</h3>
        <div className="mt-3 space-y-2">
          {item.documents.length ? item.documents.map((document) => (
            <a key={document.id} className="block rounded-xl bg-slate-100 p-3 font-semibold text-violet-700" href={(import.meta.env.VITE_API_URL ?? '') + '/guest-documents/' + document.id + '/download'}>
              {document.document_type}
            </a>
          )) : <p className="text-sm text-slate-500">Nessun documento caricato.</p>}
        </div>
      </section>
    </div>
  )
}
