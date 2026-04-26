# Practiq Production Routing

Practiq production app path:

- `/opt/practiq`

Caddy config path:

- `/root/Caddyfile`

## Public Domains

- `practiqapp.com` reverse-proxies to the Laravel nginx container.
- `www.practiqapp.com` reverse-proxies to the Laravel nginx container.
- `app.practiqapp.com` also reverse-proxies to the Laravel app.
- The Laravel nginx container is currently `healthcare-saas-nginx:80`.

## Previous Issue

`practiqapp.com` previously served an old static Caddy root instead of the Laravel app:

- Caddy static root: `/usr/share/caddy/index.html`
- Old copy included `Start Free Trial`, `Watch Demo`, and `Clinical Encounter Notes`.

The app itself was already deployed correctly at `/opt/practiq`; the issue was Caddy routing for the apex domain.

## Caddy Reload Note

If Caddy bind mounts are not behaving as expected, editing `/root/Caddyfile` may not update the active file inside the running Caddy container. Check the active container file and reload Caddy after changes:

```bash
docker exec caddy cat /etc/caddy/Caddyfile
docker exec caddy caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
docker exec caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
```

If the active container file differs from `/root/Caddyfile`, update the active mounted file carefully and reload Caddy. Do not make routing changes without validating the config first.

## Production Safety

Do not run `demo:reset` on production unless explicitly approved.

## Safe Verification

```bash
curl -L https://practiqapp.com/ | grep -E "A smarter practice system|Explore Demo|Join Early Access|Clinical Encounter|Start Free Trial"
```

Expected result:

- New copy appears: `A smarter practice system`, `Explore Demo`, `Join Early Access`.
- Old public homepage copy does not appear: `Clinical Encounter`, `Start Free Trial`.
