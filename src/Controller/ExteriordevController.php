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

/**
 * Users Controller
 */

class ExteriordevController extends AppController{   
    
    public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
         $this->RequestHandler->renderAs($this, 'json');
    }


    /** Index method    */
    public function index(){              
        $data['message'] ="APi is not authenticated. Please use authentication key";      
        $this->set(array(
            'data' => $data,
            '_serialize' => ['data']
        ));
    }


    // The external used API - to get the Question List API
    //POST parametesr $subject=null, $grade_id=null,$standrd=null,$limit= -1
  public function getMenifestQuestions(){
    
     /*$curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]);*/

     $vendor_authenticate =$this->externalUsersAuthVerification() ;
   
     if($vendor_authenticate['status']=="Ture"){        
              
        $data['auth_status'] = $vendor_authenticate['message'];
        $user_id = isset($vendor_authenticate['key'] ) ? $vendor_authenticate['key'] :null;
        $course_ids=array();        


        // to get the question list
         $course_id = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : null;
         $grade_id = isset($_REQUEST['grade']) ? $_REQUEST['grade'] : null;        

         $standard=isset($_REQUEST['standard'])?$_REQUEST['standard']: null;
       
        $skills=isset($_REQUEST['skills'])?$_REQUEST['skills']:NULL;
        $target=isset($_REQUEST['target'])?$_REQUEST['target']:NULL;
       // $country=isset($_REQUEST['country'])?$_REQUEST['country']:NULL;
        $dok=isset($_REQUEST['dok'])?$_REQUEST['dok']:NULL;
        $difficulty=isset($_REQUEST['difficulty'])? $_REQUEST['difficulty']:NULL;
        $type=isset($_REQUEST['type'])?$_REQUEST['type']:NULL;
        $limit=isset($_REQUEST['limit'])?$_REQUEST['limit']: null; // number of question

        $course_ids[]=$course_id;

        if(!empty($course_id) && !empty($grade_id) && !empty($standard) && !empty($limit) ){
            
            //check shared course id is subject/skill/subskill 
             $connection = ConnectionManager::get('default');         
             $sql = "SELECT c.level_id,c.course_code,c.course_name,cd.course_id,cd.parent_id
                   FROM  courses as c, course_details as cd
                   WHERE c.id=cd.course_id AND cd.parent_id=$course_id AND c.level_id=$grade_id";


              $skillRecords = $connection->execute($sql)->fetchAll('assoc');              
              if($skillRecords > 0){ 
               // $data['status'] = "Ture";              
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
                     }
                }
                
                
                $subj = $course_ids;
                $grade = $grade_id;              
                $data['questions']=$this->getQuestionsList($subj,$grade,$standard,$limit,$skills,$target,$dok,$difficulty,$type,$user_id);              
              }
              else{
                  $data['message']="No Records Found"; // No skill for courses
                  $data['status']="False";
              }            
        }else{
          $data['message'] = "Subject, Grade, Standard and limit (number of question) cannot be null. Please set these parameters in Post request.";
          $data['status']="False";
        }       
     }else{
        $data['status']="False";
        $data['message']=$vendor_authenticate['message'];

     }

  //$data= base64_encode(serialize($data));
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
                       $data['status'] = "Ture";
                       $data['quiz_id'] =  $qresult->id;
                      $data ['message'] ="quiz is created.";
                    }
                    else{
                        $data['status'] = "False";
                        $data ['message'] ="Not able to create quiz item. Please consult with admin";
                    }
                 }             
            }
            else{
                $data['status'] = "False";
                $data ['message'] ="Not able to create quiz. Please consult with admin";
              }      

        }
        else{
          $data['status'] = "False";
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
              where qm.course_id IN '.$subj.' and qm.grade_id="'.$grade.'"';


           if($standard !== NULL){
                $standard=explode("|",$standard);               
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
        if($limit !== null){ $sql.="ORDER BY RAND() limit ".$limit; }
        

        $question_info=array();
        $quiz_marks = 0;
        $ques_ids = array();
         $questionRecords = $connection->execute($sql)->fetchAll('assoc');              
              if($questionRecords){ 
                $data['status'] = "Ture";                             
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
                     // $question_info['answers'] =  $answerArray; 
                      $answerArray =[];                   
                   }else{
                    $question_info ['answer_message'] = "No Answer Found for this question";
                   }

                   //Question Collections
                   $questions[]=$question_info; 
                 }      
           
                  // Create Quiz                
                   $quiz= $this->createQuiz($limit,$ques_ids,$quiz_marks,$user_id);
                   if($quiz['status']=="Ture"){
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
                $data['status'] = "False";
                $data['message'] = "No Record Found";
              }
           
      return ($data);


    }



// API - Pass either username/plateform

public function externalUsersAuthVerification(){ 
         
         //$access_token=isset($_REQUEST['token'])? $_REQUEST['token'] :null;
          $token = $this->request->header('token') ;
          $access_token = isset($token)  ? $token : null;
          if(!empty($access_token)){ 
             
             $plateform_name= isset($_REQUEST['platform']) ? $_REQUEST['platform']: null;
             $username =isset($_REQUEST['username']) ? $_REQUEST['username'] : null;
            
            if($plateform_name!=null && $access_token!=null){ 
              $users =TableRegistry::get('vendors')->find('all')->where(['plateform'=>$plateform_name,'access_token'=>$access_token]);

              if($users->count() ){          
                  foreach ($users as $vrow) {                     
                    $data['status']="Ture"; 
                    $data['vendor_key'] = $vrow['id'];
                    $data['message']="You are authenticated";                    
                }                
              }
              else{
                  $data['status']="False";
                  $data['message']="either Platform or token is wrong;";
              }

        }// users of the vendor
        elseif($username!=null && $access_token!=null){
            
            $ExternalUsers =TableRegistry::get('external_users');
            $exusers= $ExternalUsers->find('all')->where(['username'=> $username, 'access_token'=>$access_token]);

              if($exusers->count() > 0 ){          
                  foreach ($exusers as $vrow) {
                      $external_userid = $vrow['id'];
                      $extuserstatus = $vrow['status'];

                      // Subscribe student/user
                      if($extuserstatus ==1){
                          $udetails = TableRegistry::get('UserDetails')->find()->where(['external_user_id' => $external_userid]);
                          // get user id in users table
                          if($udetails->count() > 0){
                                foreach ($udetails as $user_detail) { 
                                  $data['status']="Ture";                     
                                  $data['message']="You are authenticated";                      
                                  $data['key'] = $user_detail->user_id;                                                 
                                }                           
                          }else{
                              $data['message']="No userdetails for this user";
                              $data['status']="False";
                              $user_id = null ;
                          }
                      }
                      else{
                          $data['status']="False";
                          $data['message']="Your subscription has been expire. Please Renew it again";                        
                      }
                }                  
              }else{
                        $data['status']="False"; 
                        $data['message']="Wrong username and Access Token. Please choose valid"; 
                   } 

        }else{
                $data['status']="False"; 
                $data['message']="Please post username/platform."; 
         } 
            
    }else{
      $data['status'] = "False";
      $data['message'] = "token is not set in header of your request";

    }


      return($data);    

  }



// External API to get the user Quiz Response
  public function getMenifestQuizResponse(){

      $vendor_authenticate =$this->externalUsersAuthVerification() ; 
         
      if($vendor_authenticate['status']=="Ture"){     
          $data['auth_status'] = $vendor_authenticate['message'] ;
          $user_id = isset( $vendor_authenticate['key']) ? $vendor_authenticate['key'] : null;
          $quiz_id=isset($_REQUEST['quiz_id'])?$_REQUEST['quiz_id']:null;

          if(!empty($user_id) && !empty($quiz_id) ){
              $data['status']="Ture";
              $UserQuizResponses = TableRegistry::get('UserQuizResponses') ;         
              $userQuizResults = $UserQuizResponses->find()->where(['user_id' => $user_id,'exam_id'=>$quiz_id]);
              $numRecords=$userQuizResults->count();

              if($numRecords>0){
                  foreach ($userQuizResults as $qresult) {                     
                      $data['results'][]=$qresult;                     
                  }           
                
            }else{
                $data['message']="No record Found for quiz : $quiz_id. ";
                $data['status']="False";
            }            
           }
          else{
            $data['status']="False";
           $data['message'] = "$quiz_id cannot be null. Please post quiz_id" ;
          }

      }else{
          $data['status']="False";
          $data['message'] = $vendor_authenticate['message'] ;

      }

      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));


  }


  //ApI to get vendor students
  public function getVendorUsers(){
      
      $vendor_authenticate =$this->externalUsersAuthVerification() ;
      if($vendor_authenticate['status']=="Ture"){          
          $data['auth_status'] = $vendor_authenticate['message'] ;
          $vendor_id = isset( $vendor_authenticate['vendor_key']) ? $vendor_authenticate['vendor_key'] : null; 

          if(!empty($vendor_id) ){

            $external_users = TableRegistry::get('ExternalUsers')->find()->where(['vendor_id' => $vendor_id]);         
            $numRecords=$external_users->count();

              if($numRecords>0){
                  foreach ($external_users as $extuser) {                     
                      $vendor_user['username']=$extuser['username']; 
                      $vendor_user['Subscription Start Date']= date('d M Y',strtotime( $extuser['subscription_start_date'] ));
                      $vendor_user['Subscription End Date']=date('d M Y',strtotime( $extuser['subscription_end_date'] ));

                      $interval = $extuser['subscription_start_date'] -> diff( $extuser['subscription_end_date'] );
                      //$months_of_subscription  = $interval->format('%m months and %d days');
                      $months_of_subscription  = $interval->format('%m months');
      
                       
                        $current_date = date_create(date('Y-m-d')) ;
                        $end_date = date_create(date('Y-m-d', strtotime($vendor_user['Subscription End Date'])) );
                        $remaining_interval = date_diff($current_date, $end_date);                      
                        //$reamining_days = $remaining_interval->format('%R%a days');
                        $reamining_days = $remaining_interval->format('%a days');
                        if($current_date < $end_date){
                            $remaining_time = $reamining_days ;
                        }elseif($current_date > $end_date) {
                            $remaining_time = "Your Subscription has been expired.";
                            $update_status =TableRegistry::get('external_users')->query()->update()->set(['status' =>0 ])->where(['id'=> $extuser['id'] ])->execute();
                        }else{
                            $remaining_time = "Today your subscription will expire." ;
                        }
                                   
                      $vendor_user['Subscription Months'] = $months_of_subscription;
                      $vendor_user['Remaining Time for Subscription'] =$remaining_time;
                      //$vendor_user['user enrolled with MLG']=date('d M Y',strtotime( $Vendor['created'] )); 
                      $vendor_user['status']=$extuser['status']; 
                      $data['users'][] = $vendor_user;
                  }

                }

          }else{
              $data['status']="False";
              $data['message'] = 'Platform id is not valid' ;
          }
      }
      else{
          $data['status']="False";
          $data['message'] = $vendor_authenticate['message'] ;
      }


      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));


  }


    // set auth token in header as 'token', POST json for users array in json as 'data{'users':[{username,subscription_start_date, subscription_end_date} ] }  
   public function setEnrollUsers(){

    /*pr($acceptHeader = $this->request->header('auth')); die;*/   

    $token = $this->request->header('token') ;
    $access_token = isset($token)  ? $token : null;
    if(!empty($access_token)){

        $user_vendor =TableRegistry::get('vendors')->find('all')->where(['access_token'=>$access_token]);
        if($user_vendor->count() >0 ){
              foreach ($user_vendor as $vendor) { 

                  $vendor_id = $vendor->id;
                  $extUser['vendor_id'] = $vendor->id;
                  $extUser['vendor_name']=$vendor->plateform;                                                                     
              }

              // read json to get the list of request users
               $jsondata_users = isset( $this->request->data['data']['users'] ) ? $this->request->data['data']['users'] : null;
               if( !empty($jsondata_users) ){
                    $ExternalUsers =TableRegistry::get('external_users');
                    $Users = TableRegistry::get('Users');
                    $user_details=TableRegistry::get('UserDetails');
                    foreach ($jsondata_users as $juser) {

                        $username = isset($juser['username'] ) ? $juser['username'] :null;
                        $sub_startdate = isset($juser['subscription_start_date'])? $juser['subscription_start_date'] : null ;
                        $sub_enddate = isset($juser['subscription_end_date'])? $juser['subscription_end_date'] : null ;  

                        if(!empty($username) && !empty($sub_startdate)  && !empty($sub_enddate) ){
                            $extUser['username'] = $username;                           
                            $extUser['first_name'] = $username ;
                            $extUser['access_token'] = $access_token ;
                            $extUser['subscription_start_date'] = $sub_startdate ;
                            $extUser['subscription_end_date'] = $sub_enddate;
                            
                            $extUser['status'] = 1;                        
                            $extUser['created'] = time();
                            $extUser['modfied'] = time();

                            $exusers= $ExternalUsers->find('all')->where(['username'=> $username, 'access_token'=>$access_token, 'vendor_id'=> $vendor_id]); 
                            if($exusers->count() > 0 ){          
                                $data ['records'][] = "user - $username, is already enroll with us.";                
                             }else{
                                // Insert/add Records in External Users                      
                                 $new_ExternalUsers = $ExternalUsers->newEntity($extUser);
                                if ($exSavedUser= $ExternalUsers->save($new_ExternalUsers)) {
                                    $extUser['external_user_id']  = $exSavedUser->id;
                                    $extUser['username'] = $username.'-'.$exSavedUser->id;                      
                          
                                    //save data in users                          
                                    $new_Users = $Users->newEntity($extUser);
                                    if ($userSaved = $Users->save($new_Users)) {
                                       $extUser['user_id']  = $userSaved->id;
                              
                                        $new_user_details = $user_details->newEntity($extUser);
                                        if ($user_details->save($new_user_details)) {
                                          $data['status']="Ture";
                                         // $data['key'] = $userSaved->id; 
                                          $data['records'][]="user $username -  saved/enroll with MLG";
                                      }
                                      else{
                                          $data['status']="False"; 
                                          $data['message']="Opps... Record  $username is not inserted--User Details. Please Try Again";  //  User Details Table
                                      }
                                  }
                                  else{
                                  $data['status']="False"; 
                                  $data['message']="Opps... Record is not inserted--User . Please Try Again";  // Users Table
                                }

                            }else{
                              $data['status']="False"; 
                              $data['message'][]="$suername -- not saved ";  // External Table 
                          }

                        }

                        }else{
                          $data['status'] ="False" ;
                          $data['message'] ="Parameter username, subscription_start_date or subscription_end_date cannot be null";

                        }

                    }
                }else{
                    $data['status'] ="False";
                    $data['message'] ="No users data is post in json";
               }


        }else{
          $data['status'] ="False";
          $data['message'] ="Access token is not authenticated on MLG. Please use correct token.";
        }
    }else{
      $data['status'] = "False";
      $data['message'] = "token is not set in your header request";

    }


    $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));
  
   }



   public function activateUserSubscription(){
      $token = $this->request->header('token') ;
      $access_token = isset($token)  ? $token : null;
      
      if(!empty($access_token)){

            $user_vendor =TableRegistry::get('vendors')->find('all')->where(['access_token'=>$access_token]);
              if($user_vendor->count() >0 ){
                foreach ($user_vendor as $vendor) { 
                    $vendor_id = $vendor->id ;
                    $extUser['vendor_id'] = $vendor->id;
                    $extUser['vendor_name']=$vendor->plateform;                                                                     
                }

                // read json to get the list of request users
                 $jsondata_users = isset( $this->request->data['data']['users'] ) ? $this->request->data['data']['users'] : null;
                 if( !empty($jsondata_users) ){
                      $ExternalUsers =TableRegistry::get('external_users');
                      

                      foreach ($jsondata_users as $juser) {

                        $username = $juser['username'];
                        $sub_startdate = isset($juser['subscription_start_date'])? $juser['subscription_start_date'] : null ;
                        $sub_enddate = isset($juser['subscription_end_date'])? $juser['subscription_end_date'] : null ;
  

                        if(!empty($username) && !empty($sub_startdate)  && !empty($sub_enddate) ){
                              
                               $query = $ExternalUsers->query();
                               $result=  $query->update()
                                    ->set(['subscription_start_date' => $sub_startdate, 'subscription_end_date'=>$sub_enddate, 'status' =>1, 'modfied'=>time() ])
                                    ->where(['username' => $username, 'vendor_id' => $vendor_id])
                                    ->execute();
                                $affectedRows = $result->rowCount();

                                if($affectedRows>0){
                                    $data['status']="Ture";
                                    $data['records'][] = "$username - updated for Activation/Renewal";
                                }
                                else{
                                    $data['status']="Ture";
                                    $data['records'][] = "$username - Not updated";
                                  }

                        }else{
                            $data['status'] ="False" ;
                            $data['message'] ="Parameter username, subscription_start_date or subscription_end_date cannot be null";

                        }
                  }
                      
                  }else{
                      $data['status'] ="False";
                      $data['message'] ="No user record is set in json request.";
                  }
            }else{
                $data['status'] ="False";
                $data['message'] = "Token is not valid. Please send valid token in header request.";
            }
      }
      else{
          $data['status'] ="False";
          $data['message'] = "token is not set in your header request";
      }


      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));

   }



   public function cancellUserSubscription(){
      $token = $this->request->header('token') ;
      $access_token = isset($token)  ? $token : null;
      
      if(!empty($access_token)){

            $user_vendor =TableRegistry::get('vendors')->find('all')->where(['access_token'=>$access_token]);
              if($user_vendor->count() >0 ){
                foreach ($user_vendor as $vendor) { 
                    $vendor_id = $vendor->id ;
                    $extUser['vendor_id'] = $vendor->id;
                    $extUser['vendor_name']=$vendor->plateform;                                                                     
                }

                // read json to get the list of request users
                 $jsondata_users = isset( $this->request->data['data']['users'] ) ? $this->request->data['data']['users'] : null;
                 if( !empty($jsondata_users) ){
                      $ExternalUsers =TableRegistry::get('external_users');
                      

                      foreach ($jsondata_users as $juser) {
                        $username = isset($juser['username'] )? $juser['username'] : null ;
                        //$sub_startdate = isset($juser['subscription_start_date'])? $juser['subscription_start_date'] : null ;
                        //$sub_enddate = isset($juser['subscription_end_date'])? $juser['subscription_end_date'] : null ;
  

                        if(!empty($username) ){                              
                               $query = $ExternalUsers->query();
                               $result=  $query->update()
                                    ->set(['status' =>0, 'modfied' => time() ])
                                    ->where(['username' => $username, 'vendor_id' => $vendor_id])
                                    ->execute();
                                $affectedRows = $result->rowCount();

                                if($affectedRows>0){
                                    $data['status']="Ture";
                                    $data['records'][] = "$username - updated for Cancellation/Deactivation";
                                }
                                else{
                                    $data['status']="False";
                                    $data['records'][] = "$username - Not updated. Because Record is not found for this user";
                                  }

                        }else{
                            $data['status'] ="False" ;
                            $data['message'] ="username  cannot be null";
                        }
                  }
                      
                }else{
                      $data['status'] ="False";
                      $data['message'] ="No user record is set in json request.";
                  }
            }else{
                $data['status'] ="False";
                $data['message'] = "Token is not valid. Please send valid token in header request.";
            }
      }
      else{
          $data['status'] ="False";
          $data['message'] = "token is not set in your header request";
      }


      $this->set(array(
        'response' => $data,
        '_serialize' => ['response']
      ));

   }




// Function for get the corect answer
  public function setUserQuizOption() {
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



// API to call curl 
  /*way of calling $curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]); */
public function curlPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, Ture);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}










}
