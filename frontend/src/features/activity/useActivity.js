import { useQuery } from '@tanstack/react-query';
import { fetchActivity, fetchActivityEvent, fetchActivityFilterOptions } from './api.js';

export function useActivity(params) {
  return useQuery({ queryKey: ['activity', params], queryFn: () => fetchActivity(params), placeholderData: previous => previous });
}

export function useActivityEvent(id) {
  return useQuery({ queryKey: ['activity', 'detail', id], queryFn: () => fetchActivityEvent(id), enabled: Boolean(id) });
}

export function useActivityFilterOptions() {
  return useQuery({ queryKey: ['activity', 'filter-options'], queryFn: fetchActivityFilterOptions, staleTime: 30_000 });
}
