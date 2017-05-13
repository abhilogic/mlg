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



  // The external used API - to get the Question List API
  public function getMenifestQuestions($course_id=null, $grade_id=null,$standrd=null,$limit= -1 ){
    
     /*$curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]);*/

     $vendor_authenticate =$this->externalUsersAuthVerification() ;

    
     if($vendor_authenticate['status']=="true"){
        $data['status'] = true;
        $data['authenticate'] ='Yes';       
        $data['message'] = $vendor_authenticate['message'];
        $course_ids=array();


        $user_id = isset($vendor_authenticate['key'] ) ? $vendor_authenticate['key'] :null;


        // to get the question list
         $course_id = isset($_REQUEST['course']) ? $_REQUEST['course'] : $course_id;
         $grade_id = isset($_REQUEST['grade']) ? $_REQUEST['grade'] : $grade_id;      
         $course_ids[]=$course_id;

         $standard=isset($_REQUEST['standard'])?$_REQUEST['standard']: $standrd;
       
        $skills=isset($_REQUEST['skills'])?$_REQUEST['skills']:NULL;
        $target=isset($_REQUEST['target'])?$_REQUEST['target']:NULL;
        $country=isset($_REQUEST['country'])?$_REQUEST['country']:NULL;
        $dok=isset($_REQUEST['dok'])?$_REQUEST['dok']:NULL;
        $difficulty=isset($_REQUEST['difficulty'])? $_REQUEST['difficulty']:NULL;
        $type=isset($_REQUEST['type'])?$_REQUEST['type']:NULL;
        $limit=isset($_REQUEST['limit'])?$_REQUEST['limit']:$limit;



        if(!empty($course_id) && !empty($grade_id) && !empty($standard) ){

            //check shared course id is subject/skill/subskill 
             $connection = ConnectionManager::get('default');         
             $sql = "SELECT c.level_id,c.course_code,c.course_name,cd.course_id,cd.parent_id
                   FROM  courses as c, course_details as cd
                   WHERE c.id=cd.course_id AND cd.parent_id=$course_id AND c.level_id=$grade_id";


              $skillRecords = $connection->execute($sql)->fetchAll('assoc');              
              if($skillRecords > 0){ 
               // $data['status'] = "true";              
                foreach ($skillRecords as $skillRecord) {                   
                    $course_ids[]=$skillRecord['course_id'];

                    $subskill_qry = "SELECT c.level_id,c.course_code,c.course_name,cd.course_id,cd.parent_id
                                     FROM  courses as c, course_details as cd 
                                  WHERE c.id=cd.course_id AND cd.parent_id=".$skillRecord['course_id']." AND c.level_id=$grade_id";

                    $subskillRecords = $connection->execute($subskill_qry)->fetchAll('assoc');
                    if($subskillRecords > 0){
                        foreach ($subskillRecords as $subskillRecord) {
                            $course_ids[]=$subskillRecord['course_id'];
                        }

                        //$data['courseIds'] = $course_ids;
                       // $data['grade_id'] = $grade_id;
                        $data['status']="true";                      

                        
                     }
                }
                $subj = $course_ids;
                $grade = $grade_id;
                
             
                $data['questions']=$this->getQuestionsList($subj,$grade,$standard,$limit,$skills,$target,$dok,$difficulty,$type,$user_id);
              
              }
              else{
                  $data['message']="No Records Found"; // No skill for courses
                  $data['status']="false";
              }
            
        }else{
          $data['message'] = "Either Course, Grade or Standard is missing or set wrong value for these";
          $data['status']="false";
        }       
     }else{
        $data['status']="false";
        $data['message']=$vendor_authenticate['message'];

     }

      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));
     
  }



    public function createQuiz($limit=null,$itemsIds=array(),$quiz_marks=null,$user_id=null){
        if(!empty($itemsIds) && !empty($limit) && !empty($quiz_marks) ){
            $date=date("Y-m-d H:i:s");    
            $epoch=date("YmdHis");
            $quiz_info['name'] = "external-".$epoch;
            $quiz_info['is_graded'] = 1;
            $quiz_info['is_time'] = 1;
            $quiz_info['max_marks'] = $quiz_marks;
            $quiz_info['max_questions'] = count($itemsIds);
            $quiz_info['duration'] = '1'; 
            $quiz_info['status'] = 1;
            $quiz_info['created_by'] = $user_id;
            $quiz_info['created'] = time();
            $quiz_info['modified'] = time();


            $Quizes=TableRegistry::get('Quizes');
            $new_quiz = $Quizes->newEntity($quiz_info);
            if ($qresult= $Quizes->save($new_quiz) ) {
                $quiz_item['exam_id']  = $qresult->id;
                $quiz_item['created']  = time();
                $quiz_item['status']  = 1;

                foreach ($itemsIds as $key => $value) {
                   $quiz_item['item_id']  = $value;

                    $QuizItems=TableRegistry::get('QuizItems');
                    $new_quizitem = $QuizItems->newEntity($quiz_item);
                    if ($qitemresult= $QuizItems->save($new_quizitem) ) {
                       $data['status'] = "true";
                       $data['quiz_id'] =  $qresult->id;
                      $data ['message'] ="quiz is created.";
                    }
                    else{
                        $data['status'] = "false";
                        $data ['message'] ="Not able to create quiz item. Please consult with admin";
                    }
                 }             
            }
            else{
                $data['status'] = "false";
                $data ['message'] ="Not able to create quiz. Please consult with admin";
              }      

        }
        else{
          $data['status'] = "false";
          $data ['message'] ="Not able to create quiz. Please consult with admin";
        }

        return($data);      

    }

    public function getQuestionsList($subj,$grade,$standard,$limit,$skills,$target,$dok,$difficulty,$type,$user_id){

      $subj= '('.implode(',', $subj).')';
      $connection = ConnectionManager::get('default');     
      

      $sql = 'SELECT  distinct qm.id, type, qm.grade,qm.subject,qm.standard, qm.docId, qm.uniqueId, questionName,  qm.level,
                 mimeType, paragraph, item,Claim,Domain,Target,`CCSS-MC`,`CCSS-MP`,cm.state, GUID,ParentGUID, AuthorityGUID, Document, Label, Number,Description,Year, createdDate
              FROM mlg.question_master AS qm
              JOIN mlg.header_master AS hm ON hm.uniqueId = qm.docId and hm.headerId=qm.headerId
              LEFT JOIN mlg.mime_master AS mm ON mm.uniqueId = qm.uniqueId
              LEFT JOIN mlg.paragraph_master as pm on pm.question_id=qm.docId
              JOIN  mlg.compliance_master as cm on (cm.Subject=qm.subject OR cm.grade=qm.grade)
              where qm.course_id IN '.$subj.' and qm.grade_id="'.$grade.'"  ';


           if($standard !== NULL){
                $standard=explode("|",$standard);
                pr($standard);
                $sql.=" and `CCSS-MC` in (";
                $countArray=0;
                foreach($standard as $std){
                    ++$countArray;
                    $sql.="'".$std."' ";
                    if(!empty($standard[$countArray])){ $sql.=",";  }
                }
          
              $sql.=")";
         }      

        if($difficulty !== NULL){
          
            $difficulty=explode("|",$difficulty);
           $sql.=" and qm.level in (";
            $countArray=0;
            foreach($difficulty as $level):
                ++$countArray;
                $sql.="'".$level."' ";
                if(!empty($difficulty[$countArray])){
                   $sql.=",";
                }
          endforeach;
          
          $sql.=")";
        }

        if($type !== NULL){
          $type=explode("|",$type);
          $sql.=" and qm.type in (";
          $countArray=0;
          foreach($type as $typos):
            ++$countArray;
            $sql.="'".$typos."' ";
            if(!empty($type[$countArray])){
              $sql.=",";
            }
          endforeach;
          
          $sql.=")";
        }

        //if($skills !== NULL){ $sql.=" and skills = '".$skills."'";  }
        if($target !== NULL){ $sql.=" and target ='".$target."'";   }
        if($dok !== NULL){    $sql.=" and hm.DOK ='".$dok."'";      }

        if($limit !== -1){ $sql.="ORDER BY RAND() limit ".$limit; }

        $question_info=array();
        $quiz_marks = 0;
        $ques_ids = array();
         $questionRecords = $connection->execute($sql)->fetchAll('assoc');              
              if($questionRecords){ 
                $data['status'] = "true";                             
                foreach ($questionRecords as $questionRow) {                  
                   $ques_ids[] = $questionRow ['id']; 
                   
                   foreach ($questionRow as $key => $value) {                    
                     $question_info[$key] = $value;
                   }
                   $question_info['id'] = 'response_id-'.$questionRow['id'];                   
                   $question_info['questionName']=$questionRow['questionName'];                  


                   // Find option to question
                   $option_sql = "SELECT * FROM mlg.option_master WHERE uniqueId ='".$questionRow['uniqueId']."'";
                   $optionRecords = $connection->execute($option_sql)->fetchAll('assoc');
                   if($optionRecords > 0){ 
                      foreach ($optionRecords as $optionRow) {
                        $optionArray[]=array('value'=>$optionRow['options'],'label'=>$optionRow['options']);
                      }
                      $question_info['options'] =  $optionArray; 
                      $optionArray =[];                         
                   }else{
                    $question_info ['option_message'] = "No option Found for this question";
                   }


                   // Find Answers for a question
                   $answer_sql = "SELECT * FROM answer_master WHERE uniqueId ='".$questionRow['uniqueId']."'";
                   $answerRecords = $connection->execute($answer_sql)->fetchAll('assoc');
                   if($answerRecords > 0){ 
                      foreach ($answerRecords as $answerRow) {
                        $answerArray[]=array('value'=>$answerRow['answers'],'score'=>1);
                        $quiz_marks = $quiz_marks +1;
                      } 
                      $question_info['answers'] =  $answerArray; 
                      $answerArray =[];                   
                   }else{
                    $question_info ['answer_message'] = "No Answer Found for this question";
                   }


                   //Question Collections
                   $questions[]=$question_info;           


                 }      
           
                  // Create Quiz                
                   $quiz= $this->createQuiz($limit,$ques_ids,$quiz_marks,$user_id);
                   if($quiz['status']=="true"){
                      $quiz_id =$quiz['quiz_id'];
                   }else{
                      $data['quiz_status'] = $quiz;
                   } 

                   // Result
                   foreach ($questions as $ques) {
                      $questions_detail['quiz_id'] = $quiz_id;                     
                      $data[] = array_merge($questions_detail, $ques);                      
                   }                  

              }else{
                $data['status'] = "false";
                $data['message'] = "No Record Found";
              }



             /* $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));*/
//pr($data);
      return ($data);


    }



// API - Pass either username/plateform

public function externalUsersAuthVerification(){
   

         $plateform_name= isset($_GET['plateform']) ? $_GET['plateform']: null;
         $username =isset($_GET['username']) ? $_GET['username'] : null;
         $access_token=isset($_GET['token'])? $_GET['token'] :null; 
        
        if($plateform_name!=null && $access_token!=null){ 
          $users =TableRegistry::get('vendors')->find('all')->where(['plateform'=>$plateform_name,'access_token'=>$access_token]);

              if($users->count() ){          
                  foreach ($users as $vrow) {                     
                    $data['status']="true"; 
                    $data['vendor_key'] = $vrow['id'];
                    $data['message']="You are authenticated";                    
                }
                
              }
              else{
                  $data['status']="false";
                  $data['message']="either Plateform or password is wrong;";
              }

        }
        // users of the vendor
        elseif($username!=null && $access_token!=null){
         
            $ExternalUsers =TableRegistry::get('external_users');
            $exusers= $ExternalUsers->find('all')->where(['username'=> $username, 'access_token'=>$access_token]);

              if($exusers->count() > 0 ){          
                  foreach ($exusers as $vrow) {                     
                    $data['status']="true";
                    //$data['key'] = $vrow['id']; 
                    $data['message']="You are authenticated"; 

                    // get user id in users table
                  $external_userid = $vrow['id'];
                    $udetails = TableRegistry::get('UserDetails')->find()->where(['external_user_id' => $external_userid]);

                    if($udetails->count() > 0){
                            foreach ($udetails as $user_detail) {                     
                                  $data['key'] = $user_detail->user_id;                                                 
                            }           
                          
                      }else{
                          $data['message']="No record Found in external user";
                          $data['status']="false";
                          $user_id = null ;
                      }                   
                }              
                
              }
              else{                
                    
                   $user_vendor =TableRegistry::get('vendors')->find('all')->where(['access_token'=>$access_token]);
                   if($user_vendor->count() >0 ){
                      foreach ($user_vendor as $vendor) { 
                          $extUser['vendor_id'] = $vendor->id;                    
                          $extUser['vendor_name']=$vendor->plateform;
                          $extUser['access_token']=$access_token;                          
                          $extUser['username'] = $username ;
                          $extUser['first_name'] = $username ; 
                          $extUser['status'] = 1;
                          $extUser['created'] = time();
                          $extUser['modfied'] = time();                                                           
                      }

                      // Insert/add Records in External Users                      
                      $new_ExternalUsers = $ExternalUsers->newEntity($extUser);
                      if ($exSavedUser= $ExternalUsers->save($new_ExternalUsers)) {
                          $extUser['external_user_id']  = $exSavedUser->id;                          
                          
                          //save data in users
                          $Users = TableRegistry::get('Users');
                          $new_Users = $Users->newEntity($extUser);
                          if ($userSaved = $Users->save($new_Users)) {
                              $extUser['user_id']  = $userSaved->id;

                              $user_details=TableRegistry::get('UserDetails');
                              $new_user_details = $user_details->newEntity($extUser);
                              if ($user_details->save($new_user_details)) {
                                  $data['status']="true";
                                  $data['key'] = $userSaved->id; 
                                  $data['message']="We have save Record to authenticate you";
                              }
                              else{
                                  $data['status']="false"; 
                                  $data['message']="Opps... Record is not inserted--User Details. Please Try Again";  //  User Details Table
                              }
                          }
                          else{
                            $data['status']="false"; 
                            $data['message']="Opps... Record is not inserted--User . Please Try Again";  // Users Table

                          }

                      }else{
                          $data['status']="false"; 
                          $data['message']="Opps... Record is not inserted--External Table. Please Try Again";  // External Table 
                      }
 
                   }else{
                        $data['status']="false"; 
                        $data['message']="Wrong Access Token. Please choose valid"; 
                   } 
             }   
          
     
      }else{        
        $data['status']="false";
        $data['message']="either plateform or token is missing. please check";

      }

       return($data);    

  }



// External API to get the user Quiz Response
  public function getMenifestQuizResponse($username=null, $quiz_id=null){

      $vendor_authenticate =$this->externalUsersAuthVerification() ; 
      if($vendor_authenticate['status']="true"){
          //$data['status']="true";
          $data['message'] = $vendor_authenticate['message'] ;
          $user_id = isset( $vendor_authenticate['key']) ? $vendor_authenticate['key'] : 0;

           $quiz_id=isset($_REQUEST['quiz_id'])?$_REQUEST['quiz_id']:$quiz_id;

          if(!empty($user_id) && !empty($quiz_id) ){

              $data['status']="true";
              $UserQuizResponses = TableRegistry::get('UserQuizResponses') ;         
              $userQuizResults = $UserQuizResponses->find()->where(['user_id' => $user_id,'exam_id'=>$quiz_id]);
              $numRecords=$userQuizResults->count();


              if($numRecords>0){
                  foreach ($userQuizResults as $qresult) {                     
                      $data['results'][]=$qresult;                     
                  }           
                
            }else{
                $data['message']="No record Found for quiz response";
                $data['status']="false";
            }            
           }
          else{
            $data['status']="false";
           $data['message'] = "No record exits on username , token and quiz_id" ;
          }

      }else{
          $data['status']="false";
          $data['message'] = $vendor_authenticate['message'] ;

      }
      
  

      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));


  }


  //ApI to get vendor students
  public function getVendorUsers($plateform=null, $token=null){
      
      $vendor_authenticate =$this->externalUsersAuthVerification() ;
      if($vendor_authenticate['status']="true"){
          $data['status']="true";
          $data['message'] = $vendor_authenticate['message'] ;
          $vendor_id = isset( $vendor_authenticate['vendor_key']) ? $vendor_authenticate['vendor_key'] : null; 

          if(!empty($vendor_id) ){

            $Vendors = TableRegistry::get('ExternalUsers')->find()->where(['vendor_id' => $vendor_id]);         
            $numRecords=$Vendors->count();

              if($numRecords>0){
                  foreach ($Vendors as $Vendor) {                     
                      $data['vendor_users'][]=$Vendor;                     
                  } 
                }

          }else{
              $data['status']="false";
              $data['message'] = 'Plateform id is not valid' ;
          }
      }
      else{
          $data['status']="false";
          $data['message'] = $vendor_authenticate['message'] ;
      }


      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));



  }



// API to call curl 
  /*way of calling $curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]); */
public function curlPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}






}// end of class


