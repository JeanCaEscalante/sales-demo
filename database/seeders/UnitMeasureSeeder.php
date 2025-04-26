<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UnitMeasure;

class UnitMeasureSeeder extends Seeder
{
    protected $units = [
        [
           'constant' => 'UNIT_DEFAULT',
           'description' => 'Unidades (por defecto)',
        ],
        
        // Unidades de Peso
        [
            'constant' => 'UNIT_GRAMS',
            'description' => 'Gramos',
        ],
        [
            'constant' => 'UNIT_KILOGRAMS',
            'description' => 'Kilogramos',
        ],
        [
            'constant' => 'UNIT_POUNDS',
            'description' => 'Libras',
        ],
        [
            'constant' => 'UNIT_OUNCES',
            'description' => 'Onzas',
        ],

        // Unidades de Volumen
        [
            'constant' => 'UNIT_LITERS',
            'description' => 'Litros',
        ],
        [
            'constant' => 'UNIT_MILLILITERS',
            'description' => 'Mililitros',
        ],
        [
            'constant' => 'UNIT_CUBICMETERS',
            'description' => 'Metros Cúbicos',
        ],
        [
            'constant' => 'UNIT_GALLONS',
            'description' => 'Galones',
        ],

        // Unidades de Longitud
        [
            'constant' => 'UNIT_METERS',
            'description' => 'Metros',
        ],
        [
            'constant' => 'UNIT_CENTIMITERS',
            'description' => 'Centímetros',
        ],
        [
            'constant' => 'UNIT_INCHES',
            'description' => 'Pulgadas',
        ],

        //Otras unidades
        [  
            'constant' => 'UNIT_HOURS',
            'description' => 'Horas',
        ],
        [
            'constant' => 'UNIT_OTHER',
            'description' => 'Otros',
        ]
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UnitMeasure::insert($this->units);
    }
}
