<?php
/**
 * Spaceship.com Domain Registrar - TLD Requirements Helper
 *
 * This class provides TLD-specific requirements and additional fields
 * for domain registration with certain TLDs.
 *
 * @copyright 2026
 * @license MIT
 */

namespace WHMCS\Module\Registrar\Spaceship;

class TldRequirements
{
    /**
     * TLDs that require specific additional fields
     */
    protected static $tldRequirements = [
        // UK domains
        'co.uk' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'IND' => 'Individual',
                    'LTD' => 'UK Limited Company',
                    'PLC' => 'UK Public Limited Company',
                    'LLP' => 'UK Limited Liability Partnership',
                    'RCHAR' => 'UK Registered Charity',
                    'PTNR' => 'Partnership',
                    'STRA' => 'Sole Trader',
                    'OTHER' => 'Other',
                ],
                'required' => true,
            ],
            'companyNumber' => [
                'type' => 'text',
                'required' => false,
                'description' => 'Company registration number (for LTD/PLC)',
            ],
        ],
        'uk' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'IND' => 'Individual',
                    'LTD' => 'UK Limited Company',
                    'PLC' => 'UK Public Limited Company',
                ],
                'required' => true,
            ],
        ],

        // EU domains
        'eu' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'NATURAL_PERSON' => 'Natural Person',
                    'CORPORATION' => 'Corporation',
                    'REGISTERED_ORGANIZATION' => 'Registered Organization',
                ],
                'required' => true,
            ],
            'euCountry' => [
                'type' => 'dropdown',
                'options' => self::getEuCountries(),
                'required' => true,
                'description' => 'Country of residence/establishment (must be in EU)',
            ],
        ],

        // Australian domains
        'com.au' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'ABN' => 'Australian Business Number',
                    'ACN' => 'Australian Company Number',
                    'TM' => 'Trademark',
                ],
                'required' => true,
            ],
            'registrantNumber' => [
                'type' => 'text',
                'required' => true,
                'description' => 'ABN/ACN/TM Number',
            ],
        ],
        'net.au' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'ABN' => 'Australian Business Number',
                    'ACN' => 'Australian Company Number',
                ],
                'required' => true,
            ],
            'registrantNumber' => [
                'type' => 'text',
                'required' => true,
                'description' => 'ABN/ACN Number',
            ],
        ],

        // Canadian domains
        'ca' => [
            'cprCategory' => [
                'type' => 'dropdown',
                'options' => [
                    'CCT' => 'Canadian Corporation',
                    'CCO' => 'Canadian Company',
                    'RES' => 'Canadian Resident',
                    'GOV' => 'Government Entity',
                    'EDU' => 'Educational Institution',
                    'ASS' => 'Association',
                    'HOP' => 'Hospital',
                    'PRT' => 'Political Party',
                    'TDM' => 'Trademark Owner',
                    'TRD' => 'Trade Union',
                    'PLT' => 'Political Party',
                    'LAM' => 'Library/Archive/Museum',
                    'TRS' => 'Trust',
                    'ABO' => 'Aboriginal Peoples',
                    'INB' => 'Indian Band',
                    'LGR' => 'Legal Representative',
                    'OMK' => 'Official Mark',
                    'MAJ' => 'Her Majesty the Queen',
                ],
                'required' => true,
            ],
        ],

        // German domains
        'de' => [
            'adminContact' => [
                'type' => 'info',
                'description' => 'Admin contact must have a complete postal address',
                'required' => false,
            ],
        ],

        // Spanish domains
        'es' => [
            'identificationType' => [
                'type' => 'dropdown',
                'options' => [
                    'DNI' => 'DNI/NIF (Spanish National ID)',
                    'NIE' => 'NIE (Foreign National ID)',
                    'CIF' => 'CIF (Company Tax ID)',
                    'OTHER' => 'Passport/Other',
                ],
                'required' => true,
            ],
            'identificationNumber' => [
                'type' => 'text',
                'required' => true,
                'description' => 'Identification number',
            ],
        ],

        // French domains
        'fr' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'INDIVIDUAL' => 'Individual',
                    'COMPANY' => 'Company',
                    'ASSOCIATION' => 'Association',
                ],
                'required' => true,
            ],
            'sirenNumber' => [
                'type' => 'text',
                'required' => false,
                'description' => 'SIREN number (for companies)',
            ],
        ],

        // Italian domains
        'it' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    '1' => 'Natural Person (Italian Citizen)',
                    '2' => 'Company (Italian)',
                    '3' => 'Non-EU Natural Person',
                    '4' => 'Non-EU Company',
                    '5' => 'Entity (Italian)',
                    '6' => 'Other (Italian)',
                    '7' => 'Foreigner living in Italy',
                ],
                'required' => true,
            ],
            'taxId' => [
                'type' => 'text',
                'required' => true,
                'description' => 'Tax ID / Codice Fiscale',
            ],
        ],

        // Swedish domains
        'se' => [
            'identificationNumber' => [
                'type' => 'text',
                'required' => true,
                'description' => 'Personal ID or Organization number',
            ],
        ],

        // Finnish domains
        'fi' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    '1' => 'Private person (Finnish)',
                    '2' => 'Company (Finnish)',
                    '3' => 'Private person (foreign)',
                    '4' => 'Company (foreign)',
                ],
                'required' => true,
            ],
            'businessId' => [
                'type' => 'text',
                'required' => false,
                'description' => 'Business ID (for companies)',
            ],
        ],

        // Dutch domains
        'nl' => [
            'legalForm' => [
                'type' => 'dropdown',
                'options' => [
                    'BV' => 'BV (Private Limited Company)',
                    'NV' => 'NV (Public Limited Company)',
                    'PERSOON' => 'Private Person',
                    'ANDERS' => 'Other',
                ],
                'required' => true,
            ],
        ],

        // Belgian domains
        'be' => [
            'language' => [
                'type' => 'dropdown',
                'options' => [
                    'en' => 'English',
                    'fr' => 'French',
                    'nl' => 'Dutch',
                    'de' => 'German',
                ],
                'required' => true,
            ],
        ],

        // US domains
        'us' => [
            'nexusCategory' => [
                'type' => 'dropdown',
                'options' => [
                    'C11' => 'US Citizen',
                    'C12' => 'US Permanent Resident',
                    'C21' => 'US Organization',
                    'C31' => 'Foreign Entity with US Presence',
                    'C32' => 'Foreign Entity with US Products/Services',
                ],
                'required' => true,
            ],
            'nexusValidator' => [
                'type' => 'text',
                'required' => false,
                'description' => 'State/Application Purpose (for C31/C32)',
            ],
        ],

        // Brazilian domains
        'com.br' => [
            'cpfCnpj' => [
                'type' => 'text',
                'required' => true,
                'description' => 'CPF or CNPJ number',
            ],
        ],

        // Argentinian domains
        'com.ar' => [
            'cuit' => [
                'type' => 'text',
                'required' => true,
                'description' => 'CUIT number',
            ],
        ],

        // Indian domains
        'in' => [
            'panNumber' => [
                'type' => 'text',
                'required' => false,
                'description' => 'PAN number (for Indian registrants)',
            ],
        ],

        // Japanese domains
        'jp' => [
            'registrantType' => [
                'type' => 'dropdown',
                'options' => [
                    'INDIVIDUAL' => 'Individual',
                    'COMPANY' => 'Company',
                ],
                'required' => true,
            ],
        ],

        // Asian domains requiring local presence
        'cn' => [
            'idCardNumber' => [
                'type' => 'text',
                'required' => true,
                'description' => 'ID Card or Business License Number',
            ],
        ],

        // Russian domains
        'ru' => [
            'passportNumber' => [
                'type' => 'text',
                'required' => false,
                'description' => 'Passport number (for individuals)',
            ],
            'inn' => [
                'type' => 'text',
                'required' => false,
                'description' => 'INN (for companies)',
            ],
        ],
    ];

    /**
     * Get requirements for a specific TLD.
     *
     * @param string $tld
     * @return array
     */
    public static function getRequirements($tld)
    {
        $tld = strtolower(ltrim($tld, '.'));
        return self::$tldRequirements[$tld] ?? [];
    }

    /**
     * Check if a TLD has additional requirements.
     *
     * @param string $tld
     * @return bool
     */
    public static function hasRequirements($tld)
    {
        $tld = strtolower(ltrim($tld, '.'));
        return isset(self::$tldRequirements[$tld]);
    }

    /**
     * Get all TLDs with requirements.
     *
     * @return array
     */
    public static function getAllTldsWithRequirements()
    {
        return array_keys(self::$tldRequirements);
    }

    /**
     * Validate additional fields for a TLD.
     *
     * @param string $tld
     * @param array $fields
     * @return array Errors array (empty if valid)
     */
    public static function validateFields($tld, $fields)
    {
        $errors = [];
        $requirements = self::getRequirements($tld);

        foreach ($requirements as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['required']) && $fieldConfig['required']) {
                if (!isset($fields[$fieldName]) || trim($fields[$fieldName]) === '') {
                    $errors[$fieldName] = "The {$fieldName} field is required for .{$tld} domains";
                }
            }
        }

        return $errors;
    }

    /**
     * Get EU member countries.
     *
     * @return array
     */
    public static function getEuCountries()
    {
        return [
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'HR' => 'Croatia',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'FI' => 'Finland',
            'FR' => 'France',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IT' => 'Italy',
            'LV' => 'Latvia',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ES' => 'Spain',
            'SE' => 'Sweden',
        ];
    }

    /**
     * Get countries that support WHOIS privacy for their ccTLDs.
     *
     * @return array
     */
    public static function getPrivacySupportedTlds()
    {
        return [
            'com', 'net', 'org', 'info', 'biz', 'name', 'pro',
            'co', 'me', 'io', 'ai', 'app', 'dev', 'tech',
            'online', 'site', 'website', 'xyz',
        ];
    }

    /**
     * Check if a TLD supports WHOIS privacy.
     *
     * @param string $tld
     * @return bool
     */
    public static function supportsPrivacy($tld)
    {
        $tld = strtolower(ltrim($tld, '.'));
        return in_array($tld, self::getPrivacySupportedTlds());
    }

    /**
     * Get TLDs that support domain locking.
     *
     * @return array
     */
    public static function getLockSupportedTlds()
    {
        // Most gTLDs support locking, some ccTLDs don't
        $unsupportedTlds = ['de', 'eu', 'uk', 'co.uk', 'org.uk', 'me.uk'];
        
        return function($tld) use ($unsupportedTlds) {
            return !in_array(strtolower(ltrim($tld, '.')), $unsupportedTlds);
        };
    }

    /**
     * Get minimum registration period for a TLD.
     *
     * @param string $tld
     * @return int Years
     */
    public static function getMinRegistrationPeriod($tld)
    {
        $tld = strtolower(ltrim($tld, '.'));
        
        $customPeriods = [
            'uk' => 2,
            'co.uk' => 2,
            'org.uk' => 2,
            'me.uk' => 2,
            'ph' => 2,
        ];

        return $customPeriods[$tld] ?? 1;
    }

    /**
     * Get maximum registration period for a TLD.
     *
     * @param string $tld
     * @return int Years
     */
    public static function getMaxRegistrationPeriod($tld)
    {
        $tld = strtolower(ltrim($tld, '.'));
        
        $customPeriods = [
            'com' => 10,
            'net' => 10,
            'org' => 10,
            'co' => 5,
        ];

        return $customPeriods[$tld] ?? 10;
    }
}
