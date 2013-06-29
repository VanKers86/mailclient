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
                $controllers->post('/', array($this, 'loginPost'))->requireHttps();
                
		return $controllers;

	}

        //Login GET
        public function loginGet(Application $app) {
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
                
                //Connection succesfull          
                if ($this->checkInbox($app, $formData)) {
                    $mails = $app['mail.checker']->inbox;
                    var_dump($mails);
                    return $app['twig']->render('inbox.twig', array('mails' => $mails));
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
        
        public function checkInbox(Application $app, $formData) {
            $email = $formData['Email'];
            $passw = $formData['Password'];
            $server = $formData['Server'];
            $port = $formData['Port'];
            $ssl = $formData['SSL'];
            $valCert = $formData['ValidateCert'];
            $service = $formData['Service'];
            $check = $app['mail.checker']->check($email, $passw, $server, $port, $ssl, $valCert, $service);
            return $check;
        }
         

}