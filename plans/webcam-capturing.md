# Webcam capturing plan (server app)

This plan covers the server application only. Desktop client work is documented in `/Users/romankholiavkoWork/Projects/cattr/cattr-desktop-application/plans/webcam-capturing.md`.
Webcam policy mirrors screenshots policy with required/optional/forbidden states at company, user, and project levels.

## 1) Data model & policy

### 1.1 Enum and state helpers
- [x] Add `App\Enums\WebcamState` mirroring `ScreenshotsState`:
  - `ANY (-1)`, `FORBIDDEN (0)`, `REQUIRED (1)`, `OPTIONAL (2)`
  - `title()`, `states()`, `createFrom()`, `withGlobalOverrides()`, `getNormalizedValue()`

### 1.2 Database schema
- [x] Company settings:
  - Add `webcam_state` and `webcam_state_locked` (env override locking like screenshots).
- [x] Users:
  - Add `webcam_state`, `webcam_state_locked` columns.
- [x] Projects:
  - Add `webcam_state` column.

Migrations should follow the existing screenshots state migrations.

### 1.3 Validation & serialization
- [x] Update requests to accept and validate webcam state:
  - `CompanySettings` update request
  - `User` create/edit requests
  - `Project` create/edit requests
- [x] Add webcam state fields to API responses for company/user/project.
- [x] Include env override in `config/app.php` (e.g., `WEBCAM_STATE`).
- [x] Add webcam_state_locked logic to UserController create/edit methods.

## 2) Storage & service layer

### 2.1 Webcam screenshot service
- [x] Create `App\Contracts\WebcamScreenshotService` or extend `ScreenshotService` with webcam paths:
  - `getWebcamPath(TimeInterval|int $interval)`
  - `getWebcamThumbPath(TimeInterval|int $interval)`
  - `saveWebcamScreenshot($file, TimeInterval $interval)`
- [x] Add production implementation similar to `ProductionScreenshotService`.

### 2.2 TimeInterval model
- [x] Add `has_webcam_screenshot` accessor similar to `has_screenshot`.

## 3) Interval API

### 3.1 Create interval with webcam screenshot
- [x] Extend `IntervalController@create` to accept `webcam_screenshot` (multipart file).
- [x] Store webcam screenshot based on policy:
  - `REQUIRED` => must store (reject if missing or invalid)
  - `OPTIONAL` => store if provided
  - `FORBIDDEN` => ignore or reject

### 3.2 Endpoints
- [x] Add endpoints to fetch webcam images:
  - `GET /time-intervals/{interval}/webcam`
  - `GET /time-intervals/{interval}/webcam-thumb`
- [x] Add a PUT endpoint to attach a webcam screenshot after interval creation (mirrors screenshot flow).

## 4) Offline upload flow

### 4.1 Offline intervals metadata
- [x] Include a `webcam_screenshot_id` in offline interval payloads when webcam is present.

### 4.2 Offline screenshot upload
- [x] Add a webcam equivalent to `uploadOfflineScreenshots`:
  - Dedicated ZIP upload endpoint
  - Validate filename schema (user_id + webcam_screenshot_id)
  - Attach webcam screenshots and clear the ID after upload

## 5) Server UI

### 5.1 Settings toggles
- [x] Add "Webcam monitoring" state selectors in:
  - Company Settings
  - User Settings
  - Project Settings
- [x] Implement state override/locking behavior identical to screenshot controls.

### 5.2 Screenshots UI
- [ ] Update `resources/frontend/core/components/Screenshot.vue` and `ScreenshotModal.vue`:
  - Add a toggle to switch between "Screen" and "Webcam".
  - Show "camera-off" if webcam image is missing.
- [ ] Use new endpoints for image/thumbnail loading.

### 5.3 Store module
- [ ] Add `resources/frontend/core/store/modules/webcam.js` similar to `screenshots.js`:
  - `states`, `enabled`, override logic, locking checks.

## 6) API client contract

### 6.1 Field names
- [ ] Standardize multipart field name `webcam_screenshot`.
- [ ] Standardize offline metadata `webcam_screenshot_id` and ZIP filename schema.

## 7) Tests & validation

### 7.1 Server tests
- [ ] Add/extend tests:
  - webcam state validation for company/user/project
  - create interval with webcam screenshot
  - webcam endpoints (image + thumb)
  - offline webcam upload

## 8) Notes & edge cases
- If webcam is not present or blocked by OS policy, accept interval without webcam and log.
- Keep screenshot and webcam shots decoupled so one failure doesn't affect the other.
