<?php

namespace Ikdoeict\Provider\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class InboxController implements ControllerProviderInterface {

        // '/dietician' controller, functionality and different paths
	public function connect(Application $app) {

		//@note $app['controllers_factory'] is a factory that returns a new instance of ControllerCollection when used.
		//@see http://silex.sensiolabs.org/doc/organizing_controllers.html
		$controllers = $app['controllers_factory'];

		// Bind sub-routes
                $controllers->match('/', array($this, 'inbox'));
                
		return $controllers;

	}

        //Login page and functionality
        public function inbox(Application $app, Request $request) {

            return $app['twig']->render('inbox.twig');
        }

        //Console page
        public function console(Application $app, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            $dietician = $app['session']->get('dietician');
            
            $newMeals = $app['dietician']->getNewMeals($dietician['id']);
            $newComm = $app['dietician']->getNewMessages($dietician['id']);
            
            return $app['twig']->render('dietician/console.twig', array('dietician' => $dietician, 'newMeals' => $newMeals, 'newComm' => $newComm));
        }
        
        //Customers page
        public function customers(Application $app, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            $dietician = $app['session']->get('dietician');
            $customers = $app['dietician']->findCustomers($dietician['id']);
            
            return $app['twig']->render('dietician/customers.twig', array('dietician' => $dietician, 'customers' => $customers));            
        }
        
        //Add new customer page and functionality
        public function addCustomer(Application $app, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            $dietician = $app['session']->get('dietician');
            
            $days = array();
            for ($i = 1; $i <= 31; $i++) {
                if ($i < 10) {
                    $days[(int) '0' . $i] = $i;
                }
                else {
                    $days[$i] = $i;
                }
            }
           
            $months = array(
                '01' => 'januari', 
                '02' => 'februari', 
                '03' => 'maart', 
                '04' => 'april', 
                '05' => 'mei', 
                '06' => 'juni', 
                '07' => 'juli', 
                '08' => 'augustus', 
                '09' => 'september', 
                '10' => 'oktober', 
                '11' => 'november', 
                '12' => 'december'
            );
            
            $years = array();
            for ($i = 1995; $i >= 1905; $i--) {
                $years[] = $i;
            }

            if ($request->isMethod('POST')) {
                $customerInfo['name'] = htmlentities($_POST['name']);
                $customerInfo['email'] = htmlentities($_POST['email']);
                $customerInfo['dietician_id'] = $dietician['id'];
                $customerInfo['phone'] = htmlentities($_POST['phone']);
                $customerInfo['gender'] = $_POST['gender'];
                $customerInfo['birthdate'] = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
                $customerInfo['password'] = $this->RandomPass();
                $customerInfo['date_added'] = date("Y-m-d H:i:s");
                $customerId = $app['dietician']->addCustomer($customerInfo);
                $customerData['customer_id'] = $customerId;
                $customerData['weight'] = $_POST['weight'];
                $customerData['height'] = $_POST['height'];
                $customerData['PAL'] = $_POST['pal'];
                $customerData['bmi'] = $_POST['bmi'];
                $customerData['kcal'] = $_POST['kcal'];
                $customerData['carbohydrates'] = $_POST['carbs'];
                $customerData['sugars'] = $_POST['sugars'];
                $customerData['proteins'] = $_POST['proteins'];
                $customerData['fats'] = $_POST['fats'];
                $customerData['cholesterol'] = $_POST['cholesterol'];
                $customerData['fibres'] = $_POST['fibres'];
                $customerData['sodium'] = $_POST['sodium'];
                $customerData['comments'] = htmlentities($_POST['comments']);
                $customerData['bmi'] = $_POST['bmi'];
                $customerData['date'] = date("Y-m-d H:i:s");
                $app['dietician']->addCustomerData($customerData);
                
                $message = \Swift_Message::newInstance()
                    ->setSubject('Dieetopvolging.be - Uw inloggegevens')
                    ->setFrom(array($dietician['email']))
                    ->setTo(array($_POST['email']))
                    ->setBody($app['twig']->render('dietician/mails/newCustomer.twig', array('customer' => $customerInfo, 'dietician' => $dietician), 'text/html'));

                $app['mailer']->send($message);
                
                return $app->redirect('/dietist/klanten/' . $customerId);
            }
            
            return $app['twig']->render('dietician/addCustomer.twig', array('dietician' => $dietician, 'days' => $days, 'months' => $months, 'years' => $years));
        }
        
        //Get customer detail page
        public function customerDetail(Application $app, $customerId, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            
            if ($request->isMethod('POST')) {
                if ($_POST['delete'] === "deleteOk") {
                    $app['dietician']->deleteCustomer($customerId);
                    return $app->redirect('/dietist/klanten');
                }
            }
            
            $dietician = $app['session']->get('dietician');
            $customer = $app['dietician']->getDietistCustomer($dietician['id'], $customerId);
            //Not a customer of this dietician
            if (!$customer) {
                return $app->redirect('/dietist/console');
            }

            $consultations = $app['dietician']->getConsultations($customerId);
            $communication = $app['dietician']->getUnseenCommunication($dietician['id'], $customerId);
            
            return $app['twig']->render('dietician/viewCustomer.twig', array('dietician' => $dietician, 
                                                                            'customer' => $customer,
                                                                            'consultations' => $consultations,
                                                                            'communication' => $communication
                    ));
	}
        
        //Logout
        public function logout(Application $app, Request $request) {
            $app['session']->remove('dietician');
            return $app->redirect('/');
        }
        
        
        //Add new consultation page and functionality
        public function customerConsultation(Application $app, $customerId, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            $dietician = $app['session']->get('dietician');
            $customer = $app['dietician']->getDietistCustomer($dietician['id'], $customerId);
            if (!$customer) {
                return $app->redirect('/dietist/console');
            }
            
            if ($request->isMethod('POST')) {
                $customerData['customer_id'] = $customerId;
                $customerData['weight'] = $_POST['weight'];
                $customerData['height'] = $_POST['height'];
                $customerData['PAL'] = $_POST['pal'];
                $customerData['bmi'] = $_POST['bmi'];
                $customerData['kcal'] = $_POST['kcal'];
                $customerData['carbohydrates'] = $_POST['carbs'];
                $customerData['sugars'] = $_POST['sugars'];
                $customerData['proteins'] = $_POST['proteins'];
                $customerData['fats'] = $_POST['fats'];
                $customerData['cholesterol'] = $_POST['cholesterol'];
                $customerData['fibres'] = $_POST['fibres'];
                $customerData['sodium'] = $_POST['sodium'];
                $customerData['comments'] = htmlentities($_POST['comments']);
                $customerData['bmi'] = $_POST['bmi'];
                $customerData['date'] = date("Y-m-d H:i:s");
                $app['dietician']->addCustomerData($customerData);
                return $app->redirect('/dietist/klanten/' . $customerId);
            }
            
            $lastConsultation = $app['dietician']->getLastConsultation($customerId);
            if ($lastConsultation['PAL'] === "Licht") {
                $pal = 1;
            }
            else if ($lastConsultation['PAL'] === "Middelmatig") {
                $pal = 2;
            }
            else {
                $pal = 3;
            }

            return $app['twig']->render('dietician/customerConsultation.twig', array('dietician' => $dietician, 'customer' => $customer, 'lastConsultation' => $lastConsultation, 'pal' => $pal));
	}
        
        //Foodtable page
        public function foodtable(Application $app, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            $dietician = $app['session']->get('dietician');
            
            $foodByCategories = $app['dietician']->getAllFoodByCategories();           
            
            return $app['twig']->render('dietician/foodtable.twig', array('dietician' => $dietician, 'foodByCategories' => $foodByCategories));            
        }
        
        //New foot table entry page and functionality
        public function newFoodtableEntry(Application $app, Request $request) {
            if (!$app['session']->get('dietician')) {
                return $app->redirect('/');
            }
            $dietician = $app['session']->get('dietician');
            
            $foodCategories = $app['dietician']->getAllFoodCategories();
            
            if ($request->isMethod('POST')) {
                $newFood['category_id'] = $_POST['category'];
                $newFood['name'] = $_POST['name'];
                $newFood['calories'] = $_POST['calories'];
                $newFood['proteins'] = $_POST['proteins'];
                $newFood['fats'] = $_POST['fats'];
                $newFood['carbohydrates'] = $_POST['carbohydrates'];
                $newFood['sugars'] = $_POST['sugars'];
                $newFood['cholesterol'] = $_POST['cholesterol'];
                $newFood['sodium'] = $_POST['natrium'];
                $newFood['fibres'] = $_POST['fibres'];
                
                $app['dietician']->addNewFood($newFood);
                
                return $app->redirect('/dietist/voedingstabel');
            }
            return $app['twig']->render('dietician/foodtableNew.twig', array('dietician' => $dietician, 'foodCategories' => $foodCategories));            
        }          
        
        //make random password of 6 capital letters
        public function RandomPass($length = null) {

            $length = $length < 6 ? 6 : $length;

            for ($a = 0; $a < $length; $a++) {
                @$pass .= chr(rand(65, 90));
            }

            return $pass;

        }        

}