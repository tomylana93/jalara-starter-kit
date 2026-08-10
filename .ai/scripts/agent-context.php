<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Agent Context Publication Guard
|--------------------------------------------------------------------------
|
| This repository supports exactly two agents: Claude Code and Codex. Laravel
| Boost composes their outputs, but some upstream skills ship text naming
| agents we do not support, and a hand edit of a published file is overwritten
| by the next publication. This script is therefore the only supported way to
| remove such a reference.
|
| Usage:
|   php .ai/scripts/agent-context.php sanitize   rewrite generated outputs
|   php .ai/scripts/agent-context.php check      read-only validation
|
| `sanitize` runs as the last step of `composer run agents:update`. It is
| idempotent: each rule removes its target when present and is satisfied when
| already absent. A residual scan then fails loudly if any unsupported-agent
| reference survives, so an upstream format change surfaces as an error rather
| than silently leaving the reference in place.
*/

const SUPPORTED_AGENTS = ['claude_code', 'codex'];

/**
 * Tokens that must not appear in any tracked file. Matched case-insensitively
 * with word boundaries so unrelated words are never flagged.
 *
 * @var list<string>
 */
const FORBIDDEN_TOKENS = ['agy', 'antigravity', 'gemini', 'opencode', 'open-code'];

/**
 * Paths excluded from the residual scan: this script names the tokens by
 * design, and lockfile integrity hashes produce meaningless matches.
 *
 * @var list<string>
 */
const SCAN_EXCLUDED_PATHS = [
    '.ai/scripts/agent-context.php',
    'composer.lock',
    'pnpm-lock.yaml',
];

/**
 * Line-removal rules applied to generated outputs, keyed by file path.
 *
 * @var array<string, list<string>>
 */
const SANITIZE_LINE_REMOVALS = [
    '.agents/skills/shadcn-vue/mcp.md' => ['| OpenCode | `opencode.json` |'],
    '.claude/skills/shadcn-vue/mcp.md' => ['| OpenCode | `opencode.json` |'],
];

/**
 * Files that must all carry the same verification gate.
 *
 * @var list<string>
 */
const GATE_SURFACES = [
    '.codex/config.toml',
    '.agents/skills/grill-me/assets/handoff-template.md',
    '.claude/commands/apply-plan.md',
];

/**
 * Commands every verification gate must name.
 *
 * @var list<string>
 */
const GATE_COMMANDS = [
    'composer run fix',
    'composer run agents:update',
    'composer run agents:check',
    'serena memories check --include-unmarked --fuzzy-matching',
    'composer run ci:check',
];

/**
 * Files that must expose the same handoff structure.
 *
 * @var list<string>
 */
const HANDOFF_SURFACES = [
    '.codex/config.toml',
    '.agents/skills/grill-me/assets/handoff-template.md',
];

/**
 * Sections every handoff must contain, in this order.
 *
 * @var list<string>
 */
const HANDOFF_SECTIONS = [
    '### Goals and acceptance criteria',
    '### Verified context',
    '### Assumptions and unknowns',
    '### Change plan',
    '### Out of scope',
    '### Tests',
    '### Verification gate',
    '### Memory/context update',
    '### Freshness check',
    '### Completion criteria',
];

/**
 * Paths that must not come back once their integration was removed.
 *
 * @var list<string>
 */
const REMOVED_PATHS = ['opencode.json', '.agents/plugins'];

$root = dirname(__DIR__, 2);
$mode = $argv[1] ?? '';

exit(match ($mode) {
    'sanitize' => runSanitize($root),
    'check' => runCheck($root),
    default => fail('Usage: php .ai/scripts/agent-context.php {sanitize|check}'),
});

function fail(string $message): int
{
    fwrite(STDERR, $message.PHP_EOL);

    return 1;
}

/**
 * @param  list<string>  $violations
 */
function report(string $label, array $violations): int
{
    if ($violations === []) {
        fwrite(STDOUT, $label.': OK'.PHP_EOL);

        return 0;
    }

    return fail($label.' FAILED:'.PHP_EOL.'  - '.implode(PHP_EOL.'  - ', $violations));
}

function runSanitize(string $root): int
{
    $changed = [];

    foreach (SANITIZE_LINE_REMOVALS as $relativePath => $needles) {
        $absolutePath = $root.'/'.$relativePath;

        if (! is_file($absolutePath)) {
            continue;
        }

        $original = (string) file_get_contents($absolutePath);
        $sanitized = $original;

        foreach ($needles as $needle) {
            $sanitized = preg_replace(
                '/^'.preg_quote($needle, '/').'\R/m',
                '',
                $sanitized,
            ) ?? $sanitized;
        }

        if ($sanitized !== $original) {
            file_put_contents($absolutePath, $sanitized);
            $changed[] = $relativePath;
        }
    }

    if ($changed !== []) {
        fwrite(STDOUT, 'Sanitized '.count($changed).' generated file(s): '.implode(', ', $changed).PHP_EOL);
    }

    $residual = scanForbiddenTokens($root);

    if ($residual !== []) {
        return fail(
            'Sanitize FAILED: unsupported-agent references survived. A sanitize rule no longer'.PHP_EOL
            .'matches its upstream text, or a new reference appeared. Update SANITIZE_LINE_REMOVALS'.PHP_EOL
            .'in .ai/scripts/agent-context.php instead of editing the generated file.'.PHP_EOL
            .'  - '.implode(PHP_EOL.'  - ', $residual),
        );
    }

    fwrite(STDOUT, 'Sanitize: OK'.PHP_EOL);

    return 0;
}

function runCheck(string $root): int
{
    $status = 0;
    $status |= report('Agent selection', checkAgentSelection($root));
    $status |= report('Removed integrations', checkRemovedPaths($root));
    $status |= report('Unsupported-agent references', scanForbiddenTokens($root));
    $status |= report('Verification gate parity', checkGateParity($root));
    $status |= report('Handoff structure parity', checkHandoffParity($root));
    $status |= report('Scoped Boost rules', checkScopedRules($root));

    return $status === 0 ? 0 : 1;
}

/**
 * @return list<string>
 */
function checkAgentSelection(string $root): array
{
    $path = $root.'/boost.json';

    if (! is_file($path)) {
        return ['boost.json is missing.'];
    }

    $config = json_decode((string) file_get_contents($path), true);

    if (! is_array($config) || ! isset($config['agents']) || ! is_array($config['agents'])) {
        return ['boost.json has no "agents" array.'];
    }

    $agents = array_values($config['agents']);

    if ($agents !== SUPPORTED_AGENTS) {
        return [sprintf(
            'boost.json agents must be exactly [%s]; found [%s].',
            implode(', ', SUPPORTED_AGENTS),
            implode(', ', array_map(strval(...), $agents)),
        )];
    }

    return [];
}

/**
 * @return list<string>
 */
function checkRemovedPaths(string $root): array
{
    $violations = [];

    foreach (REMOVED_PATHS as $relativePath) {
        if (file_exists($root.'/'.$relativePath)) {
            $violations[] = $relativePath.' was removed from this repository and must not return.';
        }
    }

    return $violations;
}

/**
 * @return list<string>
 */
function checkGateParity(string $root): array
{
    $violations = [];

    foreach (GATE_SURFACES as $relativePath) {
        $contents = readTracked($root, $relativePath, $violations);

        if ($contents === null) {
            continue;
        }

        foreach (GATE_COMMANDS as $command) {
            if (! str_contains(collapseWhitespace($contents), collapseWhitespace($command))) {
                $violations[] = $relativePath.' omits the gate command "'.$command.'".';
            }
        }
    }

    return $violations;
}

/**
 * @return list<string>
 */
function checkHandoffParity(string $root): array
{
    $violations = [];

    foreach (HANDOFF_SURFACES as $relativePath) {
        $contents = readTracked($root, $relativePath, $violations);

        if ($contents === null) {
            continue;
        }

        $offset = 0;

        foreach (HANDOFF_SECTIONS as $section) {
            $position = strpos($contents, $section, $offset);

            if ($position === false) {
                $violations[] = $relativePath.' omits the handoff section "'.$section.'" or lists it out of order.';

                continue;
            }

            $offset = $position + strlen($section);
        }
    }

    return $violations;
}

/**
 * @return list<string>
 */
function checkScopedRules(string $root): array
{
    if (! is_dir($root.'/.ai/rules/boost')) {
        return ['.ai/rules/boost is missing; run `composer run agents:update` to extract scoped package guidance.'];
    }

    $index = $root.'/.ai/rules/index.md';

    if (! is_file($index)) {
        return ['.ai/rules/index.md is missing.'];
    }

    if (! str_contains((string) file_get_contents($index), '.ai/rules/boost/')) {
        return ['.ai/rules/index.md does not list any .ai/rules/boost/ rule file.'];
    }

    return [];
}

/**
 * @param  list<string>  $violations
 */
function readTracked(string $root, string $relativePath, array &$violations): ?string
{
    $absolutePath = $root.'/'.$relativePath;

    if (! is_file($absolutePath)) {
        $violations[] = $relativePath.' is missing.';

        return null;
    }

    return (string) file_get_contents($absolutePath);
}

function collapseWhitespace(string $value): string
{
    return (string) preg_replace('/\s+/', ' ', $value);
}

/**
 * Scan every tracked text file for references to unsupported agents.
 *
 * @return list<string>
 */
function scanForbiddenTokens(string $root): array
{
    $pattern = '/\b(?:'.implode('|', array_map(
        static fn (string $token): string => preg_quote($token, '/'),
        FORBIDDEN_TOKENS,
    )).')\b/i';

    $violations = [];

    foreach (trackedFiles($root) as $relativePath) {
        if (in_array($relativePath, SCAN_EXCLUDED_PATHS, true)) {
            continue;
        }

        $absolutePath = $root.'/'.$relativePath;

        if (! is_file($absolutePath)) {
            continue;
        }

        $contents = (string) file_get_contents($absolutePath);

        if (str_contains($contents, "\0")) {
            continue;
        }

        foreach (explode("\n", $contents) as $index => $line) {
            if (preg_match($pattern, $line) === 1) {
                $violations[] = sprintf('%s:%d: %s', $relativePath, $index + 1, trim($line));
            }
        }
    }

    return $violations;
}

/**
 * @return list<string>
 */
function trackedFiles(string $root): array
{
    $command = sprintf('git -C %s ls-files -z', escapeshellarg($root));
    $output = shell_exec($command);

    if (! is_string($output) || $output === '') {
        return [];
    }

    return array_values(array_filter(explode("\0", $output), static fn (string $path): bool => $path !== ''));
}
