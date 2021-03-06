<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Core\Exception\Exception;
use Cake\Routing\Router;
use Cake\Datasource\ConnectionManager;
use App\Controller\TeachersController;

/**
 * Courses Controller
 */
class CoursesController extends AppController{

    public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
         $this->RequestHandler->renderAs($this, 'json');
    }


    /** Index method    */
    public function index() {

        $courses = $this->Courses->find('all')->toArray();
        foreach($courses as $course){
            $data['courses'][]= $course;
        }

        $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));
    }


    /**
        * S1 – isCourseExists
        * Request –  String <CourseCode / Null> , String <courseName / Null>
        * Desc – Service to check for course exists or not.
     */
    public function isCourseExists() {
      $response = FALSE;
      $data['message'] = '';
      try {
        if ($this->request->is(['post'])) {
          $course_code = isset($this->request->data['course_code']) ? $this->request->data['course_code'] : '';
          $course_name = isset($this->request->data['course_name']) ? $this->request->data['course_name'] : '';
          if (!empty($course_code) || !empty($course_name)) {
            $isCourseExists = $this->Courses->find()->Where(['OR' => ['course_code' => $course_code, 'course_name' => $course_name]])->count();
            if (!empty($isCourseExists)) {
              $response = TRUE;
            } else {
              $data['message'] = 'Course code or course name does not exist';
            }
          } else {
            $data['message'] = 'Either course code or course name is required';
          }
        }
      } catch (Exception $e) {
        $this->log($e->getMessage() . '(' . __METHOD__ . ')', 'error');
      }

      $this->set(array(
        'response' => $response,
        'data' => $data,
        '_serialize' => ['response', 'data']
      ));
    }


  /**
        *S2 – getCourses
        * Request -  String <CourseCode / Null>
    */ 
    public function getCourses($coursecode=null){
         if($coursecode!=null){
                $courses = $this->Courses->find('all')->where(['course_code'=>$coursecode])->toArray();
                foreach($courses as $course){
                    $crs['course_id'] = $course->id;
                    $crs['course_code'] = $course->course_code;
                    $crs['course_name'] = $course->course_name;

                    $data['message']    = "Course Result for couse code $coursecode" ;
                    $data['courses'][]= $crs;
                }            

         }
         else{
                $courses = $this->Courses->find('all')->toArray();
                foreach($courses as $course){
                    $crs['course_id'] = $course->id;
                    $crs['course_code'] = $course->course_code;
                    $crs['course_name'] = $course->course_name;

                    $data['message']    = "All Course Result" ;
                    $data['courses'][]  = $crs;                    
                }
         }            

        $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));
    }

    /*
     * S5- Desc – Service to update course details or meta information.
     */
    public function setCourseDetails() {
      $response = FALSE;
      $data['message'] = '';
      try {
        if ($this->request->is(['post'])) {
          $course_id = isset($this->request->data['course_id']) ? $this->request->data['course_id'] : '';
          $course_content_id = isset($this->request->data['course_content_id']) ? $this->request->data['course_content_id'] : '';
          $name = isset($this->request->data['name']) ? $this->request->data['name'] : '';
          $meta_tags = isset($this->request->data['meta_tags']) ? $this->request->data['meta_tags'] : '';
          $descriptions = isset($this->request->data['descriptions']) ? $this->request->data['descriptions'] : '';
          $modified = date('Y-m-d H:i:s');
          $validity = isset($this->request->data['validity']) ? $this->request->data['validity'] : '';
          $status = isset($this->request->data['status']) ? $this->request->data['status'] : '';
          if (!empty($course_id)) {
            $course_details_table = TableRegistry::get('CourseDetails');
            $course_details = $course_details_table->find()->where(['course_id' => $course_id]);
            if ($course_details->count()) {
            foreach ($course_details as $course_detail) {
              $course_detail->course_content_id = !empty($course_content_id) ? $course_content_id : $course_detail->course_content_id;
              $course_detail->name = !empty($name) ? $name : $course_detail->name;
              $course_detail->meta_tags = !empty($meta_tags) ? $meta_tags : $course_detail->meta_tags;
              $course_detail->descriptions = !empty($descriptions) ? $descriptions : $course_detail->descriptions;
              $course_detail->validity = !empty($validity) ? $validity : $course_detail->validity;
              $course_detail->status = !empty($status) ? $status : $course_detail->status;
              $course_detail->modified = $modified;
            }
            if ($course_details_table->save($course_detail)) {
                $response = TRUE;
              }
            } else {
              $data['message'] = "No records found";
              throw new Exception($data['message']);
            }
          } else {
            $data['message'] = 'Course id could not be empty';
            throw new Exception($data['message']);
          }
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() . '(' . __METHOD__ . ')',  'error');
      }
      $this->set(array(
        'response' => $response,
        'data' => $data,
        '_serialize' => ['response', 'data']
      ));
    }


      /**
          * S6 - mapContentToCourse
          * Request -   Int <courseID> or <CourseCode> , Int <resourceID>          
         */
        public function mapContentToCourse() {
          try {
            $response = FALSE;
            if ($this->request->is('post')) {
              $data = $this->request->data;
              $course_id = isset($data['course_id']) ? $data['course_id'] : '';
              $course_code = isset($data['course_code']) ? $data['course_code'] : '';
              $resource_id = $data['resource_id'];
              $connection = ConnectionManager::get('default');
              $sql = "SELECT course_contents.url, course.course_code, course.course_id"
                . " FROM course_contents"
                . " INNER JOIN course_details ON course_contents.course_detail_id = course_details.course_content_id"
                . " INNER JOIN course ON course_details.parent_id = course.id WHERE course_contents.id ='$resource_id'"
                . " AND (course.course_code='$course_code' OR course.course_id='$course_id')";
            }
            $results = $connection->execute($sql)->fetchAll('assoc');
            foreach ($results as $result) {
              if (file_exists(WWW_ROOT . $result['url'])) {
                $response = TRUE;
              }
            }
          } catch (Exception $ex) {
            $this->log($ex->getMessage()); 
          }

          $this->set([
            'response' => $response,
            '_serialize' => ['response']
          ]);
        }

    /**
     * S26 - Desc – Service to create course with lguru.
     * Request –  String<courseName>, Array<meta info>
     */
    public function createCourse() {
      $response = FALSE;
      $data['message'] = '';
      try {
        if ($this->request->is(['post'])) {
          $level_id = isset($this->request->data['level_id']) ? $this->request->data['level_id'] : '';
          $course_code = isset($this->request->data['course_code']) ? $this->request->data['course_code'] : '';
          $course_name = isset($this->request->data['course_name']) ? $this->request->data['course_name'] : '';
          $logo = isset($this->request->data['course_logo']) ? $this->request->data['course_logo'] : '';
          $meta_tags = isset($this->request->data['meta_tags']) ? $this->request->data['meta_tags'] : '';
          $descriptions = isset($this->request->data['descriptions']) ? $this->request->data['descriptions'] : '';
          $author = isset($this->request->data['author']) ? $this->request->data['author'] : '';
          $created = $modified = time();
          $created_by = isset($this->request->data['created_by']) ? $this->request->data['created_by'] : '';
          $paid = isset($this->request->data['paid']) ? $this->request->data['paid'] : '';
          $price = isset($this->request->data['price']) ? $this->request->data['price'] : '';
          $parent_id = isset($this->request->data['parent_id']) ? $this->request->data['parent_id'] : 0;

          // Entries for Courses
          $courses = $this->Courses->newEntity();
          $courses->level_id = $level_id;
          $courses->course_code = $course_code;
          $courses->course_name = $course_name;
          $courses->logo = $logo;
          $courses->meta_tags = $meta_tags;
          $courses->descriptions = $descriptions;
          $courses->author = $author;
          $courses->created = $created;
          $courses->modified = $modified;
          $courses->created_by = $created_by;
          $courses->paid = $paid;
          $courses->price = $price;

          //Entries for course_details
          $course_details_table = TableRegistry::get('CourseDetails');
          $course_detail = $course_details_table->newEntity();
          $course_detail->parent_id = $parent_id;
          $course_detail->created = $created;
          $course_detail->modified = $modified;
          if ($this->Courses->save($courses)) {
            $course_detail->course_id = $courses->id;
            if ($course_details_table->save($course_detail)) {
              $response = TRUE;
              $this->request->data['course_id'] = $courses->id;
              $this->setCourseDetails();
            } else {
              $data['message'] = 'Data not saved to course detail';
            }
          } else {
            $data['message'] = 'Data not saved to course';
          }
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() . '(' . __METHOD__ . ')',  'error');
      }
      $this->set(array(
        'response' => $response,
        'data' => $data,
        '_serialize' => ['response', 'data']
      ));
    }

      /** 
        *G1 – getUsersByCourseID
        * Request –  Int<courseID> 
    */ 
    public function getUsersByCourseID($courseid=null){

        $usercourses = TableRegistry::get('UserCourses');
        $coursesrecord_counts = $usercourses->find('all')->contain(['Users'])->where(['course_id'=>$courseid])->count();
        $courses = $usercourses->find('all')->contain(['Users'])->where(['course_id'=>$courseid]);

        if($coursesrecord_counts>0){                
                foreach ($courses as $cr) {
                    $user['id'] = $cr->user['id'];
                    $user['First Name'] = $cr->user['first_name'];
                    $user['Last Name'] = $cr->user['last_name'];
                        
                    $data['message'] ="Result corresponding to course ID $courseid";
                    $data['course_id'] = $courseid;
                    $data['users'][] =  $user;
                }

        }else{
                $data['message'] ="No Records found because either course id is null or no records for selected course id";
                $data['course_id'] = $courseid;   
        }
        

        $this->set(array(
        'data' => $data,
           '_serialize' => ['data']
        ));

    }

       /** 
        *G3 - blockUsersFromCourse
        * Request - Int<courseID> , Array<UUIDs>, Bool<status>  BlockUserCourses
    */ 
    public function blockUsersFromCourse($courseid=null){     
        
      $usercourses= TableRegistry::get('BlockUserCourses');
      if ($this->request->is(['post', 'put'])) {

                if($this->request->data){
                   if($this->request->data['user_id']!=null ){
                      //$user_ids = ["1", "2", "3"];
                       $uids[]= $this->request->data['user_id'];
                        //$uids=$user_ids;   
                    foreach ($uids as $uid) {
                        $uc_records=$usercourses->find('all')->where(['course_id'=>$courseid,'user_id'=> $uid])->count();
 
                          if($uc_records>0){                            
                              $usercourse=$usercourses->patchEntity($this->request->data);
                                  if ($usercourses->save($usercourse))
                                      $data['message'] = "The Record is updated";                                  
                                  else
                                      $data['message'] = "The Record is not updated";
                                  
                          }
                          else{ 
                                  $usercourse=$usercourses->newEntity($this->request->data);
                                  if ($usercourses->save($usercourse))
                                    $data['message'] = "The Record was not exist. New Record is saved";                                  
                                  else
                                    $data['message'] = "The Record was not exist. New Record is also not saved";
                          }
                        
                    }
                  }
                  else{
                      $data['message'] = "UsserId is not set. Please set for response";
                  }
                }
                else{
                     $data['message'] = "No data is set. Please check again";
             }
      }
      else{
          $data['message'] = "No data is set. Please check again";
      }

      $this->set([
            'data' => $data,
            '_serialize' => ['data']
        ]);   
    }


/** 
        *S12 – isCourseAdmin
        *Request -   Int <courseID> , Int<UUID>
    */
        public function isCourseAdmin($uid=null, $courseid=null){
            if($uid!=null && $courseid!=null){
                  $connection = ConnectionManager::get('default');
                  $str="SELECT ur.role_id FROM courses as cr INNER JOIN user_roles as ur ON cr.created_by=ur.user_id where created_by=$uid AND cr.id=$courseid";
                  $results = $connection->execute($str)->fetchAll('assoc');
                  $count=0;
                  foreach ($results as $row) {
                      if($row['role_id']==3){
                          $count++;
                      }
                  }
                  if($count>0)
                      $data['response']="True";
                    else
                      $data['response']="False";
            }
              else{
                $data['message']="Either Course ID or User ID is null";
              }
              
      
              $this->set([
                'data' => $data,
                '_serialize' => ['data']
              ]);


            }


        /** 
      S31 - getHomeWorksbyUUID
    */
      public function getHomeWorksbyUUID($uid=null){
          $homework_records=TableRegistry::get('HomeWorks')->find('all')->where(['user_id'=>$uid])->count();
          $homeworks=TableRegistry::get('HomeWorks')->find('all')->where(['HomeWorks.user_id'=>$uid])->contain(['Users','Courses','CourseContents']);
          if($homework_records>0){
              foreach ($homeworks as $homework) {
                $data['message'] = 'Home works List';
                $data['HomeWorks'][] = $homework;
              }
            
          }
          else{
            $data['message'] = 'No Home works List for this user';
          }
          $this->set(['data' => $data, '_serialize' => ['data'] ]); 


      }



    /** 
        *S32 -  createHomework
        *Request –  Int<courseID> , Int<UUID>,String<desc>,String<title>,Int<lesson / nodeID>
    */
    public function createHomework(){
        $homework = TableRegistry::get('HomeWorks');
       
        if ($this->request->is(['post', 'put'])) {

                if($this->request->data){
                    if($this->request->data['course_id']!=null && $this->request->data['user_id']!=null ){
                    $usercourses=TableRegistry::get('UserCourses')->find('all')
                        ->where([
                            'course_id'=>$this->request->data['course_id'],
                             'user_id'=> $this->request->data['user_id']
                          ])
                        ->count();
                    if($usercourses>0){
                        $home_work=$homework->newEntity($this->request->data);
                        if ($homework->save($home_work)) {
                            $data['message'] = 'Home work details has been saved';
                        }else{
                            $data['message'] = 'Not Save. Please check the POST data';        
                        }

                    }else{
                        $data['message'] = 'No such course alloted to this user  '; 
                    }

                  }
                  else{
                      $data['message'] = 'Either user_id and course_id is not set '; 
                  }
                }                
        }else{
            $data['message'] = "No relvant content to store home work details ";
        }
        
        $this->set([
            'data' => $data,
            '_serialize' => ['data']
        ]); 

    }



    
        /** 
        *S33 – getExamsByCourse
        *Request –  String<CourseCode>
    */
        public function getExamsByCourse($coursecode=null){
          if($coursecode!=null){
            
            /*
            $examcourses = TableRegistry::get('ExamCourses');
                $query = $examcourses->find('all')
                //->fields('ExamCourses.*', 'course_details.*', 'courses.*')

                 ->join([
            'course_details' => [
                'table' => 'course_details',
                'type' => 'INNER',
                'conditions' => 'course_details.id = ExamCourses.course_detail_id'
            ],
            'courses' => [
                'table' => 'courses',
                'type' => 'INNER',
                'conditions' => 'courses.id = course_details.course_id'
            ]
        ]);
        */

         $connection = ConnectionManager::get('default');
         $str="SELECT exams.* FROM exams INNER JOIN exam_courses on exams.id=exam_courses.exam_id  INNER JOIN course_details ON course_details.id=exam_courses.course_detail_id INNER JOIN courses ON courses.id=course_details.course_id where course_code='$coursecode'";
          $results = $connection->execute($str)->fetchAll('assoc');

          foreach ($results as $result) {
                $exams[] = $result;
          }
           $data['message'] []= $exams;

          }
          else{
             $data['message'] = "No exams for course code null";
          }

            $this->set(['data' => $data, '_serialize' => ['data'] ]); 

        }

      


    /**
     * S7 - CourseContentTable
     */
    public function assetUpload() {
      $response = FALSE;
      $data['message'] = $data['url']=  '';
      try {
        $course_id = isset($this->request->data['course_id']) ? $this->request->data['course_id'] : '';
        $file = isset($this->request->data['asset']) ? $this->request->data['asset'] : '';
        if (!empty($course_id)) {
          $course_details_table = TableRegistry::get('CourseDetails');
          $course_details = $course_details_table->find()->where(['course_id' => $course_id]);
          foreach ($course_details as $details) {
            $course_detail_id = $details->id;
            break;
          }
          if (!empty($file['name'])) {
            $course_contents_table = TableRegistry::get('CourseContents');
            $upload_path = 'img/assets/';
            $resp_msg = $this->_uploadFiles($file, $upload_path, $course_contents_table, array(),$course_detail_id);
            if ($resp_msg['success'] == TRUE) {
              if ($course_details->count()) {
                foreach ($course_details as $course_details_fields) {
                  $course_details_fields->course_content_id = $resp_msg['course_content_id'];
                }
                if ($course_details_table->save($course_details_fields)) {
                  $response = TRUE;
                  $data['url'] = $resp_msg['url'];
                } else {
                  $data['message'] = 'Unable to save course details';
                }
              } else {
                $data['message'] = 'No record found regarding course id';
              }
            } else {
              $data['message'] = $resp_msg['message'];
            }
          } else {
            $data['message'] = 'File is missing';
          }
        } else {
          $data['message'] = 'Course id missing';
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() , 'error');
      }

      $this->set([
        'data' => $data,
        'response' => $response,
        '_serialize' => ['data', 'response']
      ]);
    }
    /*
     * S8 Desc – Service to update course logo to Amazons3 and return cdn url.
     */
    public function setCourseLogo() {
      $response = FALSE;
      $url = $data['message'] = '';
      try {
        if ($this->request->is(['post'])) {
          $file = isset($this->request->data['logo']) ? $this->request->data['logo'] : '';
          $course_code = isset($this->request->data['course_code']) ? $this->request->data['course_code'] : '';
          if (!empty($course_code)) {
            $allowded_extension = array('png', 'jpg', 'jpeg');
            $file_extension = @end(explode('/', $file['type']));
            if (in_array($file_extension, $allowded_extension)) {
              if (!empty($file['name'])) {
                $upload_path = 'img/logo/';
                $resp_msg = $this->_uploadFiles($file, $upload_path, 'default_table', ['course_code' => $course_code]);
                $response = $resp_msg['success'];
                $url = $resp_msg['url'];
                $data['message'] = $resp_msg['message'];
              } else {
                $data['message'] = 'File is required';
              }
            } else {
              $data['message'] = 'Invalid file format. Allowded files are: ' . implode(',', $allowded_extension);
            }
          } else {
            $data['message'] = 'Course code is required';
          }
        }
      } catch (Exception $e) {
        $this->log($e->getMessage() . '(' . __METHOD__ . ')', 'error');
      }
      $this->set(array(
        'response' => $response,
        'data' => $data,
        'url' => $url,
        '_serialize' => ['response', 'url', 'data']
      ));
    }

    /**
     * function _uploadFiles().
     *
     * @param Array $file
     *   contains $_FILES values.
     * @param String $upload_file
     *   location of file to be uploaded.
     * @param $table
     *   table details on which operation need to be processed.
     * @param Array $condition
     *   condition to match entity to be updated on db.
     * @return Array
     *   return response.
     */
    private function _uploadFiles($file, $upload_path, $table = 'default_table', $condition = array(), $variable = null) {
      $response = array('success' => FALSE, 'url' => '', 'message' => '');
      $file_name = time() . '_' . $file['name'];
      $file_path = $upload_path . $file_name;
      if (is_dir(WWW_ROOT . $upload_path)) {
        if (is_writable(WWW_ROOT . $upload_path)) {
          if (move_uploaded_file($file['tmp_name'], WWW_ROOT . $file_path)) {
            if ($table == 'default_table') {
              $course = $this->Courses->find()->where($condition);
              $fields = array();
              foreach ($course as $fields) {
                $fields->logo = $file_path;
              }
              if (!empty($fields) && $this->Courses->save($fields)) {
                $response['success'] = TRUE;
                $response['url'] = Router::url('/', true) . $file_path;
              } else {
                $response['message'] = 'Unable to save url to courses';
              }
            } else {
              $new_entry = $table->newEntity();
              $new_entry->url = $file_path;
              $new_entry->course_detail_id = !empty($variable) ? $variable : 0;
              if ($table->save($new_entry)) {
                $response['success'] = TRUE;
                $response['course_content_id'] = $new_entry->id;
                $response['url'] = Router::url('/', true) . $file_path;
              } else {
                $response['message'] = 'Unable to save url to table';
              }
            }
          } else {
            $response['message'] = 'Unable to upload file due to some error';
          }
        } else {
          $response['message'] = 'Upload path is not writable';
        }
      } else {
        $response['message'] = 'No such directory exist.';
      }

      return $response;
    }

    /*
     * S17 - Desc – Service to update or set course’s status to Active or Inactive.
     */
    public function setCourseStatus() {
    $response = FALSE;
    $data['message'] = '';
    try {
      if ($this->request->is(['post'])) {
        $course_id = isset($this->request->data['course_id']) ? $this->request->data['course_id'] : '';
        $status = isset($this->request->data['status']) ? $this->request->data['status'] : '';
        if (!empty($course_id)) {
          if ($status != '') {
            $course_details_table = TableRegistry::get('CourseDetails');
            $course_details = $course_details_table->find()->where(['course_id' => $course_id]);
            if (!empty($course_details->count())) {
              $course = array();
              foreach ($course_details as $course) {
                $course->status = $status;
              }
              if ($course_details_table->save($course)) {
                $response = TRUE;
              } else {
                $data['message'] = 'Error while saving into database';
              }
            } else {
              $data['message'] = 'No record found regarding entered course id';
            }
          } else {
            $data['message'] = 'Value of status is required';
          }
        } else {
          $data['message'] = 'Course id is required';
        }
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')', 'error');
    }

    $this->set(array(
        'response' => $response,
        'data' => $data,
        '_serialize' => ['response', 'data']
      ));
    }


    
    /*
     * SB4 – getCourseTeacher
     * Request -   String <CourseCode>
     */
     public function getCourseTeacher($coursecode=null) {
        if($coursecode!=null){          
          $course_records=$this->Courses->find()->contain(['Users'=>['UserRoles'] ])->where(['course_code' => $coursecode])->count();
          $courses=$this->Courses->find()->contain(['Users'=>['UserRoles'] ])->where(['course_code' => $coursecode]);
          if($course_records>0){
          foreach ($courses as $course) {
              $roles=$course->user->user_roles;
              foreach ($roles as $role) {
                $user_roles[]=$role['role_id'];
              }

              if(in_array('3',$user_roles )){
                   $data['message']="Result for Course Code is $coursecode";
                   $teacher['first_name'] = $course->user['first_name'];
                   $teacher['last_name'] = $course->user['last_name'];
                   $teacher['email'] = $course->user['email'];
                   $teacher['mobile'] = $course->user['mobile'];
                   $data['teacher'][] = $teacher;
              }
              else{
                  $data['message'] = "No Teacher found for Course code $coursecode ";
              }              
          }
        }
          else{
            $data['message'] = "No Teacher found for Course code $coursecode ";
          }
        }
        else{
            $data['message'] = "Course code is null";
        }
        $this->set(array(
        'data' => $data,
        '_serialize' => ['response', 'data']
      ));

     }


/*
      
    // * New - getPackages
    // * Request -   String <CourseCode>
     
     public function getPackages() {
        $packs = TableRegistry::get('Packages')->find('all');
        foreach ($packs as $pack) {
            $data['packs'][] = $pack;
                    }

        $this->set(array(
        'data' => $data,
        '_serialize' => ['response', 'data']
      ));

     }

*/

     public function getCourseListForLevel($grade=null){
       $data = array('message' => '', 'courses' => array(), 'course_list' => '');
       if($grade!=null){
          $courses_count= $this->Courses->find('all')->where(['level_id'=>$grade])->count();
         /*$courses= $this->Courses->find('all')->where(['level_id'=>$grade])->contain(['CourseDetails'=>['CourseContents'] ]);*/
          $courses= $this->Courses->find('all')->where(['level_id'=>$grade , 'parent_id'=>0])->contain(['CourseDetails']);
          if($courses_count>0){
              foreach ($courses as $course) {
                $data['message']="Records to course level $grade ";
                $data['courses'][]= $course;
                $data['course_list']= 1;
              }
          }else{
            $data['message']="Records is not found ";
            $data['courses'][]= "";
            $data['course_list']= 0;
          }

          
        }
        else{
            $data['message']="Please select Level/Grade ";
        }
        $this->set([           
           'response' => $data,
           '_serialize' => ['response']
         ]);

     }
       
     public function getAllCourseList($parent_id=0, $type=null, $course_id = null, $user_id = null){
      try {
        $base_url = Router::url('/', true);
        $teacher_controller = new TeachersController();
        if ($parent_id == null || $parent_id == 'null') {
          $json_course_parent_response = $teacher_controller->curlPost($base_url.'courses/getCourseInfo/' . $course_id);
          $course_parent_response = json_decode($json_course_parent_response, TRUE);
          if (isset($course_parent_response['response']) && $course_parent_response['response']['skill_info_of_subskill']
            && !empty($course_parent_response['response']['skill_info_of_subskill'])) {
            $parent_id = $course_parent_response['response']['skill_info_of_subskill']['id'];
          }
        }
        $khan_api_slugs = array();
        $teacher_contents = array();
        $khan_api_content_title = array();
        $course_details_table = TableRegistry::get('CourseDetails');
        $courses_table= TableRegistry::get('Courses');
        if ($type == 'type') {
          $connection = ConnectionManager::get('default');
          $sql = "SELECT * FROM course_details WHERE parent_id = $parent_id";
          $course_details = $connection->execute($sql)->fetchAll('assoc');
          $course_info = $connection->execute("select * from courses where id=$parent_id")->fetchAll('assoc');
        } else {
          if($type == 'student') {
            $course_details = $course_details_table->find('all')->where(['parent_id' => $parent_id]);
            $course_info = $courses_table->find('all')->where(['id' => $parent_id]);
          }else{
            $user_role = TableRegistry::get('user_roles');
            $role = $user_role->find('all', ['fields' => ['user_id']])->where(['role_id' => 1])->toArray();
            foreach ($role as $key => $value) {
              $rol[$key] = $value['user_id'];
            }
            $id = implode(',',$rol);
            $id = $id.','.$user_id;
            $connection = ConnectionManager::get('default');
            $sql = " SELECT * from course_details where created_by IN ($id) AND parent_id = $parent_id ";
            $course_details = $connection->execute($sql)->fetchAll('assoc');
            $course_info = $courses_table->find('all')->where(['id' => $parent_id]); 
          }
        }
      }  catch (Exception $e) {
        $this->log('Error in getAllCourseList function in Courses Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
      }
      if($type == 'type') {
        foreach ($course_details as $course_detail) {
          if (isset($course_detail['slug']) && !empty($course_detail['slug'])) {
            $slugs = explode(',' ,$course_detail['slug']);
            foreach ($slugs as $slug) {
              $slug_array = explode('/', $slug);
              if (count($slug_array) > 1) {
                $href_slice = array_slice($slug_array, -3, 3);
                if (strtoupper($href_slice[1]) == 'V') {
                  $khan_api_slugs[@current($href_slice)] = @current($href_slice);
                  $khan_api_content_title[] = @end($href_slice);
                }
              } else {
                $khan_api_slugs[] = @current($slug_array);
              }
            }
          }
          if (!empty($course_id) && ($course_detail['course_id'] == $course_id)) {
            $teacher_id = '';
            $json_student_teacher_response = $teacher_controller->curlPost($base_url.'teachers/getTeachersOfStudents/', json_encode(array('sid' => $user_id)), TRUE);
            $student_teacher_response = json_decode($json_student_teacher_response, TRUE);
            if ($student_teacher_response['status'] == TRUE) {
              if (isset($student_teacher_response['student_teacher_relation'][$user_id])) {
                $teacher_id = $student_teacher_response['student_teacher_relation'][$user_id];
              }
            }
            $sql = "SELECT DISTINCT * FROM course_contents WHERE course_detail_id = " . $course_detail['course_id'];
            $sql.= ' AND (created_by = ' . "'" . $teacher_id . "'" . ' OR shared_mode = 1)';
            $course_detail['course_contents'] = $connection->execute($sql)->fetchAll('assoc');
          }
          if (isset($course_detail['course_contents']) && !empty($course_detail['course_contents'])) {
            $course_contents = $course_detail['course_contents'];
            $root_path = Router::url('/', true);
            foreach ($course_contents as $course_content) {
              $user_id = $course_content['created_by'];
              $user = $this->getUserRoleByid($user_id);
              if (strtoupper($user['role_name']) == 'TEACHER') {
                $course_content['content'] = $course_content['content'];
                $teacher_contents[] = array(
                  'lesson_name' =>  $course_content['lesson_name'],
                  'url' =>  $course_content['url'],
                  'name' =>  $course_content['title'],
                  'kind' =>  $course_content['type'],
                  'descriptions' =>  $course_content['content'],
                  'standards' =>  $course_content['standards'],
                  'standard_type' =>  $course_content['standard_type'],
                );
              }
            }
          }
        }
      }
      $this->set([
         'response' => ['course_information'=>$course_info ,'course_details' => $course_details,
         'khan_api_slugs' => $khan_api_slugs,
         'khan_api_content_title' => $khan_api_content_title,
         'teacher_contents' => $teacher_contents],
         '_serialize' => ['response']
       ]);
     }

     /**
      * function getCourseAllDetails.
      * This function will fetch all contents , details regarding course id.
      */
     public function getCourseAllDetails($course_id = null) {
       try {
         $message = '';
         $data = array();
         if ($course_id == NULL) {
           $message = 'Course Id could not be empty';
           throw new Exception($message);
         }
         $course_details_table = TableRegistry::get('CourseDetails');
         $course_details = $course_details_table->find('all')->where(['course_id' => $course_id]);
         $data['course_details'] = $course_details->toArray();
         if ($course_details->count()) {
           $course_detail_id = '';
           foreach ($course_details as $course_detail) {
             $course_detail_id = $course_detail->id;
           }
           $course_contents_table = TableRegistry::get('CourseContents');
           $course_contents = $course_contents_table->find('all')->where(['course_detail_id' => $course_detail_id]);
           if ($course_contents->count()) {
             $data['course_contents'] = $course_contents->toArray();
           } else {
             $message = "No related content found regrading course";
             throw new Exception($message);
           }
         } else {
           $message = "No related content found regrading course";
           throw new Exception($message);
         }
       } catch (Exception $e) {
         $this->log($e->getMessage() . '(' . __METHOD__ . ')', 'error');
       }
       $this->set([
         'data' => $data,
         'message' => $message,
         '_serialize' => ['data', 'message']
       ]);

     }

     public function readCSV() {
       $status = FALSE;
       $message = '';
       try {
         $headers = array('parent_code', 'level_id', 'course_code', 'course_name', 'logo', 'meta_tags',
            'descriptions','author', 'created', 'modified', 'created_by', 'paid','price');
         if (!isset($this->request->data['csv']) || (@end(explode('/', $csv['type'])) == 'csv')) {
           $message = 'please upload CSV';
           throw new Exception('please upload CSV');
         }
         $csv = $this->request->data['csv'];
         $file =  fopen($csv['tmp_name'], 'r');
         $first_row = TRUE;
         while ($row = fgetcsv($file, 256, ',')) {
           if ($first_row) {
             $first_row = FALSE;
             continue;
           }
           $temp = array_combine($headers, $row);
           if ($this->Courses->find()->where(['course_code' => $temp['course_code']])->count()) {
             $message = 'No new record to insert';
             continue;
           }
           $parent_id = 0;
           if ($temp['parent_code'] != 'NULL') {
             $parent_course = $this->Courses->find()->select('id')->where(['course_code' => $temp['parent_code']])->limit(1)->toArray();
             foreach ($parent_course as $parent) {
               $parent_id = $parent->id;
               break;
             }
           }
           $course = $this->Courses->newEntity();
           $course->level_id = isset($temp['level_id']) ? $temp['level_id'] : '';
           $course->course_code = isset($temp['course_code']) ? $temp['course_code'] : '';
           $course->course_name = isset($temp['course_name']) ? $temp['course_name'] : '';
           $course->logo = isset($temp['logo']) ? $temp['logo'] : '';
           $course->meta_tags = isset($temp['meta_tags']) ? $temp['meta_tags'] : '';
           $course->descriptions = isset($temp['descriptions']) ? $temp['descriptions'] : '';
           $course->author = isset($temp['author']) ? $temp['author'] : '';
           $course->created = time();
           $course->modified = time();
           $course->created_by = isset($temp['created_by']) ? $temp['created_by'] : '';
           $course->paid = isset($temp['paid']) ? $temp['paid'] : '';
           $course->price = isset($temp['price']) ? $temp['price'] : '';


           if ($saved_course = $this->Courses->save($course)) {
             $course_details_table = TableRegistry::get('CourseDetails');
             $course_detail = $course_details_table->newEntity();
             $course_detail->course_id = $saved_course->id;
             $course_detail->course_content_id = isset($temp['course_content_id']) ? $temp['course_content_id'] : '';
             $course_detail->name = isset($temp['course_name']) ? $temp['course_name'] : '';
             $course_detail->meta_tags = isset($temp['meta_tags']) ? $temp['meta_tags'] : '';
             $course_detail->descriptions = isset($temp['descriptions']) ? $temp['descriptions'] : '';
             $course_detail->modified = time();
             $course_detail->validity = isset($temp['validity']) ? $temp['validity'] : '';
             $course_detail->status = isset($temp['status']) ? $temp['status'] : '';
             $course_detail->parent_id = $parent_id;
             $course_details_table->save($course_detail);
             $message = 'Records Inserted Successfully';
             $status = TRUE;
           }
         }
         fclose($file);
       } catch (Exception $ex) {
         $this->log($e->getMessage() . '(' . __METHOD__ . ')', 'error');
       }
        $this->set([
         'status' => $status,
         'message' => $message,
         '_serialize' => ['status', 'message']
       ]);
   }


/* important API get course/skill/subskill information */
  public function getCourseInfo($course_id=null){
      $course_id = isset($_GET['course_id']) ? $_GET['course_id'] : $course_id ;

      if(!empty($course_id)){
      $connection = ConnectionManager::get('default');
       $sql =" SELECT courses.id,courses.level_id, courses.course_code, courses.course_name, cd.parent_id ,levels.name as grade_name
                 from courses"
                . " INNER JOIN course_details as cd ON courses.id = cd.course_id "
               . " INNER JOIN levels ON courses.level_id = levels.id "
                . " WHERE courses.id =".$course_id." ORDER BY level_id ";
        $cdetails = $connection->execute($sql)->fetchAll('assoc');
       //pr($result);
        $course_count = count($cdetails);
        if($course_count>0){
            foreach ($cdetails as $coursedetail) {
                $parent_id = $coursedetail['parent_id'];
                $data['course_Information'] = $coursedetail;
            
                if($parent_id==0){                   
                    $data['course_Information']['course_type'] ="Main course/subject.";
              }

              //second level check
              else{                  
                  $data['course_Information']['course_type'] ="skill";

                // to check skill of course
                $sql1 =" SELECT courses.id,courses.level_id, courses.course_code, courses.course_name, cd.parent_id ,levels.name as grade_name
                 from courses"
                  . " INNER JOIN course_details as cd ON courses.id = cd.course_id "
                  . " INNER JOIN levels ON courses.level_id = levels.id "
                  . " WHERE courses.id =".$parent_id." ORDER BY level_id ";
                  $cdetails1 = $connection->execute($sql1)->fetchAll('assoc');

                  $count1 = count($cdetails1);
                  if($count1>0){
                      foreach ($cdetails1 as $cdetailrow) {
                          $parentid = $cdetailrow['parent_id'];
                          //$data['parent_Information'][] = $cdetailrow;

                          if($parentid==0){
                              $data['course_Information']['course_type'] ="skill";
                              $data['parent_info_of_skill'] = $cdetailrow;
                        }
                        else{

                          $data['course_Information']['course_type'] ="Sub skill";
                          $data['skill_info_of_subskill'] = $cdetailrow;

                          $sql2 =" SELECT courses.id,courses.level_id, courses.course_code, courses.course_name, cd.parent_id ,levels.name as grade_name
                           from courses"
                          . " INNER JOIN course_details as cd ON courses.id = cd.course_id "
                          . " INNER JOIN levels ON courses.level_id = levels.id "
                          . " WHERE courses.id =".$parentid." ORDER BY level_id ";

                            $cdetails2 = $connection->execute($sql2)->fetchAll('assoc');
                            $count2 = count($cdetails2);


                            if($count2>0){

                                foreach ($cdetails2 as $crow) {
                                  $prid = $crow['parent_id'];                                 
                                    if($prid==0){                                      
                                      $data['course_Information']['course_type'] ="Sub skill";
                                      $data['parent_info_of_skill'] = $crow;
                                    }
                                    else{
                                      $data['hhh'] ="kk";
                                    }
                                }
                            }

                        }                   


                      }

                  }


              }

            }



          

        }
        else{
          $data['status'] ="False";
          $data['message'] = " No Course information for this course id.";
        }

      }
      else{
        $data['status'] = "False";
        $data['message'] = "Course_id cannot null. Please set course course id.";
      }

      $this->set([
        'response' => $data,
         '_serialize' => ['response']
       ]);



  }



   /*
    * function getUserRoleByid
    *
    * $user_id : User's Id.
    */
   protected function getUserRoleByid($user_id = null) {
    try {
      $message = '';
      $user = array('user_id' => '', 'role_id' => '', 'role_name' => '');
      if ($user_id == null) {
        $message = 'User_id cannot be null';
        throw new Exception($message);
      }
      $user_roles_table = TableRegistry::get('UserRoles');
      $role = $user_roles_table->find()->where(['user_id' => $user_id])->contain(['Roles'])->toArray();
      foreach ($role as $user_role) {
        $user = array(
          'user_id' => $user_role->user_id,
          'role_id' => $user_role->role_id,
          'role_name' => $user_role->role->name,
        );
      }
    } catch (Exception $ex) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')', 'error');
    }
    return $user;
  }



  // Function to get courses of a grade : for main course (math) parent_id=0; but course (number system) parent_id=course_id of math is as skill, but for course(number system) parent_id=course_id of number system as subkill.
  public function getCourseSkillSubskills($grade_id=null,  $parent_id=0){
    $grade_id = isset($_GET['grade_id'])?$_GET['grade_id']:$grade_id;    
    $parent_id = isset($_GET['parent_id'])?$_GET['parent_id']:$parent_id;

    if(!empty($grade_id) && !empty($parent_id) ){
        $courses= $this->Courses->find('all')->contain(['CourseDetails'])->where(['level_id'=>$grade_id,'parent_id'=>$parent_id ]);
          
        if($courses->count() > 0 ){                    
            foreach ($courses as $cr) {
               $course_list['course_id'] = $cr['id'];
               $course_list['course_name'] = $cr['course_name'];
               $course_list['course_code'] = $cr['course_code'];              
              
               $data['courses'][] = $course_list;                            
              }
               
            $data['status'] = "True";    
        }else{
            $data['status'] = "False";
            $data['message'] = "No records found";
        }
    }else{
        $data['status'] = "False";
        $data['message'] = "Course_id and Parent_id cannot null;";
    }
    

  $this->set([
     'response' => $data,       
     '_serialize' => ['response']
  ]);
}
/**
 * This api is used for getting student skills.
 **/
  public function getStudentSkills($user_id=null,$parent_id=null,$course=null) {
    try{
      $message='';
      $status = FALSE;
      $scopes = array();
      $course_details = '';
      $setting = FALSE;
      $by = 'course';
      $scope = TableRegistry::get('scope_and_sequence');
      $group = TableRegistry::get('student_groups');
      $course_detail = TableRegistry::get('course_details');
      if($user_id == NULL) {
        $message = 'Please login.';
        throw new Exception('Please Login');
      }else if($parent_id == NULL) {
        $message = 'Please select a subject.';
        throw new Exception('Please select a subject.');
      }else{
        $connection = ConnectionManager::get('default');
        if($course == NULL ) {
          $sql = " SELECT * from student_teachers where student_id = $user_id AND course_id = $parent_id "; 
        }else if($course == '-1'){
          $result = $course_detail->find('all',['fields'=>['parent_id']])->where(['course_id' => $parent_id])->toArray();
          $sql = " SELECT * from student_teachers where student_id = $user_id AND course_id = ".$result[0]['parent_id'] ;
        }else{
          $sql = " SELECT * from student_teachers where student_id = $user_id AND course_id = $course ";
        }
        $tid = $connection->execute($sql)->fetchAll('assoc');
        $scopes = $scope->find()->where(['created_for'=> $user_id,'parent_id' => $parent_id]);
        $scopCount = $scopes->count();
        if($scopCount == 1 ) {
          $by = 'scope';
          $status = TRUE;
          $course_details[0] = $scopes;
        }else{
          if(!empty($tid)) {
            $user_setting = TableRegistry::get('teacher_settings')
              ->find()->where(['user_id' => $tid[0]['teacher_id'], 'course_id' => $tid[0]['course_id'], 
                 'level_id' => $tid[0]['grade_id']]);
            $scopes = $scope->find()->where(['created_by'=> $tid[0]['teacher_id'] ,'parent_id' => $parent_id,
              'type IN'=>['class','group']])->orderDESC('type','created');
            $scopCount = $scopes->count();
            if($scopCount > 0) {
            $by = 'scope';
            $count = 0;
            foreach ($scopes  as $key => $value) {
               if($count >= 1) {
                    break;
               }
              if($value['type'] == 'group') {
                $data = $group->find()->where(['id' => $value['created_for']]);
                foreach($data as $ki=>$val) {
                  $students = explode(',', $val['student_id']);
                  if(in_array($user_id,$students)){
                    $course_details[0] = $value;
                    $count++;
                    $status = TRUE;
                    break;
                  }
                }
              }else if($value['type'] == 'class') {
                $course_details[0] = $value;
                $status = TRUE;
              }
            }
          }else{
            $course_detail = TableRegistry::get('course_details');
            $user_role = TableRegistry::get('user_roles');
            $role = $user_role->find('all', ['fields' => ['user_id']])->where(['role_id' => 1])->toArray();
            foreach ($role as $key => $value) {
              $rol[$key] = $value['user_id'];
            }
            $id = implode(',',$rol);
            $id = $id.','.$tid[0]['teacher_id'];
            $connection = ConnectionManager::get('default');
            $sql = " SELECT * from course_details where created_by IN ($id) AND parent_id = $parent_id ";
            $skills = $connection->execute($sql)->fetchAll('assoc');
            if(count($skills) > 0) {
              foreach ($skills as $key => $value) {
              $skill['course_id'] = $value['course_id'];
              $skill['parent_id'] = $value['parent_id'];
              $skill['name'] = $value['name'];
              $skill['start_date'] = $value['start_date'];
              $skill['end_date'] = $value['end_date'];
              $skill['created_by'] = $value['created_by'];
              $skill['status'] = $value['status'];
              $skill['visibility'] = 1;
              $course_details[] = $skill;
              }
              $status = TRUE; 
            }
          }
          if(!empty($user_setting)) {
            foreach ($user_setting as $key => $value) {
             $temp_setting[$key] = json_decode($value['settings']);
            }
            foreach($temp_setting as $ki=>$val) {
              if($val->auto_progression == TRUE) {
                if($val->auto_progression_by == 'individual'
                        && $val->auto_progression_for_individual == $user_id ) {
                  $setting = TRUE;
                }else if($val->auto_progression_by == 'group') {
                  $data = $group->find()->where(['id' => $val->auto_progression_for_group]);
                  foreach($data as $key=>$value) {
                    $students = explode(',', $value['student_id']);
                    if(in_array($val->$user_id,$students)){
                      $setting = TRUE;
                      break;
                    }
                  } 
                }else if($val->auto_progression_by == 'all_class') {
                  $setting = TRUE;
                }
              }
            }
   
          }
          }else{
            $course_detail = TableRegistry::get('course_details');
            $user_role = TableRegistry::get('user_roles');
            $role = $user_role->find('all', ['fields' => ['user_id']])->where(['role_id' => 1])->toArray();
            foreach ($role as $key => $value) {
              $rol[$key] = $value['user_id'];
            }
            $id = implode(',',$rol);
            $connection = ConnectionManager::get('default');
            $sql = " SELECT * from course_details where created_by IN ($id) AND parent_id = $parent_id ";
            $skills = $connection->execute($sql)->fetchAll('assoc');
            if(count($skills) > 0) {
              foreach ($skills as $key => $value) {
              $skill['course_id'] = $value['course_id'];
              $skill['parent_id'] = $value['parent_id'];
              $skill['name'] = $value['name'];
              $skill['start_date'] = $value['start_date'];
              $skill['end_date'] = $value['end_date'];
              $skill['created_by'] = $value['created_by'];
              $skill['status'] = $value['status'];
              $skill['visibility'] = 1;
              $course_details[] = $skill;
              }
              $status = TRUE; 
            }
          }
        }
      }
    }catch(Exception $e) {
      $this->log('Error in getUserCreatedScope function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $course_details,
        'setting' => $setting,
        'message' => $message,
        'status' => $status,
        'by' => $by,
        '_serialize' => ['response','message','status','by','setting']
    ]);
  }

  /*
   * function deleteUserCourses().
   */
  public function deleteUserAllCourses() {
    try {
      $status = FALSE;
      $record_found = '';
      $message = '';
      if ($this->request->is('post')) {
        if (!isset($this->request->data['user_id']) && empty($this->request->data['user_id'])) {
          $message = 'user id cannot be null';
          throw new Exception('user_id cannot be null');
        }
        $user_courses_table = TableRegistry::get('UserCourses');
        $record_found = $user_courses_table->find()->where(['user_id' => $this->request->data['user_id']])->count();
        if (!$record_found) {
          $message = 'No record to delete';
          throw new Exception($message);
        }
        if ($user_courses_table->deleteAll(['user_id' => $this->request->data['user_id']])) {
          $status = TRUE;
        } else {
          $message = 'unable to delete user courses entries';
          throw new Exception($message);
        }
      }
    } catch(Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'record_found' => $record_found,
      '_serialize' => ['status','message','record_found']
    ]);
  }

  /*
   * function setUserCourse().
   */
  public function setUserCourse() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        if (!isset($this->request->data['user_id']) && empty($this->request->data['user_id'])) {
          $message = 'user id cannot be null';
          throw new Exception('user_id cannot be null');
        }
        if (!isset($this->request->data['course_ids']) && empty($this->request->data['course_ids'])) {
          $message = 'course id cannot be null';
          throw new Exception('course id cannot be null');
        }
        $course_ids = $this->request->data['course_ids'];
        if (!is_array($course_ids)) {
          $message = 'course id must be an array';
          throw new Exception($message);
        }
        $user_id = $this->request->data['user_id'];
        $expiry_date = isset($this->request->data['expiry_date']) ? $this->request->data['expiry_date'] : date('Y-m-d');
        $user_courses_table = TableRegistry::get ('UserCourses');
        $data = array();
        foreach ($course_ids as $course_id) {
          $data[] = [
            'user_id' => $user_id,
            'course_id' => $course_id,
            'expiry_date' => $expiry_date
          ];
        }
        $entities = $user_courses_table->newEntities($data);
        if ($user_courses_table->saveMany($entities)) {
          $status = TRUE;
        } else {
          $message = 'unable to save data';
        }
      }
    } catch(Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      '_serialize' => ['status','message']
    ]);
  }


  /** important  API will return skill and subskill of a subject
        if find skill of subject then child_level=1 and parent_id= skill_id
        if find subskill of subject then child_level=2 and parent_id
        return_type defind return type =1/2 of function as as json=1 or array=2
   **/
   public function getChildCoursesOfSubject($parent_id=null, $child_level=1, $return_type=1){ // child_level is hierarchical
      if(!empty($parent_id)){  // parent_id can be subject_id or skill_id.
            $connection = ConnectionManager::get('default');
           
            /* ************************************ */
            if($child_level==1){

                $sql = "SELECT c.*, l.name as grade_name FROM courses as c, course_details as cd, levels as l 
                        WHERE  c.id=cd.course_id AND c.level_id=l.id AND cd.parent_id = $parent_id ";          
                $records = $connection->execute($sql)->fetchAll('assoc');
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $data['child_courses'][] = $record;
                        $data['childcourse_ids'][] = $record['id'];
                    }
                    $data['status'] = True;
                }
                else{
                      $data['status']=False;
                      $data['message']="No child courses are found.";
                } 
                         
            }   
           
            /* ************************************ */
            if($child_level==2){

                $sql = "SELECT c.*, l.name as grade_name FROM courses as c, course_details as cd, levels as l 
                        WHERE  c.id=cd.course_id AND c.level_id=l.id AND cd.parent_id = $parent_id ";        
                $records = $connection->execute($sql)->fetchAll('assoc');
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $cid = $record['id'];
                        $data[]= $this->getChildCoursesOfSubject($record['id'],1,2);                                              
                    }                    
                    $data['status'] = True;
                }
                else{
                      $data['status']=False;
                      $data['message']="No child courses are found.";
                }                

            }    
       }else{
            $data['status'] =False;
            $data['message']="Parent_id cannot be null.";
        }


        // check return type value
        if($return_type==1){
              $this->set([ 'response' => $data, '_serialize' => ['response','message','status','by'] ]);
        }
        if($return_type==2){                     
            return $data;
        }

        
    
    }// end function
  /**
   * This api is used for getting standard.
   **/
   public function getStandard($type,$grade,$course_id){
     try{
       $message = '';
       if($type == null || $type == ''){
         $message = 'Select standard type';
         throw new Exception('Select standard type');
       }else if($grade == null || $grade == ''){
         $message = 'Select grade';
         throw new Exception('Select grade');
       }else if($course_id == null || $course_id == ''){
         $message = 'Select course';
         throw new Exception('Select course.');
       }else{
         $connection = ConnectionManager::get('default');
         $sql = "select * from standard where type = '$type' AND grade_id = $grade AND course_id = $course_id";
         $standardList = $connection->execute($sql)->fetchAll('assoc');
       }
     }catch(Exception $e){
       $this->log('Error in getStandard function in Courses Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
     }
     $this->set([
        'response' => $standardList,
        'message' => $message,
//        'status' => $status,
        '_serialize' => ['response','message']
    ]);
   }
}