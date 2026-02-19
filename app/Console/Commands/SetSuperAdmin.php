<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserModel;

class SetSuperAdmin extends Command
{
    protected $signature   = 'superadmin:set {email}';
    protected $description = 'Promote a user to super_admin by email';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = UserModel::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email: {$email}");
            return Command::FAILURE;
        }

        if ($user->role === 'super_admin') {
            $this->info("{$user->name} is already a super_admin.");
            return Command::SUCCESS;
        }

        $user->role = 'super_admin';
        $user->save();

        $this->info("✓ {$user->name} ({$email}) has been promoted to super_admin.");
        return Command::SUCCESS;
    }
}
