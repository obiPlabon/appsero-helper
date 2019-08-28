<?php
namespace Appsero\Helper\Edd;

use EDD_Payment;
use EDD_SL_License;
use EDD_SL_Download;
use Appsero\Helper\Traits\Hooker;
use Appsero\Helper\Edd\UseCases\SendRequestsHelper;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Hooker, SendRequestsHelper;

    /**
     * Constructor of EDD SendRequests class
     */
    public function __construct() {

        // EDD add new order and license
        $this->action( 'edd_complete_download_purchase', 'add_new_order_and_license', 20, 5 );

        // EDD update order and license status
        $this->action( 'edd_update_payment_status', 'update_order_and_license', 20, 3 );

        // Cancel order on payment deletion
        $this->action( 'edd_payment_delete', 'delete_order_and_license', 20, 1 );

        // Update license when an item is removed from a payment
        $this->action( 'edd_remove_download_from_payment', 'delete_order_and_license', 20, 2 );
    }

    /**
     * Send request to add order with license
     */
    public function add_new_order_and_license( $download_id = 0, $payment_id = 0, $type = 'default', $cart_item = [], $cart_index = 0 ) {
        $connected = get_option( 'appsero_connected_products', [] );

        // Check the product is connected with appsero
        if ( in_array( $download_id, $connected ) ) {
            $payment = new EDD_Payment( $payment_id );

            $this->add_or_update_order_and_license( $payment, $download_id );
        }
    }

    /**
     * EDD update order and license
     */
    public function update_order_and_license( $payment_id, $new_status, $old_status ) {

        if ( 'pending' == $old_status && 'publish' == $new_status ) {
            return;
        }

        $payment = new EDD_Payment( $payment_id );
        $connected = get_option( 'appsero_connected_products', [] );

        foreach ( $payment->downloads as $download ) {
            // Check the product is connected with appsero
            if ( in_array( $download['id'], $connected ) ) {
                $this->add_or_update_order_and_license( $payment, $download['id'] );
            }
        }
    }

    /**
     * Update or create order and license
     */
    private function add_or_update_order_and_license( $payment, $download_id ) {
        require_once __DIR__ . '/Orders.php';

        $ordersObject = new Orders();
        $ordersObject->download_id = $download_id;
        $order = $ordersObject->get_order_data( $payment );

        $order['licenses'] = $this->get_order_licenses( $payment->ID, $download_id );

        $route = 'public/' . $download_id . '/update-order';

        $api_response = appsero_helper_remote_post( $route, $order );
        $response = json_decode( wp_remote_retrieve_body( $api_response ), true );

        if ( isset( $response['license'] ) ) {
            $this->create_appsero_license( $response['license'], $order, $download_id );
        }
    }

    /**
     * Cancel order and license on delete order
     */
    public function delete_order_and_license( $payment_id, $download_id = null ) {
        $payment = new EDD_Payment( $payment_id );

        if ( empty( $download_id ) ) {
            foreach ( $payment->downloads as $download ) {
                $this->send_delete_order_and_license_request( $payment, $download['id'] );
            }
        } else {
            $this->send_delete_order_and_license_request( $payment, $download_id );
        }
    }

    /**
     * Send Delete request
     */
    private function send_delete_order_and_license_request( $payment, $download_id ) {
        $connected = get_option( 'appsero_connected_products', [] );

        // Check the product is connected with appsero
        if ( in_array( $download_id, $connected ) ) {
            $route = 'public/' . $download_id . '/delete-order/' . $payment->ID;

            appsero_helper_remote_post( $route, [] );
        }
    }

    /**
     * Create appsero license from response
     */
    private function create_appsero_license( $license, $orderData, $product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';

        $appsero_license = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE `source_id` = " . $license['id'] . " LIMIT 1", ARRAY_A );

        if ( $appsero_license ) {
            // Update
            $wpdb->update( $table_name, [
                'product_id'       => $product_id,
                'variation_id'     => $orderData['variation_id'] ? $orderData['variation_id'] : null,
                'order_id'         => $orderData['id'],
                'user_id'          => $orderData['customer']['id'],
                'key'              => $license['key'],
                'status'           => $license['status'],
                'activation_limit' => $license['activation_limit'],
                'expire_date'      => $license['expire_date']['date'],
            ], [
                'id' => $appsero_license['id']
            ]);
        } else {
            // Create
            $wpdb->insert( $table_name, [
                'product_id'       => $product_id,
                'variation_id'     => $orderData['variation_id'] ? $orderData['variation_id'] : null,
                'order_id'         => $orderData['id'],
                'user_id'          => $orderData['customer']['id'],
                'key'              => $license['key'],
                'status'           => $license['status'],
                'activation_limit' => $license['activation_limit'],
                'expire_date'      => $license['expire_date']['date'],
                'source_id'        => $license['id'],
            ] );
        }
    }

}