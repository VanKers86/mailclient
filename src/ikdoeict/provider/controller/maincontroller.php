<?php

namespace Ikdoeict\Provider\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request as Request;


class MainController implements ControllerProviderInterface {

        private $inbox;
    
        // default controller, functionality and different paths
	public function connect(Application $app) {

		//@note $app['controllers_factory'] is a factory that returns a new instance of ControllerCollection when used.
		//@see http://silex.sensiolabs.org/doc/organizing_controllers.html
		$controllers = $app['controllers_factory'];

		// Bind sub-routes
                $controllers->get('/', array($this, 'loginGet'))->requireHttps();
                $controllers->get('/login', array($this, 'loginGet'))->requireHttps();
                $controllers->post('/', array($this, 'loginPost'))->requireHttps();
                $controllers->post('/login', array($this, 'loginPost'))->requireHttps();
                $controllers->get('/inbox/{pageNr}', array($this, 'inbox'))->assert('pageNr', '\d+')->requireHttps();
                $controllers->get('/inbox/', array($this, 'inboxInitial'))->requireHttps();
                $controllers->get('/inbox/logout', array($this, 'logout'))->requireHttps();

		return $controllers;

	}

        //Login GET
        public function loginGet(Application $app) {
            if ($app['session']->get('logged')) {
                return $app->redirect('inbox');
            }                
            $form = $app['form.factory']->createBuilder('form')
                ->add('Email', 'email', array('label' => 'Email *'))
                ->add('Password', 'password', array('label' => 'Password *'))
                ->add('Server', 'text', array('label' => 'Server address (my.domain.com) *'))
                ->add('Port', 'integer', array('label' => 'TCP Port Number', 'required' => false))
                ->add('SSL', 'checkbox', array('label' => 'SSL (Secure Socket Layer)', 'required' => false, 'attr' => array('checked' => 'checked')))
                ->add('ValidateCert', 'checkbox', array('label' => 'Validate Certificates', 'required' => false, 'attr' => array('checked' => 'checked')))
                ->add('Service', 'choice', array(
                    'choices' => array('imap' => 'imap', 'pop3' => 'pop3', 'nntp' => 'nntp'),
                    'empty_value' => false
                ))
                ->getForm();

            return $app['twig']->render('login.twig', array('form' => $form->createView()));
        }

        //Login POST
        public function loginPost(Application $app, Request $request) {
            if ($app['session']->get('logged')) {
                return $app->redirect('inbox');
            }
            $form = $app['form.factory']->createBuilder('form')
                ->add('Email', 'email', array('label' => 'Email *'))
                ->add('Password', 'password', array('label' => 'Password *'))
                ->add('Server', 'text', array('label' => 'Server address (my.domain.com) *'))
                ->add('Port', 'integer', array('label' => 'TCP Port Number', 'required' => false))
                ->add('SSL', 'checkbox', array('label' => 'SSL (Secure Socket Layer)', 'required' => false, 'attr' => array('checked' => 'checked')))
                ->add('ValidateCert', 'checkbox', array('label' => 'Validate Certificates', 'required' => false, 'attr' => array('checked' => 'checked')))
                ->add('Service', 'choice', array(
                    'choices' => array('imap' => 'imap', 'pop3' => 'pop3', 'nntp' => 'nntp'),
                    'empty_value' => false
                ))
                ->getForm();
            
            $form->bind($request);
            
            if ($form->isValid()) {
                $formData = $form->getData();
                
                $check = $this->checkInbox($app, $formData);
                
                //Connection succesfull
                if ($check) {
                    $app['session']->set('logged', true);
                    return $app->redirect('inbox');
                }
                //Connection failed
                else {
                    $error = 'Could not make connection to this mail client, try using different settings';
                    return $app['twig']->render('login.twig', array('form' => $form->createView(), 'error' => $error));   
                } 

            }
            else {
                $error = 'Please fill in the required fields';
                return $app['twig']->render('login.twig', array('form' => $form->createView(), 'error' => $error)); 
            }
        }
        
        //Check inbox function
        //Uses the mail.checker service to establish a connection
        //Returns valid connection string or false
        public function checkInbox(Application $app, $formData) {
            $email = $formData['Email'];
            $passw = $formData['Password'];
            $server = $formData['Server'];
            $port = $formData['Port'];
            $ssl = $formData['SSL'];
            $valCert = $formData['ValidateCert'];
            $service = $formData['Service'];
            $check = $app['mail.checker']->check($app, $email, $passw, $server, $port, $ssl, $valCert, $service);
            return $check;
        }
        
        //Inbox page
        //Shows emails by page number
        public function inbox(Application $app, $pageNr) {
            if (!$app['session']->get('logged')) {
                return $app->redirect('../login');
            }            
            $mails = $app['mail.checker']->getMails($app, $pageNr);
            if (!$mails) {
                return $app->redirect('logout');
            }
            return $app['twig']->render('inbox.twig', array('mails' => $mails));
        }
        
        //Initial inbox page
        //Redirects to inbox with page 1
        public function inboxInitial(Application $app) {
            if (!$app['session']->get('logged')) {
                return $app->redirect('../login');
            }
            return $app->redirect('1');
        }
        
        //Logout functionality
        public function logout(Application $app) {
            $app['session']->remove('logged');
            $app['mail.checker']->logout($app);
            return $app->redirect('../login');
        }
         

}