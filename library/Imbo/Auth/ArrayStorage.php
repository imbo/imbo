<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth;

/**
 * Store users as an array
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Auth
 */
class ArrayStorage implements UserLookupInterface {
    /**
     * Users
     *
     * @var array
     */
    private $users = array();

    /**
     * Class constructor
     *
     * @param array $users The users
     */
    public function __construct(array $users) {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey($publicKey) {
        return isset($this->users[$publicKey]) ? $this->users[$publicKey] : null;
    }

    /**
     * @see http://www.php.net/manual/en/class.iterator.php
     */
    public function rewind() {
        reset($this->users);
    }

    /**
     * @see http://www.php.net/manual/en/class.iterator.php
     */
    public function current() {
        return current($this->users);
    }

    /**
     * @see http://www.php.net/manual/en/class.iterator.php
     */
    public function key() {
        return key($this->users);
    }

    /**
     * @see http://www.php.net/manual/en/class.iterator.php
     */
    public function next() {
        return next($this->users);
    }

    /**
     * @see http://www.php.net/manual/en/class.iterator.php
     */
    public function valid() {
        return key($this->users) !== null;
    }
}
