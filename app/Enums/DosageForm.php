<?php

namespace App\Enums;

enum DosageForm: string
{
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case OralLiquid = 'oral_liquid';
    case BulkLiquid = 'bulk_liquid';
    case Injection = 'injection';
    case Infusion = 'infusion';
    case Topical = 'topical';
    case Drops = 'drops';
    case Inhaler = 'inhaler';
    case Powder = 'powder';
    case Suppository = 'suppository';
    case Consumable = 'consumable';
    case Reagent = 'reagent';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tablet => 'Tablet',
            self::Capsule => 'Capsule',
            self::OralLiquid => 'Syrup or suspension (whole bottle)',
            self::BulkLiquid => 'Mixture measured out (ml)',
            self::Injection => 'Injection (ampoule or vial)',
            self::Infusion => 'IV fluid',
            self::Topical => 'Cream, ointment or gel',
            self::Drops => 'Eye, ear or nose drops',
            self::Inhaler => 'Inhaler',
            self::Powder => 'Powder (sachet or tin)',
            self::Suppository => 'Suppository or pessary',
            self::Consumable => 'Medical supply',
            self::Reagent => 'Lab reagent',
            self::Other => 'Other',
        };
    }

    /**
     * A short name for badges and lists.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::OralLiquid => 'Syrup',
            self::BulkLiquid => 'Mixture',
            self::Injection => 'Injection',
            self::Infusion => 'IV fluid',
            self::Topical => 'Topical',
            self::Drops => 'Drops',
            self::Powder => 'Powder',
            self::Suppository => 'Suppository',
            self::Consumable => 'Supply',
            default => $this->label(),
        };
    }

    /**
     * The unit stock is counted and dispensed in, and the pack it usually comes in.
     *
     * @return array{unit: string, unit_content: ?string, pack_unit: ?string, pack_size: int}
     */
    public function defaults(): array
    {
        return match ($this) {
            self::Tablet => ['unit' => 'tablet', 'unit_content' => null, 'pack_unit' => 'tin', 'pack_size' => 1000],
            self::Capsule => ['unit' => 'capsule', 'unit_content' => null, 'pack_unit' => 'tin', 'pack_size' => 500],
            self::OralLiquid => ['unit' => 'bottle', 'unit_content' => '100 ml', 'pack_unit' => 'carton', 'pack_size' => 50],
            self::BulkLiquid => ['unit' => 'ml', 'unit_content' => null, 'pack_unit' => 'can', 'pack_size' => 5000],
            self::Injection => ['unit' => 'vial', 'unit_content' => null, 'pack_unit' => 'box', 'pack_size' => 10],
            self::Infusion => ['unit' => 'bottle', 'unit_content' => '500 ml', 'pack_unit' => 'carton', 'pack_size' => 20],
            self::Topical => ['unit' => 'tube', 'unit_content' => '15 g', 'pack_unit' => 'box', 'pack_size' => 12],
            self::Drops => ['unit' => 'bottle', 'unit_content' => '10 ml', 'pack_unit' => 'box', 'pack_size' => 12],
            self::Inhaler => ['unit' => 'inhaler', 'unit_content' => '200 doses', 'pack_unit' => null, 'pack_size' => 1],
            self::Powder => ['unit' => 'sachet', 'unit_content' => null, 'pack_unit' => 'box', 'pack_size' => 100],
            self::Suppository => ['unit' => 'suppository', 'unit_content' => null, 'pack_unit' => 'box', 'pack_size' => 10],
            self::Consumable => ['unit' => 'piece', 'unit_content' => null, 'pack_unit' => 'box', 'pack_size' => 100],
            self::Reagent => ['unit' => 'test', 'unit_content' => null, 'pack_unit' => 'kit', 'pack_size' => 25],
            self::Other => ['unit' => 'unit', 'unit_content' => null, 'pack_unit' => null, 'pack_size' => 1],
        };
    }

    /**
     * How the pharmacist hands it over: counted out of a bulk pack, measured, or given whole.
     */
    public function handling(): string
    {
        return match ($this) {
            self::Tablet, self::Capsule => 'count',
            self::BulkLiquid => 'measure',
            default => 'whole',
        };
    }

    /**
     * The likely form when only the category and unit are known.
     */
    public static function guess(string $category, ?string $unit = null): self
    {
        return match (true) {
            $category === 'consumable' => self::Consumable,
            $category === 'reagent' => self::Reagent,
            $unit === null => self::Tablet,
            in_array($unit, ['tablet', 'tab'], true) => self::Tablet,
            in_array($unit, ['capsule', 'cap'], true) => self::Capsule,
            in_array($unit, ['ml', 'litre'], true) => self::BulkLiquid,
            in_array($unit, ['ampoule', 'vial'], true) => self::Injection,
            in_array($unit, ['tube'], true) => self::Topical,
            in_array($unit, ['sachet'], true) => self::Powder,
            default => self::Other,
        };
    }
}
