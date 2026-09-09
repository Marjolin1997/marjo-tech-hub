import { ArrowLeftOutlined } from '@ant-design/icons';
import { Button, Layout, Space, Typography } from 'antd';
import { useNavigate } from 'react-router-dom';
import { useCategories, useTags } from '../library/useLibrary.js';
import DocumentsPanel from './DocumentsPanel.jsx';

export default function DocumentsPage(){
 const navigate=useNavigate(),{data:categories=[]}=useCategories(),{data:tags=[]}=useTags();
 return <Layout className="workspace-shell"><Layout><Layout.Header className="workspace-header"><Space><Button type="text" icon={<ArrowLeftOutlined/>} onClick={()=>navigate('/')}>Knowledge library</Button><Typography.Text strong>Marjo Tech Hub</Typography.Text></Space></Layout.Header><Layout.Content className="workspace-content"><DocumentsPanel categories={categories} tags={tags}/></Layout.Content></Layout></Layout>;
}
