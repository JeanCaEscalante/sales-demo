<?php

namespace Database\Seeders;

use App\Models\Category; // Importación del modelo
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    protected $categories = [
        [
            'category_name' => 'Frutas y Verduras',
            'description' => 'Productos frescos, vegetales y frutas de temporada.',
        ],
        [
            'category_name' => 'Lácteos y Quesos',
            'description' => 'Leche, yogures, mantequillas y variedad de quesos.',
        ],
        [
            'category_name' => 'Carnicería',
            'description' => 'Cortes de res, cerdo, pollo y embutidos frescos.',
        ],
        [
            'category_name' => 'Abarrotes',
            'description' => 'Arroz, aceites, granos, pastas y productos no perecederos.',
        ],
        [
            'category_name' => 'Bebidas',
            'description' => 'Refrescos, aguas, jugos y bebidas energizantes.',
        ],
        [
            'category_name' => 'Snacks y Galletas',
            'description' => 'Botanas saladas, galletas dulces y golosinas.',
        ],
        [
            'category_name' => 'Limpieza',
            'description' => 'Detergentes, desinfectantes y artículos para el hogar.',
        ],
        [
            'category_name' => 'Cuidado Personal',
            'description' => 'Jabones, shampoo, cremas y artículos de higiene.',
        ],
        [
            'category_name' => 'Panadería',
            'description' => 'Pan fresco del día, bollería y repostería.',
        ],
        [
            'category_name' => 'Congelados',
            'description' => 'Alimentos precocidos, helados y verduras congeladas.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::insert($this->categories);
    }
}
