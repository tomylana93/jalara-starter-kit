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
 * Vitest creates a Vite dev server to run the unit suite, which makes the
 * Laravel plugin register a process-exit handler that deletes its configured
 * hot file. Pointing the unit run at a marker nobody serves keeps that cleanup
 * away from the development session.
 */
export const UNIT_TEST_ASSET_OUTPUT: AssetOutput = {
    buildDirectory: 'build-vitest',
    hotFile: 'public/hot-vitest',
};

export const resolveAssetOutput = (
    env: Record<string, string | undefined>,
): AssetOutput => {
    if (env.VITEST !== undefined) {
        return UNIT_TEST_ASSET_OUTPUT;
    }

    return DEVELOPMENT_ASSET_OUTPUT;
};
