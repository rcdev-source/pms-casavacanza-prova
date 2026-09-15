export type Property = {
  id: string
  name: string
  description: string | null
  address: string
  city: string
  postal_code: string
  province: string
  country: string
  email: string | null
  phone: string | null
  default_check_in_time: string
  default_check_out_time: string
  currency: string
  timezone: string
}

export type Amenity = {
  id: string
  name: string
  slug: string
}

export type RoomStatus =
  | 'AVAILABLE'
  | 'RESERVED'
  | 'OCCUPIED'
  | 'DIRTY'
  | 'CLEANING'
  | 'READY'
  | 'MAINTENANCE'
  | 'BLOCKED'

export type Room = {
  id: string
  property_id: string
  name: string
  code: string
  description: string | null
  max_guests: number
  max_adults: number
  max_children: number
  base_price: string
  status: RoomStatus
  is_active: boolean
  amenities: Amenity[]
}

export type Guest = {
  id: string
  first_name: string
  last_name: string
  birth_date: string | null
  email: string | null
  phone: string | null
  country: string | null
}

export type DataResponse<T> = { data: T }
export type PaginatedResponse<T> = { data: T[]; current_page: number; last_page: number; total: number }
