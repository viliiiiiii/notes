# Notes HQ

Notes HQ is a feature-rich, plain PHP notes application with multi-user and multi-role support. It uses MySQL for persistence and is prepared for MinIO-backed attachment storage. The codebase intentionally avoids an MVC framework to keep things lightweight and easy to deploy on shared hosting or simple PHP stacks.

## Features

- **Multi-user authentication** with role-based access control (`admin`, `manager`, `editor`, `viewer`).
- **Dashboard** with pinned notes, archived view, search by title/body/tag, and sections for owned notes, notes shared with you, and team-wide visibility for managers/admins.
- **Rich note management** supporting tagging, pinning, archiving, and permanent deletion.
- **Private sharing** of notes with specific teammates. Shared notes are clearly marked with a badge on the dashboard.
- **Reply threads** on shared notes—recipients can respond without altering the original content.
- **Team administration** screen for admins to provision additional accounts.
- **MinIO attachment scaffold** (commented out) that shows how to hook in object storage when ready.
- **Sample data** with four pre-configured accounts for quick testing.

## Project structure

```
.
├── actions/             # Form action handlers (pin/archive/delete)
├── assets/              # Stylesheet
├── includes/            # Reusable helpers, auth, and data access logic
├── partials/            # Shared layout fragments
├── storage/             # Commented MinIO integration sample
├── config.php           # Database and MinIO settings
├── bootstrap.php        # Shared bootstrap for every entry point
├── database.sql         # Schema and seed data
├── index.php            # Dashboard
├── login.php            # Authentication screen
├── note.php             # Note detail, replies, sharing info
├── note_form.php        # Create/edit form
├── share_note.php       # Share management UI
├── user_admin.php       # Admin-only team management
└── README.md
```

## Getting started

1. **Install dependencies** (PHP 8.1+, MySQL 8, optional MinIO).
2. **Create the database** and import the schema:
   ```sql
   CREATE DATABASE notes_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON notes_app.* TO 'notes_user'@'%' IDENTIFIED BY 'notes_pass';
   FLUSH PRIVILEGES;
   ```
   Then load the schema and seed data:
   ```bash
   mysql -u notes_user -p notes_app < database.sql
   ```
3. **Configure environment** by editing `config.php` with the correct database host/user/password and, if applicable, MinIO credentials.
4. **Serve the app** with PHP’s built-in server:
   ```bash
   php -S 0.0.0.0:8000
   ```
   Navigate to `http://localhost:8000/login.php` and sign in with one of the test accounts below.

## Test accounts

| Role    | Username | Password    |
|---------|----------|-------------|
| Admin   | admin    | `Admin!234` |
| Manager | manager  | `Manager!234` |
| Editor  | editor   | `Editor!234` |
| Viewer  | viewer   | `Viewer!234` |

## MinIO integration (optional)

The application ships with commented-out attachment upload logic to keep local testing simple. When you are ready to store attachments:

1. Install the AWS SDK for PHP (`composer require aws/aws-sdk-php`).
2. Update `config.php` with your MinIO endpoint, credentials, and target bucket.
3. Uncomment the helper functions in `storage/minio_client.php` and the upload block in `note_form.php`.
4. Create a table (e.g., `note_attachments`) to persist attachment metadata and display download links.

## Security considerations

- All state-changing requests include CSRF protection.
- Passwords are hashed using PHP’s `password_hash` defaults (`bcrypt`).
- Role checks protect admin and owner-only areas; managers gain read-only visibility into team notes.
- Sharing is explicit: only the owner, the recipient, managers, and admins can open a shared note, and only designated recipients can reply.

## Next steps

- Wire up the attachment metadata table to surface uploads inside `note.php`.
- Add notifications (email or in-app) when new replies are posted.
- Extend the role model if you need more granular permissions.
