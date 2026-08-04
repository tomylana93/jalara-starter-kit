<?php

use App\Models\Documentation;
use App\Models\User;

it('searches only published documentation and prioritizes title matches', function () {
    $user = User::factory()->create();
    $titleMatch = Documentation::factory()->published()->create(['title' => 'Account guide', 'searchable_text' => 'Profile']);
    Documentation::factory()->published()->create(['title' => 'General guide', 'searchable_text' => 'Manage the account']);
    Documentation::factory()->create(['title' => 'Secret account', 'searchable_text' => 'account']);

    $response = $this->actingAs($user)->getJson(route('documentation.search', ['query' => 'account']));

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $titleMatch->id);
});

it('returns no remote results for a query shorter than two characters', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('documentation.search', ['query' => 'a']))
        ->assertExactJson(['data' => []]);
});
