import { Alert, Form, Input, Modal, Select } from 'antd';
import { useCreateCategory } from './useLibrary.js';

function flatten(categories = [], depth = 0) {
  return categories.flatMap((category) => [
    { value: category.id, label: `${'— '.repeat(depth)}${category.name}` },
    ...flatten(category.children, depth + 1),
  ]);
}

export default function CategoryModal({ open, onClose, categories, onCreated }) {
  const [form] = Form.useForm();
  const createCategory = useCreateCategory();

  const submit = async () => {
    const values = await form.validateFields();
    const category = await createCategory.mutateAsync(values);
    form.resetFields();
    onCreated?.(category);
    onClose();
  };

  const close = () => {
    if (createCategory.isPending) return;
    form.resetFields();
    onClose();
  };

  return <Modal title="New category" open={open} onCancel={close} onOk={submit} okText="Create category" confirmLoading={createCategory.isPending} cancelButtonProps={{disabled:createCategory.isPending}} closable={!createCategory.isPending} maskClosable={!createCategory.isPending} destroyOnHidden>
    {createCategory.isError && <Alert type="error" showIcon message="Could not create category" />}
    <Form form={form} layout="vertical" requiredMark="optional">
      <Form.Item name="name" label="Name" rules={[{ required: true, message: 'Enter a category name.' }]}>
        <Input autoFocus placeholder="e.g. Laravel" maxLength={100} onPressEnter={submit} />
      </Form.Item>
      <Form.Item name="parent_id" label="Parent category" extra="Leave empty to create a top-level category.">
        <Select allowClear placeholder="Top level" options={flatten(categories)} />
      </Form.Item>
    </Form>
  </Modal>;
}
