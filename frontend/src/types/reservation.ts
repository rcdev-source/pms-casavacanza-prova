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
  payments?: Payment[]
  services?: ReservationCharge[]
  check_in?: CheckInRecord | null
  check_out?: CheckOutRecord | null
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

export type Payment = {
  id: string
  amount: string
  method: 'CASH' | 'CARD' | 'BANK_TRANSFER' | 'ONLINE' | 'OTHER'
  status: 'PENDING' | 'COMPLETED' | 'FAILED' | 'REFUNDED' | 'CANCELLED'
  transaction_reference: string | null
  paid_at: string | null
  refunded_at: string | null
}

export type ReservationCharge = {
  id: string
  description: string
  quantity: string
  unit_price: string
  total: string
  occurred_at: string
}

export type CheckInRecord = {
  id: string
  completed_at: string
  keys_delivered: boolean
  notes: string | null
}

export type CheckOutRecord = {
  id: string
  completed_at: string
  keys_returned: boolean
  condition_notes: string | null
  damages_amount: string
}

export type PublicPreCheckInData = {
  booking_code: string
  check_in_date: string
  check_out_date: string
  room_name: string
  expires_at: string
  guest: {
    first_name: string
    last_name: string
    birth_date: string | null
    birth_place: string | null
    nationality: string | null
    email: string | null
    phone: string | null
    address: string | null
    city: string | null
    postal_code: string | null
    country: string | null
  }
}
