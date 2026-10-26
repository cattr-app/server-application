// Shared constants of the e2e harness.

// Backend under test (`artisan serve` of the stand); see tests/e2e/README.md.
export const BASE_URL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8001';

// Admin created by `cattr:reset --seed` (AdminSeeder -> cattr:make:admin defaults).
export const ADMIN = { email: 'admin@cattr.app', password: 'password' };

// Clock of the seed (tests/e2e/support/seed.php, SEED_NOW). Browser pages run with this fixed time,
// so "today" in the UI is the day the demo intervals were seeded (09:30-12:00 UTC).
export const FROZEN_NOW = '2026-03-11T12:00:00Z';
export const SEED_DATE = '2026-03-11';

// Fixed browser environment for deterministic rendering.
export const TIMEZONE = 'UTC';
export const LOCALE = 'en-US';

export const STORAGE_STATE = 'tests/e2e/.auth/admin.json';

// Which frontend build the stand serves: "dev" (`pnpm dev`, Vue prints [Vue warn]) or "prod" (`pnpm prod`).
// Only informational for the reports; the smoke suite checks [Vue warn] whenever the build prints them.
export const BUILD = process.env.E2E_BUILD || 'prod';
