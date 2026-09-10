import api from '../../lib/api.js';

export async function fetchActivity(params = {}) {
  const { data } = await api.get('/activity', { params });
  return data;
}

export async function fetchActivityEvent(id) {
  const { data } = await api.get(`/activity/${id}`);
  return data;
}
