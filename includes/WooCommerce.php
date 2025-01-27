<?php
namespace Appsero\Helper;

/**
 * API Class
 */
class WooCommerce {

    /**
     * Products REST API Class
     *
     * @return WooCommerce\Downloads
     */
    public function products() {
        require_once __DIR__ . '/WooCommerce/Products.php';

        return new WooCommerce\Products();
    }

    /**
     * Licenses REST API Class
     *
     * @return WooCommerce\Licenses
     */
    public function orders() {
        require_once __DIR__ . '/WooCommerce/Orders.php';

        return new WooCommerce\Orders();
    }

    /**
     * Licenses REST API Class
     *
     * @return WooCommerce\Licenses
     */
    public function licenses() {
        require_once __DIR__ . '/WooCommerce/Licenses.php';

        return new WooCommerce\Licenses();
    }

    /**
     * Activations REST API Class
     *
     * @return WooCommerce\Activations
     */
    public function activations() {
        require_once __DIR__ . '/WooCommerce/Activations.php';

        return new WooCommerce\Activations();
    }

    /**
     * Subscriptions REST API Class
     *
     * @return WooCommerce\Activations
     */
    public function subscriptions() {
        require_once __DIR__ . '/WooCommerce/Subscriptions.php';

        return new WooCommerce\Subscriptions();
    }
}
