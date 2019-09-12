<?php
namespace Imbo\Model;

/**
 * Model interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
interface ModelInterface {
    /**
     * Return the "data" found in the model
     *
     * @return mixed
     */
    function getData();
}
