<?php

namespace Database\Seeders;

use App\Models\Icd10Code;
use Illuminate\Database\Seeder;

class Icd10Seeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['B50.9', 'Plasmodium falciparum malaria, unspecified', 'Infectious'],
            ['B54', 'Unspecified malaria', 'Infectious'],
            ['J06.9', 'Acute upper respiratory infection, unspecified', 'Respiratory'],
            ['J18.9', 'Pneumonia, unspecified organism', 'Respiratory'],
            ['J22', 'Unspecified acute lower respiratory infection', 'Respiratory'],
            ['J00', 'Acute nasopharyngitis', 'Respiratory'],
            ['J45.9', 'Asthma, unspecified', 'Respiratory'],
            ['A09', 'Infectious gastroenteritis and colitis', 'Infectious'],
            ['A01.0', 'Typhoid fever', 'Infectious'],
            ['A06.0', 'Acute amoebic dysentery', 'Infectious'],
            ['A16.9', 'Respiratory tuberculosis, unspecified', 'Infectious'],
            ['B20', 'Human immunodeficiency virus disease', 'Infectious'],
            ['A90', 'Dengue fever', 'Infectious'],
            ['B01.9', 'Varicella without complication', 'Infectious'],
            ['B77.9', 'Ascariasis, unspecified', 'Infectious'],
            ['E11.9', 'Type 2 diabetes mellitus without complications', 'Endocrine'],
            ['I10', 'Essential hypertension', 'Circulatory'],
            ['N39.0', 'Urinary tract infection', 'Genitourinary'],
            ['L30.9', 'Dermatitis, unspecified', 'Skin'],
            ['K29.7', 'Gastritis, unspecified', 'Digestive'],
            ['H10.9', 'Conjunctivitis, unspecified', 'Eye'],
            ['R50.9', 'Fever, unspecified', 'Symptoms'],
            ['E86.0', 'Dehydration', 'Endocrine'],
            ['O80', 'Encounter for full-term uncomplicated delivery', 'Pregnancy'],
            ['Z34.9', 'Supervision of normal pregnancy, unspecified', 'Pregnancy'],
            ['O21.0', 'Mild hyperemesis gravidarum', 'Pregnancy'],
            ['K35.80', 'Unspecified acute appendicitis', 'Digestive'],
            ['G40.909', 'Epilepsy, unspecified', 'Nervous'],
            ['S09.90', 'Unspecified injury of head', 'Injury'],
            ['N92.0', 'Excessive and frequent menstruation', 'Genitourinary'],
        ];

        foreach ($codes as [$code, $title, $chapter]) {
            Icd10Code::query()->updateOrCreate(
                ['code' => $code],
                ['title' => $title, 'chapter' => $chapter],
            );
        }
    }
}
