# Mobile App API

This document describes the mobile app API implemented by this application.

## Base URL and request format

Use the application URL as the base URL. For example, a local XAMPP install may use:

```text
http://localhost/jusbeeadmin
```

Send JSON request bodies with:

```http
Content-Type: application/json
Accept: application/json
```

All responses are JSON. Protected endpoints require the bearer token returned by registration or login:

```http
Authorization: Bearer <token>
```

Tokens are 64-character hexadecimal strings, are valid for 30 days, and are stored server-side as hashes. An inactive account cannot use protected endpoints.

## Endpoints

Notification preference endpoints are `POST /api/Set_user_notification` and `GET /api/fetch_user_notification` (authenticated).

| Method | Path | Authentication | Description |
|---|---|---|---|
| `POST` | `/api/Set_user_notification` | App-user bearer token | Set notification preferences |
| `GET` | `/api/fetch_user_notification` | App-user bearer token | Fetch notification preferences |
| `POST` | `/api/register` | Public | Register and issue an access token |
| `POST` | `/api/login` | Public | Log in and issue an access token |
| `GET` | `/api/profile` | Bearer token | Get the authenticated user's profile |
| `PUT` | `/api/profile` | Bearer token | Update profile fields or password |
| `POST` | `/api/profile` | Bearer token | Same profile update operation |
| `GET` | `/api/journal-log?type={type}` | Bearer token | List the authenticated user's entries of a type |
| `POST` | `/api/journal-log` | Bearer token | Create a journal entry |
| `GET` | `/api/media-categories/{slug}` | Bearer token | Get a category and its child categories |
| `GET` | `/api/media-categories/{slug}/media` | Bearer token | List active media in a category |
| `GET` | `/api/media/{slug}` | Bearer token | Get active media content by slug |
| `GET` | `/api/test-content/category/{slug}` | Bearer token | List test content assigned to a category |
| `GET` | `/api/test-content/{slug}` | Bearer token | Get test content details by slug |
| `POST` | `/api/logout` | Bearer token | Revoke the current token |
| `POST` | `/api/logout-all` | Bearer token | Revoke all tokens for the authenticated user |

## Register

`POST /api/register`

Required JSON fields:

| Field | Type | Constraints |
|---|---|---|
| `first_name` | string | 1–120 characters |
| `last_name` | string | Optional; up to 120 characters and may be empty |
| `email` | string | Valid email, up to 190 characters; must be unique |
| `phone` | string | 7–15 digits, optionally prefixed with `+`; must be unique |
| `password` | string | 8–72 characters |

Optional profile fields:

| Field | Type | Constraints |
|---|---|---|
| `profile_thumbnail` | string or `null` | Up to 255 characters |
| `device_id` | string or `null` | Up to 255 characters |
| `current_stage` | string or `null` | Up to 120 characters |
| `birth_date` | string or `null` | Date in `YYYY-MM-DD` format |
| `reduce_stress` | boolean | Defaults to `false` |
| `sleep_better` | boolean | Defaults to `false` |
| `build_heithy_habits` | boolean | Defaults to `false` |
| `journal_throughts` | boolean | Defaults to `false` |
| `find_professional_support` | boolean | Defaults to `false` |
| `device_name` | string | Optional label for the issued token |

Example request:

```json
{
  "first_name": "Ari",
  "last_name": "Lee",
  "email": "ari@example.com",
  "phone": "+15551234567",
  "password": "replace-with-a-secure-password",
  "device_id": "phone-abc123",
  "birth_date": "1995-06-15",
  "reduce_stress": true,
  "sleep_better": false,
  "device_name": "Ari's phone"
}
```

Success response (`201 Created`):

```json
{
  "success": true,
  "message": "Registration successful.",
  "token": "<64-character-token>",
  "token_type": "Bearer",
  "expires_at": "2030-01-31 12:00:00",
  "user": {
    "id": 123,
    "email": "ari@example.com",
    "phone": "+15551234567",
    "first_name": "Ari",
    "last_name": "Lee",
    "profile_thumbnail": null,
    "device_id": "phone-abc123",
    "current_stage": null,
    "birth_date": "1995-06-15",
    "reduce_stress": true,
    "sleep_better": false,
    "build_heithy_habits": false,
    "journal_throughts": false,
    "find_professional_support": false,
    "status": "active",
    "created_at": "2030-01-01 12:00:00",
    "modified_at": "2030-01-01 12:00:00"
  }
}
```

## Log in

`POST /api/login`

Send `password` and either:

- `login`: an email address or phone number; or
- `email`: an email address (accepted as an alternative to `login`).

`device_name` is optional.

```json
{
  "login": "ari@example.com",
  "password": "replace-with-a-secure-password",
  "device_name": "Ari's phone"
}
```

A successful login returns `200 OK` with the same token and user response structure as registration, with `"message": "Login successful."`.

## Get profile

`GET /api/profile`

Requires a bearer token. Returns `200 OK`:

```json
{
  "success": true,
  "user": {
    "id": 123,
    "email": "ari@example.com",
    "phone": "+15551234567",
    "first_name": "Ari",
    "last_name": "Lee",
    "profile_thumbnail": null,
    "device_id": "phone-abc123",
    "current_stage": null,
    "birth_date": "1995-06-15",
    "reduce_stress": true,
    "sleep_better": false,
    "build_heithy_habits": false,
    "journal_throughts": false,
    "find_professional_support": false,
    "status": "active",
    "created_at": "2030-01-01 12:00:00",
    "modified_at": "2030-01-01 12:00:00"
  }
}
```

## Update profile

`PUT /api/profile` (or `POST /api/profile`)

Send only fields to change. Supported fields are `first_name`, `last_name`, `email`, `phone`, and the optional profile fields listed under registration.

To change the password, include both `password` and `current_password`. The new password must be 8–72 characters. On success, other sessions for this user are revoked while the current token remains valid.

```json
{
  "first_name": "Ari",
  "current_stage": "getting_started",
  "sleep_better": true
}
```

Success response (`200 OK`):

```json
{
  "success": true,
  "message": "Profile updated.",
  "user": {}
}
```

The `user` value contains the same fields as the profile response above.

## Journal logs

The supported `type` values are:

- `journal`
- `graditude`
- `dialy_dream`
- `worry_tree`

These values are case-sensitive and use the exact spellings shown.

### List entries by type

`GET /api/journal-log?type=journal`

Requires a bearer token and a valid `type` query parameter. Results belong to the authenticated user only and are sorted newest first by `added_date_time`.

Success response (`200 OK`):

```json
{
  "success": true,
  "journal_logs": [
    {
      "id": 45,
      "type": "journal",
      "app_user_id": 123,
      "title": "A fresh start",
      "description": "Today I took a moment to reflect.",
      "favorite": false,
      "added_date_time": "2030-01-01 12:00:00"
    }
  ]
}
```

If there are no matching entries, `journal_logs` is an empty array.

### Create an entry

`POST /api/journal-log`

Requires a bearer token. JSON fields:

| Field | Type | Constraints |
|---|---|---|
| `type` | string | One of the four supported types |
| `title` | string | Required, 1–255 characters after trimming |
| `description` | string | Required, maximum 65,535 bytes |
| `favorite` | boolean | Optional; defaults to `false` |

`app_user_id` is assigned from the authenticated token. `added_date_time` is set by the database when the entry is created; clients cannot set either field.

```json
{
  "type": "journal",
  "title": "A fresh start",
  "description": "Today I took a moment to reflect.",
  "favorite": false
}
```

Success response (`201 Created`):

```json
{
  "success": true,
  "message": "Journal log created.",
  "journal_log": {
    "id": 45,
    "type": "journal",
    "app_user_id": 123,
    "title": "A fresh start",
    "description": "Today I took a moment to reflect.",
    "favorite": false,
    "added_date_time": "2030-01-01 12:00:00"
  }
}
```

## Notification settings

These APIs require a valid app-user bearer token. Settings are scoped to the authenticated user; do not send `app_user_id`. Boolean fields may be updated individually or together. Omitted fields remain unchanged, and fields not previously set default to `false`.

### Set user notification

`POST /api/Set_user_notification` (alias: `POST /api/set_user_notification`)

Send one or more of `daily_reminder`, `streak_end_reminder`, `push_notification`, or `lunar_notification` as JSON booleans:

```json
{
  "daily_reminder": true,
  "streak_end_reminder": false
}
```

Returns `200 OK` with the complete saved settings:

```json
{
  "success": true,
  "message": "Notification settings saved.",
  "notification_settings": {
    "app_user_id": 123,
    "daily_reminder": true,
    "streak_end_reminder": false,
    "push_notification": false,
    "lunar_notification": false,
    "added_date_time": "2030-01-01 10:00:00",
    "updated_date_time": "2030-01-01 10:00:00"
  }
}
```

### Fetch user notification

`GET /api/fetch_user_notification` (alias: `GET /api/user-notifications`)

Returns `200 OK` with `notification_settings`. If the user has not saved any preferences yet, all four values are `false` and both timestamps are `null`; fetching does not create a row.

Create the `Notification_settings` table using `database/notification_settings.sql` after `ju_app_users` exists. If it was already created with the earlier mixed-case column names, run `database/migrate_notification_settings_lowercase_fields.sql`.

## Log out

`POST /api/logout` revokes the bearer token used for the request.

```json
{
  "success": true,
  "message": "Logged out."
}
```

`POST /api/logout-all` revokes every token belonging to the authenticated user.

```json
{
  "success": true,
  "message": "Logged out from all devices."
}
```

## Errors

Validation errors return `422 Unprocessable Entity`:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "type": "Provide a valid journal log type."
  }
}
```

Other common errors:

| Status | Meaning |
|---|---|
| `401 Unauthorized` | Bearer token is missing, malformed, invalid, expired, or revoked |
| `403 Forbidden` | App-user account is inactive, or login was attempted for an inactive account |
| `422 Unprocessable Entity` | Request fields are missing or invalid |

## Database setup

Create the app-user and token tables using `database/ju_app_users.sql` and `database/ju_app_user_tokens.sql`. Create the journal table using `database/journal_log.sql`.

## Media categories and content

These read-only endpoints require a valid bearer token. They return only active media. The category API includes only direct children, not all descendants.

### Get a category and its child categories

`GET /api/media-categories/{slug}`

Example: `GET /api/media-categories/mindfulness`

```json
{
  "success": true,
  "category": {
    "category_id": 8,
    "category_name": "Mindfulness",
    "description": "Mindfulness exercises",
    "slug": "mindfulness",
    "parent_id": null,
    "updated_by": 2,
    "added_date_time": "2030-01-01 10:00:00",
    "updated_date_time": "2030-01-02 10:00:00",
    "children": [
      {
        "category_id": 9,
        "category_name": "Breathing",
        "description": null,
        "slug": "breathing",
        "parent_id": 8,
        "added_date_time": "2030-01-01 10:00:00",
        "updated_date_time": "2030-01-01 10:00:00"
      }
    ]
  }
}
```

### List media by category slug

`GET /api/media-categories/{slug}/media`

Example: `GET /api/media-categories/breathing/media`

```json
{
  "success": true,
  "category": {
    "category_id": 9,
    "category_name": "Breathing",
    "slug": "breathing"
  },
  "media": [
    {
      "id": 41,
      "title": "Guided breathing",
      "slug": "guided-breathing",
      "thumbnail": "uploads/library/thumbnail-example.webp",
      "media_file": "uploads/library/media_file-example.mp3",
      "media_type": "audio",
      "play_time": "04:32",
      "artist": "Jusbee",
      "description": "A short guided breathing exercise.",
      "created_at": "2030-01-01 10:00:00",
      "modified_at": "2030-01-02 10:00:00"
    }
  ]
}
```

If a category has no active media, `media` is an empty array.

### Get media content by slug

`GET /api/media/{slug}`

Example: `GET /api/media/guided-breathing`

Returns the media fields shown above and the categories assigned to the media:

```json
{
  "success": true,
  "media": {
    "id": 41,
    "title": "Guided breathing",
    "slug": "guided-breathing",
    "thumbnail": "uploads/library/thumbnail-example.webp",
    "media_file": "uploads/library/media_file-example.mp3",
    "media_type": "audio",
    "play_time": "04:32",
    "artist": "Jusbee",
    "description": "A short guided breathing exercise.",
    "created_at": "2030-01-01 10:00:00",
    "modified_at": "2030-01-02 10:00:00",
    "categories": [
      {
        "category_id": 9,
        "category_name": "Breathing",
        "slug": "breathing",
        "parent_id": 8
      }
    ]
  }
}
```

The `thumbnail` and `media_file` values are paths relative to the application base URL. A missing category or unavailable/inactive media item returns `404 Not Found`.

## Test content

Test-content endpoints require a valid app-user bearer token. Content is available through the API once an admin creates it; the test-content schema has no draft/status field.

### List test content by category

`GET /api/test-content/category/{category_slug}`

Example: `GET /api/test-content/category/sleep`

```json
{
  "success": true,
  "category": {
    "category_id": 4,
    "category_name": "Sleep",
    "slug": "sleep",
    "parent_id": null
  },
  "contents": [
    {
      "id": 18,
      "slug": "sleep-routine",
      "title": "Build a sleep routine",
      "short_test": "A few ideas for a restful evening.",
      "thumb_nail": "uploads/test-content/thumbnail-example.webp",
      "added_date_time": "2030-01-01 10:00:00",
      "updated_date_time": "2030-01-02 10:00:00"
    }
  ]
}
```

The list includes items directly assigned to the requested category. `full_text` is returned by the detail endpoint. If the category has no content, `contents` is an empty array.

### Get test content by slug

`GET /api/test-content/{content_slug}`

Example: `GET /api/test-content/sleep-routine`

```json
{
  "success": true,
  "content": {
    "id": 18,
    "slug": "sleep-routine",
    "title": "Build a sleep routine",
    "short_test": "A few ideas for a restful evening.",
    "full_text": "Keep a consistent bedtime. Dim the lights and take time to wind down before sleep.",
    "thumb_nail": "uploads/test-content/thumbnail-example.webp",
    "updated_by": 2,
    "added_date_time": "2030-01-01 10:00:00",
    "updated_date_time": "2030-01-02 10:00:00",
    "categories": [
      {
        "category_id": 4,
        "category_name": "Sleep",
        "slug": "sleep",
        "parent_id": null
      }
    ]
  }
}
```

The thumbnail path is relative to the application base URL. An unknown category or content slug returns `404 Not Found`.

Create the test-content, category, and mapping tables with `database/test_content.sql` after `ju_users` exists.

## Counsellors

Counsellor endpoints require a valid app-user bearer token. Only counsellors with `verifies` set to `Y` are returned; unverified records are not accessible through the app API.

### List counsellors

`GET /api/counsellors` (also available as `GET /api/conselor_list`)

Returns `200 OK`, with an empty `counsellors` array when no counsellors are verified:

```json
{
  "success": true,
  "counsellors": [
    {
      "id": 5,
      "consellor_name": "Ari Lee",
      "profile_thumbnail": "uploads/counsellors/profile-example.webp",
      "qualifications": "MSc Counselling",
      "email": "ari@example.com",
      "phone": "+15551234567",
      "verifies": "Y",
      "biography": "A short counsellor biography.",
      "expertise": "Stress management, sleep",
      "standard_fee": 85,
      "discovery_fee": 0,
      "added_date_time": "2030-01-01 10:00:00",
      "updated_date_time": "2030-01-02 10:00:00"
    }
  ]
}
```

### Get a counsellor by ID

`GET /api/counsellors/{id}` (also available as `GET /api/conselor_by_id/{id}`)

Returns `200 OK` with a `counsellor` object using the same fields as a list item. Unknown IDs, invalid IDs, and unverified counsellors return `404 Not Found`. Thumbnail paths are relative to the application base URL.

Create the counsellor table with `database/counsellors.sql` after `ju_users` exists. Admins can manage counsellor profiles at `/counsellors`; profile thumbnails accept WebP or JPEG.
