# Blog Portal - Implementation Guide

Objective
- Build a simple blog management portal using PHP (CodeIgniter 3) + MySQL with JWT authentication and Pixabay image/video search.
- This guide explains architecture, endpoints, setup, security, roles, and how to demo.

Stack and Versions
- PHP: 7.4+ recommended (works on 8.x)
- CodeIgniter: 3.1.13
- MySQL: 5.7+ (or MariaDB 10.4+)
- Frontend: Minimal HTML/CSS/JS
- JWT: firebase/php-jwt v6.x (embedded as plain PHP files)
- HTTP client: PHP cURL (fallback to file_get_contents if cURL is disabled)

Repository Structure (key parts)
- application/
  - config/
    - config.php, autoload.php, database.php, env_loader.php, routes.php
  - controllers/
    - Auth.php (login endpoint + login HTML view)
    - Blog.php (public list + post by slug)
    - Dashboard.php (admin HTML dashboard)
    - api/Posts.php (CRUD API)
    - api/Pixabay.php (Pixabay proxy)
  - helpers/
    - jwt_helper.php (JWT encode/decode, guards)
    - slug_helper.php (slug generation)
    - sanitize_helper.php (allowed HTML)
  - models/
    - User_model.php (auth, managing users)
    - Post_model.php (posts CRUD, pagination, slug handling)
  - views/
    - auth/login.php (login form + JS)
    - blog/index.php (public list with pagination)
    - blog/view.php (public single post + attribution)
    - dashboard/index.php (admin listing with search)
    - dashboard/form.php (admin create/edit form + Pixabay modal via JS)
- assets/
  - css/style.css (minimal styling)
  - js/app.js (dashboard logic and Pixabay picker)
- sql/schema_seed.sql (DB schema + seed)
- .env.example (BASE_URL, DB, JWT_SECRET, PIXABAY_API_KEY)
- .gitignore
- index.php (CI front controller)

Environment Configuration
- application/config/env_loader.php reads .env at project root into $_ENV and getenv() via putenv.
- .env.example variables:
  - CI_ENV=development
  - BASE_URL=http://localhost:8000/
  - APP_KEY=change_this_to_a_random_32_char_string
  - JWT_SECRET=change_this_to_a_long_random_string
  - DB_HOST=127.0.0.1
  - DB_NAME=blog_ci
  - DB_USER=root
  - DB_PASS=
  - PIXABAY_API_KEY=your_pixabay_api_key_here
  - PAGINATION_PER_PAGE=10

Security Practices
- Secrets are stored in .env (ignored by .gitignore).
- Database access uses CodeIgniter PDO driver with bound parameters (preventing SQL injection).
- JWT:
  - HS256 signing with secret from .env (JWT_SECRET).
  - Claims include sub (user id), role, exp and iat.
  - require_jwt() guard checks token and role (401/403 returned appropriately).
- Post body sanitation:
  - sanitize_body allows a minimal whitelist of tags (p, br, strong, em, b, i, u, ul, ol, li, a) and cleans attributes on links.
- Output escaping:
  - e() helper escapes variables in views.
- CORS:
  - Not needed for this single-origin app. If split into separate FE/BE, configure appropriate CORS.

Authentication and Roles
- Login endpoint issues JWT on valid credentials.
- Roles:
  - admin: can create, edit, soft delete any post.
  - user: can view all posts; can create/edit/soft-delete own posts.
- JWT storage:
  - Client stores the token in localStorage and sends Authorization: Bearer <token> for API requests.

Database Schema (see sql/schema_seed.sql)
- users: id, name, email (unique), password_hash (bcrypt), role (admin|user), created_at
- posts: id, user_id (FK users.id), title, slug (unique), body, cover_media_url, media_type (image|video), status (active|deleted), created_at, updated_at

Slug Generation and Collisions
- On create: slugify(title) and check uniqueness.
- If slug exists, append -1, -2, ... until unique (safety cap at 1000).
- On update: slug remains stable as required; title can change without re-slugging.

Soft Delete
- posts.status is set to 'deleted'; the record is retained.
- Public endpoints show only active posts.

Pagination and Search
- Public list: GET / (Blog::index) paginates (default 10/page).
- Admin dashboard: uses API with page/per_page and filters (title, author).

Pixabay Integration
- Admin form includes a modal to search Pixabay (images or videos) via GET /api/pixabay/search endpoint (server-side proxy).
- Results show thumbnails; on select, cover_media_url and media_type are set in the form.
- API key is stored in .env as PIXABAY_API_KEY and never exposed to client; proxy attaches it.
- Attribution: Public post view shows “Media via Pixabay”.

Endpoints Summary
- Authentication
  - POST /index.php/api/login
    - Body: email, password (x-www-form-urlencoded)
    - Response: { token, user: { id, name, email, role } }
    - JWT claims: sub, role, exp, iat
- Posts (protected; Authorization: Bearer)
  - GET /index.php/api/posts
    - Query: page, per_page, title, author
    - Response: { page, per_page, total, rows: [ { id, title, slug, author, created_at, status } ] }
  - GET /index.php/api/posts/{id}
    - Response: a full post row (for editing)
  - POST /index.php/api/posts
    - Body: { title, body, cover_media_url?, media_type? } (JSON or form)
    - Role: admin or user (both can create)
    - Returns: { id, slug }
  - PUT /index.php/api/posts/{id}
    - Body: { title?, body?, cover_media_url?, media_type? }
    - Role: admin or owner
    - Slug does not change on update
  - DELETE /index.php/api/posts/{id}
    - Role: admin or owner
    - Soft deletes: status = deleted
- Pixabay (protected; Authorization: Bearer)
  - GET /index.php/api/pixabay/search
    - Query: q, type=image|video, page
    - Returns: sanitized JSON with hits suitable for the form UI
- Public
  - GET /index.php/ -> Blog::index (list)
  - GET /index.php/post/{slug} -> Blog::view (detail with attribution)
- HTML Screens
  - GET /index.php/login -> HTML login form
  - GET /index.php/dashboard -> Admin dashboard (JS-driven)
  - GET /index.php/posts/create -> Create screen
  - GET /index.php/posts/edit/{id} -> Edit screen

Controller Responsibilities
- Auth
  - Renders login HTML.
  - Issues JWT on POST /api/login.
- api/Posts
  - Validates JWT and role.
  - Implements list (pagination + filters), show, create (slug generation), update (stable slug), soft delete.
- api/Pixabay
  - Validates JWT.
  - Proxies to Pixabay’s API with PIXABAY_API_KEY from .env and returns simplified payload.
- Blog
  - Public list and detail rendering.
- Dashboard
  - Renders HTML views and bootstraps JS which calls APIs with JWT.

Client (assets/js/app.js)
- Stores/fetches JWT in/from localStorage.
- Provides apiFetch wrapper attaching Authorization header.
- Admin list: searches, paginates, edit/delete actions.
- Admin form: create/edit logic and Pixabay picker modal.
- Renders cover preview for images/videos.

Setup Instructions (Step-by-step)
1) Create .env
- Copy .env.example to .env and fill:
  - BASE_URL, DB_HOST, DB_NAME, DB_USER, DB_PASS
  - JWT_SECRET (long random)
  - APP_KEY (random string for CI encryption_key)
  - PIXABAY_API_KEY (from https://pixabay.com/api/docs/)
  - PAGINATION_PER_PAGE (default 10)

2) Create the database and seed it
- Open phpMyAdmin.
- Run the SQL at sql/schema_seed.sql
  - Creates database blog_ci, tables, and seeds:
    - Admin: admin@example.com / Admin@123
    - Users:
      - user1@example.com / User@123
      - user2@example.com / User@123
  - Password hashes generated via bcrypt.

3) Run the app locally
- From project root:
  - php -S localhost:8080 -t .
- Open BASE_URL in browser (e.g., http://localhost:8080/)
- Login at /index.php/login (form will call /index.php/api/login).
- After login, you will be redirected to /index.php/dashboard.

Notes on URL format:
- CodeIgniter index_page is set to 'index.php', so URLs include /index.php/ unless you configure Apache rewrite. This is acceptable and explicit.

Required for Pixabay Integration
- Provide a valid API key (PIXABAY_API_KEY) in the .env file.
- Ensure outbound HTTPS is allowed from the server (php_curl enabled recommended).
- The proxy prevents exposing the key to the frontend; do not put the key in JS.

Assumptions and Known Limitations
- UI is minimal and focused on functionality.
- Token refresh is not implemented (optional bonus in the task).
- Rich text body accepts a small safe subset of HTML tags.
- No file uploads; posts reference external media URLs only.
- Intelephense (IDE analyzer) may show undefined hints for CI magic globals; runtime is correct.

Testing Checklist (What the reviewer might ask)
- JWT claim contents: sub, role, exp (verify via jwt.io by copying a token string)
- Role enforcement: as user, attempt to edit another user’s post -> 403.
- Soft delete: delete a post -> status changes to deleted; public list excludes it.
- Slug collision handling: create two posts with same title -> second post gets -1 suffix.
- Pagination: dashboard and public list show 10 per page by default and proper next/prev.
- Pixabay: search from form, select media, preview shown, “Media via Pixabay” appears on public page.

Troubleshooting
- 500 errors on API calls: check JWT_SECRET and APP_KEY are set; verify DB connection in .env.
- 401/403: ensure Authorization: Bearer header is present; JWT not expired.
- cURL errors on Pixabay: ensure php_curl is enabled or allow_url_fopen; check PIXABAY_API_KEY.
- Base URL issues: ensure BASE_URL in .env ends with slash.

Credentials for Demo
- Admin:
  - Email: admin@example.com
  - Password: Admin@123
- User 1:
  - Email: user1@example.com
  - Password: User@123
- User 2:
  - Email: user2@example.com
  - Password: User@123

How to Export This Guide as PDF
- Open docs/IMPLEMENTATION.md in VS Code, use Markdown preview and “Print to PDF”, or
- Open this file in a Markdown viewer and export as PDF via Print dialog.
