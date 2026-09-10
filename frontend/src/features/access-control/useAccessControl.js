import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { AUTH_KEY } from '../auth/useAuth.js';
import { getPermissions, getRoles, getUsers, updateUserRoles } from './api.js';
const USERS=['access-control','users'],ROLES=['access-control','roles'],PERMISSIONS=['access-control','permissions'];
export function useAccessUsers(){return useQuery({queryKey:USERS,queryFn:getUsers});}
export function useAccessRoles(){return useQuery({queryKey:ROLES,queryFn:getRoles});}
export function useAccessPermissions(){return useQuery({queryKey:PERMISSIONS,queryFn:getPermissions});}
export function useUpdateUserRoles(){const qc=useQueryClient();return useMutation({mutationFn:updateUserRoles,onSuccess:()=>{qc.invalidateQueries({queryKey:USERS});qc.invalidateQueries({queryKey:AUTH_KEY});}});}
