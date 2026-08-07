# Shared helpers for the deployment scripts. Sourced, never executed.
#
# Callers own `set -euo pipefail`; this file only defines functions and loads
# configuration, so sourcing it can never terminate the caller by surprise.

if [[ -n "${JALARA_DEPLOY_LIB_LOADED:-}" ]]; then
    return 0
fi
JALARA_DEPLOY_LIB_LOADED=1

DEPLOY_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${DEPLOY_DIR}/.." && pwd)"

if [[ -t 1 ]]; then
    C_RESET=$'\033[0m'
    C_BOLD=$'\033[1m'
    C_DIM=$'\033[2m'
    C_RED=$'\033[31m'
    C_GREEN=$'\033[32m'
    C_YELLOW=$'\033[33m'
else
    C_RESET='' C_BOLD='' C_DIM='' C_RED='' C_GREEN='' C_YELLOW=''
fi

log() { printf '%s\n' "$*"; }
step() { printf '\n%s==>%s %s%s%s\n' "${C_GREEN}" "${C_RESET}" "${C_BOLD}" "$*" "${C_RESET}"; }
note() { printf '%s    %s%s\n' "${C_DIM}" "$*" "${C_RESET}"; }
warn() { printf '%s!!  %s%s\n' "${C_YELLOW}" "$*" "${C_RESET}" >&2; }
die() {
    printf '\n%sGAGAL:%s %s\n' "${C_RED}${C_BOLD}" "${C_RESET}" "$*" >&2
    exit 1
}

# Loads deploy/config.sh and fails with an actionable message when it is absent,
# which is the normal state of a fresh clone.
load_config() {
    local config="${DEPLOY_DIR}/config.sh"

    if [[ ! -f "${config}" ]]; then
        die "deploy/config.sh belum ada. Salin dulu:

    cp deploy/config.example.sh deploy/config.sh

lalu isi SSH_HOST, APP_ROOT, REPO_URL, REPO_SLUG, dan APP_URL."
    fi

    # shellcheck source=/dev/null
    . "${config}"

    local required=(SSH_HOST SSH_PORT APP_ROOT REPO_URL REPO_SLUG APP_URL BUILD_WORKTREE KEEP_RELEASES KEEP_DEPLOY_DUMPS)
    local name
    for name in "${required[@]}"; do
        if [[ -z "${!name:-}" ]]; then
            die "deploy/config.sh belum mengisi ${name}."
        fi
    done

    : "${REMOTE_PHP:=php}"
    : "${REMOTE_COMPOSER:=composer}"
    : "${REMOTE_PNPM:=pnpm}"

    # Trailing slashes would turn every "${APP_ROOT}/x" into a double slash and,
    # worse, make a "${APP_ROOT}" that is accidentally "/" harder to spot.
    APP_ROOT="${APP_ROOT%/}"
    APP_URL="${APP_URL%/}"
    BUILD_WORKTREE="${BUILD_WORKTREE%/}"

    if [[ "${APP_ROOT}" == "/" || "${APP_ROOT}" != /* ]]; then
        die "APP_ROOT harus path absolut dan bukan '/': ${APP_ROOT}"
    fi
}

require_local_commands() {
    local missing=()
    local command_name
    for command_name in "$@"; do
        command -v "${command_name}" >/dev/null 2>&1 || missing+=("${command_name}")
    done

    if ((${#missing[@]} > 0)); then
        die "Perintah berikut tidak ada di mesin ini: ${missing[*]}"
    fi
}

# One multiplexed SSH connection is reused by every remote call in a run. Without
# this a deployment opens well over a dozen connections, which is slow and, on
# servers with connection rate limiting, occasionally refused mid-deploy.
ssh_options() {
    printf '%s\n' \
        -o ControlMaster=auto \
        -o "ControlPath=${TMPDIR:-/tmp}/jalara-deploy-%C" \
        -o ControlPersist=120 \
        -o BatchMode=no \
        -p "${SSH_PORT}"
}

# Runs a command on the server. The argument is a shell snippet, so callers must
# quote anything interpolated into it.
remote() {
    local -a options
    mapfile -t options < <(ssh_options)
    ssh "${options[@]}" "${SSH_HOST}" "bash -lc $(printf '%q' "set -euo pipefail
$1")"
}

# Same, but attaches the local terminal so the caller can be prompted (used only
# by bootstrap, where a passphrase or host-key confirmation may be needed).
remote_interactive() {
    local -a options
    mapfile -t options < <(ssh_options)
    ssh -t "${options[@]}" "${SSH_HOST}" "bash -lc $(printf '%q' "set -euo pipefail
$1")"
}

close_ssh_control() {
    local -a options
    mapfile -t options < <(ssh_options)
    ssh "${options[@]}" -O exit "${SSH_HOST}" >/dev/null 2>&1 || true
}

# Runs an Artisan command inside a release directory on the server.
remote_artisan() {
    local release_path="$1"
    shift
    remote "cd $(printf '%q' "${release_path}") && ${REMOTE_PHP} artisan $*"
}

# Basename of the release the `current` symlink points at, empty on first deploy.
remote_current_release() {
    remote "if [[ -L $(printf '%q' "${APP_ROOT}/current") ]]; then basename \"\$(readlink -f $(printf '%q' "${APP_ROOT}/current"))\"; fi"
}

# Release directories are named "<utc timestamp>-<tag>" so a lexical sort is also
# a chronological one. Sorting by tag would put v1.9.0 after v1.10.0.
release_tag_of() {
    local release_name="$1"
    printf '%s' "${release_name#*-}"
}

confirm() {
    local prompt="$1"
    local answer=""

    printf '\n%s%s%s ' "${C_BOLD}" "${prompt}" "${C_RESET}"
    read -r answer || true

    [[ "${answer}" == "yes" ]]
}
