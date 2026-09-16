import { LockOutlined, MailOutlined, MessageOutlined, SafetyCertificateOutlined, WhatsAppOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Checkbox, Form, Input, Radio, Space, Typography } from 'antd';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useCurrentUser, useLogin } from './useAuth.js';

const { Title, Paragraph, Text } = Typography;

export default function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const currentUser = useCurrentUser();
  const login = useLogin();
  const destination = location.state?.from?.pathname ?? '/';

  if (currentUser.data) return <Navigate to="/" replace />;

  const onFinish = async values => {
    try {
      const data = await login.mutateAsync(values);
      if (data.requires_2fa) {
        navigate('/verify-2fa', { state: { challengeId: data.challenge_id, channel: data.channel, verificationDestination: data.destination, destination } });
      }
    } catch {}
  };

  const errorMessage = login.error?.response?.data?.errors?.email?.[0]
    ?? login.error?.response?.data?.message
    ?? (login.error ? 'We could not sign you in. Check your details and try again.' : null);

  return <main className="login-page">
    <section className="login-intro" aria-label="Marjo Tech Hub introduction">
      <div className="login-brand-mark">M</div><Text className="login-eyebrow">MARJO TECH HUB</Text>
      <Title>Engineering knowledge, ready when you need it.</Title>
      <Paragraph>Keep commands, snippets, notes and operational workflows organized in one focused workspace.</Paragraph>
      <Space className="security-note"><SafetyCertificateOutlined/><Text>Password plus one-time verification protects every sign-in.</Text></Space>
    </section>
    <section className="login-panel" aria-label="Sign in"><Card className="login-card" bordered={false}>
      <Space direction="vertical" size={4} className="login-heading"><Title level={2}>Welcome back</Title><Text type="secondary">Sign in to continue to your workspace.</Text></Space>
      {errorMessage && <Alert type="error" showIcon message={errorMessage} className="login-alert"/>}
      <Form layout="vertical" size="large" requiredMark={false} onFinish={onFinish} initialValues={{ channel: 'email', remember: true }}>
        <Form.Item label="Email" name="email" rules={[{required:true,message:'Enter your email address.'},{type:'email',message:'Enter a valid email address.'}]}><Input prefix={<MailOutlined/>} autoComplete="email" placeholder="you@example.com" autoFocus/></Form.Item>
        <Form.Item label="Password" name="password" rules={[{required:true,message:'Enter your password.'}]} style={{marginBottom:8}}><Input.Password prefix={<LockOutlined/>} autoComplete="current-password" placeholder="Your password"/></Form.Item>
        <div style={{textAlign:'right',marginBottom:16}}><Link to="/forgot-password">Forgot password?</Link></div>
        <Form.Item label="Send verification code via" name="channel"><Radio.Group><Space direction="vertical"><Radio value="email"><MailOutlined/> Email <Text type="secondary">(default)</Text></Radio><Radio value="sms"><MessageOutlined/> SMS</Radio><Radio value="whatsapp"><WhatsAppOutlined/> WhatsApp</Radio></Space></Radio.Group></Form.Item>
        <Alert type="info" showIcon message="SMS and WhatsApp require a verified phone number and provider configuration." style={{marginBottom:16}}/>
        <Form.Item name="remember" valuePropName="checked"><Checkbox>Keep me signed in</Checkbox></Form.Item>
        <Button type="primary" htmlType="submit" block loading={login.isPending}>Continue securely</Button>
      </Form>
      <Text type="secondary" className="login-help">Email remains the default verification channel.</Text>
    </Card></section>
  </main>;
}
