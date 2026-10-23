<?php

namespace Database\Factories;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return ['nombres' => 'Persona', 'apellidos' => 'Prueba', 'correo' => 'prueba-'.bin2hex(random_bytes(8)).'@example.test',
            'password_hash' => Hash::make(bin2hex(random_bytes(24))), 'estado' => EstadoUsuario::ACTIVO, 'fecha_registro' => now()];
    }
}
