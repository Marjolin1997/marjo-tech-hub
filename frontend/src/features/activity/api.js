import http from '../../lib/http.js';

export async function fetchActivity(params = {}) {
  const { data } = await http.get('/activity', { params });
  return data;
}

export async function fetchActivityEvent(id) {
  const { data } = await http.get(`/activity/${id}`);
  return data;
}

export async function fetchActivityFilterOptions() {
  const { data } = await http.get('/activity/filter-options');
  return data.data;
}
