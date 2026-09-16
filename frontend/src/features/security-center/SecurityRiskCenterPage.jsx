import { ArrowLeftOutlined, CheckCircleOutlined, GlobalOutlined, InfoCircleOutlined, SafetyCertificateOutlined, WarningOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Input, Row, Space, Spin, Statistic, Tag, Typography } from 'antd';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import http from '../../lib/http.js';
import './security-center.css';

const { Title, Paragraph, Text } = Typography;
const statusMeta = {
  passed: { color: 'success', icon: <CheckCircleOutlined />, label: 'Passed' },
  warning: { color: 'warning', icon: <WarningOutlined />, label: 'Warning' },
  failed: { color: 'error', icon: <WarningOutlined />, label: 'Failed' },
  info: { color: 'default', icon: <InfoCircleOutlined />, label: 'Info' },
};

export default function SecurityRiskCenterPage() {
  const navigate = useNavigate();
  const [target, setTarget] = useState('');
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const inspect = async () => {
    const value = target.trim();
    if (!value || loading) return;
    setLoading(true); setError(''); setResult(null);
    try {
      const { data } = await http.post('/api/security-center/inspect', { target: value });
      setResult(data);
    } catch (e) {
      setError(e.response?.data?.message || (e.response?.status === 429 ? 'Too many checks were started. Please wait a moment and retry.' : 'The site could not be inspected.'));
    } finally { setLoading(false); }
  };

  const counts = result?.summary || {};
  return <main className="security-center-page">
    <div className="security-center-nav"><Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/')}>Back to platform</Button><Tag icon={<SafetyCertificateOutlined />} color="processing">DEFENSIVE CHECK</Tag></div>
    <section className="security-center-hero"><Text className="eyebrow">SECURITY & RISK CENTER</Text><Title level={1}>Find configuration risks before they become incidents.</Title><Paragraph>Inspect an authorized public website for TLS health and browser security headers. Results are transient and this focused check does not prove a site is secure.</Paragraph></section>
    <Card className="security-scan-card"><Space.Compact block size="large"><Input value={target} onChange={e => setTarget(e.target.value)} onPressEnter={inspect} prefix={<GlobalOutlined />} placeholder="example.com or https://example.com" aria-label="Public website to inspect" disabled={loading}/><Button type="primary" icon={<SafetyCertificateOutlined />} onClick={inspect} loading={loading}>Run security check</Button></Space.Compact><Text type="secondary" className="security-scan-hint">Only public HTTP/HTTPS targets are accepted. Local, private and reserved network addresses are blocked server-side.</Text></Card>
    {error && <Alert className="security-center-alert" type="error" showIcon message="Inspection could not finish" description={error}/>} 
    {loading && <Card className="security-loading"><Spin size="large"/><Title level={4}>Inspecting TLS and response protections…</Title><Text type="secondary">The request is bounded and redirects are not followed.</Text></Card>}
    {result && <>
      <Card className="security-target-card"><div><Text type="secondary">TARGET</Text><strong>{result.target.host}</strong></div><div><Text type="secondary">RESOLVED IP</Text><strong>{result.target.resolved_ip}</strong></div><div><Text type="secondary">HTTP</Text><strong>{result.http.status}</strong></div><div><Text type="secondary">TLS EXPIRES</Text><strong>{result.tls?.valid_until ? new Date(result.tls.valid_until).toLocaleDateString() : 'Unavailable'}</strong></div></Card>
      <Row gutter={[16,16]} className="security-summary"><Col xs={12} md={6}><Card><Statistic title="Passed" value={counts.passed || 0} valueStyle={{color:'#15803d'}}/></Card></Col><Col xs={12} md={6}><Card><Statistic title="Warnings" value={counts.warning || 0}/></Card></Col><Col xs={12} md={6}><Card><Statistic title="Failed" value={counts.failed || 0}/></Card></Col><Col xs={12} md={6}><Card><Statistic title="Informational" value={counts.info || 0}/></Card></Col></Row>
      <section className="security-findings"><div className="security-section-heading"><div><Title level={2}>Findings</Title><Paragraph type="secondary">Evidence first, followed by why it matters and a practical remediation.</Paragraph></div><Text type="secondary">Checked {new Date(result.scanned_at).toLocaleString()}</Text></div><div className="security-findings-grid">{result.findings.map(f => { const meta=statusMeta[f.status] || statusMeta.info; return <Card key={f.key} className={`security-finding security-finding-${f.status}`}><div className="security-finding-head"><Title level={4}>{f.title}</Title><Tag color={meta.color} icon={meta.icon}>{meta.label}</Tag></div><div className="security-evidence"><Text type="secondary">EVIDENCE</Text><Text>{f.evidence}</Text></div><Paragraph><Text strong>Why it matters: </Text>{f.why}</Paragraph><Paragraph><Text strong>Recommended fix: </Text>{f.recommendation}</Paragraph></Card>;})}</div></section>
      <Alert className="security-center-notice" type="info" showIcon message="Scope of this check" description={result.notice}/>
    </>}
  </main>;
}
