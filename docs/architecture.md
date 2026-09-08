# Architecture

## Context

Marjo Tech Hub is a developer knowledge and operations workspace. It stores reusable technical knowledge such as commands, snippets, notes, and nested categories while keeping environment-specific secrets outside the repository.

## System Boundary

The application uses a separated frontend/backend architecture:

- **React + Vite** is responsible for presentation, routing, user interaction, and client-side application state.
- **Laravel** is responsible for REST APIs, authentication, authorization, validation, persistence, and business rules.
- The frontend must not depend on Blade-rendered application screens.
- The backend must expose explicit JSON API contracts rather than leaking database models directly to the UI.

## Initial Domain Direction

The first domain model is intentionally small:

- `User` — authenticated application user.
- `Category` — hierarchical container with an optional parent category.
- `Entry` — reusable knowledge item such as a command, code snippet, or note.
- `Favorite` — user-specific reference to an entry.

Exact schemas are intentionally deferred until their feature work begins. This avoids documenting fields that are not implemented yet.

## Category Hierarchy

Categories should support arbitrary nesting through a parent relationship. This allows structures such as:

```text
Server Commands
└── Production Logs
    ├── Analytics Errors
    ├── Laravel Errors
    ├── Maintenance
    └── Business Ops
```

The hierarchy is persisted by the backend and consumed by React; it is not hard-coded as a fixed menu tree.

## Security Boundary

This repository is public. Therefore:

- no real production credentials or access tokens;
- no private server addresses or customer information;
- no employer/client source code or proprietary command collections;
- `.env` files remain local and only sanitized `.env.example` values may be committed;
- demo/seed content must be generic and safe to publish.

Application authentication and authorization will be designed in a dedicated feature rather than mixed into the foundation change.

## Evolution

Redis, Horizon, Elasticsearch, Docker, Nginx, and CI/CD are intended parts of the engineering roadmap, but they should be introduced only in changes where they provide a concrete function. Keeping the baseline small makes each architectural decision visible in Git history and reviewable through its own pull request.
