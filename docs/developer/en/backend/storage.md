# File storage: local disk or S3

Cattr keeps three kinds of files. Each of them lives either on the local disk (`storage/app`, a volume / PVC) or in an S3-compatible bucket (AWS S3, MinIO, ...).

| Files | Disk | Selected by | Local location | S3 key prefix |
|---|---|---|---|---|
| Screenshots and thumbnails | default disk | `FILESYSTEM_DISK=local\|s3` | `storage/app/screenshots/` | `<AWS_ROOT>/screenshots/` |
| Task attachments | `attachments` | `ATTACHMENTS_DISK=local\|s3` | `storage/app/attachments/` | `<AWS_ROOT>/attachments/` |
| Generated reports | `reports` | `REPORTS_DISK=local\|s3` | `storage/app/public/reports/` (served at `/storage`) | `<AWS_ROOT>/reports/` |

Temporary files (uploads being processed, offline-sync archives) always stay on the local disk.

## S3 settings

| Variable | Meaning |
|---|---|
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | credentials |
| `AWS_DEFAULT_REGION` | region, `us-east-1` by default (any value for MinIO) |
| `AWS_BUCKET` | bucket, shared by all S3-backed disks |
| `AWS_ROOT` | optional key prefix inside the bucket |
| `AWS_ENDPOINT` | endpoint of a non-AWS service, e.g. `https://minio.example.com`; presigned links are signed for this host (see below) |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` for MinIO and most self-hosted services |
| `AWS_TEMPORARY_URL` | optional; replaces the scheme, host and port of presigned links, only for a CDN / proxy that preserves the signed host (see below) |

The bucket stays private: browsers get presigned links only.

### Endpoint for presigned links

A presigned link is signed with AWS Signature V4 for the host of `AWS_ENDPOINT`, and `Host` is part of the signature (`X-Amz-SignedHeaders=host`). The storage rejects a link that arrives with a different `Host`.

- **Recommended:** set `AWS_ENDPOINT` to an address that both the Cattr Pods and browsers reach under the same name, for example the public URL of MinIO (`https://minio.example.com`) with `AWS_USE_PATH_STYLE_ENDPOINT=true`, and leave `AWS_TEMPORARY_URL` empty.
- `AWS_TEMPORARY_URL` does not re-sign anything: Laravel signs the link for `AWS_ENDPOINT` and then replaces only the scheme, host and port (a path in `AWS_TEMPORARY_URL` is ignored). Use it only for a CDN or reverse proxy that forwards requests to the storage with the `Host` of `AWS_ENDPOINT`. An internal endpoint such as `http://minio:9000` plus an ingress that passes the browser's public `Host` gives `SignatureDoesNotMatch`.

## How browsers get files

- **Screenshots.** `time-intervals/list` and `time-intervals/show` return `screenshot_url` and `thumbnail_url` that expire in **10 minutes**. On the local disk these are relative URLs of `GET /api/time-intervals/{id}/screenshot|thumb` with a temporary signature (no bearer token needed, an invalid or expired signature gives 403). These two routes are throttled per IP: `SCREENSHOTS_RATE_LIMIT` requests per minute, 600 by default, which covers a full timeline day of 1-minute screenshots. Raise it when many users share one IP (office NAT, a proxy that does not forward client addresses). On S3 they are presigned object URLs. `GET /api/time-intervals/{id}/screenshot-urls` (authenticated, same access check as viewing the interval) returns fresh URLs. The frontend calls it for report payloads that carry no URLs (dashboard, project report) and when a page stays open past the URL lifetime.
- **Attachments.** `GET /api/attachment/{id}/temporary-url?seconds=N` (1 hour by default) returns a signed app URL on the local disk and a presigned URL on S3. `N` is limited to 7 days (604800, the S3 presigning limit) on every disk; a larger value is a validation error.
- **Reports.** The download endpoints return `/storage/reports/...` on the local disk and a presigned URL, valid for 10 minutes, on S3. The e-mail with the report attaches the file read from the `reports` disk.

## Screenshot rotation and retention

`cattr:screenshots:rotate` and the storage tab in the settings use the free space of the local disk. Object storage has no free space to measure, so on `FILESYSTEM_DISK=s3` rotation does nothing (`about/storage` reports `rotation_supported: false`). Set retention with bucket lifecycle rules instead, for example expire objects under `<AWS_ROOT>/screenshots/` (thumbnails are under `screenshots/thumbs/`) and `<AWS_ROOT>/reports/` after N days. Leave `attachments/` alone. Unlike local rotation, a lifecycle rule does not skip important projects, tasks or users with permanent screenshots.

[Backend guides](README.md) · [Русский](../../ru/backend/storage.md)
