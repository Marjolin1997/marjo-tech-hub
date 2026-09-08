import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { libraryApi } from './api.js';

export function useCategories() {
  return useQuery({ queryKey: ['categories'], queryFn: libraryApi.categories });
}

export function useEntries(filters) {
  return useQuery({
    queryKey: ['entries', filters],
    queryFn: () => libraryApi.entries(filters),
  });
}

export function useCreateCategory() {
  const client = useQueryClient();
  return useMutation({
    mutationFn: libraryApi.createCategory,
    onSuccess: () => client.invalidateQueries({ queryKey: ['categories'] }),
  });
}

function entryMutation(mutationFn) {
  return function useEntryMutation() {
    const client = useQueryClient();
    return useMutation({
      mutationFn,
      onSuccess: () => {
        client.invalidateQueries({ queryKey: ['entries'] });
        client.invalidateQueries({ queryKey: ['categories'] });
      },
    });
  };
}

export const useCreateEntry = entryMutation(libraryApi.createEntry);
export const useUpdateEntry = entryMutation(libraryApi.updateEntry);
export const useDeleteEntry = entryMutation(libraryApi.deleteEntry);
