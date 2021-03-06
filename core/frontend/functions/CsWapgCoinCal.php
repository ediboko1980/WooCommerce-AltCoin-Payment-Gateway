<?php namespace WooGateWayCoreLib\frontend\functions;
/**
 * Frontend Functions
 * 
 * @package WAPG Admin 
 * @since 1.0.0
 * @author CodeSolz <customer-service@codesolz.com>
 */

if ( ! defined( 'CS_WAPG_VERSION' ) ) {
    exit;
}

use WooGateWayCoreLib\lib\Util;
use WooGateWayCoreLib\admin\functions\CsAdminQuery;

class CsWapgCoinCal {
    
    /**
     * Currency converter api
     *
     * @var type 
     */
    private $currency_converter_api_url = 'https://api.coinmarketstats.online/fiat/v1/ticker/%s';
    
    /**
     *
     * @var type Coinmarketcap public api
     */
    private $coinmarketcap_api_url = "https://api.coinmarketcap.com/v1/ticker/%s";

    /**
     * Get Coin Price
     * 
     * @param type $coinName
     */
    public function calcualteCoinPrice(){
        global $woocommerce;
       
        $coin_id = sanitize_text_field($_POST['data']['coin_id']);
        
        
        if( empty($coin_id) ){
            wp_send_json(array('response' => false, 'msg' => __( 'Something Went Wrong! Please try again.', 'woo-altcoin-payment-gateway' ) ) );
        }else{
            $custom_fields = CsAdminQuery::get_coin_by( 'id', $coin_id );
            if( empty( $custom_fields ) ){
                wp_send_json(array('response' => false, 'msg' => __( 'Something Went Wrong! Please try again.', 'woo-altcoin-payment-gateway' ) ) );
            }
            
            $coinFullName = $custom_fields->name . '( ' . $custom_fields->coin_web_id . ' )';
            $coinId  = $custom_fields->coin_web_id;
            $coinAddress = $custom_fields->address;
            $coinName = $custom_fields->coin_web_id;
            $cartTotal = $woocommerce->cart->total;
            $store_currency = get_woocommerce_currency();
            $currency_symbol = get_woocommerce_currency_symbol();
            
            //apply special discount if active
            $special_discount = false;
            $special_discount_msg = ''; $special_discount_amount = ''; $cartTotalAfterDiscount = '';
            if( true === $this->is_offer_valid( $custom_fields ) ){
                $cartTotalAfterDiscount = $cartTotal = $this->apply_special_discount( $cartTotal, $custom_fields );
                $special_discount = true;
                $special_discount_type = Util::special_discount_msg( $currency_symbol, $custom_fields );
                $special_discount_msg = $special_discount_type['msg'];
                $special_discount_amount = $special_discount_type['discount'];
            }
            
            if( $store_currency != 'USD' ){
                $cartTotal = $this->store_currency_to_usd( $store_currency, $cartTotal );
                if( isset( $cartTotal['error' ] ) ){
                    wp_send_json( array('response' => false, 'msg' => $cartTotal['response'] ) );
                }
            }
            
            $coin_price = $this->get_coin_martket_price( $coinId );
            if( isset( $coin_price['error' ] ) ){
                wp_send_json( array('response' => false, 'msg' => $coin_price['response'] ) );
            }
            
            //calculate the coin
            $totalCoin = $this->get_total_coin_amount( $coin_price, $cartTotal );
            
            //return status
            wp_send_json( array( 'response' => true, 'cartTotal' => $woocommerce->cart->total, 'cartTotalAfterDiscount' => $cartTotalAfterDiscount, 
                'currency_symbol' => $currency_symbol, 'totalCoin' => $totalCoin,
                'coinPrice' => $coin_price, 'coinFullName' => $coinFullName,
                'coinName' => $coinName, 'coinAddress' => $coinAddress,
                'special_discount_status' => $special_discount, 'special_discount_msg' => $special_discount_msg, 
                'special_discount_amount' => $special_discount_amount 
            ));
        }
    }
    
    /**
     * Check offer is valid
     * 
     * @return boolean
     */
    private function is_offer_valid( $customField ){
        if( $customField->offer_status != 1 ){ //offer expired
            return false;
        }
        
        //check if offer end date not found
        if( empty( $customField->offer_end ) ){
            return false;
        }
        $currDateTime = Util::get_current_datetime();
        
        //check offer expired
        if( $currDateTime > $customField->offer_end || $currDateTime < $customField->offer_start ){
            return false;
        }
        
        return true;
    }
    
    /**
     * Add special discount
     */
    private function apply_special_discount( $cartTotal, $customField ){
        if( $customField->offer_type == 1 ){
            //percent
            $final_amount = (int)$cartTotal - ( ( $customField->offer_amount / 100 ) * $cartTotal );
        }
        elseif( $customField->offer_type == 2 ){
            //flat amount
            $final_amount = (int)$cartTotal - $customField->offer_amount;
        }
        return $final_amount;
    }

    /**
     * Get converted store currency to usd
     */
    private function store_currency_to_usd( $store_currency, $cart_total ){
        $key = strtolower($store_currency);
        $api_url = sprintf( $this->currency_converter_api_url , $key );
        $response = Util::remote_call( $api_url );
        if( isset( $response['error' ] ) ){
            return $response;
        }
        
        $response = json_decode( $response );
        
        if( is_object( $response ) ){
            if( $response->data[0]->currency == $key ){
                return  $response->data[0]->usd * $cart_total;
            }else{
                return array(
                    'error' => true,
                    'response' => __( 'Currency not found. Please contact support@codesolz.net to add your currency.', 'woo-altcoin-payment-gateway' )
                );
            }
        }
        
        return array(
            'error' => true,
            'response' => __( 'Currency converter not working! Please contact administration.', 'woo-altcoin-payment-gateway' )
        );
    }
    
    /**
     * Get coin price from coin market cap
     */
    private function get_coin_martket_price( $coin_slug ){
        $api_url = sprintf( $this->coinmarketcap_api_url , $coin_slug );
        $response = Util::remote_call( $api_url );
        if( isset( $response['error' ] ) ){
            return $response;
        }
        
        $getMarketPrice = json_decode( $response );
        if( isset( $getMarketPrice[0]->price_usd ) ){
            return $getMarketPrice[0]->price_usd;
        }
        
        return array(
            'error' => true,
            'response' => __( 'Coinmarketcap api error. Please contact administration.', 'woo-altcoin-payment-gateway' )
        );
    }
    
    /**
     * Get total coin amount
     * 
     * @param type $coin_price
     * @param type $cartTotal
     * @return type
     */
    private function get_total_coin_amount( $coin_price, $cartTotal){
        return round( ( ( 1 / $coin_price ) * $cartTotal ), 8 );
    }
    
}
