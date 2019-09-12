<?php
namespace Imbo\EventListener\AccessToken;

/**
 * Implementation of the default SHA256 access token generator (HMAC-ed with the private key)
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package AccessToken
 */
class SHA256 extends AccessTokenGenerator {
    /**
     * {@inheritdoc}
     */
    public function generateSignature($argumentKey, $data, $privateKey) {
        return hash_hmac('sha256', $data, $privateKey);
    }
}