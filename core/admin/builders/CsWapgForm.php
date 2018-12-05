<?php namespace WooGateWayCoreLib\admin\builders;
/**
 * From Builder
 * 
 * @package WAPG Admin 
 * @since 1.0.0
 * @author CodeSolz <customer-service@codesolz.com>
 */

if ( ! defined( 'CS_WAPG_VERSION' ) ) {
    exit;
}

use WooGateWayCoreLib\lib\Util;

class CsWapgForm {
    
    /**
     * Admin Settings Form
     * 
     * @param type $obj
     * @return type
     */
    public static function getAdminSettings( $obj ){
        $custom_fields = get_option( $obj->cs_altcoin_fields );
        $custom_fields =  empty( $custom_fields ) ? '' : json_decode($custom_fields);
        $customFields = array();
        if( ! empty( $custom_fields ) && $custom_fields->count > 0 ){
            $i = 1; unset($custom_fields->count);
            $allCoins = self::getAltCoinsSelect();
            foreach($custom_fields as $field){
                $customFields += array(
                    "altCoinName_{$i}" => array(
                        'title'		=> __( 'Select AltCoin', 'woo-altcoin-payment-gateway' ),
                        'type'		=> 'select',
                        'class'                => 'alt-coin',
                        'custom_attributes' => array(
                            'data-coinid' => $i
                        ),
                        'desc_tip'	=> __( 'Select AltCoin where do you want to receive your payment', 'woo-altcoin-payment-gateway' ),
                        'default'       => $field->id,
                        'options'       => $allCoins
                    ),
                    "altCoinAddress_{$i}" => array(
                        'title'		=> sprintf(__( 'Enter %s %s %s address.', 'woo-altcoin-payment-gateway' ), '<span class="altCoinVallabel_'.$i.'">', $allCoins[$field->id], '</span>'),
                        'type'		=> 'text',
                        'class'         => "alt-value-{$i}",
                        'default'       => $field->address,
                        'placeholder'   => __( 'please enter here your coin address', 'woo-altcoin-payment-gateway' )
                    )
                );
                $i++;
            }
        }else{
            $customFields = array(
                'altCoinName_1' => array(
                'title'		=> __( 'Select AltCoin', 'woo-altcoin-payment-gateway' ),
                'type'		=> 'select',
                'class'                => 'alt-coin',
                'custom_attributes' => array(
                    'data-coinid' => 1
                ),
                'desc_tip'	=> __( 'Select AltCoin where do you want to receive your payment', 'woo-altcoin-payment-gateway' ),
                'default'  => '0',
                'options' => self::getAltCoinsSelect()
            ),
            'altCoinAddress_1' => array(
                'title'		=> sprintf(__( 'Enter %s altcoin %s address.', 'woo-altcoin-payment-gateway' ), '<span class="altCoinVallabel_1">', '</span>'),
                'type'		=> 'text',
                'class'            => 'alt-value-1',
                'placeholder'   => __( 'please enter here your coin address', 'woo-altcoin-payment-gateway' )
                )
            );
        }
        
        
        return $obj->form_fields = array(
            'enabled' => array(
                    'title'		=> __( 'Enable / Disable', 'woo-altcoin-payment-gateway' ),
                    'label'		=> __( 'Enable AltCoin payment gateway', 'woo-altcoin-payment-gateway' ),
                    'type'		=> 'checkbox',
                    'default'	=> 'no',
            ),
            'title' => array(
                    'title'		=> __( 'Title', 'woo-altcoin-payment-gateway' ),
                    'type'		=> 'text',
                    'desc_tip'          => __( 'Payment title of checkout process.', 'woo-altcoin-payment-gateway' ),
                    'default'           => __( 'AltCoin', 'woo-altcoin-payment-gateway' ),
            ),
            'description' => array(
                    'title'		=> __( 'Description', 'woo-altcoin-payment-gateway' ),
                    'type'		=> 'textarea',
                    'desc_tip'          => __( 'Payment title of checkout process.', 'woo-altcoin-payment-gateway' ),
                    'default'           => __( 'Make your payment directly into our AltCoin address. Your order won’t be shipped until the funds have cleared in our account.', 'woo-altcoin-payment-gateway' ),
                    'css'		=> 'max-width:450px;'
            ),
            'payment_icon_url' => array(
                    'title'		=> __( 'Payment Icon url', 'woo-altcoin-payment-gateway' ),
                    'type'		=> 'text',
                    'desc_tip'          => __( 'Image next to the gateway’s name', 'woo-altcoin-payment-gateway' ),
            ),
            'loader_gif_url' => array(
                    'title'		=> __( 'Calculator Gif URL', 'woo-altcoin-payment-gateway' ),
                    'type'		=> 'text',
                    'desc_tip'          => __( 'Calculating gif when price being calculate', 'woo-altcoin-payment-gateway' ),
            ),
        ) + $customFields;
    }
    
    /**
     * Generate Custom Form
     * 
     * @param type $refObj
     */
    public static function customForm( $refObj ){
        if ( $description = $refObj->get_description() ) {
            echo wpautop( wptexturize( $description ) );
        }
        $fields = array();
        
        $default_fields = array(
                'alt-con' => '<p class="form-row form-row-wide altCoinSelect">
                        <label for="' . esc_attr( $refObj->id ) . '-alt-name">' . __( 'Please select coin you want to pay:', 'woo-altcoin-payment-gateway' ) . ' <span class="required">*</span></label>'.
                        self::getActiveAltCoinSelect( $refObj )
                .'</p><div class="coin-detail"><!--coin calculation--></div>'
        );
        
        $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_altcoin_form_fields', $default_fields, $refObj->id ) );
        ?>
        <fieldset id="wc-<?php echo esc_attr( $refObj->id ); ?>-cc-form" class='wc-altcoin-form wc-payment-form'>
                <?php do_action( 'woocommerce_altcoin_form_start', $refObj->id ); ?>
                <?php
                        foreach ( $fields as $field ) {
                                echo $field;
                        }
                ?>
                <?php do_action( 'woocommerce_altcoin_form_end', $refObj->id ); ?>
                <div class="clear"></div>
        </fieldset>
        <?php
    }
    
    /**
     * Output field name HTML
     *
     * Gateways which support tokenization do not require names - we don't want the data to post to the server.
     *
     * @since  2.6.0
     * @param  string $name
     * @return string
     */
    public static function field_name( $id, $name ) {
        return ' name="' . esc_attr( $id . '-' . $name ) . '" ';
    }
    
    /**
     * All Alt coin in select
     * 
     * @return string
     */
    public static function getAltCoinsSelect( $type = false ){
        $currencies = \file_get_contents(CS_WAPG_PLUGIN_ASSET_URI.'/js/currencies.json');

        if( $type == 'html' ){
            $select = '<option value="0">======== ' . __( 'Please Slect An AltCoin!', 'woo-altcoin-payment-gateway') . ' ========</option>';
        }else{
            $select = array( '0' => '===='.__( 'Please Slect An AltCoin!', 'woo-altcoin-payment-gateway').'====' );
        }
        
        foreach( \json_decode($currencies) as $currency ){
            $symbol = Util::encode_html_chars( $currency->symbol );
            $name = Util::encode_html_chars( $currency->name);
            $id = Util::encode_html_chars($currency->id);
            if( $type == 'html' ){
                $select .= '<option value="'. $id .'">'. $name .'('. $symbol . ')</option>';
            }else{
                $select += array( 
                    $id =>" {$name}({$symbol})"
                );
            }
        }
        
        return $select;
    }
    
    /**
     * Get Active altCoins
     * 
     * @param type $refObj
     * @return type
     */
    public static function getActiveAltCoinSelect( $refObj ){
        $custom_fields = get_option( $refObj->cs_altcoin_fields );
        $altCoin = '<select name="altcoin" id="CsaltCoin" class="select">';
        if( empty($custom_fields)){
            $altCoin .= '<option value="0">===='.__('Sorry! No AltCoin Payment is actived!', 'woo-altcoin-payment-gateway').'====</option>';
        }else{
            $altCoin .= '<option value="0">===='.__( 'Please Slect An AltCoin!', 'woo-altcoin-payment-gateway').'====</option>';
            $custom_fields = json_decode($custom_fields);
            $allAltCoins = self::getAltCoinsSelect();
            unset($custom_fields->count);
            foreach( $custom_fields as $field){
                $altCoin .= '<option value="'.$field->id.'__'.$field->address.'">'.$allAltCoins[$field->id].'</option>';
            }
            return $altCoin .='</select>';
        }
    }
}