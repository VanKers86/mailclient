<?php
namespace Ikdoeict\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailChecker implements ServiceProviderInterface {

    public $inbox;
    private $connection;
    public $msgCount;
    public $mailsPerPage = 10;
    
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
                
        $this->connection = @imap_open($connString, $email, $passw);

        if (!imap_errors()) {
            $this->msgCount = imap_num_msg($this->connection);
            $this->getMails(1);           
            return true;
        }
        else {
            return false;
        }
    }
    
    public function getMails($pageNr) {
        $start = $this->msgCount - (($pageNr - 1) * $this->mailsPerPage);
        $end = $this->msgCount - ($pageNr * $this->mailsPerPage) + 1;
        $this->inbox = array_reverse(imap_fetch_overview($this->connection, $start . ":" . $end, 0));
    }

}