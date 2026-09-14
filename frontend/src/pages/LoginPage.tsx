import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import { loginSchema, type LoginInput } from '../schemas/login'
import { api } from '../services/api'
import type { AuthResponse } from '../types/auth'

export function LoginPage() {
  const navigate = useNavigate()
  const form = useForm<LoginInput>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: 'admin@example.test', password: 'Password123!' },
  })

  const login = useMutation({
    mutationFn: (payload: LoginInput) => api<AuthResponse>('/auth/login', { method: 'POST', body: payload }),
    onSuccess: (response) => {
      localStorage.setItem('pms_token', response.data.token)
      navigate('/dashboard', { replace: true })
    },
  })

  return (
    <main className="grid min-h-screen place-items-center bg-slate-950 p-5">
      <section className="w-full max-w-md rounded-3xl border border-white/10 bg-white p-7 shadow-2xl sm:p-9">
        <p className="text-xs font-bold uppercase tracking-[0.3em] text-violet-600">PMS Casa Vacanze</p>
        <h1 className="mt-3 text-3xl font-black text-slate-950">Bentornato</h1>
        <p className="mt-2 text-sm text-slate-500">Accedi al centro operativo della struttura.</p>

        <form className="mt-8 space-y-5" onSubmit={form.handleSubmit((values) => login.mutate(values))}>
          <label className="block text-sm font-semibold text-slate-700">
            Email
            <input
              className="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-violet-600 focus:ring-4 focus:ring-violet-100"
              type="email"
              autoComplete="email"
              {...form.register('email')}
            />
            <span className="mt-1 block text-xs text-red-600">{form.formState.errors.email?.message}</span>
          </label>

          <label className="block text-sm font-semibold text-slate-700">
            Password
            <input
              className="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-violet-600 focus:ring-4 focus:ring-violet-100"
              type="password"
              autoComplete="current-password"
              {...form.register('password')}
            />
            <span className="mt-1 block text-xs text-red-600">{form.formState.errors.password?.message}</span>
          </label>

          {login.isError ? (
            <p role="alert" className="rounded-xl bg-red-50 p-3 text-sm text-red-700">
              Accesso non riuscito. Verifica le credenziali.
            </p>
          ) : null}

          <button
            className="w-full rounded-xl bg-violet-600 px-4 py-3 font-bold text-white transition hover:bg-violet-700 disabled:opacity-60"
            type="submit"
            disabled={login.isPending}
          >
            {login.isPending ? 'Accesso…' : 'Accedi'}
          </button>
        </form>
      </section>
    </main>
  )
}
