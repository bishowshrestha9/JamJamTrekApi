# Gallery API Documentation (JamJamTrek)

This document provides requested request and response structures for the newly implemented Gallery API. All admin routes require a Bearer token in the `Authorization` header.

---

## 1. Get Gallery Images (Public)
Fetch all active gallery images to display on the website.

- **Endpoint**: `/api/gallery`
- **Method**: `GET`
- **Auth Required**: No

### Response (Success)
```json
{
  "status": true,
  "message": "Gallery images fetched successfully",
  "data": [
    {
      "id": 15,
      "image": "gallery/gallery_1711726543_abcdef.jpg",
      "image_url": "https://jamjamtreks.com/storage/gallery/gallery_1711726543_abcdef.jpg",
      "caption": "View of Mt. Everest",
      "is_active": true,
      "created_at": "2026-03-29T21:40:43.000000Z",
      "updated_at": "2026-03-29T21:40:43.000000Z"
    },
    {
      "id": 14,
      "image": "gallery/gallery_1711726543_ghijk.jpg",
      "image_url": "https://jamjamtreks.com/storage/gallery/gallery_1711726543_ghijk.jpg",
      "caption": "Sunrise at Poon Hill",
      "is_active": true,
      "created_at": "2026-03-29T21:40:43.000000Z",
      "updated_at": "2026-03-29T21:40:43.000000Z"
    }
  ]
}
```

---

## 2. Bulk Upload Images (Admin Only)
Upload multiple images at once. Uses `multipart/form-data`.

- **Endpoint**: `/api/gallery`
- **Method**: `POST`
- **Auth Required**: Yes (Bearer Token)
- **Content-Type**: `multipart/form-data`

### Request Body
| Key | Type | Description | Required |
| :--- | :--- | :--- | :--- |
| `images[]` | `File[]` | Array of image files (JPG, PNG, WebP) | Yes |
| `caption` | `String` | Caption to apply to ALL images in this batch | No |
| `is_active` | `Boolean` | Visibility status (`true`/`false`) | No (Default: true) |

### Response (Created)
```json
{
  "status": true,
  "message": "3 image(s) added to gallery successfully"
}
```

---

## 3. Update Gallery Item (Admin Only)
Update a single image or its caption. To upload a new file, you MUST use `POST` instead of `PUT/PATCH` due to Laravel's multipart file handling.

- **Endpoint**: `/api/gallery/{id}`
- **Method**: `POST`
- **Auth Required**: Yes (Bearer Token)
- **Content-Type**: `multipart/form-data`

### Request Body
| Key | Type | Description | Required |
| :--- | :--- | :--- | :--- |
| `image` | `File` | New image file (replace existing) | No |
| `caption` | `String` | New caption | No |
| `is_active` | `Boolean` | New visibility status | No |

### Response (Updated)
```json
{
  "status": true,
  "message": "Gallery updated successfully"
}
```

---

## 4. Delete Gallery Item (Admin Only)
Permanently remove an image from the gallery and file storage.

- **Endpoint**: `/api/gallery/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Bearer Token)

### Response (Deleted)
```json
{
  "status": true,
  "message": "Gallery item deleted successfully"
}
```

---

## Technical Notes for Frontend:
1. **Base URL**: The application is running at `https://jamjamtreks.com` (as per CSRF config).
2. **Image URLs**: Always use the `image_url` field from the response to display images, as it provides the absolute path.
3. **Multi-upload**: When sending the images array, ensure the key is named `images[]` in your FormData object.
4. **Error Handling**: Standard Laravel 422 for validation errors (e.g., file too large > 10MB) or 401 for unauthorized access.
