<?php

namespace kushki\lib;

class RequestHandler {
    public function call($request) {
        $requestBody = $request->getBody();
        $responseRaw = \Httpful\Request::post($request->getUrl())
                                       ->contentType($request->getContentType())
                                       ->withStrictSSL()
                                       ->body($requestBody)
                                       ->send();
        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }
}

?>
