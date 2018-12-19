<?php
namespace Appsero\Helper;

/**
 * API Class
 */
class Edd {

    /**
     * Products REST API Class
     *
     * @return Edd\Downloads
     */
    public function products() {
        require_once __DIR__ . '/Edd/Downloads.php';

        return new Edd\Downloads();
    }

    /**
     * Licenses REST API Class
     *
     * @return Edd\Licenses
     */
    public function orders() {
        require_once __DIR__ . '/Edd/Orders.php';

        return new Edd\Orders();
    }

    /**
     * Licenses REST API Class
     *
     * @return Edd\Licenses
     */
    public function licenses() {
        require_once __DIR__ . '/Edd/Licenses.php';

        return new Edd\Licenses();
    }
}