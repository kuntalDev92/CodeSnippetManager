# 📝 Code Snippet Manager

A modern, full-featured **Code Snippet Manager** designed for developers and teams who value efficiency. Built with **Core PHP (OOP)**, **MySQL**, **Bootstrap 5**, and **AJAX** — it provides a centralized workspace to store, organize, search, and collaborate on reusable code across projects.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

---

## 🚀 Features

### Core Features
- ✅ **CRUD Operations** — Create, Read, Update, Delete code snippets via AJAX
- ✅ **Syntax Highlighting** — Highlight.js with 18+ language support
- ✅ **One-Click Copy** — Clipboard copy with usage tracking
- ✅ **Categories** — Color-coded categories with management UI
- ✅ **Tags** — Flexible tagging system (any user can create)
- ✅ **Search** — Full-text search with real-time autocomplete (Ctrl+K)
- ✅ **Favorites** — Bookmark your most-used snippets
- ✅ **Team Sharing** — Share snippets with view/edit permissions
- ✅ **Save Shared Snippets** — Clone shared snippets to your own collection
- ✅ **Version History** — Track changes with snapshots & restore
- ✅ **Pin Snippets** — Pin important snippets to the top

### User Management & Roles
- ✅ **Authentication** — Secure login/registration with bcrypt
- ✅ **Admin Role** — Full system control (see below)
- ✅ **Member Role** — Standard CRUD access
- ✅ **Profile Management** — Update profile & change password
- ✅ **Session Security** — CSRF, httponly cookies, session regeneration

### Admin Panel Features
- ✅ **System Dashboard** — Total users, snippets, categories, tags, shares
- ✅ **User Management** — View all users with stats
- ✅ **Activate / Deactivate** — Enable or disable user accounts
- ✅ **Role Management** — Promote members to admin or demote
- ✅ **Activity Log** — System-wide audit trail (all users)
- ✅ **Admin Badge** — Visual indicator in navbar

### Admin vs Member

| Feature | Admin | Member |
|---------|-------|--------|
| Create/Edit/Delete own snippets | ✅ | ✅ |
| Search, filter, favorites | ✅ | ✅ |
| Share snippets with team | ✅ | ✅ |
| Create/Edit/Delete categories | ✅ | ✅ |
| Create/Edit/Delete tags | ✅ | ✅ |
| **Admin Panel** | ✅ | ❌ |
| **View all users & stats** | ✅ | ❌ |
| **Activate/Deactivate users** | ✅ | ❌ |
| **Change user roles** | ✅ | ❌ |
| **View system-wide activity log** | ✅ | ❌ |

### UI/UX
- ✅ **Fully Responsive** — Works on desktop, tablet, and mobile
- ✅ **Dark Theme** — Professional dark interface
- ✅ **AJAX Powered** — Smooth, no-reload interactions
- ✅ **Toast Notifications** — Non-intrusive feedback
- ✅ **Keyboard Shortcuts** — Ctrl+K for quick search
- ✅ **Dashboard** — Stats, charts, activity timeline
- ✅ **Pagination** — Efficient paginated browsing

### Technical
- ✅ **OOP Architecture** — Singleton, clean class structure
- ✅ **PDO Prepared Statements** — SQL injection prevention
- ✅ **CSRF Protection** — Token-based validation
- ✅ **XSS Prevention** — Output escaping throughout
- ✅ **Auto-detect URL** — Plug-and-play installation
- ✅ **Activity Logging** — Audit trail for all actions
- ✅ **Timezone Sync** — PHP & MySQL timezone alignment

---

## 📁 Project Structure

```
snippet-manager/
├── config/
│   ├── app.php              # App settings, autoloader, timezone
│   └── database.php         # PDO Singleton connection
├── classes/
│   ├── Session.php          # Session & CSRF management
│   ├── Auth.php             # Login, register, logout, roles
│   ├── User.php             # User model (CRUD, search, stats)
│   ├── Snippet.php          # Snippet model (CRUD, versions, clone)
│   ├── Category.php         # Category model (user-filtered counts)
│   ├── Tag.php              # Tag model (user-filtered counts)
│   ├── Favorite.php         # Favorites toggle
│   ├── Share.php            # Team sharing with permissions
│   └── ActivityLog.php      # Audit trail
├── helpers/
│   └── functions.php        # Utility functions
├── includes/
│   ├── header.php           # Navbar, search, admin badge
│   └── footer.php           # Scripts, config injection
├── ajax/
│   └── handler.php          # 40+ AJAX action handlers
├── assets/
│   ├── css/style.css        # Responsive styles
│   └── js/app.js            # AJAX, copy, search, forms
├── uploads/                 # User uploads
├── index.php                # Snippet listing with filters
├── login.php                # Login page
├── register.php             # Registration page
├── create.php               # Create snippet
├── edit.php                 # Edit snippet
├── view.php                 # View snippet (full code)
├── dashboard.php            # User dashboard
├── categories.php           # Category management
├── tags.php                 # Tag management
├── favorites.php            # Favorites redirect
├── shared.php               # Shared with me
├── profile.php              # Profile & password
├── admin.php                # ⭐ Admin panel (users, stats, logs)
├── logout.php               # Logout handler
├── install.php              # ⭐ Web installer
├── database.sql             # Schema (tables only)
├── .htaccess                # Security & caching
└── README.md
```

---

## ⚡ Installation

### Requirements
- **PHP 8.1+** with PDO MySQL
- **MySQL 5.7+** or MariaDB 10.3+
- **Apache** with mod_rewrite (or Nginx)

### Quick Start

1. **Extract** to your web server:
   ```bash
   # XAMPP: C:/xampp/htdocs/snippet-manager/
   # WAMP: C:/wamp64/www/snippet-manager/
   # Linux: /var/www/html/snippet-manager/
   ```

2. **Run the web installer:**
   ```
   http://localhost/snippet-manager/install.php
   ```
   The installer creates the database, tables, admin user, and default data.

3. **Configure database** (if needed): edit `config/database.php`

4. **Delete install.php** after setup!

5. **Login:**
   ```
   http://localhost/snippet-manager/login.php
   ```

### Manual Installation (Step-by-Step)

If you prefer to set things up manually instead of using `install.php`, follow these steps carefully:

---

#### Step 1 — Create the Database

Open **phpMyAdmin** (or MySQL CLI) and run:

```sql
CREATE DATABASE snippet_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

#### Step 2 — Import Tables

Import the `database.sql` file which creates all 9 tables:

**Option A — phpMyAdmin:**
1. Open phpMyAdmin → Click on `snippet_manager` database
2. Go to **Import** tab
3. Click **Choose File** → select `database.sql`
4. Click **Go**

**Option B — Command line:**
```bash
mysql -u root -p snippet_manager < database.sql
```

---

#### Step 3 — Generate a Password Hash

This is important! MySQL cannot hash passwords using bcrypt. You must use PHP to generate the hash first.

**Option A — Run this in your terminal:**
```bash
php -r "echo password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);"
```

**Option B — Create a temporary PHP file** called `hash.php`:
```php
<?php
// Open this file in browser to see the hash
echo password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```
Open `http://localhost/hash.php` in your browser → copy the entire output.

The output will look something like this (yours will be different):
```
$2y$12$LJ3m4ks9Fnh.VgO1hR8pMeXz7QZy5YvrKBwUn0cEQ7gTJpGXa6Yfa
```

> ⚠️ **Delete `hash.php` after use!** Never leave it on your server.

---

#### Step 4 — Create Admin User

Run this SQL in phpMyAdmin (SQL tab) — **replace the hash** with the one you generated above:

```sql
INSERT INTO users (username, email, password, full_name, role) VALUES (
    'admin',
    'admin@example.com',
    '$2y$12$PASTE_YOUR_HASH_FROM_STEP_3_HERE',
    'Administrator',
    'admin'
);
```

**Example with a real hash:**
```sql
INSERT INTO users (username, email, password, full_name, role) VALUES (
    'admin',
    'admin@example.com',
    '$2y$12$LJ3m4ks9Fnh.VgO1hR8pMeXz7QZy5YvrKBwUn0cEQ7gTJpGXa6Yfa',
    'Administrator',
    'admin'
);
```

> The `password` column stores the **bcrypt hash**, not the plain text password. You will login using the plain text password (`admin123` in this example).

---

#### Step 5 — (Optional) Create a Demo Member User

To test the member role, create a second user. Generate a hash for `member123` the same way as Step 3, then:

```sql
INSERT INTO users (username, email, password, full_name, role) VALUES (
    'demo',
    'demo@example.com',
    '$2y$12$PASTE_HASH_FOR_member123_HERE',
    'Demo User',
    'member'
);
```

---

#### Step 6 — Insert Default Categories

```sql
INSERT INTO categories (name, slug, description, color, sort_order, created_by) VALUES
('Database', 'database', 'MySQL, PDO, and database-related snippets', '#ef4444', 1, 1),
('Authentication', 'authentication', 'Login, registration, and auth snippets', '#f97316', 2, 1),
('File Handling', 'file-handling', 'File upload, download, and manipulation', '#eab308', 3, 1),
('API', 'api', 'REST API and cURL related snippets', '#22c55e', 4, 1),
('String Manipulation', 'string-manipulation', 'String processing and formatting', '#3b82f6', 5, 1),
('Array Operations', 'array-operations', 'Array sorting, filtering, and manipulation', '#8b5cf6', 6, 1),
('Email', 'email', 'Email sending and template snippets', '#ec4899', 7, 1),
('Security', 'security', 'Encryption, sanitization, and security', '#14b8a6', 8, 1),
('Utilities', 'utilities', 'Helper functions and utilities', '#6366f1', 9, 1),
('OOP Patterns', 'oop-patterns', 'Design patterns and OOP concepts', '#a855f7', 10, 1);
```

---

#### Step 7 — Insert Default Tags

```sql
INSERT INTO tags (name, slug, color) VALUES
('php', 'php', '#777BB4'),
('mysql', 'mysql', '#4479A1'),
('pdo', 'pdo', '#336791'),
('security', 'security', '#DC2626'),
('helper', 'helper', '#059669'),
('crud', 'crud', '#D97706'),
('ajax', 'ajax', '#2563EB'),
('oop', 'oop', '#7C3AED'),
('api', 'api', '#0891B2'),
('validation', 'validation', '#E11D48');
```

---

#### Step 8 — Configure the Application

**Edit `config/database.php`** — update these values to match your setup:
```php
'host'     => 'localhost',        // your database host
'port'     => 3306,               // your database port
'dbname'   => 'snippet_manager',  // database name from Step 1
'username' => 'root',             // your MySQL username
'password' => '',                 // your MySQL password
```

**Edit `config/app.php`** — set your timezone:
```php
define('APP_TIMEZONE', 'Asia/Kolkata');  // Change to your timezone
```
Full list of timezones: [php.net/timezones](https://www.php.net/manual/en/timezones.php)

---

#### Step 9 — Create Uploads Directory

```bash
mkdir uploads
chmod 755 uploads
```

On Windows (XAMPP), just create an `uploads` folder inside `snippet-manager/`.

---

#### Step 10 — Login and Start Using

Open your browser:
```
http://localhost/snippet-manager/login.php
```

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` (or whatever you set in Step 3) |
| Member | `demo` | `member123` (if you did Step 5) |

---

> 💡 **Tip:** The web installer (`install.php`) does all of Steps 1–9 automatically with a simple GUI form. Manual installation is recommended only if you need full control or your hosting restricts browser-based setup.

### Going to Production

1. Set `APP_DEBUG` to `false` in `config/app.php`
2. Set `APP_TIMEZONE` to your timezone in `config/app.php`
3. Update database credentials in `config/database.php`
4. Delete `install.php`
5. Ensure `uploads/` has write permissions (755)
6. Enable HTTPS and update `.htaccess` accordingly
7. Change default admin password

---

## 🔒 Security

| Feature | Implementation |
|---------|---------------|
| Password Hashing | bcrypt, cost 12 |
| SQL Injection | PDO prepared statements |
| XSS Prevention | htmlspecialchars on output |
| CSRF Protection | Token validation on all forms |
| Session Security | httponly, samesite, regeneration |
| Input Validation | Server-side on all inputs |
| File Protection | .htaccess denies config/class access |
| Admin Guard | Role-checked on all admin endpoints |

---

## 🛠️ AJAX API

All requests go through `ajax/handler.php?action=<action>`.

### Snippets
| Action | Method | Description |
|--------|--------|-------------|
| `create_snippet` | POST | Create new snippet |
| `update_snippet` | POST | Update existing snippet |
| `delete_snippet` | POST | Delete snippet |
| `quick_search` | GET | Autocomplete search |
| `copy_snippet` | POST | Track copy action |
| `toggle_pin` | POST | Pin/unpin snippet |
| `clone_snippet` | POST | Fork shared snippet |

### Favorites & Sharing
| Action | Method | Description |
|--------|--------|-------------|
| `toggle_favorite` | POST | Toggle bookmark |
| `share_snippet` | POST | Share with user |
| `remove_share` | POST | Remove share link |
| `search_users` | GET | Find users to share with |

### Categories & Tags
| Action | Method | Description |
|--------|--------|-------------|
| `create_category` | POST | Create category (duplicate check) |
| `create_tag` | POST | Create tag (duplicate check) |
| `update_tag` | POST | Edit tag |
| `delete_tag` | POST | Remove tag |

### Admin (admin role required)
| Action | Method | Description |
|--------|--------|-------------|
| `admin_toggle_role` | POST | Change user role |
| `admin_toggle_status` | POST | Activate/deactivate user |

### Versions
| Action | Method | Description |
|--------|--------|-------------|
| `get_versions` | GET | Version history |
| `restore_version` | POST | Restore old version |

---

## 📋 Database (9 Tables)

| Table | Purpose |
|-------|---------|
| `users` | Accounts, roles, auth |
| `snippets` | Code + full-text search index |
| `categories` | Snippet organization |
| `tags` | Flexible tagging |
| `snippet_tags` | Many-to-many pivot |
| `favorites` | User bookmarks |
| `shared_snippets` | Sharing with permissions |
| `snippet_versions` | Change history |
| `activity_log` | Audit trail |

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit changes
4. Push and open a Pull Request

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🙏 Credits

- [Bootstrap 5](https://getbootstrap.com/) — UI Framework
- [Highlight.js](https://highlightjs.org/) — Syntax highlighting
- [Bootstrap Icons](https://icons.getbootstrap.com/) — Icons
- [JetBrains Mono](https://www.jetbrains.com/lp/mono/) — Code font
- [Inter](https://rsms.me/inter/) — UI font
