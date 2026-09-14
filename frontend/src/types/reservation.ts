import type { Guest, Room } from './core'

export type ReservationStatus =
  | 'DRAFT'
  | 'REQUESTED'
  | 'CONFIRMED'
  | 'PRE_CHECKIN'
  | 'CHECKED_IN'
  | 'IN_HOUSE'
  | 'CHECKED_OUT'
  | 'COMPLETED'
  | 'CANCELLED'
  | 'NO_SHOW'

export type ReservationSource =
  | 'DIRECT'
  | 'WEBSITE'
  | 'BOOKING'
  | 'AIRBNB'
  | 'PHONE'
  | 'EMAIL'
  | 'OTHER'

export type Reservation = {
  id: string
  property_id: string
  room_id: string
  booking_code: string
  primary_guest_id: string
  check_in_date: string
  check_out_date: string
  adults: number
  children: number
  status: ReservationStatus
  source: ReservationSource
  currency: string
  subtotal: string
  discount: string
  taxes: string
  extras_total: string
  total: string
  deposit_required: boolean
  deposit_amount: string
  balance_due: string
  notes: string | null
  internal_notes: string | null
  room?: Room
  primary_guest?: Guest
  guests?: Guest[]
}

export type PricingPreview = {
  nights: number
  minimum_stay: number
  subtotal: string
  breakdown: { date: string; price: string; rule: string | null }[]
}

export type AvailableRoom = Room & { pricing: PricingPreview }

export type CalendarRoom = Room & {
  reservations: Reservation[]
  availability_blocks: {
    id: string
    start_date: string
    end_date: string
    reason: string | null
  }[]
}

export type CalendarData = {
  from: string
  to: string
  rooms: CalendarRoom[]
}
