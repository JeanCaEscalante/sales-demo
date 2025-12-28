<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitMeasureSeeder extends Seeder
{
    protected $units = [
        [
            'code' => 'pcs',
            'name' => 'Piezas',
        ],
        [
            'code' => 'cj',
            'name' => 'Cajas',
        ],
        [
            'code' => 'und',
            'name' => 'Unidades',
        ],

        // Unidades de Peso
        [
            'code' => 'g',
            'name' => 'Gramos',
        ],
        [
            'code' => 'kg',
            'name' => 'Kilogramos',
        ],
        [
            'code' => 'lb',
            'name' => 'Libras',
        ],
        [
            'code' => 'oz',
            'name' => 'Onzas',
        ],

        // Unidades de Volumen
        [
            'code' => 'L',
            'name' => 'Litros',
        ],
        [
            'code' => 'mL',
            'name' => 'Mililitros',
        ],
        [
            'code' => 'm³',
            'name' => 'Metros Cúbicos',
        ],
        [
            'code' => 'gal',
            'name' => 'Galones',
        ],

        // Unidades de Longitud
        [
            'code' => 'm',
            'name' => 'Metros',
        ],
        [
            'code' => 'cm',
            'name' => 'Centímetros',
        ],
        [
            'code' => 'in',
            'name' => 'Pulgadas',
        ],

        [
            'code' => 'other',
            'name' => 'Otros',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::insert($this->units);
    }
}
