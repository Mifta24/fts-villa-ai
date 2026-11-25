<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Villa;
use App\Models\VillaUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Creates the owner account of an existing villa. The password is read twice
 * from a hidden prompt (or stdin), never accepted as an argument, and never
 * printed. Existing accounts are never changed.
 */
#[Signature('villa:create-admin {--email=} {--name=} {--villa= : Existing villa slug}')]
#[Description('Create an owner account for an existing villa using a hidden password prompt')]
class CreateAdminCommand extends Command
{
    public const PASSWORD_MIN = 12;

    public function handle(): int
    {
        $villa = Villa::query()->where('slug', $this->option('villa'))->first();

        if (! $villa) {
            $this->error('Villa not found. Supply --villa with an existing villa slug.');

            return self::FAILURE;
        }

        $data = ['email' => mb_strtolower(trim((string) $this->option('email'))), 'name' => trim((string) $this->option('name'))];
        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'name' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $password = $this->secret('Password (minimum '.self::PASSWORD_MIN.' characters)');
        $confirmation = $this->secret('Confirm password');

        if (! is_string($password) || mb_strlen($password) < self::PASSWORD_MIN || $password !== $confirmation) {
            $this->error('Passwords must match and contain at least '.self::PASSWORD_MIN.' characters.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($villa, $data, $password) {
            $user = User::create([...$data, 'password' => Hash::make($password), 'email_verified_at' => now()]);
            $villa->users()->syncWithoutDetaching([$user->id => ['role' => VillaUser::ROLE_OWNER, 'status' => 'active']]);
        });

        $this->info('Admin '.$data['email'].' created.');

        return self::SUCCESS;
    }
}
