import http from '../../lib/http.js';

export const searchWorkspace = async (q) => (await http.get('/api/search', { params: { q } })).data;
export const getSearchHistory = async () => (await http.get('/api/search/history')).data.data;
export const clearSearchHistory = async () => (await http.delete('/api/search/history')).data;
