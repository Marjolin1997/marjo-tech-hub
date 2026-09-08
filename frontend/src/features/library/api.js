import api from '../../lib/api.js';

export const libraryApi = {
  categories: async () => (await api.get('/api/categories')).data.data,
  createCategory: async (payload) => (await api.post('/api/categories', payload)).data.data,
  entries: async (params = {}) => (await api.get('/api/entries', { params })).data,
  entry: async (id) => (await api.get(`/api/entries/${id}`)).data.data,
  createEntry: async (payload) => (await api.post('/api/entries', payload)).data.data,
  updateEntry: async ({ id, ...payload }) => (await api.put(`/api/entries/${id}`, payload)).data.data,
  deleteEntry: async (id) => api.delete(`/api/entries/${id}`),
  tags: async () => (await api.get('/api/tags')).data.data,
  createTag: async (name) => (await api.post('/api/tags', { name })).data.data,
  favorite: async (id) => (await api.put(`/api/entries/${id}/favorite`)).data,
  unfavorite: async (id) => (await api.delete(`/api/entries/${id}/favorite`)).data,
};
