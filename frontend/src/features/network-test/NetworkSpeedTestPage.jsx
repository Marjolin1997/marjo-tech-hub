import { CloudDownloadOutlined, CloudUploadOutlined, DashboardOutlined, ReloadOutlined, WifiOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Progress, Row, Space, Statistic, Tag, Typography } from 'antd';
import { useEffect, useRef, useState } from 'react';

const { Title, Paragraph, Text } = Typography;
const DOWNLOAD_BYTES = 10_000_000;
const UPLOAD_BYTES = 4_000_000;

const mbps = (bytes, milliseconds) => Number(((bytes * 8) / (milliseconds / 1000) / 1_000_000).toFixed(2));
const quality = (download, upload, latency) => {
  if (download >= 100 && upload >= 20 && latency < 40) return { label: 'Excellent', color: 'success' };
  if (download >= 25 && upload >= 5 && latency < 100) return { label: 'Good', color: 'processing' };
  if (download >= 10 && upload >= 2) return { label: 'Fair', color: 'warning' };
  return { label: 'Limited', color: 'error' };
};

async function measureLatency(signal) {
  const samples = [];
  for (let i = 0; i < 4; i += 1) {
    const start = performance.now();
    const response = await fetch(`/network-test/ping?t=${Date.now()}-${i}`, { cache: 'no-store', signal });
    if (!response.ok) throw new Error('Latency endpoint unavailable.');
    await response.text();
    samples.push(performance.now() - start);
  }
  samples.sort((a, b) => a - b);
  return Math.round((samples[1] + samples[2]) / 2);
}

async function measureDownload(signal) {
  const start = performance.now();
  const response = await fetch(`/network-test/download?bytes=${DOWNLOAD_BYTES}&t=${Date.now()}`, { cache: 'no-store', signal });
  if (!response.ok) throw new Error('Download test endpoint unavailable.');
  const blob = await response.blob();
  return mbps(blob.size, performance.now() - start);
}

async function measureUpload(signal) {
  const payload = new Blob([new Uint8Array(UPLOAD_BYTES)]);
  const start = performance.now();
  const response = await fetch(`/network-test/upload?t=${Date.now()}`, { method: 'POST', body: payload, signal });
  if (!response.ok) throw new Error('Upload test endpoint unavailable.');
  return mbps(payload.size, performance.now() - start);
}

export default function NetworkSpeedTestPage() {
  const [status, setStatus] = useState('idle');
  const [stage, setStage] = useState('Ready');
  const [progress, setProgress] = useState(0);
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');
  const controllerRef = useRef(null);

  useEffect(() => () => controllerRef.current?.abort(), []);

  const runTest = async () => {
    controllerRef.current?.abort();
    const controller = new AbortController();
    controllerRef.current = controller;
    setStatus('running'); setError(''); setResult(null); setProgress(8);
    try {
      setStage('Measuring latency');
      const latency = await measureLatency(controller.signal);
      setProgress(32);
      setStage('Testing download');
      const download = await measureDownload(controller.signal);
      setProgress(68);
      setStage('Testing upload');
      const upload = await measureUpload(controller.signal);
      setProgress(100);
      setStage('Complete');
      setResult({ latency, download, upload });
      setStatus('complete');
    } catch (err) {
      if (err.name === 'AbortError') return;
      setStatus('error'); setStage('Test unavailable'); setError(err.message || 'Could not complete the network test.');
    }
  };

  const q = result ? quality(result.download, result.upload, result.latency) : null;
  return <main className="network-test-page">
    <section className="network-test-hero">
      <Space direction="vertical" size={8}>
        <Tag icon={<WifiOutlined/>}>NETWORK DIAGNOSTICS</Tag>
        <Title level={1}>Internet Speed Test</Title>
        <Paragraph>Measure this device’s connection to Marjo Tech Hub: latency, download throughput and upload throughput. Results can vary with Wi-Fi, VPNs, browser load and server distance.</Paragraph>
      </Space>
    </section>
    <Card className="network-test-console">
      <div className="network-test-action">
        <div className={`speed-orb ${status === 'running' ? 'is-running' : ''}`}><DashboardOutlined/></div>
        <Title level={3}>{stage}</Title>
        <Text type="secondary">{status === 'idle' ? 'No test has been run in this session.' : status === 'running' ? 'Keep this tab open while the test is running.' : 'Measurements are estimates from this browser to this server.'}</Text>
        <Progress percent={progress} status={status === 'error' ? 'exception' : status === 'complete' ? 'success' : 'active'} showInfo={status !== 'idle'} />
        <Button type="primary" size="large" icon={result ? <ReloadOutlined/> : <WifiOutlined/>} loading={status === 'running'} onClick={runTest}>{result ? 'Test again' : 'Start speed test'}</Button>
      </div>
      {error && <Alert type="error" showIcon message="Speed test could not finish" description={error}/>} 
      {result && <><Row gutter={[16,16]} className="network-results">
        <Col xs={24} md={8}><Card><Statistic title="Latency" value={result.latency} suffix="ms" prefix={<DashboardOutlined/>}/></Card></Col>
        <Col xs={24} md={8}><Card><Statistic title="Download" value={result.download} precision={2} suffix="Mbps" prefix={<CloudDownloadOutlined/>}/></Card></Col>
        <Col xs={24} md={8}><Card><Statistic title="Upload" value={result.upload} precision={2} suffix="Mbps" prefix={<CloudUploadOutlined/>}/></Card></Col>
      </Row><div className="network-quality"><Text strong>Connection assessment </Text><Tag color={q.color}>{q.label}</Tag></div></>}
    </Card>
    <Alert className="network-test-note" type="info" showIcon message="What this measures" description="This is not an Ookla clone or an ISP-certified line-speed result. It measures real HTTP performance between the visitor’s browser and the Marjo Tech Hub server, which makes it useful for quick client diagnostics."/>
  </main>;
}
