<?php
namespace Appsero\Helper;

use EDD_SL_License;
use EDD_SL_Download;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Traits\Hooker;

    public function __construct() {
        $this->action( 'edd_complete_download_purchase', 'edd_add_license', 20, 5 );

        $this->action( 'edd_sl_post_revoke_license', 'edd_cancel_order', 10, 2 );

        $this->action( 'edd_sl_post_delete_license', 'edd_cancel_order', 10, 2 );
    }

    /**
     * Send request to add license
     */
    public function edd_add_license( $download_id = 0, $payment_id = 0, $type = 'default', $cart_item = [], $cart_index = 0 ) {
        // Bail if this cart item is for a renewal
        if( ! empty( $cart_item['item_number']['options']['is_renewal'] ) ) {
            return;
        }

        // Bail if this cart item is for an upgrade
        if( ! empty( $cart_item['item_number']['options']['is_upgrade'] ) ) {
            return;
        }

        $purchased_download = new EDD_SL_Download( $download_id );
        if ( ! $purchased_download->is_bundled_download() && ! $purchased_download->licensing_enabled() ) {
            return;
        }

        $licenses = edd_software_licensing()->get_licenses_of_purchase( $payment_id );

        if ( false !== $licenses ) {
            foreach( $licenses as $license ) {
                $this->send_add_license_request( $license );
            }
        }
    }

    /**
     * Send request to appsero server to add license
     */
    public function send_add_license_request( $license ) {
        $status = ('active' == $license->status || 'inactive' == $license->status) ? 1 : 0;
        $expiration = $license->expiration ? date( 'Y-m-d H:i:s', (int) $license->expiration ) : '';

        $route = 'public/' . $license->download_id . '/add-license';

        $body = [
            'key'               => $license->key,
            'status'            => $status,
            'activation_limit'  => $license->activation_limit ?: '',
            'active_sites'      => (int) $license->activation_count,
            'expire_date'       => $expiration,
            'source_identifier' => $license->id,
            'variation_source'  => (int) $license->price_id ?: '',
            'license_source'    => 'EDD',
        ];

        appsero_helper_remote_post( $route, $body );
    }

    /**
     * Hook trigger after cancel order
     * It will deactive a license on appsero server
     */
    public function edd_cancel_order( $license_id, $payment_id ) {
        $license = new EDD_SL_License( $license_id );

        $route = 'public/' . $license->download_id . '/cancel-order';

        $body = [
            'key'            => $license->key,
            'license_source' => 'EDD',
            'order_id'       => $payment_id,
        ];

        appsero_helper_remote_post( $route, $body );
    }

}