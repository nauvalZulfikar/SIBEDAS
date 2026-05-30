<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mapping place_type Google Places → fungsi_bg (target = retribution_estimates.fungsi_bg).
 *
 * Target fungsi_bg yang valid di retribution_estimates:
 *   Fungsi Hunian (9.181,50) · Fungsi Usaha (28.000/44.321) ·
 *   Fungsi Usaha (UMKM) (31.659) · Fungsi Sosial Budaya (18.995) ·
 *   Fungsi Keagamaan (0 — rumah ibadah exempt) · Campuran Besar (50.652) ·
 *   Campuran Kecil (37.990).
 *
 * Dilengkapi melampaui daftar brief untuk menutup place_type frekuensi tinggi
 * yang benar-benar ada di property_enrichment (220 distinct). place_type yang
 * tak ter-mapping akan jatuh ke default Hunian unenriched di RefreshKecamatanStats.
 */
class PlaceTypeFunctionMappingSeeder extends Seeder
{
    public function run(): void
    {
        $auto = [
            'Fungsi Hunian' => [
                'house', 'apartment_building', 'housing_complex', 'lodging',
                'real_estate_agency', 'hotel', 'motel', 'hostel', 'guest_house',
                'private_guest_room', 'resort_hotel', 'bed_and_breakfast', 'campground',
            ],
            'Fungsi Usaha' => [
                // perdagangan & jasa
                'restaurant', 'cafe', 'coffee_shop', 'store', 'shop', 'supermarket',
                'convenience_store', 'bakery', 'clothing_store', 'electronics_store',
                'hardware_store', 'building_materials_store', 'furniture_store', 'book_store',
                'jewelry_store', 'pharmacy', 'gas_station', 'car_dealer', 'car_repair',
                'car_wash', 'bank', 'atm', 'finance', 'insurance_agency', 'accounting',
                'lawyer', 'beauty_salon', 'hair_care', 'spa', 'gym', 'bar', 'night_club',
                'internet_cafe', 'laundry', 'post_office', 'courier_service', 'travel_agency',
                'corporate_office', 'office', 'grocery_store', 'food_store', 'home_goods_store',
                'wholesaler', 'supplier', 'deli', 'diner', 'snack_bar', 'noodle_shop',
                'cake_shop', 'tailor', 'pet_store', 'shipping_service', 'sports_club',
                'indonesian_restaurant', 'chicken_restaurant', 'brunch_restaurant',
                'fast_food_restaurant', 'meal_takeaway', 'meal_delivery', 'liquor_store',
                'shoe_store', 'florist', 'hardware', 'auto_parts_store', 'bicycle_store',
                'movie_theater', 'amusement_center', 'general_contractor', 'manufacturer',
                'distributor', 'wholesale_market', 'service', 'photographer',
                'real_estate', 'consultant', 'printing', 'workshop',
            ],
            'Fungsi Keagamaan' => [
                'mosque', 'church', 'hindu_temple', 'place_of_worship', 'cemetery',
                'funeral_home', 'synagogue', 'buddhist_temple',
            ],
            'Fungsi Sosial Budaya' => [
                'school', 'primary_school', 'secondary_school', 'university', 'library',
                'hospital', 'doctor', 'dentist', 'clinic', 'medical_clinic', 'veterinary_care',
                'museum', 'art_gallery', 'tourist_attraction', 'park', 'stadium',
                'community_center', 'local_government_office', 'government_office', 'courthouse',
                'embassy', 'police', 'fire_station', 'educational_institution', 'health',
                'association_or_organization', 'public_school', 'sports_complex',
                'kindergarten', 'preschool', 'health_post',
            ],
            'Campuran Besar' => [
                'shopping_mall', 'department_store',
            ],
            'Campuran Kecil' => [
                'market', 'wholesale_market_hall',
            ],
            // Industri → "Fungsi Usaha" sementara (brief: nunggu tarif khusus).
            'Fungsi Usaha (UMKM)' => [
                'factory', 'warehouse', 'storage', 'moving_company',
            ],
        ];

        // Ambigu — masuk enriched tapi dipaksa default Hunian di refresh (hindari double-count Usaha diam-diam).
        $manualReview = [
            'point_of_interest', 'establishment', 'premise', 'subpremise', 'locality',
            'food', 'finance', 'health_and_beauty', 'point_of_interest_establishment',
        ];

        $rows = [];
        $now = now();
        foreach ($auto as $fungsi => $types) {
            foreach ($types as $t) {
                $rows[$t] = ['place_type' => $t, 'fungsi_bg' => $fungsi, 'confidence' => 'auto', 'notes' => null, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        foreach ($manualReview as $t) {
            // 'finance'/'food' muncul di dua list → manual_review menang (ambigu).
            $rows[$t] = ['place_type' => $t, 'fungsi_bg' => 'Fungsi Hunian', 'confidence' => 'manual_review', 'notes' => 'ambigu — default Hunian sampai direview', 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('place_type_function_mapping')->upsert(
            array_values($rows), ['place_type'], ['fungsi_bg', 'confidence', 'notes', 'updated_at']
        );

        $this->command?->info('place_type_function_mapping seeded: '.count($rows).' rows.');
    }
}
