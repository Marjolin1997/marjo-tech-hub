import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deleteDocument, listDocuments, updateDocument, uploadDocument } from './api.js';

export const documentKeys = { all: ['documents'], list: (filters) => ['documents', 'list', filters] };
export function useDocuments(filters) { return useQuery({ queryKey: documentKeys.list(filters), queryFn: () => listDocuments(filters) }); }
export function useUploadDocument() { const qc=useQueryClient(); return useMutation({ mutationFn:uploadDocument, onSuccess:()=>qc.invalidateQueries({queryKey:documentKeys.all}) }); }
export function useUpdateDocument() { const qc=useQueryClient(); return useMutation({ mutationFn:({id,payload})=>updateDocument(id,payload), onSuccess:()=>qc.invalidateQueries({queryKey:documentKeys.all}) }); }
export function useDeleteDocument() { const qc=useQueryClient(); return useMutation({ mutationFn:deleteDocument, onSuccess:()=>qc.invalidateQueries({queryKey:documentKeys.all}) }); }
