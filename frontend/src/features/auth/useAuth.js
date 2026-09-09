import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getCurrentUser, login, logout } from './api.js';
const AUTH_KEY=['auth','me'];
export function useCurrentUser(){return useQuery({queryKey:AUTH_KEY,queryFn:getCurrentUser,retry:false,staleTime:60_000});}
export function useLogin(){return useMutation({mutationFn:login});}
export function useLogout(){const queryClient=useQueryClient();return useMutation({mutationFn:logout,onSuccess:()=>queryClient.setQueryData(AUTH_KEY,null)});}
export { AUTH_KEY };
