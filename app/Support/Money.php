<?php

namespace App\Support;

use App\Models\Facility;

class Money
{
    /**
     * Currencies a facility can collect in: East Africa first, then the rest of the world by name.
     * Amounts are stored in whole units, so currencies with cents are collected without them.
     *
     * @var array<string, string>
     */
    public const CURRENCIES = [
        'UGX' => 'Ugandan shilling (UGX)',
        'KES' => 'Kenyan shilling (KES)',
        'TZS' => 'Tanzanian shilling (TZS)',
        'RWF' => 'Rwandan franc (RWF)',
        'BIF' => 'Burundian franc (BIF)',
        'SSP' => 'South Sudanese pound (SSP)',
        'CDF' => 'Congolese franc (CDF)',
        'ETB' => 'Ethiopian birr (ETB)',
        'SOS' => 'Somali shilling (SOS)',
        'DZD' => 'Algerian dinar (DZD)',
        'AOA' => 'Angolan kwanza (AOA)',
        'ARS' => 'Argentine peso (ARS)',
        'AUD' => 'Australian dollar (AUD)',
        'BDT' => 'Bangladeshi taka (BDT)',
        'BWP' => 'Botswana pula (BWP)',
        'BRL' => 'Brazilian real (BRL)',
        'GBP' => 'British pound (GBP)',
        'CAD' => 'Canadian dollar (CAD)',
        'XOF' => 'CFA franc BCEAO (XOF)',
        'XAF' => 'CFA franc BEAC (XAF)',
        'CLP' => 'Chilean peso (CLP)',
        'CNY' => 'Chinese yuan (CNY)',
        'COP' => 'Colombian peso (COP)',
        'CZK' => 'Czech koruna (CZK)',
        'DKK' => 'Danish krone (DKK)',
        'DJF' => 'Djiboutian franc (DJF)',
        'EGP' => 'Egyptian pound (EGP)',
        'ERN' => 'Eritrean nakfa (ERN)',
        'EUR' => 'Euro (EUR)',
        'GMD' => 'Gambian dalasi (GMD)',
        'GHS' => 'Ghanaian cedi (GHS)',
        'GNF' => 'Guinean franc (GNF)',
        'HKD' => 'Hong Kong dollar (HKD)',
        'HUF' => 'Hungarian forint (HUF)',
        'INR' => 'Indian rupee (INR)',
        'IDR' => 'Indonesian rupiah (IDR)',
        'ILS' => 'Israeli new shekel (ILS)',
        'JPY' => 'Japanese yen (JPY)',
        'JOD' => 'Jordanian dinar (JOD)',
        'KWD' => 'Kuwaiti dinar (KWD)',
        'LSL' => 'Lesotho loti (LSL)',
        'LRD' => 'Liberian dollar (LRD)',
        'LYD' => 'Libyan dinar (LYD)',
        'MGA' => 'Malagasy ariary (MGA)',
        'MWK' => 'Malawian kwacha (MWK)',
        'MYR' => 'Malaysian ringgit (MYR)',
        'MRU' => 'Mauritanian ouguiya (MRU)',
        'MUR' => 'Mauritian rupee (MUR)',
        'MXN' => 'Mexican peso (MXN)',
        'MAD' => 'Moroccan dirham (MAD)',
        'MZN' => 'Mozambican metical (MZN)',
        'NAD' => 'Namibian dollar (NAD)',
        'NPR' => 'Nepalese rupee (NPR)',
        'NZD' => 'New Zealand dollar (NZD)',
        'NGN' => 'Nigerian naira (NGN)',
        'NOK' => 'Norwegian krone (NOK)',
        'OMR' => 'Omani rial (OMR)',
        'PKR' => 'Pakistani rupee (PKR)',
        'PHP' => 'Philippine peso (PHP)',
        'PLN' => 'Polish zloty (PLN)',
        'QAR' => 'Qatari riyal (QAR)',
        'RUB' => 'Russian ruble (RUB)',
        'SAR' => 'Saudi riyal (SAR)',
        'SLE' => 'Sierra Leonean leone (SLE)',
        'SGD' => 'Singapore dollar (SGD)',
        'ZAR' => 'South African rand (ZAR)',
        'KRW' => 'South Korean won (KRW)',
        'LKR' => 'Sri Lankan rupee (LKR)',
        'SDG' => 'Sudanese pound (SDG)',
        'SZL' => 'Swazi lilangeni (SZL)',
        'SEK' => 'Swedish krona (SEK)',
        'CHF' => 'Swiss franc (CHF)',
        'THB' => 'Thai baht (THB)',
        'TND' => 'Tunisian dinar (TND)',
        'TRY' => 'Turkish lira (TRY)',
        'AED' => 'UAE dirham (AED)',
        'USD' => 'US dollar (USD)',
        'VND' => 'Vietnamese dong (VND)',
        'ZMW' => 'Zambian kwacha (ZMW)',
        'ZWG' => 'Zimbabwe gold (ZWG)',
    ];

    /**
     * Extra words a currency can be found by, for currencies whose name does not say where it is used.
     *
     * @var array<string, string>
     */
    private const SEARCH_TERMS = [
        'USD' => 'United States America USA dollar $ Ecuador Zimbabwe Liberia',
        'EUR' => 'Europe European Union Germany France Italy Spain Netherlands Belgium Ireland Portugal Austria Finland Greece €',
        'GBP' => 'United Kingdom UK Britain England Scotland Wales sterling £',
        'XOF' => 'West Africa Senegal Ivory Coast Côte d\'Ivoire Mali Burkina Faso Niger Benin Togo Guinea-Bissau',
        'XAF' => 'Central Africa Cameroon Chad Gabon Congo Brazzaville Central African Republic Equatorial Guinea',
        'CHF' => 'Switzerland Liechtenstein',
        'AED' => 'United Arab Emirates Dubai Abu Dhabi',
        'CNY' => 'China renminbi RMB',
        'KRW' => 'Korea',
        'CDF' => 'DRC Democratic Republic of the Congo Kinshasa',
        'SZL' => 'Eswatini Swaziland',
        'ZWG' => 'Zimbabwe',
        'INR' => 'India ₹',
        'JPY' => 'Japan ¥',
        'NGN' => 'Nigeria ₦',
    ];

    /**
     * Number of entries at the start of CURRENCIES that are grouped as East African.
     */
    private const EAST_AFRICAN_COUNT = 9;

    /**
     * Currencies as options for the searchable dropdown, East Africa grouped first.
     *
     * @return list<array{value: string, label: string, group: string, keywords: string}>
     */
    public static function currencyOptions(): array
    {
        return collect(array_keys(self::CURRENCIES))
            ->map(fn (string $code, int $index): array => [
                'value' => $code,
                'label' => self::CURRENCIES[$code],
                'group' => $index < self::EAST_AFRICAN_COUNT ? 'East Africa' : 'Worldwide',
                'keywords' => self::SEARCH_TERMS[$code] ?? '',
            ])
            ->all();
    }

    public static function format(int $amount, ?string $currency = null): string
    {
        return ($currency ?? self::currency()).' '.number_format($amount);
    }

    /**
     * The currency of the facility being worked in, falling back to the platform default.
     */
    public static function currency(): string
    {
        $facility = auth()->user()?->facility
            ?? (FacilityContext::id() !== null ? Facility::query()->find(FacilityContext::id()) : null);

        return $facility?->currency ?? config('hms.currency');
    }
}
