<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
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
    public function index(){
              
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

    

   
}
