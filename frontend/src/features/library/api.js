import http from '../../lib/http.js';

export const libraryApi = {
  categories: async () => (await http.get('/api/categories')).data.data,
  createCategory: async (payload) => (await http.post('/api/categories', payload)).data.data,
  entries: async (params = {}) => (await http.get('/api/entries', { params })).data,
  entry: async (id) => (await http.get(`/api/entries/${id}`)).data.data,
  createEntry: async (payload) => (await http.post('/api/entries', payload)).data.data,
  updateEntry: async ({ id, ...payload }) => (await http.put(`/api/entries/${id}`, payload)).data.data,
  deleteEntry: async (id) => http.delete(`/api/entries/${id}`),
  tags: async () => (await http.get('/api/tags')).data.data,
  createTag: async (name) => (await http.post('/api/tags', { name })).data.data,
  favorite: async (id) => (await http.put(`/api/entries/${id}/favorite`)).data,
  unfavorite: async (id) => (await http.delete(`/api/entries/${id}/favorite`)).data,
};
