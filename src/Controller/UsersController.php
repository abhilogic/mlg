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
use DateTime;
use App\Controller\PaymentController;

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


       /** site_landing method    */
    public function contactUs(){

        if ($this->request->is('post')) {

             $user_name= isset($this->request->data['name'])? $this->request->data['name']:null;
             $user_email= isset($this->request->data['email'])? $this->request->data['email']:null;
             $user_phone= isset($this->request->data['phone'])? $this->request->data['phone']:null;
             $user_message= isset($this->request->data['contact_text'])?$this->request->data['contact_text']:null;

            $data['message'][] = "";

            if( !empty($user_name)  &&  !empty($user_email) && !empty($user_phone) && !empty($user_message) ){

                $user_email = filter_var($user_email, FILTER_SANITIZE_EMAIL);
                // Validate e-mail
                    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL) === false) {
                        $to="anita@apparrant.com";
                        $subject="Contact Us";
                        $headers = "From: info@mylearninguru.com" . "\r\n" .
                          "CC: $user_email";
                        $user_message= "Hi 

                         $user_name is trying to contact with you for his query. Please Find the detail below :

                          Email : $user_email
                          Mobile: $user_phone

                          Query:
                          ".$user_message;

                       // $sent_mail=mail($to,$subject,$user_message,$headers=null);

                        $sent_mail = $this->sendEmail($to, $user_email, $subject, $user_message);
                        if($sent_mail==TRUE){ 
                            $data['status'] = "True";
                          $data['message']= "Thank You for contacting us."; 
                        }
                        else{ 
                              $data['status'] = "False";
                              $data['message']= "Oppss mail is not send";                         
                      }       
                    }else {
                      $data['status'] = 'False';
                      $data['message'] ="$email is not a valid email address";                    
                    }
            }else{
               $data['status'] = 'False';
               $data['message'] = "Please fill all contact information properly. No field can be empty.";
            }

        }
        else {
            $data['status'] = 'False';
            $data['message'] = "Please fill contact form information.";
        }

        $this->set(array(
            'data' => $data,
            '_serialize' => array('data')
        ));
      }

      /**
       * getUserPreferences().
       *
       *
       * To get user preferences.
       */
      public function getUserPreferences($uid = null) {
        $data = array();
        $status = FALSE;
        $message = '';
        $user_Preferences_table = TableRegistry::get('UserPreferences');
        $data = $user_Preferences_table->find()->where(['user_id' => $uid]);
        if ($data->count()) {
          $status = TRUE;
        } else {
          $message = 'No record found';
        }
        $this->set(array(
            'status' => $status,
            'data' => $data->first(),
            'message' => $message,
            '_serialize' => array('status', 'data', 'message')
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
    public function getUserDetails($id = null, $function_call = FALSE){
      $user_record = $this->Users->find()->where(['Users.id' => $id])->count();
      $data['user_all_details'] = array();
      if ($user_record > 0) {
        $data['user'] = $this->Users->get($id);
        $data['user_all_details'] = $this->Users->find('all')->where(['user_id' => $id])->contain(['UserDetails']);
        $data['image_directory'] = Router::url('/', true);
      } else {
        $data['response'] = "Record is not found";
      }
      if ($function_call) {
        if (!empty($data['user_all_details'])) {
          $temp = $data['user_all_details'];
          foreach ($temp as $user_details) {
            $data['user_all_details'] = $user_details->toArray();
          }
        }
        return $data;
      }
      $this->set([
        'data' => $data,
        '_serialize' => ['data']
      ]);
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
          $message = 'Wrong current password';
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
          $user['status'] = 0;
          $user['trial_period_end_date'] = $user['subscription_end_date'] = date('Y-m-d' ,(time() + 60 * 60 * 24 * $user['subscription_days']));
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
              $email_message.= "\n Please activate using following url \n" . $source_url . 'email/confirmation/' . $user_id;

              $this->sendEmail($to, $from, $subject, $email_message);

              $message = 'User registered successfuly';
              $data['response'] = TRUE;
            } else {
              $entity = $this->Users->get($user_id);
              $this->Users->delete($entity);
              $message = 'Some error occured during registration';
              throw new Exception('Unable to save user roles');
            }
          } else {
            $message = 'Some error occured during registration';
            throw new Exception('Unable to save user');
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
            throw new Exception('User id missing');
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
        $role_id = '';
        $user = array();
        $status = $first_time_login = 'false';
        $child_info = array();
        $token = $message = $role_id = '';
        $warning = $no_of_child = 0;
        if ($this->request->is('post')) {
          $user = $this->Auth->identify();
          if ($user) {
            $user_roles = TableRegistry::get('UserRoles');
            $valid_user = $user_roles->find('all')->where(['user_id' => $user['id']]);
            $role_id = $valid_user->first()->role_id;
            if (strtotime($user['created']) == strtotime($user['modfied'])) {
              $first_time_login = TRUE;
            }

            //saving user login time for user time calculation
            $user_login_sessions = TableRegistry::get('user_login_sessions');
            $user_login_sessions_row = $user_login_sessions->find()->where(['user_id' =>  $user['id'], 'check_out IS NULL']);
            if ($user_login_sessions_row->count() > 0) {
              foreach ($user_login_sessions_row as $value) {
                $check_in = (array)$value->check_in;
                $chcek_in_dateTime = new DateTime($check_in['date']);
                $current_dateTime = date('Y-m-d H:i:s');
                $chcek_out_dateTime = new DateTime($current_dateTime);

                $value->check_out = $current_dateTime;
                $diff = $chcek_out_dateTime->diff($chcek_in_dateTime);

                $seconds = $diff->s;
                $minutes = $diff->i;
                $hours = $diff->h;
                $total_seconds = $seconds + ($minutes*60) + ($hours*60*60) + ($diff->days*24*60*60);

                $value->time_spent = $total_seconds;
                break;
              }
              $user_login_sessions->save($value);
            }
            $user_new_login_session = $user_login_sessions->newEntity(array(
              'user_id' => $user['id'],
              'check_in' => time()
            ));
            $user_login_sessions->save($user_new_login_session);

            if ($user['status'] != 0) {
              $subscription_end_date = !empty($user['subscription_end_date']) ? strtotime($user['subscription_end_date']) : 0;
              if ($user['status'] != 2) {
                //If subscription is over
                if ((time() - (60 * 60 * 24))  > $subscription_end_date) {
                  $user['status'] = 2;
                  $this->Users->query()->update()->set($user)->where(['id' => $user['id']])->execute();
                  $message = 'Your subscription period is over';
                  $warning = 1;
                  throw new Exception('subscription period is over, Account Expired for user id: ' .  $user['id']);
                }

                //check if any of the child has eneded with subcription period.
                if ($role_id == PARENT_ROLE_ID) {

                  $children = $this->getChildrenDetails($user['id'], null, TRUE);;
                  if (!empty($children)) {
                    $no_of_child = count($children);
                    foreach ($children as $child) {
                      $subscription_end_date = strtotime($child['subscription_end_date']);
                      if ((time() - (60 * 60 * 24))  > $subscription_end_date) {
                        $warning = 1;
                        $child_info[] = $child;
                      }

                      // if subcription is going to expire after the defiend alert days
                      // the activity will be stored to notification.
                      // "ALERT_BEFORE_SUBSCRIPTION_EXPIRE" contain the no of day after child
                      //  subscription will expire.
                      $date_1 = date_create();
                      $date_2 = date_create($child['subscription_end_date']);
                      $diff = date_diff($date_1,$date_2);
                      if ($diff->d < ALERT_BEFORE_SUBSCRIPTION_EXPIRE) {
                        $payment_controller = new PaymentController();
                        $param['url'] = Router::url('/', true) . 'users/setUserNotifications';
                        $param['return_transfer'] = TRUE;
                        $param['post_fields'] = array(
                          'user_id' => $child['user_id'],
                          'role_id' => STUDENT_ROLE_ID,
                          'bundle' => 'SUBSCRIPTIONS',
                          'category_id' => NOTIFICATION_CATEGORY_SUBSCRIPTIONS,
                          'sub_category_id' => $child['user_id'],
                          'title' => 'SUBSCRIPTION EXPIRE',
                          'description' => 'expire in ' . $diff->d . ' day(s)'
                        );
                        $param['json_post_fields'] = TRUE;
                        $param['curl_post'] = 1;
                        $payment_controller->sendCurl($param);
                      }
                    }
                  }
                }

                //updating login time.
                $this->Users->query()->update()->set(['modfied' => time()])->where(['id' => $user['id']])->execute();

                if ($valid_user->count()) {
                  $this->Auth->setUser($user);
                  $token = $this->request->session()->id();
//                  $this->request->session()->write('Auth.User.token', $token);
                  $status = 'success';
                } else {
                  $user = array();
                  $message = "You are not authenticated to login into this page";
                }
              } elseif ($user['status'] == 2 && $role_id == STUDENT_ROLE_ID) {
                $warning = 1;
              } else {
                $message = 'Your subscription period is over';
              }
            } else {
              $message = 'Please activate your account';
            }
          } else {
            $message = 'You entered either wrong user id or password';
          }
        } else {
          $message = 'Either Usename or password is null';
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
      }
      $this->set([
        'user' => $user,
        'role_id'=>$role_id,
        'status' => $status,
        'warning' => $warning,
        'child_info' => $child_info,
        'no_of_child' => $no_of_child,
        'first_time_login' => $first_time_login,
        'response' => ['secure_token' => $token],
        'message' => $message,
        '_serialize' => ['user', 'status', 'warning', 'no_of_child', 'child_info', 'response', 'message','role_id' , 'first_time_login']
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
            }else{
              $status = FALSE;
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
               if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
                 $message = 'Please enter valid mobile number';
                 throw new Exception('not valid mobile number');
               }
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
       public function getPaymentbrief($child_id = null) {
         $status = FALSE;
         $message = '';
         $child_info = array();
         $total_amount = 0;
         if ($this->request->is('post') || !empty($params)) {
          try {
            $parent_id = isset($this->request->data['user_id']) ? $this->request->data['user_id'] : '';

            if (!empty($parent_id)) {
              $parent_children = array();
              // for a single child
              if (!empty($child_id) && is_numeric($child_id)) {
                $parent_children = array($child_id);
              } else {
                $user_details = TableRegistry::get('UserDetails');
                $user_info = $user_details->find()->select('user_id')->where(['parent_id' => $parent_id]);
                foreach ($user_info as $user) {
                  $parent_children[] = $user->user_id;
                }
              }

              $connection = ConnectionManager::get('default');
              $sql = "SELECT users.id as user_id, users.first_name as user_first_name, users.last_name as user_last_name,"
                . " SUM(user_purchase_items.amount) as purchase_amount, packages.name as package_subjects, plans.name as plan_duration"
                . " FROM users"
                . " INNER JOIN user_purchase_items on user_purchase_items.user_id=users.id"
                . " INNER JOIN packages ON user_purchase_items.package_id=packages.id"
                . " INNER JOIN plans ON user_purchase_items.plan_id=plans.id"
                . " WHERE user_purchase_items.user_id IN (" . implode(',', $parent_children) . ") "
                . " AND user_purchase_items.item_paid_status != 1"
                . " GROUP BY user_purchase_items.user_id";
              $results = $connection->execute($sql)->fetchAll('assoc');
              if (!empty($results)) {
                $status = TRUE;

                foreach ($results as $result) {
                  $child_info[] = array(
                    'child_id' => $result['user_id'],
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
         if (!empty($params)) {
           return $child_info;
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

        public function setStepNum($uid=null,$step_num=null) {

           $user_id = isset($_GET['user_id'] ) ? $_GET['user_id'] : $uid;
           $step_num = isset($_GET['step_num'] ) ? $_GET['step_num'] : $step_num;
      
          if(!empty($user_id) && !empty($step_num) ){          
              $userdetails = TableRegistry::get('UserDetails');
              $result =$userdetails->query()->update()->set(['step_completed' => $step_num])->where(['user_id' => $user_id] )->execute();

              $affectedRows = $result->rowCount();
              if($affectedRows>0){
                  $data['status'] = "True";
                  $data['message'] ="updated;";
              }else{
                  $data['status'] = "False";
                  $data['message'] ="not updated;";
              }

          }else{
            $data['status'] = "False";
            $data['message'] ="user_id and step_num cannot be null";
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
         $user_id = $this->request->data['user_id'];
         $user_login_sessions = TableRegistry::get('user_login_sessions');
         $user_login_sessions_row = $user_login_sessions->find()->where(['user_id' =>  $user_id, 'check_out IS NULL']);
         if ($user_login_sessions_row->count() > 0) {
           foreach ($user_login_sessions_row as $value) {
             $check_in = (array)$value->check_in;
             $chcek_in_dateTime = new DateTime($check_in['date']);
             $current_dateTime = date('Y-m-d H:i:s');
             $chcek_out_dateTime = new DateTime($current_dateTime);

             $value->check_out = $current_dateTime;
             $diff = $chcek_out_dateTime->diff($chcek_in_dateTime);

             $seconds = $diff->s;
             $minutes = $diff->i;
             $hours = $diff->h;
             $total_seconds = $seconds + ($minutes*60) + ($hours*60*60) + ($diff->days*24*60*60);

             $value->time_spent = $total_seconds;
             break;
           }
           $user_login_sessions->save($value);
         }
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


    public function addChildrenRecord() {          
      try{         

          if($this->request->is('post')) { 
              //$postdata=$this->request->data;
            $data['message'][]="";                              
            $time = time();
              //username validation ******
              $postdata['username']=isset($this->request->data['username'])?$this->request->data['username']:"";
              if(!empty($postdata['username'])){
                  $username_exist = $this->Users->find()->where(['Users.username' => $this->request->data['username'] ])->count();
                  if ($username_exist) {
                      $data['message'][0] = 'Username already exist';                      
                  }
              }else{
                $data['message'][0]="User Name is required to child login";
              }

              //email validation ********
              $postdata['emailchoice']=isset($this->request->data['emailchoice'])?$this->request->data['emailchoice']:1;              
              $postdata['email']=isset($this->request->data['email'])?$this->request->data['email']:"";
              if(!empty($postdata['email'])&& $postdata['emailchoice']==1){
                  $email_exist = $this->Users->find()->where(['Users.email' => $this->request->data['email'] ])->count();
                  if ($email_exist) {
                      $data['message'][1] = 'Email is already exist';                      
                  }
                  if (!filter_var($postdata['email'], FILTER_VALIDATE_EMAIL)) {
                     $data['message'][1] = 'Email is not valid';
               }

              }else if(empty($postdata['email']) && $postdata['emailchoice']==1){
                $data['message'][1]="Please Add Your Child Email to send password over there.";
              }
              else{
                $postdata['email']="";
              }
              
              $postdata['first_name']=isset($this->request->data['first_name'])? $this->request->data['first_name']:$data['message'][2]="First name is require";             
              $postdata['last_name']=isset($this->request->data['last_name'])?$this->request->data['last_name']:$data['message'][3]="Last Name is require"; 
              $postdata['parent_id']=isset($this->request->data['parent_id'])? $this->request->data['parent_id']:$data['message'][4]="The Parent ID has been expired. please Login Again";                          
              $postdata['school']=isset($this->request->data['school'])? $this->request->data['school']:$data['message'][5]="School Name is require";        
              $postdata['dob']=isset($this->request->data['dob'])? $this->request->data['dob']:$data['message'][6]="Please select Date of Birth";

              $postdata['role_id']=$this->request->data['role_id'];
              $postdata['status']=$this->request->data['status'];
              //$postdata['created']=$this->request->data['created'];
              //$postdata['modfied']=$this->request->data['created'];
              //$postdata['order_date']=$this->request->data['created'];



              $postdata['created']=time();
              $postdata['modfied']=time();
              $postdata['order_date']=time();


              $postdata['promocode_id']=isset($this->request->data['vcode'])?$this->request->data['vcode']:'0'; 

              $postdata['package_id']=isset($this->request->data['package_id'])?$this->request->data['package_id']:$data['message'][7]="Please select package for your child";
              $postdata['plan_id']=isset($this->request->data['plan_id'])?$this->request->data['plan_id']:$data['message'][8]="Please slelect Plans for your child";
              $postdata['level_id']=$this->request->data['level_id'];


              $data['message'] = array_filter($data['message']); // to check array is empty array_filter return(0, null)
              if(empty($data['message']) || $data['message']=="" ){
                     $users=TableRegistry::get('Users');
                     $user_details=TableRegistry::get('UserDetails');
                     $user_roles=TableRegistry::get('UserRoles');
                     $user_courses=TableRegistry::get('UserCourses');
                     $user_purchase_items=TableRegistry::get('UserPurchaseItems');
                     $subtotal=0;
                     $count=0;
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
                           // $upurchase['discount']=$pack['discount'];
                            $postdata['discount']=$pack['discount'];
                            $postdata['no_of_subjects']=$pack['no_of_subjects'];                          
                            $discount_type=$pack['type'];                    
                       } 

                         // to check selected validation on courses
                          //count(array_filter($array));
                          $selected_courses_count=count( array_filter($this->request->data['courses']));
                          if($selected_courses_count>$postdata['no_of_subjects'] ){
                            $data['message'][9]="Your selected pakcage is for ". $postdata['no_of_subjects'] .
                                            " courses.You have selected ". $selected_courses_count." So please choose course accordingly";
                            $data['status']="false";
                            throw new Exception("selected course is greater");
                          }
                           if($selected_courses_count==0 ||$selected_courses_count==null){
                            $data['message'][9]="Please select courses";
                            $data['status']="false";                            
                            throw new Exception("please select courses cannot ");
                          }

                          //promo code Records
                          $pcodes= TableRegistry::get('PromoCodes')->find('all')->where(["id"=> $postdata['promocode_id'] ]);
                          $pcodesRecordsCount=$pcodes->count();
                            $pcode_discount=0;
                            $pcode_discountType="";
                             if($pcodesRecordsCount>0){
                                $pcode_discount=$pcodes->first()->discount;
                                $pcode_discountType=$pcodes->first()->discount_type;
                             }
                          
                                                    

                         // find number of month in selected plan
                         $plans= TableRegistry::get('Plans')->find('all')->where(["id"=>$postdata['plan_id'] ]);
                         foreach ($plans as $plan) {
                             $num_months=$plan['num_months'];                                     
                          }         

                       // check emailchoice is yes/no 
                        $pass= rand(1, 1000000);
                        $postdata['password']  = $pass;

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

                      // parent and child having same subscription_end_date;
                      $parent_info = $this->getUserDetails($postdata['parent_id'], TRUE);
                      $parent_subscription = (array)$parent_info['user_all_details']['subscription_end_date'];
                      $parent_subscription_end_date = $parent_subscription['date'];
                      $postdata['trial_period_end_date'] = $postdata['subscription_end_date'] = strtotime($parent_subscription_end_date);

                      $new_user = $this->Users->newEntity($postdata);
                      if ($result=$this->Users->save($new_user)) { 
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
                                      //$upurchase['course_price']=$camount['price'];
                                      $postdata['course_price']=$camount['price'];                                                        
                                  }
                                  if($discount_type=="fixed"){
                                     //$upurchase['amount']=($cramount-$upurchase['discount'])*($num_months);
                                     $postdata['amount']=($cramount-$postdata['discount'])*($num_months);
                                  }
                                  if($discount_type=="percent"){
                                    //$upurchase['amount']=($cramount-($cramount*($upurchase['discount'])*0.01))*($num_months);
                                    $postdata['amount']=($cramount-($cramount*($postdata['discount'])*0.01))*($num_months);
                                  }

                                  // amount alter if promo code is applied
                                  if($pcode_discountType=="fixed"){
                                    $postdata['amount']=($postdata['amount'])-($pcode_discount);               
                                  }
                                  if($pcode_discountType=="percent"){
                                  $postdata['amount']= $postdata['amount']-($postdata['amount']*($pcode_discount*0.01));
                                  }
                                  $postdata['order_timestamp'] = $time;
                                  $postdata['item_paid_status'] = 0;
                                //5. User Purchase Item Table
                                $new_user_purchase_items = $user_purchase_items->newEntity($postdata);
                                if ($user_purchase_items->save($new_user_purchase_items)) {$data['status']="True";}
                                else{ 
                                  $data['status']='false';
                                  $data['message']="Not able to save data in User Purchase Item Table Table";
                                  throw new Exception("Not able to save data in User Purchase Item Table Table");
                                }
                             }
                            else{
                              $data['status']='false';
                              $data['message']=" Not able to save data in User Courses Table";
                              throw new Exception("Not able to save data in User Roles Table");
                          }
                        }

                      }
                      else{ 
                        $data['status']='false';
                        $data['message']=" Not able to save data in User Roles Table";
                        throw new Exception("Not able to save data in User Roles Table");
                      }
                    }
                    else{ 
                      $data['status']='false';
                      $data['message']="Not able to save data in User Details Table";
                      throw new Exception("Not able to save data in User Details Table");
                   }                   
                //$data['status']='True';
                }else{
                  $data['status']='false';
                  $data['message']="Not Able to add data in user table";
                  throw new Exception("Not Able to add data in user table");

            }
          }else{
              $data['status']="False";
             // $data['message']="All are validate ";
          }

        } else{ $data['status']='No data is send/post to save'; }

        }
        catch (Exception $ex) {
           $this->log($ex->getMessage());
        }
          if (strtoupper($data['status']) == 'TRUE') {
            $this->sendEmail($to, $from, $subject,$email_message);
          }
          $this->set([           
              'response' => $data,
               '_serialize' => ['response']
          ]);         

       }       

       public function promocode($prcode=null){       
            if($prcode!=null){
                $pcodes=TableRegistry::get('PromoCodes')->find('all')->where(['voucher_code'=>$prcode]);
                $recordcount=$pcodes->count();
                
                if($recordcount>0){
                    foreach ($pcodes as $pcode) {
                       $data['promocode']=$pcodes;
                       $data['status']="True";
                    }
                }else{ 
                  $data['message']="Invalid code. Use valid code";
                  $data['status']="false";
                 } 
            }else{ 
              $data['message']="no promod code is set";
              $data['status']="false";
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
         $status = $payment_status = FALSE;
         $data = $name = array();
         $trial_period = TRUE;
         $payment_controller = new PaymentController();
         if ($this->request->is('post')) {
           try {
             if (empty($this->request->data['user_id'])) {
               $message = 'Please login to submit payment';
               throw new Exception($message);
             }
             $access_token = $payment_controller->paypalAccessToken();
             $validation = $payment_controller->validatePaymentCardDetail($this->request->data, $access_token);
             $response = $validation['response'];
             $data = $validation['data'];
             $message = $validation['message'];

             if (!empty($message)) {
                throw new Exception($message);
             }
             $card_token = $external_cutomer_id = '';
             if  (isset($response['state']) && $response['state'] == 'ok') {
               $data['card_response'] = $message;
               $data['card_token'] = $response['id'];
               $data['external_customer_id'] = $response['external_customer_id'];
               $parent_id = $this->request->data['user_id'];

               $user_ids = $this->request->data['children_ids'];
               foreach ($user_ids as $child_id) {

               //If Parent is on trial period, child will also be in trial period.
               $parent_info = $this->Users->get($parent_id)->toArray();
               $parent_subcription = (array)$parent_info['subscription_end_date'];
               $subcription_end_date = $parent_subcription['date'];
               if (time() > strtotime($subcription_end_date)) {
                 $trial_period = FALSE;
               }

                //deactivate previously selected billing
                $param['url'] =  Router::url('/', true) . 'users/deactivateUserSubscription';
                $param['return_transfer'] = TRUE;
                $param['post_fields'] = array(
                  'user_id' => $parent_id,
                  'child_id' => $child_id
                );
                $param['json_post_fields'] = TRUE;
                $param['curl_post'] = 1;
                $curl_response = $payment_controller->sendCurl($param);
                if (!empty($curl_response['curl_exec_result'])) {
                  $curl_exec_result = json_decode($curl_response['curl_exec_result'], TRUE);
                  if ($curl_exec_result['status'] == FALSE && $curl_exec_result['is_billing_id_present'] == TRUE) {
                    $message = 'unable to deactivate previous billing';
                    throw new Exception($message);
                  }
                } else {
                  $message = 'unable to deactivate previous billing';
                  throw new Exception('curl not completed successfully');
                }

                $billing_plan = $payment_controller->createBillingPlan($child_id, $access_token, $trial_period);
                if (!empty($billing_plan['plan_id'])) {
                   $plan_id = $billing_plan['plan_id'];

                   //total amount.
                   $data['total_amount'] = $billing_plan['total_amount'];
                   $plan_status = $payment_controller->activatePayaplPlan($plan_id, $access_token);
                   if ($plan_status == 'ACTIVE') {
                     $user_id = $this->request->data['user_id'];

                     //same order date and time will be saved on user orders table.
                     $data['order_date'] = date('Y-m-d', time());
                     $order_timestamp = $data['purchase_item_order_timestamp'] = $billing_plan['order_timestamp'];
                     $data['trial_period'] = 1;

                     $billing_response = $payment_controller->billingAgreementViaCreditCard($user_id, $data, $plan_id, $access_token);
                     if ($billing_response['status'] == TRUE) {
                       $payment_status = TRUE;
                       $status = TRUE;

                       // Updated user purchase items table.
                       $data['paypal_plan_id'] = $plan_id;
                       $data['paypal_plan_status'] = $plan_status;
                       $data['billing_id'] = $billing_response['result']['id'];
                       $data['billing_state'] = $billing_response['result']['state'];
                       $data['trial_period'] = $trial_period;

                       // update user orders table.
                       $this->setUserOrders($user_id, $child_id, $data);

                       //setting user purchase items status as paid.
                       $user_purchase_items_table = TableRegistry::get('user_purchase_items');
                       $query = $user_purchase_items_table->query();
                       $query->update()
                        ->set(['item_paid_status' => 1])
                        ->where(['order_timestamp' => $order_timestamp, 'user_id' => $child_id])
                        ->execute();

                       // update child subscription period
                       $param['url'] =  Router::url('/', true) . 'users/updateUserSubscriptionPeriod';
                       $param['return_transfer'] = TRUE;
                       $param['post_fields'] = array(
                        'user_id' => $parent_id,
                        'child_id' => $child_id
                       );
                       $param['json_post_fields'] = TRUE;
                       $param['curl_post'] = 1;
                       $payment_controller->sendCurl($param);

                       // update user courses
                       $user_courses_update_curl['url'] = Router::url('/', true) . 'users/updateUserCourseDetailsByUserPurchaseItems';
                       $user_courses_update_curl['return_transfer'] = TRUE;
                       $user_courses_update_curl['post_fields'] = array('user_id' => $child_id);
                       $user_courses_update_curl['json_post_fields'] = TRUE;
                       $user_courses_update_curl['curl_post'] = 1;
                       $payment_controller->sendCurl($user_courses_update_curl);

                       $message = 'card added successfully';
                     } else {
                       $message = "Some Error occured, Kindly ask to administrator. ERRCODE: PY2Bill";
                       throw new Exception('Unable to process billing agreement');
                     }
                   } else {
                     $message = "Some Error occured, Kindly ask to administrator. ERRCODE: PY2ACTPLN";
                     throw new Exception('unable to activate plan');
                   }
                 } else {
                   $message = "Some Error occured, Kindly ask to administrator. ERRCODE: PY2CTEPLN";
                   throw new Exception('Unable to create Plan');
                 }
               }
             }
             if (!$payment_status) {
               $status = FALSE;
               $message = 'Payment not completed';
               throw new Exception('Payment not compleated succesfully');
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
       public function getChildrenDetails($pid, $cid = null, $function_call = FALSE) {

        
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
             'created_date' => $childRecord['user']['created'],
             'subscription_end_date' => $childRecord['user']['subscription_end_date'],
             'trial_period_end_date' => $childRecord['user']['trial_period_end_date'],
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
           if (!empty($temp_array)) {
             array_unshift($data, $temp_array);
           }
         }

         if ($function_call) {
           return $data;
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
     try {
       $status = FALSE;
       $message = '';
       $offer_list = array();
       if ($this->request->is('post')) {
        $param = $this->request->data;
        if (isset($param['user_type']) && !empty($param['user_type'])) {
          $connection = ConnectionManager::get('default');
          $user_type = strtoupper($param['user_type']);
          $sql = 'SELECT * FROM coupons WHERE user_type = ' . "'" . $user_type . "'"
               . ' AND applied_for = "OFFER"'
               . ' AND validity >= ' . "'" . time() . "'";
          $offer_list = $connection->execute($sql)->fetchAll('assoc');
          if (!empty($offer_list)) {
            $status = TRUE;
            $avail_coupon_sql =  'SELECT * FROM coupon_avail_status WHERE user_id = ' . "'" . $param['user_id'] . "'";
            $avail_offers = $connection->execute($avail_coupon_sql)->fetchAll('assoc');
            if (!empty($avail_offers)) {
              $temp = array();
              foreach ($avail_offers as $avail) {
                $temp[$avail['coupon_id']] = $avail;
              }
            }
            foreach($offer_list as &$offer) {
              $offer['status'] = '';
              if (isset($temp[$offer['id']])) {
                $offer['status'] = $temp[$offer['id']]['status'];
              }
            }
          } else {
           $message = 'No offers available';
           throw new Exception('No Record found');
          }
        } else {
          $message = 'user type missing';
         throw new Exception('No user Type given for offers');
        }
       }
     } catch (Exception $e) {
       $this->log($e->getMessage(). '(' . __METHOD__ . ')');
     }
     $this->set([
       'status' => $status,
       'message' => $message,
       'result' => $offer_list,
       '_serialize' => ['status', 'message', 'result']
     ]);
   }

   /**
    * function getUserPurchaseDetails().
    *
    * $uid :  user Id
    */
   public function getUserPurchaseDetails($uid, $function_call = FALSE, $recent_order = TRUE) {
     $status = FALSE;
     $message = '';
     $purchase_details = array();
     try {
       if (empty($uid)) {
         $message = 'Please add child';
         throw new Exception('User Id is empty');
       }
       $connection = ConnectionManager::get('default');
       $sql = "SELECT users.id as user_id, users.first_name as user_first_name, users.last_name as user_last_name,"
         . " user_purchase_items.amount as purchase_amount, user_purchase_items.level_id as level_id,"
         . " user_purchase_items.course_id, user_purchase_items.order_date as order_date,"
         . " user_purchase_items.order_timestamp as order_timestamp, user_purchase_items.item_paid_status as paid_status,"
         . " packages.name as package_subjects, packages.id as package_id, "
         . " plans.id as plan_id, plans.name as plan_duration, plans.num_months as plan_num_months,"
         . " courses.course_name"
         . " FROM users"
         . " INNER JOIN user_purchase_items on user_purchase_items.user_id=users.id"
         . " INNER JOIN packages ON user_purchase_items.package_id=packages.id"
         . " INNER JOIN plans ON user_purchase_items.plan_id=plans.id"
         . " INNER JOIN courses ON user_purchase_items.course_id=courses.id"
         . " WHERE user_purchase_items.user_id IN (" . $uid . ")";
       if ($recent_order) {
         $subquery = "(SELECT MAX(order_timestamp) FROM user_purchase_items"
         . " WHERE user_id = $uid)";
         $sql.= " AND user_purchase_items.order_timestamp = $subquery";
       }

       $purchase_details_result = $connection->execute($sql)->fetchAll('assoc');
       if (!empty($purchase_details_result)) {
         $status = TRUE;
         $total_amount = 0;
         foreach ($purchase_details_result as $purchase_result) {
           $purchase_details['user_id'] = $purchase_result['user_id'];
           $purchase_details['user_first_name'] = $purchase_result['user_first_name'];
           $purchase_details['user_last_name'] = $purchase_result['user_last_name'];
           $purchase_details['package_id'] = $purchase_result['package_id'];
           $purchase_details['package_subjects'] = $purchase_result['package_subjects'];
           $purchase_details['plan_id'] = $purchase_result['plan_id'];
           $purchase_details['plan_duration'] = $purchase_result['plan_duration'];
           $purchase_details['plan_num_months'] = $purchase_result['plan_num_months'];
           $purchase_details['level_id'] = $purchase_result['level_id'];
           $purchase_details['order_date'] = @current(explode(' ', $purchase_result['order_date']));
           $purchase_details['db_order_date'] = $purchase_result['order_date'];
           $purchase_details['order_timestamp'] = $purchase_result['order_timestamp'];
           $total_amount = $total_amount + $purchase_result['purchase_amount'];
           $purchase_details['package_amount'] = $total_amount;
           $purchase_details['paid_status'] = $purchase_result['paid_status'];
           $purchase_details['purchase_detail'][] = array(
              'purchase_amount' => $purchase_result['purchase_amount'],
              'course_id' => $purchase_result['course_id'],
              'course_name' => $purchase_result['course_name'],
           );
         }
       } else {
         $message = 'No record found';
       }
     } catch(Exception $e) {
       $this->log($e->getMessage(), '(' . __METHOD__ . ')');
     }
     if ($function_call) {
       return $purchase_details;
     }
     $this->set([
      'status' => $status,
      'message' => $message,
      'response' => $purchase_details,
      '_serialize' => ['status', 'message', 'response']
    ]);
   }

   /**
    * function upgrade().
    *
    */
   public function upgrade() {
     $response = array('status' => FALSE, 'message' => '', 'order_timestamp' => '');
     if ($this->request->is('post')) {
       try {
        $total_amount = 0;
        $data = $this->request->data;
        $user_id = isset($data['user_id']) ? $data['user_id'] : '';
        if (!empty($user_id)) {
          $child_id = $data['child_id'];
          $courses = $data['updatedCourses'];
          $package = $data['updatedPackage'];
          $plan = $data['updatedPlan'];

          //backing up data
          $connection = ConnectionManager::get('default');
          $col_names = 'user_id, course_id, plan_id, package_id, level_id, discount,'
            . ' promocode_id, course_price, amount, order_date, order_timestamp, item_paid_status';
          $backup_sql = 'INSERT INTO user_purchase_history (' . $col_names . ') SELECT '. $col_names
            .' FROM user_purchase_items where user_id =' . $child_id;
          if (!$connection->execute($backup_sql)) {
            $message = "unable to save data";
            throw new Exception('unable to backup');
          }

          //deleting previous record
          $delete_sql = 'DELETE FROM user_purchase_items WHERE user_id =' . $child_id;
          if (!$connection->execute($delete_sql)) {
            $message = "unable to delete previous record";
            throw new Exception($message);
          }
          $time = time();
          //insert row
          foreach ($courses as $course) {
            $total_amount = $total_amount + ($course['price'] * $package['discount'] * 0.01);
            $user_purchase_items = TableRegistry::get('User_purchase_items');
            $user_purchase_item = $user_purchase_items->newEntity(
              array(
                'user_id' => $child_id,
                'course_id' => $course['id'],
                'package_id' => $package['id'],
                'level_id' => $course['level_id'],
                'plan_id' => $plan['id'],
                'amount' => $course['price'],
                'discount' => $package['discount'],
                'order_date' => date('Y-m-d H:i:s'),
                'order_timestamp' => $time,
                'item_paid_status' => 0,
                )
            );
            if (!$user_purchase_items->save($user_purchase_item)) {
              $message = "unable to delete previous record";
              throw new Exception($message);
            }
          }

          $response['status'] = TRUE;
          $response['order_timestamp'] = $time;
        } else {
          $response['message'] = 'Please login to update';
        }
       } catch (Exception $e) {
         $this->log($e->getMessage(), '(' . __METHOD__ . ')');
       }
     }
     $this->set([
       'response' => $response,
      '_serialize' => ['response']
     ]);
   }

   /** guestLogin().
    */
    public function guestLogin() {
      try {
        $status = 0;
        $message = '';
        if ($this->request->is('post')) {
          if (empty($this->request->data['username'])) {
            $message = 'User name cannot be empty';
            throw new Exception($message);
          }
          if (empty($this->request->data['levelchoice']['id'])) {
            $message = 'Grade cannot be empty';
            throw new Exception($message);
          }
          $email = $this->request->data['email'];
          if (empty($email)) {
            $message = 'Email cannot be empty';
            throw new Exception($message);
          }
          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email is not valid';
            throw new Exception($message);
          }
          $guest_session = TableRegistry::get('Guest_session');
          $existed_guest = $guest_session->find()->where(['ip' => $this->request->data['user_ip']]);
          if ($existed_guest->count()) {
            $status = -1;
            $message = 'Trial already Taken';
          } else {
            $new_guest = $guest_session->newEntity(array(
              'username' => $this->request->data['username'],
              'email' => $email,
              'grade' => $this->request->data['levelchoice']['id'],
              'ip' => $this->request->data['user_ip'],
              'created' => time()
              )
            );
            if ($guest_session->save($new_guest)) {
              $status = 1;
              $message = 'Your Guest session started';
            }
          }
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
      }
      $this->set([
        'user' => $this->request->data['username'],
        'user_id' => isset($new_guest->id) ? $new_guest->id : '',
        'status' => $status,
        'message' => $message,
        '_serialize' => ['user', 'user_id', 'status', 'message']
      ]);
    }

    /**
     * function getUserOrders.
     *
     * This function is used to store user order to table
     *
     * $user_id : Integer
     *
     * $child_id : Integer
     *   if user type is teacher, then child id will be 0
     *
     * $order_details : Array
     *    contains the order details
     */
    public function getUserOrders() {
      try {
        $status = FALSE;
        $order_details = array();
        $record_found = 0;
        $message = '';
        if ($this->request->is('post')) {
          if (!isset($this->request->data['child_id']) && !empty($this->request->data['child_id'])) {
            $message = 'child id missing';
            throw new Exception($message);
          }
          $conditions['child_id'] = $this->request->data['child_id'];
          if (!isset($this->request->data['parent_id']) && !empty($this->request->data['parent_id'])) {
            $conditions['user_id'] = $this->request->data['parent_id'];
          }
          $user_orders = TableRegistry::get('UserOrders');
          $order_details = $user_orders->find()->where($conditions);
          if ($record_found = $order_details->count()) {
            $status = TRUE;
          } else {
            $message = 'No record found';
          }
        }
      } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
      }
      $this->set([
        'status' => $status,
        'message' => $message,
        'data' => (isset($this->request->data['last_order'])) ? array($order_details->last()) : $order_details,
        'record_found' => empty($record_found) ? 0 : $record_found,
        '_serialize' => ['status', 'message', 'data', 'record_found']
      ]);
    }

    /**
     * function setUserOrders.
     *
     * This function is used to store user order to table
     *
     * $user_id : Integer
     *
     * $child_id : Integer
     *   if user type is teacher, then child id will be null
     *
     * $order_details : Array
     *    contains the order details
     */
    public function setUserOrders($user_id, $child_id = 0, $order_details = array()) {
      try {
        $status = FALSE;
        $user_orders = TableRegistry::get('UserOrders');
        $order = array(
          'user_id' => $user_id,
          'child_id' => !empty($child_id) ? $child_id : 0,
          'amount' => isset($order_details['total_amount']) ? $order_details['total_amount'] : 0,
          'discount' => isset($order_details['discount']) ? $order_details['discount'] : '',
          'order_date' => isset($order_details['order_date']) ? $order_details['order_date'] : '',
          'trial_period' => ($order_details['trial_period'] == TRUE) ? 1 : 0,
          'card_response' => isset($order_details['card_response']) ? $order_details['card_response'] : '',
          'card_token' => isset($order_details['card_token']) ? $order_details['card_token'] : '',
          'external_customer_id' => isset($order_details['external_customer_id']) ? $order_details['external_customer_id'] : '',
          'paypal_plan_id' => $order_details['paypal_plan_id'],
          'paypal_plan_status' => $order_details['paypal_plan_status'],
          'billing_id' => $order_details['billing_id'],
          'billing_state' => strtoupper($order_details['billing_state']),
          'purchase_item_order_timestamp' => isset($order_details['purchase_item_order_timestamp']) ? $order_details['purchase_item_order_timestamp'] : time(),
        );
        $user_order = $user_orders->newEntity($order);
        if ($user_orders->save($user_order)) {
          $status = TRUE;
        }
      } catch (Exception $ex) {
        $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
      }
      return $status;
    }

  /*
   * function getCouponByUserType()
   */
  public function getCouponByUserType() {
    try {
      $status = FALSE;
      $message = $user_type = '';
      $coupon_results = array();
      if ($this->request->is('post')) {
        $param = $this->request->data;
        if (isset($param['user_type']) && !empty($param['user_type'])) {
          $connection = ConnectionManager::get('default');
          $user_type = strtoupper($param['user_type']);
          $sql = 'SELECT * FROM coupons WHERE user_type = ' . "'" . $user_type . "'"
            . ' AND validity >= ' . "'" . time() . "'";
          if (isset($param['applied_for']) && !empty($param['applied_for'])) {
            $sql.= ' AND applied_for = '. "'" . $param['applied_for'] . "'";
          }
          if (isset($param['external_coupon']) && !empty($param['external_coupon'])) {
            $sql.= ' AND external_coupon = '. "'" . $param['external_coupon'] . "'";
          }
          $coupon_results = $connection->execute($sql)->fetchAll('assoc');
          $conditional_coupons = array();
          if (isset($param['condition_key']) && isset($param['condition_value']) &&
            (strtolower($param['condition_key']) == 'points' )) {
            $sql_coupon = 'SELECT * FROM coupons'
              . ' INNER JOIN coupon_conditions ON coupons.id = coupon_conditions.coupon_id'
              . ' WHERE  coupon_conditions.condition_key = ' . "'" . $param['condition_key']  . "'"
              . ' AND coupon_conditions.condition_value <= ' . "'" . $param['condition_value']  . "'"
              . ' AND coupons.user_type = ' . "'" . $user_type  . "'"
              . ' AND coupons.validity >= ' . "'" . time() . "'";
            if (strtoupper($user_type) == 'STUDENT') {
              $sql_coupon.= ' AND coupons.external_coupon = 1 AND coupons.applied_for =' . "'coupon'";
            }
            $conditional_coupons = $connection->execute($sql_coupon)->fetchAll('assoc');
          }
          $conditional_array = array();
          if (!empty($coupon_results)) {
            $status = TRUE;
            if (!empty($conditional_coupons)) {
              foreach ($conditional_coupons as $conditional_coupon) {
                $conditional_array[$conditional_coupon['id']] = $conditional_coupon;
              }
            }
            foreach ($coupon_results as &$coupon_result) {
              if (isset($conditional_array[$coupon_result['id']])) {
                $coupon_result['conditional_value'] = isset($conditional_array[$coupon_result['id']]['condition_value']) ?
                $conditional_array[$coupon_result['id']]['condition_value'] : 0;
                $coupon_result['visibility'] = 'visible';
              } else {
                $coupon_result['conditional_value'] = 0;
                $coupon_result['visibility'] = 'hidden';
              }
            }
          } else {
            $message = 'No Record Found';
          }
        } else {
          throw new Exception('User Type is mandatory');
        }
      } else {
        throw new Exception('Request is not using POST');
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'user_type' => $user_type,
      'result' => $coupon_results,
      '_serialize' => ['status', 'message', 'user_type', 'result']
    ]);
  }

  /*
   * function getUsedCoupons()
   */
  public function getUsedCoupon() {
    try {
      $status = FALSE;
      $message = '';
      $coupons_status_result = array();
      $param = $this->request->data;
      if (isset($param['user_id']) && !empty($param['user_id'])) {
        $coupon_avail_status_table = TableRegistry::get('coupon_avail_status');
        $coupons_status_result = $coupon_avail_status_table->find('all')->where(['user_id' => $param['user_id']]);
        $coupons_status_result->join([
          'table' => 'coupons',
          'type' => 'INNER',
          'conditions' => 'coupons.id = coupon_avail_status.coupon_id'
        ])->join([
          'table' => 'coupon_conditions',
          'type' => 'INNER',
          'conditions' => 'coupon_conditions.coupon_id = coupon_avail_status.coupon_id'
        ])->select([ 'coupons.id', 'coupons.title', 'coupons.description', 'coupons.image','coupons.user_type',
           'coupon_avail_status.id','coupon_avail_status.user_id', 'coupon_avail_status.coupon_id',
          'coupon_avail_status.date', 'coupon_avail_status.status', 'coupon_avail_status.updated_by',
          'coupon_conditions.condition_value'
        ]);
        if ($coupons_status_result->count()) {
          $status = TRUE;
        }
      } else {
        throw new Exception('user Id cannot be empty');
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'result' => $coupons_status_result,
      '_serialize' => ['status', 'message', 'result']
    ]);
  }

  /*
   * function setAvailableCoupon
   */
  public function setAvailableCoupon() {
    try {
      $status = FALSE;
      $message = $error_code = '';
      $automatic_approval = 0;
      $param = $this->request->data;
      if (!isset($param['updated_by_user_id'])) {
        throw new Exception('Kindly login to update coupons');
      }
      if (isset($param['user_id']) && !empty($param['user_id'])) {
       if (isset($param['coupon_id']) && !empty($param['coupon_id'])) {
         $user_details_table = TableRegistry::get('UserDetails');

        //if automatic approval turned on by parent
         $user_data = $user_details_table->find()->where(['user_id' => $param['user_id']]);
         foreach ($user_data as $user_info) {
           $parent_id = $user_info->parent_id;
           if (!empty($parent_id)) {
             $this->request->data['user_id'] = $parent_id;
             $this->request->data['child_id'] = $param['user_id'];
             $this->request->data['requested'] = TRUE;
             $user_settings = $this->getUserSetting();
             if (!empty($user_settings) && isset($user_settings['settings']) && !empty($user_settings['settings'])) {
               $settings = json_decode($user_settings['settings'], TRUE);
              if ($settings['automatic_approval'] == 1) {
                $automatic_approval = 1;
              } elseif (isset($settings['global_automatic_approval']) &&
                $settings['global_automatic_approval'] == 1) {
                $automatic_approval = 1;
              }
             }
           }
           break;
         }
         // When coupon is in state of approval pending, the point will be deducted
         if (strtolower($param['status']) == 'approval pending') {
           if ($automatic_approval == 1) {
             $param['status'] = 'acquired';
           }
           $coupon_condition_key = isset($param['coupon_condition_key']) ? $coupon_condition_key : 'points';
           $condition_response = $this->_getCondtionsDetailsOnCoupon($param['coupon_id'], $coupon_condition_key);
           if ($condition_response['status'] == TRUE) {
             $user_details = $this->getUserDetails($param['user_id'], TRUE);
             $user_current_points = $user_details['user_all_details']['user_detail']['points'];

             if ($user_current_points > 0) {
               $user_new_points = $user_current_points - $condition_response['result']['condition_value'];
               if ($user_new_points < 0) {
                 $error_code = 'LESS_POINT';
                 $message = 'Insufficient Points';
                 throw new Exception('User points are less');
               }

               $query_updated = $user_details_table->query()->update()->set(['points' => $user_new_points])->where(['user_id' => $param['user_id']])->execute();
               if (!$query_updated) {
                 $message = 'Some Error occured. Please contact to administrator';
                 throw new Exception('unable to update points to user details table');
               }
             } else {
               $error_code = 'ZERO_POINT';
               $message = 'Insufficient Points';
               throw new Exception('User points are below Zero');
             }
           } else {
             $error_code = 'POINT_GET_ERROR';
             $message = "Unable to get points";
             throw new Exception('Error in coupon condition table. Message: ' . $condition_response['message']);
           }
         }

          // When coupon is in state of rejected, the point will be added.
         if (strtolower($param['status']) == 'rejected') {
           $user_details = $this->getUserDetails($param['user_id'], TRUE);
           $user_current_points = $user_details['user_all_details']['user_detail']['points'];
           $user_new_points = $user_current_points + $param['conditional_value'];
           $query_updated = $user_details_table->query()->update()->set(['points' => $user_new_points])->where(['user_id' => $param['user_id']])->execute();
           if (!$query_updated) {
             $message = 'Some Error occured. Please contact to administrator';
             throw new Exception('unable to update points to user details table');
           }
         }

          $coupon_avail_status_table = TableRegistry::get('coupon_avail_status');
          $coupon = $coupon_avail_status_table->find()->where(['user_id' => $param['user_id'],
            'coupon_id' => $param['coupon_id']]);
          if ($coupon->count()) {
          $entry_status = $coupon_avail_status_table->query()->update()
            ->set(['status' => $param['status'], 'updated_by' => $param['updated_by_user_id']])
            ->where(['coupon_id' => $param['coupon_id'], 'user_id' => $param['user_id']])->execute();
          } else {
            $coupon_entry = $coupon_avail_status_table->newEntity(array(
              'user_id' => $param['user_id'],
              'coupon_id' => $param['coupon_id'],
              'date' => date('Y-m-d'),
              'status' => ucfirst($param['status']),
              'updated_by' => $param['updated_by_user_id'],
              )
            );
           $entry_status = $coupon_avail_status_table->save($coupon_entry);
          }

          if (!empty($entry_status)) {
            $coupon_avail_transactions = TableRegistry::get('coupon_avail_transactions');
            $new_transaction_entity = $coupon_avail_transactions->newEntity(array(
              'user_id' => $param['user_id'],
              'coupon_id' => $param['coupon_id'],
              'date' => date('Y-m-d'),
              'status' => $param['status'],
              'updated_by' => $param['updated_by_user_id'],
            ));
            if (!$coupon_avail_transactions->save($new_transaction_entity)) {
              $message = "Some error occured while updating Information";
              throw new Exception('unable to save in coupon availble transactions');
            }
            $status = TRUE;

            $role_id = $this->getRoleIdByUserId($param['user_id']);
            //setting notification for coupon.
            $payment_controller = new PaymentController();
            $param['url'] = Router::url('/', true) . 'users/setUserNotifications';
            $param['return_transfer'] = TRUE;
            $param['post_fields'] = array(
              'user_id' => $param['user_id'],
              'role_id' => $role_id,
              'bundle' => 'COUPONS',
              'category_id' => NOTIFICATION_CATEGORY_COUPONS,
              'sub_category_id' => $param['coupon_id'],
              'title' => strtoupper($param['status']), //coupon in state of reedemed , approved , rejected or pending for approval by mlg
              'description' => 'Coupon is ' . strtoupper($param['status'])
            );
            $param['json_post_fields'] = TRUE;
            $param['curl_post'] = 1;
            $payment_controller->sendCurl($param);
          }
        } else {
         $message = 'Coupon Id cannot be empty';
         throw new Exception('Coupon Id cannot be empty');
       }
      } else {
        $message = 'user id cannot be empty';
        throw new Exception('user id cannot be empty');
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
     $this->set([
      'status' => $status,
      'message' => $message,
      'error_code' => $error_code,
      'coupon_status' => ucfirst($param['status']),
      '_serialize' => ['status', 'coupon_status', 'message', 'error_code']
    ]);
  }

  /*
   * function _getCondtionsDetailsOnCoupon().
   */
  public function _getCondtionsDetailsOnCoupon($coupon_id = NULL, $coupon_condition_key = 'points') {
    try {
      $response = array('status' => FALSE, 'message' => '', 'result' => array());
      if (empty($coupon_id)) {
        $response['message'] = 'coupon id cannot be blank';
        throw new Exception($response['message']);
      }
      $coupon_condition_table = TableRegistry::get('coupon_conditions');
      $query_results = $coupon_condition_table->find('all')->where(['condition_key' => $coupon_condition_key, 'coupon_id' => $coupon_id]);
      if ($query_results->count()) {
        $response['status'] = TRUE;
        foreach ($query_results as $query_result) {
          $response['result'] = $query_result->toArray();
        }
      } else {
        $response['message'] = 'No record found';
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return $response;
  }

  /*
   * function setpreTestStatus().
   */
  public function setpreTestStatus(){
    $test_status = isset($this->request->data['pretestStatus'] ) ? $this->request->data['pretestStatus'] : 0;
    $user_id = isset( $this->request->data['user_id'] ) ? $this->request->data['user_id'] : 0;

     $userdetails = TableRegistry::get('UserDetails');
     $result =$userdetails->query()->update()->set(['preTestStatus' => $test_status])->where(['user_id' => $user_id] )->execute();

     $affectedRows = $result->rowCount();
     if($affectedRows>0){
          $data['status'] = "True";
          $data['message'] ="updated;";
      }else{
            $data['status'] = "False";
            $data['message'] ="not updated;";
       }

      $this->set([
      'response' => $data,      
      '_serialize' => ['response']
    ]);
  }

  public function getpreTestStatus(){
    $user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : 0;
   
     $user_detail =TableRegistry::get('UserDetails')->find('all')->where(['user_id'=>$user_id]);
        if($user_detail->count() >0 ){
              foreach ($user_detail as $userdetail) {                 
                  $data['preTestStatus'] = $userdetail->preTestStatus;
              }
          $data['status'] = "True";          
      }else{
            $data['status'] = "False";
            $data['message'] ="No records;";
       }
    
      $this->set([
      'response' => $data,      
      '_serialize' => ['response']
    ]);
  }
// save avtar image on location.
  public function uploadAvatarImage() {
    try{
      if($this->request->is('post')) {
        $message = '';
        $id = '';
        $status = FALSE;
        if(empty($this->request->data['uid'])) {
          $message = 'Please login.';
        }else{
          $id = $this->request->data['uid'];
        }
        if($message == '') {
          $img = $this->request->data['image'];
          $img = str_replace('data:image/png;base64,', '', $img);
          $imgData = base64_decode($img);
          $image =  'Avtar_'.$id.'.png';
          // Path where the image is going to be saved
          $filePath = WWW_ROOT .'/upload/Avtar/'.$image;
          // Delete previously uploaded image
          if (file_exists($filePath)) {
           unlink($filePath);
          }
          // Write $imgData into the image file
          $file = fopen($filePath, 'w');
          fwrite($file, $imgData);
          fclose($file);
          $user = TableRegistry::get('user_details');
          $query = $user->query();
          $result = $query->update()->set([
                'profile_pic' => '/upload/Avtar/'.$image,
                'step_completed' => '1',
             ])->where(['user_id' => $id ])->execute();
          $row_count = $result->rowCount();
          if ($row_count == '1') {
            $message = 'Avtar saved.';
            $status = TRUE;
          }  else {
            throw new Exception('udable to update value in db');
          }
        }
      }
    }catch(Exception $e) {
      $this->log('Error in uploadAvtarImage function in Users Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'response' => '/Avtar/'.$image,
      'message' => $message,
      'status' => $status,
      '_serialize' => ['response','message','status']
    ]);
  }

  // get avtar image on location.
  public function getAvatarImage($id=NULL) {
    try{
      $message = '';
      $result = '';
      $user = TableRegistry::get('user_details');
      if($id == NULL) {
        $message = 'Please login.';
      }
      if($message == '') {
        $result = $user->find()->where(['user_id'=> $id]);
      }
    }catch(Exception $e) {
      $this->log('Error in getAvtarImage function in Users Controller.'
              .$e->getMessage().'(' . __METHOD__ . ')');
    }
    $this->set([
      'response' => $result,
      'message' => $message,
      '_serialize' => ['response','message']
    ]);
  }

  /*
   * function getUserSetting().
   */
  public function getUserSetting() {
    try {
      $status = FALSE;
      $message = '';
      $user_setting = array();
      $data = $this->request->data;
      if ($this->request->is('post') || isset($data['requested'])) {
        if (!isset($data['user_id']) || empty($data['user_id'])) {
          $message = 'Kindly login';
          throw new Exception('User Id cannot be null');
        }
        $data['child_id'] = isset($data['child_id']) ? $data['child_id'] : 0;
        $user_setting = TableRegistry::get('user_settings')
           ->find()->where(['user_id' => $data['user_id'], 'child_id' => $data['child_id']]);
        if ($user_setting->count()) {
          $status = TRUE;
        } else {
          $message = 'user setting not found';
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    if (isset($data['requested'])) {
      if (!empty($user_setting->first())) {
        return $user_setting->first()->toArray();
      } else {
        return array();
      }
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'result' => $user_setting->first(),
      '_serialize' => ['status', 'message', 'result']
    ]);
  }

  /*
   * function setUserSetting().
   */
  public function setUserSetting() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        $data = $this->request->data;
        if (!isset($data['user_id']) || empty($data['user_id'])) {
          $message = 'Kindly login';
          throw new Exception('User Id cannot be null');
        }
        if (!isset($data['settings'])) {
          $message = 'Settings not found';
          throw new Exception('Settings key not present in post data');
        }
        $user_setting_table = TableRegistry::get('user_settings');
        $data['child_id'] = isset($data['child_id']) ? $data['child_id'] : 0;

        $user_settings = $user_setting_table->find('all')->where(['user_id' => $data['user_id'], 'child_id' => $data['child_id']]);
        $settings_decoded_json = array();
        if ($user_settings->count()) {
          foreach ($user_settings as $user) {
            $settings_decoded_json = json_decode($user->settings, TRUE);
            break;
          }

          foreach ($data['settings'] as $key => $value) {
            $settings_decoded_json[$key] = $value;
          }

          $saved_user_setting = $user_setting_table->query()->update()->set(['settings' => json_encode($settings_decoded_json)])
            ->where(['user_id' => $data['user_id'], 'child_id' => $data['child_id']])->execute();
          if ($saved_user_setting) {
            $status = TRUE;
          } else {
            $message = 'unable to update your settings';
          }
        } else {
          //settings defaut settings for new user
          $data_settings = $this->_setDefaultSettings($data['settings']);
          $data['settings'] = json_encode($data_settings);
          if ($user_setting_table->save($user_setting_table->newEntity($data))) {
            $status = TRUE;
          } else {
            $message = 'Unable to save your settings';
          }
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      '_serialize' => ['status', 'message']
    ]);
  }

  /**
   * setDefaultSettings().
   */
  private function _setDefaultSettings($user_settings = array()) {
    try {
      $default_settings = array(
        'automatic_approval' => FALSE,
        'text_notification' => FALSE,
        'email_notification' => FALSE,
        'mlg_offers' => FALSE,
        'chat' => FALSE,
        'group_builder' => FALSE,
        'placement_test' => TRUE,
        'auto-progression' => TRUE,
        'fill_in_the_blank' => TRUE,
        'single_choice' => TRUE,
        'multiple_choice' => TRUE,
        'true_false' => TRUE,
      );
      foreach ($user_settings as $settings_key => $settings_value) {
        if (array_key_exists($settings_key, $default_settings)) {
          $default_settings[$settings_key] = $settings_value;
        } else {
          $default_settings[$settings_key] = $settings_value;
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return $default_settings;
  }

  /**
   * updateMyAccount().
   */
  public function updateMyAccount() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        $user = $this->request->data;
        if (!isset($user['user_id'])) {
          $message = 'Kindly login';
          throw new Exception('user id missing');
        }

        $user_details_table = TableRegistry::get('UserDetails');
        $user_details = $user_details_table->find()->where(['user_id' => $user['user_id']]);
        if ($user_details->count()) {
          $user_details_table->patchEntity($user_details->first(), $user, ['validate' => false]);
          if(!$user_details_table->save($user_details->first())) {
            $message = 'Some Error Occured, Kindly contact the Administrator';
            throw new Exception('Unable to save data');
          } else {
            $status = TRUE;
            $user_preferences_table = TableRegistry::get('UserPreferences');
            $user_preference = $user_preferences_table->find()->where(['user_id' => $user['user_id']]);
            if ($user_preference->count()) {
              $user_preferences_table->patchEntity($user_preference->first(), $user, ['validate' => false]);
              if(!$user_preferences_table->save($user_preference->first())) {
                $status = FALSE;
                $message = 'Some Error Occured, Kindly contact the Administrator';
                throw new Exception('Unable to save user Preference data');
              }
            } else {
              $new_preferences = $user_preferences_table->newEntity(array(
                'user_id' => isset($user['user_id']) ? $user['user_id'] : '',
                'mobile' => isset($user['mobile']) ? $user['mobile'] : ''
              ));
              if (!$user_preferences_table->save($new_preferences)) {
                $message = 'User Preference not found and unable to save new preferences';
                throw new Exception('User id not found in userPreferences');
              }
            }
          }
        } else {
          $message = 'User Details not found';
          throw new Exception('User id not found in userDetails');
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
     'status' => $status,
     'message' => $message,
     '_serialize' => ['status', 'message']
   ]);
  }

  /**
   * function updateProfilePic().
   */
  public function updateProfilePic() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        $data = $this->request->data;
        if (isset($data['user_id'])) {
          if (!empty($data['user_id'])) {
            if (isset($data['file']) && !empty($data['file'])) {
              $response = $this->_uploadFiles($data['file'], DEFAULT_IMAGE_DIRECTORY);
              if ($response['success'] == TRUE) {
                $user_details_table = TableRegistry::get('UserDetails');
                $user_details = $user_details_table->find()->where(['user_id' => $data['user_id']]);
                foreach ($user_details as $user_detail) {
                  $user_detail->profile_pic = DEFAULT_IMAGE_DIRECTORY . $response['file_name'];
                }
                if (!$user_details_table->save($user_detail)) {
                  $message = 'unable to save Profile image';
                  throw new Exception('unable to save data');
                }
                $status = TRUE;
              } else {
                $message = 'unable to save Profile image';
                throw new Exception($response['message']);
              }
            } else {
              $message = "No data to save";
              throw new Exception('empty data file');
            }
          } else {
            $message = "user Id empty";
            throw new Exception('user_id canot be empty');
          }
        } else {
          $message = 'Kindly login';
          throw new Exception('key : user_id not present');
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
     'status' => $status,
     'message' => $message,
     '_serialize' => ['status', 'message']
   ]);
  }

  /**
   * function _uploadFiles().
   *
   * @param Array $file
   *   contains $_FILES values.
   * @param String $upload_file
   *   location of file to be uploaded.
   * @return Array
   *   return response.
   */
  private function _uploadFiles($file, $upload_path) {
    $response = array('success' => FALSE, 'url' => '', 'message' => '', 'file_name' => '');
    $file_name = time() . '_' . $file['name'];
    $file_path = $upload_path . $file_name;
    if (is_dir(WWW_ROOT . $upload_path)) {
      if (is_writable(WWW_ROOT . $upload_path)) {
        if (move_uploaded_file($file['tmp_name'], WWW_ROOT . $file_path)) {
          $response['success'] = TRUE;
          $response['file_name'] = $file_name;
          $response['url'] = Router::url('/', true) . $file_path;
        } else {
          $response['message'] = 'Unable to upload file due to some error';
        }
      } else {
        $response['message'] = 'Upload path is not writable';
      }
    } else {
      $response['message'] = 'No such directory exist.';
    }
    return $response;
  }

  /**
   * function deactiveUserSubscription().
   */
  public function deactivateUserSubscription() {
    try {
      $status = FALSE;
      $is_billing_id_present = $is_billing_state_active = FALSE;
      $message = '';
      if (!isset($this->request->data['user_id']) || empty($this->request->data['user_id'])) {
        $message = 'User id is null';
        throw new Exception('User id is null');
      }
      $child_id = 0;
      if (isset($this->request->data['child_id'])) {
        $child_id = $this->request->data['child_id'];
      }
      $user_id = $this->request->data['user_id'];
      $user_orders_table = TableRegistry::get('UserOrders');
      $user_orders = $user_orders_table->find()->where(
        ['user_id' => $user_id,
         'child_id' => $child_id
        ]);
      if ($user_orders->count()) {
        $user_orders_details = $user_orders->last()->toArray();
        if ($user_orders_details['billing_state'] == 'ACTIVE') {
          $is_billing_state_active = TRUE;
        } else {
          $message = 'No active bill found';
          throw new Exception($message);
        }
      } else {
        $message = 'User order not found';
        throw new Exception($message);
      }
      if ($is_billing_state_active) {
        $billing_id = $user_orders_details['billing_id'];
        if (empty($billing_id)) {
          $is_billing_id_present = FALSE;
          $message = 'No billing id found';
          throw new Exception($message);
        }
        $payment_controller = new PaymentController();
        $billiing_cancel_response = $payment_controller->cancelBillingAgreement($billing_id);
        if (!empty($billiing_cancel_response)) {
          $user_orders_details['order_date'] = $user_orders_details['purchase_item_order_timestamp'] = time();
          $user_orders_details['billing_state'] = $billiing_cancel_response;
          $user_orders_details['total_amount'] = $user_orders_details['amount'];
          $order_entry = $this->setUserOrders($user_id, $child_id, $user_orders_details);
          if ($order_entry === TRUE) {
            $status = TRUE;
          } else {
            $message = 'unable to deactivate order';
            throw new Exception($message);
          }
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    if (isset($this->request->data['requested'])) {
      return array('status' => $status, 'message' => $message, 'is_billing_id_present' => $billing_id_present);
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'is_billing_id_present' => $is_billing_id_present,
      '_serialize' => ['status', 'message', 'is_billing_id_present']
    ]);
  }

  /**
   * function deactivateChildrenOnParentDeactivation()
   */
  public function deactivateChildrenOnParentDeactivation() {
    try {
      $status = FALSE;
      $number_of_child = 0;
      $deactivated_child_ids = array();
      $message = '';
      if ($this->request->is('post')) {
        if (!isset($this->request->data['parent_id'])) {
          $message = 'Parent id cannot be null';
          throw new Exception('Parent id cannot be null');
        }
        $parent_id = $this->request->data['parent_id'];
        $parent = $this->getUserDetails($parent_id, TRUE);
        if ($parent['user_all_details']['status'] != 0) {
          $message = 'Parent is not deactivated yet';
          throw new Exception("Parent id: $parent_id is active");
        }
        $children = $this->getChildrenDetails($parent_id, null,TRUE);
        if (empty($children)) {
          $message = 'No child  Found';
          throw new Exception($message);
        }
        $number_of_child = count($children);
        foreach ($children as $child) {
          $controller_url = Router::url('/', true) . 'users/';
          $payment_controller = new PaymentController();
          $param['url'] = $controller_url . 'setUserStatus';
          $param['return_transfer'] = 1;
          $param['post_fields'] = array('id' => $child['user_id'], 'status' => 0);
          $param['json_post_fields'] = TRUE;
          $param['curl_post'] = 1;
          $curl_response = $payment_controller->sendCurl($param);
          if (!empty($curl_response['curl_exec_result'])) {
            $set_status_result = json_decode($curl_response['curl_exec_result'], TRUE);
            if ($set_status_result['status'] == TRUE) {
              $deactivated_child_ids[] = $child['user_id'];
            }
          }
        }
        if (count($deactivated_child_ids) === $number_of_child) {
          $status = TRUE;
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'number_of_child' => $number_of_child,
      'deactivated_child_ids' => $deactivated_child_ids,
      '_serialize' => ['status', 'message', 'number_of_child', 'deactivated_child_ids']
    ]);
  }

  /**
   * function updateUserSubscriptionPeriod().
   *
   * This function updates user's subscription period
   * if user is not in TRIAL period.
   */
  public function updateUserSubscriptionPeriod() {
    try {
      $status = FALSE;
      $message = '';
      $new_subscription_end_date = '';
      $req_data = $this->request->data;

      // usually user_id refers to parent_id or teacher_id.
      if (!isset($req_data['user_id']) && empty($req_data['user_id'])) {
        $message = 'User id missing';
        throw new Exception($message);
      }
      $child_id = 0;
      if (isset($req_data['child_id']) && !empty($req_data['child_id'])) {
        $child_id = $req_data['child_id'];
      }
      $conditions['UserOrders.user_id'] = $req_data['user_id'];
      $conditions['UserOrders.child_id'] = $child_id;

      $user_orders_table = TableRegistry::get('UserOrders');
      $user_order = $user_orders_table->find()->where($conditions);
      $orders_join_purchase_table = $user_order->join([
        'table' => 'user_purchase_items',
        'type' => 'INNER',
        'conditions' => [
          'user_purchase_items.order_timestamp = UserOrders.purchase_item_order_timestamp',
          'user_purchase_items.item_paid_status = 1',
          "UserOrders.billing_state = 'ACTIVE'",
        ]
      ])->select([
       'user_purchase_items.plan_id',
       'user_purchase_items.order_timestamp',
       'UserOrders.trial_period'
      ]);
      if ($orders_join_purchase_table->count()) {
        $orders_join_purchase_result = $orders_join_purchase_table->last()->toArray();
        $plan_id = $orders_join_purchase_result['user_purchase_items']['plan_id'];
        $order_timestamp = $orders_join_purchase_result['user_purchase_items']['order_timestamp'];
        $plans_table = TableRegistry::get('plans');
        $plan = $plans_table->find()->select('num_months')->where(['id' => $plan_id])->first()->toArray();
        $plan_month = $plan['num_months'];
        if (!empty($child_id)) {
          $add_trial_days_timestamp = 0;

          // if parent on trial subscription. Trial period will be added to child subscription.
          $parent_info = $this->getParentInfoByChildId($child_id);
          if ($parent_info['parent_subscription_days_left'] > 0) {
            $add_trial_days_timestamp = (60 * 60 * 24 * $parent_info['parent_subscription_days_left']);
          }
          $new_subscription_time = strtotime("+$plan_month months", $order_timestamp) + $add_trial_days_timestamp;
          $new_subscription_end_date = date('Y-m-d', $new_subscription_time);
          $user = $this->Users->find()->where(['id' => $child_id]);
          foreach($user as $info) {
            $info->subscription_end_date = $new_subscription_end_date;
            break;
          }
          if ($this->Users->save($info)) {
            $status = TRUE;
          }
        }
      } else {
        $message = 'No active record found';
        throw new Exception($message);
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'new_subscription_end_date' => $new_subscription_end_date,
      '_serialize' => ['status', 'message', 'new_subscription_end_date']
    ]);
  }

  /**
   * function getParentInfoByChildId().
   */
  public function getParentInfoByChildId($child_id = NULL) {
    try {
      $status = FALSE;
      $message = '';
      $parent_info = array();
      $parent_subscription_days_left = 0;
      $parent_subscription_timestamp = '';
      if (empty($child_id)) {
        $message = 'Child id could not be null';
        throw new Exception($message);
      }
      $child_info = $this->getUserDetails($child_id, TRUE);
      if (!empty($child_info['user_all_details'])) {
        $child_details = $child_info['user_all_details'];
        $parent_id = $child_details['user_detail']['parent_id'];
        if ($parent_id == 0) {
          $message = "Unable to find user's Parent";
          throw new Exception("parent_id is 0");
        }
        $parent_info = $this->getUserDetails($parent_id, TRUE);
        if (empty($parent_info['user_all_details']['subscription_end_date'])) {
          $message = 'unable to get subscription info';
          throw new Exception('Subscription period is null or undefiend');
        }
        $status = TRUE;
        $parent_subscription_info = (array)$parent_info['user_all_details']['subscription_end_date'];
        $parent_subscription_timestamp = strtotime($parent_subscription_info['date']);
        $parent_subscription_days_left = floor(($parent_subscription_timestamp - time())/(60 * 60 * 24));
      } else {
        $message = 'No record found regarding child id';
        throw new Exception($message);
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return [
      'status' => $status,
      'message' => $message,
      'parent_info' => isset($parent_info['user_all_details']) ? $parent_info['user_all_details'] : array(),
      'parent_subscription_days_left' => $parent_subscription_days_left,
      'parent_subscription_timestamp' => $parent_subscription_timestamp
    ];
  }

  /*
   * function updateUserCourseDetailsByUserPurchaseItems().
   *
   * $user_id : Is basically a child id.
   */
  public function updateUserCourseDetailsByUserPurchaseItems() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        $req_data = $this->request->data;
        if (!isset($req_data['user_id'])) {
          $message = 'user_id not found';
          throw new Exception($message);
        }
        if (empty($req_data['user_id'])) {
          $message = 'user id can not be empty';
          throw new Exception($message);
        }
        $payment_controller = new PaymentController();
        $purchase_detail_curl['url'] = Router::url('/', true) . 'users/getUserPurchaseDetails/' . $req_data['user_id'] . '/0/1';
        $purchase_detail_curl['return_transfer'] = 1;
        $purchase_detail_curl['post_fields'] = array();
        $purchase_detail_curl_response = $payment_controller->sendCurl($purchase_detail_curl);
        if ($purchase_detail_curl_response['status'] == TRUE && !empty($purchase_detail_curl_response['curl_exec_result'])) {
          $purchase_details = json_decode($purchase_detail_curl_response['curl_exec_result'], TRUE);
          if ($purchase_details['status'] == TRUE && !empty($purchase_details['response'])) {
            $purchase_details_response = $purchase_details['response'];
            if ($purchase_details_response['paid_status'] == 1) {
              $data['user_id'] = $purchase_details_response['user_id'];
              $data['course_ids'] = array();
              foreach ($purchase_details_response['purchase_detail'] as $detail) {
                $data['course_ids'][] = $detail['course_id'];
              }
              $delete_user_courses_curl['url'] = Router::url('/', true) . 'courses/deleteUserAllCourses';
              $delete_user_courses_curl['return_transfer'] = 1;
              $delete_user_courses_curl['post_fields'] = array('user_id' => $data['user_id']);
              $delete_user_courses_curl['json_post_fields'] = TRUE;
              $delete_user_courses_curl['curl_post'] = 1;
              $delete_user_courses_curl_response = $payment_controller->sendCurl($delete_user_courses_curl);
              if ($delete_user_courses_curl_response['status'] == TRUE && !empty($delete_user_courses_curl_response['curl_exec_result'])) {
                $delete_user_courses = json_decode($delete_user_courses_curl_response['curl_exec_result'], TRUE);
                if ($delete_user_courses['status'] == TRUE || $delete_user_courses['record_found'] == 0) {
                  $set_user_courses_curl['url'] = Router::url('/', true) . 'courses/setUserCourse';
                  $set_user_courses_curl['return_transfer'] = 1;
                  $set_user_courses_curl['post_fields'] = $data;
                  $set_user_courses_curl['json_post_fields'] = TRUE;
                  $set_user_courses_curl['curl_post'] = 1;
                  $set_user_courses_curl_response = $payment_controller->sendCurl($set_user_courses_curl);
                  if ($set_user_courses_curl_response['status'] == TRUE && !empty($set_user_courses_curl_response['curl_exec_result'])) {
                    $set_user_courses = json_decode($set_user_courses_curl_response['curl_exec_result'], TRUE);
                    if ($set_user_courses['status'] == TRUE) {
                      $status = TRUE;
                    } else {
                      $message = 'Unable to set user new courses';
                      throw new Exception($message);
                    }
                  } else {
                    $message = 'Unable to get curl response from set courses for users';
                    throw new Exception($message);
                  }
                } else {
                  $message = 'Unable to delete courses for users';
                  throw new Exception($message);
                }
              } else {
                $message = 'Unable to get curl response from delete courses for users';
                throw new Exception($message);
              }
            } else {
              $message = 'user has not purchased the courses yet';
              throw new Exception($message);
            }
          } else {
            $message = 'Unable to get info about purchase details';
            throw new Exception($message);
          }
        } else {
          $message = 'Unable to get curl response';
          throw new Exception($message);
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      '_serialize' => ['status', 'message']
    ]);
  }

  /*
   * function getRoleIdByUserId()
   */
  public function getRoleIdByUserId($user_id) {
    //getting roll_id
    $user_roles = TableRegistry::get('UserRoles');
    $valid_user = $user_roles->find('all')->where(['user_id' => $user_id]);
    $role_id = $valid_user->first()->role_id;
    return $role_id;
  }

  /**
   * function setUserNotifications().
   */
  public function setUserNotifications() {
    try {
      $status = FALSE;
      $message = '';
      $notifications_table = TableRegistry::get('notifications');
      $new_notification = $notifications_table->newEntity($this->request->data);
      if ($notifications_table->save($new_notification)) {
        $status = TRUE;
      } else {
        $message = 'Unable to save notification';
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      '_serialize' => ['status', 'message']
    ]);
  }

  /*
   * function getNotificationForParent().
   *
   * user_id will be used as child id as well as parent id. (situation based).
   */
  public function getNotificationForParent() {
    $message = '';
    $notification_message = array('offers' => '', 'subcriptions' => '', 'coupons' => '');
    $req_data = $this->request->data;
    if (isset($req_data['parent_id']) && empty($req_data['parent_id'])) {
      $message = 'parent id cannot be blank';
      throw new Exception($message);
    }
    if (isset($req_data['child_id']) && empty($req_data['child_id'])) {
      $message = 'child id cannot be blank';
      throw new Exception($message);
    }
    $notifications_table = TableRegistry::get('notifications');

    // For offers
    $offer_notification = $notifications_table->find()->where(['bundle' => 'OFFERS']);
    $offer = $offer_notification->last()->toArray();
    $coupons_table = TableRegistry::get('coupons');
    $parent_offer = $coupons_table->get($offer['sub_category_id'])->toArray();
    $notification_message['offers'] = $parent_offer['description'];

    // For subscription
    $subscription_notification = $notifications_table->find()->where(['user_id IN' => $req_data['child_id'], 'bundle' => 'SUBSCRIPTIONS']);
    if ($subscription_notification->count()) {
      $subscription = $subscription_notification->last()->toArray();
      $child = $this->Users->get($subscription['user_id'])->toArray();
      if (strtoupper($subscription['title']) == 'SUBSCRIPTION EXPIRE') {
        $date1 = $child['subscription_end_date'];
        $date2 = date_create();
        $date = date_diff($date1, $date2);
        $notification_message['subcriptions'] = 'Your subscription for ' . $child['first_name'] . ' ' . $child['last_name'] . ' is going to end ' . $date->days;
      }
    }

    // For coupon.
    $coupon_notification = $notifications_table->find()->where(['user_id IN' => $req_data['child_id'], 'bundle' => 'COUPONS']);
    if ($coupon_notification->count()) {
      $coupon = $coupon_notification->last()->toArray();
      $child = $this->Users->get($coupon['user_id'])->toArray();
      if (strtoupper($coupon['title']) == 'APPROVAL PENDING') {
        $notification_message['coupons'] = $child['first_name'] . ' ' . $child['last_name'] . ' has requested to redeem a coupon';
      }
      if (strtoupper($coupon['title']) == 'ACQUIRED') {
        $notification_message['coupons'] = $child['first_name'] . ' ' . $child['last_name'] . ' has acquired a coupon';
      }
    }
    $this->set([
      'message' => $message,
      'notification_message' => $notification_message,
      '_serialize' => ['message', 'notification_message']
    ]);
  }
}