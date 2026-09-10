import { ArrowLeftOutlined, AuditOutlined, ReloadOutlined } from '@ant-design/icons';
import { Button, Card, DatePicker, Descriptions, Drawer, Empty, Pagination, Select, Skeleton, Space, Table, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useActivity, useActivityEvent } from './useActivity.js';

const { Title, Text } = Typography;
const actionOptions = [
  { value: 'access.roles_updated', label: 'Access roles updated' },
  { value: 'entry.created', label: 'Entry created' },
  { value: 'entry.updated', label: 'Entry updated' },
  { value: 'entry.deleted', label: 'Entry deleted' },
];
const resourceOptions = ['user','entry','category','tag','document','profile','invitation'].map(value => ({ value, label: value[0].toUpperCase()+value.slice(1) }));

export default function ActivityPage() {
  const navigate = useNavigate();
  const [page,setPage]=useState(1), [action,setAction]=useState(), [resourceType,setResourceType]=useState(), [dates,setDates]=useState(null), [selectedId,setSelectedId]=useState(null);
  const params=useMemo(()=>({page,per_page:20,...(action?{action}:{}),...(resourceType?{resource_type:resourceType}:{}),...(dates?.[0]?{date_from:dates[0].format('YYYY-MM-DD')}:{}) ,...(dates?.[1]?{date_to:dates[1].format('YYYY-MM-DD')}:{})}),[page,action,resourceType,dates]);
  const query=useActivity(params), detail=useActivityEvent(selectedId);
  const rows=query.data?.data??[], meta=query.data;
  const columns=[
    {title:'When',dataIndex:'created_at',width:190,render:v=>new Date(v).toLocaleString()},
    {title:'Actor',dataIndex:'actor',render:v=>v?.name||'System'},
    {title:'Action',dataIndex:'action',render:v=><Tag>{v.replaceAll('.',' · ')}</Tag>},
    {title:'Resource',render:(_,r)=><Space direction="vertical" size={0}><Text>{r.resource_label||r.resource_type}</Text><Text type="secondary">{r.resource_type}{r.resource_id?` #${r.resource_id}`:''}</Text></Space>},
    {title:'Target',dataIndex:'target_user',render:v=>v?.name||'—'},
  ];
  const reset=()=>{setAction(undefined);setResourceType(undefined);setDates(null);setPage(1)};

  return <div className="feature-page activity-page">
    <Space className="feature-page-heading" align="start"><Button icon={<ArrowLeftOutlined/>} onClick={()=>navigate('/')}/><div><Text className="eyebrow">WORKSPACE GOVERNANCE</Text><Title level={1}><AuditOutlined/> Activity</Title><Text type="secondary">A privacy-safe history of important workspace changes.</Text></div></Space>
    <Card className="activity-filters"><Space wrap><Select allowClear value={action} onChange={v=>{setAction(v);setPage(1)}} placeholder="Action" options={actionOptions} style={{minWidth:220}}/><Select allowClear value={resourceType} onChange={v=>{setResourceType(v);setPage(1)}} placeholder="Resource" options={resourceOptions} style={{minWidth:170}}/><DatePicker.RangePicker value={dates} onChange={v=>{setDates(v);setPage(1)}}/><Button onClick={reset}>Clear</Button><Button icon={<ReloadOutlined/>} onClick={()=>query.refetch()} loading={query.isFetching}>Refresh</Button></Space></Card>
    {query.isLoading?<Card><Skeleton active paragraph={{rows:8}}/></Card>:query.isError?<Card><Empty description="Activity could not be loaded."><Button type="primary" onClick={()=>query.refetch()}>Try again</Button></Empty></Card>:rows.length===0?<Card><Empty description="No activity matches these filters."/></Card>:<Card className="activity-table-card"><Table rowKey="id" columns={columns} dataSource={rows} pagination={false} scroll={{x:850}} onRow={record=>({onClick:()=>setSelectedId(record.id),style:{cursor:'pointer'}})}/>{meta?.last_page>1&&<div className="library-pagination"><Pagination current={meta.current_page} pageSize={meta.per_page} total={meta.total} showSizeChanger={false} onChange={setPage}/></div>}</Card>}
    <Drawer title="Activity details" width={560} open={Boolean(selectedId)} onClose={()=>setSelectedId(null)}>{detail.isLoading?<Skeleton active/>:detail.data&&<Descriptions column={1} bordered size="small"><Descriptions.Item label="Action">{detail.data.action}</Descriptions.Item><Descriptions.Item label="Actor">{detail.data.actor?.name||'System'}</Descriptions.Item><Descriptions.Item label="Target">{detail.data.target_user?.name||'—'}</Descriptions.Item><Descriptions.Item label="Resource">{detail.data.resource_label||detail.data.resource_type}</Descriptions.Item><Descriptions.Item label="Time">{new Date(detail.data.created_at).toLocaleString()}</Descriptions.Item><Descriptions.Item label="Safe metadata"><pre style={{whiteSpace:'pre-wrap',margin:0}}>{JSON.stringify(detail.data.metadata??{},null,2)}</pre></Descriptions.Item></Descriptions>}</Drawer>
  </div>;
}
