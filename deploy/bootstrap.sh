#!/usr/bin/env bash
#
# One-time preparation of a deployment target. Run this once per application per
# server, before the first `deploy/deploy.sh`. It is idempotent: everything it
# creates is skipped when it already exists, so re-running it after a change is
# safe and never touches a live release.
#
# The split matters. This script owns everything that outlives a single release —
# the directory skeleton, `shared/.env`, the deploy key, the pnpm store. The
# deployment script owns only what belongs to one release. Merging them would put
# a rarely-executed branch inside the script you run dozens of times.
#
# ---------------------------------------------------------------------------
# SERVER PREREQUISITES — install these before running this script
# ---------------------------------------------------------------------------
# Provisioning is deliberately out of scope: it runs once per server, needs root,
# and folding it in here would mean the deployment account keeps sudo forever.
# What the application needs present:
#
#   * PHP 8.5 with php-fpm, plus ext-intl (locale month names in exports),
#     ext-pgsql, ext-mbstring, ext-xml, ext-zip, ext-gd (Intervention Image).
#   * Composer 2.
#   * Node 22+ and pnpm 11. Required even though assets are built on your
#     machine: `puppeteer` is a runtime dependency, because Browsershot renders
#     the PDF exports through it.
#   * Chrome/Chromium. `pnpm-workspace.yaml` declines puppeteer's bundled
#     download on purpose, so the environment must supply one and
#     `LARAVEL_PDF_CHROME_PATH` in shared/.env must point at it.
#   * PostgreSQL, plus the `pg_dump` client binary — the deployment takes a
#     database dump before every migration.
#   * nginx. The vhost MUST resolve the release path, or PHP-FPM will keep
#     serving the previous release out of its realpath/OPcache after the symlink
#     flips and the deployment will look successful while running old code:
#
#         root /srv/jalara/current/public;
#         location ~ \.php$ {
#             fastcgi_pass unix:/run/php/php8.5-fpm.sock;
#             include fastcgi_params;
#             fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
#             fastcgi_param DOCUMENT_ROOT   $realpath_root;
#         }
#
#     A second server block reverse-proxies the Reverb websocket; see
#     docs/deployment.md.
#
#   * Supervisor, with THREE programs. Two are queue workers, and the second one
#     is not optional: RunBackupJob runs on the `database-long` connection, and a
#     worker that only consumes the default connection leaves every backup
#     sitting unclaimed with nothing failing to explain it.
#
#         [program:jalara-queue]
#         command=php /srv/jalara/current/artisan queue:work --queue=default --tries=3
#         [program:jalara-queue-long]
#         command=php /srv/jalara/current/artisan queue:work database-long --tries=1 --timeout=1800
#         [program:jalara-reverb]
#         command=php /srv/jalara/current/artisan reverb:start
#
#     All three point at `current`, never at a release path, so the graceful
#     restarts the deployment issues pick up the new release. Reverb also needs
#     `minfds=10000` under `[supervisord]`.
#
#   * Cron, for the scheduler (daily backups, image and model pruning):
#
#         * * * * * cd /srv/jalara/current && php artisan schedule:run >> /dev/null 2>&1
#
set -euo pipefail

. "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

load_config
require_local_commands ssh openssl

trap close_ssh_control EXIT

step "Menyiapkan ${APP_ROOT} di ${SSH_HOST}"

remote "
mkdir -p \
    $(printf '%q' "${APP_ROOT}/releases") \
    $(printf '%q' "${APP_ROOT}/shared/storage/app/public") \
    $(printf '%q' "${APP_ROOT}/shared/storage/app/private") \
    $(printf '%q' "${APP_ROOT}/shared/storage/framework/cache/data") \
    $(printf '%q' "${APP_ROOT}/shared/storage/framework/sessions") \
    $(printf '%q' "${APP_ROOT}/shared/storage/framework/views") \
    $(printf '%q' "${APP_ROOT}/shared/storage/logs") \
    $(printf '%q' "${APP_ROOT}/shared/storage/backups") \
    $(printf '%q' "${APP_ROOT}/shared/deploy-backups") \
    $(printf '%q' "${APP_ROOT}/shared/pnpm-store")
"
note "releases/, shared/storage/, shared/deploy-backups/, shared/pnpm-store/"

# ---------------------------------------------------------------------------
# shared/.env
# ---------------------------------------------------------------------------
# APP_KEY is generated here with openssl rather than `artisan key:generate`,
# because at this point no release exists and therefore no vendor/ to boot
# Artisan from. The format is exactly what Laravel writes: "base64:" followed by
# 32 random bytes, base64 encoded.

step "shared/.env"

if remote "test -f $(printf '%q' "${APP_ROOT}/shared/.env")"; then
    note "sudah ada, dibiarkan apa adanya"
else
    app_key="base64:$(openssl rand -base64 32)"

    env_contents="$(
        sed \
            -e 's|^APP_ENV=.*|APP_ENV=production|' \
            -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
            -e "s|^APP_KEY=.*|APP_KEY=${app_key}|" \
            -e 's|^APP_URL=.*|APP_URL='"${APP_URL}"'|' \
            -e 's|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|' \
            -e 's|^LOG_LEVEL=.*|LOG_LEVEL=warning|' \
            "${PROJECT_DIR}/.env.example"
    )"

    mapfile -t ssh_opts < <(ssh_options)
    printf '%s\n' "${env_contents}" |
        ssh "${ssh_opts[@]}" "${SSH_HOST}" \
            "cat > $(printf '%q' "${APP_ROOT}/shared/.env") && chmod 600 $(printf '%q' "${APP_ROOT}/shared/.env")"

    note "dibuat dari .env.example dengan APP_KEY baru, mode production"
    warn "shared/.env masih harus diisi: kredensial DB_*, MAIL_*, REVERB_*, LARAVEL_PDF_CHROME_PATH, SUPER_ADMIN_*"
fi

# ---------------------------------------------------------------------------
# Deploy key
# ---------------------------------------------------------------------------
# Read-only, one per repository. GitHub refuses the same public key as a deploy
# key on two repositories, so a private fork of this project generates its own
# here rather than reusing this one.

step "Deploy key"

if remote "test -f ~/.ssh/jalara_deploy_key"; then
    note "sudah ada"
else
    remote "
mkdir -p ~/.ssh && chmod 700 ~/.ssh
ssh-keygen -t ed25519 -N '' -C 'jalara-deploy' -f ~/.ssh/jalara_deploy_key >/dev/null
"
    note "dibuat"
fi

# `IdentitiesOnly` keeps the agent from offering other keys first, which GitHub
# answers by authenticating as whatever that key belongs to — a confusing failure
# when it happens to be an account without access to this repository.
remote "
if ! grep -q 'Host github.com-jalara' ~/.ssh/config 2>/dev/null; then
    cat >> ~/.ssh/config <<'SSHCONFIG'

Host github.com
    IdentityFile ~/.ssh/jalara_deploy_key
    IdentitiesOnly yes
SSHCONFIG
    chmod 600 ~/.ssh/config
fi
ssh-keyscan -H github.com >> ~/.ssh/known_hosts 2>/dev/null
sort -u -o ~/.ssh/known_hosts ~/.ssh/known_hosts
"

public_key="$(remote "cat ~/.ssh/jalara_deploy_key.pub")"

# ---------------------------------------------------------------------------
# Bare repository
# ---------------------------------------------------------------------------
# Cloning is attempted but not required to succeed: on a first run the deploy key
# is not registered on GitHub yet, and failing the whole bootstrap for that would
# be unhelpful. deploy/deploy.sh creates it on demand.

step "Bare repository"

if remote "test -d $(printf '%q' "${APP_ROOT}/shared/repo")"; then
    note "sudah ada"
elif remote "git clone --bare $(printf '%q' "${REPO_URL}") $(printf '%q' "${APP_ROOT}/shared/repo") >/dev/null 2>&1"; then
    note "berhasil di-clone"
else
    warn "clone gagal — kemungkinan besar deploy key belum didaftarkan di GitHub."
    warn "Daftarkan dulu (lihat di bawah), lalu jalankan ulang script ini."
fi

# ---------------------------------------------------------------------------

step "Selesai"

cat <<INSTRUCTIONS

Langkah berikutnya, dikerjakan manual:

  1. Daftarkan deploy key di GitHub
     ${REPO_SLUG} -> Settings -> Deploy keys -> Add deploy key
     JANGAN centang "Allow write access". Server tidak pernah perlu menulis.

${public_key}

  2. Isi ${APP_ROOT}/shared/.env
     Minimal: DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD,
     APP_URL, REVERB_*, MAIL_*, LARAVEL_PDF_CHROME_PATH, SUPER_ADMIN_*.

     Nilai VITE_* di file ini adalah yang dipakai saat membangun bundle di mesin
     kamu, jadi VITE_REVERB_* harus berisi alamat yang benar-benar dipakai
     browser — bukan localhost.

  3. Buat database PostgreSQL beserta user-nya sesuai kredensial di atas.

  4. Pasang vhost nginx, program Supervisor, dan entri cron scheduler.
     Snippet-nya ada di komentar paling atas file ini dan di docs/deployment.md.
     Bagian \$realpath_root pada vhost bukan opsional.

  5. Deploy pertama:

         ./deploy/deploy.sh

INSTRUCTIONS
