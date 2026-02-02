<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Orophile Trek API Documentation

Welcome to the Orophile Trek API. This application provides a RESTful API for managing treks and authentication.

## Base URL

`http://localhost:8000` (Development)

## Authentication

The API uses **Laravel Sanctum** for authentication.

*   **Header**: `Authorization: Bearer <your_token>`

Most endpoints related to managing data require authentication.

---

## API Endpoints

### 1. Authentication

#### User Login
Authenticate a user and receive an API token.

*   **Endpoint**: `POST /api/login`
*   **Access**: Public

**Request Body (JSON):**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "status": true,
  "message": "Login successful",
  "token": "auth_token_string_here..."
}
```

**Response (401 Unauthorized):**
```json
{
  "status": false,
  "message": "Invalid login details"
}
```

#### User Logout
Invalidate the current user's token.

*   **Endpoint**: `POST /api/logout`
*   **Access**: Protected (Requires Bearer Token)

**Response (200 OK):**
```json
{
  "status": true,
  "message": "Logged out"
}
```

#### Get Authenticated User Info
Retrieve details about the currently logged-in user.

*   **Endpoint**: `GET /api/me`
*   **Access**: Protected (Requires Bearer Token)

**Response (200 OK):**
```json
{
  "status": true,
  "role": "admin"
}
```

---

### 2. Treks & Packages

Manage treks and packages.

#### Get All Treks
Retrieve a paginated list of treks.

*   **Endpoint**: `GET /api/treks`
*   **Access**: Protected

**Query Parameters:**
*   `data_type`: (Optional) Filter by type. Values: `trek`, `package`.
*   `is_active`: (Optional) Filter by data status. Values: `1` (true), `0` (false).

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "treks": [
      {
        "id": 1,
        "title": "Everest Base Camp",
        "location": "Solukhumbu",
        "price": 1500,
        "duration": "14 Days",
        "difficulty": "Hard",
        "type": "Adventure",
        "distance_km": 130,
        "description": "A wonderful trek...",
        "featured_image": "http://localhost:8000/treks/image.jpg",
        "is_featured": true,
        "is_active": true,
        "currency": "USD",
        "data_type": "trek",
        "trek_days": ["Day 1: Arrival", "Day 2: Kathmandu"],
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "total": 50,
      "per_page": 10,
      "current_page": 1,
      "last_page": 5
    }
  },
  "message": "Treks retrieved successfully"
}
```

#### Create a New Trek
Store a new trek in the database.

*   **Endpoint**: `POST /api/treks`
*   **Access**: Protected
*   **Content-Type**: `multipart/form-data`

**Request Body (Form Data):**

| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `title` | String | Yes | Title of the trek/package. |
| `location` | String | Yes | Location/Region. |
| `price` | Number | Yes | Cost. |
| `currency` | String | Yes | E.g., USD, NPR. |
| `duration` | String | Yes | E.g., "5 Days". |
| `difficulty` | String | Yes | E.g., Easy, Medium, Hard. |
| `type` | String | Yes | Type of activity. |
| `distance_km` | Number | Yes | Total distance. |
| `data_type` | String | Yes | `trek` or `package`. |
| `is_featured` | Boolean | Yes | `1` or `0`. |
| `is_active` | Boolean | Yes | `1` or `0`. |
| `trek_days[]` | Array | Yes | Array of day description strings. |
| `description` | String | No | Detailed description. |
| `featured_image` | File | No | Image file (jpg, png, etc.). |

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "New Trek",
    ...
  },
  "message": "Trek created successfully"
}
```

#### Get Single Trek
Retrieve details of a specific trek.

*   **Endpoint**: `GET /api/treks/{id}`
*   **Access**: Protected

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Everest Base Camp",
    ...
  },
  "message": "Trek retrieved successfully"
}
```

**Response (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Trek] {id}"
}
```

#### Update a Trek
Update an existing trek.

*   **Endpoint**: `POST /api/treks/{id}?_method=PUT`
*   **Access**: Protected
*   **Content-Type**: `multipart/form-data` (if uploading image) or `application/x-www-form-urlencoded`

**Note:** To support file uploads via `PUT`, use the `POST` method with `_method=PUT` data or query parameter.

**Request Body:**
Same as specific fields in "Create a New Trek".

**Response (200 OK):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Trek updated successfully"
}
```

---

### 3. Blogs

Manage blog posts with images and rich content.

#### Get All Blogs
Retrieve a paginated list of all blogs.

*   **Endpoint**: `GET /api/blogs`
*   **Access**: Public

**Query Parameters:**
*   `per_page`: (Optional) Number of items per page. Default: `10`.

**Response (200 OK):**
```json
{
  "status": true,
  "message": "Blogs fetched successfully",
  "data": [
    {
      "id": 1,
      "title": "My First Blog",
      "subtitle": "A subtitle",
      "description": "Blog description",
      "excerpt": "Short excerpt",
      "author": "John Doe",
      "slug": "my-first-blog",
      "is_active": true,
      "image": "blogs/image.jpg",
      "image_url": "http://localhost:8000/storage/blogs/image.jpg",
      "content": [
        {
          "heading": "Introduction",
          "paragraph": "This is the intro"
        }
      ],
      "conclusion": "Final thoughts",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

#### Create a New Blog
Store a new blog post with image upload.

*   **Endpoint**: `POST /api/blogs`
*   **Access**: Protected (Admin only)
*   **Content-Type**: `multipart/form-data`

**Request Body (Form Data):**

| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `title` | String | Yes | Blog title. |
| `subtitle` | String | No | Blog subtitle. |
| `description` | String | Yes | Full description. |
| `excerpt` | String | No | Short excerpt (max 500 chars). |
| `author` | String | No | Author name. |
| `content` | String (JSON) | No | JSON array: `[{"heading":"...", "paragraph":"..."}]` |
| `conclusion` | String | No | Conclusion text. |
| `is_active` | Boolean | Yes | `true` or `false`. |
| `slug` | String | Yes | URL-friendly slug (must be unique). |
| `image` | File | Yes | Featured image (jpeg, jpg, png, gif, webp, max 5MB). |

**Response (201 Created):**
```json
{
  "status": true,
  "message": "Blog created successfully"
}
```

#### Get Single Blog
Retrieve details of a specific blog by title slug.

*   **Endpoint**: `GET /api/blogs/{title}`
*   **Access**: Public

**Path Parameter:**
*   `title`: Blog title slug (e.g., `my-first-blog`)

**Response (200 OK):**
```json
{
  "status": true,
  "message": "Blog fetched successfully",
  "data": {
    "id": 1,
    "title": "My First Blog",
    "subtitle": "A subtitle",
    "description": "Blog description",
    "excerpt": "Short excerpt",
    "author": "John Doe",
    "slug": "my-first-blog",
    "is_active": true,
    "image": "blogs/image.jpg",
    "image_url": "http://localhost:8000/storage/blogs/image.jpg",
    "content": [
      {
        "heading": "Introduction",
        "paragraph": "This is the intro"
      }
    ],
    "conclusion": "Final thoughts",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Response (404 Not Found):**
```json
{
  "status": false,
  "message": "Blog not found"
}
```

#### Update a Blog
Update an existing blog post.

*   **Endpoint**: `POST /api/blogs/{id}`
*   **Access**: Protected (Admin only)
*   **Content-Type**: `multipart/form-data`

**Path Parameter:**
*   `id`: Blog ID

**Request Body:**
Same fields as "Create a New Blog", but all fields are optional except those being updated.

**Response (200 OK):**
```json
{
  "status": true,
  "message": "Blog updated successfully"
}
```

#### Delete a Blog
Remove a blog from the database.

*   **Endpoint**: `DELETE /api/blogs/{id}`
*   **Access**: Protected (Admin only)

**Response (200 OK):**
```json
{
  "status": true,
  "message": "Blog deleted successfully"
}
```

#### Get Total Blogs Count
Retrieve the total number of blogs.

*   **Endpoint**: `GET /api/blogs/total`
*   **Access**: Protected (Admin only)

**Response (200 OK):**
```json
{
  "status": true,
  "total_blogs": 42
}
```
