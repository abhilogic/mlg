<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Core\Exception\Exception;
use Cake\Routing\Router;
use Cake\Datasource\ConnectionManager;

/**
 * Users Controller
 */
class ExamsController extends AppController{

    public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
         $this->RequestHandler->renderAs($this, 'json');
    }


    /** Index method    */
    public function index() {

        $data['courses'] = array();
        $courses = $this->Exams->find('all')->toArray();
        foreach($courses as $course){
            $data['courses'][]= $course;
        }

        $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));
    }


    /**
     * Desc – Service to get all the attempts made by user for quiz.
     * Request –  String<quizID>, Int<UUID>
     */
    public function getUserAttemptsToQuiz() {
      $data['count'] = '';
      try {
        if ($this->request->is('post')) {
          $quiz_id = $this->request->data['quiz_id'];
          $user_id = $this->request->data['user_id'];
          $connection = ConnectionManager::get('default');
          $sql="SELECT COUNT(user_quiz_responses.user_id)"
            . " FROM user_quiz_responses"
            . " INNER JOIN quiz_items ON user_quiz_responses.item_id=quiz_items.item_id"
            . " INNER JOIN exam_sections ON quiz_items.exam_section_id=exam_sections.id"
            . " WHERE exam_sections.exam_id='$quiz_id' AND user_quiz_responses.user_id='$user_id'";
          $results = $connection->execute($sql)->fetchAll('num');
          foreach ($results as $result) {
            $data['count']  = @end($result);
          }
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage());
      }

      $this->set(array(
        'data' => $data,
        '_serialize' => ['data']
      ));
    }

    /**
     * Desc – Service to get question by question review of quiz attempted by user.
     * Request –  String<quizID>, Int<UUID>
     */
    public function getUserAttemptDetailsByQuizID() {
      try {
        if ($this->request->is('post')) {
          $data = array();
          $quiz_id = $this->request->data['quiz_id'];
          $user_id = $this->request->data['user_id'];
          $connection = ConnectionManager::get('default');
          $sql = "SELECT user_quiz_responses.*, quiz_items.exam_section_id, exam_sections.description"
            . " FROM user_quiz_responses"
            . " INNER JOIN quiz_items on user_quiz_responses.item_id=quiz_items.item_id"
            . " INNER JOIN exam_sections ON quiz_items.exam_section_id=exam_sections.id"
            . " WHERE exam_sections.exam_id='$quiz_id' AND user_quiz_responses.user_id='$user_id'";
          $results = $connection->execute($sql)->fetchAll('assoc');
          $i = 0;
          foreach ($results as $result) {
            if($i++ === 0) {
              $data[$result['exam_section_id']]  = [
                'section_id' => $result['exam_section_id'],
                'description' => $result['description'],
              ];
            }
            $data[$result['exam_section_id']]['questions'][] = [
              'qId' => $result['item_id'],
              'option' => $result['response'],
              'timeTaken' => $result['time_taken'],
              'skip' => $result['skip_count'],
              'score' => $result['score'],
              'askedForHint' => 'yes',
            ];
          }
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage());
      }

      $this->set(array(
        'data' => $data,
        '_serialize' => ['data']
      ));
    }


/** 
        *A1 – getQuestionsBySectionID
        *Request –  String<CourseCode>
    */
  public function getQuestionsBySectionID($examid=null, $examsectionid=null) {
    if($examid!=null){
        $data['message'] ="Questions of the Exam ID=$examid";
          $quizitems = TableRegistry::get('QuizItems');
          $quizs=$quizitems->find()->contain(['Items','ExamSections'])->where(['ExamSections.exam_id'=>$examid, 'ExamSections.id'=>$examsectionid]); 

      
          foreach($quizs as $quiz){   
              $questions['exam_item_id']=$quiz['id'];
              $questions['item_id']=$quiz['item_id'];
              
              $questions['exam_section_id']=$quiz->exam_section['id'];
              $questions['exam_id']=$quiz->exam_section['exam_id'];
              $questions['exam_section_name']=$quiz->exam_section['name'];
              $questions['no_of_questions']=$quiz->exam_section['no_of_questions'];
              $questions['exam_section_description']=$quiz->exam_section['description']; 
              
              $questions['item_type']=$quiz->items['type'];
              $questions['time_dependent']=$quiz->items['time_dependent'];
              $questions['item_title']=$quiz->items['item_body'];
              $questions['item_title']=$quiz->items['marks'];
              $data['response'][]=$questions;              
             // pr($quiz); die;                    
          }           
    }
    else{
        $data['message']= "Exam ID is null";
    }

    $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));

  }


  /** 
        *A2 – getSectionsByQuizID
        *Request –  String<CourseCode>, Int<quizID/exam_id>
    */
  public function getSectionsByQuizID($examid=null, $courseid=null) {
        if($examid!=null && $courseid!=null ){
          $data['message']= "Section ID for ExamID=$examid and CourseCode=$courseid";
          $exam_courses = TableRegistry::get('ExamCourses');
          $sections_records=$exam_courses->find()->contain(['CourseDetails'=>['Courses'],'ExamSections' ])->where(['Courses.course_code'=> $courseid, 'ExamSections.exam_id'=>$examid])->count();

          $sections=$exam_courses->find()->contain(['CourseDetails'=>['Courses'],'ExamSections' ])->where(['Courses.course_code'=> $courseid, 'ExamSections.exam_id'=>$examid]);
          //debug($sections); 
          
            if($sections_records>0){
                foreach($sections as $examsection){
                    $section['section_id']    =  $examsection->exam_section['id'];
                    $section['exam_id'] = $examsection->exam_section['exam_id'];
                    $section['course_id'] = $examsection->course['id'];
                    $section['course_code'] = $examsection->course['course_code'];
                    
                    $section['name']  =  $examsection->exam_section['name'];
                    $section['description'] = $examsection->exam_section['description'];
                    $section['no_of_questions'] = $examsection->exam_section['no_of_questions'];

                    $data['section'][] = $section;
                }
            }
            else{
                $data['message']= "No Record for ExamID=$examid and CourseCode=$courseid";
            }

        }
        else{
            $data['message']= "Either ExamID or CourseCode is null";
        }

        $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));

  }


   
}// end of class







}
