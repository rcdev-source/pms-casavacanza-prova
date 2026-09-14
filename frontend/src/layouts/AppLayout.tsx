import { NavLink, Outlet, useNavigate } from 'react-router'

const links = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/calendar', label: 'Calendario' },
  { to: '/rooms', label: 'Camere' },
  { to: '/reservations', label: 'Prenotazioni' },
  { to: '/guests', label: 'Ospiti' },
  { to: '/cleaning', label: 'Pulizie' },
  { to: '/maintenance', label: 'Manutenzione' },
  { to: '/reports', label: 'Report' },
]

export function AppLayout() {
  const navigate = useNavigate()

  function logout() {
    localStorage.removeItem('pms_token')
    navigate('/login', { replace: true })
  }

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 lg:grid lg:grid-cols-[260px_1fr]">
      <aside className="border-b border-slate-200 bg-slate-950 p-5 text-white lg:min-h-screen lg:border-b-0 lg:border-r">
        <div className="mb-8">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-violet-300">Catania</p>
          <h1 className="mt-2 text-xl font-bold">PMS Casa Vacanze</h1>
        </div>
        <nav className="flex gap-2 overflow-x-auto lg:flex-col">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) =>
                [
                  'whitespace-nowrap rounded-xl px-3 py-2 text-sm transition',
                  isActive ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                ].join(' ')
              }
            >
              {link.label}
            </NavLink>
          ))}
        </nav>
        <button className="mt-8 text-sm text-slate-400 hover:text-white" type="button" onClick={logout}>
          Esci
        </button>
      </aside>
      <main className="p-4 sm:p-6 lg:p-10">
        <Outlet />
      </main>
    </div>
  )
}
