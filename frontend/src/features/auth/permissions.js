export const can = (user, permission) => Boolean(user?.permissions?.includes(permission));
export const hasRole = (user, role) => Boolean(user?.roles?.includes(role));
