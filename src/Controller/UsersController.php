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
      if ($user_record > 0) {
        $data['user'] = $this->Users->get($id);
      } else {
        $data['response'] = "Record is not found";
      }
      if ($function_call) {
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
          $user['subscription_end_date'] = date('Y-m-d' ,(time() + 60 * 60 * 24 * $user['subscription_days']));
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
        $child_info = array();
        $token = $message = $role_id = '';
        $warning = 0;

        if ($this->request->is('post')) {
          $user = $this->Auth->identify();
          if ($user) {
            $user_roles = TableRegistry::get('UserRoles');
            $valid_user = $user_roles->find('all')->where(['user_id' => $user['id']]);
            $role_id=$valid_user->first()->role_id;
            if ($user['status'] != 0) {
              $subscription_end_date = !empty($user['subscription_end_date']) ? strtotime($user['subscription_end_date']) : 0;
              if ($user['status'] != 2) {
                //If subscription is over
                if ((time() - (60 * 60 * 24))  > $subscription_end_date) {
                  $user['status'] = 2;
                  $this->Users->query()->update()->set($user)->where(['id' => $user['id']])->execute();
                  $message = 'Your subscription period is over';
                  throw new Exception('subscription period is over, Account Expired for user id: ' .  $user['id']);
                }

                //check if any of the child has eneded with subcription period.
                if ($role_id == PARENT_ROLE_ID) {

                  $children = $this->getChildrenDetails($user['id'], null, TRUE);;
                  if (!empty($children)) {
                    foreach ($children as $child) {
                      $subscription_end_date = strtotime($child['subscription_end_date']);
                      if ((time() - (60 * 60 * 24))  > $subscription_end_date) {
                        $warning = 1;
                        $child_info[] = $child;
                      }
                    }
                  }
                }

                //updating login time.
                $this->Users->query()->update()->set(['modfied' => time()])->where(['id' => $user['id']])->execute();

                if ($valid_user->count()) {
                  $this->Auth->setUser($user);
                  $token = $this->request->session()->id();
                  $status = 'success';
                } else {
                  $user = array();
                  $message = "You are not authenticated to login into this page";
                }
              } else {
                $message = 'Your subscription period is over';
              }
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
        'role_id'=>$role_id,
        'status' => $status,
        'warning' => $warning,
        'child_info' => $child_info,
        'response' => ['secure_token' => $token],
        'message' => $message,
        '_serialize' => ['user', 'status', 'warning', 'child_info', 'response', 'message','role_id']
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
       public function getPaymentbrief($params = null) {
         $status = FALSE;
         $message = '';
         $child_info = array();
         $total_amount = 0;
         if ($this->request->is('post') || !empty($params)) {
          try {
            $parent_id = isset($this->request->data['user_id']) ? $this->request->data['user_id'] : '';
            if (isset($params['user_id']) && !empty($params['user_id'])) {
              $parent_id = $params['user_id'];
            }
            if (!empty($parent_id)) {

              $user_details = TableRegistry::get('UserDetails');
              $user_info = $user_details->find()->select('user_id')->where(['parent_id' => $parent_id]);
              $parent_children = array();
              foreach ($user_info as $user) {
                $parent_children[] = $user->user_id;
              }

              $connection = ConnectionManager::get('default');
              $sql = "SELECT users.id as user_id, users.first_name as user_first_name, users.last_name as user_last_name,"
                . " SUM(user_purchase_items.amount) as purchase_amount, packages.name as package_subjects, plans.name as plan_duration"
                . " FROM users"
                . " INNER JOIN user_purchase_items on user_purchase_items.user_id=users.id"
                . " INNER JOIN packages ON user_purchase_items.package_id=packages.id"
                . " INNER JOIN plans ON user_purchase_items.plan_id=plans.id"
                . " WHERE user_purchase_items.user_id IN (" . implode(',', $parent_children) . ") GROUP BY user_purchase_items.user_id";
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


    public function addChildrenRecord() {          
      try{         

          if($this->request->is('post')) { 
              //$postdata=$this->request->data;
            $data['message'][]="";                              

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
                      $postdata['subscription_end_date'] = time() + 60 * 60 * 24 * $this->request->data['subscription_days'];
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
                                //5. User Purchase Item Table
                                $new_user_purchase_items = $user_purchase_items->newEntity($postdata);
                                if ($user_purchase_items->save($new_user_purchase_items)) {$data['status']="True";}
                                else{ 
                                  $data['status']='flase';
                                  $data['message']="Not able to save data in User Purchase Item Table Table";
                                  throw new Exception("Not able to save data in User Purchase Item Table Table");
                                }
                             }
                            else{
                              $data['status']='flase';
                              $data['message']=" Not able to save data in User Courses Table";
                              throw new Exception("Not able to save data in User Roles Table");
                          }
                        }

                      }
                      else{ 
                        $data['status']='flase';
                        $data['message']=" Not able to save data in User Roles Table";
                        throw new Exception("Not able to save data in User Roles Table");
                      }
                    }
                    else{ 
                      $data['status']='flase';
                      $data['message']="Not able to save data in User Details Table";
                      throw new Exception("Not able to save data in User Details Table");
                   }                   
                //$data['status']='True';
                }else{
                  $data['status']='flase';
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

       /**
        * function paypalAcessToken
        *   To generate paypal Acess Token.
        */
       public function  paypalAccessToken() {

         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/oauth2/token");
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_USERPWD, "AX-eb2b8qhYpvLJs_kw6vnDIYoWYcCzV8-mby7q7gHFA452lqrO95Qm2ivu4hYCt2VDTRTDy5jzLAscD" . ":" . "EC1WXNHIEoBMMvswkRw6LTASVcs5IWX8WV5LSlbQIrnTfTgdBJAZCj8vlwxHraq3SI-c6Pqe_ETpDAii");

         $headers = array();
         $headers[] = "Accept: application/json";
         $headers[] = "Accept-Language: en_US";
         $headers[] = "Content-Type: application/x-www-form-urlencoded";
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

         $result = curl_exec($ch);
         if (curl_errno($ch)) {
           echo 'Error:' . curl_error($ch);
         }
         curl_close ($ch);
         $result = json_decode($result, TRUE);
         return $result['access_token'];
       }

       /*
       * function saveCardToPaypal().
       */
       public function saveCardToPaypal() {
         $message = $response = '';
         $status = FALSE;
         $data = $name = array();
         $Acess_token = $this->paypalAccessToken();
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
             $card_token = $external_cutomer_id = '';
             if  (isset($response['state']) && $response['state'] == 'ok') {
               $message = 'card added successfully';
               $card_token = $response['id'];
               $external_cutomer_id = $response['external_customer_id'];
               $user_id = $this->request->data['user_id'];
               $payment_controller = new PaymentController();
               $payment_controller->createBillingPlan($user_id, $data);
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
                  $user_order->card_token = $card_token;
                  $user_order->external_cutomer_id = $external_cutomer_id;
               }
             } else {
               $order = array(
                 'user_id' => $this->request->data['user_id'],
                 'amount' => $this->request->data['amount'],
                 'discount' => isset($this->request->data['discount']) ? $this->request->data['discount'] : '',
                 'order_date' => time(),
                 'trial_period' => 1,
                 'card_response' => $message,
                 'card_token' => $card_token,
                 'external_cutomer_id' => $external_cutomer_id,
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
             'subscription_end_date' => $childRecord['user']['subscription_end_date'],
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
     try{
      $offer_list = array();
      $current_date = Time::now();
      $offers = TableRegistry::get('Offers');
      $offers_detail = $offers->find('all')->where(['validity >=' => $current_date])->toArray();
      $i=0;
      foreach ($offers_detail as $offersDetails) {
        if(isset($offersDetails->title) && !empty($offersDetails->title) ) {
          $offer_list[$i]['title'] = $offersDetails->title;
      $offer_list[$i]['description'] = $offersDetails->description;
          $offer_list[$i]['image'] = $offersDetails->image;
          $date = explode(' ',$offersDetails->validity);
          $offer_list[$i]['validity'] = date('d M Y',strtotime($date[0]));
          $i++;
        } else {
          throw new Exception('Unable to find offers');
        }
      } 
    } catch (Exception $e) {
      $this->log($e->getMessage(). '(' . __METHOD__ . ')');
    }
    $this->set([
      'response' => $offer_list,
      '_serialize' => ['response']
    ]);
   }

   /**
    * function getUserPurchaseDetails().
    *
    * $uid :  user Id
    */
   public function getUserPurchaseDetails($uid, $recent_order = TRUE, $function_call = FALSE) {
     $status = FALSE;
     $message = '';
     $purchase_details = array();
     try {
       if (empty($uid)) {
         $message = 'Please add child';
         throw new Exception('User Id is empty');
       }
       $connection = ConnectionManager::get('default');
       $sql = "SELECT users.first_name as user_first_name, users.last_name as user_last_name,"
         . " user_purchase_items.amount as purchase_amount, user_purchase_items.level_id as level_id,"
         . " user_purchase_items.course_id, user_purchase_items.order_date as order_date, packages.name as package_subjects,"
         . " packages.id as package_id, plans.id as plan_id, plans.name as plan_duration,"
         . " courses.course_name"
         . " FROM users"
         . " INNER JOIN user_purchase_items on user_purchase_items.user_id=users.id"
         . " INNER JOIN packages ON user_purchase_items.package_id=packages.id"
         . " INNER JOIN plans ON user_purchase_items.plan_id=plans.id"
         . " INNER JOIN courses ON user_purchase_items.course_id=courses.id"
         . " WHERE user_purchase_items.user_id IN (" . $uid . ")";
       if ($recent_order = TRUE) {
         $subquery = "(SELECT MAX(order_date) FROM user_purchase_items"
         . " WHERE user_id = $uid)";
         $sql.= " AND user_purchase_items.order_date = $subquery";
       }

       $purchase_details_result = $connection->execute($sql)->fetchAll('assoc');
       if (!empty($purchase_details_result)) {
         $status = TRUE;
         foreach ($purchase_details_result as $purchase_result) {
           $purchase_details['user_first_name'] = $purchase_result['user_first_name'];
           $purchase_details['user_last_name'] = $purchase_result['user_last_name'];
           $purchase_details['package_id'] = $purchase_result['package_id'];
           $purchase_details['package_subjects'] = $purchase_result['package_subjects'];
           $purchase_details['plan_id'] = $purchase_result['plan_id'];
           $purchase_details['plan_duration'] = $purchase_result['plan_duration'];
           $purchase_details['level_id'] = $purchase_result['level_id'];
           $purchase_details['order_date'] = @current(explode(' ', $purchase_result['order_date']));
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
     $response = array('status' => FALSE, 'message' => '');
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
          $col_names = 'user_id, course_id, plan_id, package_id, level_id, discount, amount, order_date';
          $backup_sql = 'INSERT INTO user_purchase_history (' . $col_names . ') SELECT '. $col_names .  ' FROM user_purchase_items where user_id =' . $child_id;
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
                )
            );
            if (!$user_purchase_items->save($user_purchase_item)) {
              $message = "unable to delete previous record";
              throw new Exception($message);
            }
          }

          //updating table on user_order
          $user_orders = TableRegistry::get('User_orders');
          $user_order = $user_orders->newEntity(
            array(
              'user_id' => $user_id,
              'amount' => $total_amount,
              'discount' => $package['discount'],
              'order_date' => date('Y-m-d H:i:s'),
              'status' => 'done',
              'trial_period' => 0,
              'card_response' => 'card deducted successfully',
            )
          );
          if (!$user_orders->save($user_order)) {
            $message = "unable to delete previous record";
            throw new Exception($message);
          }
          $response['status'] = TRUE;
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

}
