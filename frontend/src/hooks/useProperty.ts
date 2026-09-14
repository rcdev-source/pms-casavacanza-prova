import { useQuery } from '@tanstack/react-query'
import { api } from '../services/api'
import type { DataResponse, Property } from '../types/core'

export function useProperty() {
  const query = useQuery({
    queryKey: ['properties'],
    queryFn: () => api<DataResponse<Property[]>>('/properties'),
  })

  return {
    ...query,
    property: query.data?.data[0],
  }
}
