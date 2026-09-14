import { Navigate, Route, Routes } from 'react-router'
import { AppLayout } from '../layouts/AppLayout'
import { CalendarPage } from '../pages/CalendarPage'
import { CleaningPage } from '../pages/CleaningPage'
import { DashboardPage } from '../pages/DashboardPage'
import { GuestDetailPage } from '../pages/GuestDetailPage'
import { GuestsPage } from '../pages/GuestsPage'
import { LoginPage } from '../pages/LoginPage'
import { MaintenancePage } from '../pages/MaintenancePage'
import { PublicPreCheckInPage } from '../pages/PublicPreCheckInPage'
import { ReservationDetailPage } from '../pages/ReservationDetailPage'
import { ReservationFormPage } from '../pages/ReservationFormPage'
import { ReservationsPage } from '../pages/ReservationsPage'
import { RoomDetailPage } from '../pages/RoomDetailPage'
import { RoomsPage } from '../pages/RoomsPage'

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  return localStorage.getItem('pms_token') ? children : <Navigate to="/login" replace />
}

export function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/pre-check-in/:token" element={<PublicPreCheckInPage />} />
      <Route
        element={
          <ProtectedRoute>
            <AppLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/calendar" element={<CalendarPage />} />
        <Route path="/rooms" element={<RoomsPage />} />
        <Route path="/rooms/:id" element={<RoomDetailPage />} />
        <Route path="/reservations" element={<ReservationsPage />} />
        <Route path="/reservations/new" element={<ReservationFormPage />} />
        <Route path="/reservations/:id/edit" element={<ReservationFormPage />} />
        <Route path="/reservations/:id" element={<ReservationDetailPage />} />
        <Route path="/guests" element={<GuestsPage />} />
        <Route path="/guests/:id" element={<GuestDetailPage />} />
        <Route path="/cleaning" element={<CleaningPage />} />
        <Route path="/maintenance" element={<MaintenancePage />} />
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
