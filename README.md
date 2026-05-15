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
│   ├── User.php             # User model
│   ├── Snippet.php          # Snippet model (CRUD, versions, clone)
│   ├── Category.php         # Category model
│   ├── Tag.php              # Tag model
│   ├── Favorite.php         # Favorites toggle
│   ├── Share.php            # Team sharing
│   └── ActivityLog.php      # Audit trail
├── helpers/functions.php    # Utility functions
├── includes/
│   ├── header.php           # Navbar, search
│   └── footer.php           # Scripts, JS config
├── ajax/handler.php         # 40+ AJAX action handlers
├── assets/
│   ├── css/style.css        # Responsive styles
│   └── js/app.js            # AJAX, copy, search, forms
├── uploads/                 # User uploads
├── index.php                # Snippet listing
├── login.php / register.php # Authentication
├── create.php / edit.php    # Snippet forms
├── view.php                 # Snippet detail view
├── dashboard.php            # User dashboard
├── categories.php           # Category management
├── tags.php                 # Tag management
├── shared.php               # Shared with me
├── profile.php              # Profile & password
├── admin.php                # Admin panel
├── install.php              # Web installer
├── generate_hash.php        # Password hash helper
├── database.sql             # Complete database with all data
├── .htaccess                # Security & caching
└── README.md
```

---

## ⚡ Installation

### Requirements
- **PHP 8.1+** with PDO MySQL extension
- **MySQL 5.7+** or MariaDB 10.3+
- **Apache** with mod_rewrite (or Nginx)

---

### Method 1 — Auto Install (Recommended)

1. **Copy files** to your web server (`htdocs/snippet-manager/`)
2. **Open** `http://localhost/snippet-manager/install.php`
3. **Fill in** database credentials and admin password → Click **Install Now**
4. **Delete** `install.php` and `generate_hash.php` after setup
5. **Login** at `http://localhost/snippet-manager/login.php`

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Member | `demo` | `demo123` |

---

### Method 2 — Manual Install (Import SQL & Play)

The `database.sql` file contains **everything** — tables, users, categories, tags, and sample snippets:

| Included in database.sql | ✅ |
|--------------------------|---|
| 9 database tables | ✅ |
| Admin user (`admin`) | ✅ |
| Demo member user (`demo`) | ✅ |
| 10 categories | ✅ |
| 10 tags | ✅ |
| 3 sample snippets with tags & versions | ✅ |

#### Step 1 — Import database.sql

**Using phpMyAdmin:**
1. Open phpMyAdmin
2. Click **Import** (top menu)
3. Click **Choose File** → select `database.sql`
4. Click **Go**
5. Done — database, tables, and all data created ✅

**Using command line:**
```bash
mysql -u root -p < database.sql
```

#### Step 2 — Fix Password Hashes

The users are created but passwords need PHP to hash them. Pick **any one** option:

**Option A — Open `generate_hash.php` in browser (Easiest):**
```
http://localhost/snippet-manager/generate_hash.php
```
- It shows ready-to-run SQL with correct password hashes
- Click **Copy SQL** button
- Go to phpMyAdmin → `snippet_manager` database → **SQL** tab → Paste → **Go**
- Delete `generate_hash.php` after ✅

**Option B — Open `install.php` in browser:**
```
http://localhost/snippet-manager/install.php
```
- It detects existing users and automatically fixes their password hashes
- Delete `install.php` after ✅

**Option C — PHP command line:**
```bash
php -r "echo 'admin123: ' . password_hash('admin123', PASSWORD_BCRYPT, ['cost'=>12]) . PHP_EOL;"
php -r "echo 'demo123: ' . password_hash('demo123', PASSWORD_BCRYPT, ['cost'=>12]) . PHP_EOL;"
```
Copy the output hashes and run in phpMyAdmin:
```sql
UPDATE users SET password = 'PASTE_ADMIN_HASH' WHERE username = 'admin';
UPDATE users SET password = 'PASTE_DEMO_HASH' WHERE username = 'demo';
```

> **Why this step?** MySQL cannot generate bcrypt hashes. PHP must do it. Every bcrypt hash is unique — even for the same password — so it must be generated fresh on your server.

#### Step 3 — Configure

Edit `config/database.php` with your MySQL credentials:
```php
'host'     => 'localhost',
'dbname'   => 'snippet_manager',
'username' => 'root',
'password' => '',
```

Edit `config/app.php` for your timezone:
```php
define('APP_TIMEZONE', 'Asia/Kolkata');
```

#### Step 4 — Create uploads folder

Create an `uploads` folder inside `snippet-manager/`. On Windows just right-click → New Folder.

#### Step 5 — Login

```
http://localhost/snippet-manager/login.php
```

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Member | `demo` | `demo123` |

#### Step 6 — Clean up

Delete these files after successful login:
- `install.php`
- `generate_hash.php`

---

### Going Live

1. Set `APP_DEBUG` to `false` in `config/app.php`
2. Set `APP_TIMEZONE` to your timezone
3. Update database credentials in `config/database.php`
4. Delete `install.php` and `generate_hash.php`
5. Ensure `uploads/` has write permissions (755)
6. Enable HTTPS
7. Change default passwords

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

| Action | Method | Description |
|--------|--------|-------------|
| `create_snippet` | POST | Create new snippet |
| `update_snippet` | POST | Update snippet |
| `delete_snippet` | POST | Delete snippet |
| `quick_search` | GET | Autocomplete search |
| `copy_snippet` | POST | Track copy action |
| `toggle_pin` | POST | Pin/unpin snippet |
| `clone_snippet` | POST | Fork shared snippet |
| `toggle_favorite` | POST | Toggle bookmark |
| `share_snippet` | POST | Share with user |
| `remove_share` | POST | Remove share link |
| `search_users` | GET | Find users to share with |
| `create_category` | POST | Create category |
| `create_tag` | POST | Create tag |
| `update_tag` | POST | Edit tag |
| `delete_tag` | POST | Remove tag |
| `get_versions` | GET | Version history |
| `restore_version` | POST | Restore old version |
| `admin_toggle_role` | POST | Change user role (admin only) |
| `admin_toggle_status` | POST | Activate/deactivate user (admin only) |

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
