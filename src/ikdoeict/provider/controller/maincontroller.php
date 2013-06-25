<?php

namespace Ikdoeict\Provider\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MainController implements ControllerProviderInterface {

        // '/dietician' controller, functionality and different paths
	public function connect(Application $app) {

		//@note $app['controllers_factory'] is a factory that returns a new instance of ControllerCollection when used.
		//@see http://silex.sensiolabs.org/doc/organizing_controllers.html
		$controllers = $app['controllers_factory'];

		// Bind sub-routes
                $controllers->get('/', array($this, 'loginGet'))->requireHttps();
                $controllers->post('/', array($this, 'loginPost'))->requireHttps();
                $controllers->get('/login', array($this, 'loginGet'))->requireHttps();
                $controllers->post('/login', array($this, 'loginPost'))->requireHttps();
                $controllers->get('/inbox', array($this, 'inbox'))->requireHttps();
                
		return $controllers;

	}

        //Login GET page
        public function loginGet(Application $app, Request $request) {

            return $app['twig']->render('login.twig');
        }

        //Login POST page
        public function loginPost(Application $app, Request $request) {

            return $app['twig']->render('login.twig');
        }
        
        //Inbox page
        public function inbox(Application $app, Request $request) {

            return $app['twig']->render('inbox.twig');
        }   

}