import { LockOutlined, MailOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Checkbox, Form, Input, Space, Typography } from 'antd';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useCurrentUser, useLogin } from './useAuth.js';

const { Title, Paragraph, Text } = Typography;

export default function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const currentUser = useCurrentUser();
  const login = useLogin();
  const destination = location.state?.from?.pathname ?? '/';

  if (currentUser.data) return <Navigate to="/" replace />;

  const onFinish = async (values) => {
    try {
      await login.mutateAsync(values);
      navigate(destination, { replace: true });
    } catch {
      // The inline alert keeps authentication failures in context.
    }
  };

  const errorMessage = login.error?.response?.data?.errors?.email?.[0]
    ?? (login.error ? 'We could not sign you in. Check your details and try again.' : null);

  return (
    <main className="login-page">
      <section className="login-intro" aria-label="Marjo Tech Hub introduction">
        <div className="login-brand-mark">M</div>
        <Text className="login-eyebrow">MARJO TECH HUB</Text>
        <Title>Engineering knowledge, ready when you need it.</Title>
        <Paragraph>
          Keep commands, snippets, notes and operational workflows organized in one focused workspace.
        </Paragraph>
        <Space className="security-note">
          <SafetyCertificateOutlined />
          <Text>Secure cookie-based session. Credentials never live in browser storage.</Text>
        </Space>
      </section>

      <section className="login-panel" aria-label="Sign in">
        <Card className="login-card" bordered={false}>
          <Space direction="vertical" size={4} className="login-heading">
            <Title level={2}>Welcome back</Title>
            <Text type="secondary">Sign in to continue to your workspace.</Text>
          </Space>

          {errorMessage && <Alert type="error" showIcon message={errorMessage} className="login-alert" />}

          <Form layout="vertical" size="large" requiredMark={false} onFinish={onFinish}>
            <Form.Item label="Email" name="email" rules={[{ required: true, message: 'Enter your email address.' }, { type: 'email', message: 'Enter a valid email address.' }]}>
              <Input prefix={<MailOutlined />} autoComplete="email" placeholder="you@example.com" autoFocus />
            </Form.Item>
            <Form.Item label="Password" name="password" rules={[{ required: true, message: 'Enter your password.' }]}>
              <Input.Password prefix={<LockOutlined />} autoComplete="current-password" placeholder="Your password" />
            </Form.Item>
            <Form.Item name="remember" valuePropName="checked" initialValue>
              <Checkbox>Keep me signed in</Checkbox>
            </Form.Item>
            <Button type="primary" htmlType="submit" block loading={login.isPending}>
              Sign in
            </Button>
          </Form>
          <Text type="secondary" className="login-help">Private workspace · Access is limited to authorized users.</Text>
        </Card>
      </section>
    </main>
  );
}
