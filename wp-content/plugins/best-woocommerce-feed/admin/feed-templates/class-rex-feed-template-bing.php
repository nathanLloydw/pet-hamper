<?php

/**
 * The Bing Feed Template class.
 *
 * @link       https://rextheme.com
 * @since      1.1.4
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/feed-templates/
 */

/**
 *
 * Defines the attributes and template for bing feed.
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/feed-templates/Rex_Feed_Template_Bing
 * @author     RexTheme <info@rextheme.com>
 */
class Rex_Feed_Template_Bing extends Rex_Feed_Abstract_Template {

    protected function init_atts(){
        $this->attributes = array(
            'Required Information'  =>  array(
                'id'                => 'Merchant Product ID',
                'title'             => 'Product Title',
                'description'       => 'Description',
                'link'              => 'Product URL',
                'image_link'        => 'Primary Image URL',
                'price'             => 'Price',
            ),

            'Additional Information'    => array(
                'mpn'                   => 'Manufacturer Part Number',
                'gtin'                  => 'Global Trade Item Number',
                'brand'                 => 'Brand',
                'upc'                   => 'UPC / EAN',
                'condition'             => 'Condition',
                'availability'          => 'Availability',
                'category'              => 'Merchant Category',
            ),

        );
    }

    protected function init_default_template_mappings(){
        $this->template_mappings = array(
            array(
                'attr'     => 'id',
                'type'     => 'meta',
                'meta_key' => 'id',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'title',
                'type'     => 'meta',
                'meta_key' => 'title',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'description',
                'type'     => 'meta',
                'meta_key' => 'description',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'remove_tab',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'price',
                'type'     => 'meta',
                'meta_key' => 'price',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' '.get_option('woocommerce_currency'),
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'link',
                'type'     => 'meta',
                'meta_key' => 'link',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'image_link',
                'type'     => 'meta',
                'meta_key' => 'featured_image',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
                'limit'    => 0,
            ),
        );
    }

}
