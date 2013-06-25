<?php

namespace Ikdoeict\Provider\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LoginController implements ControllerProviderInterface {

        // '/home' controller and path
	public function connect(Application $app) {

		//@note $app['controllers_factory'] is a factory that returns a new instance of ControllerCollection when used.
		//@see http://silex.sensiolabs.org/doc/organizing_controllers.html
		$controllers = $app['controllers_factory'];

		// Bind sub-routes
		$controllers->match('/', array($this, 'home'));
		$controllers->match('/login', array($this, 'home'));

		return $controllers;

	}

        // home page
        public function home(Application $app, Request $request) {
                return $app['twig']->render('login.twig');
        }


}