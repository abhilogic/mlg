<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Users Controller
 */
class UsersController extends AppController{


    public function initialize(){
        parent::initialize();
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
        $user = $this->Users->get($id);

        $this->set([
            'user' => $user,
            '_serialize' => ['user']
        ]);
    }

/*
        *  U3 - getUserProfilesByUUID 
        *   Request – String <UUID>;
*/
    public function getUserProfilesByUUID($id = null){
        //$user = $this->Users->get($id);
        $data['user'] = $this->Users->find('all')
                        ->contain(['UserDetails'])
                        ->where([
                            'Users.id' => $id                            
                         ]);      


        $this->set([
            'data' => $data,
            '_serialize' => ['data']
        ]);
    }



    /**  
        *  U4- setUserProfileByUUID
        *  Request – Int <uuid>; , String <firstName>; ,  String <lastName / Null>; , String <fatherName / Null>; , String <motherName / Null> 

    */
    public function setUserProfileByUUID($id = null){
        $user = $this->Users->find()->where(['Users.id'=>$id])->contain('UserDetails')->first();


            if ($this->request->is(['post', 'put'])) {
                 $this->Users->patchEntity($user, $this->request->data(), ['associated'=>['UserDetails']]);
        
            if ($result = $this->Users->save($user, ['associated'=>['UserDetails']])) {
                 $message = 'Saved';
            }
            else{
                 $message = 'Unable to save';
            }
    }

        $this->set([
            'response' => $message,
            '_serialize' => ['response']
        ]);
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







/*
     public function getUserRoles($id=null) { 

        $user = $this->Users->find('all')
                        ->contain(['UserDetails'])
                        ->where([
                            'Users.id' => $id                            
                         ]); 

     }*/


    

   
}
