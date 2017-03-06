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

    /*
     * S5- Desc – Service to update course details or meta information.
     */
    public function setCourseDetails() {
      $response = FALSE;
      $data['message'] = '';
      try {
        if ($this->request->is(['post'])) {
          $course_id = isset($this->request->data['course_id']) ? $this->request->data['course_id'] : '';
          $name = isset($this->request->data['name']) ? $this->request->data['name'] : '';
          $meta_tags = isset($this->request->data['meta_tags']) ? $this->request->data['meta_tags'] : '';
          $descriptions = isset($this->request->data['descriptions']) ? $this->request->data['descriptions'] : '';
          if (!empty($course_id)) {
            $courses_table = $this->Courses->find()->where(['id' => $course_id]);
            foreach ($courses_table as $courses) {
              $courses->course_name = !empty($name) ? $name : $courses->course_name;
              $courses->meta_tags = !empty($meta_tags) ? $meta_tags : $courses->meta_tags;
              $courses->descriptions = !empty($descriptions) ? $descriptions : $courses->descriptions;
            }
            $course_details_table = TableRegistry::get('CourseDetails');
            $course_details = $course_details_table->find()->where(['course_id' => $course_id]);
            foreach ($course_details as $course_detail) {
              $course_detail->name = !empty($name) ? $name : $course_detail->name;
              $course_detail->meta_tags = !empty($meta_tags) ? $meta_tags : $course_detail->meta_tags;
              $course_detail->descriptions = !empty($descriptions) ? $descriptions : $course_detail->descriptions;
            }
            if ($this->Courses->save($courses) && $course_details_table->save($course_detail)) {
              $response = TRUE;
            }
          } else {
            $data['message'] = 'Course id could not be empty';
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
          if (!empty($file['name'])) {
            $course_contents_table = TableRegistry::get('CourseContents');
            $upload_path = 'img/assets/';
            $resp_msg = $this->_uploadFiles($file, $upload_path, $course_contents_table);
            if ($resp_msg['success'] == TRUE) {
              $course_details_table = TableRegistry::get('CourseDetails');
              $course_details = $course_details_table->find()->where(['course_id' => $course_id]);
              if ($course_details->count()) {
                foreach ($course_details as $course_details_fields) {
                  $course_details_fields->node_id = $resp_msg['node_id'];
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
    private function _uploadFiles($file, $upload_path, $table = 'default_table', $condition = array()) {
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
              if ($table->save($new_entry)) {
                $response['success'] = TRUE;
                $response['node_id'] = $new_entry->id;
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
}
