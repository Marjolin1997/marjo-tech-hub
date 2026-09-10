import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { clearSearchHistory, getSearchHistory, searchWorkspace } from './api.js';

export function useWorkspaceSearch(query) {
  return useQuery({
    queryKey: ['workspace-search', query],
    queryFn: () => searchWorkspace(query),
    enabled: query.length >= 2,
    staleTime: 15000,
  });
}

export function useSearchHistory(enabled = true) {
  return useQuery({ queryKey: ['search-history'], queryFn: getSearchHistory, enabled, staleTime: 10000 });
}

export function useClearSearchHistory() {
  const queryClient = useQueryClient();
  return useMutation({ mutationFn: clearSearchHistory, onSuccess: () => queryClient.setQueryData(['search-history'], []) });
}
