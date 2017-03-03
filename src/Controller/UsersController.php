<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
//use Cake\Datasource\ConnectionManager;

/**
 * Users Controller
 */
class UsersController extends AppController{


    public function initialize(){
        parent::initialize();
       // $conn = ConnectionManager::get('default');
        $this->loadComponent('RequestHandler');
         $this->RequestHandler->renderAs($this, 'json');

    }


    /** Index method    */
    public function index(){
              
        $user = $this->Users->find('all')->contain(['UserDetails']);        
        $this->set(array(
            'data' => $user,
            '_serialize' => ['data']
        ));
    }


   /*
        ** U1 - IsUserExists
        ** Request – String <Email> / String <Username>;

     */
    public function isUserExists($param){

       // $userTable = TableRegistry::get('Users');       
       // $query = $userTable->findAllByUsernameOrEmail($param, $param);

        //or
        //#param is either id or email
        $query_userexist = $this->Users->findAllByUsernameOrEmail($param, $param)->count();

        if($query_userexist>0){
            $data['response'] = True;
        }
        else{
            $data['response'] = False;
        }        

        $this->set(array(
            'data' => $data,
            '_serialize' => array('data')
        ));

    }


    /** 
        *  U2- getUserDetails,         
        *  Request – Int <UUID>;
    */
    public function getUserDetails($id = null){
        
        $user_record = $this->Users->find()->where(['Users.id' => $id])->count();
        if($user_record>0){
              $data['user'] = $this->Users->get($id);
              $this->set([
                'data' => $data,
                '_serialize' => ['data']
              ]);          

        }
        else{
            $data['response'] = "Record is not found";
            $this->set([
              'data' => $data,
              '_serialize' => ['data']
          ]);


        }       
    }

/*
        *  U3 - getUserProfilesByUUID 
        *   Request – String <UUID>;
*/
    public function getUserProfilesByUUID($id = null){
        //$user = $this->Users->get($id);
        $record=$this->Users->find('all')->contain(['UserDetails'])->where(['Users.id' => $id])->count();

         if($record>0){
            $data['user'] = $this->Users->find('all')->contain(['UserDetails'])->where(['Users.id' => $id]);
            $this->set([
                  'data' => $data,
                  '_serialize' => ['data']
              ]);
          }
          else{
              $data['message']='Record is not found';
              $this->set([
                    'data' => $data,
                    '_serialize' => ['data']
                ]);
            }        
    }



    /**  
        *  U4- setUserProfileByUUID
        *  Request – Int <uuid>; , String <firstName>; ,  String <lastName / Null>; , String <fatherName / Null>; , String <motherName / Null> 

    */
    public function setUserProfileByUUID($id = null){

        $user_record = $this->Users->find()->where(['Users.id'=>$id])->count();

            if ($this->request->is(['post', 'put'])) {
                if($user_record>0){
                    $user = $this->Users->get($id);
                    $user=$this->Users->patchEntity($user, $this->request->data);
                    if($this->Users->save($user)){
                        //$userdetails = TableRegistry::get('UserDetails');
                        $this->loadModel('UserDetails');
                        $userdetail=$this->UserDetails->find('all')->where(['user_id' => $id])->first();
                       // $userdetail = $userdetails->get($id);
                        
                        $userdetail= $this->UserDetails->patchEntity($userdetail, $this->request->data());
                        //if($userdetails->save($userdetail)){
                        if ($this->UserDetails->save($userdetail)) {
                              $data['message'] ="User record has been update on this id $id";
                        }                        
                    }
                      else{
                          $data['message'] = 'Unable to update the records';
                      }

                }
                else{
                    $data['message'] ="Record is not found at this id $id.";
                }               
      }
      else{
           $data['message'] ='not data is set to add';
      }

        $this->set([
            'response' => $data,
            '_serialize' => ['response']
        ]);
    }




    /* 
        ** U6- setUserMobile
        ** Request – Int &lt;UUID&gt; , Int &lt;mobileNumber&gt;
     */
    public function setUserMobile($id=null,$mobile=null) {
        if($id!=null && $mobile!=null ){
              $user_record = $this->Users->find()->where(['Users.id'=>$id])->count();
            if($user_record>0){
                $user = $this->Users->get($id);
                if(preg_match('/^[0-9]{10}$/', $mobile) ){
                    $user->mobile = $mobile;
                    if ($this->Users->save($user))
                         $data['message'] = 'True';
                    else 
                        $data['message'] = 'False';
                }else{
                     $data['message'] = 'Mobile Number is not valid';
                  }
            }else{
                $data['message'] = "No user exist on this UID";
            }
        }else{
              $data['message'] = 'User ID and mobile may be null ';
        }       
        $this->set([
            'response' => $data,
            '_serialize' => ['response']
        ]);

    }


    /* 
        ** U7- setUserEmail 
        ** Request – Int <UUID> , Int <email>;
    */
    public function setUserEmail($id=null,$email=null) {              
       
       if($id!=null && $email!=null ){
            $user_record = $this->Users->find()->where(['Users.id'=>$id])->count();
            if($user_record>0){
                $user = $this->Users->get($id);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                // Validate e-mail
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $user->email = $email;
                        if ($this->Users->save($user)) 
                              $data['message'] = 'True';
                        else
                              $data['message'] = 'False';                        
                    } else {
                              $data['message']="$email is not a valid email address";
                         }
            }else{
                $data['message'] = "No user exist on this UID";
              } 
      }else{
            $data['message'] = 'User ID and Email may be null ';
      }           
               
        $this->set([
            'response' => $data,
            '_serialize' => ['response']
        ]);

    }


    /** U8-registerUser
     * Request –  Strting <firstName>, String <lastName>, (Int <mobile / Null> , String <EmailID / Null>) , Int <roleID>
     */
    public function registerUser() {
      try {
        $message = 'Error during adding user, ';
        $data['response'] = FALSE;
        if ($this->request->is(['post', 'put'])) {
          $user = $this->Users->newEntity($this->request->data);
          if (!preg_match('/^[A-Za-z]+$/', $user['username'])) {
            $message = 'Userame name is not valid';
            throw new Exception('Pregmatch not matched for Username');
          }
          $username_exist = $this->isUserExists($user['username']);
          if ($username_exist['response']) {
            $message = 'Username already exist';
            throw new Exception($message);
          }
          if (!preg_match('/^[A-Za-z]+$/', $user['first_name'])) {
            $message = 'First name is not valid';
            throw new Exception('Pregmatch not matched for first name');
          }
          if (!preg_match('/^[A-Za-z]+$/', $user['last_name'])) {
            $message = 'Last name is not valid';
            throw new Exception('Pregmatch not matched for last name');
          }
          if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $message = 'Email is not valid';
            throw new Exception($message);
          }
          if (!preg_match('/^[0-9]{10}$/', $user['mobile'])) {
            $message = 'Mobile number is not valid';
            throw new Exception($message);
          }
          $user['status'] = 0;
          $user['created'] = $user['modfied'] = time();
          if ($this->Users->save($user)) {
            $message = 'User registered successfuly';
            $data['response'] = TRUE;
          }
        }
      } catch (Exception $e) {
        $this->log($e->getMessage() .'(' . __METHOD__ . ')', 'error');
      }
      $this->set([
        'message' => $message,
        'data' => $data,
        '_serialize' => ['message', 'data']
      ]);
    }


    /**U9-Service to update status of user to Active or Inactive  */
    public function setUserStatus($id = null, $status) {
      try {
        $message = 'Error Occured while changing status';
        $data['response'] = FALSE;
        $user = $this->Users->get($id);
        $user = $this->Users->patchEntity($user, array('id' => $id, 'status' => $status));
        if ($this->Users->save($user)) {
            $message = 'Status Changed';
            $data['response'] = TRUE;
        }
      } catch (Exceptio $e) {
        $this->log($e->getMessage() .'(' . __METHOD__ . ')', 'error');
      }
      $this->set([
        'message' => $message,
        'data' => $data,
        '_serialize' => ['message', 'data']
      ]);
    }

    /**U11 – Service to check that user still logged in or not.
     * basically to check his active session  */
    public function isUserLoggedin() {
      $this->set('loggedIn', $this->Auth->loggedIn());
    }




    /** UB10 – getUserCourses  */
    public function getUserCourses($id = null) {

       $user_record = $this->Users->find()->where(['Users.id'=>$id])->count();

      if($user_record>0){
          $usercourses = TableRegistry::get('UserCourses');
          //$menus = TableRegistry::get('Menus');
          $usercourse_records=$usercourses->find('all')->where(['user_id' => $id])->contain('Courses')->count();

            if($usercourse_records>0){
              $usercourse=$usercourses->find('all')->where(['user_id' => $id])->contain('Courses')->toArray();
              foreach($usercourse as $uc){ 
                  $ucourse['courseID']    =  $uc['course']['id'];
                  $ucourse['courseName']  = $uc['course']['course_name'];
                  $ucourse['level']       = $uc['course']['level_id'];
                  $ucourse['role']        = $uc['course']['author'];
                
                    $data['courses'][] = $ucourse;
                 } 
            }
            else{
              $data['message']= "No Courses available for this user";
            }          
      }
      else{ 
         $data['message'] = "No user exist on this id";       
      }

        $this->set([
               'data' => $data,
              '_serialize' => ['data']
            ]);
        }


    /*
        U12 – getUserRoles ( or profile you can say )
        Request – Int <UUID>
   */
     public function getUserRoles($id=null) { 
      $user_record = $this->Users->find()->where(['Users.id'=>$id])->count();

      if($user_record>0){
          $userroles = TableRegistry::get('UserRoles');
          //$roles = TableRegistry::get('Roles');
          $userrole_records=$userroles->find('all')->where(['user_id' => $id])->contain('Roles')->count();

            if($userrole_records>0){
                $userrole=$userroles->find('all')->where(['user_id' => $id])->contain('Roles')->toArray();
                foreach($userrole as $ur){ $data['roles'][] = $ur->role;  }
            }
            else{
              $data['message']='No Roles is assigned to the user';
            }
           
      }
      else{ 
         $data['message'] = "Record is not found";       
      }

      $this->set([
             'data' => $data,
            '_serialize' => ['data']
          ]);

     }



      /*
        UB13 -  getUserServices ( may be few other services he availed )
        Request – Int <UUID>
   */
     public function getUserServices($id=null) { 
      $user_record = $this->Users->find()->where(['Users.id'=>$id])->count();

      if($user_record>0){
          $usermenus = TableRegistry::get('UserMenus');
          //$menus = TableRegistry::get('Menus');
          $menu_records=$usermenus->find('all')->where(['user_id' => $id])->contain('Menus')->count();

            if($menu_records>0){
              $usermenu=$usermenus->find('all')->where(['user_id' => $id])->contain('Menus')->toArray();
              foreach($usermenu as $um){ 
                  $menu['id']= $um->menu['id'];
                  $menu['name']= $um->menu['name'];
                  $menu['validity']=  $um['validity'] ;

                  $data['services'][] = $menu;


                 } 
            }
            else{
              $data['message']= "No Service available for this user";
            }          
      }
      else{ 
         $data['message'] = "Record is not found";       
      }

        $this->set([
               'data' => $data,
              '_serialize' => ['data']
            ]);

     }


      /*
        UB14 – getUserPurchaseHistory ( user_orders) order_date
        Request -  Int<UUID> , String<startDate / Null> , String <endDate / Null>
   */
     public function getUserPurchaseHistory($id=null) { 

          $userorder_records=$this->Users->find('all')->where(['id' => $id])->contain('UserOrders')->count();
          $userorders=$this->Users->find('all')->where(['id' => $id])->contain('UserOrders')->toArray();
          
        if($userorder_records>0){
              foreach ($userorders as $userorder) {
                  $orders=$userorder->user_orders;
                  if( isset($this->request->data['start_date']) && isset($this->request->data['end_date']) ){
                        $startdate = $this->request->data['start_date'];
                        $enddate = $this->request->data['end_date'];

                        foreach ($orders as $order) {
                            $orderdate = (new Time($order->order_date))->format('Y-m-d');
                            $odt=strtotime(date($orderdate));  
                            $start_date = strtotime(date($startdate)); 
                            $end_date = strtotime(date($enddate));             

                             if($start_date >= $odt || $end_date < $odt){
                                //$data['purchases'][]= $userorder->user_orders;
                              $data['message']= "All Recors of between dates are shown";
                              $data['purchases'][]= $order;
                             }
                             else{
                                $data['message'] = "Records are not available for selected date";
                             }                      
                      } 

                      }
                      else{
                            $data['message']= "All Recors are shown";
                            $data['purchases'][]= $userorder->user_orders;

                      }

              }

         }
         else{
            $data['message'] = "No Purchase Record is found";
         }
         $this->set([
               'data' => $data,
              '_serialize' => ['data']
            ]);
     }

        
     /**
        * UB15 – setUsercourse
        * Request –  String<CourseCode>
     **/
         public function setUsercourse($uid=null,$currentcourseid=null,$newcourseid=null) { 
           //$currentcourseid= $this->request->data('current_course_id');
           //$newcourseid= $this->request->data('new_course_id');
            if($uid!=null && $currentcourseid!=null && $newcourseid!=null){

                $this->loadModel('UserCourses');
                $ucourse_count = $this->UserCourses->find()->where([
                    'UserCourses.user_id' => $uid,
                    'UserCourses.course_id' => $currentcourseid
                   ])->count();
                if($ucourse_count>0){
                      $ucourse = $this->UserCourses->find()->where([
                        'UserCourses.user_id' => $uid,
                        'UserCourses.course_id' => $currentcourseid
                       ])
                      ->first();
                      $ucourse->course_id = $newcourseid;                    
                      if ($this->UserCourses->save($ucourse)) {
                          $data['message'] = 'saved';
                      }
                      else{
                          $data['message'] = 'not saved';
                      }
                }else{
                    $data['message'] = "No user course record exist in table";
                }

            }else{
              $data['message'] = "uid,current course id or new course id cannot be null ";
            }             

              $this->set([
               'data' => $data,
              '_serialize' => ['data']
            ]);

            }



     /**
        * UB19 – getMaxUserCountbyCourseCode
        * Request –  String<CourseCode>
     **/
      public function getMaxUserCountbyCourseCode($coursecode=null) {
        $usercourses = TableRegistry::get('UserCourses');
        if($coursecode!=null){             
            $usercourses = TableRegistry::get('UserCourses');
            $uc_records=$usercourses->find('all')
                         ->contain('Courses')
                         ->select(['course_id', 'registeredUsers' => 'count(*)' ])
                         ->where(['course_code' => $coursecode])
                         ->group('course_id');

                    foreach ($uc_records as $row) {
                       $data['course'][]=$row;
                    }
                    if(empty($data['course'])){
                              $data['message'] = "No Record found for this course code";
                          }

        }
        else{            
            $uc_records=$usercourses->find('all')
                         ->contain('Courses')
                         ->select(['course_id', 'registeredUsers' => 'count(*)' ])                         
                         ->group('course_id');

                         foreach ($uc_records as $row) {
                              $data['course'][]=$row;
                          }                          

        }
          $this->set([
               'data' => $data,
              '_serialize' => ['data']
            ]);

      }


    

   
}