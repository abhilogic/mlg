<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Core\Exception\Exception;

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
                        $userdetails = TableRegistry::get('UserDetails');
                        $userdetail=$userdetails->find('all')->where(['user_id' => $id])->toArray();
                       // $userdetail = $userdetails->get($id);
                        
                        $userdetail= $userdetails->patchEntity($userdetail, $this->request->data());
                        if($userdetails->save($userdetail)){
                          die('kkk');
                        }

                        
                    }
                      else{
                          $data['message'] = 'Unable to update the records';
                      }

                }
                else{
                    $data['response'] ='Record is not found';
                }
               
      }

        $this->set([
            'response' => $message,
            '_serialize' => ['response']
        ]);
    }


    /*U5 Service to update or set user’s password and return Boolean status. */
    public function setUserPassword($id) {
      $message = FALSE;
      if ($this->request->is(['post', 'put'])) {
        $old_password = Security::hash($this->request->data['old_password']);
        $user_count = $this->Users->find()->where(['Users.id' => $id, 'Users.password' => $old_password])->count();
        if ($user_count) {
          if (isset($this->request->data['password'])) {
            $password = Security::hash($this->request->data['password']);
            $user = $this->Users->get($id);
            $user->password = $password;
            if ($this->Users->save($user)) {
              $message = TRUE;
            }
          } else {
            $message = 'Password is not Set';
          }
        } else {
          $message = 'You have entered either wrong username or password';
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
    public function setUserMobile($id=null) {
        $user = $this->Users->get($id);
        
        if ($this->request->is(['post', 'put'])) {
           // $user = $this->Users->patchEntity($user, $this->request->data);
            if(isset($this->request->data['mobile']) ){
                $user->mobile = $this->request->data['mobile'];
                 if ($this->Users->save($user)) {
                $message = 'True';
            } else {
                $message = 'False';
            }
            }
            else{
                $message = 'Mobile is not Set';

            }
            
           
        }
        
        $this->set([
            'response' => $message,
            '_serialize' => ['response']
        ]);

    }


    /* 
        ** U7- setUserEmail 
        ** Request – Int &lt;UUID&gt; , Int &lt;email&gt;
    */
    public function setUserEmail($id=null) {
        $user = $this->Users->get($id);
        
        if ($this->request->is(['post', 'put'])) {
           // $user = $this->Users->patchEntity($user, $this->request->data);
            if(isset($this->request->data['email']) ){
                $user->email = $this->request->data['email'];
                if ($this->Users->save($user)) {
                $message = 'True';
            } else {
                $message = 'False';
            }

            }
            else{
                $message = 'Email is not set';
            }
            
        }
        
        $this->set([
            'response' => $message,
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

    /**U11 – Service to check that user still logged in or not.
     * basically to check his active session  */
    public function isUserLoggedin() {
      $response = $status = FALSE;
      $isUserLoggedin = $this->request->session()->id();
      if (!empty($isUserLoggedin)) {
        $response = $status = TRUE;
      }
      $this->set([
         'status' => $status,
         'response' => $response,
         '_serialize' => ['status', 'response']
      ]);
    }
    
    /**U11 – login User
     * loadComponent is defiened in this function for a time being. */
    public function login() {
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
          $this->Auth->setUser($user);
          $token = $this->request->session()->id();
          $status = 'success';
        } else {
          $message = 'You entered either wrong email id or password';
        }
      }
      $this->set([
        'status' => $status,
        'response' => ['secure_token' => $token],
        'message' => $message,
        '_serialize' => ['status', 'response', 'message']
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
          $startdate = $this->request->data['start_date'];
         $enddate = $this->request->data['end_date'];
        if($userorder_records>0){
              foreach ($userorders as $userorder) {
                  $orders=$userorder->user_orders;

                  foreach ($orders as $order) {
                     $orderdate = (new Time($order->order_date))->format('Y-m-d');
                      $odt=strtotime(date($orderdate));  
                      $start_date = strtotime(date($startdate)); 
                     $end_date = strtotime(date($enddate));             

                     if($start_date >= $odt || $end_date < $odt){
                        //$data['purchases'][]= $userorder->user_orders;
                      $data['purchases'][]= $order;
                     }
                     else{
                        $data['message'] = "Records are not available for selected date";
                     }                      
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
     
     /*U17 Request – String <CourseCode>, String<username> , Int <roleID> */
     public function setUserRoleByuserName() {
       $status = $response = FALSE;
       if ($this->request->is('post')) {
         $user_id = '';
//         $course_code = $this->request->data['course_code'];
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

}
