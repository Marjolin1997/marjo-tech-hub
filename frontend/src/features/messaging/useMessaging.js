import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { archiveConversation, createConversation, getConversations, getMessages, getMessagingUsers, markConversationRead, sendMessage } from './api.js';

export const CONVERSATIONS_KEY=['messaging','conversations'];
export function useConversations(search=''){return useQuery({queryKey:[...CONVERSATIONS_KEY,search],queryFn:()=>getConversations({search}),refetchInterval:10000,refetchIntervalInBackground:false});}
export function useMessagingUsers(search='',enabled=true){return useQuery({queryKey:['messaging','users',search],queryFn:()=>getMessagingUsers(search),enabled,staleTime:30000});}
export function useMessages(conversationId){return useInfiniteQuery({queryKey:['messaging','messages',conversationId],queryFn:({pageParam})=>getMessages({conversationId,cursor:pageParam}),initialPageParam:null,getNextPageParam:last=>last.next_cursor||undefined,enabled:Boolean(conversationId),refetchInterval:conversationId?5000:false,refetchIntervalInBackground:false});}
export function useCreateConversation(){const qc=useQueryClient();return useMutation({mutationFn:createConversation,onSuccess:()=>qc.invalidateQueries({queryKey:CONVERSATIONS_KEY})});}
export function useSendMessage(){const qc=useQueryClient();return useMutation({mutationFn:sendMessage,onSuccess:(_,v)=>{qc.invalidateQueries({queryKey:['messaging','messages',v.conversationId]});qc.invalidateQueries({queryKey:CONVERSATIONS_KEY});}});}
export function useMarkRead(){const qc=useQueryClient();return useMutation({mutationFn:markConversationRead,onSuccess:()=>qc.invalidateQueries({queryKey:CONVERSATIONS_KEY})});}
export function useArchiveConversation(){const qc=useQueryClient();return useMutation({mutationFn:archiveConversation,onSuccess:()=>qc.invalidateQueries({queryKey:CONVERSATIONS_KEY})});}
