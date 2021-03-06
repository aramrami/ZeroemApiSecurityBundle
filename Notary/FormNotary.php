<?php

namespace Zeroem\ApiSecurityBundle\Notary;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;


class FormNotary implements NotaryInterface
{
    private static $requiredFields = array("api_token","signature","date","resource");
    private static $optionalFields = array();

    public function getUsername(Request $request) {
        return return $this->getSource()->get("api_token", false);
    }

    public function canVerify(Request $request) {
        $source = $this->getSource();

        foreach(static::$requiredFields as $field) {
            if(!$source->has($field)) {
                return false;
            }
        }
        
        return true;
    }

    public function sign(UserInterface $signator, Request $request) {
        $signature = $this->makeSignature($signator,$request);

        if(false !== $signature) {
            $request->headers->set("authorization","API {$signator->getUsername()}:{$signature}");
            return true;
        }

        return false;
    }

    private function makeSignature(UserInterface $signator, Request $request) {
        $parts = array();
        $parts[] = $request->getMethod();
        if($request->headers->has("content-md5")) {
            $parts[] = $request->headers->get("content-md5");
        } else if(in_array($request->getMethod(),self::$requiresContentMd5)) {
            return false;
        }

        $parts[] = $request->headers->get("date");

        // the Request object already sorts the QS parameters
        $parts[] = $request->getUri();

        $message = implode("\n",$parts);

        return $calculatedSignature = hash_hmac("sha256",$message,$signator->getPassword());                
    }

    /**
     * Was the request made within the last 5 minutes?
     * 
     * @param integer $timestamp unix timestamp
     * @return boolean
     */
    protected function isValidTimestamp(\DateTime $date) {
        return  (abs(time() - $date->getTimestamp()) < 300);
    }

    public function verify(UserInterface $signator, Request $request) {
        // Enforce a time limit on the request
        if(!$this->isValidTimestamp($request->headers->getDate("date"))) {
            return false;
        }

        $parts = array();
        if(preg_match(self::$authRegex,$this->getAuthorizationHeader($request),$parts)) {
            $requestUser = $parts[1];
            $requestSignature = $parts[2];
            
            if($requestUser == $signator->getUsername()) {
                $calculatedSignature = $this->makeSignature($signator,$request);

                if($calculatedSignature == $requestSignature) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getAuthorizationHeader(Request $request) {
        if($request->headers->has("authorization")) {
            return $request->headers->get("authorization");
        } else {
            $headers = apache_request_headers();

            if(isset($headers["Date"]) && $headers["Date"] == $request->headers->get("date")) {
                if(isset($headers["Authorization"])) {
                    return $headers["Authorization"];
                }
            }
        }

        return false;
    }

    private function getSource(Request $request) {
        if("POST" === $request->getMethod()) {
            return $request->request;
        } else {
            return $request->query;
        }
    }

    private function method
}

