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

class StudentsController extends AppController {
  
  public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
       // $connection = ConnectionManager::get('default');
         $this->RequestHandler->renderAs($this, 'json');
  }
  

  public function getStudentCourses($uid){
    try{
        $connection = ConnectionManager::get('default');
       // $users=TableRegistry::get('Users')->find('all')->contain(['UserCourses','Courses']);
      $sql ="SELECT * FROM courses as cr
                INNER JOIN user_courses as uc ON cr.id = uc.course_id 
                INNER JOIN users as u ON u.id=uc.user_id 
                INNER JOIN levels as l ON l.id = cr.level_id 
                WHERE uc.user_id =$uid";
      $users = $connection->execute($sql)->fetchAll('assoc');
       if (!empty($users)) {
          foreach($users as $user){
                $user_courses['id']=$user['course_id'];  
                $user_courses['course_name']=$user['course_name'];
                $data['student_courses'][]= $user_courses;           
                $data['student_class']=$user['level_id'];
                $data['student_class_name']=$user['name'];
                $data['student_grade_id']=$user['level_id'];
           }
          //$data['student_courses']=$user_courses;
          $data['status']="TRUE";
       }
       else{
            $data['status']="FALSE";
            //throw new Exception("Subscribe Us to purchase the courses for your child");
       }   
    }
    catch (Exception $ex) {
      $this->logs('Error in setTeacherRecord function in Teachers Controller'
              . $e->getMessage().'(' . __METHOD__ . ')');
    }
      $this->set([
          'response' => $data,
          '_serialize' => ['response']
   ]);

  }




public function getStudentAssignments($user_id = null, $subject_id=null){
      $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $user_id ;
      if(!empty($user_id)){
            $connection = ConnectionManager::get('default');
            $base_url = Router::url('/', true);

            // find all subskill of subject on which assignments list need to findout
            $crObj = new CoursesController();
            $courses_info= $crObj->getSkillListOfSubject($subject_id,2,2);
            
            $subskill_ids = array();
            foreach ($courses_info as $coursesinfo) {                     
                if($coursesinfo['status']==1){
                    $subskill_ids = array_merge($subskill_ids, $coursesinfo['childcourse_ids'] );
                }
            }
           $subskillids = implode(',', $subskill_ids);

          if(!empty($subskill_ids)){
              // To find user assignment
              $sql ="SELECT
                adetails.id as assignment_id, adetails.grade_id, adetails.course_id, cr.course_name, group_id, student_id,assignment_for, schedule_time, adetails.comments,

                qz.id as quiz_id, qz.name as quiz_name, qz.quiz_type_id, max_marks, max_questions, qz.modified as created, qt.name as type, qt.id as type_id       
                
                from assignment_details as  adetails "                
                  ." INNER JOIN quizes as qz ON qz.id = adetails.quiz_id " 
                  ." INNER JOIN courses as cr ON  cr.id = adetails.course_id "
                  ." INNER JOIN quiz_types as qt ON  qt.id = qz.quiz_type_id "          
                  . " WHERE FIND_IN_SET(".$user_id." , student_id) AND adetails.course_id IN ($subskillids)" ;                  
            
              $results = $connection->execute($sql)->fetchAll('assoc');
              // print_r($result);
              $count = count($results);
              $data['counts'] = $count;
              if($count > 0){
                  foreach ($results as $result) {
                   // $assg['items'] = $result;
                    $assg['assignment_id'] =$result['assignment_id'];                   
                    $assg['quiz_id'] = $result['quiz_id']; 
                    $assg['type']   = $result['type']; 
                    $assg['type_id']   = $result['type_id']; 
                    $assg['student_id'] = $result['student_id'];
                    $assg['quiz_name'] = $result['quiz_name']; 
                    $assg['max_marks'] = $result['max_marks']; 
                    $assg['max_questions'] = $result['max_questions']; 
                    $assg['created'] = $result['created']; 
                    $assg['grade_id'] = $result['grade_id'];  
                    $assg['course_id'] = $result['course_id'];
                    $assg['course_name'] = $result['course_name'];


                    // to get subject/class , skill subskill name of a subject
                    $json_courseinfo = $this->curlPost($base_url.'courses/getCourseInfo/'.$result['course_id'], array() );                  
                   $array_courseinfo = (array)json_decode($json_courseinfo);
                   if(isset($array_courseinfo['response'])) {
                        if(isset($array_courseinfo['response']->parent_info_of_skill)){
                          $assg['subject_id'] = $array_courseinfo['response']->parent_info_of_skill->id;
                          $assg['subject_name'] = $array_courseinfo['response']->parent_info_of_skill->course_name;
                          $assg['class_name'] = $array_courseinfo['response']->parent_info_of_skill->grade_name;
                        }
                    }else{
                      $assg['subject_id'] = 'issue/not found';
                    }


                  // get score if user has given challenges
                  $userchallenge_str = "SELECT * FROM user_quizes where user_id=$user_id AND exam_id=".$assg['quiz_id']." ORDER BY id DESC LIMIT 0,1 "; 
                  $userchallenge_results = $connection->execute($userchallenge_str)->fetchAll('assoc');               
                  if(count($userchallenge_results) > 0){
                      foreach ($userchallenge_results as $ch_result) {
                          if($ch_result['exam_marks']==0){
                              $ch_result['score']=0 ; // to avoid infinite result exception
                          }
                          $st_res= ($ch_result['score'] / $ch_result['exam_marks'])*100;
                          $assg['student_result_percent'] = round($st_res,2);
                          $assg['has_attempted_challenge'] = 1;  //1=Yes
                      }                      
                  }else{
                          $assg['has_attempted_challenge'] = 0;  //0=No
                          $assg['student_result_percent'] =0;
                      } 

                $data['assignment'][]= $assg;
              } 
                 $data['status'] =True;            
            }
            else{
                $data['status'] =False;
                $data['message'] ="No data found";
            }
          }else{
              $data['status'] =False;
              $data['message'] ="No child courses are found.";
          }            
        }else{
          $data['status'] =False;
          $data['message'] ="user_id cannot null.";
      }
    $this->set([
            'response' => $data,
            '_serialize' => ['response']
     ]);


}

public function getAssignmentItems($assignment_id = null){
    $assignment_id = isset($_GET['assignment_id']) ? $_GET['assignment_id'] : $assignment_id ;

    if(!empty($assignment_id)){
        // To find items of quiz/assignment            
       $sql ="SELECT
             qz.id as quiz_id, qz.name as quiz_name, qz.max_marks, qz.max_questions, qz.modified as created, qitem.item_id as question_id, adetails.id as assignment_id, adetails.grade_id, adetails.course_id, adetails.comments,  adetails.group_id, adetails.student_id,adetails.assignment_for, adetails.schedule_time ,cr.course_name,qm.*       
           from assignment_details as adetails" 
                ." INNER JOIN quizes as qz ON qz.id = adetails.quiz_id "              
               . " INNER JOIN quiz_items as qitem ON qz.id = qitem.exam_id "          
                ." INNER JOIN courses as cr ON  cr.id = adetails.course_id "
                ." INNER JOIN question_master as qm ON  qitem.item_id = qm.id "       
                . " WHERE adetails.id=$assignment_id" ;

              $connection = ConnectionManager::get('default');
              $results = $connection->execute($sql)->fetchAll('assoc');
            // print_r($result);
            $count = count($results);
            $data['counts'] = $count;
            if($count > 0){
                foreach ($results as $result) {
                  //$data['items'][] = $result; 

                $assign_detail['assignment_id']   = $result['assignment_id'];        
                $assign_detail['course_id']       = $result['course_id'];
                $assign_detail['course_name']     = $result['course_name'];
                $assign_detail['assignment_for']  = $result['assignment_for'];
                $assign_detail['grade_id']        = $result['grade_id']; 
                $assign_detail['group_id']        = $result['group_id'];
                $assign_detail['student_id']      = $result['student_id']; 
                $assign_detail['schedule_time']   = $result['schedule_time'];        
                $assign_detail['comments']        = $result['comments'];
                $assign_detail['quiz_id']         = $result['quiz_id'];
                $assign_detail['quiz_name']       = $result['quiz_name'];
                $assign_detail['max_questions']   = $result['max_questions'];
                $assign_detail['max_marks']       = $result['max_marks'];
                $assign_detail['created']         = $result['created'];

                //Question Details
                $assign_ques['question_id']   = $result['question_id'];
                $assign_ques['question_name']   = $result['questionName'];
                $assign_ques['type']        = $result['type'];
                $assign_ques['level']        = $result['level'];
                $assign_ques['uniqueId']        = $result['uniqueId'];


                // to get the options of question
                // Find option to question
                   $option_sql = "SELECT * FROM option_master WHERE uniqueId ='".$result['uniqueId']."'";
                   $optionRecords = $connection->execute($option_sql)->fetchAll('assoc');
                   $optionArray =[];  
                   if(count($optionRecords) > 0){                                     
                      foreach ($optionRecords as $optionRow) {
                        $optionArray[]=array('value'=>$optionRow['options'],'label'=>$optionRow['options']);                         
                      }
                      $assign_ques['options'] =  $optionArray;             
                                             
                   }else{
                    $assign_ques['options'] = [];
                    $assign_ques ['option_message'] = "Option is not available in data";
                   }


                   // Find Answers for a question
                   $answer_sql = "SELECT * FROM answer_master WHERE uniqueId ='".$result['uniqueId']."'";
                   $answerRecords = $connection->execute($answer_sql)->fetchAll('assoc');
                   $answerArray =[];
                   if(count($answerRecords) > 0){                       
                      foreach ($answerRecords as $answerRow) {
                        $answerArray[]=array('value'=>$answerRow['answers'],'score'=>1);                        
                      } 
                     $assign_ques['answers'] =  $answerArray;
                                       
                   }else{
                    $assign_ques['answers'] = [];
                    $assign_ques ['answer_message'] = "No correct answer available.";
                   }

                   $data['status'] =True;
                   $data['assignment_details'] = $assign_detail ;
                   $data['questions'][] =$assign_ques;
                }
                
                
            }else{
              $data['status'] =False;
              $data['message'] ="No record found fir this assignments.";
            }

    }else{
      $data['status'] =False;
      $data['message'] ="assignment_id id cannot null.";
    }

     $this->set([
            'response' => $data,
            '_serialize' => ['response']
     ]);      

}


  // Function to get List of questions in subskill quiz
  public function getQuestionsList($subjects = null, $grade_id = null, $standard = null, $limit = 5, $target = null, $dok = null, $difficulty = 'Easy', $type = null, $user_id = null, $quiz_type_id=0) {

    $subjects = isset($this->request->data['subjects']) ? $this->request->data['subjects'] : $subjects;

    $course_ids = isset($this->request->data['subjects']) ? $this->request->data['subjects'] : $subjects;

    $grade_id = isset($this->request->data['grade_id']) ? $this->request->data['grade_id'] : $grade_id;

    $standard = isset($this->request->data['standard']) ? $this->request->data['standard'] : $standard;

    $quiz_type_id = isset($this->request->data['quiz_type_id']) ? $this->request->data['quiz_type_id'] : $quiz_type_id;

    $quiz_name = isset($this->request->data['quiz_name']) ? $this->request->data['quiz_name'] : 'mlg'.date("YmdHis");


    $limit = isset($this->request->data['limit']) ? $this->request->data['limit'] : $limit;

    $target = isset($this->request->data['target']) ? $this->request->data['target'] : $target;

    $dok = isset($this->request->data['dok']) ? $this->request->data['dok'] : $dok;

    $difficulty = isset($this->request->data['difficulty']) ? $this->request->data['difficulty'] : $difficulty;

    $type = isset($this->request->data['type']) ? $this->request->data['type'] : $type;

    $user_id = isset($this->request->data['user_id']) ? $this->request->data['user_id'] : $user_id;    


    // $subj= '('.implode(',', $subj).')';
    $subjects = '(' . $subjects . ')';
    $data['status'] = "False";
    $data['message'] = "";
    $connection = ConnectionManager::get('default');


    $sql = 'SELECT  distinct qm.id, type, qm.grade,qm.grade_id,qm.subject,qm.standard,qm.course_id, qm.docId, qm.uniqueId, questionName,  qm.level,qm.marks as question_marks,
                 mimeType, paragraph, item,Claim,Domain,Target,`CCSS-MC`,`CCSS-MP`,
                 cm.state, cm.GUID, cm.ParentGUID, cm.AuthorityGUID, cm.Document, cm.Label, cm.Number, cm.Description, cm.Year, createdDate
              FROM question_master AS qm
              LEFT JOIN header_master AS hm ON hm.uniqueId = qm.docId and hm.headerId=qm.headerId
              LEFT JOIN mime_master AS mm ON mm.uniqueId = qm.uniqueId
              LEFT JOIN paragraph_master as pm on pm.question_id=qm.docId
              LEFT JOIN  compliance_master as cm on (cm.Subject=qm.subject OR cm.grade=qm.grade)
              where qm.course_id IN ' . $subjects . ' and qm.grade_id=' . $grade_id;



    if ($standard !== NULL) {
      $standard = explode("|", $standard);
      $sql.=" and `CCSS-MC` in (";
      $countArray = 0;
      foreach ($standard as $std) {
        ++$countArray;
        $sql.="'" . $std . "' ";
        if (!empty($standard[$countArray])) {
          $sql.=",";
        }
      }

      $sql.=")";
    }

    if ($difficulty !== NULL) {

      $difficulty = explode("|", $difficulty);
      $sql.=" and qm.level in (";
      $countArray = 0;
      foreach ($difficulty as $level):
        ++$countArray;
        $sql.="'" . $level . "' ";
        if (!empty($difficulty[$countArray])) {
          $sql.=",";
        }
      endforeach;

      $sql.=")";
    }

    if ($type !== NULL) {
      $type = explode("|", $type);
      $sql.=" and qm.type in (";
      $countArray = 0;
      foreach ($type as $typos):
        ++$countArray;
        $sql.="'" . $typos . "' ";
        if (!empty($type[$countArray])) {
          $sql.=",";
        }
      endforeach;

      $sql.=")";
    }

    //if($skills !== NULL){ $sql.=" and skills = '".$skills."'";  }
    if ($target !== NULL) {
      $sql.=" and target ='" . $target . "'";
    }
    if ($dok !== NULL) {
      $sql.=" and hm.DOK ='" . $dok . "'";
    }
    if ($limit !== null) {
      $sql.="ORDER BY RAND() limit " . $limit;
    }

    $question_info = array();
    $quiz_marks = 0;
    $ques_ids = array();
    $questionRecords = $connection->execute($sql)->fetchAll('assoc');    
    if (count($questionRecords) >0) {
      $data['status'] = "True";
      foreach ($questionRecords as $questionRow) {
        $ques_ids[] = $questionRow ['id'];


        foreach ($questionRow as $key => $value) {
          $question_info[$key] = $value;
        }
        $question_info['id'] = 'response_id-' . $questionRow['id'];
        $question_info['question_id'] = $questionRow['id'];
        $question_info['questionName'] = $questionRow['questionName'];
        // Find option to question
        $option_sql = "SELECT * FROM option_master WHERE uniqueId ='" . $questionRow['uniqueId'] . "'";
        $optionRecords = $connection->execute($option_sql)->fetchAll('assoc');
        if (count($optionRecords) > 0) {
          foreach ($optionRecords as $optionRow) {
            $optionArray[] = array('value' => $optionRow['options'], 'label' => $optionRow['options']);
          }
          $question_info['options'] = $optionArray;
          $optionArray = [];
        } else {
          $question_info ['option_message'] = "No option Found for this question";
        }


        // Find Answers for a question

        $quiz_marks = $quiz_marks + $questionRow ['question_marks'];
         $answer_sql = "SELECT * FROM answer_master WHERE uniqueId ='" . $questionRow['uniqueId'] . "'";
        $answerRecords = $connection->execute($answer_sql)->fetchAll('assoc');
        if (count($answerRecords) > 0) {
          foreach ($answerRecords as $answerRow) {
            $answerArray[] = array('value' => $answerRow['answers'], 'score' => $questionRow ['question_marks']);
          }
           $question_info['answers'] =  $answerArray; 
          $answerArray = [];          
        } else {
          $question_info ['answer_message'] = "No Answer Found for this question";
        }

        //Question Collections
        $questions[] = $question_info;

      }

      //Result 1-  if quiz is a custom Assignment  
      if ($user_id == null) {        
        $data['questions'] = $questions;
      }
      // Result 2-  if quiz is auto generated
      else {

        // Create Quiz                             
        $quiz = $this->createQuiz($quiz_name, $quiz_type_id, $course_ids, $grade_id, $limit, $ques_ids, $quiz_marks, $user_id);      
        if ($quiz['status'] == True || $quiz['status'] ==1) {
          $quiz_id = $quiz['quiz_id'];

              foreach ($questions as $ques) {
              $questions_detail['quiz_id'] = $quiz_id;
              $quesList[] = array_merge($questions_detail, $ques);

            }
            $data['questions'] = $quesList;

        } else {
          $data['quiz_status'] = $quiz;
          $data['status'] = "False";
        }

        
      }
    } else {
      $data['status'] = "False";
      $data['message'] = "No question found in our dataware house.";
    }

   // return ($data);
    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
    
  }

  public function createQuiz($quiz_name, $quiz_type_id, $course_ids, $grade_id,$limit = null, $itemsIds = array(), $quiz_marks = null, $user_id = null) {

    if(isset($quiz_name) && isset($quiz_type_id) && isset($course_ids) && isset($grade_id) ){
      if (!empty($itemsIds) && !empty($limit) && !empty($quiz_marks)) {
         
          $quiz_info['name'] =$quiz_name;
          $quiz_info['quiz_type_id'] =$quiz_type_id;
          $quiz_info['course_id'] =$course_ids;
          $quiz_info['grade_id'] =$grade_id;
          $quiz_info['is_graded'] = 1;
          $quiz_info['is_time'] = 1;
          $quiz_info['max_marks'] = $quiz_marks;
          $quiz_info['max_questions'] = count($itemsIds);
          $quiz_info['duration'] = '1';
          $quiz_info['status'] = 1;
          $quiz_info['created_by'] = $user_id;
          $quiz_info['created'] = time();
          $quiz_info['modified'] = time();  

          $Quizes = TableRegistry::get('Quizes');
          $new_quiz = $Quizes->newEntity($quiz_info);
          if ($qresult = $Quizes->save($new_quiz)) {
            $quiz_item['exam_id'] = $qresult->id;
            $quiz_item['created'] = time();
            $quiz_item['status'] = 1;

            foreach ($itemsIds as $key => $value) {

              $quiz_item['item_id'] = $value;

              $QuizItems = TableRegistry::get('QuizItems');
              $new_quizitem = $QuizItems->newEntity($quiz_item);
              if ($qitemresult = $QuizItems->save($new_quizitem)) {
                $data['status'] = True;
                $data['quiz_id'] = $qresult->id;
                $data ['message'] = "quiz is created.";
              } else {
                $data['status'] = False;
                $data ['message'] = "Not able to create quiz item. Please consult with admin1";
              }
            }
          } else {
            $data['status'] = False;
            $data ['message'] = "Not able to create quiz. Please consult with admin2";
          }
        } else {
          $data['status'] = False;
          $data ['message'] = "Not able to create quiz. Please consult with admin3";
        }
      }
      else{
        $data['status'] =False;
        $data['message'] = "Please set quiz_name, quiz_type_id, course_ids and grade_id";
      }
    return($data);
  }




public function getStudentReport($user_id=null){
    if(!empty($user_id)){
          //$UserQuizes = TableRegistry::get('UserQuizes') ;
          //$results= $UserQuizes->find('all')->where(['user_id' => $uid])->order(['id'=>'ASC']);
          
          $connection = ConnectionManager::get('default');
          $sql = "SELECT uq.*, cr.id,cr.course_name,cr.level_id as grade_id FROM user_quizes as uq, courses as cr WHERE uq.course_id=cr.id ANd uq.grade_id=cr.level_id
           AND uq.user_id=$user_id";
           $results = $connection->execute($sql)->fetchAll('assoc');
          
          if(count($results) > 0){                 
            foreach ($results as $result) {               
                
              $row['user_quiz_id'] = $result['id'];  
              $row['grade_id']=$result['grade_id'];
              $row['course_id']=$result['course_id'];
              $row['quiz_type_id'] = $result['quiz_type_id'];  
              
              $row['quiz_id'] = $result['exam_id'];                                      
              $row['exam_marks']=$result['exam_marks'];
              $row['student_score']=$result['score'];
              $row['course_name']=$result['course_name'];
                    
              if($result['exam_marks']!=0){
                $row['student_result_percent']=(int)( ($result['score']/$result['exam_marks'])*(100));
              }else{ 
                  $row['student_result_percent'] = 0;
                  $row['message'] = "Either quiz is not started or quiz attempted incomplete.";
              } 

             // $data['details'][] = $row;

              // To check other students on same course_id and grade_id
              $UserQuizes = TableRegistry::get('UserQuizes') ;
              $userquiz_results= $UserQuizes->find('all')->where(['course_id'=>$row['course_id'], 'user_id !='=> $user_id,'quiz_type_id'=>$row['quiz_type_id'] ])->order(['id'=>'ASC']);
              if($userquiz_results->count()>0){
                  $othersts_score_percent = 0;
                  $st_count = 0;
                  foreach ($userquiz_results as $otherstrow) {
                     $othersts_score_percent = $othersts_score_percent +( ($otherstrow['score']/$otherstrow['exam_marks'])*(100) );
                      $st_count = $st_count +1;
                  }
                 // $row['status'] = True;
                  $row['other_Student_average'] = round( ($othersts_score_percent / $st_count),1);

                  //$data['details'][] = $row;
                  ///$row =[];

              }
              else{
                //$data['status'] = False;
                $row['other_Student_average'] ="";
                $row['message'] = 'No students found for same course';
                
              }

              $data['details'][] = $row;
              $row['other_Student_average'] ="";
              $row['message'] ="";
            }               
          }
            else{
                $data['status'] = False;
                $data['message'] = "No result found.";
            }

    }else{
      $data['status'] = "";
      $data['message'] ="please set user_id";
    }

     $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);


}


// API to call curl 
  /*way of calling $curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]); */
  public function curlPost($url, $data) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      $response = curl_exec($ch);
      curl_close($ch);

      return $response;
  }
 

 
}

