# 📝 Code Snippet Manager

A production-ready, feature-rich **Code Snippet Manager** built with **Core PHP (OOP)**, **MySQL**, **Bootstrap 5**, and **AJAX**. Store, organize, search, and share frequently used code snippets with your team.

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
