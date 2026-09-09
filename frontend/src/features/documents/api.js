import http from '../../lib/http.js';

export const listDocuments = (params = {}) => http.get('/documents', { params }).then(r => r.data);
export const uploadDocument = (payload) => http.post('/documents', payload).then(r => r.data);
export const updateDocument = (id, payload) => http.put(`/documents/${id}`, payload).then(r => r.data);
export const deleteDocument = (id) => http.delete(`/documents/${id}`);
export const downloadDocument = (id) => http.get(`/documents/${id}/download`, { responseType: 'blob' });
export const previewDocument = (id) => http.get(`/documents/${id}/preview`, { responseType: 'blob' });
