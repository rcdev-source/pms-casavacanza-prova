import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router'
import { z } from 'zod'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse, Guest, PaginatedResponse } from '../types/core'

const guestSchema = z.object({
  first_name: z.string().min(1),
  last_name: z.string().min(1),
  email: z.union([z.email(), z.literal('')]),
  phone: z.string(),
})

type GuestInput = z.infer<typeof guestSchema>

export function GuestsPage() {
  const queryClient = useQueryClient()
  const { property } = useProperty()
  const guests = useQuery({
    queryKey: ['guests', property?.id],
    enabled: Boolean(property),
    queryFn: () => api<PaginatedResponse<Guest>>('/guests?property_id=' + property!.id),
  })
  const form = useForm<GuestInput>({
    resolver: zodResolver(guestSchema),
    defaultValues: { first_name: '', last_name: '', email: '', phone: '' },
  })
  const createGuest = useMutation({
    mutationFn: (values: GuestInput) =>
      api<DataResponse<Guest>>('/guests', {
        method: 'POST',
        body: { ...values, property_id: property!.id, country: 'IT' },
      }),
    onSuccess: () => {
      form.reset()
      void queryClient.invalidateQueries({ queryKey: ['guests', property?.id] })
    },
  })

  if (!property) return <p>Caricamento struttura…</p>

  return (
    <div>
      <header>
        <p className="text-sm font-semibold text-violet-600">{property.name}</p>
        <h2 className="mt-1 text-3xl font-black">Ospiti</h2>
      </header>

      <section className="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        {guests.data?.data.length ? guests.data.data.map((guest) => (
          <Link key={guest.id} to={'/guests/' + guest.id} className="flex items-center justify-between border-b border-slate-100 p-4 last:border-0 hover:bg-slate-50">
            <div>
              <p className="font-bold">{guest.last_name} {guest.first_name}</p>
              <p className="text-sm text-slate-500">{guest.email || guest.phone || 'Nessun contatto'}</p>
            </div>
            <span className="text-violet-600">Apri →</span>
          </Link>
        )) : <p className="p-6 text-slate-500">Nessun ospite registrato.</p>}
      </section>

      <section className="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
        <h3 className="text-lg font-black">Nuovo ospite</h3>
        <form className="mt-5 grid gap-4 sm:grid-cols-2" onSubmit={form.handleSubmit((values) => createGuest.mutate(values))}>
          <input className="rounded-xl border p-3" placeholder="Nome" {...form.register('first_name')} />
          <input className="rounded-xl border p-3" placeholder="Cognome" {...form.register('last_name')} />
          <input className="rounded-xl border p-3" type="email" placeholder="Email" {...form.register('email')} />
          <input className="rounded-xl border p-3" placeholder="Telefono" {...form.register('phone')} />
          <button className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white sm:col-span-2" type="submit">Registra ospite</button>
        </form>
      </section>
    </div>
  )
}
