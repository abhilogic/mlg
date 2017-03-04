<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Core\Exception\Exception;
use Cake\Routing\Router;
//use Cake\Datasource\ConnectionManager;

/**
 * Users Controller
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
        * Request - Int<courseID> , Array<UUIDs>, Bool<status> 
    */ 
    public function blockUsersFromCourse($courseid=null){

    }

     /** 
        *S32 -  createHomework
        *Request –  Int<courseID> , Int<UUID>,String<desc>,String<title>,Int<lesson / nodeID>
    */
    public function createHomework(){
        $homework = TableRegistry::get('HomeWorks');
       
        if ($this->request->is(['post', 'put'])) {

                if($this->request->data){
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
        }else{
            $data['message'] = "No relvant content to store home work details ";
        }
        
        $this->set([
            'data' => $data,
            '_serialize' => ['data']
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
            if (!empty($file['name'])) {
              $file_name = time() . '_' .$file['name'];
              $upload_path = 'img/logo/';
              $upload_file = $upload_path.$file_name;
              if (move_uploaded_file($file['tmp_name'], WWW_ROOT . $upload_file)) {
                $course = $this->Courses->find()->where(['course_code' => $course_code]);
                $fields = array();
                foreach ($course as $fields) {
                  $fields->logo = $upload_file;
                }
                if (!empty($fields) && $this->Courses->save($fields)) {
                  $response = TRUE;
                  $url = Router::url('/', true) . $upload_file;
                } else {
                  $data['message'] = 'Unable to save file to database';
                }
              } else {
                $data['message'] = 'unable to upload file due to some error';
              }
            } else {
              $data['message'] = 'File is required';
            }
          } else {
            $data['message'] = 'Course code is required';
          }
        }
      } catch (Exception $e) {
        $this->log($e->getMessage() . '('. __METHOD__.')','error');
      }
      $this->set(array(
        'response' => $response,
        'data' => $data,
        'url' => $url,
        '_serialize' => ['response', 'url', 'data']
      ));
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
}
