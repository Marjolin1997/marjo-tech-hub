import http from '../../lib/http.js';

export async function getCurrentUser() {
  const { data } = await http.get('/api/auth/me');
  return data.user;
}

export async function login(credentials) {
  await http.get('/sanctum/csrf-cookie');
  const { data } = await http.post('/api/auth/login', credentials);
  return data.user;
}

export async function logout() {
  await http.post('/api/auth/logout');
}
