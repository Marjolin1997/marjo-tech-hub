import { Spin } from 'antd';
import { Navigate, useLocation } from 'react-router-dom';
import { useCurrentUser } from './useAuth.js';

export default function ProtectedRoute({ children }) {
  const location = useLocation();
  const { data: user, isLoading, isError } = useCurrentUser();

  if (isLoading) {
    return <div className="route-loader"><Spin size="large" tip="Opening your workspace..." /></div>;
  }

  if (isError || !user) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  return children;
}
