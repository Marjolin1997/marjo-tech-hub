import http from '../../lib/http.js';
export async function getUsers(){const{data}=await http.get('/api/access-control/users');return data;}
export async function getRoles(){const{data}=await http.get('/api/access-control/roles');return data.data;}
export async function getPermissions(){const{data}=await http.get('/api/access-control/permissions');return data.data;}
export async function updateUserRoles({userId,roles}){const{data}=await http.put(`/api/access-control/users/${userId}/roles`,{roles});return data.data;}
