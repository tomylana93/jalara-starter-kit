import assert from 'node:assert/strict';
import { test } from 'vitest';
import {
    ASSET_ISOLATION_VARIABLE,
    resolveAssetOutput,
} from './asset-output.ts';

test('keeps the development paths when asset isolation is not requested', () => {
    assert.deepEqual(resolveAssetOutput({}), {
        buildDirectory: 'build',
        hotFile: 'public/hot',
    });
});

test('keeps the development paths when asset isolation is disabled', () => {
    assert.deepEqual(
        resolveAssetOutput({ [ASSET_ISOLATION_VARIABLE]: 'false' }),
        {
            buildDirectory: 'build',
            hotFile: 'public/hot',
        },
    );
});

test('keeps the unit suite off the development hot marker', () => {
    const output = resolveAssetOutput({ VITEST: 'true' });

    assert.deepEqual(output, {
        buildDirectory: 'build-vitest',
        hotFile: 'public/hot-vitest',
    });
    assert.notEqual(output.hotFile, 'public/hot');
});

test('moves the bundle out of the development paths when isolation is enabled', () => {
    const output = resolveAssetOutput({
        [ASSET_ISOLATION_VARIABLE]: 'true',
    });

    assert.deepEqual(output, {
        buildDirectory: 'build-e2e',
        hotFile: 'public/hot-e2e',
    });
    assert.notEqual(output.buildDirectory, 'build');
    assert.notEqual(output.hotFile, 'public/hot');
});
