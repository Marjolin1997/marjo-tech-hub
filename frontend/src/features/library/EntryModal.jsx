import { PlusOutlined, TagsOutlined } from '@ant-design/icons';
import { Alert, Button, Form, Input, Modal, Select, Space, Switch, message } from 'antd';
import { useEffect, useState } from 'react';
import CategoryModal from './CategoryModal.jsx';
import { useCreateEntry, useCreateTag, useTags, useUpdateEntry } from './useLibrary.js';

const { TextArea } = Input;
function flattenCategories(categories = [], depth = 0) { return categories.flatMap((c) => [{ value:c.id, label:`${'— '.repeat(depth)}${c.name}` }, ...flattenCategories(c.children, depth + 1)]); }

export default function EntryModal({ open, onClose, categories, entry = null, defaultCategoryId = null }) {
  const [form] = Form.useForm();
  const [categoryOpen, setCategoryOpen] = useState(false);
  const [tagOpen, setTagOpen] = useState(false);
  const [tagName, setTagName] = useState('');
  const [messageApi, contextHolder] = message.useMessage();
  const { data: tags = [] } = useTags();
  const createTag = useCreateTag();
  const createEntry = useCreateEntry();
  const updateEntry = useUpdateEntry();
  const mutation = entry ? updateEntry : createEntry;
  const entryType = Form.useWatch('type', form) ?? entry?.type ?? 'command';

  useEffect(() => {
    if (!open) return;
    form.resetFields();
    form.setFieldsValue(entry ? {
      category_id: entry.category_id, title: entry.title, type: entry.type, language: entry.language,
      description: entry.description, content: entry.content, is_sensitive: entry.is_sensitive,
      tag_ids: entry.tags?.map((tag) => tag.id) ?? [],
    } : { category_id: defaultCategoryId, type: 'command', is_sensitive: false, tag_ids: [] });
  }, [open, entry, defaultCategoryId, form]);

  const submit = async () => {
    const values = await form.validateFields();
    await mutation.mutateAsync(entry ? { id: entry.id, ...values } : values);
    form.resetFields();
    onClose();
  };

  const close = () => {
    if (mutation.isPending) return;
    form.resetFields();
    setTagName('');
    setTagOpen(false);
    setCategoryOpen(false);
    onClose();
  };

  const createInlineTag = async () => {
    const name = tagName.trim();
    if (!name || createTag.isPending) return;
    try {
      const tag = await createTag.mutateAsync(name);
      const current = form.getFieldValue('tag_ids') ?? [];
      form.setFieldValue('tag_ids', [...new Set([...current, tag.id])]);
      setTagName('');
      setTagOpen(false);
      messageApi.success(`Tag “${tag.name}” created and selected`);
    } catch {
      messageApi.error('Could not create the tag. Please retry.');
    }
  };

  const onCategoryCreated = (category) => {
    form.setFieldValue('category_id', category.id);
    messageApi.success(`Category “${category.name}” created and selected`);
  };

  const actionLabel = entry ? 'Save changes' : entryType === 'command' ? 'Create command' : entryType === 'snippet' ? 'Create snippet' : 'Create note';

  return <>{contextHolder}<Modal title={entry ? 'Edit entry' : 'Add knowledge entry'} open={open} onCancel={close} onOk={submit} okText={actionLabel} confirmLoading={mutation.isPending} cancelButtonProps={{disabled:mutation.isPending}} closable={!mutation.isPending} maskClosable={!mutation.isPending} width={800} destroyOnHidden>
    {mutation.isError && <Alert type="error" showIcon message="Could not save this entry" description="Review the fields and try again." />}
    <Form form={form} layout="vertical" requiredMark="optional" className="entry-form">
      <Form.Item name="title" label="Title" rules={[{ required:true, message:'Give this entry a clear title.' }]}><Input autoFocus placeholder="e.g. Follow Laravel logs" maxLength={160} showCount /></Form.Item>
      <div className="form-grid">
        <Form.Item name="type" label="Type" rules={[{ required:true }]}><Select options={[{value:'command',label:'Command'},{value:'snippet',label:'Code snippet'},{value:'note',label:'Note'}]} /></Form.Item>
        <Form.Item label="Category">
          <Space.Compact block>
            <Form.Item name="category_id" noStyle><Select allowClear placeholder="Uncategorized" options={flattenCategories(categories)} /></Form.Item>
            <Button icon={<PlusOutlined/>} onClick={()=>setCategoryOpen(true)}>Add category</Button>
          </Space.Compact>
        </Form.Item>
      </div>
      <div className="form-grid">
        <Form.Item name="language" label="Language / shell"><Input placeholder="bash, php, javascript, sql..." maxLength={50} /></Form.Item>
        <Form.Item label="Tags">
          <Space.Compact block>
            <Form.Item name="tag_ids" noStyle><Select mode="multiple" allowClear maxTagCount="responsive" placeholder="Choose tags" options={tags.map((tag) => ({ value:tag.id, label:tag.name }))} /></Form.Item>
            <Button icon={<TagsOutlined/>} onClick={()=>setTagOpen(true)}>Add tag</Button>
          </Space.Compact>
        </Form.Item>
      </div>
      <Form.Item name="description" label="Description"><TextArea rows={2} placeholder="When and why would you use this?" maxLength={2000} showCount /></Form.Item>
      <Form.Item name="content" label="Content" rules={[{ required:true, message:'Add the command, snippet or note content.' }]}><TextArea className="code-editor" rows={10} placeholder={entryType === 'command' ? 'Paste or write the command here...' : 'Paste or write reusable knowledge here...'} /></Form.Item>
      <Form.Item name="is_sensitive" label="Sensitive content" valuePropName="checked" extra="Marks entries that may need extra care before sharing or exporting."><Switch /></Form.Item>
    </Form>
  </Modal>
  <CategoryModal open={categoryOpen} categories={categories} onCreated={onCategoryCreated} onClose={()=>setCategoryOpen(false)} />
  <Modal title="Create new tag" open={tagOpen} onCancel={()=>{if(!createTag.isPending){setTagOpen(false);setTagName('')}}} onOk={createInlineTag} okText="Create tag" confirmLoading={createTag.isPending} cancelButtonProps={{disabled:createTag.isPending}} closable={!createTag.isPending} maskClosable={!createTag.isPending} destroyOnHidden>
    <Form layout="vertical" requiredMark={false}>
      <Form.Item label="Tag name" required extra="The new tag will be selected automatically for this entry.">
        <Input autoFocus value={tagName} onChange={e=>setTagName(e.target.value)} onPressEnter={createInlineTag} placeholder="e.g. production" maxLength={60} disabled={createTag.isPending} />
      </Form.Item>
    </Form>
  </Modal></>;
}
