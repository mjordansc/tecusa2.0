<?php
/**
 * Dictionaries.
 *
 * @since 4.3.0-3
 */

namespace WOOMC;

/**
 * Class Dict
 *
 * @since 4.3.0-3
 */
class Dict {

	/**
	 * Var currency_to_country_alpha2.
	 * ISO 3166 - country codes
	 *
	 * @link  https://www.iso.org/obp/ui/#search
	 * ISO 4217 - currency codes
	 * @link  https://www.six-group.com/en/products-services/financial-information/data-standards.html
	 *
	 * @since 4.3.0-3
	 *
	 * @var array
	 */
	protected static $currency_to_country_alpha2 = array(
		'ANG' => 'SX', // ANG will expire as a legal tender by July 1, 2025. Replaced by XCG.
		'XCG' => 'SX', // Caribbean Guilder: SINT MAARTEN
		'XAF' => 'CF', // CFA Franc BEAC: CENTRAL AFRICAN REPUBLIC (THE)
		'XCD' => 'AG', // ANTIGUA AND BARBUDA
		'XOF' => 'SN', // CFA Franc BCEAO: SENEGAL
		'XPF' => 'PF', // CFP Franc: FRENCH POLYNESIA
	);

	/**
	 * Getter currency_to_country_alpha_2
	 *
	 * @since 4.3.0-3
	 *
	 * @param string $currency
	 *
	 * @return string
	 */
	public static function currency_to_country_alpha_2( string $currency ): string {
		$dict     = self::$currency_to_country_alpha2;
		$currency = strtoupper( $currency );

		if ( isset( $dict[ $currency ] ) ) {
			$alpha2 = $dict[ $currency ];
		} else {
			$alpha2 = substr( $currency, 0, 2 );
		}

		/**
		 * Filter woocommerce_currency_to_country_alpha_2
		 *
		 * @since 4.3.0-3
		 *
		 * @param string $alpha2 ISO 3166-1-alpha-2 code of a country
		 */
		$output = \apply_filters( 'woocommerce_currency_to_country_alpha_2', $alpha2, $currency );

		// Sanitize after filtering.
		if ( ! is_string( $output ) || strlen( $output ) !== 2 ) {
			$output = '';
		}

		return $output;
	}
}
