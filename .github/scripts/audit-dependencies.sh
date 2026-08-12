#!/usr/bin/env bash
#
# Audits the locked dependencies and tells the two failure modes apart.
#
# A published advisory against a package this repository locks is a finding: it
# fails immediately, because retrying cannot change the answer and a slow gate
# is worse than a fast one. An advisory service that cannot be reached is not a
# finding, so the run is retried before it is reported as infrastructure.
#
# Neither outcome is reported as success. A gate that quietly goes green when it
# could not check anything is the failure this separation exists to avoid.

set -euo pipefail

readonly ATTEMPTS=3
readonly BACKOFF_SECONDS=15

# The same Composer script the local gate runs, so a lock file that passes here
# passes there.
#
# Delegating also removes a trap: `set -e` is suspended inside a function called
# as the condition of an `if`, so listing the ecosystems here would have let a
# failing `composer audit` be overwritten by a clean `pnpm audit` and reported as
# success. `composer run` stops at the first failing command and propagates its
# status.
audit() {
    composer run audit:check || return 1

    return 0
}

advisory_services_reachable() {
    curl --fail --silent --show-error --max-time 15 --output /dev/null \
        https://repo.packagist.org/packages.json &&
        curl --fail --silent --show-error --max-time 15 --output /dev/null \
            https://registry.npmjs.org/-/ping
}

for attempt in $(seq 1 "${ATTEMPTS}"); do
    if audit; then
        echo 'No advisory affects the locked dependencies.'
        exit 0
    fi

    if advisory_services_reachable; then
        echo '::error::A locked dependency is affected by a published advisory. Raise the dependency, or record why it does not apply.'
        exit 1
    fi

    echo "::warning::The advisory services were unreachable on attempt ${attempt} of ${ATTEMPTS}."

    if [ "${attempt}" -lt "${ATTEMPTS}" ]; then
        sleep "${BACKOFF_SECONDS}"
    fi
done

echo '::error::The advisory services stayed unreachable. This is an infrastructure failure, not a vulnerable dependency: re-run the job.'
exit 1
