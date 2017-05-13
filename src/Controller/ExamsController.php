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

        $data['exams'] = array();
       
        //$exams = $this->Exams->find()->contain(['ExamSections'=>['QuizItems'=>['Items'] ] ]);
        $exams = $this->Exams->find();

        foreach($exams as $exam){
          //$exam_details['exam_id']=$exam['id'];
         // $exam_details['section_id']=$exam->exam_section['id'];
        //  $exam_details['item_id']= $exam->exam_section->quiz_item['id'];          
            $data['exams'][]= $exam;
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
    if($examid!=null && $examsectionid!=null){
        
          $quizitems = TableRegistry::get('QuizItems');
          $quiz_records=$quizitems->find()->contain(['Items','ExamSections'])->where(['ExamSections.exam_id'=>$examid, 'ExamSections.id'=>$examsectionid])->count();
          $quizs=$quizitems->find()->contain(['Items','ExamSections'])->where(['ExamSections.exam_id'=>$examid, 'ExamSections.id'=>$examsectionid]); 
            
            if($quiz_records>0){
              $data['message'] ="Questions of the Exam ID=$examid";
              foreach($quizs as $quiz){   
                  $questions['exam_id']=$quiz->exam_section['exam_id'];
                  $questions['exam_section_id']=$quiz->exam_section['id'];
                  $questions['exam_section_name']=$quiz->exam_section['name'];
                  $questions['no_of_questions']=$quiz->exam_section['no_of_questions'];
                  $questions['exam_section_description']=$quiz->exam_section['description'];

                  $questions['exam_item_id']=$quiz['id'];
                  $questions['item_id']=$quiz['item_id'];             
                  $questions['item_type']=$quiz->items['type'];
                  $questions['time_dependent']=$quiz->items['time_dependent'];
                  $questions['item_title']=$quiz->items['item_body'];
                  $questions['item_title']=$quiz->items['marks'];
                  $data['Questions'][]=$questions;              
                 // pr($quiz); die;                    
              } 
            }
            else{
              $data['message'] ="No record found the Exam ID=$examid";              
            }
    }
    else{
        $data['message']= "Either Exam ID or Exam SectionId is null";
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


    /**
     * A3 - getUserAttemptsToQuiz
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
        *A4 – getUserScoreForQuiz
        *Request –  String<quizID>, Int<UUID>
    */
  public function getUserScoreForQuiz($examid=null, $uid=null) {
      if (isset($_REQUEST)){
          $examid=isset($_REQUEST['exam_id'])? $_REQUEST['exam_id']:'null';
          $uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:'null';
      }
      $data['status'] = 1;
      if($examid!=null && $uid!=null){
          $data['message']= "Score of UserID $uid for examId $examid";
          $userQuizes = TableRegistry::get('UserQuizes');

          $userquizes=$userQuizes->find('all');
          foreach($userquizes as $userquize){
              $data['score'] = $userquize['score'];
          }
      }
      else{
          $data['message']= "Either ExamID or user id is null";
      }

      $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));
 }



    /**
     * A5 – getUserAttemptDetailsByQuizID
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
        *A8 – mapItemsToQuiz
        *Request –  String<quizID>, or Int<sectionId>
    */
public function mapItemsToQuiz($examid=null, $sectionid=null) {
  $examItems = TableRegistry::get('QuizItems');
  if($examid!=null && $sectionid!=null){
        $data['message']= "Items for examId $examid and sectionId $sectionid";      
        $examitems=$examItems->find()->contain(['ExamSections','Items'])->where(['ExamSections.exam_id'=>$examid,'exam_section_id'=>$sectionid]);

        foreach ($examitems as $examitem) {
          $data['quiz_items'][]=$examitem;
  }
                }
    elseif($examid!=null){
        $data['message']= "Items for examId $examid";
        $examitems= $examItems->find()->contain(['ExamSections','Items'])->where(['ExamSections.exam_id'=>$examid]);
        
        foreach ($examitems as $examitem) {
          $data['quiz_items'][]=$examitem;
        }
    }
      else{
          $data['message']= "Either ExamID or SectionId is null";
      }
    $this->set(array(
                'data' => $data,
                '_serialize' => ['data']
           ));
}



// To add the value of attamped question response in Table user quiz response
public function setUserQuizResponse(){   
    if ($this->request->is('post')) {  
        $quiz_marks=0;  
        $student_score=0;
        $attamp_questions=0; 
       $attendQuizResponses= $this->request->data;

         // Array traverse to get User Quiz Result
        foreach ($attendQuizResponses as $attendQuizResponse) {
              $uid=$attendQuizResponse['user_id'];
              $eid=$attendQuizResponse['exam_id'];
              $quiz_marks=$quiz_marks+$attendQuizResponse['item_marks'];
              $student_score=($student_score)+($attendQuizResponse['score']);
              $attamp_questions++;                 
        }

       
       // add Student Quiz Result in user_quiz table
        $quiz_result= ($student_score*100)/($quiz_marks);
        if($quiz_result<60){ $pass=0;}
        else{ $pass=1;}
        $userQuizes = TableRegistry::get('UserQuizes');
        $new_userQuizes = $userQuizes->newEntity(array('user_id'=>$uid, 'exam_id'=>$eid,'exam_marks'=>$quiz_marks,'attempts' =>$attamp_questions,'created' => time(),'score'=>$student_score,'status'=>1, 'pass'=>$pass ) );
            
              if ($result=$userQuizes->save($new_userQuizes)) { 
                $postdata['user_quiz_id']  = $result->id;               
                $data['message']="Successfull data is save.";
                $data['status']="true";

                  // Add each question attamp response in user_quiz_response 
                  $userQuizResponses = TableRegistry::get('UserQuizResponses');                 
                  foreach ($attendQuizResponses as $attendQuizRes) {
                      $postdata['user_id']    =isset($attendQuizRes['user_id'])?$attendQuizRes['user_id']:"null";
                      $postdata['exam_id']    =isset($attendQuizRes['exam_id'])?$attendQuizRes['exam_id']:"null";                
                      $postdata['response']   =isset($attendQuizRes['response'])?$attendQuizRes['response']:"null";
                      $postdata['correct']    =isset($attendQuizRes['correct'])?$attendQuizRes['correct']:"null";
                      $postdata['item_marks'] =isset($attendQuizRes['item_marks'])?$attendQuizRes['item_marks']:"0";
                      $postdata['score']      =isset($attendQuizRes['score'])?$attendQuizRes['score']:"null";
                      $postdata['skip_count'] =isset($attendQuizRes['skip_count'])?$attendQuizRes['skip_count']:"0";
                      $postdata['time_taken'] =isset($attendQuizRes['time_taken'])?$attendQuizRes['time_taken']:"0";
                      $postdata['exam_date']  =time();       
                      $item_id =isset($attendQuizRes['item_id'])?$attendQuizRes['item_id']:"null";
                      $itemid = explode('-', $item_id);
                      $postdata['item_id']=$itemid[1];

                      $new_userQuizResponse = $userQuizResponses->newEntity($postdata);
                      if ($userQuizResponses->save($new_userQuizResponse)) {
                        $data['message']="add data in user responnse table";
                        $data['quiz_attampt']=$postdata['user_quiz_id'];
                        $data['status']="true";
                      }
                      else{
                          $data['message']="No data add in user quiz response table";
                          $data['quiz_attampt']=$postdata['user_quiz_id'];
                          $data['status']="false";
                       }
                  }
              }else{
                  $data['message']="opps data is not saved in user quiz table";
                  $data['status']="false";
              }

            }else{
              $data['message']="No post data to save";
              $data['status']="false";

            }

      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));
}




// To get the quiz Response
public function getUserQuizResponse($uid=null,$exam_id=null,$quiz_id=null){

    if(!empty($_REQUEST)){
        $uid=isset($_REQUEST['user_id'])?$_REQUEST['user_id']:null;
        $exam_id=isset($_REQUEST['exam_id'])?$_REQUEST['exam_id']:null;
         $quiz_id=isset($_REQUEST['quiz_id'])?$_REQUEST['quiz_id']:null;
    }

    
    if($uid!=null){
        $UserQuizResponses = TableRegistry::get('UserQuizResponses') ;           

       if($exam_id=='null' && $quiz_id=='null'){ 
              

           $results= $UserQuizResponses->find('all')->where(['user_id' => $uid]);
            $rowcount=$results->count();        

               if($rowcount>0){
                  $lastrow=$results->last();
                  $exam_id=$lastrow->exam_id;
                  $quiz_id=$lastrow->user_quiz_id;
               }
               else{
                  $exam_id=0;
                  $quiz_id=0;
               }
        }

  

        $userQuizResults = $UserQuizResponses->find()->where(['user_id' => $uid,'exam_id'=>$exam_id,'user_quiz_id'=>$quiz_id]);
        $numRecords=$userQuizResults->count();



        $correct_count=0;
        $wrong_count=0;
        $exam_marks=0;
        $student_score=0;

        if($numRecords>0){
            foreach ($userQuizResults as $qresult) {
              
                if($qresult['correct']==0)
                    { $wrong_count++;  }              
                else
                   { $correct_count++; }

                       

                //$data['results'][]=$qresult;                
                $exam_marks= $exam_marks+($qresult['item_marks']);
                $student_score=$student_score+($qresult['score']);

                     // pr($qresult['correct']);


            } 

           
            $data['status']="true";
            $data['correct_questions']=$correct_count;
            $data['wrong_questions']=$wrong_count;
            $data['exam_marks']=$exam_marks;
            $data['student_score']=$student_score;
            $data['student_result']=(int)(($student_score/$exam_marks)*(100));

                    


        }else{
            $data['message']="No record Found";
            $data['status']="false";
        }
    }
    else{
      $data['message']="UID and exam_id is null.";
      $data['status']='false';
    }

    $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));

  }




  // The external used API 
  public function getManifestQuestions(){
    
     $curl_response = curlPost('google.com',['username' => 'admin','password' => '12345', ]);




  }





function curlPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}


/* how can call

curlPost('google.com', [
    'username' => 'admin',
    'password' => '12345',
]);
*/



}// end of class


