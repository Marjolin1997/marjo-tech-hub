import { ArrowLeftOutlined, MailOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Form, Input, Space, Typography } from 'antd';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { forgotPassword } from './api.js';
const { Title, Text }=Typography;
export default function ForgotPasswordPage(){
 const [loading,setLoading]=useState(false),[sent,setSent]=useState(false),[error,setError]=useState(null);
 const submit=async({email})=>{setLoading(true);setError(null);try{await forgotPassword(email);setSent(true);}catch(e){setError(e?.response?.data?.message||'Could not process the request. Please retry.')}finally{setLoading(false)}};
 return <main className="login-page"><section className="login-intro"><div className="login-brand-mark">M</div><Text className="login-eyebrow">MARJO TECH HUB</Text><Title>Recover access securely.</Title><Text>Request a time-limited password reset link for your account.</Text></section><section className="login-panel"><Card className="login-card" bordered={false}><Space direction="vertical" size={4} className="login-heading"><Title level={2}>Forgot password?</Title><Text type="secondary">Enter the email associated with your account.</Text></Space>{sent?<Alert type="success" showIcon message="Check your email" description="If an account exists for that address, a password reset link has been sent."/>:<Form layout="vertical" size="large" onFinish={submit} requiredMark={false}>{error&&<Alert type="error" showIcon message={error} style={{marginBottom:20}}/>}<Form.Item name="email" label="Email" rules={[{required:true},{type:'email'}]}><Input prefix={<MailOutlined/>} autoComplete="email" autoFocus/></Form.Item><Button type="primary" htmlType="submit" block loading={loading}>Send reset link</Button></Form>}<Link to="/login"><Button type="link" icon={<ArrowLeftOutlined/>} style={{paddingLeft:0,marginTop:16}}>Back to sign in</Button></Link></Card></section></main>;
}
