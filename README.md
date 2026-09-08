# Marjo Tech Hub

> A developer knowledge and operations hub for organizing commands, code snippets, technical notes, and repeatable engineering workflows.

Marjo Tech Hub is an independent full-stack project built as a practical engineering workspace. The application is designed to make frequently used technical knowledge easy to organize, search, favorite, and reuse without keeping production secrets in source control.

## Product Direction

```text
Login
  |
  v
Marjo Tech Hub
  |
  +-- Favorites
  +-- Server Commands
  |     +-- Production Logs
  |           +-- Analytics Errors
  |           +-- Laravel Errors
  |           +-- Maintenance
  |           +-- Business Ops
  +-- Git
  +-- Docker
  +-- Laravel
  +-- Elasticsearch
  +-- Database
  +-- Nginx
  +-- Redis
  +-- Deployment
  +-- Code Snippets
  +-- Notes
```

The category tree is a product concept, not hard-coded frontend navigation. Categories and entries will be persisted by the backend so the workspace can evolve without application code changes.

## Architecture

```text
React + Vite
     |
     | HTTPS / JSON
     v
Laravel REST API
     |
     +-- Relational database
     +-- Redis / queues       (planned)
     +-- Elasticsearch        (planned)
```

### Frontend

React owns the application UI. The frontend will be developed as an independent Vite application and will communicate with Laravel exclusively through the API.

### Backend

Laravel owns authentication, authorization, validation, persistence, business rules, and API contracts. Blade is not used as the application UI.

## Repository Structure

```text
marjo-tech-hub/
├── backend/        # Laravel REST API
├── frontend/       # React + Vite
├── docs/           # Architecture and engineering decisions
└── README.md
```

The application directories will be bootstrapped as part of the project-foundation work. Infrastructure will be introduced incrementally rather than committed as unused boilerplate.

## Engineering Principles

- Keep frontend and backend responsibilities explicit.
- Never commit credentials, tokens, private keys, production IPs, customer data, or employer-specific code.
- Prefer small, reviewable feature branches and conventional commits.
- Add infrastructure only when the application actually uses it.
- Keep implemented functionality clearly separated from roadmap features.
- Design commands and snippets as reusable knowledge, not environment-specific secrets.
- Add automated tests around behavior that matters.

## Delivery Roadmap

1. Project foundation — Laravel API and React frontend
2. Authentication — secure login/session flow
3. Categories — nested technical knowledge structure
4. Entries — commands, snippets, and notes
5. Favorites — quick access to frequently used entries
6. Global search and filtering
7. Redis caching and asynchronous workloads where justified
8. Elasticsearch-backed search when the dataset and search requirements justify it
9. Dockerized local/runtime infrastructure
10. CI/CD, testing, observability, and deployment documentation

## Status

Early development. See GitHub issues and pull requests for the implemented scope and engineering history.

## Author

**Marjolin Jahja** — Full-Stack Software Engineer · Backend-Focused
