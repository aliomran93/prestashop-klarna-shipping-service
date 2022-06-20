<?php

namespace Modules\Plugin\KlarnaShippingService;

class AuthController extends \ModuleFrontController
{
    /**
     * @throws \PrestaShopException
     */
    public function initContent()
    {
        /** @var \PhpDoc\Plugin\KlarnaShippingService\AuthRequest $data */
        $data = \ModulesLib::ajaxData(true);
        if (empty($data->secret->digest)) {
            header($_SERVER['SERVER_PROTOCOL'] . " 401 Unauthorized");
            die();
        }

        $digestToken = $data->secret->digest;
        $nounce = $data->secret->nonce;
        $authentication = new KSSAuthentication();
        $JWTToken = $authentication->generateBearerToken($nounce, $digestToken);
        if ($JWTToken === "") {
            header($_SERVER['SERVER_PROTOCOL'] . " 401 Unauthorized");
            die();
        }
        header("Content-type: application/json");
        header($_SERVER['SERVER_PROTOCOL'] . " 201 Created");
        die(json_encode(array("token" => $JWTToken)));
    }
}
