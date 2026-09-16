import { Alert, Form, Input, Modal, Select, message } from 'antd';
import { useEffect } from 'react';
import { useCreateService, useUpdateService } from './useServices.js';

const TYPES=['service','website','api','worker','library','database','infrastructure'];
const LIFECYCLES=['development','production','maintenance','deprecated'];
const options=items=>items.map(value=>({value,label:value.charAt(0).toUpperCase()+value.slice(1)}));
export default function ServiceModal({open,service,onClose}){
 const [form]=Form.useForm(),create=useCreateService(),update=useUpdateService(),[api,holder]=message.useMessage(); const pending=create.isPending||update.isPending;
 useEffect(()=>{if(!open)return;form.setFieldsValue(service?{...service,technologies:service.technologies||[]}:{type:'service',lifecycle:'development',technologies:[]});},[open,service,form]);
 const submit=async values=>{try{const payload={...values,technologies:(values.technologies||[]).map(v=>v.trim()).filter(Boolean)};if(service){await update.mutateAsync({id:service.id,...payload});api.success('Service updated');}else{await create.mutateAsync(payload);api.success('Service added to your catalog');}form.resetFields();onClose();}catch(e){const errors=e.response?.data?.errors; if(errors)Object.entries(errors).forEach(([name,msg])=>form.setFields([{name,errors:[msg[0]]}]));api.error(e.response?.data?.message||'Could not save the service.');}};
 return <>{holder}<Modal width={720} title={service?'Edit service':'Add service'} open={open} onCancel={onClose} onOk={()=>form.submit()} okText={service?'Save changes':'Add service'} confirmLoading={pending} destroyOnHidden>
  <Alert type="info" showIcon message="Private catalog" description="Store service metadata and links only. Do not enter passwords, API keys, access tokens or other secrets." style={{marginBottom:18}}/>
  <Form form={form} layout="vertical" onFinish={submit} disabled={pending} requiredMark="optional">
   <div className="service-form-grid"><Form.Item name="name" label="Service name" rules={[{required:true,message:'Enter a service name.'},{max:120}]}><Input placeholder="Payments API"/></Form.Item><Form.Item name="owner_label" label="Owner / team" rules={[{max:120}]}><Input placeholder="Platform Team"/></Form.Item></div>
   <div className="service-form-grid"><Form.Item name="type" label="Type" rules={[{required:true}]}><Select options={options(TYPES)}/></Form.Item><Form.Item name="lifecycle" label="Lifecycle" rules={[{required:true}]}><Select options={options(LIFECYCLES)}/></Form.Item></div>
   <Form.Item name="description" label="Description" rules={[{max:2000}]}><Input.TextArea rows={3} placeholder="What this service owns and why it exists." showCount maxLength={2000}/></Form.Item>
   <Form.Item name="technologies" label="Technology stack"><Select mode="tags" tokenSeparators={[',']} maxTagCount="responsive" placeholder="Laravel, React, Redis…"/></Form.Item>
   <Form.Item name="repository_url" label="Repository URL" rules={[{type:'url',message:'Enter a valid HTTP/HTTPS URL.'}]}><Input placeholder="https://github.com/org/repository"/></Form.Item>
   <Form.Item name="production_url" label="Production URL" rules={[{type:'url',message:'Enter a valid HTTP/HTTPS URL.'}]}><Input placeholder="https://service.example.com"/></Form.Item>
   <Form.Item name="api_base_url" label="API base URL" rules={[{type:'url',message:'Enter a valid HTTP/HTTPS URL.'}]}><Input placeholder="https://service.example.com/api"/></Form.Item>
  </Form>
 </Modal></>;
}
