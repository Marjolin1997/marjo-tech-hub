import { AppstoreOutlined, BranchesOutlined, CloudOutlined, CodeOutlined, ContainerOutlined, DatabaseOutlined, FileCodeOutlined, FileTextOutlined, LogoutOutlined, MenuFoldOutlined, MenuUnfoldOutlined, RocketOutlined, SearchOutlined, StarOutlined, ThunderboltOutlined, ToolOutlined } from '@ant-design/icons';
import { Avatar, Button, Card, Dropdown, Input, Layout, Menu, Space, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { workspaceNavigation } from './config/navigation.js';
import { useCurrentUser, useLogout } from './features/auth/useAuth.js';

const { Header, Sider, Content } = Layout;
const { Title, Paragraph, Text } = Typography;
const icons = { star:<StarOutlined/>, terminal:<ToolOutlined/>, branches:<BranchesOutlined/>, container:<ContainerOutlined/>, code:<CodeOutlined/>, search:<SearchOutlined/>, database:<DatabaseOutlined/>, cloud:<CloudOutlined/>, thunderbolt:<ThunderboltOutlined/>, rocket:<RocketOutlined/>, 'file-code':<FileCodeOutlined/>, 'file-text':<FileTextOutlined/> };
const quickStarts = [
  { title:'Server Commands', description:'Reusable operational commands and troubleshooting workflows.', icon:<ToolOutlined/> },
  { title:'Code Snippets', description:'Small, focused pieces of code worth keeping close.', icon:<FileCodeOutlined/> },
  { title:'Technical Notes', description:'Engineering decisions, explanations, and references.', icon:<FileTextOutlined/> },
];

export default function Workspace() {
  const [collapsed, setCollapsed] = useState(false);
  const { data: user } = useCurrentUser();
  const logout = useLogout();
  const navigate = useNavigate();
  const menuItems = useMemo(() => workspaceNavigation.map((item) => ({ ...item, icon: icons[item.icon] })), []);
  const initials = user?.name?.split(/\s+/).slice(0,2).map((part) => part[0]).join('').toUpperCase() || 'U';

  const signOut = async () => {
    await logout.mutateAsync();
    navigate('/login', { replace: true });
  };

  const accountMenu = { items: [
    { key:'identity', label:<div><Text strong>{user?.name}</Text><br/><Text type="secondary">{user?.email}</Text></div>, disabled:true },
    { type:'divider' },
    { key:'logout', icon:<LogoutOutlined/>, label:'Sign out', danger:true, onClick:signOut },
  ]};

  return <Layout className="workspace-shell">
    <Sider className="workspace-sider" width={252} collapsedWidth={76} collapsed={collapsed} trigger={null} breakpoint="lg" onBreakpoint={setCollapsed}>
      <div className="brand-block"><div className="brand-mark">M</div>{!collapsed && <div><Text className="brand-name">Marjo Tech Hub</Text><Text className="brand-caption">Engineering workspace</Text></div>}</div>
      <Menu mode="inline" defaultSelectedKeys={['favorites']} items={menuItems} className="workspace-menu" />
    </Sider>
    <Layout>
      <Header className="workspace-header">
        <Button type="text" className="collapse-button" icon={collapsed?<MenuUnfoldOutlined/>:<MenuFoldOutlined/>} onClick={() => setCollapsed(v=>!v)} aria-label={collapsed?'Expand navigation':'Collapse navigation'} />
        <Input className="global-search" prefix={<SearchOutlined/>} placeholder="Search commands, snippets and notes..." aria-label="Search workspace" disabled />
        <Dropdown menu={accountMenu} placement="bottomRight" trigger={['click']}>
          <Button type="text" className="account-button" aria-label="Open account menu"><Space><Avatar className="user-avatar">{initials}</Avatar><span className="account-name">{user?.name}</span></Space></Button>
        </Dropdown>
      </Header>
      <Content className="workspace-content">
        <section className="hero-section"><div><Text className="eyebrow">DEVELOPER KNOWLEDGE & OPERATIONS</Text><Title className="hero-title">Welcome back, {user?.name?.split(' ')[0]}.</Title><Paragraph className="hero-copy">Your commands, snippets, notes, and workflows will stay organized in one focused engineering workspace.</Paragraph></div><Button type="primary" size="large" disabled>Add entry — coming soon</Button></section>
        <section className="quick-grid" aria-label="Workspace areas">{quickStarts.map(item=><Card key={item.title} className="quick-card" hoverable><div className="quick-icon">{item.icon}</div><Title level={4}>{item.title}</Title><Paragraph type="secondary">{item.description}</Paragraph></Card>)}</section>
        <Card className="empty-workspace-card"><div className="empty-icon"><AppstoreOutlined/></div><Title level={3}>Your workspace is ready to grow</Title><Paragraph type="secondary">Next we will add dynamic categories and entries so this becomes your real engineering library.</Paragraph></Card>
      </Content>
    </Layout>
  </Layout>;
}
