import http from '../../lib/http.js';

export async function getConversations({search='' }={}){const{data}=await http.get('/api/messaging/conversations',{params:search?{search}:undefined});return data;}
export async function getMessagingUsers(search=''){const{data}=await http.get('/api/messaging/users',{params:search?{search}:undefined});return data.data;}
export async function createConversation(recipientId){const{data}=await http.post('/api/messaging/conversations',{recipient_id:recipientId});return data.data;}
export async function getMessages({conversationId,cursor}){const{data}=await http.get(`/api/messaging/conversations/${conversationId}/messages`,{params:cursor?{cursor}:undefined});return data;}
export async function sendMessage({conversationId,body}){const{data}=await http.post(`/api/messaging/conversations/${conversationId}/messages`,{body});return data.data;}
export async function uploadMessageAttachment({conversationId,file,kind,body='',durationMs=null}){const form=new FormData();form.append('file',file);form.append('kind',kind);if(body.trim())form.append('body',body.trim());if(durationMs!==null)form.append('duration_ms',String(durationMs));const{data}=await http.post(`/api/messaging/conversations/${conversationId}/attachments`,form);return data.data;}
export async function getAttachmentBlob(url){const{data}=await http.get(url,{responseType:'blob'});return data;}
export async function downloadAttachment(attachment){const blob=await getAttachmentBlob(attachment.download_url);const url=URL.createObjectURL(blob);const anchor=document.createElement('a');anchor.href=url;anchor.download=attachment.name||'attachment';document.body.appendChild(anchor);anchor.click();anchor.remove();URL.revokeObjectURL(url);}
export async function markConversationRead({conversationId,messageId}){const{data}=await http.put(`/api/messaging/conversations/${conversationId}/read`,{message_id:messageId});return data;}
export async function archiveConversation(conversationId){const{data}=await http.put(`/api/messaging/conversations/${conversationId}/archive`);return data;}
