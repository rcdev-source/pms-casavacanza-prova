import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useProperty } from '../hooks/useProperty'
import { api, apiDownload } from '../services/api'
import type { DataResponse } from '../types/core'

type FinancialReport = {
  from: string
  to: string
  currency: string
  reservations: number
  booked_total: string
  payments_received: string
  outstanding: string
}

function isoDate(date: Date) {
  return date.toISOString().slice(0, 10)
}

export function ReportPage() {
  const now = new Date()
  const [from, setFrom] = useState(isoDate(new Date(now.getFullYear(), now.getMonth(), 1)))
  const [to, setTo] = useState(isoDate(now))
  const [downloadError, setDownloadError] = useState('')
  const [downloading, setDownloading] = useState(false)
  const { property } = useProperty()
  const query = new URLSearchParams({ property_id: property?.id ?? '', from, to }).toString()
  const report = useQuery({
    queryKey: ['financial-report', property?.id, from, to],
    enabled: Boolean(property && from && to && from <= to),
    queryFn: () => api<DataResponse<FinancialReport>>('/reports/financial?' + query),
  })

  async function downloadCsv() {
    if (!property) return
    setDownloadError('')
    setDownloading(true)
    try {
      const blob = await apiDownload('/reports/reservations.csv?' + query)
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'prenotazioni-' + from + '-' + to + '.csv'
      link.click()
      URL.revokeObjectURL(url)
    } catch (error) {
      setDownloadError(error instanceof Error ? error.message : 'Download non riuscito.')
    } finally {
      setDownloading(false)
    }
  }

  const data = report.data?.data
  const cards = data
    ? [
        { label: 'Prenotazioni', value: String(data.reservations) },
        { label: 'Valore prenotato', value: data.currency + ' ' + data.booked_total },
        { label: 'Incassi ricevuti', value: data.currency + ' ' + data.payments_received },
        { label: 'Saldo da riscuotere', value: data.currency + ' ' + data.outstanding },
      ]
    : []

  return (
    <div>
      <header>
        <p className="text-sm font-semibold text-violet-600">{property?.name}</p>
        <h2 className="mt-1 text-3xl font-black">Report</h2>
        <p className="mt-2 text-slate-500">Controllo economico ed esportazione delle prenotazioni.</p>
      </header>

      <section className="mt-8 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-5">
        <label className="grid gap-2 text-sm font-bold">
          Dal
          <input className="rounded-xl border border-slate-300 px-3 py-2 font-normal" type="date" value={from} onChange={(event) => setFrom(event.target.value)} />
        </label>
        <label className="grid gap-2 text-sm font-bold">
          Al
          <input className="rounded-xl border border-slate-300 px-3 py-2 font-normal" type="date" value={to} onChange={(event) => setTo(event.target.value)} />
        </label>
        <button className="rounded-xl bg-slate-950 px-4 py-2 font-bold text-white disabled:opacity-50" type="button" disabled={!property || downloading || from > to} onClick={() => void downloadCsv()}>
          {downloading ? 'Preparazione…' : 'Esporta CSV'}
        </button>
      </section>

      {from > to ? <p className="mt-4 text-red-600">La data iniziale deve precedere quella finale.</p> : null}
      {report.isError ? <p className="mt-4 text-red-600">Non è stato possibile caricare il report. Verifica i permessi del tuo ruolo.</p> : null}
      {downloadError ? <p className="mt-4 text-red-600">{downloadError}</p> : null}

      <section className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => (
          <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" key={card.label}>
            <p className="text-sm text-slate-500">{card.label}</p>
            <p className="mt-3 text-2xl font-black text-violet-700">{card.value}</p>
          </article>
        ))}
      </section>
    </div>
  )
}
