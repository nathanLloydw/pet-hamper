<?php
/**
 * Class Rex_Product_Feed_Shipping
 *
 * @package    Rex_Product_Feed_Shipping
 * @subpackage Rex_Product_Feed/admin
 * @author     RexTheme <info@rextheme.com>
 */

/**
 * This class is responsible for managing shipping zones for feed
 *
 * @package    Rex_Product_Feed_Shipping
 * @subpackage Rex_Product_Feed/admin
 * @author     RexTheme <info@rextheme.com>
 * @since 7.2.36
 */
class Rex_Product_Feed_Shipping {

    /**
     * @var string $feed_country - Feed country variable.
     * @since 7.2.36
     */
    protected static $feed_country;

    /**
     * @var string $zone_countries - Feed zone countries variable.
     * @since 7.2.36
     */
    protected static $zone_countries;

    /**
     * @var string $shipping_methods - Feed shipping methods variable.
     * @since 7.2.36
     */
    protected static $shipping_methods;

    /**
     * Constructor for the Rex_Product_Feed_Shipping class
     *
     * @param string $country_code - A string containing country data in the format "state:country:continent".
     * @since 7.2.36
     */
    public function __construct( $country_code ) {
        self::$feed_country   = $country_code;
    }

    /**
     * Checks if the WooCommerce Table Rate Shipping plugin is active
     *
     * @return bool - True if the plugin is active, false otherwise.
     * @since 7.2.36
     */
    public static function is_wc_table_rate_shipping_active() {
        $active_plugings        = get_option( 'active_plugins', [] );
        $wc_table_rate_shipping = 'woocommerce-table-rate-shipping/woocommerce-table-rate-shipping.php';

        return in_array( $wc_table_rate_shipping, $active_plugings ) || is_plugin_active_for_network( $wc_table_rate_shipping );
    }

    /**
     * Retrieves the list of countries belonging to a specific continent in WooCommerce.
     *
     * @param string $continent_code - The continent code for which the countries are being retrieved.
     * @return array - An array of country codes associated with the specified continent.
     *                If no countries are found, an empty array is returned.
     * @since 7.2.36
     */
    public static function get_wc_countries_by_continent( $continent_code ) {
        // Retrieve the continent data from the WooCommerce plugin directory
        $wc_countries = new WC_Countries();
        $continents   = $wc_countries->get_continents();

        // Check if the country codes array for the specified continent exists and is not empty
        return !empty( $continents[ $continent_code ][ 'countries' ] ) ? $continents[ $continent_code ][ 'countries' ] : [];
    }

    /**
     * Get shipping zones and their shipping methods for a given product.
     *
     * @param WC_Product $product The product object for which to retrieve the shipping zones.
     * @return array An array containing information about the shipping zones and their shipping methods. Each element in the array represents a shipping zone and includes the country, region, service, price, and instance settings.
     * @since 7.2.36
     */
    public function get_shipping_zones( WC_Product $product ) {
        $wc_shipping_zones = WC_Shipping_Zones::get_zones();
        if( !empty( $wc_shipping_zones ) ) {
            foreach( $wc_shipping_zones as $zone ) {
                if( empty( $zone[ 'shipping_methods' ] ) ) continue;
                if( empty( $zone[ 'zone_locations' ] ) ) continue;

                self::format_zone_locations( $zone[ 'zone_locations' ] );

                if( self::$feed_country && ( empty( self::$zone_countries ) || !in_array( self::$feed_country, self::$zone_countries ) ) ) continue;

                $zone_name = !empty( $zone[ 'zone_name' ] ) ? $zone[ 'zone_name' ] : '';

                self::get_formatted_shipping_methods( $zone[ 'shipping_methods' ], $product, $zone_name );
            }
        }
        return self::$shipping_methods;
    }

    /**
     * Get product shipping price from Table Rate Shipping by WooCommerce
     *
     * @param WC_Product $product WooCommerce Product instance.
     * @param WC_Shipping_Table_Rate $wc_table_rate WC Table Rate Shipping instance.
     *
     * @return array|string[]
     * @throws Exception
     * @since 7.2.36
     */
    protected function get_wc_table_rate_shipping_cost( WC_Product $product, WC_Shipping_Table_Rate $wc_table_rate ) {
        if( self::is_wc_table_rate_shipping_active() ) {
            $product_price          = $product->get_price();
            $product_weight         = $product->get_weight();
            $product_shipping_class = $product->get_shipping_class_id() ?: '';

            $rates = self::get_wc_table_rate_shipping_rates( $wc_table_rate, $product_price, $product_weight, $product_shipping_class );

            $cost = self::get_shipping_rate( $rates, $product_price );
            $cost = $cost ? wc_format_decimal( $cost, wc_get_price_decimals() ) : '';

            return [
                'shipping_cost' => $cost,
                'shipping_tax'  => ''
            ];
        }
        return [ 'shipping_cost' => '', 'shipping_tax' => '' ];
    }

    /**
     * Get the shipping rates from the WC_Shipping_Table_Rate instance based on the provided parameters.
     *
     * @param WC_Shipping_Table_Rate $wc_table_rate         The instance of WC_Shipping_Table_Rate.
     * @param float                  $product_price         The price of the product.
     * @param float|string           $product_weight        The weight of the product (optional).
     * @param string                 $product_shipping_class The shipping class of the product (optional).
     *
     * @return array The array of shipping rates returned by the query_rates method.
     * @since 7.2.36
     */
    protected function get_wc_table_rate_shipping_rates( WC_Shipping_Table_Rate $wc_table_rate, $product_price, $product_weight = '', $product_shipping_class = '' ) {
        return $wc_table_rate->query_rates( [
            'price'             => $product_price,
            'weight'            => $product_weight,
            'shipping_class_id' => $product_shipping_class
        ] );
    }

    /**
     * Calculates the total rate based on the given rate and product price.
     *
     * @param object $rate An object representing the rate information.
     * @param float $product_price The price of the product for which the rate is being calculated.
     * @return float The calculated rate.
     * @since 7.2.36
     */
    protected function calculate_rate( $rate, $product_price ) {
        // Initialize variables with default values.
        $rate_cost       = !empty( $rate->rate_cost ) ? $rate->rate_cost : 0;
        $cost_per_item   = !empty( $rate->rate_cost_per_item ) ? $rate->rate_cost_per_item : 0;
        $cost_per_weight = !empty( $rate->rate_cost_per_weight_unit ) ? $rate->rate_cost_per_weight_unit : 0;
        $cost_percentage = !empty( $rate->rate_cost_percent ) ? $rate->rate_cost_percent : 0;

        return $rate_cost + $cost_per_item + $cost_per_weight + ( ( $product_price * $cost_percentage ) / 100 );
    }

    /**
     * Retrieves the shipping rate based on the given rates and product price.
     *
     * @param array $rates An array of rate information.
     * @param float $product_price The price of the product for which the shipping rate is being retrieved.
     * @return float|string The shipping rate if found, otherwise an empty string.
     * @since 7.2.36
     */
    protected function get_shipping_rate( $rates, $product_price ) {
        if( !empty( $rates ) ) {
            if( in_array( 1, array_column( $rates, 'rate_abort' ) ) ) {
                // If rate_abort is found in any of the rates, return an empty string
                return '';
            }

            $index = array_search( 1, array_column( $rates, 'rate_priority' ) );

            if( !empty( $rates[ $index ] ) ) {
                // If rate_priority is found and the corresponding rate is not empty, calculate the rate.
                return self::calculate_rate( $rates[ $index ], $product_price ) ?: '';
            }
        }
        // Return an empty string if no suitable rate is found.
        return '';
    }

    /**
     * Formats the zone locations by extracting and storing zone countries and states.
     *
     * @param array $zone_locations The zone locations to format.
     *
     * @return void
     * @since 7.2.36
     */
    protected function format_zone_locations( $zone_locations ) {
        self::$zone_countries = [];

        foreach( $zone_locations as $location ) {
            if( !empty( $location->type ) && !empty( $location->code ) ) {
                if( 'state' === $location->type ) {
                    $continent_data = explode( ':', $location->code );
                    if( !empty( $continent_data[ 0 ] ) ) {
                        self::$zone_countries[] = $continent_data[ 0 ];
                    }
                }
                elseif( 'country' === $location->type ) {
                    self::$zone_countries[] = $location->code;
                }
                elseif( 'continent' === $location->type ) {
                    $countries            = self::get_wc_countries_by_continent( $location->code );
                    self::$zone_countries = !empty( $countries ) ? array_values( array_unique( array_merge( $countries, self::$zone_countries ) ) ) : self::$zone_countries;
                }
            }
        }
    }

    /**
     * Formats the shipping methods and stores them in the class variable.
     *
     * @param array $shipping_methods The array of shipping methods to format.
     * @param WC_Product $product The WooCommerce product.
     * @param string $zone_name The WooCommerce shipping zone title.
     *
     * @return void
     * @since 7.2.36
     */
    protected function get_formatted_shipping_methods( $shipping_methods, WC_Product $product, $zone_name = '' ) {
        self::$shipping_methods = [];
        foreach( $shipping_methods as $method ) {
            if( $method->is_enabled() ) {
                $service  = '';
                $price    = 0;
                $instance = [];

                if( isset( $method->instance_settings[ 'cost' ] ) ) {
                    $price = (float)$method->instance_settings[ 'cost' ];
                }

                $service .= $zone_name;

                if( isset( $method->instance_settings[ 'title' ] ) ) {
                    $service .= ' ' . $method->instance_settings[ 'title' ];

                    if( 'WC_Shipping_Flat_Rate' === get_class( $method ) ) {
                        $instance = $method->instance_settings;
                    }
                    if( 'WC_Shipping_Table_Rate' === get_class( $method ) ) {
                        $cost  = self::get_wc_table_rate_shipping_cost( $product, $method );
                        $price = is_numeric( $cost[ 'shipping_cost' ] ) ? $cost[ 'shipping_cost' ] : '';
                    }
                }

                if( '' !== $price ) {
                    self::$shipping_methods[] = [
                        'country'  => self::$feed_country,
                        //'region'   => self::$state,
                        'service'  => "{$service} " . self::$feed_country,
                        'price'    => $price,
                        'instance' => $instance,
                    ];
                }
            }
        }
    }
}