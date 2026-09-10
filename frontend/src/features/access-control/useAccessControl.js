import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { AUTH_KEY } from '../auth/useAuth.js';
import { getInvitations, getPermissions, getRoles, getUsers, inviteUser, resendInvitation, revokeInvitation, updateUserRoles } from './api.js';
const USERS=['access-control','users'],ROLES=['access-control','roles'],PERMISSIONS=['access-control','permissions'],INVITATIONS=['access-control','invitations'];
export function useAccessUsers(){return useQuery({queryKey:USERS,queryFn:getUsers});}
export function useAccessRoles(){return useQuery({queryKey:ROLES,queryFn:getRoles});}
export function useAccessPermissions(){return useQuery({queryKey:PERMISSIONS,queryFn:getPermissions});}
export function useAccessInvitations(){return useQuery({queryKey:INVITATIONS,queryFn:getInvitations});}
export function useUpdateUserRoles(){const qc=useQueryClient();return useMutation({mutationFn:updateUserRoles,onSuccess:()=>{qc.invalidateQueries({queryKey:USERS});qc.invalidateQueries({queryKey:AUTH_KEY});}});}
export function useInviteUser(){const qc=useQueryClient();return useMutation({mutationFn:inviteUser,onSuccess:()=>qc.invalidateQueries({queryKey:INVITATIONS})});}
export function useResendInvitation(){const qc=useQueryClient();return useMutation({mutationFn:resendInvitation,onSuccess:()=>qc.invalidateQueries({queryKey:INVITATIONS})});}
export function useRevokeInvitation(){const qc=useQueryClient();return useMutation({mutationFn:revokeInvitation,onSuccess:()=>qc.invalidateQueries({queryKey:INVITATIONS})});}
