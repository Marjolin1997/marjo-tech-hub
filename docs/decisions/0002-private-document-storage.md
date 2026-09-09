# ADR 0002: Private document storage

## Context

Marjo Tech Hub needs to store personal engineering documents alongside structured knowledge entries. Uploaded files may contain private or sensitive material and must never become public web assets or repository content.

## Decision

Documents are represented by relational metadata in MySQL while binary content is stored through Laravel Filesystem on a dedicated private `documents` disk. The local implementation stores binaries below `storage/app/private/documents`, partitioned by authenticated user ID and named with collision-resistant UUIDs.

The API stores the original filename only as metadata. It also records server-derived MIME type, extension, byte size and SHA-256 checksum. Uploads are limited to 20 MB and to PDF, DOCX, TXT, Markdown, PNG and JPEG.

All list, metadata, preview, download, update and delete operations are behind `auth:sanctum` and enforce document ownership. Category and tag assignment also validates ownership. Physical paths and stored filenames are not exposed by the API resource.

Preview is restricted to PDF, PNG, JPEG, TXT and Markdown. DOCX is download-only. Responses include `X-Content-Type-Options: nosniff`.

## Consequences

- Personal binaries remain outside the public web root and Git repository.
- Database backups and file backups are separate concerns.
- File and metadata lifecycle requires explicit failure handling to avoid orphaned state.
- The domain can move to S3, Cloudflare R2 or MinIO by replacing filesystem disk configuration rather than rewriting controllers or persistence schema.

## Alternatives considered

### Store binaries in MySQL
Rejected because it couples database growth and backup behavior to large binary payloads and provides no advantage for the current product.

### Public Laravel storage
Rejected because private documents must not be addressable through predictable public URLs.

### S3-compatible storage immediately
Deferred. The filesystem abstraction preserves that path without adding infrastructure before the product needs it.

## Future scope

OCR, document text extraction, Elasticsearch indexing, semantic search, Redis/Horizon processing, document-entry relationships and cloud object storage are intentionally outside this first vertical slice.
