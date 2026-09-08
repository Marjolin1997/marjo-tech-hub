import { AppstoreOutlined, CopyOutlined, DeleteOutlined, EditOutlined, FolderAddOutlined, FolderOutlined, LogoutOutlined, MenuFoldOutlined, MenuUnfoldOutlined, PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Avatar, Button, Card, Dropdown, Empty, Input, Layout, Menu, Popconfirm, Skeleton, Space, Tag, Typography, message } from 'antd';
import { useDeferredValue, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCurrentUser, useLogout } from './features/auth/useAuth.js';
import CategoryModal from './features/library/CategoryModal.jsx';
import EntryModal from './features/library/EntryModal.jsx';
import { useCategories, useDeleteEntry, useEntries } from './features/library/useLibrary.js';

const { Header, Sider, Content } = Layout;
const { Title, Paragraph, Text } = Typography;

function categoryMenu(categories = []) {
  return categories.map((category) => ({
    key: `category:${category.id}`,
    icon: <FolderOutlined />,
    label: category.name,
    children: category.children?.length ? categoryMenu(category.children) : undefined,
  }));
}

export default function Workspace() {
  const [collapsed, setCollapsed] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [search, setSearch] = useState('');
  const [entryModal, setEntryModal] = useState({ open: false, entry: null });
  const [categoryOpen, setCategoryOpen] = useState(false);
  const deferredSearch = useDeferredValue(search.trim());
  const { data: user } = useCurrentUser();
  const { data: categories = [], isLoading: categoriesLoading } = useCategories();
  const filters = useMemo(() => ({ ...(selectedCategory ? { category_id: selectedCategory } : {}), ...(deferredSearch ? { q: deferredSearch } : {}) }), [selectedCategory, deferredSearch]);
  const { data: entryResponse, isLoading: entriesLoading, isError } = useEntries(filters);
  const entries = entryResponse?.data ?? [];
  const deleteEntry = useDeleteEntry();
  const logout = useLogout();
  const navigate = useNavigate();
  const [messageApi, contextHolder] = message.useMessage();
  const initials = user?.name?.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'U';

  const menuItems = useMemo(() => [
    { key: 'all', icon: <AppstoreOutlined />, label: 'All entries' },
    { type: 'divider' },
    ...categoryMenu(categories),
  ], [categories]);

  const selectMenu = ({ key }) => setSelectedCategory(key === 'all' ? null : Number(key.replace('category:', '')));
  const signOut = async () => { await logout.mutateAsync(); navigate('/login', { replace: true }); };
  const copy = async (entry) => {
    await navigator.clipboard.writeText(entry.content);
    messageApi.success(`Copied “${entry.title}”`);
  };
  const remove = async (entry) => {
    await deleteEntry.mutateAsync(entry.id);
    messageApi.success('Entry deleted');
  };

  const accountMenu = { items: [
    { key: 'identity', label: <div><Text strong>{user?.name}</Text><br/><Text type="secondary">{user?.email}</Text></div>, disabled: true },
    { type: 'divider' },
    { key: 'logout', icon: <LogoutOutlined />, label: 'Sign out', danger: true, onClick: signOut },
  ] };

  return <Layout className="workspace-shell">
    {contextHolder}
    <Sider className="workspace-sider" width={270} collapsedWidth={76} collapsed={collapsed} trigger={null} breakpoint="lg" onBreakpoint={setCollapsed}>
      <div className="brand-block"><div className="brand-mark">M</div>{!collapsed && <div><Text className="brand-name">Marjo Tech Hub</Text><Text className="brand-caption">Engineering workspace</Text></div>}</div>
      {!collapsed && <div className="sidebar-actions"><Button icon={<FolderAddOutlined/>} block onClick={() => setCategoryOpen(true)}>New category</Button></div>}
      {categoriesLoading ? <div className="sidebar-loading"><Skeleton active paragraph={{ rows: 5 }} title={false}/></div> : <Menu mode="inline" selectedKeys={[selectedCategory ? `category:${selectedCategory}` : 'all']} items={menuItems} className="workspace-menu" onClick={selectMenu}/>} 
    </Sider>
    <Layout>
      <Header className="workspace-header">
        <Button type="text" className="collapse-button" icon={collapsed ? <MenuUnfoldOutlined/> : <MenuFoldOutlined/>} onClick={() => setCollapsed((v) => !v)} aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'} />
        <Input allowClear value={search} onChange={(event) => setSearch(event.target.value)} className="global-search" prefix={<SearchOutlined/>} placeholder="Search your engineering library..." aria-label="Search workspace" />
        <Dropdown menu={accountMenu} placement="bottomRight" trigger={['click']}><Button type="text" className="account-button" aria-label="Open account menu"><Space><Avatar className="user-avatar">{initials}</Avatar><span className="account-name">{user?.name}</span></Space></Button></Dropdown>
      </Header>
      <Content className="workspace-content">
        <section className="library-heading">
          <div><Text className="eyebrow">KNOWLEDGE LIBRARY</Text><Title level={1} className="library-title">{deferredSearch ? 'Search results' : selectedCategory ? 'Category entries' : 'All entries'}</Title><Paragraph className="hero-copy">Keep operational commands, reusable code and technical notes easy to find and safe to reuse.</Paragraph></div>
          <Button type="primary" size="large" icon={<PlusOutlined/>} onClick={() => setEntryModal({ open: true, entry: null })}>Add entry</Button>
        </section>

        {entriesLoading && <div className="entry-grid">{[1,2,3].map((key) => <Card key={key} className="entry-card"><Skeleton active/></Card>)}</div>}
        {isError && <Card className="state-card"><Empty description="We couldn't load your library. Please try again." /></Card>}
        {!entriesLoading && !isError && entries.length === 0 && <Card className="state-card"><Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={deferredSearch ? 'No entries match your search.' : 'No entries here yet.'}><Button type="primary" icon={<PlusOutlined/>} onClick={() => setEntryModal({ open: true, entry: null })}>Create your first entry</Button></Empty></Card>}
        {!entriesLoading && entries.length > 0 && <section className="entry-grid" aria-label="Knowledge entries">{entries.map((entry) => <Card key={entry.id} className="entry-card" hoverable actions={[
          <Button key="copy" type="text" icon={<CopyOutlined/>} onClick={() => copy(entry)}>Copy</Button>,
          <Button key="edit" type="text" icon={<EditOutlined/>} onClick={() => setEntryModal({ open: true, entry })}>Edit</Button>,
          <Popconfirm key="delete" title="Delete this entry?" description="This action cannot be undone." okText="Delete" okButtonProps={{ danger: true }} onConfirm={() => remove(entry)}><Button type="text" danger icon={<DeleteOutlined/>}>Delete</Button></Popconfirm>,
        ]}>
          <Space size={6} wrap><Tag>{entry.type}</Tag>{entry.language && <Tag>{entry.language}</Tag>}{entry.is_sensitive && <Tag color="warning">Sensitive</Tag>}</Space>
          <Title level={4} className="entry-title">{entry.title}</Title>
          {entry.description && <Paragraph type="secondary" ellipsis={{ rows: 2 }}>{entry.description}</Paragraph>}
          <pre className="entry-preview"><code>{entry.content}</code></pre>
        </Card>)}</section>}
      </Content>
    </Layout>

    <EntryModal open={entryModal.open} entry={entryModal.entry} categories={categories} defaultCategoryId={selectedCategory} onClose={() => setEntryModal({ open: false, entry: null })}/>
    <CategoryModal open={categoryOpen} categories={categories} onClose={() => setCategoryOpen(false)}/>
  </Layout>;
}
