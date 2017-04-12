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
  public function getStudentDetail($grade='',$subject='',$type) {
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
          Inner Join user_roles as role on user.id = role.user_id
          where course.level_id = ".$grade. " AND course.id = ".$subject." AND role.role_id = ".$type;
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
  public function getTeacherGradeSubject($user_id='',$type) {
    try{
      $connection = ConnectionManager::get('default');
      $status = FALSE;
      $message = '';
      $role_count = 0;
      $level_subject = 'Something goes wrong.';
      $level = array();
      $urldata = array();
      $subject = array();
      if (empty($user_id)) {
        $message = 'user id is not valid';
        throw new Exception('user id is not valid'); 
      }  else { 
        $sql =" SELECT * from user_roles WHERE user_id =".$user_id.
                " AND role_id = ".$type;
        $result = $connection->execute($sql)->fetchAll('assoc');
        $role_count = count($result);
      }  
      if ($role_count == '1' && empty($message)) {
        $level_subject = 'you have not choosen any subject or grade.';
        $connection = ConnectionManager::get('default');
        $sql =" SELECT level_id,course_id,course_name from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
                . " WHERE user_courses.user_id =".$user_id." ORDER BY level_id ";
        $result = $connection->execute($sql)->fetchAll('assoc');
        $count = count($result);
        $level_subject = $result;
        $temp = '';
        $temp_subj = '';
        if ($count > 0) {
          $status = TRUE;
          foreach($result as $data) { 
            if (empty($temp)) {
              $temp = $data['level_id'];
              $urldata['level_id'] = $data['level_id'];
              $urldata['course_id'] = $data['course_id'];
              $urldata['course_name'] = $data['course_name'];
              $level['level_id'] = $data['level_id'];
            }else if($temp != $data['level_id']) {
              $temp = $data['level_id'];
              $level['level_id'] = $level['level_id'].','.$data['level_id'];
            }
            if (empty($temp_subj)) {
              $temp_subj = $data['course_id'];
              $subject['course_name'] = $data['course_name'];
              $subject['course_id'] = $data['course_id'];
            }else if($temp_subj!= $data['course_id']) {
              $temp_subj = $data['course_id'];
              $subject['course_name'] = $subject['course_name'].','.$data['course_name'];
              $subject['course_id'] = $subject['course_id'].','.$data['course_id'];
            }  
          }
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
      'grade' => $level,
      'subject' => $subject,
      'urlData' => $urldata, 
      '_serialize' => ['status','response','grade','subject','urlData']
    ]);
  }
  /**
   * this function is used for fetch teacher detail regarding to their grade.
   **/
  public function getTeacherDetailsForLesson($tid,$grade,$subject='',$type){
   try{
    $connection = ConnectionManager::get('default');
    if($subject == '-1') {
      $sql =" SELECT level_id,course_id,course_name from courses"
               . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
               . " WHERE user_courses.user_id =".$tid." AND courses.level_id =".$grade; 
    }  else {
      $sql = " SELECT * from course_contents as content "
              . "INNER JOIN course_details as detail ON content.course_detail_id = detail.course_id "
              . " WHERE detail.course_id =".$subject;
    }
    $result = $connection->execute($sql)->fetchAll('assoc');
   }catch(Exception $e){
     $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
   }
   $this->set([
      'response' => $result,
      '_serialize' => ['response']
    ]);
  }
  
  /**
   * this function is used for add lesson.
   **/
  
  public function setContentForLesson(){
   try{
     $result = "";
      if($this->request->is('post')) {
        print_r('hi');
      }
    $connection = ConnectionManager::get('default');
   }catch(Exception $e){
     $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
   }
   $this->set([
      'response' => $result,
      '_serialize' => ['response']
    ]);
  }
  
 /**
  * This function is used for read csv for add lesson.
  * 
  **/
  
  public function readCsv() {
    try{
      $status = FALSE;
      $headers = array('grade', 'subject','lesson','skills', 'sub_skills', 'standard_type', 'standard',
                 'text_title','text_description', 'video_title', 'video_url', 'image_title', 'image_url');
      $file =  fopen('../src/View/datalink/physics.csv', 'r');
      $first_row = TRUE;
      $course_detail= TableRegistry::get('CourseContents');
      while ($row = fgetcsv($file,'',':')) {
        if ($first_row) {
          $first_row = FALSE;
          continue;
        }
        $id = 0;
        $temp = array_combine($headers, $row);
        $skill = explode(',', $temp['skills']);
        if(count($skill)>0) {
          foreach ($skill as $value) {
            if($id != 0) {
              $detail->id = $id+1;
            } 
            $detail = $course_detail->newEntity();
            $detail->name = isset($temp['lesson']) ? $temp['lesson'] : '';
            $detail->text_title = isset($temp['text_title']) ? $temp['text_title'] : '';
            $detail->text_description = isset($temp['text_description']) ? $temp['text_description'] : '';
            $detail->video_title = isset($temp['video_title']) ? $temp['video_title'] : '';
            $detail->video_url = isset($temp['video_url']) ? $temp['video_url'] : '';
            $detail->image_title = isset($temp['image_title']) ? $temp['image_title'] : '';
            $detail->image_url = isset($temp['image_url']) ? $temp['image_url'] : '';
            $detail->course_detail_id = $value; 
            if($course_detail->save($detail)){
              $id = $detail->id;
            }
          }  
        }
        $sub_skill = explode(',', $temp['sub_skills']);
        if(count($sub_skill)>0) {
          foreach ($sub_skill as $value) {
            if($id != 0) {
              $detail->id = $id+1;
            } 
            $detail = $course_detail->newEntity();
            $detail->name = isset($temp['lesson']) ? $temp['lesson'] : '';
            $detail->text_title = isset($temp['text_title']) ? $temp['text_title'] : '';
            $detail->text_description = isset($temp['text_description']) ? $temp['text_description'] : '';
            $detail->video_title = isset($temp['video_title']) ? $temp['video_title'] : '';
            $detail->video_url = isset($temp['video_url']) ? $temp['video_url'] : '';
            $detail->image_title = isset($temp['image_title']) ? $temp['image_title'] : '';
            $detail->image_url = isset($temp['image_url']) ? $temp['image_url'] : '';
            $detail->course_detail_id = $value; 
            if($course_detail->save($detail)){
              $id = $detail->id;
            }
          }  
        }
      }
    }  catch (Exception $e) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    
  }
}

