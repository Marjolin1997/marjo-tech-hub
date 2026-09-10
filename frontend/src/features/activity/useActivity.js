import { useQuery } from '@tanstack/react-query';
import { fetchActivity, fetchActivityEvent } from './api.js';

export function useActivity(params) {
  return useQuery({ queryKey: ['activity', params], queryFn: () => fetchActivity(params), placeholderData: previous => previous });
}

export function useActivityEvent(id) {
  return useQuery({ queryKey: ['activity', id], queryFn: () => fetchActivityEvent(id), enabled: Boolean(id) });
}
