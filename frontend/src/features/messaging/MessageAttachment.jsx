import { FileOutlined, DownloadOutlined } from '@ant-design/icons';
import { Button, Image, Spin, Typography } from 'antd';
import { useEffect, useState } from 'react';
import { downloadAttachment, getAttachmentBlob } from './api.js';

const { Text }=Typography;
const bytes=value=>{if(!value)return '';const units=['B','KB','MB'];let size=value,i=0;while(size>=1024&&i<units.length-1){size/=1024;i++;}return `${size.toFixed(i?1:0)} ${units[i]}`;};

export default function MessageAttachment({attachment}){
  const [mediaUrl,setMediaUrl]=useState(null),[loading,setLoading]=useState(Boolean(attachment.media_url));
  useEffect(()=>{let live=true,url=null;if(!attachment.media_url){setLoading(false);return;}setLoading(true);getAttachmentBlob(attachment.media_url).then(blob=>{if(!live)return;url=URL.createObjectURL(blob);setMediaUrl(url);}).catch(()=>{}).finally(()=>live&&setLoading(false));return()=>{live=false;if(url)URL.revokeObjectURL(url);};},[attachment.id,attachment.media_url]);
  if(attachment.kind==='image')return <div className="message-attachment image-attachment">{loading?<Spin size="small"/>:mediaUrl?<Image src={mediaUrl} alt={attachment.name} preview/>:<Text type="secondary">Image unavailable</Text>}</div>;
  if(attachment.kind==='voice')return <div className="message-attachment voice-attachment">{loading?<Spin size="small"/>:mediaUrl?<audio controls preload="metadata" src={mediaUrl}/>:<Text type="secondary">Voice message unavailable</Text>}</div>;
  return <div className="message-attachment file-attachment"><FileOutlined/><div className="file-meta"><Text strong ellipsis>{attachment.name}</Text><Text type="secondary">{bytes(attachment.size_bytes)}</Text></div><Button type="text" icon={<DownloadOutlined/>} aria-label={`Download ${attachment.name}`} onClick={()=>downloadAttachment(attachment)}/></div>;
}
