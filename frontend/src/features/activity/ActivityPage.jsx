import { ArrowLeftOutlined, AuditOutlined, ReloadOutlined } from '@ant-design/icons';
import { Alert, Button, Card, DatePicker, Descriptions, Drawer, Empty, Pagination, Select, Skeleton, Space, Table, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useActivity, useActivityEvent, useActivityFilterOptions } from './useActivity.js';

const { Title, Text } = Typography;
const pretty = value => value ? value.replaceAll('.', ' · ').replaceAll('_', ' ') : '—';

export default function ActivityPage() {
  const navigate = useNavigate();
  const [page,setPage]=useState(1), [action,setAction]=useState(), [resourceType,setResourceType]=useState(), [actorId,setActorId]=useState(), [targetId,setTargetId]=useState(), [dates,setDates]=useState(null), [selectedId,setSelectedId]=useState(null);
  const params=useMemo(()=>({page,per_page:20,...(action?{action}:{}),...(resourceType?{resource_type:resourceType}:{}),...(actorId?{actor_id:actorId}:{}),...(targetId?{target_user_id:targetId}:{}),...(dates?.[0]?{date_from:dates[0].format('YYYY-MM-DD')}:{}) ,...(dates?.[1]?{date_to:dates[1].format('YYYY-MM-DD')}:{})}),[page,action,resourceType,actorId,targetId,dates]);
  const query=useActivity(params), detail=useActivityEvent(selectedId), optionsQuery=useActivityFilterOptions();
  const rows=query.data?.data??[], meta=query.data, options=optionsQuery.data??{};
  const users=(options.users??[]).map(user=>({value:user.id,label:user.name}));
  const actions=(options.actions??[]).map(value=>({value,label:pretty(value)}));
  const resources=(options.resource_types??[]).map(value=>({value,label:pretty(value)}));
  const columns=[
    {title:'When',dataIndex:'created_at',width:190,render:v=>new Date(v).toLocaleString()},
    {title:'Actor',dataIndex:'actor',render:v=>v?.name||'System'},
    {title:'Action',dataIndex:'action',render:v=><Tag>{pretty(v)}</Tag>},
    {title:'Resource',render:(_,r)=><Space direction="vertical" size={0}><Text>{r.resource_label||pretty(r.resource_type)}</Text><Text type="secondary">{r.resource_type}{r.resource_id?` #${r.resource_id}`:''}</Text></Space>},
    {title:'Target',dataIndex:'target_user',render:v=>v?.name||'—'},
  ];
  const reset=()=>{setAction(undefined);setResourceType(undefined);setActorId(undefined);setTargetId(undefined);setDates(null);setPage(1)};

  return <div className="feature-page activity-page">
    <Space className="feature-page-heading" align="start"><Button icon={<ArrowLeftOutlined/>} onClick={()=>navigate('/')}/><div><Text className="eyebrow">WORKSPACE GOVERNANCE</Text><Title level={1}><AuditOutlined/> Activity</Title><Text type="secondary">A privacy-safe history of important workspace changes. Private content is never shown here.</Text></div></Space>
    <Card className="activity-filters"><Space wrap>
      <Select showSearch optionFilterProp="label" allowClear value={actorId} onChange={v=>{setActorId(v);setPage(1)}} placeholder="Actor" options={users} loading={optionsQuery.isLoading} style={{minWidth:180}}/>
      <Select showSearch optionFilterProp="label" allowClear value={targetId} onChange={v=>{setTargetId(v);setPage(1)}} placeholder="Target user" options={users} loading={optionsQuery.isLoading} style={{minWidth:180}}/>
      <Select showSearch optionFilterProp="label" allowClear value={action} onChange={v=>{setAction(v);setPage(1)}} placeholder="Action" options={actions} loading={optionsQuery.isLoading} style={{minWidth:220}}/>
      <Select allowClear value={resourceType} onChange={v=>{setResourceType(v);setPage(1)}} placeholder="Resource" options={resources} loading={optionsQuery.isLoading} style={{minWidth:170}}/>
      <DatePicker.RangePicker value={dates} onChange={v=>{setDates(v);setPage(1)}}/><Button onClick={reset}>Clear</Button><Button icon={<ReloadOutlined/>} onClick={()=>query.refetch()} loading={query.isFetching}>Refresh</Button>
    </Space></Card>
    {query.isLoading?<Card><Skeleton active paragraph={{rows:8}}/></Card>:query.isError?<Card><Empty description="Activity could not be loaded."><Button type="primary" onClick={()=>query.refetch()}>Try again</Button></Empty></Card>:rows.length===0?<Card><Empty description="No activity matches these filters."/></Card>:<Card className="activity-table-card"><Table rowKey="id" columns={columns} dataSource={rows} pagination={false} scroll={{x:900}} onRow={record=>({onClick:()=>setSelectedId(record.id),style:{cursor:'pointer'}})}/>{meta?.last_page>1&&<div className="library-pagination"><Pagination current={meta.current_page} pageSize={meta.per_page} total={meta.total} showSizeChanger={false} onChange={setPage} showTotal={total=>`${total} events`}/></div>}</Card>}
    <Drawer title="Activity details" width={560} open={Boolean(selectedId)} onClose={()=>setSelectedId(null)}>{detail.isLoading?<Skeleton active/>:detail.isError?<Alert type="error" showIcon message="Activity details could not be loaded." action={<Button size="small" onClick={()=>detail.refetch()}>Retry</Button>}/>:detail.data&&<Descriptions column={1} bordered size="small"><Descriptions.Item label="Action">{pretty(detail.data.action)}</Descriptions.Item><Descriptions.Item label="Actor">{detail.data.actor?.name||'System'}</Descriptions.Item><Descriptions.Item label="Target">{detail.data.target_user?.name||'—'}</Descriptions.Item><Descriptions.Item label="Resource">{detail.data.resource_label||pretty(detail.data.resource_type)}{detail.data.resource_id?` #${detail.data.resource_id}`:''}</Descriptions.Item><Descriptions.Item label="Time">{new Date(detail.data.created_at).toLocaleString()}</Descriptions.Item><Descriptions.Item label="Safe metadata"><pre style={{whiteSpace:'pre-wrap',margin:0,overflowWrap:'anywhere'}}>{JSON.stringify(detail.data.metadata??{},null,2)}</pre></Descriptions.Item></Descriptions>}</Drawer>
  </div>;
}
