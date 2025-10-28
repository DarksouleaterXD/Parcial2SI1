<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Create an admin user for testing';

    public function handle()
    {
        // Verificar si el usuario admin ya existe
        $adminExists = User::where('email', 'admin@ficct.edu.ec')->first();

        if ($adminExists) {
            $this->info('✅ Usuario admin ya existe: admin@ficct.edu.ec');
            $this->info('   Contraseña: admin123');
            return;
        }

        // Crear usuario admin
        $admin = User::create([
            'nombre' => 'Administrador FICCT',
            'email' => 'admin@ficct.edu.ec',
            'password' => Hash::make('admin123'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->info('✅ Usuario admin creado exitosamente');
        $this->info('   Email: ' . $admin->email);
        $this->info('   Contraseña: admin123');
        $this->info('   Rol: admin');
    }
}
