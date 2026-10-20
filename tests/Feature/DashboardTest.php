<?php

use App\Enums\TeamRole;
use App\Models\Presentation;
use App\Models\PresentationSession;
use App\Models\Rehearsal;
use App\Models\Talk;
use App\Models\TalkEvent;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard timeline interleaves sessions and rehearsals, most recent first', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    PresentationSession::factory()
        ->ended()
        ->withReactions(['🔥' => 3, '👏' => 1])
        ->create([
            'presentation_id' => $presentation->id,
            'started_at' => Carbon::now()->subDays(2),
            'viewer_count' => 7,
        ]);

    Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'started_at' => Carbon::now()->subDay(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('timeline', 2)
        ->where('timeline.0.type', 'rehearsal')
        ->where('timeline.0.presentation_name', $presentation->name)
        ->where('timeline.1.type', 'session')
        ->where('timeline.1.viewer_count', 7)
        ->where('timeline.1.reaction_total', 4)
        ->where('timeline.1.reaction_counts.🔥', 3)
        ->has('recentDecks', 1),
    );
});

test('dashboard timeline includes talk events up to a week out with the latest deck', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $talk = Talk::factory()->create(['team_id' => $team->id, 'title' => 'Scaling Laravel']);
    Presentation::factory()->create([
        'team_id' => $team->id,
        'talk_id' => $talk->id,
        'version_major' => 1,
        'version_minor' => 0,
    ]);
    $latestDeck = Presentation::factory()->create([
        'team_id' => $team->id,
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 0,
    ]);

    TalkEvent::factory()->create([
        'team_id' => $team->id,
        'talk_id' => $talk->id,
        'name' => 'DevCon Tokyo',
        'date' => Carbon::today()->addDays(3)->toDateString(),
    ]);

    // No linked talk: still shown, but with no deck to edit or rehearse.
    TalkEvent::factory()->create([
        'team_id' => $team->id,
        'name' => 'Meetup lightning talk',
        'date' => Carbon::today()->addDays(5)->toDateString(),
    ]);

    // Outside the one-week window and in the past: both excluded.
    TalkEvent::factory()->create([
        'team_id' => $team->id,
        'date' => Carbon::today()->addDays(10)->toDateString(),
    ]);
    TalkEvent::factory()->create([
        'team_id' => $team->id,
        'date' => Carbon::today()->subDay()->toDateString(),
    ]);

    Rehearsal::factory()->create([
        'presentation_id' => $latestDeck->id,
        'started_at' => Carbon::now()->subDay(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('timeline', 3)
        ->where('timeline.0.type', 'upcoming')
        ->where('timeline.0.presentation_name', 'Meetup lightning talk')
        ->where('timeline.0.presentation_id', null)
        ->where('timeline.1.type', 'upcoming')
        ->where('timeline.1.presentation_name', 'Scaling Laravel')
        ->where('timeline.1.event_name', 'DevCon Tokyo')
        ->where('timeline.1.presentation_id', $latestDeck->id)
        ->where('timeline.2.type', 'rehearsal')
        ->etc(),
    );
});

test('dashboard recent decks expose the talk version label', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create(['team_id' => $user->currentTeam->id]);
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 1,
        'updated_at' => Carbon::now(),
    ]);
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'updated_at' => Carbon::now()->subDay(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('recentDecks', 2)
        ->where('recentDecks.0.version', '2.1')
        ->where('recentDecks.1.version', null)
        ->etc(),
    );
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});
