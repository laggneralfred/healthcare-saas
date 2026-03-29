# GitHub Actions Deployment

## Branch model

| Branch | On push | Effect |
|--------|---------|--------|
| `dev`  | Saved to GitHub only | No deployment — safe to push work-in-progress |
| `main` | Triggers `deploy.yml` | Auto-deploys to production server (137.184.33.220) |

## What the deploy workflow does

On every push to `main`, GitHub Actions:
1. SSHes into the production server
2. `git pull origin main` inside `/opt/practiq`
3. `php artisan optimize:clear` inside the running app container
4. `php artisan optimize` to rebuild config/route/view cache

This covers config and code changes. If you need a full Docker image
rebuild (e.g. new PHP dependencies), run `bash /opt/practiq/deploy.sh`
manually on the server instead.

## Required GitHub Secrets

Add these in **Settings → Secrets and variables → Actions**:

| Secret | Value |
|--------|-------|
| `SSH_HOST` | `137.184.33.220` |
| `SSH_USER` | `root` |
| `SSH_PRIVATE_KEY` | Private key whose public key is in `~/.ssh/authorized_keys` on the server |

## Merging dev → main (deploy to production)

```bash
# Finish work on dev
git add .
git commit -m "your changes"
git push origin dev

# Merge to main and deploy
git checkout main
git merge dev
git push origin main   # triggers auto-deploy

# Return to dev for next feature
git checkout dev
```
