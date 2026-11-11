<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates (or resets) a panel administrator. The password is typed by the
 * person running the command and is never shown or stored in plain text.
 */
class CreateAdmin extends Command
{
    protected $signature = 'club:admin {email} {--name=Administración}';

    protected $description = 'Crea o actualiza un usuario administrador del panel';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', 'string', 'min:12']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => (string) $this->option('name'), 'password' => Hash::make($password)],
        );
        $user->syncRoles(['admin']);

        $this->info("Listo: {$email} es administrador.");

        return self::SUCCESS;
    }
}
