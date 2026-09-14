import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router'
import { beforeEach, describe, expect, it } from 'vitest'
import { App } from './App'

describe('App routing', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('redirects unauthenticated users to login', () => {
    render(
      <QueryClientProvider client={new QueryClient()}>
        <MemoryRouter initialEntries={['/dashboard']}>
          <App />
        </MemoryRouter>
      </QueryClientProvider>,
    )

    expect(screen.getByRole('heading', { name: 'Bentornato' })).toBeInTheDocument()
  })
})
