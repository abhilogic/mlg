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
}
