import http from '../../lib/http.js';

export const getProfile = () => http.get('/api/profile').then(r => r.data.user);
export const updateProfile = (payload) => http.put('/api/profile', payload).then(r => r.data.user);
export const uploadAvatar = (file) => {
  const data = new FormData();
  data.append('avatar', file);
  return http.post('/api/profile/avatar', data).then(r => r.data.user);
};
export const deleteAvatar = () => http.delete('/api/profile/avatar').then(r => r.data.user);
export const fetchAvatar = () => http.get('/api/profile/avatar', { responseType: 'blob' }).then(r => r.data);
