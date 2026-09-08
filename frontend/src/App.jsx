import { Navigate, Route, Routes } from 'react-router-dom';
import LoginPage from './features/auth/LoginPage.jsx';
import ProtectedRoute from './features/auth/ProtectedRoute.jsx';
import Workspace from './Workspace.jsx';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/" element={<ProtectedRoute><Workspace /></ProtectedRoute>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
