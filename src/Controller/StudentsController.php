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

class StudentsController extends AppController {
  
  public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
       // $connection = ConnectionManager::get('default');
         $this->RequestHandler->renderAs($this, 'json');
  }



  

  public function getStudentCourses($uid){
    try{
        $connection = ConnectionManager::get('default');
       // $users=TableRegistry::get('Users')->find('all')->contain(['UserCourses','Courses']);
      $sql ="SELECT * FROM courses as cr
                INNER JOIN user_courses as uc ON cr.id = uc.course_id 
                INNER JOIN users as u ON u.id=uc.user_id
                WHERE uc.user_id =$uid";

      $users = $connection->execute($sql)->fetchAll('assoc');
       if (!empty($users)) {
          foreach($users as $user){
                $user_courses['id']=$user['course_id'];  
                $user_courses['course_name']=$user['course_name'];
                $data['student_courses'][]= $user_courses;           
                $data['student_class']=$user['level_id'];
           }
          //$data['student_courses']=$user_courses;
          $data['status']="TRUE";
       }
       else{
            $data['status']="FALSE";
            //throw new Exception("Subscribe Us to purchase the courses for your child");
       }       

    }
    catch (Exception $ex) {
      $this->logs('Error in setTeacherRecord function in Teachers Controller'
              . $e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $data,
        '_serialize' => ['response']
 ]);

  }

 

 
}

