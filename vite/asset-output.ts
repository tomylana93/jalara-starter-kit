export type AssetOutput = {
    buildDirectory: string;
    hotFile: string;
};

/* Paths a local `pnpm run dev` / `pnpm run build` session owns. */
export const DEVELOPMENT_ASSET_OUTPUT: AssetOutput = {
    buildDirectory: 'build',
    hotFile: 'public/hot',
};

/*
 * Paths the Playwright runner owns. Keeping the E2E bundle out of the
 * development paths means a test run can never delete or replace the hot marker
 * and manifest a concurrent Vite dev server is using.
 */
export const ISOLATED_ASSET_OUTPUT: AssetOutput = {
    buildDirectory: 'build-e2e',
    hotFile: 'public/hot-e2e',
};

/*
 * Vitest creates a Vite dev server to run the unit suite, which makes the
 * Laravel plugin register a process-exit handler that deletes its configured
 * hot file. Pointing the unit run at a marker nobody serves keeps that cleanup
 * away from both the development and the Playwright session.
 */
export const UNIT_TEST_ASSET_OUTPUT: AssetOutput = {
    buildDirectory: 'build-vitest',
    hotFile: 'public/hot-vitest',
};

export const ASSET_ISOLATION_VARIABLE = 'E2E_ASSET_ISOLATION';

/*
 * The matching backend paths are configured from `app.vite` by
 * `AppServiceProvider`; both sides must stay in sync for Laravel to resolve the
 * bundle Vite wrote.
 */
export const resolveAssetOutput = (
    env: Record<string, string | undefined>,
): AssetOutput => {
    if (env[ASSET_ISOLATION_VARIABLE] === 'true') {
        return ISOLATED_ASSET_OUTPUT;
    }

    if (env.VITEST !== undefined) {
        return UNIT_TEST_ASSET_OUTPUT;
    }

    return DEVELOPMENT_ASSET_OUTPUT;
};
