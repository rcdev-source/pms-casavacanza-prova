import type { Reservation } from './reservation'

export type DashboardData = {
  date: string
  metrics: {
    arrivals: number
    departures: number
    occupancy_percent: number
    rooms_total: number
    rooms_occupied: number
    cleaning_open: number
    maintenance_open: number
    revenue_month: string
    outstanding: string
  }
  arrivals: Reservation[]
  departures: Reservation[]
  recent_reservations: Reservation[]
}

export type AppNotification = {
  id: string
  type: string
  title: string
  message: string
  action_url: string | null
  read_at: string | null
  created_at: string
}
