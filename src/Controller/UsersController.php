<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Core\Exception\Exception;
use Cake\Log\Log;
/**
 * Users Controller
 */
class UsersController extends AppController{

    public function initialize(){
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    /** Index method    */
    public function index(){
        $data['responseCode'] = "HTTP/1.1 200 OK"; 
        $data['Content-Type'] = "application/json";
        $data['status'] = "success";
        $data['users'] = $this->Users->find('all');

        $this->set(array(
            'data' => $data,
            '_serialize' => array('data')
        ));



    }


    /* U1 - IsUserExists */
    public function isUserExists($param){

       // $userTable = TableRegistry::get('Users');       
       // $query = $userTable->findAllByUsernameOrEmail($param, $param);
        //or
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

        return $data;
    }


    /** 
        *  U2- getUserDetails, 
        *  U3 - getUserProfilesByUUID 
        *  Request – String <UUID>;
    */
    public function getUserDetails($id = null){
        $user = $this->Users->get($id);
        $this->set([
            'user' => $user,
            '_serialize' => ['user']
        ]);
    }



    /**  
        *  U4- setUserProfileByUUID
        * Request – Int&lt;uuid&gt; , String <firstName>; ,  String <lastName / Null>; , String <fatherName / Null>; , String <motherName / Null> 

    */
    public function setUserProfileByUUID(){
        $user = $this->Users->newEntity($this->request->getData());        
        if ($this->Users->save($user)) {
            $message = 'User has been added successfuly';
            $data['response'] = True;
        } else {
            $message = 'Error during adding user, ';
            $data['response'] = False;
        }
        $this->set([
            'message' => $message,
            'data' => $data,
            '_serialize' => ['message', 'data']
        ]);
    }


    /* U6- public function */
    public function setUserMobile($id=null) {
        $user = $this->Users->get($uid);
        if ($this->request->is(['post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());

            echo "<pre>";
            print_r($user);
            die;
            if ($this->Users->save($user)) {
                $message = 'True';
            } else {
                $message = 'False';
            }
        }
        $this->set([
            'message' => $message,
            '_serialize' => ['message']
        ]);



    }

    /** Edit method  */
    public function edit($id = null){
        $user = $this->Users->get($id);
        if ($this->request->is(['post', 'put'])) {
            $recipe = $this->Recipes->patchEntity($recipe, $this->request->getData());
            if ($this->Recipes->save($recipe)) {
                $message = 'Saved';
            } else {
                $message = 'Error';
            }
        }
        $this->set([
            'message' => $message,
            '_serialize' => ['message']
        ]);
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
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
}
