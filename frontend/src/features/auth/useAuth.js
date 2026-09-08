import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getCurrentUser, login, logout } from './api.js';

const AUTH_KEY = ['auth', 'me'];

export function useCurrentUser() {
  return useQuery({
    queryKey: AUTH_KEY,
    queryFn: getCurrentUser,
    retry: false,
    staleTime: 60_000,
  });
}

export function useLogin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: login,
    onSuccess: (user) => queryClient.setQueryData(AUTH_KEY, user),
  });
}

export function useLogout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: logout,
    onSuccess: () => queryClient.setQueryData(AUTH_KEY, null),
  });
}
