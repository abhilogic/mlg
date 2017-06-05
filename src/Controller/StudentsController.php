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
                INNER JOIN levels as l ON l.id = cr.level_id 
                WHERE uc.user_id =$uid";
      $users = $connection->execute($sql)->fetchAll('assoc');
       if (!empty($users)) {
          foreach($users as $user){
                $user_courses['id']=$user['course_id'];  
                $user_courses['course_name']=$user['course_name'];
                $data['student_courses'][]= $user_courses;           
                $data['student_class']=$user['level_id'];
                $data['student_class_name']=$user['name'];
                $data['student_grade_id']=$user['level_id'];
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



public function getStudentAssignments($user_id = null){
      $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $user_id ;
      if(!empty($user_id)){
            $connection = ConnectionManager::get('default');
            $base_url = Router::url('/', true);


            // To find user assignment
            $sql ="SELECT
              adetails.id as assignment_id, grade_id, course_id, cr.course_name, group_id, student_id,assignment_for, schedule_time, adetails.comments,

              qz.id as quiz_id, qz.name as quiz_name, qz.quiz_type_id, max_marks, max_questions, qz.modified as created, qt.name as type, qt.id as type_id       
              
              from assignment_details as  adetails "                
                ." INNER JOIN quizes as qz ON qz.id = adetails.quiz_id " 
                ." INNER JOIN courses as cr ON  cr.id = adetails.course_id "
                ." INNER JOIN quiz_types as qt ON  qt.id = qz.quiz_type_id "          
                . " WHERE FIND_IN_SET(".$user_id." , student_id)" ;                  
          
            $results = $connection->execute($sql)->fetchAll('assoc');
            // print_r($result);
            $count = count($results);
            $data['counts'] = $count;
            if($count > 0){
                foreach ($results as $result) {
                 // $assg['items'] = $result;
                  $assg['assignment_id'] =$result['assignment_id'];                   
                  $assg['quiz_id'] = $result['quiz_id']; 
                  $assg['type']   = $result['type']; 
                  $assg['type_id']   = $result['type_id']; 
                  $assg['student_id'] = $result['student_id'];
                  $assg['quiz_name'] = $result['quiz_name']; 
                  $assg['max_marks'] = $result['max_marks']; 
                  $assg['max_questions'] = $result['max_questions']; 
                  $assg['created'] = $result['created']; 
                  $assg['grade_id'] = $result['grade_id'];  
                  $assg['course_id'] = $result['course_id'];
                  $assg['course_name'] = $result['course_name'];


                  // to get main course/subject of the class
                  $json_courseinfo = $this->curlPost($base_url.'courses/getCourseInfo/'.$result['course_id'], array() ) ;                  
                 $array_courseinfo = (array)json_decode($json_courseinfo);
                 
                   if(isset($array_courseinfo['response'])) {
                      if(isset($array_courseinfo['response']->parent_info_of_skill)){
                        $assg['subject_id'] = $array_courseinfo['response']->parent_info_of_skill->id;
                        $assg['subject_name'] = $array_courseinfo['response']->parent_info_of_skill->course_name;
                        $assg['class_name'] = $array_courseinfo['response']->parent_info_of_skill->grade_name;
                      }
                  }else{
                    $assg['subject_id'] = 'issue/not found';
                  }

                $data['assignment'][]= $assg;
           } 
                 $data['status'] =True;            
            }
            else{
                $data['status'] =False;
                $data['message'] ="No data found";
            }            
        }else{
          $data['status'] =False;
          $data['message'] ="user_id cannot null.";
      }
    $this->set([
            'response' => $data,
            '_serialize' => ['response']
     ]);


}

public function getAssignmentItems($assignment_id = null){
    $assignment_id = isset($_GET['assignment_id']) ? $_GET['assignment_id'] : $assignment_id ;

    if(!empty($assignment_id)){
        // To find items of quiz/assignment            
        $sql ="SELECT
            qitem.item_id as question_id, qz.id as quiz_id, qz.name as quiz_name, max_marks, max_questions,qitem.item_id as question_id, qz.modified as created, adetails.id as assignment_id, grade_id, course_id, cr.course_name, group_id, student_id,assignment_for, schedule_time , adetails.comments        
           from quizes as qz"                    
               . " INNER JOIN quiz_items as qitem ON qz.id = qitem.exam_id "
                ." INNER JOIN assignment_details as adetails ON qz.id = adetails.quiz_id " 
                ." INNER JOIN courses as cr ON  cr.id = adetails.course_id "          
                . " WHERE adetails.id=$assignment_id" ;

              $connection = ConnectionManager::get('default');
              $results = $connection->execute($sql)->fetchAll('assoc');
            // print_r($result);
            $count = count($results);
            $data['counts'] = $count;
            if($count > 0){
                foreach ($results as $result) {
                  $data['items'][] = $result; 
                }
                $data['status'] ="True";
            }else{
              $data['status'] ="False";
              $data['message'] ="No record found fir this assignments.";
            }

    }else{
      $data['status'] ="False";
      $data['message'] ="assignment_id id cannot null.";
    }

     $this->set([
            'response' => $data,
            '_serialize' => ['response']
     ]);      

}



// API to call curl 
  /*way of calling $curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]); */
  public function curlPost($url, $data) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      $response = curl_exec($ch);
      curl_close($ch);

      return $response;
  }
 

 
}

