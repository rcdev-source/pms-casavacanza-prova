import type { Room } from './core'

export type CleaningTaskStatus = 'PENDING' | 'ASSIGNED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED'

export type CleaningTask = {
  id: string
  property_id: string
  room_id: string
  reservation_id: string | null
  scheduled_for: string
  status: CleaningTaskStatus
  priority: number
  assigned_to: string | null
  started_at: string | null
  completed_at: string | null
  notes: string | null
  completion_notes: string | null
  room: Room
  assignee?: { id: string; name: string } | null
}

export type MaintenanceStatus = 'OPEN' | 'IN_PROGRESS' | 'RESOLVED' | 'CLOSED' | 'CANCELLED'
export type MaintenancePriority = 'LOW' | 'NORMAL' | 'HIGH' | 'URGENT'

export type MaintenanceTicket = {
  id: string
  property_id: string
  room_id: string
  title: string
  description: string
  status: MaintenanceStatus
  priority: MaintenancePriority
  blocks_room: boolean
  assigned_to: string | null
  started_at: string | null
  resolved_at: string | null
  resolution_notes: string | null
  room: Room
  assignee?: { id: string; name: string } | null
}
