<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\ReviewableBetaRequest;
use App\Models\User;
use App\Models\Views\AdminBetaRequestView;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminBetaRequestsQuery
{
    /**
     * How long a request lingers after its requester has an account before it
     * drops off the review list.
     */
    private const STALE_AFTER_WEEKS = 2;

    /**
     * Every beta request worth reviewing, newest first, shaped for the admin
     * list. The email column is stored encrypted, so it is decrypted here for
     * display and to match against existing accounts. Requests whose requester
     * already has an account are flagged as "registered"; once that account is
     * older than two weeks the request is dropped entirely.
     *
     * The account correlation lives in PHP rather than the SQL view because the
     * stored email is non-deterministic ciphertext, so it can't be joined on.
     * That cross-row lookup is also why the projection is built here instead of
     * by a mapper on the view.
     *
     * @return array<int, ReviewableBetaRequest>
     */
    public function all(): array
    {
        $requests = AdminBetaRequestView::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AdminBetaRequestView $request): array => [
                'row' => $request,
                'email' => $this->decryptEmail($request->email),
            ]);

        $accounts = $this->accountsFor($requests->pluck('email'));
        $staleAfter = now()->subWeeks(self::STALE_AFTER_WEEKS);

        return $requests
            ->map(fn (array $request): array => [
                ...$request,
                'registered_at' => $accounts->get(Str::lower($request['email'])),
            ])
            ->reject(fn (array $request): bool => $request['registered_at']?->lt($staleAfter) ?? false)
            ->map(fn (array $request): ReviewableBetaRequest => new ReviewableBetaRequest(
                id: $request['row']->id,
                name: $request['row']->name,
                email: $request['email'],
                message: $request['row']->message,
                status: $request['row']->status->value,
                created_at: $request['row']->created_at?->toISOString(),
                registered: $request['registered_at'] !== null,
            ))
            ->values()
            ->all();
    }

    /**
     * Account creation times keyed by lower-cased email, for the requesters who
     * already have an account.
     *
     * @param  Collection<int, string>  $emails
     * @return Collection<string, CarbonInterface|null>
     */
    private function accountsFor(Collection $emails): Collection
    {
        $needles = $emails
            ->filter()
            ->map(fn (string $email): string => Str::lower($email))
            ->unique()
            ->values()
            ->all();

        return User::query()
            ->whereIn(DB::raw('lower(email)'), $needles)
            ->get(['email', 'created_at'])
            ->keyBy(fn (User $user): string => Str::lower($user->email))
            ->map(fn (User $user): ?CarbonInterface => $user->created_at);
    }

    /**
     * Mirror of the Eloquent "encrypted" cast (Crypt::decrypt with no
     * serialization). Falls back gracefully if a row can't be decrypted.
     */
    private function decryptEmail(string $ciphertext): string
    {
        try {
            return Crypt::decrypt($ciphertext, false);
        } catch (DecryptException) {
            return '';
        }
    }
}
