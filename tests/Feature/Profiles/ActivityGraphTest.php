<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\PresentationSession;
use App\Models\Rehearsal;
use App\Models\TalkEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the activity graph renders as an svg for a known handle', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(10)->addMinutes(15),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/svg+xml');
    expect($response->getContent())
        ->toContain('<svg')
        ->toContain('@'.$user->handle)
        ->toContain('1 rehearsal')
        ->toContain('#0e2a14'); // level-1 green cell
});

test('legacy runs without a user_id are attributed via the personal team', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'user_id' => null,
        'started_at' => now()->subDays(3),
        'ended_at' => now()->subDays(3)->addMinutes(15),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    expect($response->getContent())->toContain('1 rehearsal')->toContain('#0e2a14');
});

test('talk event days render in the live emerald', function () {
    $user = User::factory()->create();
    TalkEvent::factory()->create([
        'team_id' => $user->currentTeam->id,
        'date' => now()->subDays(5)->toDateString(),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    expect($response->getContent())->toContain('#fcc419')->toContain('1 talk on stage');
});

test('a delivered presentation session counts as a talk on stage', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    PresentationSession::factory()->ended()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'started_at' => now()->subDays(4),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    expect($response->getContent())->toContain('#fcc419')->toContain('1 talk on stage');
});

test('a talk event with a session on the same day is not counted twice', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    TalkEvent::factory()->create([
        'team_id' => $user->currentTeam->id,
        'date' => now()->subDays(5)->toDateString(),
    ]);
    PresentationSession::factory()->ended()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'started_at' => now()->subDays(5),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    expect($response->getContent())->toContain('1 talk on stage');
});

test('deck creation days render in the quiet neutral', function () {
    $user = User::factory()->create();
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'created_at' => now()->subDays(6),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    expect($response->getContent())->toContain('#3d3d42')->toContain('1 deck ·');
});

test('a rehearsal outranks a deck created the same day', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'created_at' => now()->subDays(7),
    ]);
    Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'user_id' => $user->id,
        'started_at' => now()->subDays(7),
        'ended_at' => now()->subDays(7)->addMinutes(15),
    ]);

    $response = $this->get(route('profiles.graph', ['handle' => $user->handle]));

    expect($response->getContent())
        ->toContain('#0e2a14') // green rehearsal cell wins the day
        ->not->toContain('#3d3d42');
});

test('handle casing in the url does not matter', function () {
    $user = User::factory()->create(['handle' => 'emilien.kopp']);

    $response = $this->get('/@Emilien.Kopp/graph.svg');

    $response->assertOk();
    expect($response->getContent())->toContain('@emilien.kopp');
});

test('an unknown handle returns 404', function () {
    $this->get(route('profiles.graph', ['handle' => 'nobody-here']))->assertNotFound();
});

test('a teammate\'s attributed rehearsal does not appear on another member\'s graph', function () {
    $owner = User::factory()->create();
    $teammate = User::factory()->create();
    DB::table('team_members')->insert([
        'team_id' => $owner->currentTeam->id,
        'user_id' => $teammate->id,
        'role' => 'member',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $presentation = Presentation::factory()->create(['team_id' => $owner->currentTeam->id]);
    Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $owner->currentTeam->id,
        'user_id' => $teammate->id,
        'started_at' => now()->subDays(2),
        'ended_at' => now()->subDays(2)->addMinutes(10),
    ]);

    $ownerGraph = $this->get(route('profiles.graph', ['handle' => $owner->handle]));
    $teammateGraph = $this->get(route('profiles.graph', ['handle' => $teammate->handle]));

    expect($ownerGraph->getContent())->toContain('0 rehearsals');
    expect($teammateGraph->getContent())->toContain('1 rehearsal ·');
});

test('recording a rehearsal stores the acting user', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => now()->subMinutes(10)->toISOString(),
        'ended_at' => now()->toISOString(),
        'duration_seconds' => 600,
        'slide_timings' => [],
    ])->assertRedirect();

    expect(Rehearsal::sole()->user_id)->toBe($user->id);
});
