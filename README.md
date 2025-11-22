# Blog Portal (CodeIgniter 3 + MySQL + JWT + Pixabay)

Objective
- Build a simple blog management portal using PHP (CodeIgniter 3) + MySQL with JWT authentication and Pixabay image/video search for attaching media to posts.

Tech Stack and Versions
- PHP: 7.4+ (tested), works on 8.x
- CodeIgniter: 3.1.13
- MySQL: 5.7+ (or MariaDB 10.4+)
- Web Server: PHP built-in server or Apache
- Frontend: Minimal HTML/CSS/JS (no heavy frameworks)
- JWT: firebase/php-jwt (embedded, no Composer)
- HTTP client: native PHP cURL for Pixabay proxy (fallback to file_get_contents)

Folders
- application/       CI app (controllers, models, views, config, helpers)
- assets/            JS/CSS assets
- system/            CodeIgniter framework core
- sql/               SQL schema + seed
- index.php          Front controller at project root

Setup

1) Environment variables
- Copy .env.example to .env and fill values:
  - BASE_URL: e.g., http://localhost:8080/
  - DB_HOST, DB_NAME, DB_USER, DB_PASS
  - JWT_SECRET: a long random string
  - APP_KEY: a random 32+ char string (used as CI encryption_key)
  - PIXABAY_API_KEY: from https://pixabay.com/api/docs/ (free account)
  - PAGINATION_PER_PAGE: default 10
- .env is ignored by .gitignore.

2) Database (MySQL)
- In phpMyAdmin, create database and seed:
  - Import sql/schema_seed.sql
  - Creates tables: users, posts
  - Seeds:
    - Admin user (role: admin)
    - 2 normal users (role: user)
    - 2 sample posts
- Seeded accounts are defined in sql/schema_seed.sql. Refer there for the seeded emails and passwords.
  Passwords are bcrypt-hashed via password_hash().

3) Run locally
Option A: PHP built-in server (recommended for quick demo)
- From project root:
  php -S localhost:8080 -t .
- Visit: http://localhost:8080/index.php

Option B: Apache
- Point VirtualHost document root to the project root directory.
- If you configure rewrite rules to hide index.php, adjust config accordingly; otherwise use explicit /index.php/ in URLs.

Environment and Config

- application/config/env_loader.php loads .env and exposes env($key, $default).
- application/config/config.php reads BASE_URL and APP_KEY and sets encryption_key.
- application/config/database.php uses PDO with DSN from .env (mysql:host=...;dbname=...;charset=utf8).
- application/config/autoload.php loads database, session, and helpers (url, form, security, jwt, slug, sanitize).
- application/config/routes.php defines:
  - GET  /index.php/                Public posts list (Blog::index, paginated)
  - GET  /index.php/post/(:any)     Public post by slug
  - GET  /index.php/login           Login page (HTML)
  - GET  /index.php/dashboard       Admin dashboard (HTML)
  - GET  /index.php/posts/create    Create form (HTML)
  - GET  /index.php/posts/edit/(:n) Edit form (HTML)
  - POST /index.php/api/login       Login (JSON) -> issues JWT
  - GET  /index.php/api/posts       List (pagination + search/filter)
  - GET  /index.php/api/posts/(:n)  Show one post (for editing)
  - POST /index.php/api/posts       Create (auth required; role checks)
  - PUT  /index.php/api/posts/(:n)  Update (auth + owner/admin)
  - DELETE /index.php/api/posts/(:n) Soft delete (auth + owner/admin)
  - GET  /index.php/api/pixabay/search  Server-side proxy to Pixabay (auth; uses API key from .env)

Roles and Authorization

- JWT contains: sub (user id), role, exp, iat
- Roles:
  - admin: can create/edit/soft-delete any post
  - user: can view all posts; can create/edit/soft-delete own posts
- Passwords stored hashed via password_hash()
- JWT verified on protected endpoints; 401/403 returned appropriately
- Client stores token in localStorage; sent via Authorization: Bearer <token>
- Logout is client-side (discard token)

Pixabay Integration

- On Create/Edit form, use the “Search Pixabay” button to query via backend /index.php/api/pixabay/search with q (term), type (image|video).
- Display thumbnails; on select, cover_media_url and media_type are set and preview shown.
- Must include attribution string in the post view (“Media via Pixabay”).
- API key is stored in .env (PIXABAY_API_KEY). Never exposed to frontend JS; proxy attaches it server-side.

Security Practices

- Secrets kept in .env (excluded via .gitignore)
- PDO prepared statements used in models (via CI database->query with bindings)
- Output escaped; body sanitized with whitelist (sanitize_body) for allowed HTML
- JWT verified on write operations; proper 401/403
- CORS: not required for single-origin app. If splitting FE/BE, configure CORS as needed.

Libraries Used

- firebase/php-jwt (embedded as application/third_party/JWT/*)
  Reason: reliable JWT implementation for PHP without Composer.
- No additional vendor frameworks to keep the project lightweight.

Assumptions and Limitations

- Minimal UI
- Token refresh endpoint implemented (optional bonus)
- Rich text editing limited to basic allowed HTML tags
- Slug format: unique with -1, -2 collision handling on create; slug remains stable on update
- Pagination: 10 per page by default (configurable via .env)

Endpoints Summary (Base URL includes /index.php/)
- POST /index.php/api/login
  Request: { email, password } (x-www-form-urlencoded)
  Response: { token, user: { id, name, role } }
- GET /index.php/api/posts?q=title&author=name&page=1&per_page=10
  Response: { page, per_page, total, rows: [...] }
- GET /index.php/api/posts/{id}
  Response: the selected post (for edit form)
- POST /index.php/api/posts
  Fields: title, body, cover_media_url (optional), media_type (image|video)
- PUT /index.php/api/posts/{id}
  Same fields as create (slug stays stable)
- DELETE /index.php/api/posts/{id}
  Soft delete: marks status=deleted
- POST /index.php/api/posts/{id}/restore
  Restore a soft-deleted post to active (auth + owner/admin)
- DELETE /index.php/api/posts/{id}/hard
  Hard delete (admin only) — permanently removes the post
- GET /index.php/api/pixabay/search?q=flowers&type=image&page=1
  Response: { hits: [...], totalHits: N } (sanitized, simplified)
- POST /index.php/api/refresh
  Response: { token } (rotates HttpOnly refresh cookie and issues new access JWT)
- POST /index.php/api/logout
  Response: { logged_out: true } (revokes refresh token and clears cookie)

How to Fill .env (Sample)

BASE_URL=http://localhost:8080/
APP_KEY=change_this_to_a_random_32_char_string
DB_HOST=127.0.0.1
DB_NAME=blog_ci
DB_USER=root
DB_PASS=
JWT_SECRET=change_this_to_a_long_random_string
REFRESH_TTL=1209600
PIXABAY_API_KEY=your_pixabay_api_key_here
PAGINATION_PER_PAGE=10

Test Accounts

Admin (for review):
- Email: admin@example.com
- Password: Admin@123

Other seeded accounts (including non-admin users) are listed in sql/schema_seed.sql.

Database Schema and Seed (from sql/schema_seed.sql)
- Database: blog_ci
- Tables:
  - users: id (PK), name, email (unique), password_hash (bcrypt), role (admin|user), created_at
  - posts: id (PK), user_id (FK users.id), title, slug (unique), body, cover_media_url, media_type (image|video), status (active|deleted), created_at, updated_at
- Constraints:
  - uniq_email on users.email
  - uniq_slug on posts.slug
  - fk_posts_user (posts.user_id -> users.id, ON DELETE CASCADE)
- Seeded Users: refer to sql/schema_seed.sql for the seeded emails and passwords.
- Seeded Posts:
  - "Welcome to the Blog" (slug: welcome-to-the-blog) — status active
  - "Getting Started with CI3" (slug: getting-started-with-ci3) — status active

Run Order Checklist

1) Create .env from .env.example
2) Create DB and import sql/schema_seed.sql
3) Start server: php -S localhost:8080 -t .
4) Visit /index.php/login to sign in and get a token (client stores it)
5) Go to /index.php/dashboard to manage posts
6) Create/edit posts; use Pixabay search; verify “Media via Pixabay” on public view

What is needed for Pixabay integration

- A valid free API key from https://pixabay.com/api/docs/
- Outbound HTTPS access from PHP (enable php_curl if available; otherwise allow_url_fopen)
- Put the key in .env as PIXABAY_API_KEY. Do not place the key in frontend code.
