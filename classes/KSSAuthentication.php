<?php

namespace Modules\Plugin\KlarnaShippingService;

use PrestaShopException;

class KSSAuthentication
{
    /**
     * validateDigest
     *
     * @param string $nounce
     * @param string $digestToken
     * @return bool
     * @throws PrestaShopException
     */
    public function validateDigest(string $nounce, string $digestToken): bool
    {
        $secret = \Configuration::get('KSS_SECRET');
        if ($secret === '') {
            throw new PrestaShopException('KSS is not configured properly.');
        }
        $digest = hash("sha256", $nounce . $secret);
        if ($digest == $digestToken) {
            return true;
        }
        return false;
    }

    /**
     * generateBearerToken
     *
     * @param string $nounce
     * @param string $digestToken
     * @return string
     * @throws PrestaShopException
     */
    public function generateBearerToken(string $nounce, string $digestToken): string
    {
        if (!$this->validateDigest($nounce, $digestToken)) {
            return "";
        }

        $hashingSecret = \Configuration::get('KSS_HASHING_SECRET');
        if ($hashingSecret === '') {
            throw new PrestaShopException('KSS is not configured properly.');
        }
        $JWTHeader = [
            "alg" => "HS256",
            "typ" => "JWT"
        ];
        $time = time();
        $JWTPayLoad = [
            "iat" => $time,
            "exp" => $time + (60 * 60)
        ];
        $encodedHeader = base64_encode(json_encode($JWTHeader));
        $encodedPayLoad = base64_encode(json_encode($JWTPayLoad));
        $JWTSignature = hash_hmac("sha256", $encodedHeader . "." . $encodedPayLoad, $hashingSecret);
        return $encodedHeader . "." . $encodedPayLoad . "." . $JWTSignature;
    }

    /**
     * validateBearerToken
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function validateBearerToken(): bool
    {
        $hashingSecret = \Configuration::get('KSS_HASHING_SECRET');
        if ($hashingSecret === '') {
            throw new PrestaShopException('KSS is not configured properly.');
        }
        $authorizationToken = trim(strstr($_SERVER['HTTP_AUTHORIZATION'], ' '));
        if (empty($authorizationToken)) {
            return false;
        }
        $JWTarray = explode(".", $authorizationToken);
        if (count($JWTarray) < 3) {
            return false;
        }
        $JWTBearerHeader = json_decode(base64_decode($JWTarray[0]), true);
        $JWTBearerPayLoad = json_decode(base64_decode($JWTarray[1]), true);
        $JWTSignature = "";
        if ($JWTBearerHeader["alg"] === "HS256" && $JWTBearerHeader["typ"] === "JWT") {
            $JWTSignature = hash_hmac("sha256", $JWTarray[0] . "." . $JWTarray[1], $hashingSecret);
        }
        $tokenExpired = $JWTBearerPayLoad["exp"] < time();
        if ($JWTSignature === $JWTarray[2] && !$tokenExpired) {
            return true;
        }
        return false;
    }
}
