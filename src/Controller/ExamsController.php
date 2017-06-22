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


public function createQuizOnStudent($grade_id=null, $subskill_id=null,$user_id=null,$quiz_type_id=0,$questions_limit=10){

  // Step-1 auto generate 15 questions
  
  $base_url = Router::url('/', true);
  $grade_id = isset($this->request->data['grade_id'])? $this->request->data['grade_id'] : grade_id ;  
  $subskill_id = isset($this->request->data['subskill_id']) ? $this->request->data['subskill_id']: $subskill_id;

    $questions_limit = isset($this->request->data['questions_limit']) ? $this->request->data['questions_limit']: $questions_limit;

    $quiz_type_id = isset($this->request->data['quiz_type_id']) ? $this->request->data['quiz_type_id']: $quiz_type_id;

    $default_quiz_name ='mlg'.date("YmdHis");
    $quiz_name = isset($this->request->data['quiz_name']) ? $this->request->data['quiz_name']: $default_quiz_name;

   $difficulty_level = 'Easy|Moderate|Difficult';
  $user_id = isset($this->request->data['user_id'] )? $this->request->data['user_id'] :$user_id ;   

  $dataToGetQuestions['subjects'] = $subskill_id; // ids of course as eg 3,13,15
  $dataToGetQuestions['user_id'] = $user_id;
  $dataToGetQuestions['grade_id'] = $grade_id;
  $dataToGetQuestions['limit'] = $questions_limit;
  $dataToGetQuestions['quiz_type_id'] = $quiz_type_id;
  $dataToGetQuestions['quiz_name'] = $quiz_name;
  $dataToGetQuestions['difficulty'] = 'Moderate|Easy|Difficult'; // eg Easy|Difficult|mod

  $json_questionslist = $this->curlPost($base_url . 'students/getQuestionsList/', $dataToGetQuestions); 

    $array_qlist = (array) json_decode($json_questionslist);
   
    if( isset($array_qlist['response']) ){
        if ($array_qlist['response']->status == "True") {
            $data['status'] = True;
            $data['questions'] = $array_qlist['response']->questions;
        } else {
              $data['status'] = $array_qlist['response']->status;
              $data['message'] = $array_qlist['response']->message;
            }

    }else{
      $data ['status'] = "False";
      $data ['message'] = "Opps... No question get.";
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
        $attamp_questions=1;
        $skip_count=0; 
        $quiz_questions = 1;
       $attendQuizResponses= $this->request->data;


         // Array traverse to get User Quiz Result
        foreach ($attendQuizResponses as $attendQuizResponse) {
              $uid=$attendQuizResponse['user_id'];
              $eid=$attendQuizResponse['exam_id'];
              $quiz_marks=$quiz_marks+$attendQuizResponse['item_marks'];
              $student_score=($student_score)+($attendQuizResponse['score']);
              $quiz_questions=$quiz_questions+1;
             if($attendQuizResponse['skip_count']==1){ $skip_count++; }
             else{ $attamp_questions++;  }
             $quiz_type_id=$attendQuizResponse['quiz_type_id'];
             $grade_id=$attendQuizResponse['grade_id'];
             $course_id=$attendQuizResponse['course_id'];                               
        }
       

       // add Student Quiz Result in user_quiz table
      if(!empty($quiz_marks) ){         

        $quiz_result= ($student_score*100)/($quiz_marks);
        //echo QUIZ_PASS_SCORE ; //constant 80%
        if($quiz_result < QUIZ_PASS_SCORE){ 
          $pass=0;
          $points = 0;
        }
        else{ 
          $pass=1;
          $points = $quiz_result ;
        }

        $userQuizes = TableRegistry::get('UserQuizes');
        $new_userQuizes = $userQuizes->newEntity(array('user_id'=>$uid, 'exam_id'=>$eid,'quiz_type_id'=>$quiz_type_id, 'grade_id'=>$grade_id, 'course_id'=> $course_id,'exam_marks'=>$quiz_marks,'quiz_questions' =>$quiz_questions,'attempts' =>$attamp_questions,'created' => time(),'score'=>$student_score,'status'=>1, 'pass'=>$pass ) );
            
              if ($result=$userQuizes->save($new_userQuizes)) { 
                $postdata['user_quiz_id']  = $result->id;               
                $data['message']="Successfull data is save.";
                $data['status']="true";

                  //1. Add each question attamp response in user_quiz_response 
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
                      if(isset($itemid[1])){ $postdata['item_id']=$itemid[1]; }
                      else{ $postdata['item_id']=$item_id;  }
                      

                      $new_userQuizResponse = $userQuizResponses->newEntity($postdata);
                      if ($userQuizResponses->save($new_userQuizResponse)) {
                        $ures['message']="add data in user responnse table";
                        $ures['quiz_attampt']=$postdata['user_quiz_id'];
                        $ures['status']="true";
                      }
                      else{
                          $ures['message']="No data add in user quiz response table";
                          $ures['quiz_attampt']=$postdata['user_quiz_id'];
                          $ures['status']="false";
                       }

                       $uquiz_response['saveresult'][] =  $ures;
                       $data ['status'] ="true";
                       $data['quiz_attempt_id'] =$postdata['user_quiz_id'];
                  }

                  //2. save data in user points
                  if($points==1){
                    $userPoints = TableRegistry::get('UserPoints');
                    $new_userPoints= $userPoints->newEntity(array('user_id'=>$uid, 'quiz_id'=>$eid ,'point_type_id'=>7 ,'points' =>$points, 'status'=>1,'created_date'=>time() ) );
                      if ($resultpoints=$userPoints->save($new_userPoints)) {
                          $data['status'] ="true";
                          $data['message']= " Quiz records save.";
                      }
                      else{
                          $data['status'] = "false";
                          $data['message']= " Quiz records does not save.";
                      }
                  }                 

              }else{
                  $data['message']="opps data is not saved in user quiz table";
                  $data['status']="false";
              }

            }else{
              $data['message'] ="Issue in calculating marks.";
              $data['status'] = "false";
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
public function getUserQuizResponse($uid=null,$quiz_id=null,$user_quiz_id=null, $quiz_type_id=null,$course_id=null){
    
    $uid=isset($_REQUEST['user_id'])?$_REQUEST['user_id']:$uid;
    $quiz_id=isset($_REQUEST['quiz_id'])?$_REQUEST['quiz_id']:$quiz_id;
    $user_quiz_id=isset($_REQUEST['user_quiz_id'])?$_REQUEST['user_quiz_id']: $user_quiz_id;    
    $quiz_type_id=isset($_REQUEST['quiz_type_id'])?$_REQUEST['quiz_type_id']: $quiz_type_id;
    $course_id=isset($_REQUEST['course_id'])?$_REQUEST['course_id']: $course_id;    

    if(!empty($uid)){
        $UserQuizes = TableRegistry::get('UserQuizes') ;           

        if( !empty($quiz_id) && !empty($user_quiz_id)){          

          $results= $UserQuizes->find('all')->where(['id'=>$user_quiz_id, 'user_id' => $uid, 'exam_id'=>$quiz_id])->order(['id'=>'DESC'])->limit(1);
        }
        elseif( !empty($quiz_type_id) && !empty($course_id) ){
           $results= $UserQuizes->find('all')->where(['user_id' => $uid, 'quiz_type_id'=>$quiz_type_id,'course_id'=>$course_id])->order(['id'=>'DESC'])->limit(1);

        }else{
            $data['status'] = False;
            $data['message'] ="either set quiz_id & user_quiz_id OR set quiz_type_id & course_id";

            //$results= $UserQuizes->find('all')->where(['user_id' =>0]);
        }
           
          if(isset($results)){  
            if($results->count() > 0){                 
              foreach ($results as $result) {               
                $data['status']= True;  
                $data['user_quiz_id'] = $result['id'];  
                $data['grade_id']=$result['grade_id'];
                $data['course_id']=$result['course_id'];                                       
                $data['exam_marks']=$result['exam_marks'];
                $data['student_score']=$result['score'];
                    
                if($result['exam_marks']!=0){
                   $data['student_result_percent']=(int)( ($result['score']/$result['exam_marks'])*(100));
                }else{ 
                    $data['student_result_percent'] = 0;
                    $data['message'] = "Either quiz is not started or quiz attempted incomplete.";
                  }                  
              }               
            }
            else{
                $data['status'] = False;
                $data['message'] = "No result found.";
            }
      }
    }else{
      $data['status'] = False;
      $data['message'] = "you are not logged-in. OR user_id is not set.";
    }  

    $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));

  }
 




// Function for get the corect answer
  public function getAnsForExam() {
    try {
      $status = FALSE;
      $message = '';
      if($this->request->is('post')) {
      $corr_ans = array();
      if(empty($this->request->data['user_response'])){
        $message = 'please select a answer';
      }
      if($message == '') {
       $question = TableRegistry::get('question_master');
      $answer = TableRegistry::get('answer_master');
      $user_quiz = TableRegistry::get('user_quiz_responses');
      $ques = $question->find('all')->where(['id' => $this->request->data['question_id']]);
      foreach ($ques as $key => $value) {
       $unique_id = $value['uniqueId'];
      }
      $correct_answer = $answer->find('all')->where(['uniqueId' => $unique_id]);
      $corr_ans_count = 0;
      foreach ($correct_answer as $key => $value) {
        $id[] = $value['id'];
        $corr_ans[] = $value['answers'];
        $corr_ans_count++;
      }
      $correct_count = 0; // for check the count the given ans of user are same as actual answer.
      $user_response = $this->request->data['user_response'];
      $count_user_response = count($user_response);
      $response = $user_quiz->newEntity();
      $response->id = '';
      $response->user_id = $this->request->data['user_id'];
      $response->exam_id = $this->request->data['exam_id'];
      $response->item_id = $this->request->data['question_id'];
      foreach ($user_response as $key => $value) {
        foreach ($corr_ans as $ki => $val) {
          if(strcmp($val, $value['value'])) {
            $correct_count++;
          }
        }
        if($key == 0){
          $user_res = $value['value'];
        }else {
          $user_res = $user_res.':'.$value['value'];
        }
      }
      if($correct_count == $corr_ans_count) {
        $response->score= 1;
        $response->correct= 1;
      }else{
        $response->score= 0;
        $response->correct = 0;
      }
      $response->response = $user_res;
      if($user_quiz->save($response)) {
        $status = TRUE;
        $message = 'data saved successfully.';
      }
      }
      }
      
    } catch (Exception $exc) {
      echo $exc->getTraceAsString();
    }
    $this->set(array(
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status','message']
      ));
  }
  public function getQuizUserId($user_name='') {
    try {
      $id = '';
      $user_id = '';
      $message = '';
      $status = FALSE;
      if($user_name == '') {
        $message = 'please fill the user name';
      }  else {
        $user_quiz = TableRegistry::get('external_users');
        $result = $user_quiz->find()->where(['username' => $user_name]);
        foreach ($result as $key => $value) {
          $id = $value['id'];
        }
        $user_detail = TableRegistry::get('user_details');
        $res = $user_detail->find()->where(['external_user_id'=>$id]);
        foreach ($res as $ki => $val) {
          $user_id = $val['user_id'];
        }
        if($user_id == ''){
          $message = 'user not exist.';
        }  else {
          $status = TRUE;
        }
      }       
    } catch (Exception $exc) {
      echo $exc->getTraceAsString();
    }
    $this->set(array(
        'user_id' => $user_id,
         'message' => $message,
        '_serialize' => ['user_id','message']
    ));
  }


//API to check Knight challenge is enabled/disabled
  public function checkKnightQuizStatus($skill_id=null, $user_id=null){

      $skill_id = isset($_REQUEST['skill_id'])? $_REQUEST['skill_id'] : $skill_id;
      $user_id= isset($_REQUEST['user_id'])? $_REQUEST['user_id'] : $user_id;
     
      //Query - how can prevent to send a assignment by teacher/parent if student mastered.
      if(!empty($skill_id) && !empty($user_id) ){
       echo   $getsubskill_str = "SELECT cr.id as subskill_id, cr.course_name FROM courses as cr, course_details  as cd WHERE cr.id=cd.course_id AND parent_id=$skill_id ORDER BY cr.id ASC";

          $connection = ConnectionManager::get('default');
          $subskills_results = $connection->execute($getsubskill_str)->fetchAll('assoc');

          if(count($subskills_results) > 0){
              foreach ($subskills_results as $row) {
                  $course_id = $row['subskill_id'];
                  
                  //check any quiz on this subskill
                  $userquiz_results= TableRegistry::get('UserQuizes')->find('all')->where(['course_id'=>$row['subskill_id'], 'user_id'=> $user_id])->order(['id'=>'ASC']);

                  if($userquiz_results->count()>0){                    
                      foreach ($userquiz_results as $stquizrow) { 
                      $data['test'][] = $stquizrow;                    
                          if( $stquizrow['pass'] ==1 ){
                            $stresult[$course_id] ='pass';    // will check pass in subskill quiz + any challenges                          
                          }else{
                            $stresult[$course_id] ='fail';
                          }
                              
                         $student_result[]=$stresult;  // array of pass/fail at subskill.                           
                      }

                      $data['status'] =True;
                      if(in_array('fail', $stresult)){
                         $data['KnightQuiz_status'] ="disable";
                      }else{
                          $data['KnightQuiz_status'] ="enable";
                      }
                  }
                  else{
                    $data['status'] =False;
                    $data['message'] = "No quiz is attend by student for subskill ".$row['course_name'];
                  }



                    
              } 

          }
          else{
            $data ['status'] =False;
            $data['message'] = "No Subskill added on this skill.";
          }
          
        





      }else{
        $data['status'] = False;
        $data['message'] ="please set skill_id and user_id";
      }

       $this->set([
          'response' => $data,
          '_serialize' => ['response']
      ]);

  }


public function curlPost($url, $data = array()) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      //$headers[] = "Content-Type: application/json";
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);     
      $response = curl_exec($ch);
      curl_close($ch);
    return $response;
  }
  


}// end of class


