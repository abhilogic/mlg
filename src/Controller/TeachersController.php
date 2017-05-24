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
use App\Controller\UsersController;
use App\Controller\PaymentController;

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
          $message = 'Login first.';
        }else if (empty($this->request->data['state'])) {
          $message = 'Choose State.';
        }else if (empty($this->request->data['district'])) {
          $message = 'Choose district';
        }else if (empty($this->request->data['country'])) {
          $message = 'Choose country.';
        }else if (empty($this->request->data['zip'])) {
          $message = 'Enter zipcode';
        }else if (empty($this->request->data['school_name'])) {
          $message = 'Enter School Name.';
        }else if (empty($this->request->data['school_address'])) {
          $message = 'Enter school address.';
        }
        if (empty($message)) {
          $id = $this->request->data['user_id'];
          $school = $this->request->data['school_name'];
          $country = $this->request->data['country'];
          $state = $this->request->data['state'];
          $district = $this->request->data['district'];
          $school_address = $this->request->data['school_address'];
          $zipcode = $this->request->data['zip'];
          $query = $teacher_detail->query();
          $result = $query->update()->set([
                'school' => $school,
                'country' => $country,
                'state' => $state,
                'district' => $district,
                'district' => $school_address,
                'district' => $zipcode,
                'step_completed'=>1
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
       
    } catch (Exception $e) {
      $this->logs('Error in setTeacherRecord function in Teachers Controller'
              . $e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status','message']
 ]);
  }

  /*  * This api is used for set teacher subject/courses/grade in database.
    * @return Boolean value.
    * 
    * **/ 
  public function setTeacherSubjects() {
      if(isset($this->request->data['selectedcourse']) && isset($this->request->data['user_id']) ){
          $user_courses = TableRegistry::get('UserCourses');
          $user_details = TableRegistry::get('UserDetails');
          $selected_courses=$this->request->data['selectedcourse'];
          $data_usercourse['user_id']= $this->request->data['user_id'];
      
          foreach ($selected_courses as $key=>$value) {              
              $data_usercourse['course_id']= $key;
              $data_usercourse['expiry_date']=time()+60;                
              $new_usercourses = $user_courses->newEntity($data_usercourse);

              if ($user_courses->save($new_usercourses)) {
                  $data['status']='TRUE';
                  $data['message']='Sucess';
              }else{
                  $data['status']='FALSE';
                  $data['message']='Opps not able to add data on course_id'.$selectedcourse['id'];
                }
          }

          // update step_completed in user detail
            $query = $user_details->query();
            $result=  $query->update()
               ->set(['step_completed'=>2])
               ->where(['user_id' => $data_usercourse['user_id'] ])
               ->execute();
                  
      }else{
        $data['status']='FALSE';
         $data['message']='Please select at least one course';
      }

  
      $this->set([
        'response' => $data,      
        '_serialize' => ['response']
 ]);

  }





  /***
    * This api is used for get teacher detail in database.
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
                . " WHERE user_courses.user_id =".$user_id.' ORDER BY level_id';
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
          $message = 'You have not taught any subject yet.';
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
  * This api is used for get student detail of a class and subject. 
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
       $sql =" SELECT level_id,course_id,course_name,name from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
               . " INNER JOIN levels ON courses.level_id = levels.id "
                . " WHERE user_courses.user_id =".$user_id." ORDER BY level_id ";
        $result = $connection->execute($sql)->fetchAll('assoc');
//        print_r($result);
        $count = count($result);
        $level_subject = $result;
        $temp = '';
        $temp_subj = '';
        $level = array();
        if ($count > 0) {
          $status = TRUE;
          foreach($result as $data) { 
            if (empty($temp)) {
              $temp = $data['level_id'];
              $urldata['level_id'] = $data['level_id'];
              $urldata['course_id'] = $data['course_id'];
              $urldata['course_name'] = $data['course_name'];
              $leveltemp['id'] = $data['level_id'];
              $leveltemp['name'] = $data['name'];
              $level[] = $leveltemp;
            }else if($temp != $data['level_id']) {
              $temp = $data['level_id'];
              $leveltemp['id'] = $data['level_id'];
              $leveltemp['name'] = $data['name'];
              $level[] = $leveltemp;
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
      'url' =>Router::url('/', true),
      '_serialize' => ['status','response','grade','subject','urlData','url']
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
//      $sql = " SELECT * from course_contents as content "
//              . "INNER JOIN course_details as detail ON content.course_detail_id = detail.course_id "
//              . " WHERE detail.course_id =".$subject;
      $sql = " SELECT * from courses "
              . " WHERE id =".$subject;
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
     $data['message'] = array();
     $subskill = '';
     $temp_message = '';
     $content = array();
     $status = FALSE;
     $connection = ConnectionManager::get('default');
     $course_detail= TableRegistry::get('CourseContents');
      if($this->request->is('post')) {
        $id = 0;
        if(isset($this->request->data['tid']) && empty($this->request->data['tid'])) {
          $data['message'][7] = "Please login.";
        }elseif(isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
          $data['message'][0] = "please select a grade.";
        }elseif ($this->request->data['course'] == '-1') {
          $data['message'][1] = "please select a course.";
        }elseif (empty($this->request->data['standard'])) {
          $data['message'][2] = "please select a standard.";
        }elseif (empty($this->request->data['standard_type'])) {
          $data['message'][3] = "please select a standard type.";
        }elseif (empty($this->request->data['lesson'])) {
          $data['message'][4] = "please select a lesson name";
        }elseif (empty($this->request->data['skills'])) {
          $data['message'][5] = "please select skills.";
        }elseif (empty($this->request->data['sub_skill'])) {
          $data['message'][6] = "please select sub skills.";
        }
        $uid = $this->request->data['tid'];
        foreach ($data['message']as $ki=>$val) {
          $temp_message = $val;
        }
        if(empty($temp_message)) {
          if(!empty($this->request->data['content'])){
            if($this->request->data['type']== 'text'){
              $content = $this->request->data['content'];
            }else{
              $temp_data = explode(',',$this->request->data['content']);
              foreach($temp_data as $key=>$value) {
                $temp = explode(': "', $value);
                $temp_string = explode('"', $temp[1]);
                if($key == 0) {
                  $content = $temp_string[0];
                }else{
                  $content = $content.','.$temp_string[0];
                }  
              }  
            }         
          }
          if(!empty($this->request->data['sub_skill'])) {
            $subskill = $this->request->data['sub_skill'];
          }
          foreach($subskill as $key => $value) {
            $detail = $course_detail->newEntity();
            if($id != 0) {
              $detail->id= $id;
            }
            $detail->created_by = $uid;
            $detail->lesson_name = isset($this->request->data['lesson']) ? $this->request->data['lesson']: '';
            $detail->course_detail_id = $value;
            if(!isset($this->request->data['title']) && empty($this->request->data['title'])) {
                 $data['message'][8] = 'Please give title.';
                 $temp_message = 'Please give title.';
            }else{
              $title = $this->request->data['title'];
            }
            if(empty($this->request->data['content'])) {
                $data['message'][9] = 'Content can not be empty.';
                $temp_message = 'Content can not be empty.';
            }
            if(!empty($content) && empty($temp_message) ) {  
              $detail->title = $title;
              $detail->standards = implode(',',$this->request->data['standard']);
              $detail->standard_type = implode(',',$this->request->data['standard_type']);
              $detail->title = $title;
              $detail->type= $this->request->data['type'];
              $detail->content = $content;
              if($course_detail->save($detail)){
                $id = $detail->id;
                $data['message'][10] = 'Value Inserted Successfully';
                $status = TRUE;
              } else {
                 $data['message'][11] = 'Unable to insert the subSkill value .';
                 $temp_message = 'Unable to insert the subSkill value .';
              }
            }
            
          }
        }
               
      }
   }catch(Exception $e){
     $this->log('Error in setContentForLesson function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
   }
   $this->set([
      'response' => $data['message'],
       'status' => $status,
      '_serialize' => ['response','status']
    ]);
  }
  
 /**
  * This function is used for read csv for add lesson.
  * 
  **/
  
  public function readCsv() {
    try{
      $status = FALSE;
      $message = '';
      $headers = array('grade', 'subject','lesson','skills', 'sub_skills', 'standard_type', 'standard',
                 'text_title','text_description', 'video_title', 'video_url', 'image_title', 'image_url');
      if (!isset($this->request->data['csv']) || (@end(explode('/', $csv['type'])) == 'csv')) {
           $message = 'please upload CSV';
           throw new Exception('please upload CSV');
      }
      $csv = $this->request->data['csv'];
      $file =  fopen($csv['tmp_name'], 'r');
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
  public function uploadfile() {
    $file_name = time() . '_' . $_FILES['uploadfile']['name'];
    move_uploaded_file($_FILES['uploadfile']['tmp_name'], WWW_ROOT .'/upload/'.$file_name );
    $this->set([
      'response' => $file_name ,
      '_serialize' => ['response']
    ]);
  }
  
  public function saveTemplate() {
    try{
      $connection = ConnectionManager::get('default');
      $status = FALSE;
      $message = '';
      $template_detail= TableRegistry::get('ContentTemplate');
      if(isset($this->request->data) && !empty($this->request->data)) {
        if(!empty($this->request->data['cont_type']) && $this->request->data['cont_type'] == 'lesson' ) {
          if(isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
            $message = "please select a grade.";
          }elseif ($this->request->data['course'] == '-1') {
            $message = "please select a course.";
          }elseif (empty($this->request->data['standard'])) {
            $message = "please select a standard.";
          }elseif (empty($this->request->data['standard_type'])) {
            $message = "please select a standard type.";
          }elseif (empty($this->request->data['lesson'])) {
            $message = "please select a lesson name";
          }elseif (empty($this->request->data['skills'])) {
            $message = "please select skills.";
          }elseif (empty($this->request->data['sub_skill'])) {
            $message = "please select sub skills.";
          }else if (empty($this->request->data['temp_name'])) {
            $message = 'Please give template name.';
          }  else {
            $standard = implode(',', $this->request->data['standard']);
            $standard_type = implode(',', $this->request->data['standard_type']);
            $content = $template_detail->newEntity();
            $content->template_name = isset($this->request->data['temp_name']) ? $this->request->data['temp_name'] : '';
            $content->user_id = isset($this->request->data['tid']) ? $this->request->data['tid'] : '';
            $content->grade = isset($this->request->data['grade']) ? $this->request->data['grade'] : '';
            $content->standard = $standard;
            $content->standard_type = $standard_type;
            $content->course_id = isset($this->request->data['course']) ? $this->request->data['course'] : '';
            $content->skill_ids = implode(',',  $this->request->data['skills']);
            $content->sub_skill_ids = implode(',',  $this->request->data['sub_skill']);
            $content->content_type = isset($this->request->data['cont_type']) ? $this->request->data['cont_type'] : '';
            if($template_detail->save($content)){
              $message = 'Value Inserted Successfully';
              $status = TRUE;
           }
          } 
        }else if(!empty ($this->request->data['cont_type']) && $this->request->data['cont_type'] == 'question') {
          if(isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
            $message = "please select a grade.";
          }elseif ($this->request->data['course'] == '-1') {
            $message = "please select a course.";
          }elseif (empty($this->request->data['standard'])) {
            $message = "please select a standard.";
          }elseif (empty($this->request->data['skills'])) {
            $message = "please select skills.";
          }elseif (empty($this->request->data['sub_skill'])) {
            $message = "please select sub skills.";
          }else if (empty($this->request->data['ques_diff'])) {
            $message = 'Please select difficulity level of question.';
          }else if (empty($this->request->data['claim'])) {
            $message = 'Please give claim.';
          }else if (empty($this->request->data['scope'])) {
            $message = 'Please give scope.';
          }else if (empty($this->request->data['dok'])) {
            $message = 'Please provide depth of knowledge.';
          }else if (empty($this->request->data['ques_passage'])) {
            $message = 'Please give passage.';
          }else if (empty($this->request->data['ques_target'])) {
            $message = 'Please give question target.';
          }else if (empty($this->request->data['task'])) {
            $message = 'Please give task.';
          }else if (empty($this->request->data['ques_complexity'])) {
            $message = 'Please give question complexity.';
          }else if (empty($this->request->data['temp_name'])) {
            $message = 'Please give template name.';
          }else {
            $standard = implode(',', $this->request->data['standard']);
            //$standard_type = implode(',', $this->request->data['standard_type']);
            $question = implode(',',$this->request->data['ques_type']);
            $content = $template_detail->newEntity();
            $content->template_name = isset($this->request->data['temp_name']) ? $this->request->data['temp_name'] : '';
            $content->user_id = isset($this->request->data['tid']) ? $this->request->data['tid'] : '';
            $content->grade = isset($this->request->data['grade']) ? $this->request->data['grade'] : '';
            $content->standard = $standard;
            //$content->standard_type = $standard_type;
            $content->course_id = isset($this->request->data['course']) ? $this->request->data['course'] : '';
            $content->skill_ids = implode(',',  $this->request->data['skills']);
            $content->sub_skill_ids = implode(',',  $this->request->data['sub_skill']);
            $content->difficulity_level = isset($this->request->data['ques_diff']) ? $this->request->data['ques_diff'] : '';
            $content->claim = isset($this->request->data['claim']) ? $this->request->data['claim'] : '';
            $content->scope = isset($this->request->data['scope']) ? $this->request->data['scope'] : '';
            $content->depth_of_knowledge = isset($this->request->data['dok']) ? $this->request->data['dok'] : '';
            $content->passage = isset($this->request->data['ques_passage']) ? $this->request->data['ques_passage'] : '';
            $content->secondary_target = isset($this->request->data['ques_target']) ? $this->request->data['ques_target'] : '';
            $content->task_noties = isset($this->request->data['task']) ? $this->request->data['task'] : '';
            $content->text_compexity = isset($this->request->data['ques_complexity']) ? $this->request->data['ques_complexity'] : '';
            $content->question = $question;
            $content->assignment = isset($this->request->data['assignment']) ? $this->request->data['assignment'] : '';
            $content->content_type = isset($this->request->data['cont_type']) ? $this->request->data['cont_type'] : '';
            if($template_detail->save($content)){
              $message = 'Template Saved.';
              $status = TRUE;
            }else{
             $message='Template Not Saved.';
            }
          }
        }       
      }
    } catch (Exception $e) {
      
    }  
    $this->set([
      'message' => $message,
      'status' => $status ,
      '_serialize' => ['status','message']
    ]);
  }
  public function getTemplate($user_id,$type) {
    try {
      $content = array();
      $content_detail = array();
      $template_detail= TableRegistry::get('ContentTemplate');
      $template = $template_detail->find('all')->where(['user_id' => $user_id ,'content_type'=>$type]);
      foreach ($template as $key => $value) {
        $content_detail['id'] = $value['id'];
        $content_detail['template_name'] = $value['template_name'];
        $content_detail['user_id'] = $value['user_id'];
        $content_detail['grade'] = $value['grade'];
        $content_detail['standard'] = explode(',', $value['standard']);
        if($type == 'lesson'){
			
	      }
        $content_detail['standard_type'] = explode(',', $value['standard_type']);
        $content_detail['course_id'] = $value['course_id'];
        $content_detail['skills'] = explode(',', $value['skill_ids']);
        $content_detail['sub_skill'] = explode(',', $value['sub_skill_ids']);
        if($type == 'question'){
          $content_detail['ques_diff'] = $value['difficulity_level'];
          $content_detail['claim'] = $value['claim'];
          $content_detail['scope'] = $value['scope'];
          $content_detail['dok'] = $value['depth_of_knowledge'];
          $content_detail['ques_passage'] = $value['passage'];
          $content_detail['ques_target'] = $value['secondary_target'];
          $content_detail['task'] = $value['task_noties'];
          $content_detail['ques_complexity'] = $value['text_compexity'];
          $content_detail['ques_type'] = explode(',',$value['question']);
          $content_detail['assignment'] = $value['assignment'];
		}
        $content[] = $content_detail;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'data' => $content,
      'status' => true ,
      '_serialize' => ['data','status']
    ]);
  }
  public function deleteContent($id) {
    try {
      $message = '';
      $status = FALSE;
      $connection = ConnectionManager::get('default');
      $delete_sql = 'DELETE FROM course_contents WHERE id =' . $id;
      if (!$connection->execute($delete_sql)) {
        $message = "unable to delete template";
        throw new Exception($message);
      }  else {
        $message = "Template deleted successfully.";
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'message'=> $message,  
      'status' => $status,
      '_serialize' => ['message','status']
    ]);
  }


  public function getDifficulty() {
    try {
      $status = FALSE;
      $difficulty = TableRegistry::get('difficulties');
      $diff_detail = $difficulty->find('all');
      if(count($diff_detail) > 0) {
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'data'=> $diff_detail,  
      'status' => $status,
      '_serialize' => ['data','status']
    ]);
  }
  public function getQuestionType() {
    try {
      $status = FALSE;
      $ques_type = TableRegistry::get('item_types');
      $ques = $ques_type->find('all');
      if(count($ques) > 0) {
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'data'=> $ques,  
      'status' => $status,
      '_serialize' => ['data','status']
    ]);
  }
  public function getUserContent() {
    try {
//      print_r($this->request->data);
      $status = FALSE;
      $content = '';
      $course_content = TableRegistry::get('course_contents');
      if($this->request->is('post')) {
        if(isset($this->request->data['uid']) && empty($this->request->data['uid'])) {
          $content = 'please login';
          throw new Exception('please login');
        }elseif(isset($this->request->data['subskills']) && empty($this->request->data['subskills'])){
          $content = 'please select subskills.';
          throw new Exception('please select subskills');
        }
      }
      if($content == '') {
        $skills = $this->request->data['subskills'];
        $content = $course_content->find('all')->where(['created_by'=>$this->request->data['uid'] ,'course_detail_id IN'=> $skills]);
        $status = TRUE;
      }
    } catch (Exception $e) {
      $this->log('Error in getUserContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'data'=> $content,  
      'status' => $status,
      'url' =>Router::url('/', true),
      '_serialize' => ['data','status','url']
    ]);
  }
  public function setUserContent($subSkill_id = '') {
    try {
      $skill_id = '';
      $message = '';
      $connection = ConnectionManager::get('default');
      if($subSkill_id == '') {
        $message = 'unable to find subskill.';
        throw new Exception('unable to find subskill.');
      }else{
        $sub_skill_sql = "Select * from course_details where course_id IN (".$subSkill_id.")";
        $sub_skill = $connection->execute($sub_skill_sql)->fetchAll('assoc');
        $sql = "Select course_id,parent_id,name from course_details where course_id IN (Select parent_id from course_details where course_id IN (".$subSkill_id.") )";
        $skill = $connection->execute($sql)->fetchAll('assoc');
        foreach($skill as $key => $value){
         if($key == 0) {
           $skill_id = $value['parent_id'];
         }else {
           $skill_id = $skill_id.','.$value['parent_id'];
         }
        }
        if(!empty($skill_id)){
          $skill_sql = "Select * from courses where id IN (".$skill_id.")";
          $subject = $connection->execute($skill_sql)->fetchAll('assoc');
        }
      }
      
    } catch (Exception $e) {
      $this->log('Error in getUserContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'skill'=> $skill,  
      'subject'=> $subject,
      'sub_skill' => $sub_skill,
      'message' => $message,
      '_serialize' => ['skill','subject','sub_skill','message']
    ]);
  }
  public function updateUserContent() {
    try {
      $status = FALSE;
      $message = '';
      if($this->request->is('post')) {
        $course_content = TableRegistry::get('CourseContents');
        if(isset($this->request->data['id']) && empty($this->request->data['id'])){
          $message = 'Some Error Occurred in updation.';
          throw new Exception('Content id not found');
        }else if(isset($this->request->data['updated_content']) && empty($this->request->data['updated_content'])) {
          $message = 'Please add some content.';
          throw new Exception('Updated content is empty');
        }elseif(isset($this->request->data['title']) && empty($this->request->data['title'])) {
          $message = 'Please give title for content.';
          throw new Exception('Title can not be empty.');   
        }elseif(isset($this->request->data['type']) && empty($this->request->data['type'])) {
          $message = 'Please add some content.';
          throw new Exception('Type cannot be empty.');   
        }
        if($message == '') {
          $type = $this->request->data['type'];
          if(!empty($this->request->data['updated_content'])){
              if($this->request->data['type']== 'text'){
                $content = $this->request->data['updated_content'];
              }else{
                $temp_data = explode(',',$this->request->data['updated_content']);
                foreach($temp_data as $key=>$value) {
                  $temp = explode(': "', $value);
                  $temp_string = explode('"', $temp[1]);
                  if($key == 0) {
                    $content = $temp_string[0];
                  }else{
                    $content = $content.','.$temp_string[0];
                  }  
                }
                $content = $this->request->data['pre_content'].','.$content; 
              }         
          }
          $query = $course_content->query();
          $result = $query->update()->set([
              'content'=> $content
          ])->where(['id' => $this->request->data['id'] ])->execute();
          $row_count = $result->rowCount();
          if($row_count == 1){
            $message = 'Lesson content updated successfully.';
            $status = TRUE;
          }  else {
            $message = 'Failed into update lesson content.';
            throw new Exception('Failed to update lesson content.');
          }
        }  
      }  
    } catch (Exception $e) {
      $this->log('Error in updateUserContent function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
       'status' => $status,
       'message' => $message,
      '_serialize' => ['status','message'] 
    ]);
  }
  

  // add student of a teacher
public function addStudent() {          
      try{         

          if($this->request->is('post')) { 
              //$postdata=$this->request->data;
             $users=TableRegistry::get('Users');
            $data['message'][]="";                              

              //username validation ******
              $postdata['username']=isset($this->request->data['username'])?$this->request->data['username']:"";
              if(!empty($postdata['username'])){
                  $username_exist = $users->find()->where(['Users.username' => $this->request->data['username'] ])->count();
                  if ($username_exist) {
                      $data['message'][0] = 'Username already exist';                      
                  }
              }else{
                $data['message'][0]="User Name is required to child login";
              }

              //email validation ********                           
              $postdata['email']=isset($this->request->data['email'])?$this->request->data['email']:"";
              if(!empty($postdata['email'])){
                  $email_exist = $users->find()->where(['Users.email' => $this->request->data['email'] ])->count();
                  if ($email_exist) {
                      $data['message'][1] = 'Email is already exist';                      
                  }
                  if (!filter_var($postdata['email'], FILTER_VALIDATE_EMAIL)) {
                     $data['message'][1] = 'Email is not valid';
               }

              }
              else{
                $data['message'][1] = 'Email cannot be empty';
              }
              

              //password
              $pass=isset($this->request->data['password'])?$this->request->data['password']:"";
              if(!empty($pass)){
                  // check emailchoice is yes/no 
                        //$pass= rand(1, 1000000); 
                        $default_hasher = new DefaultPasswordHasher();
                        $password=$default_hasher->hash($pass);
                        $postdata['password']  = $password;
                        $postdata['open_key'] = bin2hex($pass);  // encrypt a string

              }else{
                  $data['message'][9] = 'Password cannot be empty';
              }


              if(isset($this->request->data['first_name']) && !empty($this->request->data['first_name'])){ 
                $postdata['first_name']=$this->request->data['first_name'];
              }else{ $data['message'][2]="First name is require"; }

              if(isset($this->request->data['last_name']) && !empty($this->request->data['last_name']) ){ $postdata['last_name']=$this->request->data['last_name'];  }
              else{ $data['message'][3]="Last name is require"; }            

                               
              $postdata['teacher_id']=isset($this->request->data['teacher_id'])? $this->request->data['teacher_id']:$data['message'][4]="The your has been expired. please Login Again"; 


              $postdata['school']=isset($this->request->data['school'])? $this->request->data['school']:$data['message'][5]="School Name is require";        
             // $postdata['dob']=isset($this->request->data['dob'])? $this->request->data['dob']:'';

              $postdata['role_id']=$this->request->data['role_id'];
              $postdata['status']=$this->request->data['status'];
              //$postdata['created']=$this->request->data['created'];
              //$postdata['modfied']=$this->request->data['created'];
              //$postdata['order_date']=$this->request->data['created'];

              $postdata['created']=time();
              $postdata['modfied']=time();
              $postdata['order_date']=time();

              $postdata['promocode_id']=isset($this->request->data['vcode'])?$this->request->data['vcode']:'0'; 

              /*$postdata['package_id']=isset($this->request->data['package_id'])?$this->request->data['package_id']:$data['message'][7]="Please select package for your child";
              $postdata['plan_id']=isset($this->request->data['plan_id'])?$this->request->data['plan_id']:$data['message'][8]="Please slelect Plans for your child";
              $postdata['level_id']=$this->request->data['level_id'];*/


              $data['message'] = array_filter($data['message']); // to check array is empty array_filter return(0, null)
              if(empty($data['message']) || $data['message']=="" ){
                    
                     $user_details=TableRegistry::get('UserDetails');
                     $user_roles=TableRegistry::get('UserRoles');
                     $user_courses=TableRegistry::get('UserCourses');
                     $student_teachers=TableRegistry::get('StudentTeachers');
                     $user_purchase_items=TableRegistry::get('UserPurchaseItems');
                     $subtotal=0;
                     $count=0;
                      
                      // parent information by $pid
                        $parent_records= $users->find('all')->where(["id"=>$postdata['teacher_id'] ]);
                          foreach ($parent_records as $parent_record) {
                              $parentinfo['email']=$parent_record['email'];
                              $parentinfo['first_name']=$parent_record['first_name'];
                              $parentinfo['last_name']=$parent_record['last_name'];
                          }                      

                        $from = 'logicdeveloper7@gmail.com';
                            $subject ="Your Child authenticatation";
                            $email_message="Hello ". $parent_record['first_name']. $parent_record['last_name'].
                                "
                                  Your Child Login Credential in My Learning Guru is 
                                  User Name :".$postdata['username'] ." 
                                  Password : ".$pass;
                          
                        $to=$postdata['email']; 

                      //1. User Table

                      //$postdata['subscription_end_date'] = time() + 60 * 60 * 24 * $postdata['subscription_days'];
                      $new_user = $users->newEntity($postdata);
                      if ($result=$users->save($new_user)) { 
                          /*if($this->sendEmail($to, $from, $subject,$email_message)){
                            $data['message']="mail send";
                          }else{
                            $data['message']="mail send";
                          } */
                      $postdata['user_id']  = $result->id;
                      $postdata['student_id']  = $result->id;

                      //2.  User Details Table
                      $new_user_details = $user_details->newEntity($postdata);
                      if ($user_details->save($new_user_details)) {

                          //3. User Roles Table
                          $new_user_roles = $user_roles->newEntity($postdata);                        
                        if ($user_roles->save($new_user_roles)) {

                          //4. Student-Teacher table
                          $new_student_teachers = $student_teachers->newEntity($postdata);                        
                        if ($student_teachers->save($new_student_teachers)) {

                               //5.  User Courses Table
                            $courses=$this->request->data['courses'];
                            foreach ($courses as $course_id => $name) {
                              $postdata['course_id']=$course_id;

                            
                              $new_user_courses = $user_courses->newEntity($postdata);
                                if ($user_courses->save($new_user_courses)) {                                 
                                  $data['status']="True";
                                  $data['message']=" Studen- teacher relationship is added.";
                               }
                              else{
                                $data['status']='flase';
                                $data['message']=" Not able to save data in User Courses Table";
                                throw new Exception("Not able to save data in User Courses Table");
                            }
                          }



                        }else{
                              $data['status'] = "False";
                              $data['message']=" Not able to save data in Student Teacher Table";
                              throw new Exception("Not able to save data in Student Teacher Table");
                        }


                       

                      }
                      else{ 
                        $data['status']='flase';
                        $data['message']=" Not able to save data in User Roles Table";
                        throw new Exception("Not able to save data in User Roles Table");
                      }
                    }
                    else{ 
                      $data['status']='flase';
                      $data['message']="Not able to save data in User Details Table";
                      throw new Exception("Not able to save data in User Details Table");
                   }                   
                //$data['status']='True';
                }else{
                  $data['status']='flase';
                  $data['message']="Not Able to add data in user table";
                  throw new Exception("Not Able to add data in user table");

            }
          }else{
              $data['status']="False";
             // $data['message']="All are validate ";
          }

        } else{ $data['status']='No data is send/post to save'; }

        }
        catch (Exception $ex) {
           $this->log($ex->getMessage());
        }

          $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]);         

       }   


       //API to update the student
       public function updateStudent($sid=null) {

          $data['message'][]=""; 
            if($this->request->is('post')) { 
                $users=TableRegistry::get('Users');
                $connection = ConnectionManager::get('default');

                if(isset($this->request->data['first_name']) && !empty($this->request->data['first_name'])){ 
                $postdata['first_name']=$this->request->data['first_name'];
              }else{ $data['message'][2]="First name is require"; }

              if(isset($this->request->data['last_name']) && !empty($this->request->data['last_name']) ){ $postdata['last_name']=$this->request->data['last_name'];  }
              else{ $data['message'][3]="Last name is require"; } 


                $postdata['id']=isset($this->request->data['id'])? $this->request->data['id']:$data['message'][0]="Student id is null. Please check";  
                $postdata['first_name']=isset($this->request->data['first_name'])? $this->request->data['first_name']:$data['message'][2]="First name is require";             
                $postdata['last_name']=isset($this->request->data['last_name'])?$this->request->data['last_name']:$data['message'][3]="Last Name is require"; 

                //email validation ********                           
                $postdata['email']=isset($this->request->data['email'])?$this->request->data['email']:"";
                if(!empty($postdata['email'])){
                    //$email_exist = $users->find()->where(['Users.email' => $this->request->data['email'] ])->count();

                    $email_check_str ="SELECT * FROM users WHERE id!=".$postdata['id']."  AND email='".$postdata['email']."'";
                    $email_check_result = $connection->execute($email_check_str)->fetchAll('assoc');
                    $email_exist = count($email_check_result);
                    if ($email_exist >0) {
                        $data['message'][1] = 'Email is already exist';                      
                    }
                    if (!filter_var($postdata['email'], FILTER_VALIDATE_EMAIL)) {
                       $data['message'][1] = 'Email is not valid';
                 }
              }
              else{
                  $data['message'][1] = 'Email cannot be empty';
                }
              

              //password
              $pass=isset($this->request->data['password'])?$this->request->data['password']:"";
              if(!empty($pass)){
                  // check emailchoice is yes/no 
                        //$pass= rand(1, 1000000); 
                        $default_hasher = new DefaultPasswordHasher();
                        $password=$default_hasher->hash($pass);
                        $postdata['password']  = $password;

                        $postdata['open_key'] = bin2hex($pass);  // encrypt a string
              }else{
                  $data['message'][9] = 'Password cannot be empty';
              } 
                      
              if(empty($data['message']) || $data['message']==[""] ){                   
                  $upsql ="UPDATE users,user_details    
                           SET 
                            users.first_name = '".$postdata['first_name']."', users.last_name = '".$postdata['last_name']."', 
                            users.email='". $postdata['email']."', users.password = '".$postdata['password']."',
                            user_details.open_key = '".$postdata['open_key']."'    
                            WHERE users.id=".$postdata['id']."  AND users.id=user_details.user_id";
                  
                  // $student_records = $connection->execute($upsql);
                    if ($connection->execute($upsql)) {
                        $data['status']="true";
                        $data['message']="record is updated successfully";
                    }else{
                        $data['status']="false";
                        $data['message']="record is not updated. Contact Administrator";
                    }
              }
            }else{
              $data['message']="opps issue in updating the student record. Please try again";
              $data['status']="false";
            }  

             $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]);  
       }


       // Function to delete the student
       public function deleteStudent(){
          $postdata['id']=isset($_GET['id'])? $_GET['id']:$data['message'][0]="Student id is null. Please check";
          if(isset($_GET['id'])) {
             $dtsql ="DELETE users.*, user_details.* FROM users,user_details,user_courses                             
                        WHERE users.id=".$postdata['id']."  AND users.id=user_details.user_id AND users.id=user_courses.user_id";
                   
                  // $student_records = $connection->execute($upsql);
                  $connection = ConnectionManager::get('default');
                    if ($connection->execute($dtsql)) {
                      $data['status']="true";
                      $data['message']="The Student records is sucessfully deleted.";
                    }else{
                      $data['status']="false";
                      $data['message']="Opps..The Student records is not deleted.Please Try again";
                    }
              }
          $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]); 
       }


       // API to create group of a teacher for a subject
       public function createGroupInSubjectByTeacher($tid=null,$course_id=null){        
          if(isset($this->request->data['selectedstudent'] ) && isset($this->request->data['groupname']) ) {
              $students = $this->request->data['selectedstudent'];
             
              $postdata['teacher_id'] = isset($_GET['teacher_id'])? $_GET['teacher_id'] : $tid;
              $postdata['course_id'] = isset($_GET['course_id'])? $_GET['course_id'] : $course_id;
              $postdata['title'] = $this->request->data['groupname']; 
              $postdata['created_by'] = time();
              $postdata['modified_by'] = time();



              if(isset($this->request->data['group_image'])){
                $gp_img = json_decode($this->request->data['group_image']);
                $postdata['group_icon'] = $gp_img->response;
              }

              if(!empty($postdata['teacher_id']) && $postdata['teacher_id']!=null){
                  $student_ids=array();                     
                
                  foreach ($students as $id => $value) {
                    $student_ids[] = array_push($student_ids, $id) ;                     
                  }

                    $postdata['student_id'] = implode(',',$student_ids);
                    $student_groups= TableRegistry::get('StudentGroups');
                    $new_rowEntry = $student_groups->newEntity($postdata);
                    if ($student_groups->save($new_rowEntry)) {
                          $data['status']="true";
                          $data['message']="Group- ' ". $postdata['title'] ." ' has been created.";                
                    }else{
                           $data['status']="False";
                          $data['message']="Opps Issue in adding the group.";
                      }                  
                }else{
                    $data['status']="False";
                    $data['message']="Please login First. No Teacher UID get.";
                }
          }else{
              $data['status']="false";
              $data['message']="Opps....Either students are not selected or Group Title is not entered .";
          }


          $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]); 
       }


       //Get groups of a teacher
       public function getGroupsOfSubjectForTeacher($tid=null, $course_id=null){
          $teacher_id = isset($_GET['teacher_id'])?$_GET['teacher_id']:$tid;
          $course_id = isset($_GET['course_id'])?$_GET['course_id']:$course_id;

          if(!empty($teacher_id) && !empty($course_id)){
             $student_groups= TableRegistry::get('StudentGroups')->find('all')->where(['teacher_id'=>$teacher_id, 'course_id'=>$course_id])->group('group_icon')->toArray();

              if(count($student_groups) > 0){
                  foreach ($student_groups as $stgroup) {
                      if(!empty($stgroup['group_icon']) || $stgroup['group_icon'] !=""){
                        $data['groups'][] = $stgroup;
                      }
                      else{
                        $stgroup['group_icon']="group_images/default_group.png";
                        $data['groups'][] = $stgroup;
                      }
                  }
                }
                $data['status']="true";
          }else{
              $data['status']="false";
              $data['message']="Either your login session has expired or course is not set.";
          }

          $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]);


      }

       // API to get the student of a subject Added by a teacher
       public function getStudentsOfSubjectForTeacher($tid=null,$course_id=null){

          $tid = isset($_GET['teacher_id'])? $_GET['teacher_id']:$tid;
           $course_id = isset($_GET['course_id'])? $_GET['course_id']:$course_id;

            if( (!empty($tid)) && (!empty($course_id)) ){  
                  $connection = ConnectionManager::get('default');
                   $sql =" SELECT users.id as id,first_name,last_name,username,email,password,open_key, profile_pic from users"
                        . " INNER JOIN user_details ON users.id = user_details.user_id "
                        . " INNER JOIN user_courses ON users.id = user_courses.user_id "
                        . " INNER JOIN student_teachers ON users.id = student_teachers.student_id "                       
                        . " WHERE student_teachers.teacher_id =".$tid. " AND user_courses.course_id=".$course_id
                        ." ORDER BY users.id ASC "; 
                      
                  $student_records = $connection->execute($sql)->fetchAll('assoc');
                  $studentcount = count($student_records);

                  if($studentcount >0 ){
                      foreach($student_records as $studentrow) { 
                          $student['id']=$studentrow['id'];
                          $student['first_name']=$studentrow['first_name'];
                          $student['last_name']=$studentrow['last_name'];
                          $student['username']=$studentrow['username'];
                          $student['email']=$studentrow['email'];
                          $student['password']=$studentrow['password'];
                          $open_key=$studentrow['open_key'];
                          $student['open_key'] = hex2bin($open_key);
                          
                          if( $studentrow['profile_pic']==NULL ){
                              $student['profile_pic'] = '/upload/profile_img/default_studentAvtar.jpg';
                          }else{
                            $student['profile_pic'] = $studentrow['profile_pic'];
                          }

                          $data['students'][]=$student;
                      }
                      $data['status']="true";
                  }else{
                    $data['status']="false";
                    $data['message']="Please Add Student in Your Class.";
                  }   
            }else{
                $data['status']="false";
                $data['message']="Either teacher_id or course_id is null. Please check it cannot be null";
            }
           
            $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]);

       }  


       // The function to show all students of a teacher for his/her all subjects
       public function getStudentOfTeacher($tid=null){

          $tid = isset($_GET['teacher_id'])? $_GET['teacher_id']:$tid;          
            if( (!empty($tid)) ){  
                  $connection = ConnectionManager::get('default');
                   $sql =" SELECT users.id as id,first_name,last_name,username,email,password,open_key,profile_pic from users"
                        . " INNER JOIN user_details ON users.id = user_details.user_id "
                        . " INNER JOIN student_teachers ON users.id = student_teachers.student_id " 
                        . " INNER JOIN user_courses ON users.id = user_courses.user_id "                       
                        . " WHERE student_teachers.teacher_id =".$tid
                        ." ORDER BY users.id ASC "; 
                  $student_records = $connection->execute($sql)->fetchAll('assoc');
                  $studentcount = count($student_records);

                  if($studentcount >0 ){
                      foreach($student_records as $studentrow) { 
                          $student['id']=$studentrow['id'];
                          $student['first_name']=$studentrow['first_name'];
                          $student['last_name']=$studentrow['last_name'];
                          $student['username']=$studentrow['username'];
                          $student['email']=$studentrow['email'];
                          $student['password']=$studentrow['password'];
                          $open_key=$studentrow['open_key'];
                          $student['open_key'] = hex2bin($open_key);
                          if( $studentrow['profile_pic']==NULL ){
                              $student['profile_pic'] = '/upload/profile_img/default_studentAvtar.jpg';
                          }else{
                            $student['profile_pic'] = $studentrow['profile_pic'];
                          }
                          $data['students'][]=$student;
                      }    
                      $data['status']="true";                  

                  }else{
                    $data['status']="false";
                    $data['message']="No student is added yet by teacher.";
                  }                

            }else{
                $data['status']="false";
                $data['message']="teacher_id cannot be null. Please check it";
            }

           
            $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]);



       } 


       public function sendEmailToTeacher($tid=null){
           if($this->request->is('post')) {
              $teacher_id =isset($_GET['teacher_id'])?$_GET['teacher_id']:$tid;
              if($teacher_id!=null && !empty($teacher_id)){
                  $sids=implode(',',  array_keys($this->request->data['selectedstudent'])) ;   
                  $connection = ConnectionManager::get('default');
                  $str="SELECT users.id,first_name,last_name,email,username,open_key FROM users, user_details WHERE users.id IN ($sids,$teacher_id) AND users.id=user_details.user_id";

                  $users_record = $connection->execute($str)->fetchAll('assoc');
                  $usercount = count($users_record);                 
                  $index=0;

                  if($usercount >1 ){
                    $msg = "<table>";
                    $msg .= "<thead>
                          <tr>
                              <th class='sr-no'>Serial Num #</th>
                              <th class='first-name'>First Name</th>
                              <th class='last-name'>Last Name</th>
                              <th class='parent-student-email'>Parent or Student E-mail</th>
                              <th class='user-name'>User Name</th>
                              <th class='pasword'>Pasword</th>
                              <th class='actions'>Actions</th>
                          </tr>
                        </thead><tbody><tr>";

                      foreach($users_record as $userrow) { 
                          if($userrow['id'] == $teacher_id){
                              $teacher_firstname = $userrow['first_name'];
                              $teacher_lastname = $userrow['last_name'];
                              $teacher_email = $userrow['email'];
                          }else{
                              $msg .="<td>".$index++."</td>";
                              $msg .= "<td>".$userrow['first_name']."</td>";
                              $msg .= "<td>". $userrow['last_name'] . "</td>";
                              $msg .= "<td>". $userrow['email']. "</td>";
                              $msg .= "<td>". $userrow['username'] . "</td>";
                              $msg .= "<td>".$userrow['open_key']. "</td>";
                            }

                        }
                    $msg .= "</tr>";
                    $msg .= "</tbody></table>";


                    $to = $teacher_email;
                    $from = "info@mylearninguru.com";
                    $subject = "Selected Student Recotds";
                    $email_message = "Hello  $teacher_firstname  $teacher_lastname".$msg;

                    //sendEmail($to, $from, $subject,$email_message); // send email to teacher 
                    if($this->sendEmail($to, $from, $subject,$email_message)){
                            $data['message']="mail send";
                          }else{
                            $data['message']="mail is not send";
                          } 
                  }else{
                    $data['message']="Please select student first.";
                   $data['status']="false";
                  }
              }
              else{
                   $data['message']="Opps teaccher_id cannot be null. Please try again.";
                   $data['status']="false";
              }
           }else{
             $data['message']="Opps data is not recieved properly. Please try again.";
             $data['status']="false";
           }

       } 



       protected function sendEmail($to, $from, $subject = null, $email_message = null) {
          try {
            $status = FALSE;
            //send mail
            $email = new Email();
            $email->to($to)->from($from);
            $email->subject($subject);
            if ($email->send($email_message)) {
              $status = TRUE;
            }
          } catch (Exception $ex) {
            $this->log($ex->getMessage());
          }
          return $status;
       }
   /**
    * create an api for save question.
    * 
    **/
    public function saveQuestion() {
	  try{
	    $message = '';
	    $status = FALSE;
      $connection = ConnectionManager::get('default');
      if($this->request->is('post')) {
        if(isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
            $message = "please select a grade.";
        }elseif ($this->request->data['course'] == '-1') {
          $message = "please select a course.";
        }elseif (empty($this->request->data['standard'])) {
          $message = "please select a standard.";
        }elseif (empty($this->request->data['skills'])) {
          $message = "please select skills.";
        }elseif (empty($this->request->data['sub_skill'])) {
          $message = "please select sub skills.";
        }else if (empty($this->request->data['ques_diff'])) {
          $message = 'Please select difficulity level of question.';
        }else if (empty($this->request->data['claim'])) {
          $message = 'Please give claim.';
        }else if (empty($this->request->data['scope'])) {
          $message = 'Please give scope.';
        }else if (empty($this->request->data['dok'])) {
          $message = 'Please provide depth of knowledge.';
        }else if (empty($this->request->data['ques_passage'])) {
          $message = 'Please give passage.';
        }else if (empty($this->request->data['ques_target'])) {
          $message = 'Please give question target.';
        }else if (empty($this->request->data['task'])) {
          $message = 'Please give task.';
        }else if (empty($this->request->data['ques_complexity'])) {
          $message = 'Please give question complexity.';
        }else if (empty($this->request->data['question'])) {
          $message = 'Please give question';
        }else if (empty($this->request->data['answer'])) {
          $message = 'Please give options';
        }else if (empty($this->request->data['correctanswer'])) {
          $message = 'Please select an answer.';
        }
        if($message == '') {
           $subskill = $this->request->data['sub_skill'];
           foreach ($subskill as $key => $value) {
            $answer_list = explode(',', $this->request->data['answer']);
            $unique_id = date('Ymd',time()).uniqid(9);
            $question_master = TableRegistry::get('question_master');
            $question = $question_master->newEntity();
            $question->questionName = $this->request->data['question'];
            $question->grade_id = $this->request->data['grade'];
            $question->grade = $this->request->data['grade_name'];
            $question->subject = $this->request->data['course_name'];
            $question->course_id = $value;
            $question->level = $this->request->data['ques_diff_name'];
            $question->difficulty_level_id= $this->request->data['ques_diff'];
            $question->type = implode(',',$this->request->data['ques_type']);
            $question->standard = implode(',',$this->request->data['standard']);
            $question->uniqueId  = $unique_id;
            if($question_master->save($question)) {
              $header_master = TableRegistry::get('header_master');
              $header = $header_master->newEntity();
              $header->uniqueId = $unique_id;
              $header->Claim = $this->request->data['claim'];
              $header->DOK = $this->request->data['dok'];
              $header_master->save($header);
              foreach($answer_list as $key=>$value) {
                $option_master = TableRegistry::get('option_master');
                $option = $option_master->newEntity();
                $option->uniqueId  = $unique_id;
                if($this->request->data['type'] == 'image') {
                  $option->options  = 'upload/'.$value;
                }else{
                  $option->options  = $value;
                }
                if($option_master->save($option)) {
                  if($key+1 == $this->request->data['correctanswer']) {
                    $answer_master = TableRegistry::get('answer_master');
                    $answer = $answer_master->newEntity();
                    $answer->uniqueId  = $unique_id;
                    $answer->answers  = $value;
                    $answer_master->save($answer);           
                  }
                }else{
                  $mesaage = "Not able to saved option.";
                } 
              }
              $message = 'Question answer saved with options.';
              $status = TRUE;
            }else{
              $message = 'unable to save question.';
            }
           }
           
        }                 
        }else{
          $message = 'please define correct method.';
      }	    
	  }catch(Exception $e) {
	     $this->log('Error in saveQuestion function in Teachers Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
	  }
      $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' =>['message','status']
      ]);
	}

    /*
     * function saveCardToPaypal().
     */
    public function saveCardToPaypal() {
      $message = $response = '';
      $status = $payment_status = FALSE;
      $data = $name = array();
      $trial_period = TRUE;
      $payment_controller = new PaymentController();
      $access_token = $payment_controller->paypalAccessToken();
      if ($this->request->is('post')) {
        try {
          if (empty($this->request->data['user_id'])) {
            $message = 'Please login to submit payment';
            throw new Exception($message);
          }
          $validation = $payment_controller->validatePaymentCardDetail($this->request->data, $access_token);
          $response = $validation['response'];
          $data = $validation['data'];
          $message = $validation['message'];

          if (!empty($message)) {
            throw new Exception($message);
          }

          $card_token = $external_cutomer_id = '';
          if (isset($response['state']) && $response['state'] == 'ok') {
            $data['card_response'] = $message;
            $data['card_token'] = $response['id'];
            $data['external_cutomer_id'] = $response['external_customer_id'];
            $user_id = $this->request->data['user_id'];

            $users_table = TableRegistry::get('Users');
            $parent_info = $users_table->get($user_id)->toArray();
            $parent_subcription = (array) $parent_info['subscription_end_date'];
            $subcription_end_date = $parent_subcription['date'];
            if (time() > strtotime($subcription_end_date)) {
              $trial_period = FALSE;
            }

            $billing_plan = $this->createBillingPlan($user_id, $access_token, $this->request->data, $trial_period);
            if (!empty($billing_plan['plan_id'])) {
              $plan_id = $billing_plan['plan_id'];

              //total amount.
              $data['total_amount'] = $billing_plan['total_amount'];
              $plan_status = $payment_controller->activatePayaplPlan($plan_id, $access_token);
              if ($plan_status == 'ACTIVE') {
                $user_id = $this->request->data['user_id'];

                //same order date and time will be saved on user orders tabl.
                $order_date = $data['order_date'] = time();
                $order_timestamp = $data['order_timestamp'] = time();
                $data['trial_period'] = 1;

                $billing_response = $payment_controller->billingAgreementViaCreditCard($user_id, $data, $plan_id, $access_token);
                if (!$billing_response['error']) {
                  $payment_status = TRUE;
                  $status = TRUE;

                  // Updated user purchase items table.
                  $data['paypal_plan_id'] = $plan_id;
                  $data['paypal_plan_status'] = $plan_status;
                  $data['billing_id'] = $billing_response['result']['id'];
                  $data['billing_state'] = $billing_response['result']['state'];

                  //for teacher
                  $users_controller = new UsersController();
                  $users_controller->setUserOrders($user_id, 0, $data);

                  $message = 'card added successfully';
                } else {
                  $message = "Some Error occured, Kindly ask to administrator. ERRCODE: PY2Bill";
                  throw new Exception('Unable to process billing agreement');
                }
              } else {
                $message = "Some Error occured, Kindly ask to administrator. ERRCODE: PY2ACTPLN";
                throw new Exception('unable to activate plan');
              }
            } else {
              $message = "Some Error occured, Kindly ask to administrator. ERRCODE: PY2CTEPLN";
              throw new Exception('Unable to create Plan');
            }
          }

          if (!$payment_status) {
            $status = FALSE;
            $message = 'Payment not completed';
            throw new Exception('Payment not completed');
          } else {
            // start- update the user state
            $user_details = TableRegistry::get('UserDetails');
            $query = $user_details->query();
            $result = $query->update()
              ->set(['step_completed' => 4])
              ->where(['user_id' => $this->request->data['user_id']])
              ->execute();
            $affectedRows = $result->rowCount();
            if ($affectedRows > 0) {
              $data['status'] = "True";
            } else {
              $data['status'] = "False";
            }
          }
          //end- update the user
        } catch (Exception $ex) {
          $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
        }
      }
      $this->set([
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status', 'message',]
      ]);
    }

  /**
   * Create Paypal Billing Plan.
   */
  public function createBillingPlan($user_id = null, $access_token = null, $request_data, $trial_period = TRUE) {
    try {
      $user_controller = new UsersController();
      $frequency = 'MONTH';
      $cycles = 1;

      $fixed_name = 'Deduction For  Teacher';
      $fixed_description = 'You will be charged ' . $request_data['amount'] . ' for ' . 'Month';

      $fixed_payment_name = 'Regular payment defination';
      $fixed_payment_type = 'REGULAR';
      $fixed_payment_frequency = $frequency;
      $fixed_payment_frequency_interval = 1;

      $fixed_amount_value = $request_data['amount'];
      $fixed_amount_currency = PAYPAL_CURRENCY;

      $fixed_cycles = 1;
      $fixed_charged_shipping_amount_value = 0;
      $fixed_charged_shipping_amount_currency = PAYPAL_CURRENCY;
      $fixed_charged_tax_amount_value = 0;
      $fixed_charged_tax_amount_currency = PAYPAL_CURRENCY;

      $trial_name = 'Subcription on trial';
      $trial_frequency = 'MONTH';
      $trial_frequency_interval = 1;
      $trial_amount_value = 0;
      $trial_amount_currency = PAYPAL_CURRENCY;
      $trial_cycles = 1;
      $trial_charged_shipping_amount_value = 0;
      $trial_charged_shipping_amount_currency = PAYPAL_CURRENCY;
      $trial_charged_tax_amount_value = 0;
      $trial_charged_tax_amount_currency = PAYPAL_CURRENCY;

      $merchant_setup_fee_value = ($trial_period == TRUE) ? 0 : $fixed_amount_value;
      $merchant_setup_fee_currency = PAYPAL_CURRENCY;
      $return_url = 'http://www.paypal.com';
      $cancel_url = 'http://www.paypal.com/cancel';
      $auto_bill_amount = 'NO';
      $initial_fail_amount_action = 'CANCEL';
      $max_fail_attempts = 0;

      $post_fields = array(
        "name" => $fixed_name,
        "description" => $fixed_description,
        "type" => "FIXED",
        "payment_definitions" => array(
          array(
            "name" => $fixed_payment_name,
            "type" => $fixed_payment_type,
            "frequency" => $fixed_payment_frequency,
            "frequency_interval" => $fixed_payment_frequency_interval,
            "amount" => array(
              "value" => $fixed_amount_value,
              "currency" => $fixed_amount_currency
            ),
            "cycles" => $fixed_cycles,
            "charge_models" => array(
              array(
                "type" => "SHIPPING",
                "amount" => array(
                  "value" => $fixed_charged_shipping_amount_value,
                  "currency" => $fixed_charged_shipping_amount_currency
                )
              ),
              array(
                "type" => "TAX",
                "amount" => array(
                  "value" => $fixed_charged_tax_amount_value,
                  "currency" => $fixed_charged_tax_amount_currency
                )
              )
            )
          )
        ),
        "merchant_preferences" => array(
          "setup_fee" => array(
            "value" => $merchant_setup_fee_value,
            "currency" => $merchant_setup_fee_currency
          ),
          "return_url" => $return_url,
          "cancel_url" => $cancel_url,
          "auto_bill_amount" => $auto_bill_amount,
          "initial_fail_amount_action" => $initial_fail_amount_action,
          "max_fail_attempts" => $max_fail_attempts
        )
      );

      if ($trial_period == TRUE) {
        $trial_payment_definitions = array(
          "name" => $trial_name,
          "type" => "TRIAL",
          "frequency" => $trial_frequency,
          "frequency_interval" => $trial_frequency_interval,
          "amount" => array(
            "value" => $trial_amount_value,
            "currency" => $trial_amount_currency
          ),
          "cycles" => $trial_cycles,
          "charge_models" => array(
            array(
              "type" => "SHIPPING",
              "amount" => array(
                "value" => $trial_charged_shipping_amount_value,
                "currency" => $trial_charged_shipping_amount_currency
              )
            ),
            array(
              "type" => "TAX",
              "amount" => array(
                "value" => $trial_charged_tax_amount_value,
                "currency" => $trial_charged_tax_amount_currency
              )
            )
          )
        );
        $post_fields['payment_definitions'][] = $trial_payment_definitions;
      }
      $url = 'https://api.sandbox.paypal.com/v1/payments/billing-plans/';
      if (USE_SANDBOX_ACCOUNT == FALSE) {
        $url = 'https://api.paypal.com/v1/payments/billing-plans/';
      }
      if ($access_token == null) {
        $access_token = $this->paypalAccessToken();
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
      curl_setopt($ch, CURLOPT_POST, 1);

      $headers = array();
      $headers[] = "Content-Type: application/json";
      $headers[] = "Authorization: Bearer $access_token";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
      }
      curl_close($ch);
      $result = json_decode($result, TRUE);
      $error = FALSE;
      $plan_id = isset($result['id']) ? $result['id'] : '';
      if (isset($result['name']) && ($result['name'] == 'VALIDATION_ERROR')) {
        $error = TRUE;
        throw new Exception('Exception occured: ' . json_encode($result));
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return array('plan_id' => $plan_id,
      'total_amount' => $request_data['amount'],
      'error' => $error);
  }
}
