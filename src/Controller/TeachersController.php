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
      $this->logs('Error in setTeacherRecord function in Teachers Controller'
              . $e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status','message']
 ]);
  }
  /***
    * This api is used for set teacher detail in database.
    * @return Boolean value.
    * 
    * **/ 
  public function getTeacherSubject() {
    try{
      $status = FALSE;
      $message = '';
      $sub_details = array();
      $total = 0;
      if ($this->request->is('post')) {
        if(empty($this->request->data['uid'])){
          $message = 'Please login.';
        }
        $user_id = $this->request->data['uid'];
      }
      if (empty($message)) {
        $connection = ConnectionManager::get('default');
        $sql =" SELECT * from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
                . " WHERE user_courses.user_id =".$user_id;
        $result = $connection->execute($sql)->fetchAll('assoc');
        $count = count($result);
        $grade = '';
        $i = 0;
        if($count >0) {
          $status = TRUE;
          foreach ($result as $detail) {
            $total = $total + $detail['price'];
            if(!empty($grade) && $grade == $detail['level_id']) {
              $sub_details[$i]['course_name'] = $sub_details[$i]['course_name'].','.$detail['course_name'];
              $sub_details[$i]['price'] = $sub_details[$i]['price']+$detail['price'];
            }else if(!empty($grade) && $grade != $detail['level_id']){
              $i++;
              $grade = $detail['level_id'];
              $sub_details[$i]['grade'] = $detail['level_id'];
              $sub_details[$i]['course_name'] = $detail['course_name'];
              $sub_details[$i]['price'] = $detail['price'];
            } else if(empty($grade)) {
              $grade = $detail['level_id'];
              $sub_details[$i]['grade'] = $detail['level_id'];
              $sub_details[$i]['course_name'] = $detail['course_name'];
              $sub_details[$i]['price'] = $detail['price'];
            }
          } 
        } else {
          $message = 'You are not teach any subject. Please select subject.';
        } 
      }
      
    }  catch (Exception $e) {
      $this->log('Error in getTeacherSubject function in Teachers Controller.s'
              .$e->getMessage().'(' . __METHOD__ . ')');

    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'data' => $sub_details,
      'total'=> $total,
      '_serialize' => ['status','message','data','total'] 
    ]);
  }
 /**
  * This api is used for get student detail. 
  **/ 
  public function getStudentDetail($grade='',$subject='') {
    try{
      $user = array();
      $message = '';
      if (empty($grade)) {
        $message = 'grade is empty';
        throw new Exception('grade is empty');
      }elseif (empty($subject)) {
        $message = 'subject id is empty.';
        throw new Exception('subject id is empty.');
      }  elseif(empty ($message)){
        $connection = ConnectionManager::get('default');
        $sql = "SELECT * FROM users as user
          Inner Join user_details as userDetail on user.id = userDetail.user_id 
          Inner Join user_courses as userCourse on user.id = userCourse.user_id
          Inner Join courses as course on course.id = userCourse.course_id
          where course.level_id = ".$grade. " AND course.id = ".$subject;
        $result = $connection->execute($sql)->fetchAll('assoc');
        foreach($result as $data) {
          $user_detail['name'] = $data['first_name'].' '.$data['last_name'];
          $user_detail['profile_pic'] = $data['profile_pic'];
          $user_detail['user_id'] = $data['user_id'];
          $user_detail['courses_id'] = $data['course_id'];
          $user[] = $user_detail;
        }    
      }  
    }  catch (Exception $e){
      $this->log('Error in getStudentDetail function in Teachers Controller'
              .$e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'data' => $user,
      '_serialize' => ['data'] 
    ]);
  }
 /**
  * This api is used for get teacher subject with grade.
  * 
  **/
  public function getTeacherGradeSubject($user_id='') {
    try{
      $connection = ConnectionManager::get('default');
      $status = FALSE;
      $message = '';
      $role_count = 0;
      $level_subject = 'Something goes wrong.';
      if (empty($user_id)) {
        $message = 'user id is not valid';
        throw new Exception('user id is not valid'); 
      }  else { 
        $sql =" SELECT * from user_roles WHERE user_id =".$user_id.
                " AND role_id = 3 ";
        $result = $connection->execute($sql)->fetchAll('assoc');
        $role_count = count($result);
      }  
      if ($role_count == '1' && empty($message)) {
        $level_subject = 'you have not choosen any subject or grade.';
        $connection = ConnectionManager::get('default');
        $sql =" SELECT level_id,course_id,course_name from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
                . " WHERE user_courses.user_id =".$user_id;
        $level_subject  = $connection->execute($sql)->fetchAll('assoc');
        $count = count($level_subject);
        if ($count > 0) {
          $status = TRUE;
        }  else {
          $level_subject = 'you have not choosen any subject or grade.';
        } 
      }  
    }catch (Exception $e) {
      $this->log('Error in getTeacherGradeSubject function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }  
     $this->set([
      'status' => $status,
      'response' => $level_subject,
      '_serialize' => ['status','response']
    ]);

  }
}

