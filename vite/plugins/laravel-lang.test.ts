import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, writeFile } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { test } from 'node:test';
import { generateLaravelLangJson } from './laravel-lang.ts';

const createFixture = async (): Promise<{
    langPath: string;
    outputPath: string;
}> => {
    const root = await mkdtemp(path.join(os.tmpdir(), 'laravel-lang-'));
    const langPath = path.join(root, 'lang');
    const outputPath = path.join(root, 'generated');

    await mkdir(path.join(langPath, 'en'), { recursive: true });
    await mkdir(path.join(langPath, 'id'), { recursive: true });
    await writeFile(
        path.join(langPath, 'en', 'account.php'),
        "<?php return ['profile' => ['label' => ['name' => 'Name']]];",
    );
    await writeFile(
        path.join(langPath, 'id', 'account.php'),
        "<?php return ['profile' => ['label' => ['name' => 'Nama']]];",
    );
    await writeFile(
        path.join(langPath, 'id', 'messages.php'),
        "<?php return ['welcome' => 'Selamat datang'];",
    );

    return { langPath, outputPath };
};

test('generates one nested JSON file per locale', async () => {
    const paths = await createFixture();

    await generateLaravelLangJson({
        ...paths,
        phpBinary: 'php',
    });

    const english = JSON.parse(
        await readFile(path.join(paths.outputPath, 'en.json'), 'utf8'),
    );
    const indonesian = JSON.parse(
        await readFile(path.join(paths.outputPath, 'id.json'), 'utf8'),
    );

    assert.equal(english.account.profile.label.name, 'Name');
    assert.equal(indonesian.account.profile.label.name, 'Nama');
    assert.equal(indonesian.messages.welcome, 'Selamat datang');
});

test('removes JSON files for locales that no longer exist', async () => {
    const paths = await createFixture();

    await mkdir(paths.outputPath, { recursive: true });
    await writeFile(path.join(paths.outputPath, 'stale.json'), '{}');

    await generateLaravelLangJson({
        ...paths,
        phpBinary: 'php',
    });

    await assert.rejects(
        readFile(path.join(paths.outputPath, 'stale.json'), 'utf8'),
    );
});

test('keeps the last valid output when a language file is invalid', async () => {
    const paths = await createFixture();
    const options = {
        ...paths,
        phpBinary: 'php',
    };

    await generateLaravelLangJson(options);
    const validOutput = await readFile(
        path.join(paths.outputPath, 'en.json'),
        'utf8',
    );

    await writeFile(
        path.join(paths.langPath, 'en', 'invalid.php'),
        "<?php return 'invalid';",
    );

    await assert.rejects(generateLaravelLangJson(options));
    assert.equal(
        await readFile(path.join(paths.outputPath, 'en.json'), 'utf8'),
        validOutput,
    );
});
