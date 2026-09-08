import { Alert, Form, Input, Modal, Select, Switch } from 'antd';
import { useEffect } from 'react';
import { useCreateEntry, useUpdateEntry } from './useLibrary.js';

const { TextArea } = Input;

function flattenCategories(categories = [], depth = 0) {
  return categories.flatMap((category) => [
    { value: category.id, label: `${'— '.repeat(depth)}${category.name}` },
    ...flattenCategories(category.children, depth + 1),
  ]);
}

export default function EntryModal({ open, onClose, categories, entry = null, defaultCategoryId = null }) {
  const [form] = Form.useForm();
  const createEntry = useCreateEntry();
  const updateEntry = useUpdateEntry();
  const mutation = entry ? updateEntry : createEntry;

  useEffect(() => {
    if (!open) return;
    form.setFieldsValue(entry ? {
      category_id: entry.category_id,
      title: entry.title,
      type: entry.type,
      language: entry.language,
      description: entry.description,
      content: entry.content,
      is_sensitive: entry.is_sensitive,
    } : { category_id: defaultCategoryId, type: 'command', is_sensitive: false });
  }, [open, entry, defaultCategoryId, form]);

  const submit = async () => {
    const values = await form.validateFields();
    await mutation.mutateAsync(entry ? { id: entry.id, ...values } : values);
    form.resetFields();
    onClose();
  };

  return <Modal
    title={entry ? 'Edit entry' : 'Add knowledge entry'}
    open={open}
    onCancel={onClose}
    onOk={submit}
    okText={entry ? 'Save changes' : 'Create entry'}
    confirmLoading={mutation.isPending}
    width={760}
    destroyOnHidden
  >
    {mutation.isError && <Alert type="error" showIcon message="Could not save this entry" description="Review the fields and try again." />}
    <Form form={form} layout="vertical" requiredMark="optional" className="entry-form">
      <Form.Item name="title" label="Title" rules={[{ required: true, message: 'Give this entry a clear title.' }]}>
        <Input autoFocus placeholder="e.g. Follow Laravel logs" maxLength={160} showCount />
      </Form.Item>
      <div className="form-grid">
        <Form.Item name="type" label="Type" rules={[{ required: true }]}>
          <Select options={[{value:'command',label:'Command'},{value:'snippet',label:'Code snippet'},{value:'note',label:'Note'}]} />
        </Form.Item>
        <Form.Item name="category_id" label="Category">
          <Select allowClear placeholder="Uncategorized" options={flattenCategories(categories)} />
        </Form.Item>
      </div>
      <Form.Item name="language" label="Language / shell">
        <Input placeholder="bash, php, javascript, sql..." maxLength={50} />
      </Form.Item>
      <Form.Item name="description" label="Description">
        <TextArea rows={2} placeholder="When and why would you use this?" maxLength={2000} showCount />
      </Form.Item>
      <Form.Item name="content" label="Content" rules={[{ required: true, message: 'Add the command, snippet or note content.' }]}>
        <TextArea className="code-editor" rows={10} placeholder="Paste or write reusable knowledge here..." />
      </Form.Item>
      <Form.Item name="is_sensitive" label="Sensitive content" valuePropName="checked" extra="Marks entries that may need extra care before sharing or exporting.">
        <Switch />
      </Form.Item>
    </Form>
  </Modal>;
}
