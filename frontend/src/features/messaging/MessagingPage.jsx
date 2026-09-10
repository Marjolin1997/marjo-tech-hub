import { ArrowLeftOutlined, InboxOutlined, MessageOutlined, MoreOutlined, PlusOutlined, SearchOutlined, UserOutlined } from '@ant-design/icons';
import { Avatar, Badge, Button, Dropdown, Empty, Input, List, Modal, Select, Skeleton, Space, Typography, message } from 'antd';
import { useDeferredValue, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { can } from '../auth/permissions.js';
import { useCurrentUser } from '../auth/useAuth.js';
import MessageAttachment from './MessageAttachment.jsx';
import RichComposer from './RichComposer.jsx';
import { useArchiveConversation, useConversations, useCreateConversation, useMarkRead, useMessages, useMessagingUsers, useSendMessage, useUploadMessageAttachment } from './useMessaging.js';

const { Text, Title }=Typography;
const initials=name=>(name||'User').split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase();
const time=value=>value?new Intl.DateTimeFormat(undefined,{hour:'2-digit',minute:'2-digit'}).format(new Date(value)):'';
const errorText=e=>e?.response?.data?.message||e?.message||'Something went wrong. Please try again.';

export default function MessagingPage(){
  const navigate=useNavigate(),[params,setParams]=useSearchParams(),{data:user}=useCurrentUser();
  const [search,setSearch]=useState(''),deferredSearch=useDeferredValue(search.trim()),[activeId,setActiveId]=useState(()=>Number(params.get('conversation'))||null),[composer,setComposer]=useState(''),[newOpen,setNewOpen]=useState(false),[userSearch,setUserSearch]=useState(''),[recipientId,setRecipientId]=useState(null);
  const bottomRef=useRef(null),[messageApi,contextHolder]=message.useMessage();
  const conversations=useConversations(deferredSearch),rows=conversations.data?.data??[];
  const active=rows.find(c=>c.id===activeId)||null;
  const messages=useMessages(activeId),createConversation=useCreateConversation(),send=useSendMessage(),upload=useUploadMessageAttachment(),markRead=useMarkRead(),archive=useArchiveConversation();
  const users=useMessagingUsers(userSearch,newOpen),canSend=can(user,'messages.send');
  const messageRows=useMemo(()=>{const pages=messages.data?.pages??[];return pages.flatMap(p=>p.data??[]).sort((a,b)=>a.id-b.id);},[messages.data]);
  const selectConversation=id=>{setActiveId(id);setParams(id?{conversation:String(id)}:{},{replace:true});};
  useEffect(()=>{if(!activeId&&rows.length)selectConversation(rows[0].id)},[rows,activeId]);
  useEffect(()=>{bottomRef.current?.scrollIntoView({block:'end'})},[activeId,messageRows.length]);
  useEffect(()=>{const latest=messageRows.at(-1);if(activeId&&latest&&latest.sender_id!==user?.id){markRead.mutate({conversationId:activeId,messageId:latest.id});}},[activeId,messageRows.at(-1)?.id,user?.id]);

  const start=async()=>{if(!recipientId)return;try{const c=await createConversation.mutateAsync(recipientId);setNewOpen(false);setRecipientId(null);setUserSearch('');setSearch('');selectConversation(c.id);}catch(e){messageApi.error(errorText(e))}};
  const submit=async()=>{const body=composer.trim();if(!body||!activeId||send.isPending||upload.isPending)return;setComposer('');try{await send.mutateAsync({conversationId:activeId,body});}catch(e){setComposer(body);messageApi.error(errorText(e))}};
  const uploadAttachment=async({file,kind,durationMs=null})=>{if(!activeId||upload.isPending)throw new Error('An upload is already in progress.');try{await upload.mutateAsync({conversationId:activeId,file,kind,durationMs});messageApi.success(kind==='voice'?'Voice message sent':'Attachment sent');}catch(e){messageApi.error(errorText(e));throw e;}};
  const doArchive=async()=>{if(!activeId)return;try{await archive.mutateAsync(activeId);selectConversation(null);messageApi.success('Conversation archived');}catch(e){messageApi.error(errorText(e))}};
  const options=(users.data??[]).map(u=>({value:u.id,label:<Space><Avatar size="small">{initials(u.name)}</Avatar><span>{u.name}{u.username?` · @${u.username}`:''}</span></Space>}));

  return <div className={`messaging-page ${active?'has-active':''}`}>{contextHolder}
    <aside className="conversation-panel"><div className="messaging-panel-head"><div><Text className="eyebrow">WORKSPACE</Text><Title level={2}>Messages</Title></div><Space><Button onClick={()=>navigate('/')} aria-label="Back to workspace">Workspace</Button>{canSend&&<Button type="primary" icon={<PlusOutlined/>} onClick={()=>setNewOpen(true)}>New</Button>}</Space></div>
      <Input allowClear prefix={<SearchOutlined/>} value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search conversations" className="conversation-search"/>
      <div className="conversation-list">{conversations.isLoading?<Skeleton active paragraph={{rows:7}}/>:conversations.isError?<Empty description="Could not load conversations"><Button onClick={()=>conversations.refetch()}>Retry</Button></Empty>:rows.length===0?<Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={deferredSearch?'No conversations match your search.':'No conversations yet.'}>{canSend&&<Button type="primary" onClick={()=>setNewOpen(true)}>Start a conversation</Button>}</Empty>:<List dataSource={rows} renderItem={c=><List.Item className={`conversation-row ${activeId===c.id?'active':''}`} onClick={()=>selectConversation(c.id)}><List.Item.Meta avatar={<Badge count={c.unread_count} size="small"><Avatar>{initials(c.participant?.name)}</Avatar></Badge>} title={<div className="conversation-title"><Text strong>{c.participant?.name||'Workspace user'}</Text><Text type="secondary">{time(c.last_message_at)}</Text></div>} description={<Text type={c.unread_count?'':'secondary'} strong={Boolean(c.unread_count)} ellipsis>{c.last_message?.body||(c.last_message?.attachments?.length?'Attachment':'Start the conversation')}</Text>}/></List.Item>}/>} </div>
    </aside>
    <main className="chat-panel">{!active?<div className="chat-empty"><Empty image={<MessageOutlined className="chat-empty-icon"/>} description={<><Title level={4}>Your workspace conversations</Title><Text type="secondary">Select a conversation or start a new one.</Text></>}/></div>:<>
      <header className="chat-header"><Button className="mobile-back" type="text" icon={<ArrowLeftOutlined/>} onClick={()=>selectConversation(null)}/><Avatar size={42}>{initials(active.participant?.name)}</Avatar><div className="chat-person"><Text strong>{active.participant?.name}</Text><Text type="secondary">{active.participant?.job_title||(active.participant?.username?`@${active.participant.username}`:'Workspace member')}</Text></div><Dropdown menu={{items:[{key:'archive',icon:<InboxOutlined/>,label:'Archive conversation',onClick:doArchive}]}}><Button type="text" icon={<MoreOutlined/>} aria-label="Conversation actions"/></Dropdown></header>
      <section className="message-history">{messages.hasNextPage&&<div className="load-older"><Button loading={messages.isFetchingNextPage} onClick={()=>messages.fetchNextPage()}>Load older messages</Button></div>}{messages.isLoading?<Skeleton active paragraph={{rows:8}}/>:messages.isError?<Empty description="Could not load message history"><Button onClick={()=>messages.refetch()}>Retry</Button></Empty>:messageRows.length===0?<div className="message-day-empty"><UserOutlined/><Text type="secondary">This is the beginning of your conversation with {active.participant?.name}.</Text></div>:messageRows.map(m=>{const mine=m.sender_id===user?.id;return <div key={m.id} className={`message-line ${mine?'mine':'theirs'}`}><div className="message-bubble">{m.body&&<div className="message-body">{m.body}</div>}{(m.attachments??[]).map(a=><MessageAttachment key={a.id} attachment={a}/>)}<Text className="message-time">{time(m.created_at)}</Text></div></div>})}<div ref={bottomRef}/></section>
      <RichComposer value={composer} onChange={setComposer} onSend={submit} onUpload={uploadAttachment} onError={e=>messageApi.error(errorText(e))} disabled={!canSend} busy={send.isPending||upload.isPending}/>
    </>}</main>
    <Modal title="New conversation" open={newOpen} onCancel={()=>setNewOpen(false)} onOk={start} okText="Start conversation" confirmLoading={createConversation.isPending} okButtonProps={{disabled:!recipientId}} destroyOnHidden><Text type="secondary">Choose a workspace member. Private conversations are visible only to their participants.</Text><Select showSearch filterOption={false} onSearch={setUserSearch} value={recipientId} onChange={setRecipientId} options={options} loading={users.isFetching} placeholder="Search workspace members" style={{width:'100%',marginTop:18}} notFoundContent={users.isLoading?'Loading…':'No users found'}/></Modal>
  </div>;
}
