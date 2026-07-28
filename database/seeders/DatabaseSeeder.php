<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the Posive F&B application's database.
     */
    public function run(): void
    {
        // 1. Seed Users (Admin, Manager, Cashier)
        $admin = User::firstOrCreate(
            ['email' => 'admin@posive.id'],
            [
                'name' => 'Administrator Posive',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@posive.id'],
            [
                'name' => 'Manager Resto',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );

        $cashier = User::firstOrCreate(
            ['email' => 'cashier@posive.id'],
            [
                'name' => 'Kasir Utama',
                'password' => Hash::make('password'),
                'role' => 'cashier',
            ]
        );

        // 2. Seed F&B Categories
        $categoriesData = [
            ['name' => 'Makanan Utama', 'slug' => 'makanan-utama'],
            ['name' => 'Cemilan', 'slug' => 'cemilan'],
            ['name' => 'Minuman', 'slug' => 'minuman'],
            ['name' => 'Paket Hemat', 'slug' => 'paket-hemat'],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $categories[$catData['slug']] = Category::firstOrCreate(
                ['slug' => $catData['slug']],
                ['name' => $catData['name']]
            );
        }

        // 3. Seed Products with Indonesian Rupiah Prices & Initial Stocks
        $products = [
            // Makanan Utama
            [
                'category_id' => $categories['makanan-utama']->id,
                'name' => 'Nasi Goreng Spesial Posive',
                'sku' => 'MK-001',
                'price' => 28000.00,
                'cost_price' => 15000.00,
                'stock' => 50,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['makanan-utama']->id,
                'name' => 'Ayam Bakar Madu',
                'sku' => 'MK-002',
                'price' => 32000.00,
                'cost_price' => 18000.00,
                'stock' => 40,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['makanan-utama']->id,
                'name' => 'Mie Goreng Seafood',
                'sku' => 'MK-003',
                'price' => 30000.00,
                'cost_price' => 16000.00,
                'stock' => 35,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1612927601601-6638404737ce?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['makanan-utama']->id,
                'name' => 'Soto Ayam Kampung',
                'sku' => 'MK-004',
                'price' => 25000.00,
                'cost_price' => 12000.00,
                'stock' => 30,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1572656631137-7935297eff55?w=600&auto=format&fit=crop',
            ],

            // Cemilan
            [
                'category_id' => $categories['cemilan']->id,
                'name' => 'Kentang Goreng Keju',
                'sku' => 'CM-001',
                'price' => 18000.00,
                'cost_price' => 8000.00,
                'stock' => 60,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['cemilan']->id,
                'name' => 'Cireng Crispy Bumbu Rujak',
                'sku' => 'CM-002',
                'price' => 15000.00,
                'cost_price' => 6000.00,
                'stock' => 45,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['cemilan']->id,
                'name' => 'Roti Bakar Cokelat Keju',
                'sku' => 'CM-003',
                'price' => 20000.00,
                'cost_price' => 9000.00,
                'stock' => 40,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1584776296944-ab6fb57b0bdd?w=600&auto=format&fit=crop',
            ],

            // Minuman
            [
                'category_id' => $categories['minuman']->id,
                'name' => 'Es Teh Manis',
                'sku' => 'MN-001',
                'price' => 6000.00,
                'cost_price' => 2000.00,
                'stock' => 200,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['minuman']->id,
                'name' => 'Kopi Susu Gula Aren',
                'sku' => 'MN-002',
                'price' => 18000.00,
                'cost_price' => 7000.00,
                'stock' => 100,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['minuman']->id,
                'name' => 'Es Jeruk Peras',
                'sku' => 'MN-003',
                'price' => 10000.00,
                'cost_price' => 3500.00,
                'stock' => 120,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=600&auto=format&fit=crop',
            ],

            // Paket Hemat
            [
                'category_id' => $categories['paket-hemat']->id,
                'name' => 'Paket Nasi Goreng + Es Teh',
                'sku' => 'PK-001',
                'price' => 30000.00,
                'cost_price' => 16000.00,
                'stock' => 50,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&auto=format&fit=crop',
            ],
            [
                'category_id' => $categories['paket-hemat']->id,
                'name' => 'Paket Ayam Bakar + Kopi Susu',
                'sku' => 'PK-002',
                'price' => 45000.00,
                'cost_price' => 24000.00,
                'stock' => 35,
                'is_active' => true,
                'image_url' => 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=600&auto=format&fit=crop',
            ],
        ];

        foreach ($products as $prodData) {
            Product::firstOrCreate(
                ['sku' => $prodData['sku']],
                $prodData
            );
        }

        // 4. Seed Tables (Indoor 101-105, Terrace 201-205, VIP 301-303)
        $tables = [
            // Indoor
            ['number' => '101', 'area' => 'Indoor', 'status' => 'available', 'seats' => 4],
            ['number' => '102', 'area' => 'Indoor', 'status' => 'available', 'seats' => 4],
            ['number' => '103', 'area' => 'Indoor', 'status' => 'available', 'seats' => 2],
            ['number' => '104', 'area' => 'Indoor', 'status' => 'available', 'seats' => 6],
            ['number' => '105', 'area' => 'Indoor', 'status' => 'available', 'seats' => 4],

            // Terrace
            ['number' => '201', 'area' => 'Terrace', 'status' => 'available', 'seats' => 2],
            ['number' => '202', 'area' => 'Terrace', 'status' => 'available', 'seats' => 4],
            ['number' => '203', 'area' => 'Terrace', 'status' => 'available', 'seats' => 4],
            ['number' => '204', 'area' => 'Terrace', 'status' => 'available', 'seats' => 2],
            ['number' => '205', 'area' => 'Terrace', 'status' => 'available', 'seats' => 6],

            // VIP
            ['number' => '301', 'area' => 'VIP', 'status' => 'available', 'seats' => 8],
            ['number' => '302', 'area' => 'VIP', 'status' => 'available', 'seats' => 10],
            ['number' => '303', 'area' => 'VIP', 'status' => 'available', 'seats' => 12],
        ];

        foreach ($tables as $tblData) {
            Table::firstOrCreate(
                ['number' => $tblData['number']],
                $tblData
            );
        }
    }
}
