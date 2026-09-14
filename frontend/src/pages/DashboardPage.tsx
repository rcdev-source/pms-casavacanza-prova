const metrics = [
  { label: 'Check-in oggi', value: '0', accent: 'text-emerald-600' },
  { label: 'Check-out oggi', value: '0', accent: 'text-blue-600' },
  { label: 'Occupazione', value: '0%', accent: 'text-violet-600' },
  { label: 'Da pulire', value: '0', accent: 'text-amber-600' },
  { label: 'Saldi da riscuotere', value: '€0', accent: 'text-red-600' },
]

export function DashboardPage() {
  return (
    <div>
      <header>
        <p className="text-sm font-semibold text-violet-600">Centro operativo</p>
        <h2 className="mt-1 text-3xl font-black">Dashboard</h2>
        <p className="mt-2 text-slate-500">La situazione della struttura, senza perdere tempo.</p>
      </header>

      <section className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        {metrics.map((metric) => (
          <article key={metric.label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{metric.label}</p>
            <p className={'mt-3 text-3xl font-black ' + metric.accent}>{metric.value}</p>
          </article>
        ))}
      </section>

      <section className="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
        <h3 className="text-lg font-bold">Foundation pronta</h3>
        <p className="mt-2 text-sm text-slate-500">Le metriche reali verranno collegate al termine dei moduli operativi.</p>
      </section>
    </div>
  )
}
