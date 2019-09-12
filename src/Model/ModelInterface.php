<?php
namespace Imbo\Model;

/**
 * Model interface
 *
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
