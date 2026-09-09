import http from '../../lib/http.js';

export const listDocuments = (params = {}) => http.get('/api/documents', { params }).then(r => r.data);
export const uploadDocument = (payload) => http.post('/api/documents', payload).then(r => r.data);
export const updateDocument = (id, payload) => http.put(`/api/documents/${id}`, payload).then(r => r.data);
export const deleteDocument = (id) => http.delete(`/api/documents/${id}`);
export const downloadDocument = (id) => http.get(`/api/documents/${id}/download`, { responseType: 'blob' });
export const previewDocument = (id) => http.get(`/api/documents/${id}/preview`, { responseType: 'blob' });
