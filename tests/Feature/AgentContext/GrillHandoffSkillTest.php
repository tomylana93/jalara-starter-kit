<?php

use Symfony\Component\Yaml\Yaml;

/**
 * @return array{frontmatter: array<string, mixed>, body: string}
 */
function readGrillHandoffSkill(): array
{
    $contents = (string) file_get_contents(base_path('.ai/skills/grill-handoff/SKILL.md'));

    expect($contents)->toStartWith("---\n");

    [, $frontmatter, $body] = explode("---\n", $contents, 3);

    return [
        'frontmatter' => (array) Yaml::parse($frontmatter),
        'body' => $body,
    ];
}

it('declares only a name and a trigger-rich description', function () {
    $skill = readGrillHandoffSkill();

    expect(array_keys($skill['frontmatter']))->toBe(['name', 'description'])
        ->and($skill['frontmatter']['name'])->toBe('grill-handoff')
        ->and($skill['frontmatter']['description'])->toContain('$grill-handoff')
        ->and($skill['frontmatter']['description'])->toContain('handoff');
});

it('is registered as a published Boost skill', function () {
    $boost = (array) json_decode((string) file_get_contents(base_path('boost.json')), true);

    expect($boost['skills'])->toContain('grill-handoff');
});

it('restricts invocation to the explicit skill call', function () {
    $agent = (array) Yaml::parseFile(base_path('.ai/skills/grill-handoff/agents/openai.yaml'));

    expect($agent['policy']['allow_implicit_invocation'])->toBeFalse()
        ->and($agent['interface']['default_prompt'])->toContain('$grill-handoff')
        ->and($agent['interface'])->toHaveKeys(['display_name', 'short_description', 'default_prompt']);
});

it('guards against implementing, growing scope, or assuming material decisions', function () {
    $body = readGrillHandoffSkill()['body'];

    expect($body)->toContain('plan-only')
        ->and($body)->toContain('never edits')
        ->and($body)->toContain('never writes Serena memory')
        ->and($body)->toContain('Do not implement, stage, or commit.')
        ->and($body)->toContain('Do not grow the plan')
        ->and($body)->toContain('Claude Code, agy, or');
});

it('keeps every mandatory handoff section in contract order', function () {
    $template = (string) file_get_contents(base_path('.ai/skills/grill-handoff/assets/handoff-template.md'));

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
    $template = (string) file_get_contents(base_path('.ai/skills/grill-handoff/assets/handoff-template.md'));

    expect($template)->toContain('**Base commit:**')
        ->and($template)->toContain('**Working tree at analysis:**')
        ->and($template)->toContain('composer run rector')
        ->and($template)->toContain('composer run ci:check')
        ->and($template)->toContain('- [ ] All acceptance criteria met')
        ->and($template)->toContain('- [ ] Diff touches only the approved scope')
        ->and(substr_count($template, '- [ ]'))->toBeGreaterThanOrEqual(7);
});
