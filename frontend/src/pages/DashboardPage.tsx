import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router'
import { useProperty } from '../hooks/useProperty'
import { api } from '../services/api'
import type { DataResponse } from '../types/core'
import type { AppNotification, DashboardData } from '../types/dashboard'

export function DashboardPage() {
  const { property } = useProperty()
  const queryClient = useQueryClient()
  const dashboard = useQuery({
    queryKey: ['dashboard', property?.id],
    enabled: Boolean(property),
    refetchInterval: 60_000,
    queryFn: () => api<DataResponse<DashboardData>>('/dashboard?property_id=' + property!.id),
  })
  const notifications = useQuery({
    queryKey: ['notifications', property?.id],
    enabled: Boolean(property),
    refetchInterval: 60_000,
    queryFn: () => api<DataResponse<AppNotification[]>>('/notifications?property_id=' + property!.id),
  })
  const read = useMutation({
    mutationFn: (id: string) => api('/notifications/' + id + '/read', { method: 'POST' }),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['notifications', property?.id] }),
  })
  const readAll = useMutation({
    mutationFn: () => api('/notifications/read-all', { method: 'POST', body: { property_id: property!.id } }),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['notifications', property?.id] }),
  })

  if (!property || dashboard.isLoading) return <p>Caricamento centro operativo…</p>
  if (!dashboard.data) return <p className="text-red-600">Impossibile caricare la dashboard.</p>
  const data = dashboard.data.data
  const metrics = [
    { label: 'Check-in oggi', value: String(data.metrics.arrivals), accent: 'text-emerald-600', to: '/reservations' },
    { label: 'Check-out oggi', value: String(data.metrics.departures), accent: 'text-blue-600', to: '/reservations' },
    { label: 'Occupazione', value: data.metrics.occupancy_percent + '%', accent: 'text-violet-600', to: '/calendar' },
    { label: 'Da pulire', value: String(data.metrics.cleaning_open), accent: 'text-amber-600', to: '/cleaning' },
    { label: 'Guasti aperti', value: String(data.metrics.maintenance_open), accent: 'text-red-600', to: '/maintenance' },
    { label: 'Incassi mese', value: '€' + data.metrics.revenue_month, accent: 'text-emerald-700', to: '/reservations' },
    { label: 'Da riscuotere', value: '€' + data.metrics.outstanding, accent: 'text-red-700', to: '/reservations' },
  ]
  const unreadCount = notifications.data?.data.filter((item) => !item.read_at).length ?? 0

  return (
    <div>
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-violet-600">{property.name}</p>
          <h2 className="mt-1 text-3xl font-black">Dashboard</h2>
          <p className="mt-2 text-slate-500">La situazione reale della struttura, aggiornata ogni minuto.</p>
        </div>
        <Link className="rounded-xl bg-violet-600 px-4 py-3 font-bold text-white" to="/reservations/new">
          Nuova prenotazione
        </Link>
      </header>

      <section className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {metrics.map((metric) => (
          <Link key={metric.label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" to={metric.to}>
            <p className="text-sm text-slate-500">{metric.label}</p>
            <p className={'mt-3 text-3xl font-black ' + metric.accent}>{metric.value}</p>
          </Link>
        ))}
      </section>

      <div className="mt-8 grid gap-5 xl:grid-cols-[1.3fr_1fr]">
        <section className="rounded-2xl border border-slate-200 bg-white p-6">
          <h3 className="text-lg font-black">Movimenti di oggi</h3>
          <div className="mt-5 grid gap-5 md:grid-cols-2">
            <div>
              <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">Arrivi</p>
              <div className="mt-3 grid gap-2">
                {data.arrivals.map((item) => (
                  <Link className="rounded-xl bg-emerald-50 p-3 text-sm" key={item.id} to={'/reservations/' + item.id}>
                    <strong>{item.primary_guest?.last_name} {item.primary_guest?.first_name}</strong>
                    <span className="mt-1 block text-slate-500">{item.room?.name} · {item.booking_code}</span>
                  </Link>
                ))}
                {!data.arrivals.length ? <p className="text-sm text-slate-400">Nessun arrivo oggi.</p> : null}
              </div>
            </div>
            <div>
              <p className="text-xs font-bold uppercase tracking-wider text-blue-600">Partenze</p>
              <div className="mt-3 grid gap-2">
                {data.departures.map((item) => (
                  <Link className="rounded-xl bg-blue-50 p-3 text-sm" key={item.id} to={'/reservations/' + item.id}>
                    <strong>{item.primary_guest?.last_name} {item.primary_guest?.first_name}</strong>
                    <span className="mt-1 block text-slate-500">{item.room?.name} · {item.booking_code}</span>
                  </Link>
                ))}
                {!data.departures.length ? <p className="text-sm text-slate-400">Nessuna partenza oggi.</p> : null}
              </div>
            </div>
          </div>

          <h3 className="mt-8 text-lg font-black">Prenotazioni recenti</h3>
          <div className="mt-4 divide-y divide-slate-100">
            {data.recent_reservations.map((item) => (
              <Link className="flex items-center justify-between gap-4 py-3 text-sm" key={item.id} to={'/reservations/' + item.id}>
                <span><strong>{item.primary_guest?.last_name}</strong><span className="ml-2 text-slate-400">{item.room?.name}</span></span>
                <span className="font-bold text-violet-600">{item.status}</span>
              </Link>
            ))}
          </div>
        </section>

        <aside className="h-fit rounded-2xl bg-slate-950 p-6 text-white">
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs font-bold uppercase tracking-[0.2em] text-violet-300">Centro avvisi</p>
              <h3 className="mt-1 text-lg font-black">Notifiche {unreadCount ? '(' + unreadCount + ')' : ''}</h3>
            </div>
            {unreadCount ? (
              <button className="text-xs font-bold text-violet-300" type="button" onClick={() => readAll.mutate()}>
                Leggi tutte
              </button>
            ) : null}
          </div>
          <div className="mt-5 grid gap-3">
            {notifications.data?.data.slice(0, 10).map((item) => (
              <div className={'rounded-xl border p-3 ' + (item.read_at ? 'border-slate-800 text-slate-400' : 'border-violet-500 bg-violet-500/10')} key={item.id}>
                <Link className="block" to={item.action_url ?? '/dashboard'} onClick={() => !item.read_at && read.mutate(item.id)}>
                  <strong className="text-sm">{item.title}</strong>
                  <span className="mt-1 block text-xs">{item.message}</span>
                </Link>
              </div>
            ))}
            {!notifications.data?.data.length ? <p className="text-sm text-slate-400">Nessuna notifica.</p> : null}
          </div>
        </aside>
      </div>
    </div>
  )
}
