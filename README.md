# auth_cubite — Cubite SSO + storefront integration for Moodle

Plug Cubite (or any compatible storefront) into your Moodle so students never
see a Moodle login screen. The plugin handles seamless SSO, locks the catalog
down for non-admins, and makes Cubite the source of truth for user identity —
all without touching your Moodle data.

> **Status:** stable. Tested against Moodle 4.4 and 5.x.

## What it does

- **Seamless SSO.** Receives a short-lived JWT from Cubite, validates the
  HMAC-SHA256 signature, finds-or-creates the matching Moodle user, and calls
  `complete_user_login` — no second login screen, no forgotten-password loops.
- **Profile lock + sync.** Firstname, lastname, and email are read-only on
  Moodle for cubite-auth users. Every SSO refreshes the values from Cubite, so
  Cubite stays the source of truth for identity.
- **Storefront redirect.** `/login/index.php` and the signup page redirect
  unauthenticated users back to your Cubite tenant. Admins can bypass this
  with a per-tenant random token in the URL.
- **Catalog lockdown.** Disables `enrol_self` and `enrol_guest` site-wide on
  install. Non-admins hitting `/course/index.php`, `/course/search.php`, or
  `/course/index_category.php` are redirected to the storefront.
- **Navbar brand rewrite.** The Moodle "home" link points back to the Cubite
  storefront for non-admin users so SSO students return to the catalog they
  came from.
- **Self-configuring.** Storefront URL and admin bypass token arrive in the
  JWT and are saved into plugin config on the first SSO. Customers only enter
  the shared secret manually.

## Requirements

- Moodle 4.4 or later (tested on 5.x)
- A Cubite tenant with Moodle integration configured
- HTTPS on the Moodle instance (tokens travel in the URL)

## Installation

### Option A — Upload via Moodle admin (recommended)

1. Download `auth_cubite.zip` from the [latest release](../../releases/latest).
2. Log into Moodle as a site administrator.
3. **Site administration** → **Plugins** → **Install plugins** → upload the ZIP.
4. Click **Install plugin from the ZIP file** and follow the prompts.
5. **Site administration** → **Plugins** → **Authentication** →
   **Manage authentication**. Enable **Cubite SSO** (the eye icon).
6. Open **Settings** for **Cubite SSO** and paste the shared secret from your
   Cubite site settings (Moodle tab → SSO section). The storefront URL and
   admin bypass token populate themselves on the first successful SSO.

### Option B — Manual / Docker

```bash
# Extract the ZIP into your Moodle's auth/ directory
unzip auth_cubite.zip -d /path/to/moodle/auth/

# Run the upgrade
php /path/to/moodle/admin/cli/upgrade.php --non-interactive
```

For Docker setups (with the plugin folder mounted into the container):

```bash
docker cp cubite/ moodle-web:/var/www/html/auth/cubite
docker exec moodle-web php /var/www/html/admin/cli/upgrade.php --non-interactive
```

## Configuration

The plugin exposes three settings (Site administration → Plugins →
Authentication → Cubite SSO):

| Setting | What it is |
|--------|------------|
| **Shared secret** | HMAC-SHA256 key used to verify SSO tokens. Must match the value in Cubite. |
| **Storefront URL** | Auto-populated from the JWT on first SSO. The plugin redirects `/login` and `/signup` here. |
| **Admin bypass token** | Auto-populated from the JWT on first SSO. Admins log in directly via `/login/index.php?bypass={token}`. |

## How the SSO flow works

```
Student clicks "Go to Course" on Cubite
         │
         ▼
Cubite signs a JWT  { email, firstName, lastName, courseId,
                      siteUrl, adminBypassToken, exp: now + 60s }
         │
         ▼
Browser redirects to https://your-moodle/auth/cubite/login.php?token=…
         │
         ▼
auth_cubite validates the signature + expiration
         │
         ├── (first SSO) saves siteUrl + bypass token to plugin config
         │
         ▼
Find user by email → create if missing → refresh name/email on every SSO
         │
         ▼
complete_user_login() → student lands on the course page, logged in
```

## Admin direct login

Each Cubite tenant generates its own random `adminBypassToken`. Admins log in
to Moodle directly via:

```
https://your-moodle/login/index.php?bypass={your-tenant-token}
```

This URL is shown in your Cubite site settings (Moodle tab). The token is
per-tenant — leaking one tenant's URL doesn't expose any other tenant's
admin login.

## Uninstalling

1. **Site administration** → **Plugins** → **Authentication** →
   **Manage authentication**. Disable Cubite SSO.
2. **Site administration** → **Plugins** → **Plugins overview**. Find
   "Cubite SSO" → **Uninstall**.
3. The plugin doesn't leave artifacts behind; users created with `auth='cubite'`
   keep their Moodle accounts but won't be able to log in via SSO. Reassign
   them to another auth method if they need continued access.

## Building the ZIP locally

```bash
cd auth && zip -r ../auth_cubite.zip cubite -x "*.DS_Store"
```

(Or just `git tag v1.x.y && git push --tags` — GitHub Actions builds it.)

## License

GPL v3 or later — see [LICENSE](LICENSE). Required by Moodle's plugin policy.

Built and maintained by the [Cubite](https://cubite.io) team.
