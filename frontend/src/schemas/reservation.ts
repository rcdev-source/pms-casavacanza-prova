import { z } from 'zod'

export const reservationSchema = z
  .object({
    room_id: z.string().min(1, 'Seleziona una camera'),
    primary_guest_id: z.string().min(1, 'Seleziona l’ospite principale'),
    check_in_date: z.string().min(1, 'Inserisci il check-in'),
    check_out_date: z.string().min(1, 'Inserisci il check-out'),
    adults: z.coerce.number().int().min(1).max(50),
    children: z.coerce.number().int().min(0).max(50),
    source: z.enum(['DIRECT', 'WEBSITE', 'BOOKING', 'AIRBNB', 'PHONE', 'EMAIL', 'OTHER']),
    notes: z.string().max(10000),
  })
  .refine((value) => value.check_out_date > value.check_in_date, {
    message: 'Il check-out deve essere successivo al check-in',
    path: ['check_out_date'],
  })

export type ReservationInput = z.input<typeof reservationSchema>
export type ReservationOutput = z.output<typeof reservationSchema>
