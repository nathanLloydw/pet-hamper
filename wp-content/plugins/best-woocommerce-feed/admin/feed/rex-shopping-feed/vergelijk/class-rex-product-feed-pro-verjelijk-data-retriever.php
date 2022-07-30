<?php

/**
 * Class for retriving product data based on user selected feed configuration.
 *
 * Get the product data based on feed config selected by user.
 *
 * @package    Rex_Product_Verjelijk_Data_Retriever
 * @subpackage Rex_Product_Feed/admin
 * @author     RexTheme <info@rextheme.com>
 */
class Rex_Product_Verjelijk_Data_Retriever extends Rex_Product_Data_Retriever {

    /**
     * Retrieve a product's categories as a list with specified format.
     *
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     */
    protected function get_product_cats( $before = '', $sep = '>', $after = '' ) {

        if ( 'WC_Product_Variation' == get_class($this->product) ) {
            return $this->get_the_term_list( $this->product->get_parent_id(), 'product_cat', $before, $sep, $after );
        }else {
            return $this->get_the_term_list( $this->product->get_id(), 'product_cat', $before, $sep, $after );
        }
    }


}