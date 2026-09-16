import http from '../../lib/http.js';

export const serviceApi = {
  list: async (params = {}) => (await http.get('/api/services', { params })).data,
  get: async (id) => (await http.get(`/api/services/${id}`)).data.data,
  create: async (payload) => (await http.post('/api/services', payload)).data.data,
  update: async ({ id, ...payload }) => (await http.put(`/api/services/${id}`, payload)).data.data,
  remove: async (id) => http.delete(`/api/services/${id}`),
};
