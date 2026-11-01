<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\UniqueAccountEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'rdsa:create-admin';

    protected $description = 'Créer le premier compte administrateur RDSA';

    public function handle(): int
    {
        if (User::where('is_admin', true)->exists()) {
            $this->error('Un administrateur existe déjà.');

            return self::FAILURE;
        }
        $data = [
            'name' => $this->ask('Nom complet'),
            'email' => mb_strtolower(trim((string) $this->ask('Adresse e-mail'))),
            'password' => $this->secret('Mot de passe (12 caractères, majuscule, minuscule, chiffre et symbole)'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', new UniqueAccountEmail],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $user = new User($data);
        $user->is_admin = true;
        $user->is_super_admin = true;
        $user->save();
        $this->info('Administrateur créé. Connexion : /admin/login');

        return self::SUCCESS;
    }
}
