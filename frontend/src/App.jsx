import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import AccessControlPage from './features/access-control/AccessControlPage.jsx';
import ActivityPage from './features/activity/ActivityPage.jsx';
import AcceptInvitationPage from './features/auth/AcceptInvitationPage.jsx';
import ForgotPasswordPage from './features/auth/ForgotPasswordPage.jsx';
import LoginPage from './features/auth/LoginPage.jsx';
import PermissionRoute from './features/auth/PermissionRoute.jsx';
import ProtectedRoute from './features/auth/ProtectedRoute.jsx';
import ResetPasswordPage from './features/auth/ResetPasswordPage.jsx';
import TwoFactorPage from './features/auth/TwoFactorPage.jsx';
import DocumentsPage from './features/documents/DocumentsPage.jsx';
import GitHandbookPage from './features/git-handbook/GitHandbookPage.jsx';
import MessagingPage from './features/messaging/MessagingPage.jsx';
import ProfilePage from './features/profile/ProfilePage.jsx';
import Workspace from './Workspace.jsx';
const ToolboxPage=lazy(()=>import('./features/toolbox/ToolboxPage.jsx'));
const toolbox=<ProtectedRoute><Suspense fallback={<div style={{padding:32}}>Loading developer tools…</div>}><ToolboxPage/></Suspense></ProtectedRoute>;
export default function App(){return <Routes><Route path="/login" element={<LoginPage/>}/><Route path="/forgot-password" element={<ForgotPasswordPage/>}/><Route path="/reset-password" element={<ResetPasswordPage/>}/><Route path="/accept-invitation" element={<AcceptInvitationPage/>}/><Route path="/verify-2fa" element={<TwoFactorPage/>}/><Route path="/" element={<ProtectedRoute><Workspace/></ProtectedRoute>}/><Route path="/documents" element={<ProtectedRoute><PermissionRoute permission="documents.view"><DocumentsPage/></PermissionRoute></ProtectedRoute>}/><Route path="/messages" element={<ProtectedRoute><PermissionRoute permission="messages.view"><MessagingPage/></PermissionRoute></ProtectedRoute>}/><Route path="/activity" element={<ProtectedRoute><PermissionRoute permission="activity.view"><ActivityPage/></PermissionRoute></ProtectedRoute>}/><Route path="/git-handbook" element={<ProtectedRoute><GitHandbookPage/></ProtectedRoute>}/><Route path="/toolbox" element={toolbox}/><Route path="/toolbox/:toolId" element={toolbox}/><Route path="/profile" element={<ProtectedRoute><ProfilePage/></ProtectedRoute>}/><Route path="/access-control" element={<ProtectedRoute><PermissionRoute permission="users.view"><AccessControlPage/></PermissionRoute></ProtectedRoute>}/><Route path="*" element={<Navigate to="/" replace/>}/></Routes>}
