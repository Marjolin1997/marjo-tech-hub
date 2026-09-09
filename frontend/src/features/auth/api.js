import http from '../../lib/http.js';

export async function getCurrentUser() { const { data } = await http.get('/api/auth/me'); return data.user; }
export async function login(credentials) { await http.get('/sanctum/csrf-cookie'); const { data } = await http.post('/api/auth/login', credentials); return data.user; }
export async function logout() { await http.post('/api/auth/logout'); }
export async function forgotPassword(email) { await http.get('/sanctum/csrf-cookie'); const { data } = await http.post('/api/auth/forgot-password', { email }); return data; }
export async function resetPassword(payload) { await http.get('/sanctum/csrf-cookie'); const { data } = await http.post('/api/auth/reset-password', payload); return data; }
export async function changePassword(payload) { const { data } = await http.put('/api/auth/password', payload); return data; }
