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

    /*U5 Service to update or set user’s password and return Boolean status. */
    public function setUserPassword($id) {
      $message = FALSE;
      if ($this->request->is(['post', 'put'])) {
        $user_validated = FALSE;
        $current_password = '';
        $default_hasher = new DefaultPasswordHasher();
        $old_password = $this->request->data['old_password'];
        $user_fields = $this->Users->find('all')->where(['Users.id' => $id])->toArray();
        foreach($user_fields as $field) {
          $current_password = $field->password;
        }
        if (!empty($current_password)) {
          $user_validated = !empty($default_hasher->check($old_password, $current_password)) ? TRUE : FALSE;
        }
        if ($user_validated) {
          if (isset($this->request->data['password'])) {
            $user = $this->Users->get($id);
            $user->password = $this->request->data['password'];
            if ($this->Users->save($user)) {
              $message = TRUE;
            }
          } else {
            $message = 'Password is not Set';
          }
        } else {
          $message = 'You have entered either wrong Id or password';
        }
        $this->set([
          'response' => $message,
          '_serialize' => ['response']
        ]);
      }
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
        $message = '';
        $data['response'] = FALSE;
        header("HTTP/1.1 500 ERROR");
        if ($this->request->is(['post', 'put'])) {
          $user = $this->Users->newEntity($this->request->data);

          if (!preg_match('/^[A-Za-z]+$/', $user['first_name'])) {
            $message = 'First name required';
            throw new Exception('Pregmatch not matched for first name');
          }

          if (!preg_match('/^[A-Za-z]+$/', $user['last_name'])) {
            $message = 'Last name required';
            throw new Exception('Pregmatch not matched for last name');
          }
           if (empty($user['username'])) {
            $message = 'Userame required';
            throw new Exception('Pregmatch not matched for Username');
          }

          $username_exist = $this->Users->find()->where(['Users.username' => $user['username']])->count();
          if ($username_exist) {
            $message = 'Username already exist';
            throw new Exception($message);
          }

          $username_email = $this->Users->find()->where(['Users.email' => $user['email']])->count();
          if ($username_email) {
            $message = 'Email already exist';
            throw new Exception($message);
          }

          if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $message = 'Email is not valid';
            throw new Exception($message);
          }

          if (empty($user['password'])) {
            $message = 'password required';
            throw new Exception('Pregmatch not matched for Username');
          }

          if (empty($user['repass'])) {
            $message = 'Enter re-password';
            throw new Exception('Pregmatch not matched for Username');
          }

          $password_hasher = new DefaultPasswordHasher();

          if ($password_hasher->check($user['repass'], $user['password'])!=1) {
            $message = 'password not match';
            throw new Exception('Pregmatch not matched for Username');
          }
          // if (!preg_match('/^[0-9]{10}$/', $user['mobile'])) {
          //   $message = 'Mobile number is not valid';
          //   throw new Exception($message);
          // }
          $user['status'] = 0;
          $user['created'] = $user['modfied'] = time();
          $userroles = TableRegistry::get('UserRoles');
          $userdetails = TableRegistry::get('UserDetails');

          if ($new_user = $this->Users->save($user)) {
            //save into user role table
            $userinfo = $this->Users->find()->select('Users.id')->where(['Users.username' => $user['username']])->limit(1);
            foreach ($userinfo as $row) {
              $user_id = $row->id;
            }
            $new_user_role = $userroles->newEntity(array('role_id' => $user['role_id'] , 'user_id' => $user_id));
            $new_user_detail = $userdetails->newEntity(array('user_id' => $user_id));
            $userdetails->save($new_user_detail);
            if ($userroles->save($new_user_role)) {
              $to = $user['email'];
              $from = 'logicdeveloper7@gmail.com';
              $subject = 'Signup: mylearinguru.com';

              $email_message = 'Dear ' . $user['first_name'] . ' ' . $user['last_name'] . "\n";
              $email_message.= 'Your username is: ' . $user['username'] . "\n";

              $source_url = isset($user['source_url']) ? $user['source_url'] : '';
              $email_message.= "\n Please activate using following url \n" . $source_url . 'parent_confirmation/' . $user_id;

              $this->sendEmail($to, $from, $subject, $email_message);

              header("HTTP/1.1 200 OK");
              $message = 'User registered successfuly';
              $data['response'] = TRUE;
            } else {
              $message = 'Some error occured during registration';
              throw new Exception('Unnable to save user roles');
            }
          } else {
            $message = 'Some error occured during registration';
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
    public function setUserStatus() {
      try {
        $message = '';
        $success = FALSE;
        if ($this->request->is('post')) {
          $id = isset($this->request->data['id']) ? $this->request->data['id'] : null;
          $status = isset($this->request->data['status']) ? $this->request->data['status'] : 1;
          if ($id != null) {
            $user = $this->Users->get($id);
            $user = $this->Users->patchEntity($user, array('id' => $id, 'status' => $status));
            if ($this->Users->save($user)) {
                $message = 'Status Changed';
                $success = TRUE;
            } else {
              $message = 'Some Error occured';
              throw new Exception('Unable to change status');
            }
          } else {
            $message = 'Please enter the User Id';
          }
        }
      } catch (Exceptio $e) {
        $this->log($e->getMessage() .'(' . __METHOD__ . ')', 'error');
      }
      $this->set([
        'status' => $success,
        'message' => $message,
        '_serialize' => ['status', 'message']
      ]);
    }

    /**U11 – Service to check that user still logged in or not.
      * basically to check his active session  */
    public function isUserLoggedin() {
      $this->loadComponent('Auth', [
          'authenticate' => [
            'Form' => [
              'fields' => [
                'username' => 'username',
                'password' => 'password',
              ]
            ]
          ],
  //          'loginAction' => [
  //            'controller' => 'Users',
  //            'action' => 'login'
  //          ]
        ]);
      $response = $status = FALSE;
      $user_info = $this->Auth->user();
      $token=null;
      if (!empty($user_info)) {
        $response = $status = TRUE;
        $token = $this->request->session()->id();
      }
      $this->set([
         'status' => $status,
         'response' => $response,
         'user_info' => $user_info,
         'token' => $token,
         '_serialize' => ['status', 'response', 'user_info', 'token']
      ]);
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


    /**U11 – login User
     * loadComponent is defiened in this function for a time being. */
    public function login() {
      try {
        $this->loadComponent('Auth', [
          'authenticate' => [
            'Form' => [
              'fields' => [
                'username' => 'username',
                'password' => 'password',
              ]
            ]
          ],
  //          'loginAction' => [
  //            'controller' => 'Users',
  //            'action' => 'login'
  //          ]
        ]);
        $status = 'false';
        $token = $message = '';
        if ($this->request->is('post')) {
          $user = $this->Auth->identify();
          if ($user) {
            if ($user['status'] != 0) {
              $this->Auth->setUser($user);
              $token = $this->request->session()->id();
              $status = 'success';
            } else {
              $message = 'Please activate your account';
            }
          } else {
            $message = 'You entered either wrong email id or password';
          }
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
      }
      $this->set([
        'user' => $user,
        'status' => $status,
        'response' => ['secure_token' => $token],
        'message' => $message,
        '_serialize' => ['user', 'status', 'response', 'message']
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

      /*U17 Request – String <CourseCode>, String<username> , Int <roleID> */
       public function setUserRoleByuserName() {
         $status = $response = FALSE;
         if ($this->request->is('post')) {
           $user_id = '';
           $username = $this->request->data['username'];
           $role_id = $this->request->data['role_id'];
           $user = $this->Users->find()->select('Users.id')->where(['Users.username' => $username])->limit(1);
           foreach ($user as $row) {
             $user_id = $row->id;
           }
           $userroles = TableRegistry::get('UserRoles');
           $new_user_role = $userroles->newEntity(array('role_id' => $role_id , 'user_id' => $user_id));
           if ($userroles->save($new_user_role)) {
             $status = 'success';
             $response = TRUE;
           }
         }
         $this->set([
           'status' => $status,
           'response' => $response,
           '_serialize' => ['status', 'response']
         ]);

       }


       /**
        * function sendEmail().
        *
        * @param String $to
        *   contains the email to whom need to send.
        *
        * @param String $from
        *   contains seders email.
        *
        * @param String $subject
        *   contains the subject.
        *
        * @param String $email_message
        *   contains the email message.
        */
       protected function sendEmail($to, $from, $subject = null, $email_message = null) {
          try {
            $status = FALSE;
            //send mail
            $email = new Email();
            $email->to($to)->from($from);
            $email->subject($subject);
            if ($email->send($email_message)) {
              $status = TRUE;
            }
          } catch (Exception $ex) {
            $this->log($ex->getMessage());
          }
          return $status;
       }

       /**
        * function setUserPreference().
        *
        * setting user prefernce under Daily, Weekly or Fornightly basis.
        */
       public function setUserPreference() {
         try {
           $warning = $status = FALSE;
           $message = '';
           if ($this->request->is('post')) {
             $post_data = $this->request->data;
             $frequency = isset($post_data['frequency']) ? $post_data['frequency'] : '';
             $mobile = isset($post_data['mobile']) ? $post_data['mobile'] : '';
             $sms_subscription = isset($post_data['sms']) ? $post_data['sms'] : 0;
             $user_id = isset($post_data['user_id']) ? $post_data['user_id'] : 0;
             if (!empty($frequency)) {
               if (!empty($mobile)) {
                 if (preg_match('/^[0-9]{10}$/', $mobile)) {
                   $user_preference = TableRegistry::get('UserPreferences');
                   $is_existed_preference = $user_preference->find()->where(['user_id' => $user_id]);
                   $preference_data = array();
                   if ($is_existed_preference->count()) {
                     foreach ($is_existed_preference as $preference_data) {
                       $preference_data->mobile = $mobile;
                       $preference_data->frequency = $frequency;
                       $preference_data->sms_subscription = $sms_subscription;
                       $preference_data->time = time();
                     }
                   } else {
                     $preference_data = $user_preference->newEntity();
                     $preference_data['mobile'] = $mobile;
                     $preference_data['user_id'] = $user_id;
                     $preference_data['frequency'] = $frequency;
                     $preference_data['sms_subscription'] = $sms_subscription;
                     $preference_data['time'] = time();
                   }
                   if ($user_preference->save($preference_data)) {
                      $user_details = TableRegistry::get('UserDetails');
                      $query = $user_details->query();
                      $result=  $query->update()
                              ->set(['step_completed'=>3])
                              ->where(['user_id' => $user_id])
                              ->execute();
                      $affectedRows = $result->rowCount();

                      if($affectedRows>0)
                              $status = TRUE;

                     if (!empty($sms_subscription)) {
                       $username = 'abhishek@apparrant.com';
                       $api_hash = '623e0140ced100da648065a6583b6cfccf29d5fb16c024be9d5723ea2fe6adf3';
                       $sms_msg = 'Your Preferences are saved successfully @team MLG';
                       $sms_response = $this->sendSms($username, $api_hash, array($mobile), $sms_msg);
                       if ($sms_response['status'] == 'failure') {
                         if (isset ($sms_response['warnings'][0]['message'])) {
                           if ($sms_response['warnings'][0]['message'] == 'Number is in DND') {
                             $sms_response['warnings'][0]['message'].= '. Please Remove DND to receive our messages';
                           }
                           $warning = TRUE;
                           $message = $sms_response['warnings'][0]['message'];
                         } else {
                           $message = 'Unable to send message, Kindly contact to the administrator';
                           throw new Exception('Error code:' . $sms_response['errors'][0]['code'] . ' Message:' .  $sms_response['errors'][0]['message']);
                         }
                       }
                     }
                   } else {
                     $message = 'Some error occured';
                     throw new Exception('Unable to save data');
                   }
                 } else {
                   $message = 'Please enter valid mobile number';
                   throw new Exception('not valid mobile number');
                 }
               } else {
                 $message = 'Please enter mobile number';
                 throw new Exception('Mobile number can not be blank');
               }
             } else {
               $message = 'Please choose the frequency for the report';
             }
           }
         } catch (Exception $ex) {
           $this->log($ex->getMessage());
         }
         $this->set([
           'status' => $status,
           'warning' => $warning,
           'message' => $message,
           '_serialize' => ['status', 'warning',  'message']
         ]);
       }

       /**
        * function sendSms().
        */
       protected function sendSms($username, $hash, $numbers, $message) {
         try {
           // Message details
           $sender = urlencode('TXTLCL');
           $message = rawurlencode($message);
           $numbers = implode(',', $numbers);
           // Prepare data for POST request
           $data = array('username' => $username, 'hash' => $hash,
             'numbers' => $numbers, "sender" => $sender, "message" => $message);
           // Send the POST request with cURL
           $ch = curl_init('http://api.textlocal.in/send/');
           curl_setopt($ch, CURLOPT_POST, true);
           curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           $json_response = curl_exec($ch);
           curl_close($ch);
           $response = json_decode($json_response, TRUE);
           if ($response['status'] == 'failure') {
             throw new Exception('unable to send message. Response: ' . $json_response);
           }
         } catch (Exception $ex) {
           $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
         }
         return $response;
       }

       /**
        * funnction setStaticContents().
        */
       public function setStaticContents() {
         $status = FALSE;
         try {
          if ($this->request->is('post')) {
            $static_contents = TableRegistry::get('StaticContents');
            $title = trim($this->request->data['title']);
            $description = addslashes($this->request->data['description']);
            $created = $modified = time();
            $contents = $static_contents->find()->where(['title' => $title]);
            if ($contents->count()) {
              foreach($contents as $content) {
                $content->description = $description;
                $content->modified = $modified;
              }
            } else {
              $content = $static_contents->newEntity(array(
                'title' => $title,
                'description' => $description,
                'created' => $created,
                'modified' => $modified
                )
              );
            }

            if ($static_contents->save($content)) {
              $status = TRUE;
              $id = $content->id;
            }
          }
         } catch (Exception $ex) {
           $this->log($ex->getMessage(). '(' . __METHOD__ . ')');
         }

         $this->set([
           'status' => $status,
           'id' => $id,
           '_serialize' => ['status', 'id']
         ]);
       }

       /**
        * funnction getStaticContents().
        */
       public function getStaticContents() {
         $content = array();
         $status = FALSE;
         $message = '';
         try {
           if ($this->request->is('post')) {
             if (empty($this->request->data['title'])) {
               $message = 'Some error occurred.Kindly retry';
               throw new Exception('Please select titles');
             }
             $title = trim($this->request->data['title']);
             $static_contents = TableRegistry::get('StaticContents');
             $static_data = $static_contents->find('all')->where(['title' => $title])->toArray();
             if ($static_data) {
              foreach($static_data as $data) {
                $content['title'] = $data->title;
                $content['description'] = stripslashes($data->description);
                $content['created'] = $data->created;
                $content['modified'] = $data->modified;
              }
              $status = TRUE;
             } else {
               throw new Exception('Some error occurred.Kindly retry');
             }
           }
         } catch (Exception $ex) {
           $this->log($ex->getMessage(). '(' . __METHOD__ . ')');
         }

         $this->set([
           'content' => $content,
           'status' => $status,
           'message' => $message,
           '_serialize' => ['content', 'status', 'message']
         ]);
       }

       /**
        * function paymentbrief().
        */
       public function getPaymentbrief() {
         $status = FALSE;
         $message = '';
         $child_info = array();
         $total_amount = 0;
         if ($this->request->is('post')) {
          try {
            $parent_id = isset($this->request->data['user_id']) ? $this->request->data['user_id'] : '';
            if (!empty($parent_id)) {

              $user_details = TableRegistry::get('UserDetails');
              $user_info = $user_details->find()->select('user_id')->where(['parent_id' => $parent_id]);
              $parent_children = array();
              foreach ($user_info as $user) {
                $parent_children[] = $user->user_id;
              }

              $connection = ConnectionManager::get('default');
              $sql = "SELECT users.first_name as user_first_name, users.last_name as user_last_name,"
                . " SUM(user_purchase_items.amount) as purchase_amount, packages.name as package_subjects, plans.name as plan_duration"
                . " FROM users"
                . " INNER JOIN user_purchase_items on user_purchase_items.user_id=users.id"
                . " INNER JOIN packages ON user_purchase_items.package_id=packages.id"
                . " INNER JOIN plans ON user_purchase_items.plan_id=plans.id"
                . " WHERE user_purchase_items.user_id IN (" . implode(',', $parent_children) . ") GROUP BY user_purchase_items.user_id";
              $user_detail_result = $connection->execute($sql)->fetchAll('assoc');
              $results = $connection->execute($sql)->fetchAll('assoc');
              if (!empty($results)) {
                $status = TRUE;

                foreach ($results as $result) {
                  $child_info[] = array(
                    'child_name' => $result['user_first_name'] . ' ' . $result['user_last_name'],
                    'package_subjects' => $result['package_subjects'],
                    'package_amount' => $result['purchase_amount'],
                    'plan_duration' => $result['plan_duration'],
                  );
                  $total_amount += $result['purchase_amount'];
                }
              } else {
                $message = 'No record found';
                throw new Exception($message);
              }
            } else {
              $message = 'Parent id cannot be blank';
              throw new Exception('Parent id is null');
            }
          } catch (Exception $ex) {
            $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
          }
         }

         $this->set([
           'status' => $status,
           'message' => $message,
           'data' => $child_info,
           'total_amount' => $total_amount,
           '_serialize' => ['status', 'data', 'total_amount', 'message']
         ]);
       }

   

       public function getGradeList() {
          $levels = TableRegistry::get('Levels')->find('all');
          foreach ($levels as $level) {
              $data['Grades'][]= $level;
          }
          $this->set([           
           'response' => $data,
           '_serialize' => ['response']
         ]);

       }

       public function getPlanList() {
          $plans = TableRegistry::get('Plans')->find('all');
          foreach ($plans as $plan) {
              $data['plans'][]= $plan;
          }
          $this->set([           
           'response' => $data,
           '_serialize' => ['response']
         ]);
       }

        public function getPackageList() {
            $packages = TableRegistry::get('Packages')->find('all');
            foreach ($packages as $package) {
                $data['package'][]= $package;
            }
            $this->set([           
             'response' => $data,
             '_serialize' => ['response']
           ]);
       }

       
       public function getStepNum($uid) {
            $steps = TableRegistry::get('UserDetails')->find('all')->where(['user_id'=>$uid]);
            $data['step']= 0;
            foreach ($steps as $step) {
                $data['step']= $step;
            }
            $this->set([           
             'response' => $data,
             '_serialize' => ['response']
           ]);
       }


       public function logout() {
            $this->loadComponent('Auth', [
          'authenticate' => [
            'Form' => [
              'fields' => [
                'username' => 'username',
                'password' => 'password',
              ]
            ]
          ],
            
        ]);
            $this->Auth->logout();

            $this->set([           
             'response' => true,
             '_serialize' => ['response']
           ]);
       }

      
     

      public function setCountOfChildrenOfParent($id,$child_count){
          if(isset($id) && isset($child_count) ){            
              $user_details = TableRegistry::get('UserDetails');
              $query = $user_details->query();
              $result=  $query->update()
                      ->set(['no_of_children' => $child_count, 'step_completed'=>1])
                      ->where(['user_id' => $id])
                      ->execute();
              $affectedRows = $result->rowCount();

              if($affectedRows>0)
                $data['status']="True";
              else
                $data['status']="False";

              $this->set([           
             'response' => $data,
             '_serialize' => ['response']
           ]);
          }
      }

      public function getCountOfChildrenOfParent($pid){
        if(isset($pid)){
          $user_details = TableRegistry::get('UserDetails')->find('all')->where(['user_id'=>$pid]);
          //$rowcounts=$user_details->rowCount();
          $data['number_of_children']=0;
          foreach($user_details as $user_detail){            
            $data['number_of_children'] = $user_detail['no_of_children'];
          }
          $this->set([           
             'response' => $data,
             '_serialize' => ['response']
           ]);
        }
      }


      public function getChildrenListOfParent($pid){
            if(isset($pid)){
                $added_children = TableRegistry::get('UserDetails')->find('all')->where(['parent_id'=>$pid])->count();
               // $rowcounts=$user_details->rowCount();
                $data['added_children'] = $added_children;                 
            }else{
              $data['message'] = 'Set parent_id';
            }

        $this->set([           
                 'response' => $data,
                 '_serialize' => ['response']
               ]);

      }

/*
      public function getChildrenDetails($pid){
          $childRecords=TableRegistry::get('UserDetails')->find('all')->where(['parent_id'=>$pid])->contain(['Users']);
          $data['children_name']="";
          foreach ($childRecords as $childRecord) {
            $fname=$childRecord->user['first_name'];
            $lname=$childRecord->user['last_name'];
            $data['children_name'][]=$fname.' '.$lname;          
          }

          $this->set([           
                 'response' => $data,
                 '_serialize' => ['response']
               ]);

      }*/

       public function addChildrenRecord() {          

           if ($this->request->is('post')) { 
              //$postdata=$this->request->data;
              $postdata['username']=$this->request->data['username'];
              $postdata['first_name']=$this->request->data['first_name'];
              $postdata['last_name']=$this->request->data['last_name'];
              
              $postdata['parent_id']=$this->request->data['parent_id'];              
              $postdata['emailchoice']=$this->request->data['emailchoice'];
              $postdata['email']=$this->request->data['email'];
             

              $postdata['school']=$this->request->data['school'];
              $postdata['role_id']=$this->request->data['role_id'];
              $postdata['status']=$this->request->data['status'];
             
              $postdata['dob']=$this->request->data['dob'];
              $postdata['created']=$this->request->data['created'];
              $postdata['modified']=$this->request->data['created'];
              
              $postdata['package_id']=$this->request->data['package_id'];
              //$postdata['level_id']=$this->request->data['level_id'];
              $postdata['plan_id']=$this->request->data['plan_id'];
                  
               $users=TableRegistry::get('Users');
               $user_details=TableRegistry::get('UserDetails');
               $user_roles=TableRegistry::get('UserRoles');
               $user_courses=TableRegistry::get('User_Courses');
               $user_purchase_items=TableRegistry::get('User_purchase_items');

               $subtotal=0;

            // parent information by $pid
                  $parent_records= $users->find('all')->where(["id"=>$postdata['parent_id'] ]);
                  foreach ($parent_records as $parent_record) {
                      $parentinfo['email']=$parent_record['email'];
                      $parentinfo['first_name']=$parent_record['first_name'];
                      $parentinfo['last_name']=$parent_record['last_name'];
                  }                  

                // to find discount in selected package
                $packs= TableRegistry::get('Packages')->find('all')->where(["id"=>$postdata['package_id'] ]);
                foreach ($packs as $pack) {
                    $upurchase['discount']=$pack['discount'];
                    $postdata['discount']=$pack['discount'];
                    $discount_type=$pack['type'];                    
                } 

                // find number of month in selected plan
                $plans= TableRegistry::get('Plans')->find('all')->where(["id"=>$postdata['plan_id'] ]);
                foreach ($plans as $plan) {
                    $num_months=$plan['num_months'];                                     
                }         

                 // check emailchoice is yes/no 
                $pass= rand(1, 1000000); 
                $default_hasher = new DefaultPasswordHasher();
                $password=$default_hasher->hash($pass);
                $postdata['password']  = $password;

                $from = 'logicdeveloper7@gmail.com';
                    $subject ="Your Child authenticatation";
                    $email_message="Hello ". $parent_record['first_name']. $parent_record['last_name'].

                        "
                          Your Child Login Credential in My Learning Guru is 
                          User Name :".$postdata['username'] ." 
                          Password : ".$pass;

                if($postdata['emailchoice']==0){ $to=$parent_record['email'];}
                else{ $to=$postdata['email'];  }

                //1. User Table
                $new_user = $this->Users->newEntity($postdata);
                if ($result=$this->Users->save($new_user)) { 
                    $this->sendEmail($to, $from, $subject,$email_message); 
                    $postdata['user_id']  = $result->id;

                    //2.  User Details Table
                    $new_user_details = $user_details->newEntity($postdata);
                    if ($user_details->save($new_user_details)) {

                        //3. User Roles Table
                        $new_user_roles = $user_roles->newEntity($postdata);                        
                      if ($user_roles->save($new_user_roles)) {


                        // Courses and Price calculation
                        $courses=$this->request->data['courses'];
                        foreach ($courses as $course_id => $name) {
                          $postdata['course_id']=$course_id;

                          //4. User Courses Table
                          $new_user_courses = $user_courses->newEntity($postdata);
                            if ($user_courses->save($new_user_courses)) {

                               $courseamount=TableRegistry::get('Courses')->find('all')->where(['id'=>$course_id]);
                                foreach ($courseamount as $camount) {
                                    $cramount=$camount['price']; 
                                    $upurchase['course_price']=$camount['price'];
                                    $postdata['course_price']=$camount['price'];
                                                                                       
                                }

                                if($discount_type=="fixed"){
                                   $upurchase['amount']=($cramount-$upurchase['discount'])*($num_months);
                                   $postdata['amount']=($cramount-$postdata['discount'])*($num_months);
                                }
                                if($discount_type=="percent"){
                                  $upurchase['amount']=($cramount-($cramount*($upurchase['discount'])*0.01))*($num_months);
                                  $postdata['amount']=($cramount-($cramount*($postdata['discount'])*0.01))*($num_months);
                                }


                                //5. User Purchase Item Table
                                $new_user_purchase_items = $user_purchase_items->newEntity($postdata);
                                if ($user_purchase_items->save($new_user_purchase_items)) {$data['status']="True";}
                                else{ 
                                  $data['status']='flase';
                                  $data['message']=" Not able to save data in User Purchase Item Table Table";}
                                      
                            }
                            else{
                            $data['status']='flase';
                            $data['message']=" Not able to save data in User Courses Table";}
                        }

                      }
                      else{ 
                        $data['status']='flase';
                      $data['message']=" Not able to save data in User Roles Table";}
                    }
                    else{ 
                      $data['status']='flase';
                      $data['message']=" Not able to save data in User Details Table"; }
                    

                    //$data['status']='True';

                }else{
                  $data['status']='flase';
                  $data['message']="Not able to add data in Users table";

                }
            


        }

          else{
            $data['status']='No data is send/post to save';

          }

          $this->set([           
                 'response' => $data,
                 '_serialize' => ['response']
               ]);
           

       }       


       public function priceCalOnCourse(){
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);
        $id_list="0";
        foreach ($request as $key => $value) {
          if($value!=""){
              $id_list=$id_list.','.$key;
              //$data['value'][]=$value;
            }
        }
        $course_ids='('.$id_list.')';        
        $connection = ConnectionManager::get('default');
        $sql = "SELECT sum(price) as amount From courses where id IN $course_ids";
            
          $results = $connection->execute($sql)->fetchAll('assoc');
            foreach ($results as $result) { 
                if($result['amount']!=null){$data['amount']=$result['amount'];}
                else{$data['amount']=0;}              
            }
        $this->set([           
                 'response' => $data,
                 '_serialize' => ['response']
               ]);       

       }

      /*
       * function saveCardToPaypal().
       */
       public function saveCardToPaypal() {
         $message = $response = '';
         $status = FALSE;
         $data = $name = array();
         $Acess_token = 'A21AAFwsARNl_pFPq-V2Tkv0q2XaY4oZyaFf22YmmDDAc2cVHq0HNfTuV_Ck0-bfMivsZPJcd4L0Z2su0fe5iBWNMRk8hi0QA';
         if ($this->request->is('post')) {
           try {
             if (empty($this->request->data['user_id'])) {
               $message = 'Please login to submit payment';
               throw new Exception($message);
             }
             if (isset($this->request->data['name']) && !empty($this->request->data['name'])) {
               $data['first_name'] = $this->request->data['name'];
               $name =  explode(' ', $this->request->data['name']);
             }
             if (count($name) >= 2) {
               $data['first_name'] = @current($name);
               $data['last_name'] = @end($name);
             }
             if (isset($this->request->data['card_number']) && !empty($this->request->data['card_number'])) {
               $data['number'] = $this->request->data['card_number'];
             } else {
               $message = 'Card Number is required';
               throw new Exception($message);
             }
             if (isset($this->request->data['expiry_month']) && !empty($this->request->data['expiry_month'])) {
               $data['expire_month'] = $this->request->data['expiry_month'];
             } else {
               $message = 'Expiry Month is required';
               throw new Exception($message);
             }
             if (isset($this->request->data['expiry_year']) && !empty($this->request->data['expiry_year'])) {
               $data['expire_year'] = $this->request->data['expiry_year'];
             } else {
               $message = 'Expiry Year is required';
               throw new Exception($message);
             }
             if (isset($this->request->data['cvv']) && !empty($this->request->data['cvv'])) {
               if (strlen($this->request->data['cvv']) > 4) {
                 $message = 'not valid CVV';
                 throw new Exception($message);
               }
               $data['cvv2'] = $this->request->data['cvv'];
             } else {
               $message = 'CVV is required';
               throw new Exception($message);
             }
             $data['type'] = isset($this->request->data['card_type']) && !empty($this->request->data['card_type']) ?
               $this->request->data['card_type'] : 'visa';
             $data['external_customer_id'] = 'customer' . '_' . time();
             $ch = curl_init();
             curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/vault/credit-cards/");
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
             curl_setopt($ch, CURLOPT_POST, 1);
             $headers = array();
             $headers[] = "Content-Type: application/json";
             $headers[] = "Authorization: Bearer $Acess_token";
             curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
             $response = curl_exec($ch);
             $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
             if (curl_errno($ch)) {
               $message = 'Some error occured';
               throw new Exception(curl_error($ch));
             }
             curl_close ($ch);
             switch ($httpCode) {
               case 401 : $message = 'Some error occured. Unable to proceed, Kindly contact to administrator';
                          throw new Exception('Unauthorised Access');
                 break;

               case 500 : $message = 'Some error occured. Unable to proceed, Kindly contact to administrator';
                          throw new Exception('Internal Server Error Occured');
                 break;
             }
             $user_orders = TableRegistry::get('UserOrders');
             $response = json_decode($response, TRUE);
             if  (isset($response['state']) && $response['state'] == 'ok') {
               $message = 'card added successfully';
               $status = TRUE;
             }
             if (isset($response['name']) && $response['name'] == 'VALIDATION_ERROR') {
               $message = $response['details'][0]['issue'];
               $error_fields = explode(',', $response['details'][0]['field']);
               foreach ($error_fields as $error_field) {
                 switch($error_field) {
                   case 'number' :  $message = "Card number is not valid \n";
                    break;
                 }
               }
             }
             $user_order_data = $user_orders->find()->where(['user_id' => $this->request->data['user_id']]);
             $user_order = array();
             if ($user_order_data->count()) {
               foreach($user_order_data as $user_order) {
                  $user_order->card_response = $message;
                  $user_order->order_date= time();
               }
             } else {
               $order = array(
                 'user_id' => $this->request->data['user_id'],
                 'amount' => $this->request->data['amount'],
                 'discount' => isset($this->request->data['discount']) ? $this->request->data['discount'] : '',
                 'order_date' => time(),
                 'trial_period' => 1,
                 'card_response' => $message,
                 'card_id' => isset($response['id']) ? $response['id'] : '',
               );
               $user_order = $user_orders->newEntity($order);
             }
             if (!$user_orders->save($user_order)) {
               $status = FALSE;
               throw new Exception('unable to save data, Kindly retry again');
             } else {
               // start- update the user state
               $user_details = TableRegistry::get('UserDetails');
               $query = $user_details->query();
               $result=  $query->update()
                 ->set(['step_completed'=>4])
                 ->where(['user_id' => $this->request->data['user_id'] ])
                 ->execute();
               $affectedRows = $result->rowCount();
               if ($affectedRows > 0) {
                 $data['status']="True";
               } else {
                 $data['status']="False";
               }
             }
             //end- update the user
           } catch (Exception $ex) {
             $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
           }
         }
         $this->set([
           'status' => $status,
           'message' => $message,
           '_serialize' => ['status', 'message',]
         ]);
       }

       /**
        * function getChildrenDetails().
        * @param String $pid
        *   parent Id.
        */
       public function getChildrenDetails($pid, $cid = null) {

        
         $childRecords = TableRegistry::get('UserDetails')->find('all')->where(['parent_id' => $pid])->contain(['Users']);
         $data = array();
         foreach ($childRecords as $childRecord) {
           $fname = $childRecord->user['first_name'];
           $lname = $childRecord->user['last_name'];
           $data[] = array(
             'user_id' => $childRecord['user_id'],
             'parent_id' => $childRecord['parent_id'],
             'children_name' => $fname . ' ' . $lname,
             'username' => $childRecord['username'],
             'email' => $childRecord['email'],
             'mobile' => $childRecord['mobile'],
           );

         }

         /** ordering of data **/
         if (!empty($cid) && !empty($data)) {
           $temp_array = array();
           foreach ($data as $key => $child) {
             if ($child['user_id'] == $cid) {
               $temp_array = $child;
               unset($data[$key]);
             }
           }
           array_unshift($data, $temp_array);
         }

         $this->set([
           'response' => $data,
           '_serialize' => ['response']
         ]);
       }
   /***
    * This api is used for getting offers.
    * @return offer details.
    * @author Shweta Mishra <shweta.mishra@incaendo.com>
    * @link http://www.incaendo.com 
    * @copyright (c) 2017, Incaendo Technology Pvt Ltd.
    * 
    * **/    
   public function getOffers() { 
    try{
      $offer_list = array();
      $current_date = Time::now();
      $offers = TableRegistry::get('Offers');
      $offers_detail = $offers->find('all')->where(['validity >=' => $current_date])->toArray();
      $i=0;
      foreach ($offers_detail as $offersDetails) {
        if(isset($offersDetails->title) && !empty($offersDetails->title) ) {
          $offer_list[$i]['title'] = $offersDetails->title;
          $offer_list[$i]['image'] = $offersDetails->image;
          $date = explode(' ',$offersDetails->validity);
          $offer_list[$i]['validity'] = date('d M Y',strtotime($date[0]));
          $i++;
        } else {
          throw new Exception('Unable to find offers');
        }
      } 
    } catch (Exception $e) {
      log($e->getMessage(), '(' . __METHOD__ . ')');
    }
    $this->set([
      'response' => $offer_list,
      '_serialize' => ['response']
    ]);
   }
}
