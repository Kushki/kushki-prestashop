<?php

namespace kushki\lib;

use Httpful\Test\requestTest;
use kushki\lib\KushkiConstant;

class KushkiRequest {

    protected $url         = "";
    protected $params      = array();
    protected $contentType = "";

    public function __construct($url, $params = array(),
                                $contentType = KushkiConstant::CONTENT_TYPE) {
        $this->url = $url;
        $this->params = $params;
        $this->contentType = $contentType;
    }

    public function getParameter($parameterName) {
        if (isset($this->params[$parameterName])) {
            return $this->params[$parameterName];
        }
        throw new KushkiException(KushkiConstant::PARAMETER_DO_NOT_EXIST);
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getParams() {
        return $this->params;
    }

    public function getBody() {
        return $this->getEncryptBody();
    }

    private function getPlainBody() {
        return json_encode($this->getParams());
    }

    private function getEncryptBody() {
        $contentToEncrypt = $this->getPlainBody();
        $contentEncrypted = $this->encryptMessageChunk($contentToEncrypt);

        $responseEncripted = array();
        $responseEncripted['request'] = $contentEncrypted;
        return json_encode($responseEncripted);
    }

    private function encryptMessageChunk($requestMessage) {
        $contentEncripted = "";
        $chunks = str_split($requestMessage, 117);
        foreach ($chunks as $chunk) {
            $cipherData = "";
            $encryptResult = openssl_public_encrypt($chunk, $cipherData, KushkiConstant::KUSHKI_PUBLIC_KEY, OPENSSL_PKCS1_PADDING);
            if ($encryptResult === true) {
                $chnkString = base64_encode($cipherData);
                $chnkString = str_replace("\n", "", $chnkString);
                $contentEncripted .= $chnkString . "<FS>";
            } else {
                throw new KushkiException("Error encrypting with the public key");
            }
        }
        return $contentEncripted;
    }

}

?>
