import { z } from 'zod'

export const loginSchema = z.object({
  email: z.email('Inserisci un indirizzo email valido.'),
  password: z.string().min(8, 'La password deve contenere almeno 8 caratteri.'),
})

export type LoginInput = z.infer<typeof loginSchema>
