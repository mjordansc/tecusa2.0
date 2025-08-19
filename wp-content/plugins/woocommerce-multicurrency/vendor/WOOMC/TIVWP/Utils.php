<?php
/**
 * Utils
 *
 * @since 2.4.0
 *
 * Copyright (c) 2024, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP;

/**
 * Class Utils
 *
 * @since 2.4.0
 */
class Utils {

	/**
	 * True if the variable is an array and all its elements are strings.
	 *
	 * @since 2.4.0
	 *
	 * @param array|mixed $maybe_array Variable to check.
	 *
	 * @return bool
	 */
	public static function is_array_of_strings( $maybe_array ): bool {
		if ( ! is_array( $maybe_array ) ) {
			return false;
		}

		foreach ( $maybe_array as $element ) {
			if ( ! is_string( $element ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * True if the variable is an array and it's not empty.
	 *
	 * @since 2.8.0
	 *
	 * @param array|mixed $maybe_array Variable to check.
	 *
	 * @return bool
	 */
	public static function is_not_empty_array( $maybe_array ): bool {
		return is_array( $maybe_array ) && count( $maybe_array ) > 0;
	}

	/**
	 * Substitutes a given locale to its "base" language locale if a substitution is defined.
	 *
	 * @since 2.10.1
	 *
	 * @param string $locale The locale to check for substitution (e.g., 'fr_CA', 'en_GB').
	 *
	 * @return string The substituted locale if found, otherwise the original locale.
	 */
	public static function substitute_locale_to_base( string $locale ): string {

		// This array maps regional locales to a more general or primary locale for the same language.
		// The key is the regional locale, and the value is its base/substituted locale.
		static $locale_substitutions = array(

			// --- French
			// Canadian French to France French
			'fr_CA'          => 'fr_FR',
			// Belgian French to France French
			'fr_BE'          => 'fr_FR',
			// Swiss French to France French
			'fr_CH'          => 'fr_FR',
			// Luxembourg French to France French
			'fr_LU'          => 'fr_FR',

			// --- English
			// Canadian English to US English
			'en_CA'          => 'en_US',
			// British English to US English
			'en_GB'          => 'en_US',
			// Australian English to US English
			'en_AU'          => 'en_US',
			// New Zealand English to US English
			'en_NZ'          => 'en_US',
			// South African English to US English
			'en_ZA'          => 'en_US',
			// Irish English to US English
			'en_IE'          => 'en_US',

			// --- Spanish
			// Mexican Spanish to Spain Spanish
			'es_MX'          => 'es_ES',
			// Argentinian Spanish to Spain Spanish
			'es_AR'          => 'es_ES',
			// Chilean Spanish to Spain Spanish
			'es_CL'          => 'es_ES',
			// Colombian Spanish to Spain Spanish
			'es_CO'          => 'es_ES',
			// Peruvian Spanish to Spain Spanish
			'es_PE'          => 'es_ES',
			// Venezuelan Spanish to Spain Spanish
			'es_VE'          => 'es_ES',
			// US Spanish to Spain Spanish (often desired for content if generic Latin American isn't available)
			'es_US'          => 'es_ES',

			// --- German
			// Austrian German to Germany German
			'de_AT'          => 'de_DE',
			// Swiss German to Germany German
			'de_CH'          => 'de_DE',
			'de_CH_informal' => 'de_DE',

			// --- Portuguese
			// Brazilian Portuguese to Portugal Portuguese (common for content if PT-BR isn't distinct)
			'pt_BR'          => 'pt_PT',

			// --- Italian (less common for regional content differences but included for completeness)
			// Swiss Italian to Italy Italian
			'it_CH'          => 'it_IT',

			// --- Dutch
			// Belgian Dutch (Flemish) to Netherlands Dutch
			'nl_BE'          => 'nl_NL',

			// Chinese (Simplified vs. Traditional is key here)
			// Generally, zh-Hans (Simplified) maps to zh_CN (China mainland).
			// And zh-Hant (Traditional) maps to zh_TW (Taiwan).
			// If you only have one generic "Simplified Chinese" file, zh_CN is often the de facto base.
			// If you only have one generic "Traditional Chinese" file, zh_TW is often the de facto base.
			// Simplified Chinese from Singapore to Mainland China
			'zh_SG'          => 'zh_CN',
			// Traditional Chinese from Hong Kong to Taiwan
			'zh_HK'          => 'zh_TW',
			// Traditional Chinese from Macau to Taiwan
			'zh_MO'          => 'zh_TW',
			// Generic Simplified Chinese to Mainland China
			'zh_Hans'        => 'zh_CN',
			// Generic Traditional Chinese to Taiwan
			'zh_Hant'        => 'zh_TW',

			// --- Japanese
			// Generally doesn't have major regional dialect differences that require content variations,
			// so 'ja_JP' is typically the only specific locale. If content is just 'ja', use 'ja_JP'.
			'ja'             => 'ja_JP',

			// --- Korean
			// Similar to Japanese, 'ko_KR' is the standard.
			'ko'             => 'ko_KR',

			// --- Other common Asian languages if you anticipate them:
			// Indonesian (ID is often the base)
			'id_ID'          => 'id',
			// Thai (TH is often the base)
			'th_TH'          => 'th',
			// Vietnamese (VN is often the base)
			'vi_VN'          => 'vi',

			// --- Cyrillic Languages ---
			// --- Russian (ru_RU) is often the de facto base for generic Cyrillic if other specific files aren't present.
			// If you have specific content for other Cyrillic languages, keep their full locale.
			// These substitutions assume you might only have content for the main Russian locale.
			// Ukrainian to Russian (if no uk_UA content exists)
			'uk_UA'          => 'ru_RU',
			// Belarusian to Russian (if no be_BY content exists)
			'be_BY'          => 'ru_RU',
			// Bulgarian to Russian (if no bg_BG content exists)
			'bg_BG'          => 'ru_RU',
			// Macedonian to Russian (if no mk_MK content exists)
			'mk_MK'          => 'ru_RU',

			// --- Serbian is complex due to its bi-alphabetic nature.
			// If your content is only in 'sr_RS' (Cyrillic), you might want to map others to it.
			// If you only have 'ru_RU' as a fallback for all Cyrillic, then map to 'ru_RU'.
			// Serbian (Cyrillic) to Russian (if no sr_RS content exists)
			'sr_RS'          => 'ru_RU',
			// Explicit Serbian Cyrillic to Russian
			'sr_Cyrl'        => 'ru_RU',
			// If you want to handle Latin Serbian, might map to 'sr_RS' if 'sr_RS' is your main Serbian file (likely Cyrillic)
			'sr_Latn'        => 'sr_RS',

			// --- Central Asian / Other Cyrillic
			// Kazakh to Russian (if no kk_KZ content)
			'kk_KZ'          => 'ru_RU',
			// Kyrgyz to Russian (if no ky_KG content)
			'ky_KG'          => 'ru_RU',
			// Tajik to Russian (if no tg_TJ content)
			'tg_TJ'          => 'ru_RU',
			// Mongolian to Russian (if no mn_MN content)
			'mn_MN'          => 'ru_RU',
			// Uzbek to Russian (if no uz_UZ content, considering its script transition)
			'uz_UZ'          => 'ru_RU',

			// --- General language code to specific country default (if you only have generic 'ru' vs 'ru_RU')
			'ru'             => 'ru_RU',
			// Maps generic 'uk' language code to 'uk_UA' (Ukrainian for Ukraine)
			// If you have 'uk_UA' content, don't map to 'ru_RU' from generic 'uk'
			'uk'             => 'uk_UA',
			'bg'             => 'bg_BG',
			'sr'             => 'sr_RS',
			'mk'             => 'mk_MK',
		);

		// Normalize locale to use underscores if it came in with hyphens (WordPress uses underscores).
		$normalized_locale = str_replace( '-', '_', $locale );

		// Return original locale if no substitution is defined
		return $locale_substitutions[ $normalized_locale ] ?? $locale;
	}

	/**
	 * Finds the appropriate localized file based on the current locale,
	 * applying substitutions and falling back to en_US.
	 *
	 * @since 2.10.1
	 *
	 * @return string The absolute path to the found file, or empty string if no file is found.
	 */
	public static function locate_localized_file( string $template ): string {

		static $fallback_locale = 'en_US';

		// Function \determine_locale() returns the current system/user locale or defaults to 'en_US'.
		$current_locale = \determine_locale();

		// 1. Try the exact current locale
		$file_path_current = str_replace( '{{locale}}', $current_locale, $template );
		if ( file_exists( $file_path_current ) ) {
			return $file_path_current;
		}

		// 2. Try the substituted locale
		$substituted_locale = self::substitute_locale_to_base( $current_locale );
		// Only try the substituted locale if it's different from the original current locale
		if ( $substituted_locale !== $current_locale ) {
			$file_path_substituted = str_replace( '{{locale}}', $substituted_locale, $template );
			if ( file_exists( $file_path_substituted ) ) {
				return $file_path_substituted;
			}
		}

		// 3. Fallback to en_US
		$file_path_fallback = str_replace( '{{locale}}', $fallback_locale, $template );
		if ( file_exists( $file_path_fallback ) ) {
			return $file_path_fallback;
		}

		// If no file is found, return blank string.
		return '';
	}
}
