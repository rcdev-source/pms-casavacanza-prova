import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse, Room } from '../types/core'
import type { CleaningTask } from '../types/operations'

const schema = z.object({
  room_id: z.string().min(1),
  scheduled_for: z.string().min(1),
  priority: z.coerce.number().int().min(1).max(5),
  notes: z.string(),
})

type Input = z.input<typeof schema>
type Output = z.output<typeof schema>

export function CleaningPage() {
  const { property } = useProperty()
  const queryClient = useQueryClient()
  const tasks = useQuery({
    queryKey: ['cleaning-tasks', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<DataResponse<CleaningTask[]>>('/cleaning-tasks?property_id=' + property!.id),
  })
  const rooms = useQuery({
    queryKey: ['rooms', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<DataResponse<Room[]>>('/rooms?property_id=' + property!.id),
  })
  const form = useForm<Input, unknown, Output>({
    resolver: zodResolver(schema),
    defaultValues: { room_id: '', scheduled_for: '', priority: 3, notes: '' },
  })
  const refresh = () => {
    void queryClient.invalidateQueries({ queryKey: ['cleaning-tasks', property?.id] })
    void queryClient.invalidateQueries({ queryKey: ['rooms', property?.id] })
  }
  const create = useMutation({
    mutationFn: (values: Output) => api('/cleaning-tasks', { method: 'POST', body: values }),
    onSuccess: () => {
      form.reset()
      refresh()
    },
  })
  const action = useMutation({
    mutationFn: ({ id, type }: { id: string; type: 'start' | 'complete' }) =>
      api('/cleaning-tasks/' + id + '/' + type, {
        method: 'POST',
        body: type === 'complete' ? { completion_notes: 'Pulizia completata e camera controllata.' } : {},
      }),
    onSuccess: refresh,
  })

  if (!property) return <p>Caricamento struttura…</p>

  return (
    <div>
      <header>
        <p className="text-sm font-semibold text-violet-600">{property.name}</p>
        <h2 className="mt-1 text-3xl font-black">Pulizie</h2>
        <p className="mt-2 text-slate-500">Coda camere da preparare e controllo completamento.</p>
      </header>

      <section className="mt-7 grid gap-4 lg:grid-cols-2">
        {tasks.data?.data.map((task) => (
          <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" key={task.id}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Priorità {task.priority}</p>
                <h3 className="mt-1 text-xl font-black">{task.room.name}</h3>
              </div>
              <span className="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800">{task.status}</span>
            </div>
            <p className="mt-4 text-sm text-slate-500">{task.notes ?? 'Pulizia camera'}</p>
            <p className="mt-2 text-xs text-slate-400">
              Programmata: {new Intl.DateTimeFormat('it-IT', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(task.scheduled_for))}
            </p>
            <div className="mt-5 flex gap-2">
              {['PENDING', 'ASSIGNED'].includes(task.status) ? (
                <button className="rounded-lg bg-slate-950 px-3 py-2 text-sm font-bold text-white" type="button" onClick={() => action.mutate({ id: task.id, type: 'start' })}>
                  Inizia
                </button>
              ) : null}
              {task.status === 'IN_PROGRESS' ? (
                <button className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white" type="button" onClick={() => action.mutate({ id: task.id, type: 'complete' })}>
                  Completa
                </button>
              ) : null}
            </div>
          </article>
        ))}
        {!tasks.isLoading && !tasks.data?.data.length ? <p className="text-slate-500">Nessuna pulizia in coda.</p> : null}
      </section>

      <section className="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
        <h3 className="text-lg font-black">Programma pulizia manuale</h3>
        <p className="mt-1 text-sm text-slate-500">Disponibile ad amministrazione e reception.</p>
        <form className="mt-5 grid gap-3 sm:grid-cols-2" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
          <select className="rounded-xl border bg-white p-3" {...form.register('room_id')}>
            <option value="">Camera</option>
            {rooms.data?.data.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}
          </select>
          <input className="rounded-xl border p-3" type="datetime-local" {...form.register('scheduled_for')} />
          <input className="rounded-xl border p-3" min="1" max="5" type="number" {...form.register('priority')} />
          <input className="rounded-xl border p-3" placeholder="Note" {...form.register('notes')} />
          <button className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white sm:col-span-2" type="submit">Programma</button>
        </form>
        {create.isError ? <p className="mt-3 text-sm font-semibold text-red-600">{create.error.message}</p> : null}
      </section>
    </div>
  )
}
