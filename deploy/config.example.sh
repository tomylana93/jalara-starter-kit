# Deployment target configuration.
#
# Copy this file to `deploy/config.sh` and fill in the placeholders. The copy is
# gitignored on purpose: it names a specific server, while this example travels
# with the repository so a forked project only has to fill in values rather than
# reconstruct the flow.
#
#     cp deploy/config.example.sh deploy/config.sh
#
# Every value below is required unless marked optional.

# SSH target that owns the application directory. This account never needs sudo:
# the deployment restarts background processes through Artisan signals, and the
# stale-path problem PHP-FPM would otherwise have is solved in the nginx vhost
# with `$realpath_root` instead of an FPM reload. See deploy/bootstrap.sh.
SSH_HOST="deploy@vps.example.com"
SSH_PORT="22"

# Application root on the server. Holds `releases/`, `shared/`, and the `current`
# symlink that the nginx vhost points at.
APP_ROOT="/srv/jalara"

# Repository the server fetches from. Use the SSH form even while the repository
# is public: it behaves identically for public and private repositories, so a
# private fork needs a deploy key rather than a different deployment flow.
REPO_URL="git@github.com:OWNER/REPO.git"

# Same repository in `owner/name` form, used locally by `gh` to confirm the tag
# being deployed has a published, non-draft GitHub Release behind it.
REPO_SLUG="OWNER/REPO"

# Public base URL, used for the post-deployment health check. Checking from here
# rather than from inside the server also exercises DNS, TLS, and the vhost.
APP_URL="https://app.example.com"

# Local build worktree. A second checkout of this repository, pinned to the tag
# being deployed, so assets are never built from your working tree. Deliberately
# outside the project directory: inside it, Vite, ESLint, PHPStan, and Playwright
# would all pick it up regardless of .gitignore.
BUILD_WORKTREE="${HOME}/.cache/jalara-deploy-build"

# Retention. Old releases stay complete and bootable, which is what makes
# `deploy/rollback.sh` a symlink flip rather than a rebuild.
KEEP_RELEASES=5
KEEP_DEPLOY_DUMPS=5

# Optional: override if the server resolves these differently.
REMOTE_PHP="php"
REMOTE_COMPOSER="composer"
REMOTE_PNPM="pnpm"
