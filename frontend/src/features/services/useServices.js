import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { serviceApi } from './api.js';

export const serviceKeys = { all: ['services'], list: (filters) => ['services', 'list', filters], detail: (id) => ['services', 'detail', id] };
export function useServices(filters) { return useQuery({ queryKey: serviceKeys.list(filters), queryFn: () => serviceApi.list(filters) }); }
export function useService(id) { return useQuery({ queryKey: serviceKeys.detail(id), queryFn: () => serviceApi.get(id), enabled: Boolean(id) }); }
function mutation(mutationFn) { return function useServiceMutation(){ const qc=useQueryClient(); return useMutation({ mutationFn, onSuccess:()=>qc.invalidateQueries({queryKey:serviceKeys.all}) }); }; }
export const useCreateService=mutation(serviceApi.create);
export const useUpdateService=mutation(serviceApi.update);
export const useDeleteService=mutation(serviceApi.remove);
