import { Button, Card, Layout, Space, Tag, Typography } from 'antd';

const { Header, Content } = Layout;
const { Title, Paragraph, Text } = Typography;

export default function App() {
  return (
    <Layout className="app-shell">
      <Header className="app-header">
        <Text className="brand">Marjo Tech Hub</Text>
        <Tag>Foundation</Tag>
      </Header>

      <Content className="app-content">
        <Card className="welcome-card">
          <Space direction="vertical" size="middle">
            <Text type="secondary">Developer Knowledge & Operations</Text>
            <Title level={1}>Your engineering workspace, organized.</Title>
            <Paragraph>
              Commands, snippets, technical notes, and reusable workflows will live here.
              Authentication and the real dashboard are the next product features.
            </Paragraph>
            <Button type="primary" disabled>
              Sign in — coming next
            </Button>
          </Space>
        </Card>
      </Content>
    </Layout>
  );
}
