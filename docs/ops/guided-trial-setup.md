# Guided Trial Setup

New `/register` trial practices receive editable starter defaults so the first admin session is not empty.

Starter defaults:

- One active practitioner linked to the registering user.
- Weekday working hours, Monday-Friday, 9:00-17:00.
- `Initial Visit`, 60 minutes.
- `Follow-up Visit`, 45 minutes.
- Starter `service_fees` linked as each appointment type's default fee.
- Active practitioner/treatment compatibility rows.

Prices are discipline-aware and stored in decimal dollars through `service_fees.default_price`.

The flow does not create fake patients, auto-book appointments, bypass HIPAA/BAA acknowledgement, or bypass AI disclaimer acknowledgement.

## Repair Command

For an empty trial practice, rerun the idempotent default seeder:

```bash
php artisan practiq:seed-starter-defaults {practice_id}
```

If a practice has multiple users, pass the user for the initial practitioner:

```bash
php artisan practiq:seed-starter-defaults {practice_id} --user=owner@example.com
```

The command reports what it created and does not overwrite existing practitioners, working hours, or appointment types.
