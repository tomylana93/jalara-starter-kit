#!/usr/bin/env bash
#
# Manual deployment to a VPS, from a published release tag.
#
#     ./deploy/deploy.sh              # tag terbaru
#     ./deploy/deploy.sh v1.2.0       # tag tertentu
#     ./deploy/deploy.sh --dry-run    # preflight + ringkasan, tanpa mengubah apa pun
#
# Shape of a run:
#
#   PREFLIGHT       resolve tag, confirm its GitHub Release, compare .env keys,
#                   read production VITE_* — nothing is modified yet
#   PREPARE         build assets from the tag, unpack the tag into a new release
#                   directory, install dependencies — the live site is untouched
#   WINDOW          down, dump, migrate, sync authorization, flip, up
#   VERIFY          health check, automatic rollback on failure
#   FINISH          restart workers, prune old releases
#
# Everything slow happens before the maintenance window, so the window contains
# only the steps that genuinely cannot overlap two versions.
#
# Run from the project root. Requires deploy/config.sh; see config.example.sh.
set -euo pipefail

. "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

TAG=""
ASSUME_YES=0
SKIP_ENV_CHECK=0
DRY_RUN=0

while (($# > 0)); do
    case "$1" in
        --yes | -y) ASSUME_YES=1 ;;
        --skip-env-check) SKIP_ENV_CHECK=1 ;;
        --dry-run) DRY_RUN=1 ;;
        -h | --help)
            sed -n '2,20p' "${BASH_SOURCE[0]}" | sed 's|^#\s\?||'
            exit 0
            ;;
        -*) die "Opsi tidak dikenal: $1" ;;
        *)
            [[ -n "${TAG}" ]] && die "Tag sudah diberikan: ${TAG}"
            TAG="$1"
            ;;
    esac
    shift
done

load_config
require_local_commands git ssh rsync curl gh pnpm composer php

MAINTENANCE_ON=0
SYMLINK_SWITCHED=0
RELEASE_PATH=""
PREVIOUS_RELEASE=""
VITE_ENV_FILE=""

cleanup() {
    [[ -n "${VITE_ENV_FILE}" ]] && rm -f "${VITE_ENV_FILE}"
    close_ssh_control
}

# Failure handling. Two things must be true no matter how a run dies: the site is
# not left behind a maintenance page, and it is not left pointing at a release
# that never came up. Both are cheap; not doing them costs an outage that lasts
# until somebody notices.
on_failure() {
    local exit_code=$?
    trap - ERR EXIT

    if ((SYMLINK_SWITCHED)) && [[ -n "${PREVIOUS_RELEASE}" ]]; then
        warn "Mengembalikan current ke ${PREVIOUS_RELEASE}"
        switch_current "${APP_ROOT}/releases/${PREVIOUS_RELEASE}" || true
    fi

    if ((MAINTENANCE_ON)); then
        warn "Mematikan maintenance mode"
        remote_artisan "${APP_ROOT}/current" up || true
    fi

    cleanup
    exit "${exit_code}"
}

# Replacing a symlink is not atomic; creating a new one and renaming it over the
# old one is. Without this there is a moment where `current` does not exist, and
# a request arriving in it gets a 404 from nginx rather than either version.
switch_current() {
    local target="$1"
    remote "
ln -sfn $(printf '%q' "${target}") $(printf '%q' "${APP_ROOT}/current.tmp")
mv -Tf $(printf '%q' "${APP_ROOT}/current.tmp") $(printf '%q' "${APP_ROOT}/current")
"
}

trap on_failure ERR
trap cleanup EXIT

# ---------------------------------------------------------------------------
# PREFLIGHT
# ---------------------------------------------------------------------------

step "Preflight"

git -C "${PROJECT_DIR}" fetch --tags --prune --quiet

if [[ -z "${TAG}" ]]; then
    # Sorted by version, not by creation date: a tag pushed late for an older
    # line must not be mistaken for the newest release.
    TAG="$(git -C "${PROJECT_DIR}" tag --sort=-v:refname | head -n 1)"
    [[ -n "${TAG}" ]] || die "Tidak ada tag sama sekali di repository ini."
fi

git -C "${PROJECT_DIR}" rev-parse -q --verify "refs/tags/${TAG}" >/dev/null ||
    die "Tag tidak ditemukan: ${TAG}"

# A tag alone does not mean a release happened. Release Please can tag and then
# fail to publish, which leaves a tag nobody released — deploying it would ship a
# version the changelog and version.json disagree about.
release_state="$(gh release view "${TAG}" --repo "${REPO_SLUG}" --json isDraft,publishedAt 2>/dev/null || true)"

if [[ -z "${release_state}" ]]; then
    die "Tag ${TAG} tidak punya GitHub Release. Rilisnya belum dipublikasikan."
fi

if [[ "${release_state}" == *'"isDraft":true'* ]]; then
    die "GitHub Release untuk ${TAG} masih draft."
fi

release_published="$(printf '%s' "${release_state}" | sed -n 's/.*"publishedAt":"\([^"]*\)".*/\1/p')"

PREVIOUS_RELEASE="$(remote_current_release)"
previous_tag=""
if [[ -n "${PREVIOUS_RELEASE}" ]]; then
    previous_tag="$(release_tag_of "${PREVIOUS_RELEASE}")"
fi

# --- .env drift ------------------------------------------------------------
# A release that introduces a config key the server does not have does not fail
# loudly. Under `config:cache`, `env()` outside a config file returns null, so
# the feature simply goes quiet — this is how a missed BACKUP_SCHEDULE_TIME
# unschedules every backup without a single error. Key names only are compared
# and printed; values never leave the server.

missing_env_keys=""
if ((SKIP_ENV_CHECK == 0)); then
    tag_env_keys="$(git -C "${PROJECT_DIR}" show "${TAG}:.env.example" |
        sed -n 's/^\([A-Z][A-Z0-9_]*\)=.*/\1/p' | sort -u)"
    server_env_keys="$(remote "sed -n 's/^\([A-Z][A-Z0-9_]*\)=.*/\1/p' $(printf '%q' "${APP_ROOT}/shared/.env") | sort -u")"
    missing_env_keys="$(comm -23 <(printf '%s\n' "${tag_env_keys}") <(printf '%s\n' "${server_env_keys}") || true)"
fi

# --- migrations in this release -------------------------------------------
# Compared against the migrations of the release currently deployed, not against
# the database. It is the number the confirmation prompt needs: after an
# automatic rollback the code goes back and the schema does not, so this is the
# one line worth reading before saying yes.

new_migrations=""
if [[ -n "${PREVIOUS_RELEASE}" ]]; then
    tag_migrations="$(git -C "${PROJECT_DIR}" ls-tree --name-only "${TAG}" database/migrations/ | xargs -r -n1 basename | sort)"
    live_migrations="$(remote "ls -1 $(printf '%q' "${APP_ROOT}/current/database/migrations") 2>/dev/null | sort" || true)"
    new_migrations="$(comm -23 <(printf '%s\n' "${tag_migrations}") <(printf '%s\n' "${live_migrations}") || true)"
fi

commit_count="?"
if [[ -n "${previous_tag}" ]] && git -C "${PROJECT_DIR}" rev-parse -q --verify "refs/tags/${previous_tag}" >/dev/null; then
    commit_count="$(git -C "${PROJECT_DIR}" rev-list --count --no-merges "${previous_tag}..${TAG}")"
fi

# --- production VITE_* -----------------------------------------------------
# VITE_APP_NAME and VITE_REVERB_* are inlined into the bundle at build time
# (resources/js/app.ts). Building from a local .env would bake localhost:8080
# into production's websocket client, which fails silently at runtime. Vite reads
# .env files first and then lets prefixed process.env entries override them, so
# exporting these into the build is enough.
#
# Values are resolved on the server rather than grepped out of the file, because
# .env entries interpolate: VITE_APP_NAME is "${APP_NAME}" by default, and a
# plain grep would carry that reference into a build where APP_NAME does not
# exist, silently producing an empty application name. Sourcing there and
# printing with %q also keeps values containing spaces intact.

VITE_ENV_FILE="$(mktemp "${TMPDIR:-/tmp}/jalara-vite-env.XXXXXX")"
chmod 600 "${VITE_ENV_FILE}"

remote "
set -a
. $(printf '%q' "${APP_ROOT}/shared/.env")
set +a
for key in \$(compgen -v | grep '^VITE_' || true); do
    printf '%s=%q\n' \"\${key}\" \"\${!key}\"
done
" >"${VITE_ENV_FILE}"

vite_key_count="$(grep -c . "${VITE_ENV_FILE}" || true)"
((vite_key_count > 0)) || warn "shared/.env tidak punya satu pun key VITE_* — bundle akan memakai nilai default."

# ---------------------------------------------------------------------------
# SUMMARY
# ---------------------------------------------------------------------------

migration_summary="0"
if [[ -n "${new_migrations}" ]]; then
    migration_summary="$(printf '%s\n' "${new_migrations}" | grep -c . || true)"
fi

cat <<SUMMARY

  Target          : ${SSH_HOST}  ->  ${APP_ROOT}
  Rilis saat ini  : ${PREVIOUS_RELEASE:-(belum ada, ini deploy pertama)}
  Akan deploy     : ${TAG}  (GitHub Release published ${release_published})
  Perubahan       : ${commit_count} commit, ${migration_summary} migrasi baru
  Aset            : dibangun dari ${TAG} di ${BUILD_WORKTREE}
  VITE_*          : ${vite_key_count} key diambil dari shared/.env
  Downtime        : ya, jendela maintenance singkat saat migrate
SUMMARY

if [[ -n "${new_migrations}" ]]; then
    printf '\n  Migrasi baru:\n'
    printf '%s\n' "${new_migrations}" | sed 's/^/    - /'
    printf '\n  %sRollback otomatis mengembalikan kode, TIDAK membatalkan migrasi.%s\n' "${C_YELLOW}" "${C_RESET}"
fi

if [[ -n "${missing_env_keys}" ]]; then
    printf '\n  %sKey .env yang ada di %s tapi tidak ada di shared/.env:%s\n' "${C_RED}" "${TAG}" "${C_RESET}"
    printf '%s\n' "${missing_env_keys}" | sed 's/^/    - /'
    printf '\n'
    die "Lengkapi shared/.env di server, atau jalankan ulang dengan --skip-env-check kalau key di atas memang sengaja tidak dipakai."
fi

if ((DRY_RUN)); then
    step "--dry-run: berhenti di sini, tidak ada yang diubah."
    exit 0
fi

if ((ASSUME_YES == 0)); then
    if ! confirm "Ketik 'yes' untuk lanjut:"; then
        die "Dibatalkan."
    fi
fi

# ---------------------------------------------------------------------------
# PREPARE — nothing here touches the running site
# ---------------------------------------------------------------------------

step "Menyiapkan build worktree pada ${TAG}"

# A second checkout sharing this repository's .git. Persistent, so node_modules
# and Vite's cache survive between deployments, and detached from your working
# tree, so a deployment never interferes with work in progress.
if [[ ! -d "${BUILD_WORKTREE}/.git" ]] && [[ ! -f "${BUILD_WORKTREE}/.git" ]]; then
    git -C "${PROJECT_DIR}" worktree add --detach "${BUILD_WORKTREE}" "${TAG}" >/dev/null
else
    git -C "${BUILD_WORKTREE}" checkout --detach --force "${TAG}" >/dev/null 2>&1
    # Leaves the expensive, reusable directories in place. Anything else the
    # previous build wrote is removed so a stale generated file cannot survive
    # into this bundle.
    git -C "${BUILD_WORKTREE}" clean -fdq -e node_modules -e vendor -e .env -e database/database.sqlite
fi

# The Wayfinder Vite plugin boots Artisan during the build to generate the route
# modules the Vue sources import, so the worktree needs a bootable application.
# This environment only ever generates TypeScript; its database is a throwaway.
if [[ ! -f "${BUILD_WORKTREE}/.env" ]]; then
    note "menyiapkan environment build sekali jalan"
    sed \
        -e "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        "${BUILD_WORKTREE}/.env.example" >"${BUILD_WORKTREE}/.env"
    touch "${BUILD_WORKTREE}/database/database.sqlite"
    (cd "${BUILD_WORKTREE}" && php artisan migrate --force --graceful --no-interaction >/dev/null)
fi

(cd "${BUILD_WORKTREE}" && composer install --no-interaction --prefer-dist --quiet)

step "Membangun aset dari ${TAG}"

(
    cd "${BUILD_WORKTREE}"
    set -a
    # shellcheck source=/dev/null
    . "${VITE_ENV_FILE}"
    set +a
    pnpm install --frozen-lockfile --silent
    pnpm run build
)

[[ -f "${BUILD_WORKTREE}/public/build/manifest.json" ]] ||
    die "Build selesai tapi public/build/manifest.json tidak ada."

RELEASE_NAME="$(date -u +%Y%m%d%H%M%S)-${TAG}"
RELEASE_PATH="${APP_ROOT}/releases/${RELEASE_NAME}"

step "Mengambil ${TAG} di server"

remote "
repo=$(printf '%q' "${APP_ROOT}/shared/repo")

if [[ ! -d \"\${repo}\" ]]; then
    git clone --bare $(printf '%q' "${REPO_URL}") \"\${repo}\" >/dev/null
fi

# A bare clone has no fetch refspec, so tags created after it would never arrive.
git -C \"\${repo}\" config remote.origin.fetch '+refs/heads/*:refs/heads/*'
git -C \"\${repo}\" fetch --tags --prune --quiet origin

git -C \"\${repo}\" rev-parse -q --verify $(printf '%q' "refs/tags/${TAG}") >/dev/null

mkdir -p $(printf '%q' "${RELEASE_PATH}")
git -C \"\${repo}\" archive $(printf '%q' "${TAG}") | tar -x -C $(printf '%q' "${RELEASE_PATH}")
"

step "Menyambungkan shared state"

# storage/ and .env belong to the installation, not to a release. Everything else
# in a release directory is disposable, which is what makes rollback a symlink
# flip and pruning safe.
remote "
rm -rf $(printf '%q' "${RELEASE_PATH}/storage")
ln -s $(printf '%q' "${APP_ROOT}/shared/storage") $(printf '%q' "${RELEASE_PATH}/storage")
ln -sf $(printf '%q' "${APP_ROOT}/shared/.env") $(printf '%q' "${RELEASE_PATH}/.env")
"

step "composer install (--no-dev)"

remote "cd $(printf '%q' "${RELEASE_PATH}") && ${REMOTE_COMPOSER} install --no-dev --optimize-autoloader --no-interaction --prefer-dist --quiet"

step "pnpm install (--prod)"

# Only puppeteer is genuinely needed at runtime, for Browsershot's PDF rendering.
# The rest of the production dependency set comes along because maintaining a
# second package.json would be a duplicate source of truth that goes stale
# quietly. The shared store means the extra cost is hardlinks, not copies.
remote "cd $(printf '%q' "${RELEASE_PATH}") && ${REMOTE_PNPM} install --prod --frozen-lockfile --silent --store-dir $(printf '%q' "${APP_ROOT}/shared/pnpm-store")"

step "Mengirim aset"

mapfile -t ssh_opts < <(ssh_options)
remote "mkdir -p $(printf '%q' "${RELEASE_PATH}/public/build")"
rsync -az --delete \
    -e "ssh ${ssh_opts[*]}" \
    "${BUILD_WORKTREE}/public/build/" \
    "${SSH_HOST}:${RELEASE_PATH}/public/build/"

step "Menyiapkan cache rilis"

# Caching config/routes/views/events now, outside the window, means the first
# request after the flip is not the one paying for it. The caches live in the
# release's own bootstrap/cache, so this cannot disturb the running version.
remote_artisan "${RELEASE_PATH}" optimize
remote_artisan "${RELEASE_PATH}" storage:link --force --no-interaction

# ---------------------------------------------------------------------------
# WINDOW — from here on the site is affected
# ---------------------------------------------------------------------------

if [[ -n "${PREVIOUS_RELEASE}" ]]; then
    step "Maintenance mode"
    # Issued from the running release, whose vendor/ is known good. The marker
    # file lands in shared storage, so it applies across releases.
    remote_artisan "${APP_ROOT}/current" down
    MAINTENANCE_ON=1

    step "Dump database sebelum migrate"
    # Deliberately not `backup:run`: that archive would count against
    # BACKUP_MAX_STORAGE_MB and push real retention out, so deploying often would
    # quietly delete backup history. This dump is a safety net for one operation.
    dump_name="${RELEASE_NAME}.dump"
    remote "
cd $(printf '%q' "${APP_ROOT}")
set -a
. ./shared/.env
set +a
PGPASSWORD=\"\${DB_PASSWORD:-}\" pg_dump \
    -h \"\${DB_HOST:-127.0.0.1}\" \
    -p \"\${DB_PORT:-5432}\" \
    -U \"\${DB_USERNAME}\" \
    -d \"\${DB_DATABASE}\" \
    -Fc -f $(printf '%q' "${APP_ROOT}/shared/deploy-backups/${dump_name}")
"
    note "shared/deploy-backups/${dump_name}"
fi

step "Migrasi database"
remote_artisan "${RELEASE_PATH}" migrate --force --no-interaction

step "Sinkronisasi roles & permissions"
# Deliberately not driven from a migration, because it prunes catalog drift.
# Skipping it leaves permissions introduced by this release unassigned to any
# role, so the feature exists and nobody can reach it.
remote_artisan "${RELEASE_PATH}" auth:sync-authorization --no-interaction

step "Mengalihkan current -> ${RELEASE_NAME}"
switch_current "${RELEASE_PATH}"
SYMLINK_SWITCHED=1

if ((MAINTENANCE_ON)); then
    remote_artisan "${APP_ROOT}/current" up
    MAINTENANCE_ON=0
fi

# ---------------------------------------------------------------------------
# VERIFY
# ---------------------------------------------------------------------------

step "Health check ${APP_URL}/up"

health_ok=0
for attempt in 1 2 3 4 5; do
    if curl -fsS -o /dev/null --max-time 10 "${APP_URL}/up"; then
        health_ok=1
        break
    fi
    note "percobaan ${attempt} gagal, menunggu 3 detik"
    sleep 3
done

if ((health_ok == 0)); then
    trap - ERR

    if [[ -n "${PREVIOUS_RELEASE}" ]]; then
        warn "Health check gagal. Mengembalikan ke ${PREVIOUS_RELEASE}."
        switch_current "${APP_ROOT}/releases/${PREVIOUS_RELEASE}"
        remote_artisan "${APP_ROOT}/current" queue:restart || true
        remote_artisan "${APP_ROOT}/current" reverb:restart || true

        cat >&2 <<ROLLBACK

  ${C_YELLOW}Rollback selesai, tapi baca ini:${C_RESET}

    Yang dikembalikan : kode -> ${PREVIOUS_RELEASE}
    Yang TIDAK mundur : skema database. Migrasi ${TAG} sudah dijalankan.
    Dump sebelum migrate: ${APP_ROOT}/shared/deploy-backups/${RELEASE_NAME}.dump
    Rilis yang gagal   : ${RELEASE_PATH} (dibiarkan untuk diperiksa)

  Kalau ${TAG} membawa migrasi destruktif, ${PREVIOUS_RELEASE} sekarang berjalan
  di atas skema yang tidak dikenalnya. Periksa aplikasinya sebelum menganggap
  situasi ini pulih.

ROLLBACK
    else
        warn "Health check gagal dan tidak ada rilis sebelumnya untuk dikembalikan."
        warn "Rilis ada di ${RELEASE_PATH}; periksa log di ${APP_ROOT}/shared/storage/logs."
    fi

    close_ssh_control
    exit 1
fi

note "OK"

# ---------------------------------------------------------------------------
# FINISH
# ---------------------------------------------------------------------------

step "Merestart background process"

# Graceful signals, not process control: each worker finishes its current job and
# exits, and Supervisor starts it again from `current`, which now resolves to the
# new release. No sudo, no interrupted job.
remote_artisan "${APP_ROOT}/current" queue:restart
remote_artisan "${APP_ROOT}/current" reverb:restart
note "queue:restart, reverb:restart"

step "Membersihkan rilis lama (menyisakan ${KEEP_RELEASES})"

# Last, and only now. A worker still running out of a release directory that is
# deleted underneath it dies in a way that is hard to diagnose, so this waits
# until the restart signals above have been issued.
removed="$(remote "
cd $(printf '%q' "${APP_ROOT}/releases")
keep=$(printf '%q' "${KEEP_RELEASES}")
current_name=\"\$(basename \"\$(readlink -f $(printf '%q' "${APP_ROOT}/current"))\")\"

ls -1 | sort | head -n -\"\${keep}\" | while read -r name; do
    [[ -z \"\${name}\" ]] && continue
    [[ \"\${name}\" == \"\${current_name}\" ]] && continue
    rm -rf -- \"\${name}\"
    printf '%s\n' \"\${name}\"
done
")"

if [[ -n "${removed}" ]]; then
    printf '%s\n' "${removed}" | sed 's/^/    - /'
else
    note "tidak ada yang dihapus"
fi

remote "
cd $(printf '%q' "${APP_ROOT}/shared/deploy-backups")
ls -1t | tail -n +$((KEEP_DEPLOY_DUMPS + 1)) | while read -r name; do
    [[ -n \"\${name}\" ]] && rm -f -- \"\${name}\"
done
" || true

step "Selesai: ${TAG} aktif di ${APP_URL}"
