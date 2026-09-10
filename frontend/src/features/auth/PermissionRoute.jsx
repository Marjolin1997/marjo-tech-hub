import { Result, Button } from 'antd';
import { useNavigate } from 'react-router-dom';
import { can } from './permissions.js';
import { useCurrentUser } from './useAuth.js';

export default function PermissionRoute({ permission, children }) {
  const { data: user } = useCurrentUser();
  const navigate = useNavigate();
  if (can(user, permission)) return children;
  return <Result status="403" title="403" subTitle="You do not have permission to access this area." extra={<Button type="primary" onClick={() => navigate('/')}>Back to workspace</Button>} />;
}
