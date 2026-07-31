<?php

use Symfony\Component\Yaml\Yaml;

/**
 * @return array{frontmatter: array<string, mixed>, body: string}
 */
function readGrillMeSkill(): array
{
    $contents = (string) file_get_contents(base_path('.agents/skills/grill-me/SKILL.md'));

    expect($contents)->toStartWith("---\n");

    [, $frontmatter, $body] = explode("---\n", $contents, 3);

    return [
        'frontmatter' => (array) Yaml::parse($frontmatter),
        'body' => $body,
    ];
}

it('declares only a name and a trigger-rich description', function () {
    $skill = readGrillMeSkill();

    expect(array_keys($skill['frontmatter']))->toBe(['name', 'description'])
        ->and($skill['frontmatter']['name'])->toBe('grill-me')
        ->and($skill['frontmatter']['description'])->toContain('$grill-me')
        ->and($skill['frontmatter']['description'])->toContain('handoff');
});

it('interviews the developer one question at a time before the handoff', function () {
    $skill = readGrillMeSkill();

    expect($skill['frontmatter']['description'])->toContain('one question at a time')
        ->and($skill['body'])->toContain('## Interview Loop')
        ->and($skill['body'])->toContain('one question at a time')
        ->and($skill['body'])->toContain('Never batch questions')
        ->and($skill['body'])->toContain('Give your recommended answer with each question')
        ->and($skill['body'])->toContain('explore instead of asking')
        ->and($skill['body'])->toContain('Never ask a question whose answer is already in the repository.')
        ->and($skill['body'])->toContain('Always interview, never assume')
        ->and($skill['body'])->toContain('Only after the interview is done');
});

it('stays a hand-maintained Codex workspace skill outside Boost publication', function () {
    $boost = (array) json_decode((string) file_get_contents(base_path('boost.json')), true);

    expect($boost['skills'])->not->toContain('grill-me')
        ->and($boost['skills'])->not->toContain('grill-handoff')
        ->and(is_link(base_path('.agents/skills/grill-me')))->toBeFalse()
        ->and(is_dir(base_path('.ai/skills/grill-me')))->toBeFalse()
        ->and(is_dir(base_path('.ai/skills/grill-handoff')))->toBeFalse();
});

it('is not exposed to Claude Code or any other agent skill path', function () {
    expect(file_exists(base_path('.claude/skills/grill-me')))->toBeFalse()
        ->and(file_exists(base_path('.claude/skills/grill-handoff')))->toBeFalse()
        ->and(file_exists(base_path('.agents/skills/grill-handoff')))->toBeFalse()
        ->and(file_exists(base_path('.agents/plugins/jalara/skills/grill-me')))->toBeFalse();
});

it('restricts invocation to the explicit skill call', function () {
    $agent = (array) Yaml::parseFile(base_path('.agents/skills/grill-me/agents/openai.yaml'));

    expect($agent['policy']['allow_implicit_invocation'])->toBeFalse()
        ->and($agent['interface']['default_prompt'])->toContain('$grill-me')
        ->and($agent['interface'])->toHaveKeys(['display_name', 'short_description', 'default_prompt']);
});

it('guards against implementing, growing scope, or assuming material decisions', function () {
    $body = readGrillMeSkill()['body'];

    expect($body)->toContain('plan-only')
        ->and($body)->toContain('never edits')
        ->and($body)->toContain('never writes Serena memory')
        ->and($body)->toContain('Do not implement, stage, or commit.')
        ->and($body)->toContain('Do not grow the plan')
        ->and($body)->toContain('Claude Code, agy, or');
});

it('keeps every mandatory handoff section in contract order', function () {
    $template = (string) file_get_contents(base_path('.agents/skills/grill-me/assets/handoff-template.md'));

    $sections = [
        '## HANDOFF',
        '### Tujuan dan kriteria penerimaan',
        '### Konteks terverifikasi',
        '### Asumsi dan unknowns',
        '### Rencana perubahan',
        '### Di luar scope',
        '### Tes',
        '### Verification gate',
        '### Freshness check',
        '### Kriteria selesai',
    ];

    $offsets = array_map(function (string $section) use ($template): int {
        $offset = strpos($template, $section);

        expect($offset)->not->toBeFalse("Missing handoff section: {$section}");

        return (int) $offset;
    }, $sections);

    $sorted = $offsets;
    sort($sorted);

    expect($offsets)->toBe($sorted);
});

it('keeps the handoff metadata, verification gate, and completion checkboxes', function () {
    $template = (string) file_get_contents(base_path('.agents/skills/grill-me/assets/handoff-template.md'));

    expect($template)->toContain('**Base commit:**')
        ->and($template)->toContain('**Working tree at analysis:**')
        ->and($template)->toContain('composer run rector')
        ->and($template)->toContain('composer run ci:check')
        ->and($template)->toContain('- [ ] All acceptance criteria met')
        ->and($template)->toContain('- [ ] Diff touches only the approved scope')
        ->and(substr_count($template, '- [ ]'))->toBeGreaterThanOrEqual(7);
});
