import { Navigate, Route, Routes } from 'react-router-dom';
import AccessControlPage from './features/access-control/AccessControlPage.jsx';
import AcceptInvitationPage from './features/auth/AcceptInvitationPage.jsx';
import ForgotPasswordPage from './features/auth/ForgotPasswordPage.jsx';
import LoginPage from './features/auth/LoginPage.jsx';
import PermissionRoute from './features/auth/PermissionRoute.jsx';
import ProtectedRoute from './features/auth/ProtectedRoute.jsx';
import ResetPasswordPage from './features/auth/ResetPasswordPage.jsx';
import TwoFactorPage from './features/auth/TwoFactorPage.jsx';
import DocumentsPage from './features/documents/DocumentsPage.jsx';
import ProfilePage from './features/profile/ProfilePage.jsx';
import Workspace from './Workspace.jsx';
export default function App(){return <Routes><Route path="/login" element={<LoginPage/>}/><Route path="/forgot-password" element={<ForgotPasswordPage/>}/><Route path="/reset-password" element={<ResetPasswordPage/>}/><Route path="/accept-invitation" element={<AcceptInvitationPage/>}/><Route path="/verify-2fa" element={<TwoFactorPage/>}/><Route path="/" element={<ProtectedRoute><Workspace/></ProtectedRoute>}/><Route path="/documents" element={<ProtectedRoute><PermissionRoute permission="documents.view"><DocumentsPage/></PermissionRoute></ProtectedRoute>}/><Route path="/profile" element={<ProtectedRoute><ProfilePage/></ProtectedRoute>}/><Route path="/access-control" element={<ProtectedRoute><PermissionRoute permission="users.view"><AccessControlPage/></PermissionRoute></ProtectedRoute>}/><Route path="*" element={<Navigate to="/" replace/>}/></Routes>}
