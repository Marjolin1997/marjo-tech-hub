import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { libraryApi } from './api.js';

export function useCategories() { return useQuery({ queryKey: ['categories'], queryFn: libraryApi.categories }); }
export function useTags() { return useQuery({ queryKey: ['tags'], queryFn: libraryApi.tags }); }
export function useEntries(filters) { return useQuery({ queryKey: ['entries', filters], queryFn: () => libraryApi.entries(filters) }); }
export function useEntry(id) { return useQuery({ queryKey: ['entry', id], queryFn: () => libraryApi.entry(id), enabled: Boolean(id) }); }

export function useCreateCategory() {
  const client = useQueryClient();
  return useMutation({ mutationFn: libraryApi.createCategory, onSuccess: () => client.invalidateQueries({ queryKey: ['categories'] }) });
}

export function useCreateTag() {
  const client = useQueryClient();
  return useMutation({ mutationFn: libraryApi.createTag, onSuccess: () => client.invalidateQueries({ queryKey: ['tags'] }) });
}

function invalidateLibrary(client) {
  client.invalidateQueries({ queryKey: ['entries'] });
  client.invalidateQueries({ queryKey: ['categories'] });
  client.invalidateQueries({ queryKey: ['tags'] });
}

function entryMutation(mutationFn) {
  return function useEntryMutation() {
    const client = useQueryClient();
    return useMutation({ mutationFn, onSuccess: () => invalidateLibrary(client) });
  };
}

export const useCreateEntry = entryMutation(libraryApi.createEntry);
export const useUpdateEntry = entryMutation(libraryApi.updateEntry);
export const useDeleteEntry = entryMutation(libraryApi.deleteEntry);

export function useToggleFavorite() {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ id, active }) => active ? libraryApi.unfavorite(id) : libraryApi.favorite(id),
    onSuccess: (_, variables) => {
      invalidateLibrary(client);
      client.invalidateQueries({ queryKey: ['entry', variables.id] });
    },
  });
}
