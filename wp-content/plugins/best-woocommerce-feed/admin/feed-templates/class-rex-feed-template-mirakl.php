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
 * Defines the attributes and template for Ibud feed.
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/feed-templates/Rex_Feed_Template_Mirakl
 * @author     RexTheme <info@rextheme.com>
 */
class Rex_Feed_Template_Mirakl extends Rex_Feed_Abstract_Template {

    protected function init_atts()
    {
        $this->attributes = array(
            'Required Information' => array(
                'sku'                   => 'SKU',
                'product-id'            => 'Product Id',
                'product-id-type'       => 'Product Id type',
                'description'           => 'Description',
                'internal-description'  => 'Internal description',
                'price'                 => 'Price',
                'price-additional-info' => 'Price additional info',
                'quantity'              => 'Quantity',
                'min-quantity-alert'    => 'Min quantity alert',
                'state'                 => 'State',
                'available-start-date'  => 'Available Start date',
                'available-end-date'    => 'Available End date',
                'discount-start-date'   => 'Discount Start date',
                'discount-end-date'     => 'Discount End date',
                'discount-price'        => 'Discount Price',
                'update-delete'         => 'Update delete',


            ),

            'Additional Information' => array(
                'attribute_code_1'   => 'Attribute Code 1',
                'attribute_value_1'  => 'Attribute value 1',
                'attribute_code_2'   => 'Attribute Code 2',
                'attribute_value_2'  => 'Attribute value 2',
                'attribute_code_3'   => 'Attribute Code 3',
                'attribute_value_3'  => 'Attribute value 3',
                'attribute_code_4'   => 'Attribute Code 4',
                'attribute_value_4'  => 'Attribute value 4',
                'attribute_code_5'   => 'Attribute Code 5',
                'attribute_value_5'  => 'Attribute value 5',
                'attribute_code_6'   => 'Attribute Code 6',
                'attribute_value_6'  => 'Attribute value 6',
                'attribute_code_7'   => 'Attribute Code 7',
                'attribute_value_7'  => 'Attribute value 7',
                'attribute_code_8'   => 'Attribute Code 8',
                'attribute_value_8'  => 'Attribute value 8',
                'attribute_code_9'   => 'Attribute Code 9',
                'attribute_value_9'  => 'Attribute value 9',
                'attribute_code_10'  => 'Attribute Code 10',
                'attribute_value_10' => 'Attribute value 10',
                'attribute_code_11'  => 'Attribute Code 11',
                'attribute_value_11' => 'Attribute value 11',
                'attribute_code_12'  => 'Attribute Code 12',
                'attribute_value_12' => 'Attribute value 12',
                'attribute_code_13'  => 'Attribute Code 13',
                'attribute_value_13' => 'Attribute value 13',
                'attribute_code_14'  => 'Attribute Code 14',
                'attribute_value_14' => 'Attribute value 14',
                'attribute_code_15'  => 'Attribute Code 15',
                'attribute_value_15' => 'Attribute value 15',
            ),
        );
    }

    protected function init_default_template_mappings(){
        $this->template_mappings = array(
            array(
                'attr'     => 'sku',
                'type'     => 'meta',
                'meta_key' => 'sku',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'product-id',
                'type'     => 'meta',
                'meta_key' => 'id',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'product-id-type',
                'type'     => 'meta',
                'meta_key' => '',
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
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'internal-description',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => '',
                'escape'   => 'default',
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
                'attr'     => 'price-additional-info',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'quantity',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'min-quantity-alert',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'state',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'available-start-date',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'available-end-date',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'discount-start-date',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'discount-end-date',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'discount-price',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            array(
                'attr'     => 'update-delete',
                'type'     => 'meta',
                'meta_key' => '',
                'st_value' => '',
                'prefix'   => '',
                'suffix'   => ' ',
                'escape'   => 'default',
                'limit'    => 0,
            ),
            
            
        );
    }

}
