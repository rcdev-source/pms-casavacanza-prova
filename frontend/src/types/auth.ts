export type Role = {
  id: string
  key: 'ADMIN' | 'RECEPTION' | 'CLEANING' | 'MAINTENANCE'
  name: string
}

export type User = {
  id: string
  name: string
  email: string
  roles: Role[]
}

export type AuthResponse = {
  data: {
    user: User
    token: string
  }
}
