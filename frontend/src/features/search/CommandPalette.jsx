import { ClockCircleOutlined, FileTextOutlined, FolderOutlined, MessageOutlined, SearchOutlined, TagOutlined, UserOutlined } from '@ant-design/icons';
import { Button, Empty, Input, Modal, Skeleton, Space, Typography } from 'antd';
import { useDeferredValue, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useClearSearchHistory, useSearchHistory, useWorkspaceSearch } from './useSearch.js';

const { Text } = Typography;
const icons = { entry:<FileTextOutlined/>, category:<FolderOutlined/>, tag:<TagOutlined/>, document:<FileTextOutlined/>, user:<UserOutlined/>, conversation:<MessageOutlined/>, message:<MessageOutlined/> };

export default function CommandPalette() {
  const [open,setOpen]=useState(false),[query,setQuery]=useState(''),[active,setActive]=useState(0);
  const inputRef=useRef(null),navigate=useNavigate();
  const deferred=useDeferredValue(query.trim()),search=useWorkspaceSearch(deferred),history=useSearchHistory(open&&deferred.length<2),clearHistory=useClearSearchHistory();
  const items=useMemo(()=>search.data?.groups?.flatMap(group=>group.items.map(item=>({...item,group:group.label})))??[],[search.data]);

  useEffect(()=>{const onKey=e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();setOpen(v=>!v)}};window.addEventListener('keydown',onKey);return()=>window.removeEventListener('keydown',onKey)},[]);
  useEffect(()=>{if(open)setTimeout(()=>inputRef.current?.focus(),50);else{setQuery('');setActive(0)}},[open]);
  useEffect(()=>setActive(0),[deferred]);

  const choose=item=>{setOpen(false);navigate(item.target)};
  const onKeyDown=e=>{if(!items.length)return;if(e.key==='ArrowDown'){e.preventDefault();setActive(v=>(v+1)%items.length)}else if(e.key==='ArrowUp'){e.preventDefault();setActive(v=>(v-1+items.length)%items.length)}else if(e.key==='Enter'){e.preventDefault();choose(items[active])}};
  const histories=history.data??[];

  return <Modal open={open} onCancel={()=>setOpen(false)} footer={null} width={680} className="command-palette" destroyOnHidden title={null}>
    <Input ref={inputRef} size="large" prefix={<SearchOutlined/>} value={query} onChange={e=>setQuery(e.target.value)} onKeyDown={onKeyDown} placeholder="Search entries, documents, people and messages…" aria-label="Search workspace" suffix={<span className="search-shortcut">ESC</span>}/>
    <div className="command-results">
      {deferred.length<2&&<>{histories.length>0?<><div className="command-section-title"><Text type="secondary">RECENT SEARCHES</Text><Button type="link" size="small" loading={clearHistory.isPending} onClick={()=>clearHistory.mutate()}>Clear</Button></div>{histories.map(row=><button className="command-history-row" key={row.id} onClick={()=>setQuery(row.query)}><ClockCircleOutlined/><span>{row.query}</span></button>)}</>:<div className="command-hint"><SearchOutlined/><Text type="secondary">Type at least 2 characters to search your workspace.</Text></div>}</>}
      {deferred.length>=2&&search.isLoading&&<Skeleton active paragraph={{rows:5}} title={false}/>} 
      {deferred.length>=2&&search.isError&&<Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Search could not be completed."><Button onClick={()=>search.refetch()}>Try again</Button></Empty>}
      {deferred.length>=2&&!search.isLoading&&!search.isError&&items.length===0&&<Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={`No results for “${deferred}”`}/>} 
      {deferred.length>=2&&!search.isLoading&&!search.isError&&(search.data?.groups??[]).map(group=><section key={group.type} className="command-group"><Text className="command-group-label">{group.label}</Text>{group.items.map(item=>{const index=items.findIndex(x=>x.resource_type===item.resource_type&&x.id===item.id);return <button key={`${item.resource_type}:${item.id}`} className={`command-result-row ${index===active?'active':''}`} onMouseEnter={()=>setActive(index)} onClick={()=>choose(item)}><span className="command-result-icon">{icons[item.resource_type]??<SearchOutlined/>}</span><span className="command-result-copy"><Text strong>{item.title}</Text><Text type="secondary" ellipsis>{item.subtitle}</Text></span>{item.meta?.sensitive&&<span className="command-sensitive">Sensitive</span>}</button>})}</section>)}
    </div>
    <div className="command-footer"><Space size="large"><Text type="secondary">↑↓ Navigate</Text><Text type="secondary">↵ Open</Text><Text type="secondary">Esc Close</Text></Space><Text type="secondary">Marjo Tech Hub Search</Text></div>
  </Modal>;
}
