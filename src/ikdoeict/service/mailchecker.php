<?php
namespace Ikdoeict\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailChecker implements ServiceProviderInterface {

    private $connection;
    public $msgCount;
    public $mailsPerPage = 20;
    public $nrPages;
    
    function boot(Application $app) {
    }

    // Define shared service
    function register(Application $app) {
        $app['mail.checker'] = $app->share(function() {
                return new MailChecker();
        });
    }

    //Check given email and password
    //Returns true when connection was succesfull (session variables set)
    //Returns false when connection could not be established
    public function check($app, $email, $passw, $server, $port, $ssl, $valCert, $service) {
        $connString = '{';
        $connString .= $server;
        $port != null ? $connString .= ':' . $port : null;
        $connString .= '/' . $service;
        $ssl ? $connString .= '/ssl' : null;
        $valCert ? null : $connString .= '/novalidate-cert';
        $connString .= '}INBOX';
                
        $this->connection = @imap_open($connString, $email, $passw);

        if (!imap_errors()) {
            $app['session']->set('connString', $connString);
            $app['session']->set('email', $email);
            $app['session']->set('passw', $passw);
            return true;
        }
        else {
            return false;
        }
    }
    
    //Get mails by a page number
    //Returns number of mails for a certain page, or false when no connection could be established
    public function getMails($app, $pageNr) {
        //Connection not set?
        //Use session variables to establish new one
        if ($this->connection == null) {
            $this->connection = @imap_open($app['session']->get('connString'), $app['session']->get('email'), $app['session']->get('passw'));
            //Still no connection? return false
            if (!$this->connection) {
                return false;
            }
        }
        $this->msgCount = @imap_num_msg($this->connection);
        $this->nrPages = ceil($this->msgCount / $this->mailsPerPage);
        $start = $this->msgCount - (($pageNr - 1) * $this->mailsPerPage);
        $end = $this->msgCount - ($pageNr * $this->mailsPerPage) + 1;
        return @array_reverse(@imap_fetch_overview($this->connection, $start . ":" . $end, 0));
    }
    
    //Destroy session variables when logged out
    public function logout($app) {
        $app['session']->remove('connString');
        $app['session']->remove('email');
        $app['session']->remove('passw');
    }

}