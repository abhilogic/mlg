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
            $courses_info= $crObj->getChildCoursesOfSubject($subject_id,2,2);
            
            
            $subskill_ids = array();
            foreach ($courses_info as $coursesinfo) { 

                if(!empty($coursesinfo['status']) && ($coursesinfo['status']==1 )){
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
              $data['quiz_id'] = $quiz_id;
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
              $data['status'] = True;
              $row['other_Student_average'] ="";
              $row['message'] ="";
            }               
          }
            else{
                $data['status'] = False;
                $data['message'] = "No result found.";
            }
    }else{
      $data['status'] = False;
      $data['message'] ="please set user_id";
    }

     $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
}


// API need attention of a student
/* API to get Need Attention on teacher dashboard*/
public function getNeedAttentionOFStudent($student_id=null){
  
  $student_id = isset($_REQUEST['student_id']) ? $_REQUEST['student_id'] : $student_id;
  
  if(!empty($student_id)){
      $connection = ConnectionManager::get('default');

     // get students quiz result for subskills
     $sql = "SELECT uq.*,(score*100)/exam_marks as student_result, u.username,u.first_name,u.last_name,qt.name as quiz_type_name, cr.course_name from user_quizes as uq, users as u, courses as cr, quiz_types as qt WHERE u.id=uq.user_id AND uq.course_id = cr.id AND qt.id=uq.quiz_type_id AND uq.user_id=$student_id AND uq.quiz_type_id IN (2,4,5) ORDER BY created DESC";
    
      $stQuizRecords = $connection->execute($sql)->fetchAll('assoc');
      if(count($stQuizRecords) > 0){
          foreach ($stQuizRecords as $stQuizRecord) {
             // $data['attention_records'][] = $stQuizRecord;

            // to get subject/class , skill subskill name of a subject
            $base_url = Router::url('/', true);
            $json_courseinfo = $this->curlPost($base_url.'courses/getCourseInfo/'.$stQuizRecord['course_id'], array() );                 
            $array_courseinfo = (array)json_decode($json_courseinfo);
            if(isset($array_courseinfo['response'])) {
                if(isset($array_courseinfo['response']->parent_info_of_skill)){
                    $stQuizRecord['subject_id'] = $array_courseinfo['response']->parent_info_of_skill->id;
                    $stQuizRecord['subject_name'] = $array_courseinfo['response']->parent_info_of_skill->course_name;
                  $stQuizRecord['class_name'] = $array_courseinfo['response']->parent_info_of_skill->grade_name;
                }
            }else{
                    $assg['subject_id'] = 'issue/not found';
                }

              if($stQuizRecord['student_result'] < QUIZ_PASS_SCORE){
                $data['student_attention_records'][] = $stQuizRecord;
              }
          } 
          $data['status'] =True;       
      }else{
              $data['status'] =False;
              $data['message']="No Records For Your Attention.";                            
       } 
      }else{
              $data['status'] = False;
              $data['message'] = "Student Id cannot null.";
          }

      $this->set([
          'response' => $data, 
          '_serialize' => ['response']
        ]);
}


// API need attention of a student
/* API to get Need Attention on teacher dashboard*/
public function getStudentScoreForSubskills($student_id=null,$subject_id=null){
  
  $student_id = isset($_REQUEST['student_id']) ? $_REQUEST['student_id'] : $student_id;
  $subject_id = isset($_REQUEST['subject_id']) ? $_REQUEST['subject_id'] : $subject_id;
  
  if(!empty($student_id) && !empty($subject_id)){
        $connection = ConnectionManager::get('default');

        //step-1 get subskill list of a subject through which student is linked
        $course_obj = new CoursesController();
        $array_subskillidsinfo =$course_obj->getChildCoursesOfSubject($subject_id,2,2);
        $subskill_ids = array();
        if(!empty($array_subskillidsinfo)){
            foreach ($array_subskillidsinfo as $resultsubskill) {
              if(isset($resultsubskill['childcourse_ids'])){
                    $subskill_ids =array_merge($subskill_ids,$resultsubskill['childcourse_ids']) ;
              }
            }
        }
        
        // Step-2 after getting subskill get result of student on subskill
        if(!empty($subskill_ids)){              

            foreach ($subskill_ids as $subskillid) {

               // to get subject/class , skill subskill name of a subject
                $base_url = Router::url('/', true);
                $json_courseinfo = $this->curlPost($base_url.'courses/getCourseInfo/'.$subskillid, array() );                
                $array_courseinfo = (array)json_decode($json_courseinfo);
                if(isset($array_courseinfo['response'])) {
                    if(isset($array_courseinfo['response']->parent_info_of_skill)){
                      
                      $stQuizRecord['grade_id'] = $array_courseinfo['response']->parent_info_of_skill->level_id;
                      $stQuizRecord['class_name'] = $array_courseinfo['response']->parent_info_of_skill->grade_name;
                       $stQuizRecord['subject_id'] = $array_courseinfo['response']->parent_info_of_skill->id;
                      $stQuizRecord['subject_name'] = $array_courseinfo['response']->parent_info_of_skill->course_name; 
                      $stQuizRecord['skill_id'] = $array_courseinfo['response']->skill_info_of_subskill->id;
                      $stQuizRecord['skill_name'] = $array_courseinfo['response']->skill_info_of_subskill->course_name;

                       $stQuizRecord['subskill_id'] = $array_courseinfo['response']->course_Information->id;
                      $stQuizRecord['subskill_name'] = $array_courseinfo['response']->course_Information->course_name;

                      // get students quiz result for subskills
                      $sql = "SELECT max((score*100)/exam_marks) as student_percentage FROM user_quizes as uq WHERE uq.user_id=$student_id AND uq.course_id=$subskillid AND uq.quiz_type_id IN (2,4,5,6,7) ORDER BY created DESC";
            
                      $stQuizRecords = $connection->execute($sql)->fetchAll('assoc');
                      if(count($stQuizRecords) > 0){
                          foreach ($stQuizRecords as $row) {                 
                            $stQuizRecord['student_subskill_percentage'] = round($row['student_percentage'],2) ;         
                          } // foreach end

                          $data['status'] =True; 
                          $data['student_percentage'][] = $stQuizRecord;      
                      }else{
                              $data['status'] =False;
                              $data['message']="No Records For Your Attention.";                            
                       }                                                 
                    }else{
                        $data['status'] =False;
                         $data['message'] = 'No subjects found on this subskill.';
                    }
                }else{
                      $data['status'] =False;
                     $data['message'] = 'No subjects found on this subskill.';
                }

            } // end foreache of subskill

        }else{
            $data['status'] = False;
            $data['message'] = "No subskill found for this subject.";
        }
  }else{
        $data['status'] = False;
        $data['message'] = "Student Id cannot null.";
    }

      $this->set([
          'response' => $data, 
          '_serialize' => ['response']
        ]);
}




/*** API to call student records for skills ***/
// API for student profile page in teacher module for student line chart graph for grade analysis
public function getStudentScoreForSkills($student_id=null,$subject_id=null){

  $student_id = isset($_REQUEST['student_id']) ? $_REQUEST['student_id'] : $student_id;
  $subject_id = isset($_REQUEST['subject_id']) ? $_REQUEST['subject_id'] : $subject_id;
  
  if(!empty($student_id) && !empty($subject_id)){
        $connection = ConnectionManager::get('default');

        //step-1 get skill list of a subject through which student is linked
        $course_obj = new CoursesController();
        $array_skillidsinfo =$course_obj->getChildCoursesOfSubject($subject_id,1,2);        
        $skill_ids = array();
        if(!empty($array_skillidsinfo)){              
            if(isset($array_skillidsinfo['childcourse_ids'])){
               $skill_ids =array_merge($skill_ids,$array_skillidsinfo['childcourse_ids']) ;
            }
            
        }
        
       
        // Step-2 after getting skill get subskill list of skill
        if(!empty($skill_ids)){              

            foreach ($skill_ids as $skillid) {

              // subskill information
              $base_url = Router::url('/', true);
              $stRecord =array();
              $json_courseinfo = $this->curlPost($base_url.'courses/getCourseInfo/'.$skillid, array() );               
              $array_courseinfo = (array)json_decode($json_courseinfo);
              if(isset($array_courseinfo['response'])) {                
                $stRecord['class_name'] = $array_courseinfo['response']->parent_info_of_skill->grade_name;
                $stRecord['subject_id'] = $array_courseinfo['response']->parent_info_of_skill->id;
                $stRecord['subject_name'] = $array_courseinfo['response']->parent_info_of_skill->course_name;     
                $stRecord['skill_id'] = $array_courseinfo['response']->course_Information->id;
                $stRecord['skill_name'] = $array_courseinfo['response']->course_Information->course_name;
              }

               //2. to get subskills of a skill
                $array_subskillidsinfo =$course_obj->getChildCoursesOfSubject($skillid,1,2);        
                $subskill_ids = array();                
                if(!empty($array_subskillidsinfo)){              
                    if(isset($array_subskillidsinfo['childcourse_ids'])){
                        $subskill_ids =array_merge($subskill_ids,$array_subskillidsinfo['childcourse_ids']) ;
                    }
                    
                }


                //3. Now get the max result from all susbkills of a skill
                if(!empty($subskill_ids)){
                    $subskillids= implode(',', $subskill_ids);
                    
                    //Start-  Student Score: get avg result of a students  in attemped quizes of all subskills of a skill
                    $sql = "SELECT users.*,avg((score*100)/exam_marks) as student_percentage  FROM user_quizes as uq INNER JOIN users ON users.id=uq.user_id
                    WHERE uq.user_id=$student_id AND uq.course_id IN ($subskillids) AND uq.quiz_type_id IN (2,4,5,6,7) ORDER BY created DESC"; 


                      $stQuizRecords = $connection->execute($sql)->fetchAll('assoc');
                      if(count($stQuizRecords) > 0){
                        foreach ($stQuizRecords as $row) {                                              
                         // $stRecord = array_merge($stRecord, $row);
                          $stRecord['student_percentage'] =$row['student_percentage'];
                          $stRecord['student_quiz_attempt'] =1;

                          $stDetails['username'] = $row['username'];
                          $stDetails['first_name'] = $row['first_name'];
                          $stDetails['last_name'] = $row['last_name'];                          
                        }
                      }else{
                        $stRecord['student_quiz_attempt'] =0;
                      }                       
                      //Start-  Student Score:


                      //4. start - class students of a teacher : avg score 
                        $st_classmate_ids= $this->getStudentInformations($student_id,$subject_id,2);
                        if(isset($st_classmate_ids['student_informations']['classmates_ids']) ){
                            $classmate_ids = $st_classmate_ids['student_informations']['classmates_ids'][ $stRecord['subject_name'].'-'.$stRecord['subject_id'] ];
                          
                            $str_classmate_ids = implode(',', $classmate_ids);
                            $sql = "SELECT avg((score*100)/exam_marks) as student_percentage  
                                    FROM user_quizes as uq
                                    WHERE uq.user_id IN ($str_classmate_ids) AND uq.course_id IN ($subskillids) 
                                    AND uq.quiz_type_id IN (2,4,5,6,7) ORDER BY created DESC"; 
                                   

                            $otherstQuizRecords = $connection->execute($sql)->fetchAll('assoc');
                            if(count($otherstQuizRecords) > 0){
                            foreach ($otherstQuizRecords as $otherstrow) {                              
                              $stRecord['other_Student_average'] = $otherstrow['student_percentage'];
                            }
                          }else{
                            $stRecord['other_Student_average'] = 0;
                          }
                        }
                        $stRecord['status']= True;  
                   //end - class students avg score                                          
                      
                }else{
                  $stRecord['status'] = False;
                  $stRecord['student_quiz_attempt'] =0;                  
                  $stRecord['message'] = "No subskills found.";
                }
               
                $data['status'] =True;
               $data['student_details'] =$stDetails;
                $data['student_skill_percentage'][] = $stRecord;
            } // end foreache of skill

        }else{
            $data['status'] = False;
            $data['message'] = "No skill found for this subject.";
        }
  }else{
        $data['status'] = False;
        $data['message'] = "Student Id cannot null.";
    }

      $this->set([
          'response' => $data, 
          '_serialize' => ['response']
        ]);
}



/*** 
Important
  The function we return the list of classmates of a students.
  This function will return Json(return_type=1) and array(return_type=2)
  If student if belong to teacher then classmate will return accordingly
  but if students/child is belog to parent then returns all classmates belong to same grade of the portal
***/
public function getStudentInformations($student_id=null,$subject_id=null,$return_type=1){
    $student_id = isset($_REQUEST['student_id'])? $_REQUEST['student_id'] : $student_id;
    $subject_id = isset($_REQUEST['subject_id'])? $_REQUEST['subject_id'] : $subject_id;
    if(!empty($student_id)){
        //1. check teacher of the student
        $connection = ConnectionManager::get('default');     

        $whereExt = !empty($subject_id) ? "  AND uc.course_id=$subject_id": ' ORDER BY u.id ';
        $sql = "SELECT teacher_id, u.username,u.first_name,u.last_name,uc.course_id, c.course_name,c.level_id, l.name FROM student_teachers as st
                INNER JOIN users as u ON u.id=st.student_id 
                INNER JOIN user_courses as uc ON uc.user_id=st.student_id
                INNER JOIN courses as c ON c.id=uc.course_id
                INNER JOIN levels as l ON l.id=c.level_id
                WHERE student_id=$student_id".$whereExt;

        $steacher = $connection->execute($sql)->fetchAll('assoc');
        if(count($steacher) > 0){  // that means student is beloning to a teacher
          foreach ($steacher as $trow) {
              $teacher_id = $trow['teacher_id'];              
              $subject_id = $trow['course_id'];
              $subject_name = $trow['course_name'];
              $grade_id = $trow['level_id'];
               $grade_name = $trow['name'];

               $stinfo =['username'=>$trow['username'],'first_name'=>$trow['first_name'],'last_name'=>$trow['last_name'] ];

               $stclassinfo =['grade_id'=>$grade_id,'grade_name'=>$grade_name,'subject_id'=>$subject_id,'subject_name'=>$subject_name ];


              /*$stclassinfo['grade_id'] = $grade_id;
              $stclassinfo['grade_name'] =$grade_name;
              $stclassinfo['subject_id'] = $subject_id;
              $stclassinfo['subject_name'] = $subject_name;*/


               //2. get classmates list
                $sql1 = "SELECT u.* FROM users as u 
                          INNER JOIN user_details as ud ON ud.user_id=u.id
                          INNER JOIN user_courses as uc ON uc.user_id=u.id
                          INNER JOIN student_teachers as st ON st.student_id=u.id                          
                          WHERE u.id!=$student_id AND st.teacher_id=$teacher_id AND uc.course_id=$subject_id ORDER BY u.id".$whereExt; 

                  $stclassmates = $connection->execute($sql1)->fetchAll('assoc');
                  if(count($stclassmates) > 0){
                    foreach ($stclassmates as $stclassmate) {                       
                        $st_classmates[]= $stclassmate; 
                        $st_classmate_ids[]= $stclassmate['id'];                    
                    }
                  }
                  $data['status'] =True;
                  $data['student_informations']['student_info'] = $stinfo;
                  $data['student_informations']['student_subject_info'][$subject_name] = $stclassinfo;

                  if(isset($st_classmates) && count($st_classmates) > 0){
                      $data['student_informations']['student_classmates'][$subject_name.'-'.$subject_id] = $st_classmates;
                      $data['student_informations']['classmates_ids'][$subject_name.'-'.$subject_id] = $st_classmate_ids;       
                  }else{                                       
                    $data['student_informations']['student_classmates'][$subject_name.'-'.$subject_id] = "No classmates found"; 
                  }
          }
      }
        else{  // that means student is beloning to a parents only

              $str = "SELECT  ud.parent_id,u.username, u.first_name, u.last_name, c.level_id, l.name, uc.course_id, c.course_name FROM users as u
                      INNER JOIN user_details as ud ON ud.user_id=u.id                      
                      INNER JOIN user_courses as uc ON uc.user_id=u.id
                      INNER JOIN courses as c ON c.id=uc.course_id
                      INNER JOIN levels as l ON l.id=c.level_id
                      WHERE ud.user_id=$student_id".$whereExt;
              $stparent = $connection->execute($str)->fetchAll('assoc');
              if(count($stparent) > 0){
                  foreach ($stparent as $prow) {
                      $parent_id = $prow['parent_id'];
                      $subject_id = $prow['course_id'];
                      $subject_name = $prow['course_name'];
                      $grade_id = $prow['level_id'];
                      $grade_name = $prow['name'];


                      $stinfo = ['username'=>$prow['username'], 'first_name'=>$prow['first_name'], 'last_name'=>$prow['last_name'] ];

                      $stclassinfo =['grade_id'=>$grade_id,'grade_name'=>$grade_name,'subject_id'=>$subject_id,'subject_name'=>$subject_name ];

                      //2. get classmates list
                      $sql1 = "SELECT u.* FROM users as u 
                                INNER JOIN user_details as ud ON ud.user_id=u.id
                                INNER JOIN user_courses as uc ON uc.user_id=u.id                                 
                                WHERE uc.course_id=$subject_id AND u.id!=$student_id".$whereExt; 

                        $stclassmates = $connection->execute($sql1)->fetchAll('assoc');
                        if(count($stclassmates) > 0){
                          foreach ($stclassmates as $stclassmate) {                       
                              $st_classmates[]= $stclassmate; 
                              $st_classmate_ids[]= $stclassmate['id'];                    
                          }
                        }
                        $data['status'] =True;
                        $data['student_informations']['student_info'] = $stinfo;
                        $data['student_informations']['student_subject_info'][$subject_name] = $stclassinfo;

                        if(isset($st_classmates) && count($st_classmates) > 0){
                            $data['student_informations']['student_classmates'][$subject_name.'-'.$subject_id] = $st_classmates;
                            $data['student_informations']['classmates_ids'][$subject_name.'-'.$subject_id] = $st_classmate_ids;       
                        }else{                                       
                          $data['student_informations']['student_classmates'][$subject_name.'-'.$subject_id] = "No classmates found"; 
                        }
                  }
              }else{
                $data['status'] = False;
                $data['message'] = "Please set the correct student id.";
              }

        }




    }else{
        $data['status'] =False;
        $data['message'] ="student id cannot null."; 


    }

    if($return_type==1){
      $this->set([
          'response' => $data, 
          '_serialize' => ['response']
        ]);
    }else{
      return $data;
    }

}

/***
       API subskil Analytic of a student for profile 
      to check how many student on above/below of this student 
    ***/
    public function getSubskillAnalyticOfStudent($teacher_id=null,$student_id=null,$exam_id=null){

      $teacher_id = isset($_REQUEST['teacher_id']) ? $_REQUEST['teacher_id'] : $teacher_id;
      $student_id = isset($_REQUEST['student_id']) ? $_REQUEST['student_id'] : $student_id;
      $exam_id = isset($_REQUEST['exam_id']) ? $_REQUEST['exam_id'] : $exam_id;

      $connection = ConnectionManager::get('default');

      if(!empty($teacher_id) && !empty($exam_id) && !empty($student_id)){
          $count_classStudents = 0;
          $count_abovestudents =0;
          $count_belowstudent =0;
             
          //1.  course_id /subject_id of a student
          $sql = "SELECT course_id from student_teachers WHERE teacher_id = $teacher_id AND student_id = $student_id ";  
          $stcourse_records = $connection->execute($sql)->fetchAll('assoc');
          if(count($stcourse_records) > 0){
              foreach ($stcourse_records as $srecord) {                    
                  $st_courseid=$srecord['course_id'];                      
              }
          }else{
                $data['status'] = False;
                $data['message'] = "This student is for this teacher.";
            }



            //2.  get selected student result
            $sql1 = "SELECT * FROM user_quizes WHERE exam_id=$exam_id ";  
            $stexam_records = $connection->execute($sql1)->fetchAll('assoc');
          if(count($stexam_records) > 0){
              foreach ($stexam_records as $sexamrecord) {                               
                  $exam_subskillid=$sexamrecord['course_id']; 
                  $select_stud_result_precent = ($sexamrecord['score']/$sexamrecord['exam_marks'])*100;
              }
          }else{
                $data['status'] = False;
                $data['message'] = "No exam is exist in Dataware house.";
            }

          if(!empty($st_courseid) && !empty($select_stud_result_precent) && !empty($exam_subskillid)){

            //3.  get students of class except selected student for subject
            $sql2 = "SELECT st.student_id,u.username from student_teachers as st, users as u WHERE u.id=st.student_id AND teacher_id = $teacher_id AND course_id=$st_courseid AND student_id!=$student_id  ORDER BY student_id ASC ";  
            $stRecords = $connection->execute($sql2)->fetchAll('assoc');
            if(count($stRecords) > 0){
                   
                foreach ($stRecords as $stRecord) {
                    $class_stud_id = $stRecord['student_id'];

                    // check the user_quiz result for each student of class
                    $sql3 = "SELECT uq.*,qt.name as quiz_type_name, cr.course_name FROM user_quizes as uq
                              INNER JOIN courses as cr ON  cr.id=uq.course_id
                              INNER JOIN quiz_types as qt ON qt.id=uq.quiz_type_id
                              WHERE course_id=$subskill_id AND uq.user_id=$class_stud_id AND quiz_type_id=2
                              ORDER BY created DESC "; 
                      
                    $stQuizRecords = $connection->execute($sql3)->fetchAll('assoc');
                    if(count($stQuizRecords) > 0){
                        foreach ($stQuizRecords as $stQuizRecord) {

                          
                        }                          

                    }else{
                            $count_belowstudent++;
                          }

                  }
            }else{
                  $data['status']=False;
                  $data['message'] = "No Other student except this student found in database.";
                }
          }   // end if to check $st_courseid, $select_stud_result_precent           

        }else{
            $data['status'] = False;
            $data['message'] ="teacher_id, student_id and exam_id cannot null. please set the value.";
        }

        $this->set([
          'response' => $data, 
          '_serialize' => ['response']
        ]);


    }



// API for student Analytic ($user_id will be student_id and child_id)
public function getStudentProgress($user_id=null){
    $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id']:$user_id;

    if(!empty($user_id)){
          $connection = ConnectionManager::get('default');

          // step-1, get student courses (english/hindi for grade -6/7/8)
          $base_url = Router::url('/', true);
          $subskill_ids = array();
          $json_subjectsinfo = $this->curlPost($base_url.'students/getStudentCourses/'.$user_id, array() );
          $array_subjectsinfo = (array)json_decode($json_subjectsinfo);
          
          if(isset($array_subjectsinfo['response'])) {              
              if(isset($array_subjectsinfo['response']->student_courses)){
                  foreach ($array_subjectsinfo['response']->student_courses as $sub) {

                      //step-2 get subskill list of a subject through which student is linked
                      $json_subskillidsinfo = $this->curlPost($base_url.'courses/getChildCoursesOfSubject/'.$sub->id.'/2/1', array() );                      
                      $array_subskillidsinfo = (array)json_decode($json_subskillidsinfo);                      
                      if(isset($array_subskillidsinfo['response'])) {                        
                        if($array_subskillidsinfo['response']->status ==1){                          
                            foreach ($array_subskillidsinfo['response'] as $resultsubskill) {
                                if(isset($resultsubskill->childcourse_ids)){
                                    //subskill_id will contains all subskills of subject maths,english,science,ss
                                  $subskill_ids =array_merge($subskill_ids,$resultsubskill->childcourse_ids) ;
                                }                                                             
                            }
                        }
                      }
                      else{
                          $data['status'] = False;
                          $data['message'] = "No Subskill added for this subject.";
                      }                    
                  }
              }
            }else{
                  $data['status'] = False;
                  $data['message'] = "No subject is related to this user.";
              }


        //check above subskill variable is empty or has value
        if(!empty($subskill_ids)){

            //step-3 get result of a student on each subskill quiz,challenge
            $total_subskill = count($subskill_ids) ;
            $practice_count = 0;
            $conquered_count = 0;
            $fail_count =0;
            
            $subskillids= implode(',', $subskill_ids) ; 
            //foreach ($subskill_ids as $subskill_id) {
             $str ="SELECT max((score*100)/exam_marks) as score_percentage FROM user_quizes WHERE user_id=$user_id AND course_id IN ($subskillids)  AND quiz_type_id IN (2,5,6,7) GROUP BY course_id";
                $results = $connection->execute($str)->fetchAll('assoc');          
                if(count($results) > 0){ 

                  foreach ($results as $result) {
                      $practice_count++;
                      if($result['score_percentage'] >= CONQUERED){
                          $conquered_count++;
                      }else{
                          $fail_count++;
                      }

                  }
                  $data['status'] = True;
                  $data['total_subskill'] =$total_subskill;
                  $data['conquered_count'] =$conquered_count;
                  $data['practice_count'] =$practice_count;
                  $data['fail_count'] =$fail_count;
                }else{
                  $data['status'] = False;
                  $data['message'] ="No Record Found.";
                }
             
           // }                 
        }else{
              $data['status'] = False;
              $data['message'] = "No Subskill added for this subject.";
        }       


    }else{
        $data['status'] = False;
        $data['message'] ="please set user_id";
    }

     $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);

}


/***** API to get students of a teacher and their records  ***/
public function getDashboardStudentsOfTeacher($teacher_id=null,$subject_id=null,$skill_id=null,$subskill_id=null,$student_classification=null){
 
  $teacher_id = isset($_REQUEST['teacher_id']) ? $_REQUEST['teacher_id']:$teacher_id;
  $subject_id = isset($_REQUEST['subject_id']) ? $_REQUEST['subject_id']:$subject_id;
  $skill_id = isset($_REQUEST['skill_id']) ? $_REQUEST['skill_id']:$skill_id;
  $subskill_id = isset($_REQUEST['subskill_id']) ? $_REQUEST['subskill_id']:$subskill_id;
  $student_classification = isset($_REQUEST['student_classification']) ? $_REQUEST['student_classification']:$student_classification;

   if(!empty($teacher_id) && !empty($subject_id)){


      //get students
      $connection = ConnectionManager::get('default');        
      $sql ="SELECT u.*, ud.profile_pic FROM student_teachers as st INNER JOIN users as u ON u.id=st.student_id INNER JOIN user_details as ud ON ud.user_id=st.student_id INNER JOIN user_courses as uc ON uc.user_id=st.student_id INNER JOIN courses as c ON c.id=uc.course_id  WHERE st.teacher_id=$teacher_id AND uc.course_id = $subject_id ";
     

      $student_records = $connection->execute($sql)->fetchAll('assoc');
      $studentcount = count($student_records);
      $course_obj = new CoursesController(); 

      if ($studentcount > 0) {
        foreach ($student_records as $studentrow) {
          if ($studentrow['profile_pic'] == NULL) {
            $studentrow['profile_pic'] = '/upload/profile_img/default_studentAvtar.png';
          } else {
            $studentrow['profile_pic'] = $studentrow['profile_pic'];
          }

          $stRecord = $studentrow;
          $subskill_ids = array(); 
          $subskill_counts = 1;

            // get student records on subject, skill and subskill Filter
            $where = "uq.user_id=".$studentrow['id'];

            if(!empty($subject_id) && empty($skill_id)){ //subject filter
                
                $courses_info= $course_obj->getChildCoursesOfSubject($subject_id,2,2);
                $subskill_ids = [$subject_id];
                $subject_subskill_ids = array();
                if(count($courses_info)>0){
                foreach ($courses_info as $coursesinfo) {                                    
                    if(isset($coursesinfo['status']) && ($coursesinfo['status']==1)){
                      $subskill_ids = array_merge($subskill_ids, $coursesinfo['childcourse_ids'] );
                    }
                }
              }
                
                if(!empty($subskill_ids) ){
                  $subskill_counts = count($subskill_ids);
                  $course_subskillids = implode(',', $subskill_ids);
                  $where .= " AND uq.course_id IN ($course_subskillids)";
                }
            }

            elseif(!empty($skill_id) && empty($subskill_id)){ //skill filter
               
                //get all subskill_ids            
                $base_url = Router::url('/', true);                                                             
                $array_subskillidsinfo =$course_obj->getChildCoursesOfSubject($skill_id,1,2);
                $subskill_ids = [$skill_id];        
                if(!empty($array_subskillidsinfo)){              
                    if(isset($array_subskillidsinfo['childcourse_ids'])){
                        $subskill_ids =array_merge($subskill_ids,$array_subskillidsinfo['childcourse_ids']) ;
                    }                      
                }
                if(!empty($subskill_ids) ){ 
                  $subskill_counts = count($subskill_ids); 
                  $subskillids = implode(',', $subskill_ids);
                  $where .= " AND uq.course_id IN ($subskillids)";
                }            
            }

            elseif(!empty($subskill_id)){ //subskill filter
              $where .= " AND uq.course_id=$subskill_id ";
            }else{
                //$where = "uq.user_id=".$studentrow['id'];
            }



        $str= "SELECT max(score*100/exam_marks) as student_percentage FROM user_quizes as uq where $where GROUP BY course_id"; 
          $stquizrecords = $connection->execute($str)->fetchAll('assoc');
          $student_subskill_marks= 0;
          $stRecord['student_marks'] =0;
          $student_subskill_marks_avg =0;
          $stRecord['student_marks_status']='';
          $userquiz_count = count($stquizrecords);
          if ($userquiz_count > 0) {              
            foreach ($stquizrecords as $strow) {
              $student_subskill_marks= $student_subskill_marks+ $strow['student_percentage'];
            }
            $student_subskill_marks_avg = $student_subskill_marks/$subskill_counts ;           
            $stRecord['student_marks'] = $student_subskill_marks_avg;
          }

          if($student_subskill_marks_avg <= REMEDIAL){            
            $stRecord['student_marks_status'] = "REMEDIAL";
            $stRecord['style_class'] = "remedial";            
          }
          elseif($student_subskill_marks_avg >REMEDIAL && $student_subskill_marks_avg <= STRUGGLING ){
            $stRecord['student_marks_status'] = "STRUGGLING";
            $stRecord['style_class'] = "struggling";
          }
          elseif($student_subskill_marks_avg >STRUGGLING && $student_subskill_marks_avg <= ON_TARGET ){
              $stRecord['student_marks_status'] = "ON_TARGET";
              $stRecord['style_class'] = "ontarget";
          }
          elseif($student_subskill_marks_avg >ON_TARGET && $student_subskill_marks_avg <= OUTSTANDING ){
            $stRecord['student_marks_status'] = "OUTSTANDING";
            $stRecord['style_class'] = "outstanding";
          }
          elseif($student_subskill_marks_avg >OUTSTANDING && $student_subskill_marks_avg <= GIFTED ){
            $stRecord['student_marks_status'] = "GIFTED";
            $stRecord['style_class'] = "gifted";

          }
          else{
              $stRecord['student_marks_status'] = "NOT_ATTACK";
              $stRecord['style_class'] = "no_attack";
          }

          //if API has skill_id and subskill_id filter
          if(!empty($skill_id) || !empty($subskill_id) ){
            
            if($userquiz_count>0){                                          
              $data['students'][] = $stRecord;
              $data['status'] = True; 
            }
          }
          else{  

              $data['students'][] = $stRecord;
              $data['status'] = True;  
          }
  
    } // end foreach of students of a teacher
        if(!isset($data['students'])){  //if no student list found
            $data['status'] =False;
            $data['message'] = "No student found for this seaching.";
        }

      }
      else{
        $data['status'] = False;
        $data['message'] = "No students found for subject ";
      } 


  }else{
      $data['status'] =False;
      $data['message'] = "The teacher_id and subject_id cannot be null.";
  }

  $this->set([
    'response' =>$data,
    '_serialize'=>['response']
  ]);

}


/* To find award acheive of children for parent */
  public function getAwardsofChild($child_id=null){
      $child_id = isset($_REQUEST['child_id']) ?$_REQUEST['child_id']:$child_id;

      if(!empty($child_id)){
          $connection = ConnectionManager::get('default');

           // get students quiz result for subskills
           $sql = "SELECT uq.*, score*100/exam_marks as student_result, qt.name ,  c.course_name
                   FROM user_quizes as uq
                   INNER JOIN quiz_types as qt ON qt.id=uq.quiz_type_id 
                   INNER JOIN courses as c ON c.id = uq.course_id                   
                   WHERE uq.user_id =$child_id AND uq.quiz_type_id IN (2,4,5,6) ";

            //Note -  group by on two cols because subskill(eg 19) have multiple quiz_type (1,2,3) so to get max mark in each quiz type (either in subskill quiz, challenges)
            $stQuizRecords = $connection->execute($sql)->fetchAll('assoc'); 

            if(count($stQuizRecords) > 0){
                foreach ($stQuizRecords as $stQuizRecord) { 

                    $student_marks=$stQuizRecord['student_result']; 
                    $st_result = array();                  
                  
                    if( (QUIZ_PASS_SCORE < $student_marks) AND ($student_marks >= RED_BADGE)){
                        $st_result['badge_type'] = 'red_badge';
                        $st_result['quiz_type'] = $stQuizRecord['name'] ;
                        $st_result['course_name'] = $stQuizRecord['course_name'] ;
                        $st_result['marks_percentage'] = $student_marks ;
                    }

                    if( (RED_BADGE < $student_marks) AND ($student_marks >= GREEN_BADGE)){
                        $st_result['badge_type'] = 'green_badge';
                        $st_result['quiz_type'] = $stQuizRecord['name'] ;
                        $st_result['course_name'] = $stQuizRecord['course_name'] ;
                        $st_result['marks_percentage'] = $student_marks ;
                    }

                    if( (GREEN_BADGE < $student_marks) AND ($student_marks >= STAR_BADGE)){
                        $st_result['badge_type'] = 'star_badge';
                        $st_result['quiz_type'] = $stQuizRecord['name'] ;
                        $st_result['course_name'] = $stQuizRecord['course_name'] ;
                        $st_result['marks_percentage'] = $student_marks;
                    }

                    if( (STAR_BADGE < $student_marks) AND ($student_marks >= CROWN_BADGE)){
                        $st_result['badge_type'] = 'crown_badge';
                        $st_result['quiz_type'] = $stQuizRecord['name'] ;
                        $st_result['course_name'] = $stQuizRecord['course_name'] ;
                        $st_result['marks_percentage'] = $student_marks ;
                    }

                    if(!empty($st_result)){
                      $data['status'] = True;
                      $data['student_awards_results'][] = $st_result;
                    }                                      
                } // end foreach

                if(empty($data['student_awards_results'])){
                    $data['status'] = False;
                    $data['message'] = "No Awards Achieved yet.";
                }                         
            }else{
                  $data['status'] =False;
                  $data['message']="No Records Found In Awards.";
              }      
      }else{
        $data['status'] = False;
        $data['message']= "Please set child id.";
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

