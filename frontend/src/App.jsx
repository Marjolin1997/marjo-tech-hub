import { Navigate, Route, Routes } from 'react-router-dom';
import ForgotPasswordPage from './features/auth/ForgotPasswordPage.jsx';
import LoginPage from './features/auth/LoginPage.jsx';
import ProtectedRoute from './features/auth/ProtectedRoute.jsx';
import ResetPasswordPage from './features/auth/ResetPasswordPage.jsx';
import DocumentsPage from './features/documents/DocumentsPage.jsx';
import ProfilePage from './features/profile/ProfilePage.jsx';
import Workspace from './Workspace.jsx';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route path="/" element={<ProtectedRoute><Workspace /></ProtectedRoute>} />
      <Route path="/documents" element={<ProtectedRoute><DocumentsPage /></ProtectedRoute>} />
      <Route path="/profile" element={<ProtectedRoute><ProfilePage /></ProtectedRoute>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
