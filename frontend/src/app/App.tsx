import { Navigate, Route, Routes } from 'react-router'
import { AppLayout } from '../layouts/AppLayout'
import { DashboardPage } from '../pages/DashboardPage'
import { GuestDetailPage } from '../pages/GuestDetailPage'
import { GuestsPage } from '../pages/GuestsPage'
import { LoginPage } from '../pages/LoginPage'
import { RoomDetailPage } from '../pages/RoomDetailPage'
import { RoomsPage } from '../pages/RoomsPage'

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  return localStorage.getItem('pms_token') ? children : <Navigate to="/login" replace />
}

export function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        element={
          <ProtectedRoute>
            <AppLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/rooms" element={<RoomsPage />} />
        <Route path="/rooms/:id" element={<RoomDetailPage />} />
        <Route path="/guests" element={<GuestsPage />} />
        <Route path="/guests/:id" element={<GuestDetailPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
