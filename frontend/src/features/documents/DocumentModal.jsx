import { Alert, Form, Input, Modal, Select, Switch, Upload, message } from 'antd';
import { InboxOutlined } from '@ant-design/icons';
import { useEffect } from 'react';
import { useUpdateDocument, useUploadDocument } from './useDocuments.js';

const { Dragger } = Upload;
export default function DocumentModal({ open, document, categories=[], tags=[], onClose }) {
  const [form]=Form.useForm(), upload=useUploadDocument(), update=useUpdateDocument(), [messageApi,holder]=message.useMessage();
  const pending=upload.isPending||update.isPending;
  useEffect(()=>{if(open) form.setFieldsValue(document?{title:document.title,description:document.description,category_id:document.category_id,tag_ids:document.tags?.map(t=>t.id),is_sensitive:document.is_sensitive}:{is_sensitive:false,tag_ids:[]});},[open,document,form]);
  const submit=async(values)=>{try{if(document){await update.mutateAsync({id:document.id,payload:values});messageApi.success('Document updated');}else{const file=values.file?.[0]?.originFileObj;if(!file)return;const data=new FormData();data.append('file',file);data.append('title',values.title);if(values.description)data.append('description',values.description);if(values.category_id)data.append('category_id',String(values.category_id));(values.tag_ids||[]).forEach(id=>data.append('tag_ids[]',String(id)));data.append('is_sensitive',values.is_sensitive?'1':'0');await upload.mutateAsync(data);messageApi.success('Document uploaded');}form.resetFields();onClose();}catch(e){messageApi.error(e?.response?.data?.message||'Could not save the document.');}};
  return <>{holder}<Modal title={document?'Edit document':'Upload document'} open={open} onCancel={onClose} onOk={()=>form.submit()} okText={document?'Save changes':'Upload'} confirmLoading={pending} destroyOnHidden>
    <Alert type="info" showIcon message="Private storage" description="PDF, DOCX, TXT, MD, PNG and JPEG up to 20 MB. Files are served only through authenticated API endpoints." style={{marginBottom:16}}/>
    <Form form={form} layout="vertical" onFinish={submit} disabled={pending}>
      {!document&&<Form.Item name="file" label="File" valuePropName="fileList" getValueFromEvent={e=>Array.isArray(e)?e:e?.fileList} rules={[{required:true,message:'Choose a document.'}]}><Dragger beforeUpload={()=>false} maxCount={1} accept=".pdf,.docx,.txt,.md,.png,.jpg,.jpeg"><p className="ant-upload-drag-icon"><InboxOutlined/></p><p>Drop a file here or click to browse</p></Dragger></Form.Item>}
      <Form.Item name="title" label="Title" rules={[{required:true},{max:160}]}><Input/></Form.Item>
      <Form.Item name="description" label="Description"><Input.TextArea rows={3}/></Form.Item>
      <Form.Item name="category_id" label="Category"><Select allowClear options={categories.map(c=>({value:c.id,label:c.name}))}/></Form.Item>
      <Form.Item name="tag_ids" label="Tags"><Select mode="multiple" allowClear options={tags.map(t=>({value:t.id,label:t.name}))}/></Form.Item>
      <Form.Item name="is_sensitive" label="Sensitive" valuePropName="checked"><Switch/></Form.Item>
    </Form>
  </Modal></>;
}
