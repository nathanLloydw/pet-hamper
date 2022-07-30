<?php
/**
 * Helper Class to retrieve Feed Merchants
 *
 * @link       https://rextheme.com
 * @since      7.3.0
 *
 * @package    Rex_Product_Feed
 */
class Rex_Feed_Merchants {
    /**
     * @desc Retrieves all the merchant lists [free and pro]
     * @since 7.3.0
     * @return mixed|void
     */
    public static function get_merchants() {
        $popular = array(
            'custom'    => array(
                'free'   => true,
                'name'   => 'Custom',
                'formats' => [ 'xml', 'yml', 'csv', 'text', 'tsv', 'json' ]
            ),
            'google'    => array(
                'free'   => true,
                'name'   => 'Google Shopping',
                'formats' => [ 'xml' ]
            ),
            'facebook'  => array(
                'free'   => true,
                'name'   => 'Facebook',
                'formats' => [ 'xml', 'csv' ],
                'csv_separators' => [ 'comma', 'semi_colon' ]
            ),
            'instagram' => array(
                'free'   => true,
                'name'   => 'Instagram (by Facebook)',
                'formats' => [ 'xml', 'csv', 'tsv' ]
            ),
            'pinterest' => array(
                'free'   => true,
                'name'   => 'Pinterest',
                'formats' => [ 'csv', 'tsv', 'xml' ]
            ),
            'snapchat'  => array(
                'free'   => true,
                'name'   => 'Snapchat',
                'formats' => [ 'csv' ]
            ),
            'bing'      => array(
                'free'   => true,
                'name'   => 'Bing',
                'formats' => [ 'text' ]
            ),
            'yandex'    => array(
                'free'   => true,
                'name'   => 'Yandex',
                'formats' => [ 'xml', 'yml' ]
            ),
            'rakuten'   => array(
                'free'   => true,
                'name'   => 'Rakuten',
                'formats' => [ 'csv', 'tsv', 'xml' ]
            ),
            'vivino'    => array(
                'free'   => true,
                'name'   => 'Vivino',
                'formats' => []
            ),
        );
        $pro     = array(
            'google_review' => array(
                'free'   => false,
                'name'   => 'Google Review',
                'formats' => [ 'xml' ]
            ),
            'ebay_mip'      => array(
                'free'   => false,
                'name'   => 'eBay (MIP)',
                'formats' => [ 'xml', 'csv' ],
                'csv_separators' => [ 'comma', 'semi_colon' ]
            ),
            'drm'           => array(
                'free'   => false,
                'name'   => 'Google Remarketing (DRM)',
                'formats' => []
            ),
            'leguide'       => array(
                'free'   => false,
                'name'   => 'Leguide',
                'formats' => [ 'xml', 'csv' ],
                'csv_separators' => [ 'comma', 'semi_colon' ]
            ),
        );
        $free    = array(
            'google_custom_search_ads'        => array(
                'free'   => true,
                'name'   => 'Google Custom Search Ads',
                'formats' => [ 'csv' ]
            ),
            'google_Ad'                       => array(
                'free'   => true,
                'name'   => 'Google Dynamic Display Ads',
                'formats' => []
            ),
            'google_local_products'           => array(
                'free'   => true,
                'name'   => 'Google Local Products',
                'formats' => [ 'xml', 'text', 'csv' ]
            ),
            'google_local_products_inventory' => array(
                'free'   => true,
                'name'   => 'Google Local Products Inventory',
                'formats' => [ 'xml', 'text' ]
            ),
            'google_merchant_promotion'       => array(
                'free'   => true,
                'name'   => 'Google Merchant Promotion Feed',
                'formats' => []
            ),
            'google_dsa'                      => array(
                'free'   => true,
                'name'   => 'Google Dynamic Search Ads',
                'formats' => []
            ),
            'google_shopping_actions'         => array(
                'free'   => true,
                'name'   => 'Google Shopping Actions',
                'formats' => [ 'xml' ]
            ),
            'adroll'                          => array(
                'free'   => true,
                'name'   => 'AdRoll',
                'formats' => []
            ),
            'nextag'                          => array(
                'free'   => true,
                'name'   => 'Nextag',
                'formats' => []
            ),
            'pricegrabber'                    => array(
                'free'   => true,
                'name'   => 'Pricegrabber',
                'formats' => []
            ),
            'cercavino'                       => array(
                'free'   => true,
                'name'   => 'Cercavino',
                'formats' => [ 'text' ],
                'csv_separators' => [ 'pipe' ]
            ),
            'trovino'                         => array(
                'free'   => true,
                'name'   => 'Trovino',
                'formats' => [ 'text' ],
                'csv_separators' => [ 'pipe' ]
            ),
            'bing_image'                      => array(
                'free'   => true,
                'name'   => 'Bing Image',
                'formats' => [ 'json' ]
            ),
            'kelkoo'                          => array(
                'free'   => true,
                'name'   => 'Kelkoo',
                'formats' => []
            ),
            'become'                          => array(
                'free'   => true,
                'name'   => 'Become',
                'formats' => []
            ),
            'shopzilla'                       => array(
                'free'   => true,
                'name'   => 'ShopZilla',
                'formats' => [ 'text' ]
            ),
            'shopping'                        => array(
                'free'   => true,
                'name'   => 'Shopping',
                'formats' => []
            ),
            'pricerunner'                     => array(
                'free'   => true,
                'name'   => 'PriceRunner',
                'formats' => []
            ),
            'billiger'                        => array(
                'free'   => true,
                'name'   => 'Billiger',
                'formats' => []
            ),
            'vergelijk'                       => array(
                'free'   => true,
                'name'   => 'Vergelijk',
                'formats' => []
            ),
            'marktplaats'                     => array(
                'free'   => true,
                'name'   => 'Marktplaats',
                'formats' => []
            ),
            'beslist'                         => array(
                'free'   => true,
                'name'   => 'Beslist',
                'formats' => []
            ),
            'daisycon'                        => array(
                'free'   => true,
                'name'   => 'Daisycon',
                'formats' => []
            ),
            'twenga'                          => array(
                'free'   => true,
                'name'   => 'Twenga',
                'formats' => []
            ),
            'kieskeurig'                      => array(
                'free'   => true,
                'name'   => 'Kieskeurig.nl',
                'formats' => []
            ),
            'spartoo'                         => array(
                'free'   => true,
                'name'   => 'Spartoo.nl',
                'formats' => []
            ),
            'spartooFr'                       => array(
                'free'   => true,
                'name'   => 'SpartooFr',
                'formats' => [ 'xml', 'csv' ]
            ),
            'tweakers'                        => array(
                'free'   => true,
                'name'   => 'Tweakers.nl',
                'formats' => []
            ),
            'sooqr'                           => array(
                'free'   => true,
                'name'   => 'Sooqr',
                'formats' => []
            ),
            'koopkeus'                        => array(
                'free'   => true,
                'name'   => 'Koopkeus',
                'formats' => []
            ),
            'scoupz'                          => array(
                'free'   => true,
                'name'   => 'Scoupz',
                'formats' => []
            ),
            'cdiscount'                       => array(
                'free'   => true,
                'name'   => 'Cdiscount',
                'formats' => []
            ),
            'kelkoonl'                        => array(
                'free'   => true,
                'name'   => 'Kelkoo.nl',
                'formats' => []
            ),
            'uvinum'                          => array(
                'free'   => true,
                'name'   => 'Uvinum / DrinsksAndCo',
                'formats' => []
            ),
            'idealo'                          => array(
                'free'   => true,
                'name'   => 'Idealo',
                'formats' => [  'csv'  ]
            ),
            'pricesearcher'                   => array(
                'free'   => true,
                'name'   => 'Pricesearcher',
                'formats' => []
            ),
            'pricemasher'                     => array(
                'free'   => true,
                'name'   => 'Pricemasher',
                'formats' => []
            ),
            'fashionchick'                    => array(
                'free'   => true,
                'name'   => 'Fashionchick',
                'formats' => []
            ),
            'ceneo'                           => array(
                'free'   => true,
                'name'   => 'Ceneo',
                'formats' => []
            ),
            'choozen'                         => array(
                'free'   => true,
                'name'   => 'Choozen',
                'formats' => []
            ),
            'rss'                             => array(
                'free'   => true,
                'name'   => 'RSS',
                'formats' => []
            ),
            'ciao'                            => array(
                'free'   => true,
                'name'   => 'Ciao',
                'formats' => [ 'xml' ]
            ),
            'prisjkat'                        => array(
                'free'   => true,
                'name'   => 'Pricespy/Prisjkat',
                'formats' => []
            ),
            'crowdfox'                        => array(
                'free'   => true,
                'name'   => 'Crowdfox',
                'formats' => []
            ),
            'powerreviews'                    => array(
                'free'   => true,
                'name'   => 'PowerReviews',
                'formats' => []
            ),
            'trovaprezzi'                     => array(
                'free'   => true,
                'name'   => 'Trovaprezzi',
                'formats' => [ 'xml', 'csv' ]
            ),
            'zbozi'                           => array(
                'free'   => true,
                'name'   => 'Zbozi',
                'formats' => []
            ),
            'liveintent'                      => array(
                'free'   => true,
                'name'   => 'LiveIntent',
                'formats' => [ 'xml' ]
            ),
            'skroutz'                         => array(
                'free'   => true,
                'name'   => 'Skroutz',
                'formats' => []
            ),
            'otto'                            => array(
                'free'   => true,
                'name'   => 'Otto',
                'formats' => []
            ),
            'sears'                           => array(
                'free'   => true,
                'name'   => 'Sears',
                'formats' => []
            ),
            'ammoseek'                        => array(
                'free'   => true,
                'name'   => 'AmmoSeek',
                'formats' => []
            ),
            'fnac'                            => array(
                'free'   => true,
                'name'   => 'Fnac',
                'formats' => []
            ),
            'zalando'                         => array(
                'free'   => true,
                'name'   => 'Zalando',
                'formats' => [ 'json', 'csv' ]
            ),
            'zalando_stock_update'            => array(
                'free'   => true,
                'name'   => 'Zalando Stock Update',
                'formats' => [ 'csv' ]
            ),
            'pixmania'                        => array(
                'free'   => true,
                'name'   => 'Pixmania',
                'formats' => []
            ),
            'coolblue'                        => array(
                'free'   => true,
                'name'   => 'Coolblue',
                'formats' => []
            ),
            'shopmania'                       => array(
                'free'   => true,
                'name'   => 'ShopMania',
                'formats' => []
            ),
            'kleding'                         => array(
                'free'   => true,
                'name'   => 'Kleding',
                'formats' => []
            ),
            'ladenzeile'                      => array(
                'free'   => true,
                'name'   => 'Ladenzeile',
                'formats' => []
            ),
            'preis'                           => array(
                'free'   => true,
                'name'   => 'Preis',
                'formats' => []
            ),
            'winesearcher'                    => array(
                'free'   => true,
                'name'   => 'Winesearcher',
                'formats' => []
            ),
            'walmart'                         => array(
                'free'   => true,
                'name'   => 'Walmart',
                'formats' => []
            ),
            'verizon'                         => array(
                'free'   => true,
                'name'   => 'Yahoo/Verizon Dynamic Product Ads',
                'formats' => []
            ),
            'kelkoo_group'                    => array(
                'free'   => true,
                'name'   => 'Kelkoo Group',
                'formats' => []
            ),
            'target'                          => array(
                'free'   => true,
                'name'   => 'Target',
                'formats' => []
            ),
            'pepperjam'                       => array(
                'free'   => true,
                'name'   => 'Pepperjam',
                'formats' => []
            ),
            'cj_affiliate'                    => array(
                'free'   => true,
                'name'   => 'CJ Affiliate',
                'formats' => []
            ),
            'guenstiger'                      => array(
                'free'   => true,
                'name'   => 'Guenstiger',
                'formats' => []
            ),
            'hood'                            => array(
                'free'   => true,
                'name'   => 'Hood',
                'formats' => []
            ),
            'livingo'                         => array(
                'free'   => true,
                'name'   => 'Livingo',
                'formats' => []
            ),
            'jet'                             => array(
                'free'   => true,
                'name'   => 'Jet',
                'formats' => []
            ),
            'bonanza'                         => array(
                'free'   => true,
                'name'   => 'Bonanza',
                'formats' => []
            ),
            'adcell'                          => array(
                'free'   => true,
                'name'   => 'Adcell',
                'formats' => []
            ),
            'adform'                          => array(
                'free'   => true,
                'name'   => 'Adform',
                'formats' => []
            ),
            'stylefruits'                     => array(
                'free'   => true,
                'name'   => 'Stylefruits',
                'formats' => []
            ),
            'medizinfuchs'                    => array(
                'free'   => true,
                'name'   => 'Medizinfuchs',
                'formats' => []
            ),
            'moebel'                          => array(
                'free'   => true,
                'name'   => 'Moebel',
                'formats' => []
            ),
            'restposten'                      => array(
                'free'   => true,
                'name'   => 'Restposten',
                'formats' => []
            ),
            'sparmedo'                        => array(
                'free'   => true,
                'name'   => 'Sparmedo',
                'formats' => []
            ),
            'whiskymarketplace'               => array(
                'free'   => true,
                'name'   => 'Whiskymarketplace',
                'formats' => []
            ),
            'newegg'                          => array(
                'free'   => true,
                'name'   => 'NewEgg',
                'formats' => []
            ),
            '123i'                            => array(
                'free'   => true,
                'name'   => '123I',
                'formats' => []
            ),
            'adcrowd'                         => array(
                'free'   => true,
                'name'   => 'Adcrowd',
                'formats' => []
            ),
            'bikeexchange'                    => array(
                'free'   => true,
                'name'   => 'Bike Exchange',
                'formats' => []
            ),
            'cenowarka'                       => array(
                'free'   => true,
                'name'   => 'Cenowarka',
                'formats' => []
            ),
            'cezigue'                         => array(
                'free'   => true,
                'name'   => 'Cezigue',
                'formats' => []
            ),
            'check24'                         => array(
                'free'   => true,
                'name'   => 'Check24',
                'formats' => []
            ),
            'clang'                           => array(
                'free'   => true,
                'name'   => 'Clang',
                'formats' => []
            ),
            'cherchons'                       => array(
                'free'   => true,
                'name'   => 'Cherchons',
                'formats' => []
            ),
            'boetiek'                         => array(
                'free'   => true,
                'name'   => 'Boetiek B.V',
                'formats' => []
            ),
            'comparer'                        => array(
                'free'   => true,
                'name'   => 'Comparer',
                'formats' => []
            ),
            'converto'                        => array(
                'free'   => true,
                'name'   => 'Converto',
                'formats' => []
            ),
            'coolshop'                        => array(
                'free'   => true,
                'name'   => 'Coolshop',
                'formats' => []
            ),
            'commerce_connector'              => array(
                'free'   => true,
                'name'   => 'Commerce Connector',
                'formats' => []
            ),
            'everysize'                       => array(
                'free'   => true,
                'name'   => 'Everysize',
                'formats' => []
            ),
            'encuentraprecios'                => array(
                'free'   => true,
                'name'   => 'Encuentraprecios',
                'formats' => []
            ),
            'geizhals'                        => array(
                'free'   => true,
                'name'   => 'Geizhals',
                'formats' => []
            ),
            'geizkragen'                      => array(
                'free'   => true,
                'name'   => 'Geizkragen',
                'formats' => []
            ),
            'giftboxx'                        => array(
                'free'   => true,
                'name'   => 'Giftboxx',
                'formats' => []
            ),
            'go_banana'                       => array(
                'free'   => true,
                'name'   => 'Go Banana',
                'formats' => []
            ),
            'goed_geplaatst'                  => array(
                'free'   => true,
                'name'   => 'Goed Geplaatst',
                'formats' => []
            ),
            'grosshandel'                     => array(
                'free'   => true,
                'name'   => 'Grosshandel',
                'formats' => []
            ),
            'hardware'                        => array(
                'free'   => true,
                'name'   => 'Hardware.info',
                'formats' => []
            ),
            'hatch'                           => array(
                'free'   => true,
                'name'   => 'Hatch',
                'formats' => []
            ),
            'hintaopas'                       => array(
                'free'   => true,
                'name'   => 'Hintaopas',
                'formats' => []
            ),
            'fyndiq'                          => array(
                'free'   => true,
                'name'   => 'Fyndiq.se',
                'formats' => []
            ),
            'fasha'                           => array(
                'free'   => true,
                'name'   => 'Fasha',
                'formats' => []
            ),
            'realde'                          => array(
                'free'   => true,
                'name'   => 'Real.de',
                'formats' => []
            ),
            'hintaseuranta'                   => array(
                'free'   => true,
                'name'   => 'Hintaseuranta',
                'formats' => []
            ),
            'family_blend'                    => array(
                'free'   => true,
                'name'   => 'Family Blend',
                'formats' => []
            ),
            'hitmeister'                      => array(
                'free'   => true,
                'name'   => 'Hitmeister',
                'formats' => []
            ),
            'lazada'                          => array(
                'free'   => true,
                'name'   => 'Lazada',
                'formats' => [ 'csv' ]
            ),
            'get_price'                       => array(
                'free'   => true,
                'name'   => 'GetPrice.com.au',
                'formats' => []
            ),
            'home_tiger'                      => array(
                'free'   => true,
                'name'   => 'HomeTiger',
                'formats' => []
            ),
            'jurkjes'                         => array(
                'free'   => true,
                'name'   => 'Jurkjes.nl',
                'formats' => []
            ),
            'kiesproduct'                     => array(
                'free'   => true,
                'name'   => 'Kiesproduct',
                'formats' => []
            ),
            'kiyoh'                           => array(
                'free'   => true,
                'name'   => 'Kiyoh',
                'formats' => []
            ),
            'kompario'                        => array(
                'free'   => true,
                'name'   => 'Kompario',
                'formats' => []
            ),
            'kwanko'                          => array(
                'free'   => true,
                'name'   => 'Kwanko',
                'formats' => []
            ),
            'ledenicheur'                     => array(
                'free'   => true,
                'name'   => 'Le Dénicheur',
                'formats' => []
            ),
            'les_bonnes_bouilles'             => array(
                'free'   => true,
                'name'   => 'Les Bonnes Bouilles',
                'formats' => []
            ),
            'lions_home'                      => array(
                'free'   => true,
                'name'   => 'Lions Home',
                'formats' => []
            ),
            'locamo'                          => array(
                'free'   => true,
                'name'   => 'Locamo',
                'formats' => []
            ),
            'logicsale'                       => array(
                'free'   => true,
                'name'   => 'Logicsale',
                'formats' => []
            ),
            'google_manufacturer_center'      => array(
                'free'   => true,
                'name'   => 'Google Manufacturer Center',
                'formats' => []
            ),
            'google_express'      => array(
                'free'   => true,
                'name'   => 'Google Express',
                'formats' => [ 'xml' ]
            ),
            'pronto'                          => array(
                'free'   => true,
                'name'   => 'Pronto',
                'formats' => []
            ),
            'awin'                            => array(
                'free'   => true,
                'name'   => 'Awin',
                'formats' => []
            ),
            'indeed'                          => array(
                'free'   => true,
                'name'   => 'Indeed',
                'formats' => []
            ),
            'incurvy'                         => array(
                'free'   => true,
                'name'   => 'Incurvy',
                'formats' => []
            ),
            'jobbird'                         => array(
                'free'   => true,
                'name'   => 'Jobbird',
                'formats' => []
            ),
            'job_board_io'                    => array(
                'free'   => true,
                'name'   => 'JobBoard.io',
                'formats' => []
            ),
            'joblift'                         => array(
                'free'   => true,
                'name'   => 'Joblift',
                'formats' => []
            ),
            'kuantokusta'                     => array(
                'free'   => true,
                'name'   => 'KuantoKusta',
                'formats' => []
            ),
            'kauftipp'                        => array(
                'free'   => true,
                'name'   => 'Kauftipp',
                'formats' => []
            ),
            'rakuten_advertising'             => array(
                'free'   => true,
                'name'   => 'Rakuten Advertising',
                'formats' => []
            ),
            'pricefalls'                      => array(
                'free'   => true,
                'name'   => 'Pricefalls Feed',
                'formats' => []
            ),
            'clubic'                          => array(
                'free'   => true,
                'name'   => 'Clubic',
                'formats' => []
            ),
            'criteo'                          => array(
                'free'   => true,
                'name'   => 'Criteo',
                'formats' => [ 'xml' ]
            ),
            'shopalike'                       => array(
                'free'   => true,
                'name'   => 'Shopalike',
                'formats' => []
            ),
            'compartner'                      => array(
                'free'   => true,
                'name'   => 'Compartner',
                'formats' => [ 'xml' ]
            ),
            'adtraction'                      => array(
                'free'   => true,
                'name'   => 'Adtraction',
                'formats' => []
            ),
            'admitad'                         => array(
                'free'   => true,
                'name'   => 'Admitad',
                'formats' => []
            ),
            'bloomville'                      => array(
                'free'   => true,
                'name'   => 'Bloomville',
                'formats' => []
            ),
            'datatrics'                       => array(
                'free'   => true,
                'name'   => 'Datatrics',
                'formats' => []
            ),
            'deltaprojects'                   => array(
                'free'   => true,
                'name'   => 'Delta Projects',
                'formats' => []
            ),
            'drezzy'                          => array(
                'free'   => true,
                'name'   => 'Drezzy',
                'formats' => []
            ),
            'domodi'                          => array(
                'free'   => true,
                'name'   => 'Domodi',
                'formats' => []
            ),
            'doofinder'                       => array(
                'free'   => true,
                'name'   => 'Doofinder',
                'formats' => [ 'xml' ]
            ),
            'homebook'                        => array(
                'free'   => true,
                'name'   => 'Homebook.pl',
                'formats' => []
            ),
            'homedeco'                        => array(
                'free'   => true,
                'name'   => 'Home Deco',
                'formats' => []
            ),
            'glami'                           => array(
                'free'   => true,
                'name'   => 'Glami',
                'formats' => []
            ),
            'fashiola'                        => array(
                'free'   => true,
                'name'   => 'Fashiola',
                'formats' => []
            ),
            'emarts'                          => array(
                'free'   => true,
                'name'   => 'Emarts',
                'formats' => [ 'xml' ]
            ),
            'epoq'                            => array(
                'free'   => true,
                'name'   => 'Epoq',
                'formats' => [ 'xml' ]
            ),
            'grupo_zap'                       => array(
                'free'   => true,
                'name'   => 'Grupo Zap',
                'formats' => []
            ),
            'emag'                            => array(
                'free'   => true,
                'name'   => 'Emag',
                'formats' => []
            ),
            'lyst'                            => array(
                'free'   => true,
                'name'   => 'Lyst',
                'formats' => []
            ),
            'listupp'                         => array(
                'free'   => true,
                'name'   => 'Listupp',
                'formats' => []
            ),
            'hertie'                          => array(
                'free'   => true,
                'name'   => 'Hertie',
                'formats' => []
            ),
            'webgains'                        => array(
                'free'   => true,
                'name'   => ' Webgains',
                'formats' => []
            ),
            'vidaXL'                          => array(
                'free'   => true,
                'name'   => 'VidaXL',
                'formats' => []
            ),
            'mydeal'                          => array(
                'free'   => true,
                'name'   => 'My Deal',
                'formats' => []
            ),
            'idealo_de'                       => array(
                'free'   => true,
                'name'   => 'Idealo.de',
                'formats' => [ 'csv' ]
            ),
            'favi'                            => array(
                'free'   => true,
                'name'   => 'Favi - Compari & Árukereső',
                'formats' => []
            ),
            'ibud'                            => array(
                'free'   => true,
                'name'   => 'Ibud',
                'formats' => [ 'xml' ]
            ),
            'google_local_inventory_ads'      => array(
                'free'   => true,
                'name'   => 'Google Local Inventory Ads',
                'formats' => [ 'xml', 'text' ]
            ),
            'DealsForU'                       => array(
                'free'   => true,
                'name'   => 'Deals4u.gr',
                'formats' => [ 'xml' ]
            ),
            'Bestprice'                       => array(
                'free'   => true,
                'name'   => 'Bestprice',
                'formats' => [ 'xml' ]
            ),
            'mirakl'                          => array(
                'free'   => true,
                'name'   => 'Mirakl',
                'formats' => [ 'xml' ]
            ),
            'lesitedumif'                     => array(
                'free'   => true,
                'name'   => 'Lesitedumif',
                'formats' => [ 'csv' ]
            ),
            'shopee'                          => array(
                'free'   => true,
                'name'   => 'Shopee',
                'formats' => [ 'csv' ]
            ),
            'gulog_gratis'                    => array(
                'free'   => true,
                'name'   => 'GulogGratis.dk',
                'formats' => [ 'xml' ]
            ),
            'ebay_seller'                     => array(
                'free'   => true,
                'name'   => 'eBay Seller Center',
                'formats' => [ 'csv' ]
            ),
            'ebay_seller_tickets'             => array(
                'free'   => true,
                'name'   => 'eBay Seller Center (Event tickets)',
                'formats' => []
            ),
            'fruugo'                          => array(
                'free'   => true,
                'name'   => 'Fruugo',
                'formats' => [ 'csv' ]
            ),
            'bol'                             => array(
                'free'   => true,
                'name'   => 'Bol.com',
                'formats' => [ 'csv' ]
            ),
            'connexity'                       => array(
                'free'   => true,
                'name'   => 'Connexity',
                'formats' => [ 'csv', 'text' ]
            ),
            'heureka'                         => array(
                'free'   => true,
                'name'   => 'Heureka',
                'formats' => []
            ),
            'wish'                            => array(
                'free'   => true,
                'name'   => 'Wish.com',
                'formats' => [ 'csv', 'text' ]
            ),
            'zap_co_il'                       => array(
                'free'   => true,
                'name'   => 'Zap.co.il',
                'formats' => []
            ),
            'hotline'                         => array(
                'free'   => true,
                'name'   => 'Hotline',
                'formats' => [ 'xml' ]
            ),
            'rozetka'                         => array(
                'free'   => true,
                'name'   => 'Rozetka',
                'formats' => [ 'xml' ]
            ),
        );

        $merchants[ 'popular' ]        = $popular;
        $merchants[ 'pro_merchants' ]  = $pro;
        $merchants[ 'free_merchants' ] = $free;

        return apply_filters('rex_wpfm_all_merchant',$merchants);
    }

    /**
    * @desc Retrieves Supported Feed Formats
    * for a Specific Merchant
    * @since 7.3.0
    * @param $merchant
    * @return mixed|string[]
    */
    public static function get_feed_formats( $merchant ) {
        $merchants = self::get_merchants();

        if( isset( $merchants[ 'popular' ][ $merchant ][ 'formats' ] ) && !empty( $merchants[ 'popular' ][ $merchant ][ 'formats' ] ) ) {
            return $merchants[ 'popular' ][ $merchant ][ 'formats' ];
        }
        elseif( isset( $merchants[ 'pro_merchants' ][ $merchant ][ 'formats' ] ) && !empty( $merchants[ 'pro_merchants' ][ $merchant ][ 'formats' ] ) ) {
            return $merchants[ 'pro_merchants' ][ $merchant ][ 'formats' ];
        }
        elseif( isset( $merchants[ 'free_merchants' ][ $merchant ][ 'formats' ] ) && !empty( $merchants[ 'free_merchants' ][ $merchant ][ 'formats' ] ) ) {
            return $merchants[ 'free_merchants' ][ $merchant ][ 'formats' ];
        }
        return [ 'xml', 'yml', 'csv', 'text', 'tsv', 'json' ];
    }

    /**
    * @desc Retrieves Supported Separators for CSV Format
    * @since 7.3.0
    * @param $merchant
    * @return mixed|string[]
    */
    public static function get_csv_feed_separators( $merchant ) {
        $merchants = self::get_merchants();

        if( isset( $merchants[ 'popular' ][ $merchant ][ 'csv_separators' ] ) && !empty( $merchants[ 'popular' ][ $merchant ][ 'csv_separators' ] ) ) {
            return $merchants[ 'popular' ][ $merchant ][ 'csv_separators' ];
        }
        elseif( isset( $merchants[ 'pro_merchants' ][ $merchant ][ 'csv_separators' ] ) && !empty( $merchants[ 'pro_merchants' ][ $merchant ][ 'csv_separators' ] ) ) {
            return $merchants[ 'pro_merchants' ][ $merchant ][ 'csv_separators' ];
        }
        elseif( isset( $merchants[ 'free_merchants' ][ $merchant ][ 'csv_separators' ] ) && !empty( $merchants[ 'free_merchants' ][ $merchant ][ 'csv_separators' ] ) ) {
            return $merchants[ 'free_merchants' ][ $merchant ][ 'csv_separators' ];
        }
        return [ 'comma', 'semi_colon', 'pipe' ];
    }

    /**
     * @desc Renders the merchant dropdown in feed
     * @since 7.3.0
     * @param $class
     * @param $id
     * @param $name
     * @param $selected
     * @return void
     */
    public static function render_merchant_dropdown( $class, $id, $name, $selected ) {
		$all_merchants[''] = array(
			'-1'    => array(
				'free'   => true,
				'status' => 1,
				'name'   => 'Select your merchant'
			),
		);
		$all_merchants = array_merge( $all_merchants, self::get_merchants() );
		$is_premium    = apply_filters( 'wpfm_is_premium', false );

		echo '<select class="' .esc_attr( $class ). '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';
		foreach ($all_merchants as $groupLabel => $group) {
			if ( !empty($groupLabel)) {
				if ( $groupLabel === 'popular' ) {
					$groupLabel = 'Popular Merchants';
				}
				elseif ( $groupLabel === 'pro_merchants' ) {
					$groupLabel = 'Pro Merchants';
				}
				elseif ( $groupLabel === 'free_merchants' ) {
					$groupLabel = 'Others';
				}
				$disabled = ( $groupLabel === 'Pro Merchants' && !$is_premium ) ? 'disabled' : '';
                ob_start();?>
                <optgroup label='<?php echo esc_html($groupLabel); ?>' <?php echo esc_html($disabled); ?>>
                <?php echo ob_get_clean();
			}

			foreach ($group as $key => $item) {
				$value = $item['name'];

				if ( $selected == $key ) {
                    ob_start();?>
                        <option value='<?php echo esc_attr($key); ?>' selected='selected'><?php echo esc_html($value); ?></option>
                    <?php echo ob_get_clean();
				}else{
				    ob_start();?>
                        <option value='<?php echo esc_attr($key); ?>'><?php echo esc_html($value); ?></option>
                    <?php echo ob_get_clean();
				}
			}

			if ( !empty($groupLabel)) {
				echo "</optgroup>";
			}
		}

		echo "</select>";
	}
}