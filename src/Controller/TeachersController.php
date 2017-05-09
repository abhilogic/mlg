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
                'district' => $district,
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
       $sql =" SELECT level_id,course_id,course_name,name from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
               . " INNER JOIN levels ON courses.level_id = levels.id "
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
//      $sql = " SELECT * from course_contents as content "
//              . "INNER JOIN course_details as detail ON content.course_detail_id = detail.course_id "
//              . " WHERE detail.course_id =".$subject;
      $sql = " SELECT * from course_details "
              . " WHERE course_id =".$subject;
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
      $template_detail= TableRegistry::get('ContentTemplate');
      if(isset($this->request->data) && !empty($this->request->data)) {
        if(!empty($this->request->data['content_type']) && $this->request->data['content_type'] == 'lesson' ) {
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
            if($template_detail->save($content)){
              $message = 'Value Inserted Successfully';
              $status = TRUE;
           }
          } 
        }else if(!empty ($this->request->data['content_type']) && $this->request->data['content_type'] == 'question') {
          if(isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
            $message[0] = "please select a grade.";
          }elseif ($this->request->data['course'] == '-1') {
            $message[1] = "please select a course.";
          }elseif (empty($this->request->data['standard'])) {
            $message[2] = "please select a standard.";
          }elseif (empty($this->request->data['standard_type'])) {
            $message[3] = "please select a standard type.";
          }elseif (empty($this->request->data['skills'])) {
            $message[4] = "please select skills.";
          }elseif (empty($this->request->data['sub_skill'])) {
            $message[5] = "please select sub skills.";
          }else if (empty($this->request->data['ques_diff'])) {
            $message[6] = 'Please select difficulity level of question.';
          }else if (empty($this->request->data['claim'])) {
            $message[7] = 'Please give claim.';
          }else if (empty($this->request->data['scope'])) {
            $message[8] = 'Please give scope.';
          }else if (empty($this->request->data['dok'])) {
            $message[9] = 'Please provide depth of knowledge.';
          }else if (empty($this->request->data['ques_passage'])) {
            $message[10] = 'Please give passage.';
          }else if (empty($this->request->data['ques_target'])) {
            $message[11] = 'Please give question target.';
          }else if (empty($this->request->data['task'])) {
            $message[12] = 'Please give task.';
          }else if (empty($this->request->data['ques_complexity'])) {
            $message[13] = 'Please give question complexity.';
          }else if (empty($this->request->data['temp_name'])) {
            $message[14] = 'Please give template name.';
          }else {
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
            $content->difficulity_level = isset($this->request->data['ques_diff']) ? $this->request->data['ques_diff'] : '';
            $content->claim = isset($this->request->data['claim']) ? $this->request->data['claim'] : '';
            $content->scope = isset($this->request->data['scope']) ? $this->request->data['scope'] : '';
            $content->depth_of_knowledge = isset($this->request->data['dok']) ? $this->request->data['dok'] : '';
            $content->passage = isset($this->request->data['ques_passage']) ? $this->request->data['ques_passage'] : '';
            $content->secondary_target = isset($this->request->data['ques_target']) ? $this->request->data['ques_target'] : '';
            $content->task_noties = isset($this->request->data['task']) ? $this->request->data['task'] : '';
            $content->text_compexity = isset($this->request->data['ques_complexity']) ? $this->request->data['ques_complexity'] : '';
            $content->question = isset($this->request->data['ques_type']) ? $this->request->data['ques_type'] : '';
            $content->content_type = isset($this->request->data['cont_type']) ? $this->request->data['cont_type'] : '';
            if($template_detail->save($content)){
              $message[15] = 'Template Saved.';
              $status = TRUE;
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
  public function getTemplate($user_id) {
    try {
      $content = array();
      $content_detail = array();
      $template_detail= TableRegistry::get('ContentTemplate');
      $template = $template_detail->find('all')->where(['user_id' => $user_id]);
      foreach ($template as $key => $value) {
        $content_detail['id'] = $value['id'];
        $content_detail['template_name'] = $value['template_name'];
        $content_detail['user_id'] = $value['user_id'];
        $content_detail['grade'] = $value['grade'];
        $content_detail['standard'] = explode(',', $value['standard']);
        $content_detail['standard_type'] = explode(',', $value['standard_type']);
        $content_detail['course_id'] = $value['course_id'];
        $content_detail['skills'] = explode(',', $value['skill_ids']);
        $content_detail['sub_skill'] = explode(',', $value['sub_skill_ids']);
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
  public function getUserContent($uid = '' ) {
    try {
      $status = FALSE;
      $course_content = TableRegistry::get('course_contents');
      if($uid == '') {
        $content = 'Some Error Occured';
        throw new Exception('user id can not be empty.');
      }else {
        $content = $course_content->find('all')->where(['created_by'=>$uid]);
        if(count($content) > 0) {
          $status = TRUE;
        }
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

                          
               
              $postdata['parent_id']=isset($this->request->data['parent_id'])? $this->request->data['parent_id']:$data['message'][4]="The Parent ID has been expired. please Login Again";                          
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
                     $user_purchase_items=TableRegistry::get('UserPurchaseItems');
                     $subtotal=0;
                     $count=0;
                      // parent information by $pid
                        $parent_records= $users->find('all')->where(["id"=>$postdata['parent_id'] ]);
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

                      //2.  User Details Table
                      $new_user_details = $user_details->newEntity($postdata);
                      if ($user_details->save($new_user_details)) {

                          //3. User Roles Table
                          $new_user_roles = $user_roles->newEntity($postdata);                        
                        if ($user_roles->save($new_user_roles)) {

                          // Courses and Price calculation
                          $courses=$this->request->data['courses'];
                          foreach ($courses as $course_id => $name) {
                            $postdata['course_id']=$course_id;

                          //4. User Courses Table
                            $new_user_courses = $user_courses->newEntity($postdata);
                              if ($user_courses->save($new_user_courses)) {                                 
                                $data['status']="True";
                             }
                            else{
                              $data['status']='flase';
                              $data['message']=" Not able to save data in User Courses Table";
                              throw new Exception("Not able to save data in User Courses Table");
                          }
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

       // API to get the teacher added student for a course of perticular class
       public function getStudentsOfClass($tid=null,$course_id=null){


          $tid = isset($_GET['teacher_id'])? $_GET['teacher_id']:$tid;
           $course_id = isset($_GET['course_id'])? $_GET['course_id']:$course_id;

            if( (!empty($tid)) && (!empty($course_id)) ){  
                  $connection = ConnectionManager::get('default');
                   $sql =" SELECT users.id as id,first_name,last_name,username,email,password,open_key from users"
                        . " INNER JOIN user_details ON users.id = user_details.user_id "
                        . " INNER JOIN user_courses ON users.id = user_courses.user_id "                       
                        . " WHERE user_details.parent_id =".$tid. " AND user_courses.course_id=".$course_id
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
                          $data['students'][]=$student;
                      }
                      

                  }else{
                    $data['status']="true";
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
}

