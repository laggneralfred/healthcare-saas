# Owner Admin Access

Practiq does not have a separate global role column in `users`.

Global owner/admin access is represented by:

- `users.practice_id = null`

Practice users keep a real `practice_id` and remain scoped to their own practice. Do not clear `practice_id` for normal clinic staff.

## Create Or Promote The Owner Admin

Use the safe command:

```bash
php artisan practiq:make-owner-admin owner@example.com
```

Optional flags:

```bash
php artisan practiq:make-owner-admin owner@example.com --name="Owner Admin"
php artisan practiq:make-owner-admin owner@example.com --password="temporary-password"
```

Behavior:

- Finds an existing user by email or creates one.
- Sets `practice_id` to `null`.
- Verifies the email address.
- Creates a temporary password only for newly created users.
- Does not change an existing user's password.

## After Running

1. Log in at `/admin/login`.
2. Open `/admin/signedup` to view trial signup records.
3. Use the practice switcher for practice-scoped admin pages.

This is an owner/admin access utility only. It is not an approval gate and does not change the self-serve trial signup flow.
