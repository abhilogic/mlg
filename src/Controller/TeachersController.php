<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Mailer\Email;
use Cake\Routing\Router;
use Cake\Datasource\ConnectionManager;

class TeachersController extends AppController {
  
  public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
         $this->RequestHandler->renderAs($this, 'json');
  }
  
  /***
    * This api is used for set teacher detail in database.
    * @return Boolean value.
    * @author Shweta Mishra <shweta.mishra@incaendo.com>
    * @link http://www.incaendo.com 
    * @copyright (c) 2017, Incaendo Technology Pvt Ltd.
    * 
    * **/ 
  public function setTeacherRecord() {
    try {
      $status = FALSE;
      $message = '';
      $teacher_detail= TableRegistry::get('UserDetails');
      if ($this->request->is('post')) {
        if (empty($this->request->data['user_id'])) {
          $message = 'Please login first.';
        }else if (empty($this->request->data['school_name'])) {
          $message = 'Please Enter School Name.';
        }else if (empty($this->request->data['country'])) {
          $message = 'Please choose country.';
        }else if (empty($this->request->data['state'])) {
          $message = 'Please choose State.';
        }elseif (empty($this->request->data['city'])) {
          $message = 'Please choose city';
        }else if (empty($this->request->data['district'])) {
          $message = 'Please choose district';
        }
        if (empty($message)) {
          $id = $this->request->data['user_id'];
          $school = $this->request->data['school_name'];
          $country = $this->request->data['country'];
          $state = $this->request->data['state'];
          $city = $this->request->data['city'];
          $district = $this->request->data['district'];
          $query = $teacher_detail->query();
          $result = $query->update()->set([
                'school' => $school,
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'district' => $district
             ])->where(['user_id' => $id ])->execute();
          $row_count = $result->rowCount();
          if ($row_count == '1') {
            $status = TRUE;
          }  else {
            throw new Exception('udable to update value in db');
          }
        }            
      } else {
        throw new Exception('Some error occured.');
      }
       
    } catch (Exception $ex) {
      
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status','message']
 ]);
  }
}

