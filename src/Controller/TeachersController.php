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
    $connection = ConnectionManager::get('default');
    $template_detail= TableRegistry::get('ContentTemplate');
    if(isset($this->request->data) && !empty($this->request->data)) {
      if (empty($this->request->data['temp_name'])) {
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
    }
    $this->set([
//       'response' => $message,
      'status' => true ,
      '_serialize' => ['status']
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
  public function updateContent() {
    
  }
}

