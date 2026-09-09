# ADR 0003: Role-Based Access Control

## Status
Accepted for implementation after the profile/authentication baseline is merged.

## Context
Marjo Tech Hub is evolving from a single-user engineering workspace into a multi-user workspace. Authentication already establishes who the user is; the next layer must decide what each authenticated user is allowed to see and change.

A single `role` column on `users` would be simple initially but would couple application behavior to role names and make future access changes difficult. Authorization also cannot rely on React hiding buttons because direct API calls must be protected.

## Decision
Use `spatie/laravel-permission` as the Laravel RBAC implementation.

Authorization is permission-first. Roles are named bundles of permissions for administration and onboarding, while backend code checks capabilities such as `entries.update` rather than scattering role-name checks throughout controllers.

Initial roles:

- **Owner**: full access and access-control administration. The final Owner access path is protected from accidental lockout.
- **Admin**: workspace administration and content management, without authority to remove the protected Owner access path.
- **Editor**: content creation, updates and deletion; no user/role administration.
- **Viewer**: read-only access to permitted workspace resources.

Initial permissions:

- `entries.view`, `entries.create`, `entries.update`, `entries.delete`
- `categories.view`, `categories.create`, `categories.update`, `categories.delete`
- `tags.view`, `tags.create`, `tags.update`, `tags.delete`
- `documents.view`, `documents.create`, `documents.update`, `documents.delete`, `documents.download`
- `users.view`, `users.update`
- `roles.view`, `roles.manage`
- `permissions.view`

Favorites remain personal/user-owned behavior.

## Authorization boundary
Laravel is authoritative. Every protected API operation must authorize server-side. React may hide or disable actions based on permissions for usability, but this is not a security control.

Existing ownership/private-resource rules remain additive to RBAC. In particular, granting `documents.view` does not by itself make another user's private document readable. Shared-workspace semantics must be introduced deliberately rather than accidentally weakening private storage.

Self-service profile, password and 2FA operations remain available to the authenticated account and are not governed by workspace administration permissions.

## API contract
Authenticated identity will expose normalized authorization metadata:

```json
{
  "user": {
    "id": 1,
    "name": "...",
    "roles": ["Owner"],
    "permissions": ["entries.view", "entries.create"]
  }
}
```

Administrative endpoints will be grouped under `/api/access-control` and protected by explicit permissions. Role assignment endpoints validate role names against persisted roles and enforce privilege-escalation/Owner-lockout rules.

## Frontend
React will provide a reusable `can(permission)` authorization helper derived from `/api/auth/me`. An Access Control page will expose users, role assignment and readable permission details only to authorized users. Unauthorized API responses receive a dedicated 403 experience.

## Security invariants

1. Default deny when a permission is absent.
2. Backend authorization on direct API calls.
3. No privilege escalation through role assignment.
4. The final Owner cannot accidentally remove the system's last Owner access path.
5. Private resource ownership rules remain enforced unless a future explicit sharing model changes them.
6. Seeders are idempotent and do not silently downgrade an existing Owner.
7. Secrets, private documents and local operational data never enter source control.

## Delivery
RBAC is implemented on its own feature branch after Document Library and Profile/Auth are stabilized on `main`. Backend feature tests cover Viewer, Editor, Admin and Owner behavior before the frontend administration surface is considered complete.
