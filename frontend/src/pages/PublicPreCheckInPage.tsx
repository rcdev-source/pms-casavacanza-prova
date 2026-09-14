import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { useParams } from 'react-router'
import { z } from 'zod'
import { api } from '../services/api'
import type { DataResponse } from '../types/core'
import type { PublicPreCheckInData } from '../types/reservation'

const schema = z.object({
  first_name: z.string().min(1),
  last_name: z.string().min(1),
  birth_date: z.string().min(1),
  birth_place: z.string().min(1),
  nationality: z.string().length(2),
  email: z.email(),
  phone: z.string().min(5),
  address: z.string().min(2),
  city: z.string().min(2),
  postal_code: z.string().min(2),
  country: z.string().length(2),
  document_type: z.enum(['ID_CARD', 'PASSPORT', 'DRIVING_LICENSE', 'OTHER']),
  document_number: z.string().min(3),
  document_issuing_country: z.string().length(2),
  document_expiry_date: z.string().min(1),
})

type FormData = z.infer<typeof schema>

export function PublicPreCheckInPage() {
  const { token } = useParams()
  const details = useQuery({
    queryKey: ['public-pre-check-in', token],
    enabled: Boolean(token),
    retry: false,
    queryFn: () => api<DataResponse<PublicPreCheckInData>>('/public/pre-check-in/' + token),
  })
  const form = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      first_name: '',
      last_name: '',
      birth_date: '',
      birth_place: '',
      nationality: 'IT',
      email: '',
      phone: '',
      address: '',
      city: '',
      postal_code: '',
      country: 'IT',
      document_type: 'ID_CARD',
      document_number: '',
      document_issuing_country: 'IT',
      document_expiry_date: '',
    },
  })
  const submit = useMutation({
    mutationFn: (values: FormData) =>
      api('/public/pre-check-in/' + token, { method: 'POST', body: values }),
  })

  useEffect(() => {
    const guest = details.data?.data.guest
    if (!guest) return
    form.reset({
      first_name: guest.first_name ?? '',
      last_name: guest.last_name ?? '',
      birth_date: guest.birth_date?.slice(0, 10) ?? '',
      birth_place: guest.birth_place ?? '',
      nationality: guest.nationality ?? 'IT',
      email: guest.email ?? '',
      phone: guest.phone ?? '',
      address: guest.address ?? '',
      city: guest.city ?? '',
      postal_code: guest.postal_code ?? '',
      country: guest.country ?? 'IT',
      document_type: 'ID_CARD',
      document_number: '',
      document_issuing_country: 'IT',
      document_expiry_date: '',
    })
  }, [details.data, form])

  if (details.isLoading) {
    return <main className="grid min-h-screen place-items-center bg-slate-950 p-5 text-white">Verifica link…</main>
  }
  if (details.isError || !details.data) {
    return (
      <main className="grid min-h-screen place-items-center bg-slate-950 p-5">
        <section className="max-w-md rounded-3xl bg-white p-8 text-center">
          <h1 className="text-2xl font-black">Link non valido</h1>
          <p className="mt-3 text-slate-500">Il collegamento è scaduto, è già stato usato oppure non esiste.</p>
        </section>
      </main>
    )
  }
  if (submit.isSuccess) {
    return (
      <main className="grid min-h-screen place-items-center bg-emerald-950 p-5">
        <section className="max-w-md rounded-3xl bg-white p-8 text-center">
          <p className="text-4xl">✓</p>
          <h1 className="mt-3 text-2xl font-black">Pre-check-in completato</h1>
          <p className="mt-3 text-slate-500">Grazie. La struttura ha ricevuto i dati in modo sicuro.</p>
        </section>
      </main>
    )
  }

  const stay = details.data.data

  return (
    <main className="min-h-screen bg-slate-950 p-4 sm:p-8">
      <div className="mx-auto max-w-4xl">
        <header className="py-8 text-white">
          <p className="text-xs font-bold uppercase tracking-[0.3em] text-violet-300">PMS Casa Vacanze</p>
          <h1 className="mt-3 text-3xl font-black">Pre-check-in</h1>
          <p className="mt-2 text-slate-300">
            {stay.room_name} · {stay.check_in_date} → {stay.check_out_date} · {stay.booking_code}
          </p>
        </header>

        <form className="grid gap-4 rounded-3xl bg-white p-6 sm:grid-cols-2 sm:p-8" onSubmit={form.handleSubmit((values) => submit.mutate(values))}>
          <h2 className="text-xl font-black sm:col-span-2">Dati dell’ospite</h2>
          <input className="rounded-xl border p-3" placeholder="Nome" {...form.register('first_name')} />
          <input className="rounded-xl border p-3" placeholder="Cognome" {...form.register('last_name')} />
          <label className="grid gap-1 text-sm font-semibold">Data di nascita<input className="rounded-xl border p-3 font-normal" type="date" {...form.register('birth_date')} /></label>
          <input className="self-end rounded-xl border p-3" placeholder="Luogo di nascita" {...form.register('birth_place')} />
          <input className="rounded-xl border p-3 uppercase" maxLength={2} placeholder="Nazionalità (IT)" {...form.register('nationality')} />
          <input className="rounded-xl border p-3" type="email" placeholder="Email" {...form.register('email')} />
          <input className="rounded-xl border p-3" placeholder="Telefono" {...form.register('phone')} />
          <input className="rounded-xl border p-3" placeholder="Indirizzo" {...form.register('address')} />
          <input className="rounded-xl border p-3" placeholder="Città" {...form.register('city')} />
          <input className="rounded-xl border p-3" placeholder="CAP" {...form.register('postal_code')} />
          <input className="rounded-xl border p-3 uppercase" maxLength={2} placeholder="Paese (IT)" {...form.register('country')} />

          <h2 className="mt-4 text-xl font-black sm:col-span-2">Documento</h2>
          <select className="rounded-xl border bg-white p-3" {...form.register('document_type')}>
            <option value="ID_CARD">Carta d’identità</option>
            <option value="PASSPORT">Passaporto</option>
            <option value="DRIVING_LICENSE">Patente</option>
            <option value="OTHER">Altro</option>
          </select>
          <input className="rounded-xl border p-3" placeholder="Numero documento" {...form.register('document_number')} />
          <input className="rounded-xl border p-3 uppercase" maxLength={2} placeholder="Paese rilascio (IT)" {...form.register('document_issuing_country')} />
          <label className="grid gap-1 text-sm font-semibold">Scadenza documento<input className="rounded-xl border p-3 font-normal" type="date" {...form.register('document_expiry_date')} /></label>

          {Object.keys(form.formState.errors).length ? (
            <p className="text-sm font-semibold text-red-600 sm:col-span-2">Completa correttamente tutti i campi.</p>
          ) : null}
          {submit.isError ? (
            <p className="text-sm font-semibold text-red-600 sm:col-span-2">{submit.error.message}</p>
          ) : null}
          <p className="text-xs text-slate-500 sm:col-span-2">
            I dati del documento sono cifrati e il link non sarà più utilizzabile dopo l’invio.
          </p>
          <button className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white disabled:opacity-50 sm:col-span-2" disabled={submit.isPending} type="submit">
            {submit.isPending ? 'Invio sicuro…' : 'Completa pre-check-in'}
          </button>
        </form>
      </div>
    </main>
  )
}
