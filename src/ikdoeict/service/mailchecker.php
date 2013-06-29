<?php
namespace Ikdoeict\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailChecker implements ServiceProviderInterface {

    public $inbox;
    
    function boot(Application $app) {
    }

    // Define shared service
    function register(Application $app) {
        $app['mail.checker'] = $app->share(function() {
                $mc = new MailChecker();
                $mc->inbox = null;
                return $mc;
        });
    }

    //Check given email and password
    public function check($email, $passw, $server, $port, $ssl, $valCert, $service) {
        $connString = '{';
        $connString .= $server;
        $port != null ? $connString .= ':' . $port : null;
        $connString .= '/' . $service;
        $ssl ? $connString .= '/ssl' : null;
        $valCert ? null : $connString .= '/novalidate-cert';
        $connString .= '}INBOX';
                
        $this->inbox = @imap_open($connString, $email, $passw);

        if (!imap_errors()) {
            return true;
        }
        else {
            return false;
        }
    }

}