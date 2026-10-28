<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreatePlatformAdmin extends Command
{
    /**
     * The password is deliberately not an option: it is only ever typed at a hidden prompt,
     * so it never lands in shell history, process lists, CI logs or configuration files.
     *
     * @var string
     */
    protected $signature = 'hms:create-platform-admin
                            {--name= : Full name of the platform administrator}
                            {--email= : Sign-in email address}';

    /**
     * @var string
     */
    protected $description = 'Create a platform (super) administrator, or reset an existing one\'s password, from an interactive prompt';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('This command must be run interactively so the password can be typed at a hidden prompt.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim($this->option('email') ?: text(
            label: 'Email address',
            placeholder: 'ops@example.com',
            required: true,
            validate: fn (string $value): ?string => $this->firstError('email', $value, ['email']),
        )));

        if ($error = $this->firstError('email', $email, ['email', 'max:150'])) {
            $this->components->error($error);

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null && $existing->role !== Role::PlatformAdmin) {
            $this->components->error("{$email} already belongs to a facility account. Use a different email for the platform administrator.");

            return self::FAILURE;
        }

        if ($existing !== null) {
            if (! confirm(label: "{$existing->name} is already a platform administrator. Reset their password?", default: false)) {
                $this->components->info('Nothing changed.');

                return self::SUCCESS;
            }

            $existing->update([
                'password' => $this->askForPassword(),
                'is_active' => true,
            ]);

            $this->components->info("Password reset for {$existing->email}. The account is active.");

            return self::SUCCESS;
        }

        $name = trim($this->option('name') ?: text(
            label: 'Full name',
            required: true,
            validate: fn (string $value): ?string => $this->firstError('name', $value, ['max:150']),
        ));

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $this->askForPassword(),
            'role' => Role::PlatformAdmin,
            'facility_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->components->info("Platform administrator {$user->email} created. Sign in at ".route('login').'.');

        return self::SUCCESS;
    }

    /**
     * Ask for the password twice at hidden prompts until both match and it meets the password rules.
     */
    private function askForPassword(): string
    {
        while (true) {
            $password = password(
                label: 'Password',
                required: true,
                validate: fn (string $value): ?string => $this->firstError('password', $value, [Password::defaults()]),
                hint: 'Typing is hidden.',
            );

            if (password(label: 'Confirm password', required: true) === $password) {
                return $password;
            }

            $this->components->error('The passwords do not match. Try again.');
        }
    }

    /**
     * @param  list<mixed>  $rules
     */
    private function firstError(string $attribute, string $value, array $rules): ?string
    {
        return Validator::make([$attribute => $value], [$attribute => $rules])->errors()->first($attribute) ?: null;
    }
}
