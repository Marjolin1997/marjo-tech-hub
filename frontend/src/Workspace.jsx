import { AppstoreOutlined, CopyOutlined, DeleteOutlined, EditOutlined, EyeOutlined, FolderAddOutlined, FolderOutlined, LogoutOutlined, MenuFoldOutlined, MenuUnfoldOutlined, PlusOutlined, SearchOutlined, StarFilled, StarOutlined } from '@ant-design/icons';
import { Avatar, Button, Card, Drawer, Dropdown, Empty, Input, Layout, Menu, Popconfirm, Select, Skeleton, Space, Tag, Typography, message } from 'antd';
import { useDeferredValue, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCurrentUser, useLogout } from './features/auth/useAuth.js';
import CategoryModal from './features/library/CategoryModal.jsx';
import EntryModal from './features/library/EntryModal.jsx';
import { useCategories, useCreateTag, useDeleteEntry, useEntries, useTags, useToggleFavorite } from './features/library/useLibrary.js';

const { Header, Sider, Content } = Layout;
const { Title, Paragraph, Text } = Typography;
function categoryMenu(categories = []) { return categories.map((c) => ({ key:`category:${c.id}`, icon:<FolderOutlined/>, label:c.name, children:c.children?.length ? categoryMenu(c.children) : undefined })); }

export default function Workspace() {
  const [collapsed, setCollapsed] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [favoritesOnly, setFavoritesOnly] = useState(false);
  const [selectedTag, setSelectedTag] = useState(null);
  const [search, setSearch] = useState('');
  const [entryModal, setEntryModal] = useState({ open:false, entry:null });
  const [detailEntry, setDetailEntry] = useState(null);
  const [categoryOpen, setCategoryOpen] = useState(false);
  const deferredSearch = useDeferredValue(search.trim());
  const { data:user } = useCurrentUser();
  const { data:categories = [], isLoading:categoriesLoading } = useCategories();
  const { data:tags = [] } = useTags();
  const filters = useMemo(() => ({ ...(selectedCategory ? {category_id:selectedCategory}:{}), ...(favoritesOnly ? {favorite:true}:{}), ...(selectedTag ? {tag_id:selectedTag}:{}), ...(deferredSearch ? {q:deferredSearch}:{}) }), [selectedCategory, favoritesOnly, selectedTag, deferredSearch]);
  const { data:entryResponse, isLoading:entriesLoading, isError } = useEntries(filters);
  const entries = entryResponse?.data ?? [];
  const deleteEntry = useDeleteEntry();
  const toggleFavorite = useToggleFavorite();
  const createTag = useCreateTag();
  const logout = useLogout();
  const navigate = useNavigate();
  const [messageApi, contextHolder] = message.useMessage();
  const initials = user?.name?.split(/\s+/).slice(0,2).map((p)=>p[0]).join('').toUpperCase() || 'U';
  const menuItems = useMemo(() => [{key:'all',icon:<AppstoreOutlined/>,label:'All entries'},{key:'favorites',icon:<StarOutlined/>,label:'Favorites'},{type:'divider'},...categoryMenu(categories)], [categories]);
  const selectMenu = ({key}) => { setFavoritesOnly(key === 'favorites'); setSelectedCategory(key.startsWith('category:') ? Number(key.replace('category:','')) : null); };
  const signOut = async () => { await logout.mutateAsync(); navigate('/login',{replace:true}); };
  const copy = async (entry) => { await navigator.clipboard.writeText(entry.content); messageApi.success(`Copied “${entry.title}”`); };
  const remove = async (entry) => { await deleteEntry.mutateAsync(entry.id); if (detailEntry?.id === entry.id) setDetailEntry(null); messageApi.success('Entry deleted'); };
  const favorite = async (entry) => { await toggleFavorite.mutateAsync({id:entry.id,active:entry.is_favorite}); if (detailEntry?.id === entry.id) setDetailEntry({...entry,is_favorite:!entry.is_favorite}); };
  const addTag = async (value) => { const name = value?.trim(); if (!name) return; await createTag.mutateAsync(name); messageApi.success(`Tag “${name}” is ready`); };
  const accountMenu = {items:[{key:'identity',label:<div><Text strong>{user?.name}</Text><br/><Text type="secondary">{user?.email}</Text></div>,disabled:true},{type:'divider'},{key:'logout',icon:<LogoutOutlined/>,label:'Sign out',danger:true,onClick:signOut}]};
  const selectedKey = favoritesOnly ? 'favorites' : selectedCategory ? `category:${selectedCategory}` : 'all';

  return <Layout className="workspace-shell">{contextHolder}
    <Sider className="workspace-sider" width={270} collapsedWidth={76} collapsed={collapsed} trigger={null} breakpoint="lg" onBreakpoint={setCollapsed}>
      <div className="brand-block"><div className="brand-mark">M</div>{!collapsed&&<div><Text className="brand-name">Marjo Tech Hub</Text><Text className="brand-caption">Engineering workspace</Text></div>}</div>
      {!collapsed&&<div className="sidebar-actions"><Button icon={<FolderAddOutlined/>} block onClick={()=>setCategoryOpen(true)}>New category</Button></div>}
      {categoriesLoading?<div className="sidebar-loading"><Skeleton active paragraph={{rows:5}} title={false}/></div>:<Menu mode="inline" selectedKeys={[selectedKey]} items={menuItems} className="workspace-menu" onClick={selectMenu}/>} 
    </Sider>
    <Layout><Header className="workspace-header">
      <Button type="text" className="collapse-button" icon={collapsed?<MenuUnfoldOutlined/>:<MenuFoldOutlined/>} onClick={()=>setCollapsed(v=>!v)} aria-label={collapsed?'Expand navigation':'Collapse navigation'}/>
      <Input allowClear value={search} onChange={e=>setSearch(e.target.value)} className="global-search" prefix={<SearchOutlined/>} placeholder="Search your engineering library..." aria-label="Search workspace"/>
      <Dropdown menu={accountMenu} placement="bottomRight" trigger={['click']}><Button type="text" className="account-button"><Space><Avatar className="user-avatar">{initials}</Avatar><span className="account-name">{user?.name}</span></Space></Button></Dropdown>
    </Header>
    <Content className="workspace-content">
      <section className="library-heading"><div><Text className="eyebrow">KNOWLEDGE LIBRARY</Text><Title level={1} className="library-title">{favoritesOnly?'Favorites':deferredSearch?'Search results':selectedCategory?'Category entries':'All entries'}</Title><Paragraph className="hero-copy">Fast access to the engineering knowledge you actually reuse.</Paragraph></div><Button type="primary" size="large" icon={<PlusOutlined/>} onClick={()=>setEntryModal({open:true,entry:null})}>Add entry</Button></section>
      <div className="library-toolbar"><Select allowClear value={selectedTag} onChange={setSelectedTag} placeholder="Filter by tag" options={tags.map(t=>({value:t.id,label:`${t.name} (${t.entries_count ?? 0})`}))} style={{minWidth:210}}/><Input.Search placeholder="Create a tag and press Enter" enterButton="Add tag" onSearch={addTag} loading={createTag.isPending} style={{maxWidth:340}}/></div>
      {entriesLoading&&<div className="entry-grid">{[1,2,3].map(k=><Card key={k} className="entry-card"><Skeleton active/></Card>)}</div>}
      {isError&&<Card className="state-card"><Empty description="We couldn't load your library. Please try again."/></Card>}
      {!entriesLoading&&!isError&&entries.length===0&&<Card className="state-card"><Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={favoritesOnly?'No favorites yet.':deferredSearch?'No entries match your search.':'No entries here yet.'}><Button type="primary" icon={<PlusOutlined/>} onClick={()=>setEntryModal({open:true,entry:null})}>Create an entry</Button></Empty></Card>}
      {!entriesLoading&&entries.length>0&&<section className="entry-grid">{entries.map(entry=><Card key={entry.id} className="entry-card" hoverable actions={[
        <Button key="view" type="text" icon={<EyeOutlined/>} onClick={()=>setDetailEntry(entry)}>Open</Button>,
        <Button key="copy" type="text" icon={<CopyOutlined/>} onClick={()=>copy(entry)}>Copy</Button>,
        <Button key="edit" type="text" icon={<EditOutlined/>} onClick={()=>setEntryModal({open:true,entry})}>Edit</Button>,
        <Popconfirm key="delete" title="Delete this entry?" description="This action cannot be undone." okText="Delete" okButtonProps={{danger:true}} onConfirm={()=>remove(entry)}><Button type="text" danger icon={<DeleteOutlined/>}/></Popconfirm>
      ]}>
        <div className="entry-card-top"><Space size={6} wrap><Tag>{entry.type}</Tag>{entry.language&&<Tag>{entry.language}</Tag>}{entry.is_sensitive&&<Tag color="warning">Sensitive</Tag>}</Space><Button type="text" aria-label={entry.is_favorite?'Remove favorite':'Add favorite'} icon={entry.is_favorite?<StarFilled/>:<StarOutlined/>} onClick={()=>favorite(entry)}/></div>
        <Title level={4} className="entry-title" onClick={()=>setDetailEntry(entry)}>{entry.title}</Title>
        {entry.description&&<Paragraph type="secondary" ellipsis={{rows:2}}>{entry.description}</Paragraph>}
        <Space size={[4,4]} wrap>{entry.tags?.map(tag=><Tag key={tag.id} onClick={()=>setSelectedTag(tag.id)}>{tag.name}</Tag>)}</Space>
        <pre className="entry-preview"><code>{entry.content}</code></pre>
      </Card>)}</section>}
    </Content></Layout>
    <EntryModal open={entryModal.open} entry={entryModal.entry} categories={categories} defaultCategoryId={selectedCategory} onClose={()=>setEntryModal({open:false,entry:null})}/>
    <CategoryModal open={categoryOpen} categories={categories} onClose={()=>setCategoryOpen(false)}/>
    <Drawer title={detailEntry?.title} width={620} open={Boolean(detailEntry)} onClose={()=>setDetailEntry(null)} extra={detailEntry&&<Space><Button icon={detailEntry.is_favorite?<StarFilled/>:<StarOutlined/>} onClick={()=>favorite(detailEntry)}>{detailEntry.is_favorite?'Favorited':'Favorite'}</Button><Button type="primary" icon={<CopyOutlined/>} onClick={()=>copy(detailEntry)}>Copy</Button></Space>}>
      {detailEntry&&<div className="entry-detail"><Space wrap><Tag>{detailEntry.type}</Tag>{detailEntry.language&&<Tag>{detailEntry.language}</Tag>}{detailEntry.is_sensitive&&<Tag color="warning">Sensitive</Tag>}</Space>{detailEntry.description&&<Paragraph className="detail-description">{detailEntry.description}</Paragraph>}<Space wrap>{detailEntry.tags?.map(t=><Tag key={t.id}>{t.name}</Tag>)}</Space><pre className="detail-code"><code>{detailEntry.content}</code></pre><Text type="secondary">Updated {detailEntry.updated_at ? new Date(detailEntry.updated_at).toLocaleString() : 'recently'}</Text></div>}
    </Drawer>
  </Layout>;
}
