import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { api } from '../services/api'
import type { DataResponse } from '../types/core'
import type { Payment, Reservation, ReservationCharge } from '../types/reservation'

const paymentSchema = z.object({
  amount: z.coerce.number().positive(),
  method: z.enum(['CASH', 'CARD', 'BANK_TRANSFER', 'ONLINE', 'OTHER']),
  transaction_reference: z.string(),
})

const chargeSchema = z.object({
  description: z.string().min(2),
  quantity: z.coerce.number().positive(),
  unit_price: z.coerce.number().min(0),
})

const checkOutSchema = z.object({
  keys_returned: z.boolean(),
  condition_notes: z.string(),
  damages_amount: z.coerce.number().min(0),
})

type PaymentInput = z.input<typeof paymentSchema>
type PaymentOutput = z.output<typeof paymentSchema>
type ChargeInput = z.input<typeof chargeSchema>
type ChargeOutput = z.output<typeof chargeSchema>
type CheckOutInput = z.input<typeof checkOutSchema>
type CheckOutOutput = z.output<typeof checkOutSchema>

export function ReservationOperations({
  reservation,
  onChanged,
}: {
  reservation: Reservation
  onChanged: () => void
}) {
  const [generatedLink, setGeneratedLink] = useState('')
  const paymentForm = useForm<PaymentInput, unknown, PaymentOutput>({
    resolver: zodResolver(paymentSchema),
    defaultValues: { amount: Number(reservation.balance_due), method: 'CARD', transaction_reference: '' },
  })
  const chargeForm = useForm<ChargeInput, unknown, ChargeOutput>({
    resolver: zodResolver(chargeSchema),
    defaultValues: { description: '', quantity: 1, unit_price: 0 },
  })
  const checkOutForm = useForm<CheckOutInput, unknown, CheckOutOutput>({
    resolver: zodResolver(checkOutSchema),
    defaultValues: { keys_returned: true, condition_notes: '', damages_amount: 0 },
  })

  const payment = useMutation({
    mutationFn: (values: PaymentOutput) =>
      api<DataResponse<Payment>>('/reservations/' + reservation.id + '/payments', {
        method: 'POST',
        body: values,
      }),
    onSuccess: () => {
      paymentForm.reset({ amount: 0, method: 'CARD', transaction_reference: '' })
      onChanged()
    },
  })
  const charge = useMutation({
    mutationFn: (values: ChargeOutput) =>
      api<DataResponse<ReservationCharge>>('/reservations/' + reservation.id + '/services', {
        method: 'POST',
        body: values,
      }),
    onSuccess: () => {
      chargeForm.reset()
      onChanged()
    },
  })
  const issueLink = useMutation({
    mutationFn: () =>
      api<DataResponse<{ url: string; expires_at: string }>>(
        '/reservations/' + reservation.id + '/pre-check-in-link',
        { method: 'POST', body: { expires_in_hours: 72 } },
      ),
    onSuccess: (response) => setGeneratedLink(response.data.url),
  })
  const checkIn = useMutation({
    mutationFn: () =>
      api('/reservations/' + reservation.id + '/check-in', {
        method: 'POST',
        body: { keys_delivered: true, notes: 'Check-in completato dal PMS' },
      }),
    onSuccess: onChanged,
  })
  const checkOut = useMutation({
    mutationFn: (values: CheckOutOutput) =>
      api('/reservations/' + reservation.id + '/check-out', {
        method: 'POST',
        body: values,
      }),
    onSuccess: () => {
      checkOutForm.reset()
      onChanged()
    },
  })

  const canPreCheckIn = ['CONFIRMED', 'PRE_CHECKIN'].includes(reservation.status)
  const canCheckIn = ['CONFIRMED', 'PRE_CHECKIN'].includes(reservation.status)
  const canCheckOut = ['CHECKED_IN', 'IN_HOUSE'].includes(reservation.status)
  const operationError =
    payment.error ?? charge.error ?? issueLink.error ?? checkIn.error ?? checkOut.error

  return (
    <div className="mt-7 grid gap-5">
      <section className="rounded-2xl border border-slate-200 bg-white p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 className="text-lg font-black">Pagamenti</h3>
            <p className="mt-1 text-sm text-slate-500">
              Saldo dovuto: <strong>{reservation.currency} {reservation.balance_due}</strong>
            </p>
          </div>
        </div>

        <div className="mt-5 grid gap-2">
          {reservation.payments?.map((item) => (
            <div className="flex flex-wrap justify-between gap-3 rounded-xl bg-slate-50 p-3 text-sm" key={item.id}>
              <span className="font-semibold">{item.method} · {item.status}</span>
              <strong>{reservation.currency} {item.amount}</strong>
            </div>
          ))}
          {!reservation.payments?.length ? <p className="text-sm text-slate-400">Nessun pagamento.</p> : null}
        </div>

        {Number(reservation.balance_due) > 0 ? (
          <form className="mt-5 grid gap-3 sm:grid-cols-3" onSubmit={paymentForm.handleSubmit((values) => payment.mutate(values))}>
            <input className="rounded-xl border p-3" min="0.01" step="0.01" type="number" aria-label="Importo pagamento" {...paymentForm.register('amount')} />
            <select className="rounded-xl border bg-white p-3" aria-label="Metodo pagamento" {...paymentForm.register('method')}>
              <option value="CARD">Carta</option>
              <option value="CASH">Contanti</option>
              <option value="BANK_TRANSFER">Bonifico</option>
              <option value="ONLINE">Online</option>
              <option value="OTHER">Altro</option>
            </select>
            <input className="rounded-xl border p-3" placeholder="Riferimento (facoltativo)" {...paymentForm.register('transaction_reference')} />
            <button className="rounded-xl bg-emerald-600 px-4 py-3 font-bold text-white sm:col-span-3" disabled={payment.isPending} type="submit">
              Registra pagamento
            </button>
          </form>
        ) : (
          <p className="mt-5 rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800">Prenotazione saldata.</p>
        )}
      </section>

      <section className="rounded-2xl border border-slate-200 bg-white p-6">
        <h3 className="text-lg font-black">Extra e servizi</h3>
        <div className="mt-4 grid gap-2">
          {reservation.services?.map((item) => (
            <div className="flex justify-between rounded-xl bg-slate-50 p-3 text-sm" key={item.id}>
              <span>{item.description} · {item.quantity} × {item.unit_price}</span>
              <strong>{reservation.currency} {item.total}</strong>
            </div>
          ))}
          {!reservation.services?.length ? <p className="text-sm text-slate-400">Nessun extra.</p> : null}
        </div>
        <form className="mt-5 grid gap-3 sm:grid-cols-[1fr_100px_140px]" onSubmit={chargeForm.handleSubmit((values) => charge.mutate(values))}>
          <input className="rounded-xl border p-3" placeholder="Descrizione" {...chargeForm.register('description')} />
          <input className="rounded-xl border p-3" min="0.01" step="0.01" type="number" aria-label="Quantità" {...chargeForm.register('quantity')} />
          <input className="rounded-xl border p-3" min="0" step="0.01" type="number" aria-label="Prezzo unitario" {...chargeForm.register('unit_price')} />
          <button className="rounded-xl border border-violet-300 px-4 py-3 font-bold text-violet-700 sm:col-span-3" disabled={charge.isPending} type="submit">
            Aggiungi extra
          </button>
        </form>
      </section>

      <section className="rounded-2xl border border-slate-200 bg-white p-6">
        <h3 className="text-lg font-black">Operazioni soggiorno</h3>
        <div className="mt-5 grid gap-4 md:grid-cols-2">
          <div className="rounded-xl bg-violet-50 p-4">
            <p className="font-black">Pre-check-in ospite</p>
            <p className="mt-1 text-sm text-slate-600">Link sicuro, monouso, valido 72 ore.</p>
            {canPreCheckIn ? (
              <button className="mt-4 rounded-lg bg-violet-600 px-3 py-2 text-sm font-bold text-white" type="button" disabled={issueLink.isPending} onClick={() => issueLink.mutate()}>
                Genera link
              </button>
            ) : <p className="mt-4 text-sm text-slate-500">Non disponibile nello stato attuale.</p>}
            {generatedLink ? (
              <div className="mt-3 grid gap-2">
                <input className="w-full rounded-lg border bg-white p-2 text-xs" readOnly value={generatedLink} />
                <div className="flex gap-2">
                  <button className="rounded-lg border bg-white px-3 py-2 text-xs font-bold" type="button" onClick={() => void navigator.clipboard.writeText(generatedLink)}>Copia</button>
                  <a className="rounded-lg border bg-white px-3 py-2 text-xs font-bold" href={generatedLink} rel="noreferrer" target="_blank">Apri</a>
                </div>
              </div>
            ) : null}
          </div>

          <div className="rounded-xl bg-slate-50 p-4">
            <p className="font-black">Check-in</p>
            <p className="mt-1 text-sm text-slate-600">
              {reservation.check_in ? 'Completato' : 'Conferma consegna chiavi e arrivo.'}
            </p>
            {canCheckIn ? (
              <button className="mt-4 rounded-lg bg-slate-950 px-3 py-2 text-sm font-bold text-white" type="button" disabled={checkIn.isPending} onClick={() => checkIn.mutate()}>
                Completa check-in
              </button>
            ) : null}
          </div>
        </div>

        {canCheckOut ? (
          <form className="mt-4 grid gap-3 rounded-xl bg-amber-50 p-4 sm:grid-cols-2" onSubmit={checkOutForm.handleSubmit((values) => checkOut.mutate(values))}>
            <h4 className="font-black sm:col-span-2">Check-out</h4>
            <label className="flex items-center gap-2 text-sm font-semibold">
              <input type="checkbox" {...checkOutForm.register('keys_returned')} /> Chiavi riconsegnate
            </label>
            <label className="grid gap-1 text-sm font-semibold">
              Danni (€)
              <input className="rounded-lg border bg-white p-2 font-normal" min="0" step="0.01" type="number" {...checkOutForm.register('damages_amount')} />
            </label>
            <textarea className="rounded-lg border bg-white p-3 sm:col-span-2" placeholder="Condizioni camera e note" {...checkOutForm.register('condition_notes')} />
            <button className="rounded-lg bg-amber-600 px-3 py-2 font-bold text-white sm:col-span-2" disabled={checkOut.isPending} type="submit">
              Completa check-out
            </button>
          </form>
        ) : null}
        {reservation.check_out ? <p className="mt-4 rounded-xl bg-slate-100 p-3 text-sm font-bold">Check-out completato. Camera inviata alle pulizie.</p> : null}
        {operationError ? <p className="mt-4 text-sm font-semibold text-red-600">{operationError.message}</p> : null}
      </section>
    </div>
  )
}
