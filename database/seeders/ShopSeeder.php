<?php

namespace Database\Seeders;

use App\Models\ShopCategory;
use App\Models\ShopItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        // Delete old incorrect categories
        ShopCategory::whereIn('slug', ['virtual-money', 'g-coin', 'packages'])->delete();
        
        // Create categories
        $currencyCategory = ShopCategory::firstOrCreate(
            ['slug' => 'valuta'],
            [
                'name' => 'ვალუტა',
                'description' => 'ვალუტის კონვერტაცია',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $transportCategory = ShopCategory::firstOrCreate(
            ['slug' => 'transport'],
            [
                'name' => 'ტრანსპორტი',
                'description' => 'მანქანები და სხვა პირადი ტრანსპორტი',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $miscCategory = ShopCategory::firstOrCreate(
            ['slug' => 'miscellaneous'],
            [
                'name' => 'სხვადასხვა',
                'description' => 'სხვადასხვა სერვიisები და კუპონები',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        // Currency items
        ShopItem::firstOrCreate(
            ['name' => 'ვერტუალური ფული'],
            [
                'category_id' => $currencyCategory->id,
                'description' => '2,000,000 ვერტუალური ფული',
                'price' => 1,
                'currency_type' => 2, // Money
                'quantity' => 2000000,
                'is_active' => true,
                'sort_order' => 1,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'G-COIN'],
            [
                'category_id' => $currencyCategory->id,
                'description' => '1 G-COIN პრემიუმ ვალუტა',
                'price' => 1,
                'currency_type' => 1, // Coins
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 2,
                'max_quantity_per_purchase' => 1,
            ]
        );

        // Transport items
        ShopItem::firstOrCreate(
            ['name' => 'Mercedes Benz W211'],
            [
                'category_id' => $transportCategory->id,
                'description' => 'ფასდაკლებული ფასით',
                'price' => 30,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 3,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Mercedes Benz W124'],
            [
                'category_id' => $transportCategory->id,
                'description' => 'კლასიკური ჯდომი',
                'price' => 40,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 4,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Bugatti Bolide'],
            [
                'category_id' => $transportCategory->id,
                'description' => 'სპორტული კვადროციკლი',
                'price' => 30,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 5,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Toyota Supra MK4'],
            [
                'category_id' => $transportCategory->id,
                'description' => 'განთქმული მოდელი',
                'price' => 50,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 6,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Nissan GT-R R34'],
            [
                'category_id' => $transportCategory->id,
                'description' => 'ამბიციური მოდელი',
                'price' => 45,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 7,
                'max_quantity_per_purchase' => 1,
            ]
        );

        // Miscellaneous items
        ShopItem::firstOrCreate(
            ['name' => 'VIP კუპონი'],
            [
                'category_id' => $miscCategory->id,
                'description' => 'გამოკრეფილი სტატუსი',
                'price' => 20,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 8,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'სახლი-მანქანა'],
            [
                'category_id' => $miscCategory->id,
                'description' => 'პირადი უძრავი სიკეთე',
                'price' => 100,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 9,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Unmute კუპონი'],
            [
                'category_id' => $miscCategory->id,
                'description' => 'დამუიმოვის აღდგენა',
                'price' => 5,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 10,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Unjail კუპონი'],
            [
                'category_id' => $miscCategory->id,
                'description' => 'ციხიდან გამოთავისუფლება',
                'price' => 5,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 11,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Unwarn კუპონი'],
            [
                'category_id' => $miscCategory->id,
                'description' => 'გაფრთხოების ამოღება',
                'price' => 10,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 12,
                'max_quantity_per_purchase' => 1,
            ]
        );

        ShopItem::firstOrCreate(
            ['name' => 'Unban კუპონი'],
            [
                'category_id' => $miscCategory->id,
                'description' => 'აკრძალვის მოხსნა',
                'price' => 15,
                'currency_type' => 2, // Money
                'quantity' => 1,
                'is_active' => true,
                'sort_order' => 13,
                'max_quantity_per_purchase' => 1,
            ]
        );
    }
}
