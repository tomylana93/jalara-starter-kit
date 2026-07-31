<?php

use App\Models\Documentation;
use App\Models\User;

it('searches only published documentation and prioritizes title matches', function () {
    $user = User::factory()->create();
    $titleMatch = Documentation::factory()->published()->create(['title' => 'Panduan akun', 'searchable_text' => 'Profil']);
    Documentation::factory()->published()->create(['title' => 'Panduan umum', 'searchable_text' => 'Kelola akun']);
    Documentation::factory()->create(['title' => 'Akun rahasia', 'searchable_text' => 'akun']);

    $response = $this->actingAs($user)->getJson(route('documentation.search', ['query' => 'akun']));

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $titleMatch->id);
});

it('returns no remote results for a query shorter than two characters', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('documentation.search', ['query' => 'a']))
        ->assertExactJson(['data' => []]);
});
