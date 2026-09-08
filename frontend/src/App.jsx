import {
  AppstoreOutlined,
  BranchesOutlined,
  CloudOutlined,
  CodeOutlined,
  ContainerOutlined,
  DatabaseOutlined,
  FileCodeOutlined,
  FileTextOutlined,
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  RocketOutlined,
  SearchOutlined,
  StarOutlined,
  ThunderboltOutlined,
  ToolOutlined,
} from '@ant-design/icons';
import { Avatar, Button, Card, Input, Layout, Menu, Space, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { workspaceNavigation } from './config/navigation.js';

const { Header, Sider, Content } = Layout;
const { Title, Paragraph, Text } = Typography;

const icons = {
  star: <StarOutlined />,
  terminal: <ToolOutlined />,
  branches: <BranchesOutlined />,
  container: <ContainerOutlined />,
  code: <CodeOutlined />,
  search: <SearchOutlined />,
  database: <DatabaseOutlined />,
  cloud: <CloudOutlined />,
  thunderbolt: <ThunderboltOutlined />,
  rocket: <RocketOutlined />,
  'file-code': <FileCodeOutlined />,
  'file-text': <FileTextOutlined />,
};

const quickStarts = [
  { title: 'Server Commands', description: 'Reusable operational commands and troubleshooting workflows.', icon: <ToolOutlined /> },
  { title: 'Code Snippets', description: 'Small, focused pieces of code worth keeping close.', icon: <FileCodeOutlined /> },
  { title: 'Technical Notes', description: 'Engineering decisions, explanations, and references.', icon: <FileTextOutlined /> },
];

export default function App() {
  const [collapsed, setCollapsed] = useState(false);
  const menuItems = useMemo(
    () => workspaceNavigation.map((item) => ({ ...item, icon: icons[item.icon] })),
    [],
  );

  return (
    <Layout className="workspace-shell">
      <Sider
        className="workspace-sider"
        width={252}
        collapsedWidth={76}
        collapsed={collapsed}
        trigger={null}
        breakpoint="lg"
        onBreakpoint={setCollapsed}
      >
        <div className="brand-block">
          <div className="brand-mark">M</div>
          {!collapsed && (
            <div>
              <Text className="brand-name">Marjo Tech Hub</Text>
              <Text className="brand-caption">Engineering workspace</Text>
            </div>
          )}
        </div>
        <Menu mode="inline" defaultSelectedKeys={['favorites']} items={menuItems} className="workspace-menu" />
      </Sider>

      <Layout>
        <Header className="workspace-header">
          <Button
            type="text"
            className="collapse-button"
            icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
            onClick={() => setCollapsed((value) => !value)}
            aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
          />
          <Input
            className="global-search"
            prefix={<SearchOutlined />}
            placeholder="Search commands, snippets and notes..."
            aria-label="Search workspace"
            disabled
          />
          <Space size="middle">
            <Tag className="foundation-tag">Foundation</Tag>
            <Avatar className="user-avatar">MJ</Avatar>
          </Space>
        </Header>

        <Content className="workspace-content">
          <section className="hero-section">
            <div>
              <Text className="eyebrow">DEVELOPER KNOWLEDGE & OPERATIONS</Text>
              <Title className="hero-title">Everything useful, one search away.</Title>
              <Paragraph className="hero-copy">
                Keep the commands, snippets, notes, and workflows you reuse most in one clean engineering workspace.
              </Paragraph>
            </div>
            <Button type="primary" size="large" disabled>
              Add entry — coming soon
            </Button>
          </section>

          <section className="quick-grid" aria-label="Workspace areas">
            {quickStarts.map((item) => (
              <Card key={item.title} className="quick-card" hoverable>
                <div className="quick-icon">{item.icon}</div>
                <Title level={4}>{item.title}</Title>
                <Paragraph type="secondary">{item.description}</Paragraph>
              </Card>
            ))}
          </section>

          <Card className="empty-workspace-card">
            <div className="empty-icon"><AppstoreOutlined /></div>
            <Title level={3}>Your workspace is ready to grow</Title>
            <Paragraph type="secondary">
              Authentication comes next. Categories and real entries will then replace this foundation preview with your personal engineering library.
            </Paragraph>
          </Card>
        </Content>
      </Layout>
    </Layout>
  );
}
