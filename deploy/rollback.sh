#!/usr/bin/env bash
#
# Point the site back at a release that is already on the server.
#
#     ./deploy/rollback.sh                          # rilis sebelumnya
#     ./deploy/rollback.sh 20260807141522-v1.2.0    # rilis tertentu
#     ./deploy/rollback.sh --list                   # lihat yang tersedia
#
# Deliberately separate from deploy.sh, and deliberately much smaller. A
# deployment builds something new and unproven; a rollback returns to something
# that was serving traffic minutes ago and is still complete on disk — its own
# vendor/, its own node_modules/, its own cached config. Making rollback re-run
# the deployment path would make it pay for a build and a `composer install` that
# can fail on a network hiccup, at the exact moment that is least affordable.
#
# What this cannot do is move the database back. Read the warning it prints.
set -euo pipefail

. "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

TARGET=""
LIST_ONLY=0
ASSUME_YES=0

while (($# > 0)); do
    case "$1" in
        --list | -l) LIST_ONLY=1 ;;
        --yes | -y) ASSUME_YES=1 ;;
        -h | --help)
            sed -n '2,10p' "${BASH_SOURCE[0]}" | sed 's|^#\s\?||'
            exit 0
            ;;
        -*) die "Opsi tidak dikenal: $1" ;;
        *)
            [[ -n "${TARGET}" ]] && die "Target sudah diberikan: ${TARGET}"
            TARGET="$1"
            ;;
    esac
    shift
done

load_config
require_local_commands ssh

trap close_ssh_control EXIT

current_release="$(remote_current_release)"
[[ -n "${current_release}" ]] || die "Tidak ada rilis aktif di ${APP_ROOT}."

# Newest first. The naming scheme puts a UTC timestamp in front precisely so this
# ordering is chronological rather than alphabetical by tag.
mapfile -t releases < <(remote "ls -1 $(printf '%q' "${APP_ROOT}/releases") | sort -r")

((${#releases[@]} > 0)) || die "Direktori releases/ kosong."

if ((LIST_ONLY)); then
    step "Rilis di ${SSH_HOST}:${APP_ROOT}/releases"
    for name in "${releases[@]}"; do
        if [[ "${name}" == "${current_release}" ]]; then
            printf '    %s%s  <- aktif%s\n' "${C_BOLD}" "${name}" "${C_RESET}"
        else
            printf '    %s\n' "${name}"
        fi
    done
    exit 0
fi

if [[ -z "${TARGET}" ]]; then
    # The one directly below the active release, which is where "undo the last
    # deployment" actually points.
    for index in "${!releases[@]}"; do
        if [[ "${releases[index]}" == "${current_release}" ]]; then
            TARGET="${releases[index + 1]:-}"
            break
        fi
    done

    [[ -n "${TARGET}" ]] || die "Tidak ada rilis sebelum ${current_release}. Jalankan --list untuk melihat yang ada."
fi

[[ "${TARGET}" != "${current_release}" ]] || die "${TARGET} memang sedang aktif."

remote "test -d $(printf '%q' "${APP_ROOT}/releases/${TARGET}")" ||
    die "Rilis tidak ada: ${TARGET}. Jalankan --list untuk melihat yang tersedia."

latest_dump="$(remote "ls -1t $(printf '%q' "${APP_ROOT}/shared/deploy-backups") 2>/dev/null | head -n 1" || true)"

cat <<SUMMARY

  Target       : ${SSH_HOST}  ->  ${APP_ROOT}
  Dari         : ${current_release}
  Kembali ke   : ${TARGET}
  Dump terakhir: ${latest_dump:-(tidak ada)}

  ${C_YELLOW}Yang dikembalikan hanya kode. Skema database TIDAK ikut mundur.${C_RESET}
  Untuk migrasi aditif (tambah tabel/kolom) ini aman: kode lama tidak tahu
  kolom baru itu ada. Untuk migrasi destruktif (dropColumn, renameColumn),
  ${TARGET} akan berjalan di atas skema yang tidak dikenalnya — pulihkan dari
  dump di atas kalau begitu.
SUMMARY

if ((ASSUME_YES == 0)); then
    confirm "Ketik 'yes' untuk lanjut:" || die "Dibatalkan."
fi

step "Mengalihkan current -> ${TARGET}"

# Same non-atomic-replace problem as in deploy.sh: build the new link beside the
# old one, then rename over it.
remote "
ln -sfn $(printf '%q' "${APP_ROOT}/releases/${TARGET}") $(printf '%q' "${APP_ROOT}/current.tmp")
mv -Tf $(printf '%q' "${APP_ROOT}/current.tmp") $(printf '%q' "${APP_ROOT}/current")
"

step "Merestart background process"
remote_artisan "${APP_ROOT}/current" queue:restart
remote_artisan "${APP_ROOT}/current" reverb:restart

# `up` is unconditional: a rollback is often run after a deployment died inside
# its maintenance window, and a site left behind the maintenance page is exactly
# what this command exists to fix. It is a no-op when nothing was down.
remote_artisan "${APP_ROOT}/current" up || true

step "Selesai: ${TARGET} aktif di ${APP_URL}"
