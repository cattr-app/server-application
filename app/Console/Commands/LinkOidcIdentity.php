<?php

namespace App\Console\Commands;

use App\Models\OidcIdentity;
use App\Models\User;
use App\Scopes\UserAccessScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkOidcIdentity extends Command
{
    protected $signature = 'cattr:oidc:link {user_id} {subject?} {--unlink : Unlink and revoke all Cattr tokens for this user}';

    protected $description = 'Bind a verified organizational subject to an existing Cattr account without changing its role';

    public function handle(): int
    {
        $issuer = config('oidc.issuer');
        $subject = $this->argument('subject');
        if ((!$this->option('unlink') && !$issuer) || !ctype_digit((string) $this->argument('user_id')) || (int) $this->argument('user_id') < 1
            || (!$this->option('unlink') && (!is_string($subject) || !preg_match('/^[\x20-\x7E]{1,255}$/D', $subject)))) {
            $this->error('Configure OIDC_ISSUER and provide a verified subject.');
            return self::FAILURE;
        }
        return DB::transaction(function () use ($issuer, $subject): int {
            $user = User::withoutGlobalScope(UserAccessScope::class)
                ->when($this->option('unlink'), static fn($query) => $query->withTrashed())
                ->lockForUpdate()->find($this->argument('user_id'));
            if (!$user || (!$this->option('unlink') && !$user->active)) {
                $this->error('An existing active account is required.');
                return self::FAILURE;
            }
            if ($this->option('unlink')) {
                OidcIdentity::where('user_id', $user->id)->delete();
                $user->tokens()->delete();
                $this->info('Identity unlinked; all Cattr tokens for this account revoked.');
                return self::SUCCESS;
            }
            $hash = OidcIdentity::identityHash($issuer, $subject);
            $existing = OidcIdentity::where('user_id', $user->id)->orWhere('identity_hash', $hash)->first();
            if ($existing && ($existing->user_id != $user->id || $existing->identity_hash !== $hash)) {
                $this->error('Identity already bound. Explicitly unlink before changing a binding.');
                return self::FAILURE;
            }
            OidcIdentity::firstOrCreate(['identity_hash' => $hash], [
                'issuer' => $issuer, 'subject' => $subject, 'user_id' => $user->id,
            ]);
            $this->info('Identity linked; existing role and password unchanged.');
            return self::SUCCESS;
        });
    }
}
