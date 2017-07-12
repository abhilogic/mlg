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
use App\Controller\UsersController;
use App\Controller\PaymentController;

class TeachersController extends AppController {

  public function initialize() {
    parent::initialize();
    // $conn = ConnectionManager::get('default');
    $this->loadComponent('RequestHandler');
    $this->RequestHandler->renderAs($this, 'json');
  }
  
  /***
   * This api is used for set teacher detail in database.
   * @return Boolean value.
   * 
    * **/ 
  public function setTeacherRecord() {
    try {
      $status = FALSE;
      $message = '';
      $teacher_detail = TableRegistry::get('UserDetails');
      if ($this->request->is('post')) {
        if (empty($this->request->data['user_id'])) {
          $message = 'Login first.';
        } else if (empty($this->request->data['state'])) {
          $message = 'Choose State.';
        } else if (empty($this->request->data['district'])) {
          $message = 'Choose district';
        } else if (empty($this->request->data['country'])) {
          $message = 'Choose country.';
        } else if (empty($this->request->data['zip'])) {
          $message = 'Enter zipcode';
        } else if (empty($this->request->data['school_name'])) {
          $message = 'Enter School Name.';
        } else if (empty($this->request->data['school_address'])) {
          $message = 'Enter school address.';
        }
        if (empty($message)) {
          $id = $this->request->data['user_id'];
          $school = $this->request->data['school_name'];
          $country = $this->request->data['country'];
          $state = $this->request->data['state'];
          $district = $this->request->data['district'];
          $school_address = $this->request->data['school_address'];
          $zipcode = $this->request->data['zip'];
          $query = $teacher_detail->query();
          $result = $query->update()->set([
                      'school' => $school,
                      'country' => $country,
                      'state' => $state,
                      'district' => $district,
                      'school_address' => $school_address,
                      'zipcode' => $zipcode,
                      'step_completed' => 1
                  ])->where(['user_id' => $id])->execute();
          $row_count = $result->rowCount();
          if ($row_count == '1') {
            $status = TRUE;
          } else {
            throw new Exception('udable to update value in db');
          }
        }
      } else {
        throw new Exception('Some error occured.');
      }
    } catch (Exception $e) {
      $this->logs('Error in setTeacherRecord function in Teachers Controller'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status', 'message']
    ]);
  }

  /*   * This api is used for set teacher subject/courses/grade in database.
   * @return Boolean value.
   * 
   * * */

  public function setTeacherSubjects() {
    if (isset($this->request->data['selectedcourse']) && isset($this->request->data['user_id'])) {
      $user_courses = TableRegistry::get('UserCourses');
      $user_details = TableRegistry::get('UserDetails');
      $selected_courses = $this->request->data['selectedcourse'];
      $data_usercourse['user_id'] = $this->request->data['user_id'];

      foreach ($selected_courses as $key => $value) {
        $data_usercourse['course_id'] = $key;
        $data_usercourse['expiry_date'] = time() + 60;
        $new_usercourses = $user_courses->newEntity($data_usercourse);

        if ($user_courses->save($new_usercourses)) {
          $data['status'] = 'TRUE';
          $data['message'] = 'Sucess';
        } else {
          $data['status'] = 'FALSE';
          $data['message'] = 'Opps not able to add data on course_id' . $selectedcourse['id'];
        }
      }

      // update step_completed in user detail
      $query = $user_details->query();
      $result = $query->update()
              ->set(['step_completed' => 2])
              ->where(['user_id' => $data_usercourse['user_id']])
              ->execute();
    } else {
      $data['status'] = 'FALSE';
      $data['message'] = 'Please select at least one course';
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  /*   * *
   * This api is used for get teacher detail in database.
   * @return Boolean value.
   * 
   * * */

  public function getTeacherSubject() {
    try {
      $status = FALSE;
      $message = '';
      $sub_details = array();
      $total = 0;
      if ($this->request->is('post')) {
        if (empty($this->request->data['uid'])) {
          $message = 'Please login.';
        }
        $user_id = $this->request->data['uid'];
      }
      if (empty($message)) {
        $connection = ConnectionManager::get('default');
        $sql = " SELECT *,courses.id as id from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
                . " WHERE user_courses.user_id =" . $user_id . ' ORDER BY level_id';
        $result = $connection->execute($sql)->fetchAll('assoc');
        $count = count($result);
        $grade = '';
        $i = 0;
        if ($count > 0) {
          $status = TRUE;
          foreach ($result as $detail) {
            $total = $total + $detail['price'];
            if (!empty($grade) && $grade == $detail['level_id']) {
              $sub_details[$i]['course_name'] = $sub_details[$i]['course_name'] . ',' . $detail['course_name'];
              $sub_details[$i]['price'] = $sub_details[$i]['price'] + $detail['price'];
            } else if (!empty($grade) && $grade != $detail['level_id']) {
              $i++;
              $grade = $detail['level_id'];
              $sub_details[$i]['grade'] = $detail['level_id'];
              $sub_details[$i]['course_name'] = $detail['course_name'];
              $sub_details[$i]['price'] = $detail['price'];
            } else if (empty($grade)) {
              $grade = $detail['level_id'];
              $sub_details[$i]['grade'] = $detail['level_id'];
              $sub_details[$i]['course_name'] = $detail['course_name'];
              $sub_details[$i]['price'] = $detail['price'];
            }
          }
        } else {
          $message = 'You have not taught any subject yet.';
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getTeacherSubject function in Teachers Controller.s'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        'data' => $sub_details,
        'total' => $total,
        '_serialize' => ['status', 'message', 'data', 'total']
    ]);
  }

  /**
   * This api is used for get student detail of a class and subject. 
   * */
  public function getStudentDetail($grade = '', $subject = '', $type) {
    try {
      $user = array();
      $message = '';
      if (empty($grade)) {
        $message = 'grade is empty';
        throw new Exception('grade is empty');
      } elseif (empty($subject)) {
        $message = 'subject id is empty.';
        throw new Exception('subject id is empty.');
      } elseif (empty($message)) {
        $connection = ConnectionManager::get('default');
        $sql = "SELECT * FROM users as user
          Inner Join user_details as userDetail on user.id = userDetail.user_id
          Inner Join user_courses as userCourse on user.id = userCourse.user_id 
          Inner Join courses as course on course.id = userCourse.course_id
          Inner Join user_roles as role on user.id = role.user_id
          where course.level_id = " . $grade . " AND course.id = " . $subject . " AND role.role_id = " . $type;
        $result = $connection->execute($sql)->fetchAll('assoc');
        foreach ($result as $data) {
          $user_detail['name'] = $data['first_name'] . ' ' . $data['last_name'];
          $user_detail['profile_pic'] = $data['profile_pic'];
          $user_detail['user_id'] = $data['user_id'];
          $user_detail['courses_id'] = $data['course_id'];
          $user[] = $user_detail;
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getStudentDetail function in Teachers Controller'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $user,
        '_serialize' => ['data']
    ]);
  }

  /**
   * This api is used for get teacher subject with grade.
   * 
   * */
  public function getTeacherGradeSubject($user_id = '', $type, $func_name = null) {
    try {
      $connection = ConnectionManager::get('default');
      $status = FALSE;
      $message = '';
      $role_count = 0;
      $level_subject = 'Something goes wrong.';
      $level = array();
      $urldata = array();
      $subject = array();
      if (empty($user_id)) {
        $message = 'user id is not valid';
        throw new Exception('user id is not valid');
      } else {
        $sql = " SELECT * from user_roles WHERE user_id =" . $user_id .
                " AND role_id = " . $type;
        $result = $connection->execute($sql)->fetchAll('assoc');
        $role_count = count($result);
      }
      if ($role_count == '1' && empty($message)) {
        $level_subject = 'you have not choosen any subject or grade.';
        $connection = ConnectionManager::get('default');
        $sql = " SELECT level_id,course_id,course_name,name from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
                . " INNER JOIN levels ON courses.level_id = levels.id "
                . " WHERE user_courses.user_id =" . $user_id . " ORDER BY level_id ";
        $result = $connection->execute($sql)->fetchAll('assoc');
//        print_r($result);
        $count = count($result);
        $level_subject = $result;
        $temp = '';
        $temp_subj = '';
        $level = array();
        if ($count > 0) {
          $status = TRUE;
          foreach ($result as $data) {
            if (empty($temp)) {
              $temp = $data['level_id'];
              $urldata['level_id'] = $data['level_id'];
              $urldata['course_id'] = $data['course_id'];
              $urldata['course_name'] = $data['course_name'];
              $leveltemp['id'] = $data['level_id'];
              $leveltemp['name'] = $data['name'];
              $level[] = $leveltemp;
            } else if ($temp != $data['level_id']) {
              $temp = $data['level_id'];
              $leveltemp['id'] = $data['level_id'];
              $leveltemp['name'] = $data['name'];
              $level[] = $leveltemp;
            }

            if (empty($temp_subj)) {
              $temp_subj = $data['course_id'];
              $subject['course_name'] = $data['course_name'];
              $subject['course_id'] = $data['course_id'];
            } else if ($temp_subj != $data['course_id']) {
              $temp_subj = $data['course_id'];
              $subject['course_name'] = $subject['course_name'] . ',' . $data['course_name'];
              $subject['course_id'] = $subject['course_id'] . ',' . $data['course_id'];
            }
          }
        } else {
          $level_subject = 'you have not choosen any subject or grade.';
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getTeacherGradeSubject function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    if ($func_name == 'setEvent') {
      return $level_subject;
    }
    $this->set([
        'status' => $status,
        'response' => $level_subject,
        'grade' => $level,
        'subject' => $subject,
        'urlData' => $urldata,
        'url' => Router::url('/', true),
        '_serialize' => ['status', 'response', 'grade', 'subject', 'urlData', 'url']
    ]);
  }

  /**
   * this function is used for fetch teacher detail regarding to their grade.
   * */
  public function getTeacherDetailsForLesson($tid, $grade, $subject = '',$type) {
    try {
      $connection = ConnectionManager::get('default');
      if ($subject == '-1') {
        $sql = " SELECT level_id,course_id,course_name from courses"
                . " INNER JOIN user_courses ON courses.id = user_courses.course_id "
                . " WHERE user_courses.user_id =" . $tid . " AND courses.level_id =" . $grade;
      } else {
//      $sql = " SELECT * from course_contents as content "
//              . "INNER JOIN course_details as detail ON content.course_detail_id = detail.course_id "
//              . " WHERE detail.course_id =".$subject;
        $sql = " SELECT * from courses "
                . " WHERE id =" . $subject;
      }
      $result = $connection->execute($sql)->fetchAll('assoc');
    } catch (Exception $e) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $result,
        '_serialize' => ['response']
    ]);
  }

  /**
   * this function is used for add lesson.
   * */
  public function setContentForLesson() {
    try {
      $data['message'] = array();
      $subskill = '';
      $temp_message = '';
      $content = array();
      $status = FALSE;
      $connection = ConnectionManager::get('default');
      $course_detail = TableRegistry::get('CourseContents');
      if ($this->request->is('post')) {
        $id = 0;
        if (isset($this->request->data['tid']) && empty($this->request->data['tid'])) {
          $data['message'][7] = "Please login.";
        } elseif (isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
          $data['message'][0] = "please select a grade.";
        } elseif ($this->request->data['course'] == '-1') {
          $data['message'][1] = "please select a course.";
        } elseif (empty($this->request->data['standard'])) {
          $data['message'][2] = "please select a standard.";
        } elseif (empty($this->request->data['standard_type'])) {
          $data['message'][3] = "please select a standard type.";
        } elseif (empty($this->request->data['lesson'])) {
          $data['message'][4] = "please select a lesson name";
        } elseif (empty($this->request->data['skills'])) {
          $data['message'][5] = "please select skills.";
        } elseif (empty($this->request->data['sub_skill'])) {
          $data['message'][6] = "please select sub skills.";
        }
        $uid = $this->request->data['tid'];
        foreach ($data['message']as $ki => $val) {
          $temp_message = $val;
        }
        if (empty($temp_message)) {
          if (!empty($this->request->data['content'])) {
            if ($this->request->data['type'] == 'text') {
              $content = $this->request->data['content'];
            } else {
              $temp_data = explode(',', $this->request->data['content']);
              foreach ($temp_data as $key => $value) {
                 $temp_string = json_decode($value);
                if ($key == 0) {
                  $content = $temp_string->response;
                } else {
                  $content = $content . ',' . $temp_string->response;
                }
              }
            }
          }
          if (!empty($this->request->data['sub_skill'])) {
            $subskill = $this->request->data['sub_skill'];
          }
          foreach ($subskill as $key => $value) {
            $detail = $course_detail->newEntity();
            if ($id != 0) {
              $detail->id = $id+1;
            }
            $detail->created_by = $uid;
            $detail->lesson_name = isset($this->request->data['lesson']) ? $this->request->data['lesson'] : '';
            $detail->course_detail_id = $value;
            if (!isset($this->request->data['title']) && empty($this->request->data['title'])) {
              $data['message'][8] = 'Please give title.';
              $temp_message = 'Please give title.';
            } else {
              $title = $this->request->data['title'];
            }
            if (empty($this->request->data['content'])) {
              $data['message'][9] = 'Content can not be empty.';
              $temp_message = 'Content can not be empty.';
            }
            if (!empty($content) && empty($temp_message)) {
              $detail->title = $title;
              $detail->standards = implode(',', $this->request->data['standard']);
              $detail->standard_type = implode(',', $this->request->data['standard_type']);
              $detail->title = $title;
              $detail->type = $this->request->data['type'];
              $detail->status = 'approved';
              $detail->shared_mode = $this->request->data['shared_mode'];
              $detail->content = $content;
              if ($course_detail->save($detail)) {
                $id = $detail->id;
                $points = TableRegistry::get('mlg_points');
                $user_points = TableRegistry::get('user_points');
                $get_points = $user_points->newEntity();
                $get_points->user_id = $this->request->data['tid'];
                $get_points->course_content_id = $id;
                $get_points->point_type_id = $this->request->data['point_type'];
                $point = $points->find()->where(['id' => $this->request->data['point_type']]);
                foreach ($point as $key => $value) {
                  $get_points->points = $value['points'];
                }
                $get_points->status = 1;
                $get_points->created_date = date('y-m-d ', time());
                $user_points->save($get_points);
                $data['message'][10] = 'Value Inserted Successfully';
                $status = TRUE;
              } else {
                $data['message'][11] = 'Unable to insert the subSkill value .';
                $temp_message = 'Unable to insert the subSkill value .';
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      $this->log('Error in setContentForLesson function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $data['message'],
        'status' => $status,
        '_serialize' => ['response', 'status']
    ]);
  }

  /**
   * This function is used for read csv for add lesson.
   * 
   * */
  public function readCsv() {
    try {
      $status = FALSE;
      $message = '';
      $headers = array('grade', 'subject', 'lesson', 'skills', 'sub_skills', 'standard_type', 'standard',
          'text_title', 'text_description', 'video_title', 'video_url', 'image_title', 'image_url');
      if (!isset($this->request->data['csv']) || (@end(explode('/', $csv['type'])) == 'csv')) {
        $message = 'please upload CSV';
        throw new Exception('please upload CSV');
      }
      $csv = $this->request->data['csv'];
      $file = fopen($csv['tmp_name'], 'r');
      $first_row = TRUE;
      $course_detail = TableRegistry::get('CourseContents');
      while ($row = fgetcsv($file, '', ':')) {
        if ($first_row) {
          $first_row = FALSE;
          continue;
        }
        $id = 0;
        $temp = array_combine($headers, $row);
        $skill = explode(',', $temp['skills']);
        if (count($skill) > 0) {
          foreach ($skill as $value) {
            if ($id != 0) {
              $detail->id = $id + 1;
            }
            $detail = $course_detail->newEntity();
            $detail->name = isset($temp['lesson']) ? $temp['lesson'] : '';
            $detail->text_title = isset($temp['text_title']) ? $temp['text_title'] : '';
            $detail->text_description = isset($temp['text_description']) ? $temp['text_description'] : '';
            $detail->video_title = isset($temp['video_title']) ? $temp['video_title'] : '';
            $detail->video_url = isset($temp['video_url']) ? $temp['video_url'] : '';
            $detail->image_title = isset($temp['image_title']) ? $temp['image_title'] : '';
            $detail->image_url = isset($temp['image_url']) ? $temp['image_url'] : '';
            $detail->course_detail_id = $value;
            if ($course_detail->save($detail)) {
              $id = $detail->id;
            }
          }
        }
        $sub_skill = explode(',', $temp['sub_skills']);
        if (count($sub_skill) > 0) {
          foreach ($sub_skill as $value) {
            if ($id != 0) {
              $detail->id = $id + 1;
            }
            $detail = $course_detail->newEntity();
            $detail->name = isset($temp['lesson']) ? $temp['lesson'] : '';
            $detail->text_title = isset($temp['text_title']) ? $temp['text_title'] : '';
            $detail->text_description = isset($temp['text_description']) ? $temp['text_description'] : '';
            $detail->video_title = isset($temp['video_title']) ? $temp['video_title'] : '';
            $detail->video_url = isset($temp['video_url']) ? $temp['video_url'] : '';
            $detail->image_title = isset($temp['image_title']) ? $temp['image_title'] : '';
            $detail->image_url = isset($temp['image_url']) ? $temp['image_url'] : '';
            $detail->course_detail_id = $value;
            if ($course_detail->save($detail)) {
              $id = $detail->id;
            }
          }
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
  }

  public function uploadfile() {
    $file_name = time() . '-' . $_FILES['uploadfile']['name'];
    move_uploaded_file($_FILES['uploadfile']['tmp_name'], WWW_ROOT . '/upload/' . $file_name);
    $this->set([
        'response' => $file_name,
        '_serialize' => ['response']
    ]);
  }

  public function saveTemplate() {
    try {
      $connection = ConnectionManager::get('default');
      $status = FALSE;
      $message = '';
      $id = '';
      $template_detail = TableRegistry::get('ContentTemplate');
      $relation = TableRegistry::get('content_template_relation');
      if (isset($this->request->data) && !empty($this->request->data)) {
        if (!empty($this->request->data['cont_type']) && $this->request->data['cont_type'] == 'lesson') {
          if (isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
            $message = "please select a grade.";
          } elseif ($this->request->data['course'] == '-1') {
            $message = "please select a course.";
          } elseif (empty($this->request->data['standard_type'])) {
            $message = "please select a standard type.";
          } elseif (empty($this->request->data['standard'])) {
            $message = "please select a standard.";
          } elseif (empty($this->request->data['lesson'])) {
            $message = "please select a lesson name";
          } elseif (empty($this->request->data['skills'])) {
            $message = "please select skills.";
          } elseif (empty($this->request->data['sub_skill'])) {
            $message = "please select sub skills.";
          } else if (empty($this->request->data['temp_name'])) {
            $message = 'Please give template name.';
          } else {
            //content_share_mode column in course_content 0 privat 1 public
            $standard = implode(',', $this->request->data['standard']);
            $standard_type = implode(',', $this->request->data['standard_type']);
            $content = $template_detail->newEntity();
            $content->template_name = isset($this->request->data['temp_name']) ? $this->request->data['temp_name'] : '';
            $content->created_by = isset($this->request->data['tid']) ? $this->request->data['tid'] : '';
            $content->grade = isset($this->request->data['grade']) ? $this->request->data['grade'] : '';
            $content->standard = $standard;
            $content->standard_type = $standard_type;
            $content->course_id = isset($this->request->data['course']) ? $this->request->data['course'] : '';
            $content->skill_ids = implode(',', $this->request->data['skills']);
            $content->sub_skill_ids = implode(',', $this->request->data['sub_skill']);
            $content->content_type = isset($this->request->data['cont_type']) ? $this->request->data['cont_type'] : '';
            $content->content_type = isset($this->request->data['cont_type']) ? $this->request->data['cont_type'] : '';
            $content->template_status = isset($this->request->data['template_status']) ? $this->request->data['template_status'] : '';
            $id = $template_detail->save($content)->id;
            if (is_numeric($id) && $id > 0) {
              $message = 'Value Inserted Successfully';
              $status = TRUE;
            }
          }
        } else if (!empty($this->request->data['cont_type']) && $this->request->data['cont_type'] == 'question') {
          if (isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
            $message = "please select a grade.";
          } elseif ($this->request->data['course'] == '-1') {
            $message = "please select a course.";
          } elseif (empty($this->request->data['standard_type'])) {
            $message = "please select a standard type.";
          } elseif (empty($this->request->data['standard'])) {
            $message = "please select a standard.";
          } elseif (empty($this->request->data['skills'])) {
            $message = "please select skills.";
          } elseif (empty($this->request->data['sub_skill'])) {
            $message = "please select sub skills.";
          } else if (empty($this->request->data['ques_diff'])) {
            $message = 'Please select difficulity level of question.';
          } else if (empty($this->request->data['claim'])) {
            $message = 'Please give claim.';
          } else if (empty($this->request->data['scope'])) {
            $message = 'Please give scope.';
          } else if (empty($this->request->data['dok'])) {
            $message = 'Please provide depth of knowledge.';
          } else if (empty($this->request->data['ques_passage'])) {
            $message = 'Please give passage.';
          } else if (empty($this->request->data['ques_target'])) {
            $message = 'Please give question target.';
          } else if (empty($this->request->data['task'])) {
            $message = 'Please give task.';
          } else if (empty($this->request->data['ques_complexity'])) {
            $message = 'Please give question complexity.';
          } else if (empty($this->request->data['temp_name'])) {
            $message = 'Please give template name.';
          } else {
            $standard = implode(',', $this->request->data['standard']);
            $standard_type = implode(',', $this->request->data['standard_type']);
            $question = $this->request->data['ques_type'];
            $content = $template_detail->newEntity();
            $content->template_name = isset($this->request->data['temp_name']) ? $this->request->data['temp_name'] : '';
            $content->created_by = isset($this->request->data['tid']) ? $this->request->data['tid'] : '';
            $content->grade = isset($this->request->data['grade']) ? $this->request->data['grade'] : '';
            $content->standard = $standard;
            $content->standard_type = $standard_type;
            $content->course_id = isset($this->request->data['course']) ? $this->request->data['course'] : '';
            $content->skill_ids = implode(',', $this->request->data['skills']);
            $content->sub_skill_ids = implode(',', $this->request->data['sub_skill']);
            $content->difficulity_level = isset($this->request->data['ques_diff']) ? $this->request->data['ques_diff'] : '';
            $content->claim = isset($this->request->data['claim']) ? $this->request->data['claim'] : '';
            $content->scope = isset($this->request->data['scope']) ? $this->request->data['scope'] : '';
            $content->depth_of_knowledge = isset($this->request->data['dok']) ? $this->request->data['dok'] : '';
            $content->passage = isset($this->request->data['ques_passage']) ? $this->request->data['ques_passage'] : '';
            $content->secondary_target = isset($this->request->data['ques_target']) ? $this->request->data['ques_target'] : '';
            $content->task_noties = isset($this->request->data['task']) ? $this->request->data['task'] : '';
            $content->text_compexity = isset($this->request->data['ques_complexity']) ? $this->request->data['ques_complexity'] : '';
            $content->question = $question;
            $content->assignment = isset($this->request->data['assignment']) ? $this->request->data['assignment'] : '';
            $content->content_type = isset($this->request->data['cont_type']) ? $this->request->data['cont_type'] : '';
            $content->template_status = isset($this->request->data['template_status']) ? $this->request->data['template_status'] : '';
            $id = $template_detail->save($content)->id;
            if (is_numeric($id) && $id > 0) {
              $message = 'Template Saved.';
              $status = TRUE;
            } else {
              $message = 'Template Not Saved.';
            }
          }
        }
      }
      if ($status == TRUE && isset($this->request->data['last_question_id']) && !empty($this->request->data['last_question_id'])) {
          $rel = $relation->newEntity();
          $rel->template_id = $id;
          $rel->template_type = $this->request->data['cont_type'];
          $rel->created_by = isset($this->request->data['tid']) ? $this->request->data['tid'] : '';
          $rel->content_id = isset($this->request->data['last_question_id']) ? $this->request->data['last_question_id'] : '';
          $relation->save($rel);
      }
    } catch (Exception $e) {
      
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        'template_id' => $id,
        '_serialize' => ['status', 'message', 'template_id']
    ]);
  }

  public function getTemplate($user_id, $type) {
    try {
      $content = array();
      $content_detail = array();
      $template_detail = TableRegistry::get('ContentTemplate');
    $template = $template_detail->find('all')->where(['created_by' => $user_id, 'content_type' => $type,'template_status'=> '1']);
      foreach ($template as $key => $value) {
        $content_detail['id'] = $value['id'];
        $content_detail['template_name'] = $value['template_name'];
        $content_detail['user_id'] = $value['user_id'];
        $content_detail['grade'] = $value['grade'];
        $content_detail['standard'] = explode(',', $value['standard']);
        $content_detail['standard_type'] = explode(',', $value['standard_type']);
        $content_detail['course_id'] = $value['course_id'];
        $content_detail['skills'] = explode(',', $value['skill_ids']);
        $content_detail['sub_skill'] = explode(',', $value['sub_skill_ids']);
        if ($type == 'question') {
          $content_detail['ques_diff'] = $value['difficulity_level'];
          $content_detail['claim'] = $value['claim'];
          $content_detail['scope'] = $value['scope'];
          $content_detail['dok'] = $value['depth_of_knowledge'];
          $content_detail['ques_passage'] = $value['passage'];
          $content_detail['ques_target'] = $value['secondary_target'];
          $content_detail['task'] = $value['task_noties'];
          $content_detail['ques_complexity'] = $value['text_compexity'];
          $content_detail['ques_type'] = explode(',', $value['question']);
          $content_detail['assignment'] = $value['assignment'];
        }
        $content[] = $content_detail;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $content,
        'status' => true,
        '_serialize' => ['data', 'status']
    ]);
  }

  public function deleteContent($id) {
    try {
      $message = '';
      $status = FALSE;
      $connection = ConnectionManager::get('default');
      $delete_sql = 'DELETE FROM course_contents WHERE id =' . $id;
      if (!$connection->execute($delete_sql)) {
        $message = "unable to delete content";
        throw new Exception($message);
      } else {
        $message = "Content deleted successfully.";
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['message', 'status']
    ]);
  }

  public function getDifficulty() {
    try {
      $status = FALSE;
      $difficulty = TableRegistry::get('difficulties');
      $diff_detail = $difficulty->find('all');
      if (count($diff_detail) > 0) {
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $diff_detail,
        'status' => $status,
        '_serialize' => ['data', 'status']
    ]);
  }

  public function getQuestionType() {
    try {
      $status = FALSE;
      $ques_type = TableRegistry::get('item_types');
      $ques = $ques_type->find('all');
      if (count($ques) > 0) {
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $ques,
        'status' => $status,
        '_serialize' => ['data', 'status']
    ]);
  }

  public function getUserContent() {
    try {
      $status = FALSE;
      $content = '';
//      $course_content = TableRegistry::get('course_contents');
      $connection = ConnectionManager::get('default');
      if ($this->request->is('post')) {
        if (isset($this->request->data['uid']) && empty($this->request->data['uid'])) {
          $content = 'please login';
          throw new Exception('please login');
        } elseif (isset($this->request->data['subskills']) && empty($this->request->data['subskills'])) {
          $content = 'please select subskills.';
          throw new Exception('please select subskills');
        }
      }
      if ($content == '') {
        $skills = implode(',',$this->request->data['subskills']);
        $sql = "Select * ,cs.id as id from course_contents as cs "
                . "INNER JOIN course_details as cd ON cd.course_id = cs.course_detail_id"
                . " where cs.created_by = ".$this->request->data['uid']." AND cs.course_detail_id IN ($skills)";
        $content = $connection->execute($sql)->fetchAll('assoc');     
//        $content = $course_content->find('all')->where(['created_by' => $this->request->data['uid'], 'course_detail_id IN' => $skills]);
        $status = TRUE;
      }
    } catch (Exception $e) {
      $this->log('Error in getUserContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $content,
        'status' => $status,
        'url' => Router::url('/', true),
        '_serialize' => ['data', 'status', 'url']
    ]);
  }

  public function setUserContent($subSkill_id = '') {
    try {
      $skill_id = '';
      $message = '';
      $connection = ConnectionManager::get('default');
      if ($subSkill_id == '') {
        $message = 'unable to find subskill.';
        throw new Exception('unable to find subskill.');
      } else {
        $sub_skill_sql = "Select * from course_details where course_id IN (" . $subSkill_id . ")";
        $sub_skill = $connection->execute($sub_skill_sql)->fetchAll('assoc');
        $sql = "Select course_id,parent_id,name from course_details where course_id IN (Select parent_id from course_details where course_id IN (" . $subSkill_id . ") )";
        $skill = $connection->execute($sql)->fetchAll('assoc');
        foreach ($skill as $key => $value) {
          if ($key == 0) {
            $skill_id = $value['parent_id'];
          } else {
            $skill_id = $skill_id . ',' . $value['parent_id'];
          }
        }
        if (!empty($skill_id)) {
          $skill_sql = "Select * from courses where id IN (" . $skill_id . ")";
          $subject = $connection->execute($skill_sql)->fetchAll('assoc');
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getUserContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'skill' => $skill,
        'subject' => $subject,
        'sub_skill' => $sub_skill,
        'message' => $message,
        '_serialize' => ['skill', 'subject', 'sub_skill', 'message']
    ]);
  }

  public function updateUserContent() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        $course_content = TableRegistry::get('CourseContents');
        if (isset($this->request->data['id']) && empty($this->request->data['id'])) {
          $message = 'Some Error Occurred in updation.';
          throw new Exception('Content id not found');
        } else if (isset($this->request->data['updated_content']) && empty($this->request->data['updated_content'])) {
          $message = 'Please add some content.';
          throw new Exception('Updated content is empty');
        } elseif (isset($this->request->data['title']) && empty($this->request->data['title'])) {
          $message = 'Please give title for content.';
          throw new Exception('Title can not be empty.');
        } elseif (isset($this->request->data['type']) && empty($this->request->data['type'])) {
          $message = 'Please add some content.';
          throw new Exception('Type cannot be empty.');
        }
        if ($message == '') {
          $type = $this->request->data['type'];
          if (!empty($this->request->data['updated_content'])) {
            if ($this->request->data['type'] == 'text') {
              $content = $this->request->data['updated_content'];
            } else {
              $temp_data = explode(',', $this->request->data['updated_content']);
              foreach ($temp_data as $key => $value) {
                 $temp_string = json_decode($value);
                if ($key == 0) {
                  $content = $temp_string->response;
                } else {
                  $content = $content . ',' . $temp_string->response;
                }
              }
              $content = $this->request->data['pre_content'] . ',' . $content;
            }
          }
          $query = $course_content->query();
          $result = $query->update()->set([
                      'content' => $content
                  ])->where(['id' => $this->request->data['id']])->execute();
          $row_count = $result->rowCount();
          if ($row_count == 1) {
            $message = 'Lesson content updated successfully.';
            $status = TRUE;
          } else {
            $message = 'Failed into update lesson content.';
            throw new Exception('Failed to update lesson content.');
          }
        }
      }
    } catch (Exception $e) {
      $this->log('Error in updateUserContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        '_serialize' => ['status', 'message']
    ]);
  }

  // add student of a teacher
  public function addStudent() {
    try {

      if ($this->request->is('post')) {
        //$postdata=$this->request->data;
        $users = TableRegistry::get('Users');
        $data['message'][] = "";

        //username validation ******
        $postdata['username'] = isset($this->request->data['username']) ? $this->request->data['username'] : "";
        if (!empty($postdata['username'])) {
          $username_exist = $users->find()->where(['Users.username' => $this->request->data['username']])->count();
          if ($username_exist) {
            $data['message'][0] = 'Username already exist';
          }
        } else {
          $data['message'][0] = "User Name is required to child login";
        }

        //email validation ********                           
        $postdata['email'] = isset($this->request->data['email']) ? $this->request->data['email'] : "";
        if (!empty($postdata['email'])) {
          $email_exist = $users->find()->where(['Users.email' => $this->request->data['email']])->count();
          if ($email_exist) {
            $data['message'][1] = 'Email is already exist';
          }
          if (!filter_var($postdata['email'], FILTER_VALIDATE_EMAIL)) {
            $data['message'][1] = 'Email is not valid';
          }
        } else {
          $data['message'][1] = 'Email cannot be empty';
        }


        //password
        $pass = isset($this->request->data['password']) ? $this->request->data['password'] : "";
        if (!empty($pass)) {
          // check emailchoice is yes/no 
          //$pass= rand(1, 1000000); 
          // $default_hasher = new DefaultPasswordHasher();
          //$password=$default_hasher->hash($pass);
          $postdata['password'] = $pass;
          $postdata['open_key'] = bin2hex($pass);  // encrypt a string
        } else {
          $data['message'][9] = 'Password cannot be empty';
        }


        if (isset($this->request->data['first_name']) && !empty($this->request->data['first_name'])) {
          $postdata['first_name'] = $this->request->data['first_name'];
        } else {
          $data['message'][2] = "First name is require";
        }

        if (isset($this->request->data['last_name']) && !empty($this->request->data['last_name'])) {
          $postdata['last_name'] = $this->request->data['last_name'];
        } else {
          $data['message'][3] = "Last name is require";
        }


        $postdata['teacher_id'] = isset($this->request->data['teacher_id']) ? $this->request->data['teacher_id'] : $data['message'][4] = "The your has been expired. please Login Again";


        $postdata['school'] = isset($this->request->data['school']) ? $this->request->data['school'] : $data['message'][5] = "School Name is require";
        // $postdata['dob']=isset($this->request->data['dob'])? $this->request->data['dob']:'';

        $postdata['course_id'] = isset($this->request->data['course_id']) ? $this->request->data['course_id'] : null;

        $postdata['grade_id'] = isset($this->request->data['grade_id']) ? $this->request->data['grade_id'] : null;

        $postdata['role_id'] = $this->request->data['role_id'];
        $postdata['status'] = $this->request->data['status'];
        //$postdata['created']=$this->request->data['created'];
        //$postdata['modfied']=$this->request->data['created'];
        //$postdata['order_date']=$this->request->data['created'];

        $postdata['created'] = time();
        $postdata['modfied'] = time();
        $postdata['order_date'] = time();

        $postdata['promocode_id'] = isset($this->request->data['vcode']) ? $this->request->data['vcode'] : '0';


        /* $postdata['package_id']=isset($this->request->data['package_id'])?$this->request->data['package_id']:$data['message'][7]="Please select package for your child";
          $postdata['plan_id']=isset($this->request->data['plan_id'])?$this->request->data['plan_id']:$data['message'][8]="Please slelect Plans for your child";
          $postdata['level_id']=$this->request->data['level_id']; */


        $data['message'] = array_filter($data['message']); // to check array is empty array_filter return(0, null)
        if (empty($data['message']) || $data['message'] == "") {

          $user_details = TableRegistry::get('UserDetails');
          $user_roles = TableRegistry::get('UserRoles');
          $user_courses = TableRegistry::get('UserCourses');
          $student_teachers = TableRegistry::get('StudentTeachers');
          $user_purchase_items = TableRegistry::get('UserPurchaseItems');
          $subtotal = 0;
          $count = 0;

          // parent information by $pid
          $parent_records = $users->find('all')->where(["id" => $postdata['teacher_id']]);
          foreach ($parent_records as $parent_record) {
            $parentinfo['email'] = $parent_record['email'];
            $parentinfo['first_name'] = $parent_record['first_name'];
            $parentinfo['last_name'] = $parent_record['last_name'];
            $parentinfo['subscription_end_date'] = $parent_record['subscription_end_date'];
          }

          $from = 'logicdeveloper7@gmail.com';
          $subject = "Your Child authenticatation";
          $email_message = "Hello " . $parent_record['first_name'] . $parent_record['last_name'] .
                  "
                                  Your Child Login Credential in My Learning Guru is 
                                  User Name :" . $postdata['username'] . " 
                                  Password : " . $pass;

          $to = $postdata['email'];

          // To get the subscription of teacher to define the subscription date of student.
          $postdata['subscription_end_date'] = $parentinfo['subscription_end_date'];


          //1. User Table


          $new_user = $users->newEntity($postdata);
          if ($result = $users->save($new_user)) {
            /* if($this->sendEmail($to, $from, $subject,$email_message)){
              $data['message']="mail send";
              }else{
              $data['message']="mail send";
              } */
            $postdata['user_id'] = $result->id;
            $postdata['student_id'] = $result->id;

            //2.  User Details Table
            $new_user_details = $user_details->newEntity($postdata);
            if ($user_details->save($new_user_details)) {

              //3. User Roles Table
              $new_user_roles = $user_roles->newEntity($postdata);
              if ($user_roles->save($new_user_roles)) {

                //4. Student-Teacher table
                $new_student_teachers = $student_teachers->newEntity($postdata);
                if ($student_teachers->save($new_student_teachers)) {

                  //5.  User Courses Table
                  $courses = $this->request->data['courses'];
                  foreach ($courses as $course_id => $name) {
                    $postdata['course_id'] = $course_id;


                    $new_user_courses = $user_courses->newEntity($postdata);
                    if ($user_courses->save($new_user_courses)) {
                      $data['status'] = "True";
                      $data['message'] = " Studen- teacher relationship is added.";
                    } else {
                      $data['status'] = 'flase';
                      $data['message'] = " Not able to save data in User Courses Table";
                      throw new Exception("Not able to save data in User Courses Table");
                    }
                  }
                } else {
                  $data['status'] = "False";
                  $data['message'] = " Not able to save data in Student Teacher Table";
                  throw new Exception("Not able to save data in Student Teacher Table");
                }
              } else {
                $data['status'] = 'flase';
                $data['message'] = " Not able to save data in User Roles Table";
                throw new Exception("Not able to save data in User Roles Table");
              }
            } else {
              $data['status'] = 'flase';
              $data['message'] = "Not able to save data in User Details Table";
              throw new Exception("Not able to save data in User Details Table");
            }
            //$data['status']='True';
          } else {
            $data['status'] = 'flase';
            $data['message'] = "Not Able to add data in user table";
            throw new Exception("Not Able to add data in user table");
          }
        } else {
          $data['status'] = "False";
          // $data['message']="All are validate ";
        }
      } else {
        $data['status'] = 'No data is send/post to save';
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage());
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  //API to update the student
  public function updateStudent($sid = null) {

    $data['message'][] = "";
    if ($this->request->is('post')) {
      $users = TableRegistry::get('Users');
      $connection = ConnectionManager::get('default');

      if (isset($this->request->data['first_name']) && !empty($this->request->data['first_name'])) {
        $postdata['first_name'] = $this->request->data['first_name'];
      } else {
        $data['message'][2] = "First name is require";
      }

      if (isset($this->request->data['last_name']) && !empty($this->request->data['last_name'])) {
        $postdata['last_name'] = $this->request->data['last_name'];
      } else {
        $data['message'][3] = "Last name is require";
      }


      $postdata['id'] = isset($this->request->data['id']) ? $this->request->data['id'] : $data['message'][0] = "Student id is null. Please check";
      $postdata['first_name'] = isset($this->request->data['first_name']) ? $this->request->data['first_name'] : $data['message'][2] = "First name is require";
      $postdata['last_name'] = isset($this->request->data['last_name']) ? $this->request->data['last_name'] : $data['message'][3] = "Last Name is require";

      //email validation ********                           
      $postdata['email'] = isset($this->request->data['email']) ? $this->request->data['email'] : "";
      if (!empty($postdata['email'])) {
        //$email_exist = $users->find()->where(['Users.email' => $this->request->data['email'] ])->count();

        $email_check_str = "SELECT * FROM users WHERE id!=" . $postdata['id'] . "  AND email='" . $postdata['email'] . "'";
        $email_check_result = $connection->execute($email_check_str)->fetchAll('assoc');
        $email_exist = count($email_check_result);
        if ($email_exist > 0) {
          $data['message'][1] = 'Email is already exist';
        }
        if (!filter_var($postdata['email'], FILTER_VALIDATE_EMAIL)) {
          $data['message'][1] = 'Email is not valid';
        }
      } else {
        $data['message'][1] = 'Email cannot be empty';
      }


      //password
      $pass = isset($this->request->data['password']) ? $this->request->data['password'] : "";
      if (!empty($pass)) {
        // check emailchoice is yes/no 
        //$pass= rand(1, 1000000); 
        $default_hasher = new DefaultPasswordHasher();
        $password = $default_hasher->hash($pass);
        $postdata['password'] = $password;

        $postdata['open_key'] = bin2hex($pass);  // encrypt a string
      } else {
        $data['message'][9] = 'Password cannot be empty';
      }

      if (empty($data['message']) || $data['message'] == [""]) {
        $upsql = "UPDATE users,user_details    
                           SET 
                            users.first_name = '" . $postdata['first_name'] . "', users.last_name = '" . $postdata['last_name'] . "', 
                            users.email='" . $postdata['email'] . "', users.password = '" . $postdata['password'] . "',
                            user_details.open_key = '" . $postdata['open_key'] . "'    
                            WHERE users.id=" . $postdata['id'] . "  AND users.id=user_details.user_id";

        // $student_records = $connection->execute($upsql);
        if ($connection->execute($upsql)) {
          $data['status'] = "true";
          $data['message'] = "record is updated successfully";
        } else {
          $data['status'] = "false";
          $data['message'] = "record is not updated. Contact Administrator";
        }
      }
    } else {
      $data['message'] = "opps issue in updating the student record. Please try again";
      $data['status'] = "false";
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  // Function to delete the student
  public function deleteStudent() {
    $postdata['id'] = isset($_GET['id']) ? $_GET['id'] : $data['message'][0] = "Student id is null. Please check";
    if (isset($_GET['id'])) {
      $dtsql = "DELETE users.*, user_details.* FROM users,user_details,user_courses                             
                        WHERE users.id=" . $postdata['id'] . "  AND users.id=user_details.user_id AND users.id=user_courses.user_id";

      // $student_records = $connection->execute($upsql);
      $connection = ConnectionManager::get('default');
      if ($connection->execute($dtsql)) {
        $data['status'] = "true";
        $data['message'] = "The Student records is sucessfully deleted.";
      } else {
        $data['status'] = "false";
        $data['message'] = "Opps..The Student records is not deleted.Please Try again";
      }
    }
    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  // API to create group of a teacher for a subject
  public function createGroupInSubjectByTeacher($tid = null, $course_id = null, $grade_id = null) {


    if (isset($this->request->data['selectedstudent']) && isset($this->request->data['groupname'])) {
      $students = $this->request->data['selectedstudent'];

      $postdata['teacher_id'] = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : $tid;
      $postdata['grade_id'] = isset($_GET['grade_id']) ? $_GET['grade_id'] : $grade_id;
      $postdata['course_id'] = isset($_GET['course_id']) ? $_GET['course_id'] : $course_id;
      $postdata['title'] = $this->request->data['groupname'];
      $postdata['created_by'] = time();
      $postdata['modified_by'] = time();



      if (isset($this->request->data['group_image'])) {
        $gp_img = json_decode($this->request->data['group_image']);
        $postdata['group_icon'] = $gp_img->response;
      }

      if (!empty($postdata['teacher_id']) && $postdata['teacher_id'] != null) {
        $postdata['student_id'] = "";

        foreach ($students as $key => $value) {
          if (!empty($value)) {
            $postdata['student_id'] = $key . ',' . $postdata['student_id'];
          }
        }
        $postdata['student_id'] = rtrim($postdata['student_id'], ',');

        $student_groups = TableRegistry::get('StudentGroups');
        $new_rowEntry = $student_groups->newEntity($postdata);
        if ($student_groups->save($new_rowEntry)) {
          $data['status'] = "true";
          $data['message'] = "Group- ' " . $postdata['title'] . " ' has been created.";
        } else {
          $data['status'] = "False";
          $data['message'] = "Opps Issue in adding the group.";
        }
      } else {
        $data['status'] = "False";
        $data['message'] = "Please login First. No Teacher UID get.";
      }
    } else {
      $data['status'] = "false";
      $data['message'] = "Opps....Either students are not selected or Group Title is not entered .";
    }


    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  // API to update  group of a teacher for a subject
  public function editGroupOfSubject($group_id = null) {

    if (isset($this->request->data['group_id']) || isset($_GET['group_id'])) {
      $students = $this->request->data['students'];
      $group_id = isset($_GET['group_id']) ? $_GET['group_id'] : $group_id;
      $title = $this->request->data['groupname'];
      $modified_by = time();
      $student_ids = "";
      foreach ($students as $key => $value) {
        if (!empty($value)) {
          $student_ids = $key . ',' . $student_ids;
        }
      }
      $student_ids = rtrim($student_ids, ',');

      $student_groups = TableRegistry::get('StudentGroups');
      $query = $student_groups->query();
      $result = $query->update()->set([
                  'title' => $title,
                  'student_id' => $student_ids,
                  'modified_by' => $modified_by
              ])->where(['id' => $group_id])->execute();

      $row_count = $result->rowCount();
      if ($row_count == '1') {
        $data['status'] = "True";
        $data['message'] = "Group is updated sucessfully.";
      } else {
        $data['status'] = "False";
        $data['message'] = "Opps....Group is not updated. Please try again .";
      }
    } else {
      $data['status'] = "False";
      $data['message'] = "Opps....Either students are not selected or Group Title is not entered .";
    }


    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  //Get groups of a teacher
  public function getGroupsOfSubjectForTeacher($tid = null, $course_id = null) {
    $teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : $tid;
    $course_id = isset($_GET['course_id']) ? $_GET['course_id'] : $course_id;

    if (!empty($teacher_id) && !empty($course_id)) {
      $student_groups = TableRegistry::get('StudentGroups')->find('all')->where(['teacher_id' => $teacher_id, 'course_id' => $course_id])->group('group_icon')->toArray();
      $param = array();

      if (count($student_groups) > 0) {
        foreach ($student_groups as $stgroup) {
          if (empty($stgroup['group_icon']) || $stgroup['group_icon'] == "" || $stgroup['group_icon'] == null) {
            $stgroup['group_icon'] = "default_group.png";
          }
          $stgroup['URL_title'] = str_replace(' ', '-', strtolower($stgroup['title']));
          $data['groups'][] = $stgroup;
        }
      }
      $data['status'] = "true";
    } else {
      $data['status'] = "false";
      $data['message'] = "Either your login session has expired or course is not set.";
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  // API to get the students of a group
  public function getStudentsOfGroup($group_id = null) {

    $groupid = isset($_GET['group_id']) ? $_GET['group_id'] : $group_id;

    if (!empty($groupid)) {
      $connection = ConnectionManager::get('default');
      $gprecords = TableRegistry::get('StudentGroups')->find('all')->where([ 'id' => $groupid]);

      if ($gprecords->count() > 0) {
        foreach ($gprecords as $gprecord) {
          $studentids = $gprecord['student_id'];
          $data ['group_title'] = $gprecord['title'];
          $data ['course_id'] = $gprecord['course_id'];

          if ($gprecord['group_icon'] == NULL) {
            $data ['group_icon'] = "webroot/upload/group_images/default_group.png";
          } else {
            $data ['group_icon'] = 'webroot/upload/' . $gprecord['group_icon'];
          }
        }


        // find the students details whose id are linked with group                   
        $sql = " SELECT users.id as id,first_name,last_name,username,email, profile_pic from users"
                . " INNER JOIN user_details ON users.id = user_details.user_id "
                . " WHERE users.id IN ($studentids)"
                . " ORDER BY users.id ASC ";


        $student_records = $connection->execute($sql)->fetchAll('assoc');
        $studentcount = count($student_records);

        if ($studentcount > 0) {
          foreach ($student_records as $stRecord) {

            if ($stRecord['profile_pic'] == NULL) {
              $stRecord['profile_pic'] = '/upload/profile_img/default_studentAvtar.png';
            } else {
              $stRecord['profile_pic'] = $stRecord['profile_pic'];
            }

            $data['students'][] = $stRecord;
          }
          $data['status'] = "True";
        } else {
          $data['status'] = "False";
          $data['message'] = "Group does not exist";
        }
      }
    } else {
      $data['status'] = "False";
      $data['message'] = "Opps. Group  ID is missing.";
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  // API to get the student of a subject Added by a teacher
  public function getStudentsOfSubjectForTeacher($tid = null, $course_id = null) {
    
    $tid = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : $tid;
    $course_id = isset($_GET['course_id']) ? $_GET['course_id'] : $course_id; // course_id is subject

    if ((!empty($tid)) && (!empty($course_id))) {
      $connection = ConnectionManager::get('default');
      $sql = " SELECT users.id as id,first_name,last_name,username,email,password,open_key, profile_pic from users"
              . " INNER JOIN user_details ON users.id = user_details.user_id "
              . " INNER JOIN user_courses ON users.id = user_courses.user_id "
              . " INNER JOIN student_teachers ON users.id = student_teachers.student_id "
              . " WHERE student_teachers.teacher_id =" . $tid . " AND user_courses.course_id=" . $course_id
              . " ORDER BY users.first_name ASC ";

      $student_records = $connection->execute($sql)->fetchAll('assoc');
      $studentcount = count($student_records);

      if ($studentcount > 0) {
        foreach ($student_records as $studentrow) {
          $student['id'] = $studentrow['id'];
          $student['first_name'] = $studentrow['first_name'];
          $student['last_name'] = $studentrow['last_name'];
          $student['username'] = $studentrow['username'];
          $student['email'] = $studentrow['email'];
          $student['password'] = $studentrow['password'];
          $open_key = $studentrow['open_key'];
          $student['open_key'] = hex2bin($open_key); // decrypt

          if ($studentrow['profile_pic'] == NULL) {
            $student['profile_pic'] = '/upload/profile_img/default_studentAvtar.png';
          } else {
            $student['profile_pic'] = $studentrow['profile_pic'];
          }
          $data['students'][] = $student;
        }
        $data['status'] = "true";
      } else {
        $data['status'] = "false";
        $data['message'] = "Please Add Student in Your Class.";
      }
    } else {
      $data['status'] = "false";
      $data['message'] = "Either teacher_id or course_id is null. Please check it cannot be null";
    }
    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }
  
  
  // The function to show all students of a teacher for his/her all subjects
  public function getStudentOfTeacher($tid = null) {

    $tid = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : $tid;
    if ((!empty($tid))) {
      $connection = ConnectionManager::get('default');
      $sql = " SELECT users.id as id,first_name,last_name,username,email,password,open_key,profile_pic from users"
              . " INNER JOIN user_details ON users.id = user_details.user_id "
              . " INNER JOIN student_teachers ON users.id = student_teachers.student_id "
              . " INNER JOIN user_courses ON users.id = user_courses.user_id "
              . " WHERE student_teachers.teacher_id =" . $tid
              . " ORDER BY users.id ASC ";
      $student_records = $connection->execute($sql)->fetchAll('assoc');
      $studentcount = count($student_records);

      if ($studentcount > 0) {
        foreach ($student_records as $studentrow) {
          $student['id'] = $studentrow['id'];
          $student['first_name'] = $studentrow['first_name'];
          $student['last_name'] = $studentrow['last_name'];
          $student['username'] = $studentrow['username'];
          $student['email'] = $studentrow['email'];
          $student['password'] = $studentrow['password'];
          $open_key = $studentrow['open_key'];
          $student['open_key'] = hex2bin($open_key);
          if ($studentrow['profile_pic'] == NULL) {
            $student['profile_pic'] = 'upload/profile_img/default_studentAvtar.png';
          } else {
            $student['profile_pic'] = $studentrow['profile_pic'];
          }
          $data['students'][] = $student;
        }
        $data['status'] = "true";
      } else {
        $data['status'] = "false";
        $data['message'] = "No student is added yet by teacher.";
      }
    } else {
      $data['status'] = "false";
      $data['message'] = "teacher_id cannot be null. Please check it";
    }
    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  public function sendEmailStudentListToTeacher($tid = null) {
    if ($this->request->is('post')) {
      $teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : $tid;
      $selected_courseName = isset($this->request->data['selected_courseName'])? $this->request->data['selected_courseName'] : '';
      $students= isset($this->request->data['selectedstudent'])? $this->request->data['selectedstudent'] :null;
    
      if ($teacher_id != null && (!empty($teacher_id)) && (!empty($students)) ) {
        $sids = implode(',', array_keys($this->request->data['selectedstudent']));
        $connection = ConnectionManager::get('default');
        $str = "SELECT users.id,first_name,last_name,email,username,open_key FROM users, user_details WHERE users.id IN ($sids,$teacher_id) AND users.id=user_details.user_id";

        $users_record = $connection->execute($str)->fetchAll('assoc');
        $usercount = count($users_record);
        $index = 0;

        if ($usercount > 0) {
          $msg = "<table>";
          $msg .= "<thead>
                          <tr>
                              <th class='sr-no'>Serial Num #</th>
                              <th class='first-name'>First Name</th>
                              <th class='last-name'>Last Name</th>
                              <th class='parent-student-email'>Student E-mail</th>
                              <th class='user-name'>User Name</th>
                              <th class='pasword'>Pasword</th>                              
                          </tr>
                        </thead><tbody>";

          foreach ($users_record as $userrow) {
            if ($userrow['id'] == $teacher_id) {
              $teacher_firstname = $userrow['first_name'];
              $teacher_lastname = $userrow['last_name'];
              $teacher_email = $userrow['email'];
            } else {
              $msg .= "<tr>";
              $msg .="<td>" . $index++ . "</td>";
              $msg .= "<td>" . $userrow['first_name'] . "</td>";
              $msg .= "<td>" . $userrow['last_name'] . "</td>";
              $msg .= "<td>" . $userrow['email'] . "</td>";
              $msg .= "<td>" . $userrow['username'] . "</td>";
              $msg .= "<td>" . $userrow['open_key'] . "</td>";
              $msg .= "</tr>";
            }
          }          
          $msg .= "</tbody></table>";


        //  $to = $teacher_email;
          $to='anita@apparrant.com';
          $from = "info@mylearninguru.com";
          $subject = "Selected Student Records";
          $email_message = "Hello  $teacher_firstname  $teacher_lastname <br/><br/>
                           <strong> Please find your students List for class $selected_courseName </strong> <br/><br/>" . $msg;

//pr($email_message); die; 
          //sendEmail($to, $from, $subject,$email_message); // send email to teacher 
          $sent_mail = $this->sendEmail($to, $from, $subject, $email_message);         
          if($sent_mail==TRUE){ 
            $data['message'] = "mail send";
            $data['status'] = True;
          } else {
            $data['message'] = "mail is not send";
            $data['status'] = False;
          }
        } else {
          $data['message'] = "Please select student first.";
          $data['status'] = False;
        }
      } else {
        $data['message'] = "Opps either teacher_id or students are not selected for mail. Please try again.";
        $data['status'] = False;
      }
    } else {
      $data['message'] = "Opps data is not recieved properly. Please try again.";
      $data['status'] = False;
    }


    $this->set(array(
            'response' => $data,
            '_serialize' => array('response')
        ));
  }



  // Send email to each student to share their login info 
  public function sendEmailStudentInfoToStudent($teacher_id=null) {
      if ($this->request->is('post')) {
          $teacher_id= isset($_GET['teacher_id']) ? $_GET['teacher_id']:$teacher_id; 
          $students= isset($this->request->data['selectedstudent'])? $this->request->data['selectedstudent'] :null;
            if( (!empty($students) ) && (!empty($teacher_id)) ){
                  $sids = implode(',', array_keys($this->request->data['selectedstudent']));
                  $connection = ConnectionManager::get('default');
                  $str = "SELECT users.id,first_name,last_name,email,username,open_key FROM users, user_details WHERE users.id IN ($sids) AND users.id=user_details.user_id";

                  $users_record = $connection->execute($str)->fetchAll('assoc');
                  $usercount = count($users_record);
                  $index = 0;

                  if ($usercount > 0) {
                      
                    //teacher records
                    $str1 = "SELECT users.id,first_name,last_name,email,username,open_key FROM users, user_details WHERE users.id=$teacher_id AND users.id=user_details.user_id"; 
                    $teacher_record = $connection->execute($str1)->fetchAll('assoc');
                    if( count($teacher_record) > 0){
                      foreach ($teacher_record as $trow) {
                            $teacher_firstname = $trow['first_name'];
                            $teacher_lastname = $trow['last_name'];
                            $teacher_email = $trow['email'];
                      }
                    }

                    //student records
                    foreach ($users_record as $userrow) {                        
                        $msg = "<table><thead>
                                  <tr>                                     
                                      <th class='first-name'>First Name</th>
                                      <th class='last-name'>Last Name</th>
                                      <th class='parent-student-email'>Student E-mail</th>
                                      <th class='user-name'>User Name</th>
                                      <th class='pasword'>Pasword</th>                              
                                  </tr>
                              </thead><tbody>";

                          
                        $msg .= "<tr>";                        
                        $msg .= "<td>" . $userrow['first_name'] . "</td>";
                        $msg .= "<td>" . $userrow['last_name'] . "</td>";
                        $msg .= "<td>" . $userrow['email'] . "</td>";
                        $msg .= "<td>" . $userrow['username'] . "</td>";
                        $msg .= "<td>" . $userrow['open_key'] . "</td>";
                        $msg .= "</tr>";                            
                        $msg .= "</tbody></table>";
                        $to='anita@apparrant.com';
                        //$to = $userrow['email'] ;
                        $from = "info@mylearninguru.com";
                        $subject = "Student information from teacher";
                        $email_message = $index++."Hello  ".$userrow['first_name']." ". $userrow['last_name']." <br/><br/>
                           <strong> Please find your information shared by your class teacher $teacher_firstname  $teacher_lastname</strong> <br/><br/>" . $msg;  
                        
                        $sent_mail = $this->sendEmail($to, $from, $subject, $email_message);         
                        if($sent_mail==TRUE){ 
                          $data['status'] = True;
                          $data['message'] = "mail send";
                          
                        } else {
                          $data['message'] = "mail is not send";
                          $data['status'] = False;
                        }                            
                  }

              }else{
                  $data['status'] = False;
                  $data['message'] ="No student is selected to send email.";
              }

        }else {
            $data['status'] = False;
            $data['message'] = "Selected student and teacher_id cannot null.";
          }
        
        }

        else {
            $data['status'] = False;
            $data['message'] = "Opps data is not recieved properly. Please try again.";
          }
      

      $this->set(array(
            'response' => $data,
            '_serialize' => array('response')
        ));

  }

  /*protected function sendEmail($to, $from, $subject = null, $email_message = null) {
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
  }*/

  public function sendEmail($to = null, $from = null, $subject = null, $email_message = null) {
          try {
            $status = FALSE;
            $message = '';
            $to = isset($this->request->data['to']) ? $this->request->data['to'] : $to;
            $from = isset($this->request->data['from']) ? $this->request->data['from'] : $from;
            $subject = isset($this->request->data['from']) ? $this->request->data['subject'] : $subject;
            $email_message = isset($this->request->data['email_message']) ? $this->request->data['email_message'] : $email_message;

            if (empty($to)) {
              $message = "Mail Address 'to' cannot be empty";
              //throw new Exception($message);
              $data['status']=False;
              $data['message'] ="Mail Address 'to' cannot be empty";
            }
            if (empty($from)) {
              $message = "Mail Address 'from' cannot be empty";
              //throw new Exception($message);
              $data['status']=False;
              $data['message'] ="Mail Address 'to' cannot be empty";
            }
            //send mail
            $email = new Email();
            $email->to($to)->from($from);
            $email->subject($subject);
            $email->emailFormat('html');
            if ($email->send($email_message)) {
              $status = TRUE;
              $data['status']=False;
              $data['message'] ="Mail Address 'to' cannot be empty";

            }else{
              $status = FALSE;
              $data['status']=False;
              $data['message'] ="Mail Address 'to' cannot be empty";
            }
          } catch (Exception $ex) {
            $this->log($ex->getMessage());
          }
          //return $status;
          return $data ;
       }

  /**
   * create an api for save question.
   * 
   * */
  public function saveQuestion() {
    try {
      $message = '';
      $status = FALSE;
      $insertedId = '';
      $marks = '';
      $connection = ConnectionManager::get('default');
      $difficulty = TableRegistry::get('difficulties');
      $relation = TableRegistry::get('content_template_relation');
      if ($this->request->is('post')) {
        if (isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
          $message = "please select a grade.";
        } elseif ($this->request->data['course'] == '-1') {
          $message = "please select a course.";
        } 
//        elseif (empty($this->request->data['standard_type'])) {
//          $message = "please select a standard type.";
//        } 
        elseif (empty($this->request->data['standard'])) {
          $message = "please select a standard.";
        }elseif (empty($this->request->data['skills'])) {
          $message = "please select skills.";
        } elseif (empty($this->request->data['sub_skill'])) {
          $message = "please select sub skills.";
        } else if (empty($this->request->data['ques_diff'])) {
          $message = 'Please select difficulity level of question.';
        } else if (empty($this->request->data['claim'])) {
          $message = 'Please give claim.';
        } else if (empty($this->request->data['scope'])) {
          $message = 'Please give scope.';
        } else if (empty($this->request->data['dok'])) {
          $message = 'Please provide depth of knowledge.';
        } else if (empty($this->request->data['ques_passage'])) {
          $message = 'Please give passage.';
        } else if (empty($this->request->data['ques_target'])) {
          $message = 'Please give question target.';
        } else if (empty($this->request->data['task'])) {
          $message = 'Please give task.';
        } else if (empty($this->request->data['ques_complexity'])) {
          $message = 'Please give question complexity.';
        } else if (empty($this->request->data['question'])) {
          $message = 'Please give question';
        } else if (empty($this->request->data['answer'])) {
          $message = 'Please give options';
        } else if (empty($this->request->data['correctanswer'])) {
          $message = 'Please select an answer.';
        }
        if ($message == '') {
          $subskill = $this->request->data['sub_skill'];
          $result = $difficulty->find()->where(['id'=> $this->request->data['ques_diff']])->toArray();
          foreach($result as $kei=> $val){
            $marks = $val['marks'];
          }   
          foreach ($subskill as $key => $value) {
            $answer_list = explode(',', $this->request->data['answer']);
            $unique_id = date('Ymd', time()) . uniqid(9);
            $question_master = TableRegistry::get('question_master');
            $question = $question_master->newEntity();
            $question->created_by = $this->request->data['tid'];
            $question->questionName = $this->request->data['question'];
            $question->grade_id = $this->request->data['grade'];
            $question->grade = $this->request->data['grade_name'];
            $question->subject = $this->request->data['course_name'];
            $question->course_id = $value;
            $question->level = $this->request->data['ques_diff_name'];
            $question->difficulty_level_id = $this->request->data['ques_diff'];
            $question->type =  $this->request->data['ques_type'];
            $question->standard = implode(',', $this->request->data['standard']);
            $question->status = 'approved';
            $question->marks = $marks;
            $question->uniqueId = $unique_id;
            $insertedId = $question_master->save($question)->id;
            if (is_numeric($insertedId)) {
              $header_master = TableRegistry::get('header_master');
              $header = $header_master->newEntity();
              $header->uniqueId = $unique_id;
              $header->Claim = $this->request->data['claim'];
              $header->DOK = $this->request->data['dok'];
              $header_master->save($header);
              foreach ($answer_list as $key => $value) {
                $option_master = TableRegistry::get('option_master');
                $option = $option_master->newEntity();
                $option->uniqueId = $unique_id;
                if ($this->request->data['type'] == 'image') {
                  $option->options = 'upload/' . $value;
                } else {
                  $option->options = $value;
                }
                if ($option_master->save($option)) {
                  if ($key + 1 == $this->request->data['correctanswer']) {
                    $answer_master = TableRegistry::get('answer_master');
                    $answer = $answer_master->newEntity();
                    $answer->uniqueId = $unique_id;
                    $answer->answers = $value;
                    if ($answer_master->save($answer)) {
                      $points = TableRegistry::get('mlg_points');
                      $user_points = TableRegistry::get('user_points');
                      $get_points = $user_points->newEntity();
                      $get_points->user_id = $this->request->data['tid'];
                      $get_points->question_id = $insertedId;
                      $get_points->point_type_id = $this->request->data['point_type'];
                      $point = $points->find()->where(['id' => $this->request->data['point_type']]);
                      foreach ($point as $key => $value) {
                        $get_points->points = $value['points'];
                      }
                      $get_points->status = 1;
                      $get_points->created_date = date('y-m-d ', time());
                      $user_points->save($get_points);
                    }
                  }
                } else {
                  $mesaage = "Not able to saved option.";
                }
              }
              $message = 'Question answer saved with options.';
              $status = TRUE;
              if (isset($this->request->data['template_id']) && !empty($this->request->data['template_id'])) {
//                $temp = $relation->find()->where(['template_id' => $this->request->data['template_id']])->toArray();
//                $content = $temp[0]['content_id'] . ',' . $insertedId;
//                $query = $relation->query();
//                $result = $query->update()->set([
//                            'content_id' => $content
//                        ])->where(['template_id' => $this->request->data['template_id'], 'template_type' => 'question'])->execute();   
              
                $rel = $relation->newEntity();
                $rel->template_id = $this->request->data['template_id'];
                $rel->template_type = 'question';
                $rel->created_by = $this->request->data['tid'];
                $rel->content_id = $insertedId;
                $relation->save($rel);
              }
            } else {
              $message = 'unable to save question.';
            }
          }
        }
      } else {
        $message = 'please define correct method.';
      }
    } catch (Exception $e) {
      $this->log('Error in saveQuestion function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'question_id' => $insertedId,
        'status' => $status,
        '_serialize' => ['message', 'status', 'question_id']
    ]);
  }

  /* to create custome Assignment */

  public function generateAssignQuestions($courseid = null, $gradeid = null, $teacherid = null) {

    $grade_id = isset($this->request->data['grade_id']) ? $this->request->data['grade_id'] : $gradeid;
    $teacher_id = isset($this->request->data['teacher_id']) ? $this->request->data['teacher_id'] : $teacherid;
    $subject_id = isset($this->request->data['main_course_id']) ? $this->request->data['main_course_id'] : $courseid;
    $base_url = Router::url('/', true);


    if (!empty($teacher_id) && !empty($subject_id) && !empty($grade_id)) {

      if ($this->request->data['skill_id'] != '' && $this->request->data['subskill_id'] != '') {

        $skill_id = $this->request->data['skill_id'];
        $subskill_id = $this->request->data['subskill_id'];
        $questions_limit = $this->request->data['questions_limit'];
        $difficulty_level = isset($this->request->data['difficulty_level'])?$this->request->data['difficulty_level']:null;

        $difficulty = 'NA';
        if (!empty($difficulty_level)) {
          foreach ($difficulty_level as $diff) {
            if ($diff['id'] == 1) {
              $diff_str = 'Easy';
            } elseif ($diff['id'] == 2) {
              $diff_str = 'Moderate';
            } else {
              $diff_str = 'Difficult';
            }

            $difficulty = $diff_str . '|' . $difficulty;
          }
        } else {
          $difficulty = 'Easy|Moderate|Difficult';
        }

        // Step 1 - get Questions as per field
        $dataToGetQuestions['subjects'] = $subskill_id; // ids of course as eg 3,13,15
        $dataToGetQuestions['grade_id'] = $grade_id;
        $dataToGetQuestions['limit'] = $questions_limit;
        $dataToGetQuestions['difficulty'] = $difficulty; // eg Easy|Difficult|mod


        $json_questionslist = $this->curlPost($base_url . 'teachers/getQuestionsListForAssg/', $dataToGetQuestions);
        
        $array_qlist = (array) json_decode($json_questionslist);
        
        if ($array_qlist['response']->status == "True") {
          $data['status'] = "True";
          $data['questions'] = $array_qlist['response']->questions;
        } else {
          $data['status'] = $array_qlist['response']->status;
          $data['message'] = $array_qlist['response']->message;
        }
      } else {
        $data['status'] = "False";
        $data['message'] = "Please select skill and subskill.";
      }
    } else {
      $data['status'] = "False";
      $data['message'] = "teacher id and selected subject and grade cannot be null.";
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  // Function to get List of questions in assignment

  public function getQuestionsListForAssg($subjects = null, $grade_id = null, $standard = null, $limit = 5, $target = null, $dok = null, $difficulty = 'Easy', $type = null, $user_id = null, $removed_questions_id = null, $existing_questions_id = null) {

    $subjects = isset($this->request->data['subjects']) ? $this->request->data['subjects'] : $subjects;
    $grade_id = isset($this->request->data['grade_id']) ? $this->request->data['grade_id'] : $grade_id;

    $standard = isset($this->request->data['standard']) ? $this->request->data['standard'] : $standard;

    $limit = isset($this->request->data['limit']) ? $this->request->data['limit'] : $limit;

    $target = isset($this->request->data['target']) ? $this->request->data['target'] : $target;

    $dok = isset($this->request->data['dok']) ? $this->request->data['dok'] : $dok;

    $difficulty = isset($this->request->data['difficulty']) ? $this->request->data['difficulty'] : $difficulty;

    $type = isset($this->request->data['type']) ? $this->request->data['type'] : $type;

    $user_id = isset($this->request->data['user_id']) ? $this->request->data['user_id'] : $user_id;

    $removed_questions_id = isset($this->request->data['removed_questions_id']) ? $this->request->data['removed_questions_id'] : $removed_questions_id;

    $existing_questions_id = isset($this->request->data['existing_questions_id']) ? $this->request->data['existing_questions_id'] : $existing_questions_id;


    // $subj= '('.implode(',', $subj).')';
    $subjects = '(' . $subjects . ')';
    $data['status'] = "False";
    $data['message'] = "";
    $connection = ConnectionManager::get('default');


    $sql = 'SELECT  distinct qm.id, type, qm.grade,qm.subject,qm.standard,qm.course_id, qm.docId, qm.uniqueId, questionName,  qm.level,qm.marks as question_marks,
                 mimeType, paragraph, item,Claim,Domain,Target,`CCSS-MC`,`CCSS-MP`,
                 cm.state, cm.GUID, cm.ParentGUID, cm.AuthorityGUID, cm.Document, cm.Label, cm.Number, cm.Description, cm.Year, createdDate
              FROM question_master AS qm
              LEFT JOIN header_master AS hm ON hm.uniqueId = qm.docId and hm.headerId=qm.headerId
              LEFT JOIN mime_master AS mm ON mm.uniqueId = qm.uniqueId
              LEFT JOIN paragraph_master as pm on pm.question_id=qm.docId
              LEFT JOIN compliance_master as cm on (cm.Subject=qm.subject OR cm.grade=qm.grade)
              where qm.course_id IN ' . $subjects . ' and qm.grade_id=' . $grade_id;


    // To check Previous questions has been showed During Assignment Selection
    if ($removed_questions_id != null) {
      $sql .=' and qm.id Not IN (' . $removed_questions_id . ')';
    }

    if ($existing_questions_id != null) {
      $previous_selected_id = $removed_questions_id . ',' . $existing_questions_id;

      $substr = 'SELECT  distinct qm.id FROM question_master AS qm where qm.course_id IN ' . $subjects . ' and qm.grade_id=' . $grade_id . ' and qm.id NOT IN (' . $previous_selected_id . ') Limit 0,1';

      $subqids = $connection->execute($substr)->fetchAll('assoc');

      if (count($subqids) > 0) {
        foreach ($subqids as $subqid) {
          $qsids = $subqid ['id'];
        }
        $existing_questions_id = $qsids . ',' . $existing_questions_id;
        $sql .=' and qm.id IN (' . $existing_questions_id . ')';
        $data['change_question_status'] = "True";
      } else {
        $data['change_question_status'] = "False";
        $data['change_question_message'] = "Opps..No More Question exist in database to change it.";
      }
    }


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
        $epoch = date("YmdHis");
      $quiz_name = "autoStudentSubskillQuiz-" . $epoch;                      
        $quiz = $this->createQuiz($quiz_name, $limit, $ques_ids, $quiz_marks, $user_id);      
        if ($quiz['status'] == "True") {
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

  public function createQuiz($quiz_name=null,$limit = null, $itemsIds = array(), $quiz_marks = null, $user_id = null) {


    if (!empty($itemsIds) && !empty($limit) && !empty($quiz_marks)) {
      $date = date("Y-m-d H:i:s");
      $epoch = date("YmdHis");
      $quiz_name1 = 'mlg'. $epoch;
      $quiz_info['name'] = !empty($quiz_name) ? $quiz_name : $quiz_name1;
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
            $data['status'] = "True";
            $data['quiz_id'] = $qresult->id;
            $data ['message'] = "quiz is created.";
          } else {
            $data['status'] = "False";
            $data ['message'] = "Not able to create quiz item. Please consult with admin1";
          }
        }
      } else {
        $data['status'] = "False";
        $data ['message'] = "Not able to create quiz. Please consult with admin2";
      }
    } else {
      $data['status'] = "False";
      $data ['message'] = "Not able to create quiz. Please consult with admin3";
    }

    return($data);
  }

  /*  function to save the Custom Assignment Created by Teacher */

  public function setCustomAssignmentByTeacher($user_id = null) {
    $quiz_info['teacher_id'] = isset($this->request->data['teacher_id']) ? $this->request->data['teacher_id'] : $user_id;

    $data['status'] = "";
    $data['message'] = "";

    if (!empty($quiz_info['teacher_id'])) {
      $date = date("Y-m-d H:i:s");
      $epoch = date("Ymd-His");
      $quiz_info['name'] = "teacherCustomAssignment-" . $epoch;
      $quiz_info['quiz_type_id'] = 5;
      $quiz_info['is_graded'] = 1;
      $quiz_info['is_time'] = 1;
      $quiz_info['duration'] = '1';
      $quiz_info['status'] = 1;
      $quiz_info['created_by'] = $quiz_info['teacher_id'];
      $quiz_info['created'] = time();
      $quiz_info['modified'] = time();     


      $quiz_info['subject_id'] = isset($this->request->data['main_course_id']) ? $this->request->data['main_course_id'] : null;

      $quiz_info['subject_name'] = isset($this->request->data['subject_name']) ? $this->request->data['subject_name'] : null;

      $quiz_info['course_id'] = isset($this->request->data['subskill_id']) ? $this->request->data['subskill_id'] : null;

      $quiz_info['grade_id'] = isset($this->request->data['grade_id']) ? $this->request->data['grade_id'] : null;

      $quiz_info['comments'] = isset($this->request->data['comments']) ? $this->request->data['comments'] : '';

      $quiz_info['attachedresource'] = isset($this->request->data['attachedresource']) ? $this->request->data['attachedresource'] : null;

      $quiz_info['schedule_time'] = isset($this->request->data['schedule_time']) ? $this->request->data['schedule_time'] : time();


      $quiz_info['assignment_for'] = isset($this->request->data['assignmentFor']) ? $this->request->data['assignmentFor'] : null;


      // get students id-  if assignment is for selected class
      if ($quiz_info['assignment_for'] == 'class' || $quiz_info['assignment_for'] == 'CLASS') {
        $class_id = $quiz_info['grade_id'];

        if (!empty($class_id)) {
          $strecords = TableRegistry::get('StudentTeachers')->find('all')->where(['course_id' => $quiz_info['subject_id'], 'grade_id' => $quiz_info['grade_id'], 'teacher_id' => $quiz_info['teacher_id']]);

          if ($strecords->count() > 0) {
            foreach ($strecords as $strecord) {
              $studentids[] = $strecord['student_id'];
            }
            $quiz_info['student_id'] = implode(',', $studentids);
          } else {
            $data['status'] = "False";
            $data['message'] = "No students found for this class.";
          }
        } else {
          $data['status'] = "False";
          $data['message'] = "No class is found to select the stidents for assignment.";
        }
      }
      // get students id-  if assignment is for selected group
      elseif ($quiz_info['assignment_for'] == 'group' || $quiz_info['assignment_for'] == 'GROUP') {

        $quiz_info['group_id'] = isset($this->request->data['group_id']) ? $this->request->data['group_id'] : null;

        if (!empty($quiz_info['group_id'])) {

          $gstrecords = TableRegistry::get('StudentTeachers')->find('all')->where(['course_id' => $quiz_info['subject_id'], 'grade_id' => $quiz_info['grade_id'], 'teacher_id' => $quiz_info['teacher_id']]);

          if ($gstrecords->count() > 0) {
            foreach ($gstrecords as $gstrecord) {
              $gpstudentids = $gstrecord['student_id'];
            }
            $quiz_info['student_id'] = $gpstudentids;
          } else {
            $data['status'] = "False";
            $data['message'] = "No Student is added in group.";
          }
        } else {
          $data['status'] = "False";
          $data['message'] = "No group is selected. Please select Group.";
        }
      }
      // students id-  if assignment for selected students of class
      else {
        $selected_students = $this->request->data['students'];
        if (count($selected_students) > 0) {
          foreach ($selected_students as $k => $vl) {
            $select_stids[] = $vl['id'];
          }
          $quiz_info['student_id'] = implode(',', $select_stids);
        } else {
          $data['status'] = "False";
          $data['message'] = "No student is selected to this assignment.";
        }
      }


      // To get quiz Marks
      $selected_questions = isset($this->request->data['selected_questions']) ? $this->request->data['selected_questions'] : null;

      $quiz_info['max_questions'] = isset($this->request->data['questions_limit']) ? $this->request->data['questions_limit'] : null;

      $quiz_marks = 0;

      if (!empty($selected_questions)) {
        foreach ($selected_questions as $quesId => $quesLevel) {
          $questions[] = $quesId;

          if ($quesLevel == 'Easy') {
            $quiz_marks = $quiz_marks + 1;
          } elseif ($quesLevel == 'Moderate') {
            $quiz_marks = $quiz_marks + 2;
          } else {
            $quiz_marks = $quiz_marks + 3;
          }
        }

        $quiz_info['max_marks'] = $quiz_marks;
      } else {
        $data['status'] = "False";
        $data['message'] = "No question in the Assignment. Please select questions.";
      }


      // At end - Now to store the value in database
      if ($data['status'] != "False") {

        $Quizes = TableRegistry::get('Quizes');
        $new_quiz = $Quizes->newEntity($quiz_info);

        if ($qresult = $Quizes->save($new_quiz)) {
          $quiz_info['exam_id'] = $qresult->id;
          $quiz_info['quiz_id'] = $qresult->id;

          //insert data in assignment_details table
          $AssignmentDetails = TableRegistry::get('AssignmentDetails');
          $new_assignmentdetails = $AssignmentDetails->newEntity($quiz_info);
          if ($assgnresult = $AssignmentDetails->save($new_assignmentdetails)) {
            $quiz_info['assignment_id'] = $assgnresult->id;
            $attchedresources = array();


            // insert the value of attached resource if exist                


            if ($quiz_info['attachedresource'] != null) {
              if (isset($quiz_info['attachedresource']['image'])) {
                $resimages = json_decode('[' . $quiz_info['attachedresource']['image'] . ']');

                foreach ($resimages as $resimage) {
                  $attchedresources[] = $resimage->response;
                }
              }
              if (isset($quiz_info['attachedresource']['document'])) {
                $resdocs = json_decode('[' . $quiz_info['attachedresource']['document'] . ']');
                foreach ($resdocs as $resdoc) {
                  $attchedresources[] = $resdoc->response;
                }
              }
            }

            if (!empty($attchedresources)) {

              foreach ($attchedresources as $attchedresource) {
                $quiz_info['url'] = 'upload/profile_img/' . $attchedresource;


                $assignmentResources = TableRegistry::get('AssignmentResources');
                $new_assignmentResources = $assignmentResources->newEntity($quiz_info);
                $assignmentResources->save($new_assignmentResources);
              }
            }


            foreach ($questions as $question) {
              $quiz_info['item_id'] = $question;

              //Insert data in quiz item
              $QuizItems = TableRegistry::get('QuizItems');
              $new_quizitem = $QuizItems->newEntity($quiz_info);
              if ($qitemresult = $QuizItems->save($new_quizitem)) {
                $data['status'] = "True";
                $data['message'] = "Assignment is stored sucessfully.";
              } else {
                $data['status'] = "False";
                $data['message'] = "Opps... Issue in inserting question" . $quiz_info['item_id'] . "  data.";
              }
            }
          } else {
            $data['status'] = "False";
            $data['message'] = "Opps... Issue in inserting Assignment details data.";
          }
        } else {
          $data['status'] = "False";
          $data['message'] = "Opps... Issue in inserting quiz data.";
        }
      } else {
        $data['status'] = "False";
      }
    } else {
      $data['status'] = "False";
      $data['message'] = "Account is not logged in. Please login First.";
    }

    $this->set([
        'response' => $data,
        '_serialize' => ['response']
    ]);
  }

  /**
   * Upload event.
   * 
   * */
  public function setEvent() {
    try {
      $message = '';
      $status = FALSE;
      $event = TableRegistry::get('events');
      if ($this->request->is('post')) {
        if (isset($this->request->data['event_date']) && empty($this->request->data['event_date'])) {
          $message = 'Select a date first.';
          throw new Exception('Select a date first.');
        } else if (isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
          $message = 'Not getting grade.';
          throw new Exception('Not getting grade.');
        } else if (isset($this->request->data['course_id']) && empty($this->request->data['course_id'])) {
          $message = 'Not getting subject.';
          throw new Exception('Not getting subject.');
        }
        if (!empty($this->request->data['event_date'])) {
          $current_timestamp = time();
          $event_timestamp = strtotime($this->request->data['event_date']);
          if ($current_timestamp >= $event_timestamp) {
            $message = 'Event date should be greater than current date.';
            throw new Exception('Event date should be greater than current date.');
          }
        }
        if ($this->request->data['event_type'] == 'ptm') {
          if ($message == '') {
            if (empty($this->request->data['event_time'])) {
              $message = 'Schedule time.';
              throw new Exception('Schedule time.');
            } else if (isset($this->request->data['event_for']) && empty($this->request->data['event_for'])) {
              $message = 'Select category for which you create event.';
              throw new Exception('Select category for which you create event.');
            }
          }
          if ($message == '') {
            $event_detail = $event->newEntity();
            $event_detail->event_type = 'ptm';
            $event_detail->event_title = 'Parent Teacher Meeting.';
            $event_detail->event_date = $this->request->data['event_date'];
            $event_detail->event_time = $this->request->data['event_time'];
            $event_detail->event_for = $this->request->data['event_for'];
            $event_detail->created_by = $this->request->data['user_id'];
            if ($this->request->data['event_for'] == 'group') {
              $event_detail->group_id = $this->request->data['event_for_id'];
              $event_detail->grade_id = $this->request->data['grade'];
              $event_detail->grade_name = $this->request->data['grade_name'];
              $event_detail->course_id = $this->request->data['course_id'];
            }
            if ($this->request->data['event_for'] == 'people') {
              $event_detail->created_for = $this->request->data['event_for_id'];
              $event_detail->grade_id = $this->request->data['grade'];
              $event_detail->grade_name = $this->request->data['grade_name'];
              $event_detail->course_id = $this->request->data['course_id'];
            }
            if ($this->request->data['event_for'] == 'class') {
              $event_detail->created_for = implode(',', $this->request->data['event_for_id']);
              $event_detail->grade_id = $this->request->data['grade'];
              $event_detail->grade_name = $this->request->data['grade_name'];
              $event_detail->course_id = $this->request->data['course_id'];
            }
            if ($event->save($event_detail)) {
              $status = TRUE;
              $message = 'Event created Successfully.';
            } else {
              $message = 'Event not generated.';
            }
          }
        } else if ($this->request->data['event_type'] == 'todo') {
          if (empty($this->request->data['event_title'])) {
            $message = 'Please Write something you want to do???';
            throw new Exception('Please Write something you want to do???');
          }
          if ($message == '') {
            $event_detail = $event->newEntity();
            $event_detail->event_type = 'todo';
            $event_detail->event_date = $this->request->data['event_date'];
            $event_detail->created_by = $this->request->data['user_id'];
            $event_detail->event_title = $this->request->data['event_title'];
            $event_detail->created_by = $this->request->data['user_id'];
            $event_detail->grade_id = $this->request->data['grade'];
            $event_detail->grade_name = $this->request->data['grade_name'];
            $event_detail->course_id = $this->request->data['course_id'];
            if ($event->save($event_detail)) {
              $status = TRUE;
              $message = 'Event created Successfully.';
            } else {
              $message = 'Event not generated.';
            }
          }
        }
      }
    } catch (Exception $e) {
      $this->log('Error in eventUpload function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['message', 'status']
    ]);
  }

  /**
   * Get event.
   * 
   * */
  public function getEvent($id = null) {
    try {
      $status = FALSE;
      $message = '';
      if ($id == null) {
        $message = 'Login first.';
        throw new Exception('Login first.');
      }
      if ($message == '') {
        $event = TableRegistry::get('events');
        $result = $event->find()->where(['created_by' => $id]);
        $count = $event->find()->where(['created_by' => $id])->count();
        if ($count > 0) {
          $status = TRUE;
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getEvent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $result,
        'message' => $message,
        'status' => $status,
        '_serialize' => ['response', 'message', 'status']
    ]);
  }

  public function getTodayEvents($uid) {
    try {
      $event = TableRegistry::get('events');
      $user = TableRegistry::get('users');
      $user_array = '';
      $message = '';
      $list = '';
      $date = date('Y-m-d', time());
      $result = $event->find('all')->where(['event_date >' => $date])->order('event_date');
      foreach ($result as $key => $value) {
        $user_bunch_id = explode(',', $value['created_for']);
        $index = array_search($uid, $user_bunch_id);
        if ($index !== false) {
          $user_array[] = $value;
        }
      }
      foreach ($user_array as $key => $value) {
        $name = $user->find()->where(['id' => $value['created_by']]);
        foreach ($name as $val) {
          $list[] = 'You have parent teacher meeting with ' . $val['first_name'] . ' ' . $val['last_name'] . ' on ' . $value['event_date'] . ' at ' . $value['event_time'] . '.';
        }
      }
    } catch (Exception $e) {
      
    }
    $this->set([
        'response' => $list,
//      'status' => $status,
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
    $access_token = $payment_controller->paypalAccessToken();
    if ($this->request->is('post')) {
      try {
        if (empty($this->request->data['user_id'])) {
          $message = 'Please login to submit payment';
          throw new Exception($message);
        }
        $validation = $payment_controller->validatePaymentCardDetail($this->request->data, $access_token);
        $response = $validation['response'];
        $data = $validation['data'];
        $message = $validation['message'];

        if (!empty($message)) {
          throw new Exception($message);
        }

        $card_token = $external_cutomer_id = '';
        if (isset($response['state']) && $response['state'] == 'ok') {
          $data['card_response'] = $message;
          $data['card_token'] = $response['id'];
          $data['external_cutomer_id'] = $response['external_customer_id'];
          $user_id = $this->request->data['user_id'];

          $users_table = TableRegistry::get('Users');
          $parent_info = $users_table->get($user_id)->toArray();
          $parent_subcription = (array) $parent_info['subscription_end_date'];
          $subcription_end_date = $parent_subcription['date'];
          if (time() > strtotime($subcription_end_date)) {
            $trial_period = FALSE;
          }

          $billing_plan = $this->createBillingPlan($user_id, $access_token, $this->request->data, $trial_period);
          if (!empty($billing_plan['plan_id'])) {
            $plan_id = $billing_plan['plan_id'];

            //total amount.
            $data['total_amount'] = $billing_plan['total_amount'];
            $plan_status = $payment_controller->activatePayaplPlan($plan_id, $access_token);
            if ($plan_status == 'ACTIVE') {
              $user_id = $this->request->data['user_id'];

              //same order date and time will be saved on user orders tabl.
              $order_date = $data['order_date'] = time();
              $order_timestamp = $data['purchase_item_order_timestamp'] = time();
              $data['trial_period'] = 1;

              $billing_response = $payment_controller->billingAgreementViaCreditCard($user_id, $data, $plan_id, $access_token);
              if (!$billing_response['error']) {
                $payment_status = TRUE;
                $status = TRUE;

                // Updated user purchase items table.
                $data['paypal_plan_id'] = $plan_id;
                $data['paypal_plan_status'] = $plan_status;
                $data['billing_id'] = $billing_response['result']['id'];
                $data['billing_state'] = $billing_response['result']['state'];

                //for teacher
                $users_controller = new UsersController();
                $users_controller->setUserOrders($user_id, 0, $data);

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

        if (!$payment_status) {
          $status = FALSE;
          $message = 'Payment not completed';
          throw new Exception('Payment not completed');
        } else {
          // start- update the user state
          $user_details = TableRegistry::get('UserDetails');
          $query = $user_details->query();
          $result = $query->update()
                  ->set(['step_completed' => 4])
                  ->where(['user_id' => $this->request->data['user_id']])
                  ->execute();
          $affectedRows = $result->rowCount();
          if ($affectedRows > 0) {
            $data['status'] = "True";
          } else {
            $data['status'] = "False";
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
   * Create Paypal Billing Plan.
   */
  public function createBillingPlan($user_id = null, $access_token = null, $request_data, $trial_period = TRUE) {
    try {
      $user_controller = new UsersController();
      $frequency = 'MONTH';
      $cycles = 1;

      $fixed_name = 'Deduction For  Teacher';
      $fixed_description = 'You will be charged ' . $request_data['amount'] . ' for ' . 'Month';

      $fixed_payment_name = 'Regular payment defination';
      $fixed_payment_type = 'REGULAR';
      $fixed_payment_frequency = $frequency;
      $fixed_payment_frequency_interval = 1;

      $fixed_amount_value = $request_data['amount'];
      $fixed_amount_currency = PAYPAL_CURRENCY;

      $fixed_cycles = 1;
      $fixed_charged_shipping_amount_value = 0;
      $fixed_charged_shipping_amount_currency = PAYPAL_CURRENCY;
      $fixed_charged_tax_amount_value = 0;
      $fixed_charged_tax_amount_currency = PAYPAL_CURRENCY;

      $trial_name = 'Subcription on trial';
      $trial_frequency = 'MONTH';
      $trial_frequency_interval = 1;
      $trial_amount_value = 0;
      $trial_amount_currency = PAYPAL_CURRENCY;
      $trial_cycles = 1;
      $trial_charged_shipping_amount_value = 0;
      $trial_charged_shipping_amount_currency = PAYPAL_CURRENCY;
      $trial_charged_tax_amount_value = 0;
      $trial_charged_tax_amount_currency = PAYPAL_CURRENCY;

      $merchant_setup_fee_value = ($trial_period == TRUE) ? 0 : $fixed_amount_value;
      $merchant_setup_fee_currency = PAYPAL_CURRENCY;
      $return_url = 'http://www.paypal.com';
      $cancel_url = 'http://www.paypal.com/cancel';
      $auto_bill_amount = 'NO';
      $initial_fail_amount_action = 'CANCEL';
      $max_fail_attempts = 0;

      $post_fields = array(
          "name" => $fixed_name,
          "description" => $fixed_description,
          "type" => "FIXED",
          "payment_definitions" => array(
              array(
                  "name" => $fixed_payment_name,
                  "type" => $fixed_payment_type,
                  "frequency" => $fixed_payment_frequency,
                  "frequency_interval" => $fixed_payment_frequency_interval,
                  "amount" => array(
                      "value" => $fixed_amount_value,
                      "currency" => $fixed_amount_currency
                  ),
                  "cycles" => $fixed_cycles,
                  "charge_models" => array(
                      array(
                          "type" => "SHIPPING",
                          "amount" => array(
                              "value" => $fixed_charged_shipping_amount_value,
                              "currency" => $fixed_charged_shipping_amount_currency
                          )
                      ),
                      array(
                          "type" => "TAX",
                          "amount" => array(
                              "value" => $fixed_charged_tax_amount_value,
                              "currency" => $fixed_charged_tax_amount_currency
                          )
                      )
                  )
              )
          ),
          "merchant_preferences" => array(
              "setup_fee" => array(
                  "value" => $merchant_setup_fee_value,
                  "currency" => $merchant_setup_fee_currency
              ),
              "return_url" => $return_url,
              "cancel_url" => $cancel_url,
              "auto_bill_amount" => $auto_bill_amount,
              "initial_fail_amount_action" => $initial_fail_amount_action,
              "max_fail_attempts" => $max_fail_attempts
          )
      );

      if ($trial_period == TRUE) {
        $trial_payment_definitions = array(
            "name" => $trial_name,
            "type" => "TRIAL",
            "frequency" => $trial_frequency,
            "frequency_interval" => $trial_frequency_interval,
            "amount" => array(
                "value" => $trial_amount_value,
                "currency" => $trial_amount_currency
            ),
            "cycles" => $trial_cycles,
            "charge_models" => array(
                array(
                    "type" => "SHIPPING",
                    "amount" => array(
                        "value" => $trial_charged_shipping_amount_value,
                        "currency" => $trial_charged_shipping_amount_currency
                    )
                ),
                array(
                    "type" => "TAX",
                    "amount" => array(
                        "value" => $trial_charged_tax_amount_value,
                        "currency" => $trial_charged_tax_amount_currency
                    )
                )
            )
        );
        $post_fields['payment_definitions'][] = $trial_payment_definitions;
      }
      $url = 'https://api.sandbox.paypal.com/v1/payments/billing-plans/';
      if (USE_SANDBOX_ACCOUNT == FALSE) {
        $url = 'https://api.paypal.com/v1/payments/billing-plans/';
      }
      if ($access_token == null) {
        $access_token = $this->paypalAccessToken();
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
      curl_setopt($ch, CURLOPT_POST, 1);

      $headers = array();
      $headers[] = "Content-Type: application/json";
      $headers[] = "Authorization: Bearer $access_token";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
      }
      curl_close($ch);
      $result = json_decode($result, TRUE);
      $error = FALSE;
      $plan_id = isset($result['id']) ? $result['id'] : '';
      if (isset($result['name']) && ($result['name'] == 'VALIDATION_ERROR')) {
        $error = TRUE;
        throw new Exception('Exception occured: ' . json_encode($result));
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return array('plan_id' => $plan_id,
        'total_amount' => $request_data['amount'],
        'error' => $error);
  }
  // API to call curl 
  /*way of calling $curl_response = $this->curlPost('http://localhost/mlg/exams/externalUsersAuthVerification',['username' => 'ayush','password' => 'abhitest', ]); */
  public function curlPost($url, $data = array(), $json_postfields = FALSE) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      if ($json_postfields) {
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      }
      $response = curl_exec($ch);
      curl_close($ch);
    return $response;
  }

  /**
   * This function is used for get question
   *  of teacher for listing.
   * */
  public function getUserQuestions($user_id = null, $pnum = 1) {
    try {
      $range = 10;
      $message = '';
      $count = '';
      $users_record = '';
      if ($user_id == NULL) {
        $message = 'login first';
        throw new Exception('login first');
      }
      if ($message == '') {
        $current_page = 1;
        if (!empty($pnum)) {
          $current_page = $pnum;
        }
//        $question = TableRegistry::get('question_master');
        $connection = ConnectionManager::get('default');
        $sql = " SELECT * ,question_master.id as question_id,question_master.status from question_master"
                . " INNER JOIN user_points ON question_master.id = user_points.question_id "
                . " WHERE question_master.created_by = " . $user_id
                . " ORDER BY question_master.id DESC ";
        $count = $connection->execute($sql)->count();
//         $count = $question->find()->where(['created_by' => $user_id])->count();
        $last_page = ceil($count / $range);
        if ($current_page < 1) {
          $current_page = 1;
        } elseif ($current_page > $last_page && $last_page > 0) {
          $current_page = $last_page;
        }
        $limit = 'limit ' . ($current_page - 1) * $range . ',' . $range;
        $sql = " SELECT * ,question_master.id as question_id,question_master.status from question_master"
                . " INNER JOIN user_points ON question_master.id = user_points.question_id "
                . " WHERE question_master.created_by = " . $user_id
                . " ORDER BY question_master.id DESC " . $limit;
        $users_record = $connection->execute($sql)->fetchAll('assoc');
      }
    } catch (Exception $e) {
      $this->log('Error in getUserQuestions function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $users_record,
        'lastPage' => $last_page,
        'start' => (($current_page - 1) * $range) + 1,
        'last' => (($current_page - 1) * $range) + $range,
        'total' => $count,
        '_serialize' => ['data', 'lastPage', 'start', 'last', 'total']
    ]);
  }

  /**
   * This function is used for deleting question of teacher.
   * */
  public function deleteTeacherQuestions() {
    try {
      $message = '';
      $status = FALSE;
      if ($this->request->is('post')) {
        $connection = ConnectionManager::get('default');
        $uniqId = trim($this->request->data['unique_id']);
        $sql_point = "delete from user_points where question_id = " . $this->request->data['id'];
        if ($connection->execute($sql_point)) {

          $sql_question = "delete from question_master where id = " . $this->request->data['id'];
          if ($connection->execute($sql_question)) {
            $sql_option = "delete from option_master where uniqueId ='" . $uniqId . "'";
            if ($connection->execute($sql_option)) {
              $sql_answer = "delete from answer_master where uniqueId ='" . $uniqId . "'";
              if ($connection->execute($sql_answer)) {
                $status = TRUE;
                $message = 'Question deleted successfully.';
              } else {
                $message = 'Some technical error occurred.';
                throw new Exception('answer not deleted.');
              }
            } else {
              $message = 'Some technical error occurred.';
              throw new Exception('options not deleted.');
            }
          } else {
            $message = 'Some technical error occurred.';
            throw new Exception('question not deleted.');
          }
        } else {
          $message = 'Some technical error occurred.';
          throw new Exception('points not deleted.');
        }
      }
    } catch (Exception $e) {
      $this->log('Error in deleteTeacherQuestions function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['message', 'status']
    ]);
  }

  public function filteredTeacherQuestions($user_id = null, $pnum = 1, $grade, $course, $skill) {
    try {
      $message = '';
      $count = '';
      $result = '';
      $subskills = '';
      $users_record = '';
      $range = 10;
      if ($user_id == NULL) {
        $message = 'login first';
        throw new Exception('login first');
      }
      if ($message == '') {
        $current_page = 1;
        if (!empty($pnum)) {
          $current_page = $pnum;
        }
        $question = TableRegistry::get('question_master');
        $count = $question->find()->where(['created_by' => $user_id])->count();
        $last_page = ceil($count / $range);
        if ($current_page < 1) {
          $current_page = 1;
        } elseif ($current_page > $last_page && $last_page > 0) {
          $current_page = $last_page;
        }
        $limit = 'limit ' . ($current_page - 1) * $range . ',' . $range;
        if ($course != -1) {
          $course_detail = TableRegistry::get('course_details');
          $result = $course_detail->find('all')->where(['parent_id' => $course])->toArray();
          $skills = '';
          foreach ($result as $key => $value) {
            $skills[$key] = $value['course_id'];
          }
          $subskill = $course_detail->find('all')->where(['parent_id IN' => $skills]);
          foreach ($subskill as $key => $value) {
            $subskills[$key] = $value['course_id'];
          }
          if (empty($subskills)) {
            $message = 'Result Not Found.';
          }
        } if ($skill != '-1') {
          $subskill = $course_detail->find()->where(['parent_id IN' => $skill])->toArray();
          foreach ($subskill as $key => $value) {
            $subskils[$key] = $value['course_id'];
          }
          if (empty($subskils)) {
            $message = 'Result Not Found.';
          }
        }
        $connection = ConnectionManager::get('default');
        if ($message == '') {
          if ($grade == -1 && $course == -1 && $skill == -1) {//000
            $sql = " SELECT * ,question_master.status from question_master"
                    . " INNER JOIN user_points ON question_master.id = user_points.question_id "
                    . " WHERE question_master.created_by = " . $user_id
                    . " ORDER BY question_master.id DESC " . $limit;
          } else if ($grade != -1 && $course == -1 && $skill == -1) {//100
            $sql = " SELECT * ,question_master.status from question_master"
                    . " INNER JOIN user_points ON question_master.id = user_points.question_id "
                    . " WHERE question_master.created_by = " . $user_id . " AND question_master.grade_id = " . $grade
                    . " ORDER BY question_master.id DESC " . $limit;
          } else if ($grade != -1 && $course != -1 && $skill == -1) {//110
            $sql = " SELECT * ,question_master.status from question_master"
                    . " INNER JOIN user_points ON question_master.id = user_points.question_id "
                    . " WHERE question_master.created_by = " . $user_id . " AND question_master.grade_id =" . $grade . " AND question_master.course_id IN (" . implode(',', $subskills) . ")"
                    . " ORDER BY question_master.id DESC " . $limit;
          } else if ($grade != -1 && $course != -1 && $skill != -1) {//111
            $sql = " SELECT * ,question_master.status from question_master"
                    . " INNER JOIN user_points ON question_master.id = user_points.question_id "
                    . " WHERE question_master.created_by = " . $user_id . " AND question_master.grade_id =" . $grade . " AND question_master.course_id IN (" . implode(',', $subskils) . ")"
                    . " ORDER BY question_master.id DESC " . $limit;
          }
          $users_record = $connection->execute($sql)->fetchAll('assoc');
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getUserQuestions function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $users_record,
        'lastPage' => $last_page,
        'start' => (($current_page - 1) * $range) + 1,
        'last' => (($current_page - 1) * $range) + $range,
        'total' => $count,
        '_serialize' => ['data', 'lastPage', 'start', 'last', 'total']
    ]);
  }

  /**
   * This function is used for edit the question.
   * */
  public function getEditQuestionDetails($user_id = null, $id = null) {
    try {
      $message = '';
      $status = FALSE;
      $question_details = '';
      $option_details = '';
      $answer_details = '';
      $skill = '';
      $subject = '';
      $tmp = '';
      $temp_skill = '';
      $header_details = '';
      $subskill = '';
      // $connection = ConnectionManager::get('default');
      $ques_type = 'text';
      if ($user_id == null) {
        $message = 'Kindly login';
        throw new Exception('User not login.');
      } else if ($id == NULL) {
        $message = 'question not exist.';
        throw new Exception('question id is null.');
      }
      if ($message == '') {
        $question = TableRegistry::get('question_master');
        $option = TableRegistry::get('option_master');
        $answer = TableRegistry::get('answer_master');
        $course_details = TableRegistry::get('course_details');
        $courses = TableRegistry::get('courses');
        $template = TableRegistry::get('content_template_relation');
        $template_detail = TableRegistry::get('ContentTemplate');
        $question_count = $question->find()->where(['created_by' => $user_id, 'id' => $id])->count();
        if ($question_count > 0) {
          $question_details = $question->find()->where(['created_by' => $user_id, 'id' => $id])->toArray();
          foreach ($question_details as $key => $value) {
            $question_unique_id = $value['uniqueId'];
            $subskill = $value['course_id'];
          }
          $option_details = $option->find('all')->where(['uniqueId' => $question_unique_id])->toArray();
          foreach ($option_details as $key => $value) {
            $data = explode('.', $value['options']);
            if (isset($data[1]) && ($data[1] == 'jpg' || $data[1] == 'jpeg' || $data[1] == 'png')) {
              $ques_type = 'image';
            }
          }
          $answer_details = $answer->find('all')->where(['uniqueId' => $question_unique_id])->toArray();
          $connection = ConnectionManager::get('default');
          $sql = "select * from header_master where uniqueId = '" . $question_unique_id . "'";
          $header_details = $connection->execute($sql);
          $subskill_detail = $course_details->find()->where(['course_id' => $subskill])->toArray();
          foreach ($subskill_detail as $key => $value) {
            $temp_skill[$key] = $value['parent_id'];
          }
          $skill = $course_details->find()->where(['course_id IN' => $temp_skill])->orderAsc('parent_id')->toArray();
          $temp_sub_id = '';
          $i = 0;
          foreach ($skill as $key => $value) {
            if ($tmp == $value['parent_id']) {
              $temp_sub_id = $temp_sub_id;
            } else {
              $temp_sub_id[$i] = $value['parent_id'];
              $i++;
              $tmp = $value['parent_id'];
            }
          }
          $subject = $courses->find()->where(['id IN' => $temp_sub_id])->toArray();
          $temp_tpl = $template->find()->where(['content_id IN' => $id, 'created_by' => $user_id, 'template_type' => 'question'])->toArray();
          $temp_details = '';
          if (isset($temp_tpl[0]['template_id']) && !empty($temp_tpl[0]['template_id'])) {
            $temp_details = $template_detail->find('all')->where(['id' => $temp_tpl[0]['template_id']])->toArray();
          }
          $status = TRUE;
        } else {
          $message = 'Question Not found.';
          throw new Exception('Question Not found.');
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getEditQuestionDetails function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        'question' => $question_details,
        'option' => $option_details,
        'answer' => $answer_details,
        'skill' => $skill,
        'subject' => $subject,
        'sub_skill' => $subskill_detail,
        'ques_type' => $ques_type,
        'header' => $header_details,
        'template' => $temp_details,
        '_serialize' => ['status', 'message', 'question', 'option', 'answer',
            'skill', 'subject', 'sub_skill', 'ques_type', 'header', 'template']
    ]);
  }

  /**
   * getTeacherPoints().
   */
  public function getTeacherPoints() {
    $user_current_points = 0;
    $status = FALSE;
    $message = '';
    try {
      $param = $this->request->data;
      if (!$this->request->is('post')) {
        throw new Exception('Request is not secure');
      }
      if (!isset($param['user_id']) || empty($param['user_id'])) {
        throw new Exception('User Id is null');
      }
      $users_controller = new UsersController();
      $user_details = $users_controller->getUserDetails($param['user_id'], TRUE);
      $user_current_points = $user_details['user_all_details']['user_detail']['points'];
      if (empty($user_current_points)) {
        $user_current_points = 0;
      }
      $status = TRUE;
    } catch (Exception $ex) {
      $message = 'Some Error occured';
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        'points' => $user_current_points,
        '_serialize' => ['status', 'message', 'points']
    ]);
  }

  /**
   * getRewards().
   */
  public function getRewards() {
    try {
      $status = FALSE;
      $message = '';
      $available_rewards = 0;
      $reward_list = array();
      if ($this->request->is('post')) {
        $param = $this->request->data;
        $connection = ConnectionManager::get('default');
        if (!isset($param['user_id']) && empty($param['user_id'])) {
          $message = 'Kindly login';
          throw new Exception('user id is missing');
        }
        if (!isset($param['condition_key']) && !isset($param['condition_value'])) {
          $message = 'Unable to filter data';
          throw new Exception('Either Key condition_key or condition_value is missing');
        }
        if (strtolower($param['condition_key']) == 'points') {
          $sql_coupon = 'SELECT * FROM coupons'
                  . ' INNER JOIN coupon_conditions ON coupons.id = coupon_conditions.coupon_id'
                  . ' WHERE  coupon_conditions.condition_key = ' . "'" . $param['condition_key'] . "'"
                  . ' AND coupon_conditions.condition_value <= ' . "'" . $param['condition_value'] . "'"
                  . ' AND coupons.user_type = "TEACHER"'
                  . ' AND coupons.validity >= ' . "'" . time() . "'";
          $reward_list = $connection->execute($sql_coupon)->fetchAll('assoc');
          $available_rewards = count($reward_list);
        }

        $status = TRUE;
        $avail_coupon_sql = 'SELECT * FROM coupons'
                . ' INNER JOIN coupon_avail_status ON coupons.id = coupon_avail_status.coupon_id'
                . ' INNER JOIN coupon_conditions ON coupon_avail_status.coupon_id = coupon_conditions.coupon_id'
                . ' WHERE user_id = ' . "'" . $param['user_id'] . "'";
        $avail_rewards = $connection->execute($avail_coupon_sql)->fetchAll('assoc');
        $temp = array();
        if (!empty($avail_rewards)) {
          foreach ($avail_rewards as $avail) {
            $temp[$avail['coupon_id']] = $avail;
            $temp[$avail['coupon_id']]['id'] = $avail['coupon_id'];
          }
        }
        if (!empty($reward_list)) {
          foreach ($reward_list as &$reward) {
            $reward['status'] = '';
            $reward['id'] = $reward['coupon_id'];
            if (isset($temp[$reward['coupon_id']])) {
              $reward['status'] = $temp[$reward['coupon_id']]['status'];
              unset($temp[$reward['coupon_id']]);
            }
          }
        } else {
          $message = 'No rewards available';
        }
        if (!empty($temp)) {
          $reward_list = array_merge($reward_list, $temp);
        }
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'available_rewards' => $available_rewards,
        'message' => $message,
        'result' => $reward_list,
        '_serialize' => ['status', 'available_rewards', 'message', 'result']
    ]);
  }

  /**
   * setAvailableRewards().
   */
  public function setAvailableRewards() {
    try {
      $status = FALSE;
      $message = $error_code = '';
      $param = $this->request->data;
      if (!isset($param['updated_by_user_id'])) {
        throw new Exception('Kindly login to update coupons');
      }
      $users_controller = new UsersController();
      if (isset($param['user_id']) && !empty($param['user_id'])) {
        if (isset($param['coupon_id']) && !empty($param['coupon_id'])) {
          $user_details_table = TableRegistry::get('UserDetails');

          // When coupon is approved or mlg approval pending, the point will be deducted
          if (strtolower($param['status']) == 'acquired' || strtolower($param['status']) == 'mlg approval pending') {
            $coupon_condition_key = isset($param['coupon_condition_key']) ? $coupon_condition_key : 'points';
            $condition_response = $users_controller->_getCondtionsDetailsOnCoupon($param['coupon_id'], $coupon_condition_key);
            if ($condition_response['status'] == TRUE) {

              $user_details = $users_controller->getUserDetails($param['user_id'], TRUE);
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
          }
        } else {
          $message = 'Coupon Id cannot be empty';
          throw new Exception('Coupon Id cannot be empty');
        }
      } else {
        $message = 'user id cannot be empty';
        throw new Exception('user id cannot be empty');
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'status' => $status,
        'message' => $message,
        'error_code' => $error_code,
        'coupon_status' => ucfirst($param['status']),
        '_serialize' => ['status', 'coupon_status', 'message', 'error_code']
    ]);
  }
  /**
   * This api is used for update question.
   * * */
  public function updateTeacherQuestion() {
    if ($this->request->is('post')) {
      $message = '';
      $status = false;
      $count = 0;
      $question_master = TableRegistry::get('question_master');
      $answer_master = TableRegistry::get('answer_master');
      $option_master = TableRegistry::get('option_master');
      if (isset($this->request->data['tid']) && empty($this->request->data['tid'])) {
        $message = 'login';
      } else if (isset($this->request->data['qId']) && empty($this->request->data['qId'])) {
        $message = 'Quistion not Exist. ';
      } else if (isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
        $message = 'please select grade. ';
      } else if (isset($this->request->data['course']) && empty($this->request->data['course'])) {
        $message = 'Please select course. ';
      } else if (isset($this->request->data['skills']) && empty($this->request->data['skills'])) {
        $message = 'Please select skills. ';
      } else if (isset($this->request->data['sub_skill']) && empty($this->request->data['sub_skill'])) {
        $message = 'Please select sub skill.';
      } else if (isset($this->request->data['ques_diff']) && empty($this->request->data['ques_diff'])) {
        $message = 'Please select question difficulty level. ';
      } else if (isset($this->request->data['ques_diff_name']) && empty($this->request->data['ques_diff_name'])) {
        $message = 'difficulty no t found. ';
      } else if (isset($this->request->data['ques_type']) && empty($this->request->data['ques_type'])) {
        $message = 'Please select question type. ';
      } else {
        $created_by = $this->request->data['tid'];
        $question_id = $this->request->data['qId'];
        $grade = $this->request->data['grade'];
        $course = $this->request->data['course'];
        $skill = $this->request->data['skills'];
        $sub_skill = $this->request->data['sub_skill'];
        $q_diff = $this->request->data['ques_diff'];
        $q_diff_name = $this->request->data['ques_diff_name'];
        $q_type = $this->request->data['ques_type'];
        $q_detail = $this->request->data['question'];
        $subject = $this->request->data['course_name'];
        $option = explode(',', $this->request->data['answer']);
        $corect_answer = explode(',', $this->request->data['correctanswer']);
        $type = $this->request->data['type'];
        $q_status = $question_master->find()->where(['created_by' => $created_by, 'id' => $question_id])->count();
        $unique_id = $question_master->find('all', ['fields' => ['uniqueId']])->where(['created_by' => $created_by, 'id' => $question_id])->toArray('assoc');
        if ($q_status <= 0) {
          $message = 'Question Not Found.';
        }
      }
      if ($message == '') {
        foreach ($sub_skill as $ki => $valu) {
          $query = $question_master->query();
          $result = $query->update()->set([
                      'questionName' => $q_detail,
                      'level' => $q_diff_name,
                      'difficulty_level_id' => $q_diff,
                      'type' => $q_type,
                      'grade' => $grade,
                      'course_id' => $valu,
                      'subject' => $subject,
                  ])->where(['id' => $question_id])->execute();
        }
        $row_count = $result->rowCount();
        if($row_count > 0){
          $status = TRUE;
          $message = 'Question updated Successfully.';
        }
        $opt_id = $option_master->find('all', ['fields' => ['id']])->where(['uniqueId' => $unique_id[0]['uniqueId']])->min('id')->toArray('assoc');
        $option_id = $opt_id['id'];
        if ($this->request->data['type'] == 'text') {
          foreach ($corect_answer as $ki => $val) {
            foreach ($option as $key => $value) {
              if ($key != 0) {
                $option_id = $option_id + 1;
              }
              $query = $option_master->query();
              $result_opt = $query->update()->set([
                          'options' => $value
                      ])->where(['uniqueId' => $unique_id[0]['uniqueId'], 'id' => $option_id])->execute();
              $row_count = $result_opt->rowCount();
              if($row_count > 0){
                $count++;
              }
              if ($key + 1 == $val) {
                $query = $answer_master->query();
                $result_ans = $query->update()->set([
                            'answers' => $value
                        ])->where(['uniqueId' => $unique_id[0]['uniqueId']])->execute();
                $row_count = $result_ans->rowCount();
                if($row_count > 0){
                  $count++;
                }
              }
            }
          }
          if($count > 0) {
            $message = 'Question updated successfully.';
            $status = TRUE;
          }
        }else if ($this->request->data['type'] == 'image'){
          $answer_list = explode(':', $this->request->data['answer']);
          $edit_option = explode(',', $answer_list[0]);
          $edit_count = count($edit_option);
          $new_option = explode(',',$answer_list[1]);
          if($edit_count >= $this->request->data['correctanswer'] ) {
            foreach($edit_option as $key => $val) {
              if ($key+1  == $this->request->data['correctanswer']) {
                $query = $answer_master->query();
                $result_ans = $query->update()->set([
                            'answers' => $val
                        ])->where(['uniqueId' => $unique_id[0]['uniqueId']])->execute();
                $row_count = $result_ans->rowCount();
                if($row_count > 0){
                  $status = TRUE;
                  $message = 'Question updated Successfully.';
                }
              } 
            }
          }
          foreach ($new_option as $key => $value) {
            $option = $option_master->newEntity();
            $option->uniqueId = $unique_id[0]['uniqueId'];
            $option->options = 'upload/' . $value;
            if($option_master->save($option)) {
              if($this->request->data['correctanswer_type'] == 'image' && $this->request->data['correctanswer'] > $edit_count) {
                if ($key+$edit_count+1  == $this->request->data['correctanswer']) {
                  $query = $answer_master->query();
                  $result_ans = $query->update()->set([
                            'answers' => $value
                        ])->where(['uniqueId' => $unique_id[0]['uniqueId']])->execute();
                  $row_count = $result_ans->rowCount();
                  if($row_count > 0){
                    $status = TRUE;
                    $message = 'Question updated Successfully.';
                 }
                } 
              }else{
                $status = TRUE;
                $message = 'Option updated Successfully.';
              }
            }   
          }
        }
      }
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['status', 'message']
    ]);
  }

  /**
   * this function is used for getting the lesson
   * */
  public function getLessonDetailForListing($user_id = null, $pnum = 1) {
    try {
      $message = '';
      $count = '';
      $users_record = '';
      $range = 10;
      if ($user_id == NULL) {
        $message = 'login first';
        throw new Exception('login first');
      }
      if ($message == '') {
        $current_page = 1;
        if (!empty($pnum)) {
          $current_page = $pnum;
        }
        $course_contents = TableRegistry::get('course_contents');
//        $count = $course_contents->find()->where(['created_by' => $user_id])->count();
        $connection = ConnectionManager::get('default');
        $sql = " SELECT * ,course_contents.id as course_content_id,course_contents.status,course_details.name as sub_skill_name from course_contents"
                . " INNER JOIN user_points ON course_contents.id = user_points.course_content_id "
                . " INNER JOIN course_details ON course_contents.course_detail_id = course_details.course_id "
                . " WHERE course_contents.created_by = " . $user_id
                . " ORDER BY course_contents.id DESC ";
        $count = $connection->execute($sql)->count();
        $last_page = ceil($count / $range);
        if ($current_page < 1) {
          $current_page = 1;
        } elseif ($current_page > $last_page && $last_page > 0) {
          $current_page = $last_page;
        }
        $limit = 'limit '.($current_page - 1) * $range . ',' . $range;
        $sql = " SELECT * ,course_contents.id as course_content_id,course_contents.status,course_details.name as sub_skill_name from course_contents"
                . " INNER JOIN user_points ON course_contents.id = user_points.course_content_id "
                . " INNER JOIN course_details ON course_contents.course_detail_id = course_details.course_id "
                . " WHERE course_contents.created_by = " . $user_id
                . " ORDER BY course_contents.id DESC " . $limit;
        $course_details = $connection->execute($sql)->fetchAll('assoc'); 
        $course_detail = TableRegistry::get('course_details');
        $subskill = $course_detail->find()->where(['course_id' => $course_details[0]['course_detail_id']]);
//        $course_detail[0]['sub_skill_name'] = $subskill[0]['name'];
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'data' => $course_details,
        'lastPage' => $last_page,
        'start' => (($current_page - 1) * $range) + 1,
        'last' => (($current_page - 1) * $range) + $range,
        'total' => $count,
        'subSkill' => $subskill,
        '_serialize' => ['data', 'lastPage', 'start', 'last', 'total','subSkill']
    ]);
  }

  public function filteredTeacherLessons($user_id = null, $pnum = 1, $grade, $course, $skill) {
    try {
      $message = '';
      $status = FALSE;
      $count = '';
      $result = '';
      $subskills = '';
      $subskill_list = array();
      $subskill = array();
      $users_record = '';
      $range = 10;
      if ($user_id == NULL) {
        $message = 'login first';
        throw new Exception('login first');
      }
      if ($message == '') {
        $current_page = 1;
        if (!empty($pnum)) {
          $current_page = $pnum;
        }
        $course_content = TableRegistry::get('course_contents');
        $count = $course_content->find()->where(['created_by' => $user_id])->count();
        $last_page = ceil($count / $range);
        if ($current_page < 1) {
          $current_page = 1;
        } elseif ($current_page > $last_page && $last_page > 0) {
          $current_page = $last_page;
        }
        $limit = 'limit ' . ($current_page - 1) * $range . ',' . $range;
        $connection = ConnectionManager::get('default');
        if ($message == '') {
          $course_detail = TableRegistry::get('course_details');
          if ($grade != -1 && $course == -1 && $skill == -1) {
            $query = "SELECT cd.course_id from courses as cs  "
              . "INNER JOIN course_details as cd ON cd.parent_id = cs.id "
              . "WHERE cs.level_id = " . $grade;
            $temp_result = $connection->execute($query)->fetchAll('assoc');
            foreach ($temp_result as $key => $value) {
              $skils[$key] = $value['course_id'];
            }
            $subskil = $course_detail->find('all')->where(['parent_id IN' => $skils]);
            foreach ($subskil as $key => $value) {
              $subskill[$key] = $value['course_id'];
              $subskill_list[$key]['id'] = $value['course_id'];
              $subskill_list[$key]['name'] = $value['name'];
            }
          } else if ($grade != -1 && $course != -1 && $skill == -1) {
            $result = $course_detail->find('all')->where(['parent_id' => $course])->toArray();
            $skills = '';
            foreach ($result as $key => $value) {
              $skills[$key] = $value['course_id'];
            }
            $subskills = $course_detail->find('all')->where(['parent_id IN' => $skills]);
            foreach ($subskills as $key => $value) {
              $subskill[$key] = $value['course_id'];
              $subskill_list[$key]['id'] = $value['course_id'];
              $subskill_list[$key]['name'] = $value['name'];
            }
            if (empty($subskill)) {
              $message = 'Result Not Found.';
            }
          } else if ($grade != -1 && $course != -1 && $skill != -1) {
            $subskil = $course_detail->find()->where(['parent_id IN' => $skill])->toArray();
            foreach ($subskil as $key => $value) {
              $subskill[$key] = $value['course_id'];
              $subskill_list[$key]['id'] = $value['course_id'];
              $subskill_list[$key]['name'] = $value['name'];
            }
            if (empty($subskill)) {
              $message = 'Result Not Found.';
            }
          }
          if(empty($subskill)) {
            $message = 'data not found';
          }else{
            //          $users_record = $course_content->find('all')->where(['course_detail_id IN' => $subskill, 'created_by' => $user_id])->toArray();
            $connection = ConnectionManager::get('default');
            $sql = " SELECT * ,course_contents.id as course_content_id,course_contents.status,course_details.name as sub_skill_name from course_contents"
                . " INNER JOIN user_points ON course_contents.id = user_points.course_content_id "
                . " INNER JOIN course_details ON course_contents.course_detail_id = course_details.course_id "
                . " WHERE course_contents.created_by = " . $user_id ." AND course_contents.course_detail_id IN(".implode(',',$subskill) .")"
                . " ORDER BY course_contents.id DESC  $limit";
            $users_record = $connection->execute($sql)->fetchAll('assoc');
            if(!empty($users_record)) {
              $status = TRUE;
            }
          }
        }
      }
    } catch (Exception $e) {
      $this->log('Error in getUserQuestions function in Teachers Controller.'
        . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'data' => $users_record,
      'status' => $status,
      'lastPage' => $last_page,
      'start' => (($current_page - 1) * $range) + 1,
      'last' => (($current_page - 1) * 10) + 10,
      'total' => $count,
      '_serialize' => ['data','status', 'lastPage', 'start', 'last', 'total']
    ]);
  }

  /*
   * getTeachersOfStudents().
   */

  public function getTeachersOfStudents() {
    try {
      $status = FALSE;
      $record_found = 0;
      $relationship = array();
      $message = '';
      if (!isset($this->request->data['sid'])) {
        $message = 'Please provide student id';
        throw new Exception('Please provide student id');
      }
      if (!empty($this->request->data['sid'])) {
        $sid = $this->request->data['sid'];
        if (is_array($sid)) {
          $sid = implode(',', $sid);
        }
      } else {
        $message = 'student id cannot be empty';
        throw new Exception('student id cannot be empty');
      }
      $student_teachers_table = TableRegistry::get('student_teachers');
      $students_teachers = $student_teachers_table->find()->where(['student_id IN (' . $sid . ')']);
      if ($record_found = $students_teachers->count()) {
        $status = 2;
        foreach ($students_teachers as $data) {
          $relationship[$data->student_id] = $data->teacher_id;
        }
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'record_found' => $record_found,
      'student_teacher_relation' => $relationship,
      '_serialize' => ['status', 'message', 'student_teacher_relation', 'record_found']
    ]);
  }

  /**
   * timeSpentByClassOnPlatform()
   */
  public function timeSpentByClassOnPlatform() {
    try {
      $base_url = Router::url('/', true);
      $request = $this->request;
      $user_ids['user_ids'] = array();
      $date = '';
      $total_duration_in_secs = $total_duration_in_hrs = $average_duration_in_hrs = 0;
      $number_of_students = 0;
      $status = FALSE;
      $message = '';
      if ($request->is('post')) {
        if (isset($request->data['sid'])) {
          $json_teacher_of_student = $this->curlPost($base_url . 'teachers/getTeachersOfStudents/', json_encode(array('sid' => $request->data['sid'])), TRUE);
          $teacher_of_student = json_decode($json_teacher_of_student, TRUE);
          if ($teacher_of_student['status'] == TRUE) {
            $request->data['tid'] = $teacher_of_student['student_teacher_relation'][$request->data['sid']];
          }
        }
        if (!isset($request->data['tid'])) {
          $message = 'Teacher id not found';
          throw new Exception('Teacher Id not found');
        }
        $json_students_of_teacher = $this->curlPost($base_url . 'teachers/getStudentOfTeacher/' . $request->data['tid']);
        $students_of_teacher = json_decode($json_students_of_teacher, TRUE);
        if (!empty($students_of_teacher)) {
          foreach ($students_of_teacher['response']['students'] as $student) {
            $user_ids['user_ids'][] = $student['id'];
          }
        }
        if (!empty($user_ids['user_ids'])) {
          $status = TRUE;
          $json_time_spent_on_platform = $this->curlPost($base_url . 'teachers/timeSpentOnPlatform/', json_encode($user_ids), TRUE);
          $time_spent_by_class_on_platform = json_decode($json_time_spent_on_platform, TRUE);
          if ($time_spent_by_class_on_platform['status'] == TRUE) {
            $total_duration_in_secs = $time_spent_by_class_on_platform['total_duration_in_secs'];
            $total_duration_in_hrs = $time_spent_by_class_on_platform['total_duration_in_hrs'];
            $number_of_students = count($user_ids['user_ids']);
            if ($number_of_students != 0) {
              $average_duration_in_hrs = round($total_duration_in_hrs / $number_of_students, 2);
            }
          }
        } else {
          $message = 'There are no students related to teacher';
        }
      } else {
        $message = 'Some error occured';
        throw new Exception('Request is not POST');
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'total_duration_in_secs' => $total_duration_in_secs,
      'total_duration_in_hrs' => $total_duration_in_hrs,
      'average_duration_in_hrs' => $average_duration_in_hrs,
      'user_ids' => $user_ids['user_ids'],
      'number_of_students' => $number_of_students,
      'date' => $date,
      '_serialize' => ['status', 'message', 'total_duration_in_secs',
        'total_duration_in_hrs', 'user_ids', 'average_duration_in_hrs', 'number_of_students', 'date']
    ]);
  }

  /**
   * timeSpentOnPlatform().
   */
  public function timeSpentOnPlatform() {
    try {
      $request = $this->request;
      $request_data = $request->data;
      $total_duration_in_secs = $total_duration_in_hrs = 0;
      $status = FALSE;
      $message = '';
      $week = isset($request_data['week']) ? $request_data['week'] : -1;
      $date = date("Y-m-d", strtotime("$week week"));
      if ($request->is('post')) {
        if (!isset($request_data['user_ids'])) {
          $message = 'user id required';
          throw new Exception('user id required');
        }
        if (empty($request_data['user_ids'])) {
          $message = 'user id can not be empty';
          throw new Exception('user id can not be empty');
        }
        $user_login_sessions = TableRegistry::get('user_login_sessions');
        $query = $user_login_sessions->find();
        $query_result = $query->select(['sum' => $query->func()->sum('user_login_sessions.time_spent')])
          ->where([
          'check_in >=' => $date,
          'user_id IN (' . implode(',', $request_data['user_ids']) . ')',
          'time_spent IS NOT NULL'
        ]);
        if (!empty($query_result)) {
          foreach ($query_result as $query_response) {
            $status = TRUE;
            $total_duration_in_secs = !empty($query_response->sum) ? $query_response->sum : 0;
            $total_duration_in_hrs = round($total_duration_in_secs / (60 * 60), 2);
          }
        } else {
          $message = 'No record found';
        }
      } else {
        $message = 'Some error occured';
        throw new Exception('Request is not POST');
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'total_duration_in_secs' => $total_duration_in_secs,
      'total_duration_in_hrs' => $total_duration_in_hrs,
      'date' => $date,
      'user_ids' => $request->data['user_ids'],
      '_serialize' => ['status', 'message', 'total_duration_in_secs', 'total_duration_in_hrs', 'user_ids', 'date']
    ]);
  }
 /**
   * function setQuotation.
   */
  public function setQuotation() {
    try {
      $status = FALSE;
      $message = '';
      if (!isset($this->request->data['user_id']) && empty($this->request->data['user_id'])) {
        $message = 'user id not exist';
        throw new Exception($message);
      }
      if (!isset($this->request->data['first_name']) && empty(trim($this->request->data['first_name']))) {
        $message = 'first name cannot be empty';
        throw new Exception($message);
      }
      if (!isset($this->request->data['quotation'])) {
        $message = "Quotation not exist";
        throw new Exception($message);
      }
      $first_name = $this->request->data['first_name'];
      $last_name = $this->request->data['last_name'];
      $email = isset($this->request->data['email']) ? $this->request->data['email'] : '';
      if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email is not valid';
        throw new Exception($message);
      }
      $phone_number = isset($this->request->data['phone_number']) ? $this->request->data['phone_number'] : '';
      $quotations_table = TableRegistry::get('quotations');
      $new_quotation = $quotations_table->newEntity(array(
        'user_id' => $this->request->data['user_id'],
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone_number' => $phone_number,
        'quotation_content' => json_encode($this->request->data['quotation']),
        'created' => time(),
      ));
      if (!$quotations_table->save($new_quotation)) {
        $message = 'Some Error occured while saving Quotation';
        throw new Exception($message);
      } else {
        $status = TRUE;
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      '_serialize' => ['status', 'message']
    ]);
  }

  /**
   * function getQuotation.
   */
  public function getQuotation() {
    try {
      $status = FALSE;
      $message = '';
      $conditions = $quotation_contents = $quotation_fields = array();
      $quotations_table = TableRegistry::get('quotations');
      if (isset($this->request->data['user_id']) && !empty($this->request->data['user_id'])) {
        $conditions['user_id'] = $this->request->data['user_id'];
      }
      if (isset($this->request->data['first_name']) && !empty(trim($this->request->data['first_name']))) {
        $conditions['first_name'] = $this->request->data['first_name'];
      }
      if (isset($this->request->data['email']) && !empty($this->request->data['email'])) {
        $conditions['email'] = $this->request->data['email'];
      }
      if (isset($this->request->data['phone_number']) && !empty($this->request->data['phone_number'])) {
        $conditions['phone_number'] = $this->request->data['phone_number'];
      }
      if (!empty($conditions)) {
        $quotation_result = $quotations_table->find()->where($conditions);
        if ($quotation_result->count()) {
          $status = TRUE;
          foreach ($quotation_result as $quotation) {
            $quotation_contents[] = $quotation->quotation_content;
          }
          if (!empty($quotation_contents)) {
            foreach ($quotation_contents as $quotation_content) {
              $contents = json_decode($quotation_content, TRUE);
              $data = array(
                'first_name' => isset($contents['first_name']) ? $contents['first_name'] : '',
                'last_name' => isset($contents['last_name']) ? $contents['last_name'] : '',
                'email' => isset($contents['email']) ? $contents['email'] : '',
                'phone_number' => isset($contents['phone_number']) ? $contents['phone_number'] : '',
                'position' => isset($contents['position']) ? $contents['position'] : '',
                'school' => isset($contents['school']) ? $contents['school'] : '',
                'street' => isset($contents['street']) ? $contents['street'] : '',
                'city' => isset($contents['city']) ? $contents['city'] : '',
                'district' => isset($contents['district']) ? $contents['district'] : '',
                'zip_code' => isset($contents['zip_code']) ? $contents['zip_code'] : '',
                'country' => isset($contents['country']) ? $contents['country'] : '',
                'country' => isset($contents['country']) ? $contents['country'] : '',
                'licence' => isset($contents['licence']) ? $contents['licence'] : '',
                'number_of_student' => isset($contents['number_of_student']) ? $contents['number_of_student'] : '',
                'comment_for_mlg' => isset($contents['comment_for_mlg']) ? $contents['comment_for_mlg'] : '',
              );
              if (isset($contents['selected_course_values']) && !empty($contents['selected_course_values'])) {
                $selected_courses = $contents['selected_course_values'];
                foreach ($selected_courses as $courses) {
                  $data['selected_courses'][$courses['level_id']][] = array(
                    'id' => $courses['id'],
                    'name' => $courses['name'],
                    'level_id' => $courses['level_id']
                  );
                }
              }
              $quotation_fields[] = $data;
            }
          } else {
            $message = 'No qutation found in your Records';
          }
        } else {
          $message = 'No record found';
        }
      } else {
        $message = 'At least one condition should be mentioned';
      }
    } catch (Exception $e) {
      $this->log($e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'data' => $quotation_fields,
      '_serialize' => ['status', 'message', 'data']
    ]);
  }
  /*
   * function getTeacherSettings().
   */
  public function getTeacherSettings() {
    try {
      $status = FALSE;
      $message = '';
      $user_setting = array();
      $data = $this->request->data;
      if ($this->request->is('post')) {
        if (!isset($data['user_id']) || empty($data['user_id'])) {
          $message = 'Kindly login';
          throw new Exception('User Id cannot be null');
        }
        if (!isset($data['level_id']) || empty($data['level_id'])) {
          $message = 'Level id cannot be null';
          throw new Exception('level id cannot be null');
        }
        if (!isset($data['course_id']) || empty($data['course_id'])) {
          $message = 'course id cannot be null';
          throw new Exception('course Id cannot be null');
        }
        $user_setting = TableRegistry::get('teacher_settings')
           ->find()->where(['user_id' => $data['user_id'], 'course_id' => $data['course_id'], 'level_id' => $data['level_id']]);
        if ($user_setting->count()) {
          $status = TRUE;
        } else {
          $message = 'user setting not found';
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
      'status' => $status,
      'message' => $message,
      'result' => $user_setting->first(),
      '_serialize' => ['status', 'message', 'result']
    ]);
  }

  /*
   * function setTeacherSettings().
   */
  public function setTeacherSettings() {
    try {
      $status = FALSE;
      $message = '';
      if ($this->request->is('post')) {
        $data = $this->request->data;
        if (!isset($data['user_id']) || empty($data['user_id'])) {
          $message = 'Kindly login';
          throw new Exception('User Id cannot be null');
        }
        if (!isset($data['level_id']) || empty($data['level_id'])) {
          $message = 'Level id cannot be null';
          throw new Exception('level id cannot be null');
        }
        if (!isset($data['course_id']) || empty($data['course_id'])) {
          $message = 'course id cannot be null';
          throw new Exception('course Id cannot be null');
        }
        if (!isset($data['course_name']) || empty($data['course_name'])) {
          $message = 'course name cannot be null';
          throw new Exception('course name cannot be null');
        }
        if (!isset($data['settings'])) {
          $message = 'Settings not found';
          throw new Exception('Settings key not present in post data');
        }
        $user_setting_table = TableRegistry::get('teacher_settings');

        $user_settings = $user_setting_table->find('all')->where(['user_id' => $data['user_id'], 'course_id' => $data['course_id'], 'level_id' => $data['level_id']]);
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
            ->where(['user_id' => $data['user_id'], 'course_id' => $data['course_id'], 'level_id' => $data['level_id']])->execute();
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


/* API to get Need Attention on teacher dashboard*/
public function getNeedAttentionForTeacher($teacher_id=null, $subject_id=null){

  $teacher_id = isset($_REQUEST['teacher_id']) ? $_REQUEST['teacher_id'] : $teacher_id;
  $subject_id =isset($_REQUEST['subject_id']) ? $_REQUEST['subject_id'] : $subject_id;
  
  if(!empty($teacher_id) && !empty($subject_id)){
      $connection = ConnectionManager::get('default');

       // get list of skill and subskill on selected subjects (course_id);
             $str1= "SELECT courses.* FROM courses, course_details WHERE courses.id=course_details.course_id AND parent_id = $subject_id";
             $srecords = $connection->execute($str1)->fetchAll('assoc');
             $subskill_ids = $subject_id ;
             if(count($srecords) > 0){
                foreach ($srecords as $srecord) {    
                  $skill_id = $srecord['id'] ;
                  
                  //list of subskill_id 
                  $str2= "SELECT group_concat(course_id) as subskill_ids FROM courses, course_details WHERE courses.id=course_details.course_id AND parent_id = $skill_id GROUP BY courses.id ";
                  $ssRecords = $connection->execute($str2)->fetchAll('assoc');
                  
                  if(count($ssRecords) > 0){  // No condition should occure when any of skill doesn't have subskill
                      foreach ($ssRecords as $ssRecord) {
                         $subskill_ids = $subskill_ids.','.$ssRecord['subskill_ids'];
                      }
                      $data['status']=True;                      
                  }
                }
              
             }else{
                $data['status'] = False;
                $data['message'] = "No skill are found on this subject.";
             }

             // After getting all subskill ids
             if(!empty($subskill_ids)){
                
                // get students list for subject
               $sql3 = "SELECT group_concat(student_id) as student_ids from student_teachers WHERE teacher_id = $teacher_id AND course_id=$subject_id GROUP BY teacher_id ORDER BY student_id ASC "; 
               $stRecords = $connection->execute($sql3)->fetchAll('assoc');

                if(count($stRecords) > 0){
                  foreach ($stRecords as $stRecord) {
                      $stud_ids = $stRecord['student_ids'];
                  }

                  // get students quiz result for subskills
                  $sql4 = "SELECT uq.*,u.username,u.first_name,u.last_name,qt.name as quiz_type_name, cr.course_name from user_quizes as uq, users as u, courses as cr, quiz_types as qt WHERE u.id=uq.user_id AND uq.course_id = cr.id AND qt.id=uq.quiz_type_id AND course_id IN ($subskill_ids) AND uq.user_id IN ($stud_ids) AND uq.quiz_type_id IN (2,4,5,6) ORDER BY created DESC ";
                  $stQuizRecords = $connection->execute($sql4)->fetchAll('assoc');

                  if(count($stQuizRecords) > 0){
                    foreach ($stQuizRecords as $stQuizRecord) {
                        if($stQuizRecord['pass']==0){
                             $data['attention_records'][] = $stQuizRecord;
                        }
                    }        
                  }else{
                    $data['status'] =False;
                    $data['message']="No Records For Your Attention.";
                  }
                }else{
                  $data['status'] = "No students found.";
                }
             }
    }else{
      $data['status'] = False;
      $data['message'] = "Teacher Id and subject id cannot null.Please set teacher id and subject id.";
  }

  $this->set([
      'response' => $data, 
      '_serialize' => ['response']
    ]);


}




  // API to call Analyitic result of students in a subskill
    public function getSubskillAnalytic($teacher_id=null,$subject_id=null,$subskill_id=null){
        $teacher_id = isset($_REQUEST['teacher_id']) ? $_REQUEST['teacher_id'] : $teacher_id;
        $subject_id = isset($_REQUEST['subject_id']) ? $_REQUEST['subject_id'] : $subject_id;
        $subskill_id = isset($_REQUEST['subskill_id']) ? $_REQUEST['subskill_id'] : $subskill_id;
        $connection = ConnectionManager::get('default');

        if(!empty($teacher_id) && !empty($subskill_id) && !empty($subject_id)){
              $count_classStudents = 0;
              $count_noattack =0;
              $count_remedial =0;
              $count_struggling = 0;
              $count_ontarget =0;
              $count_outstanding =0;
              $count_gifted =0;
              $count_escape =0;



               // get students list for subskills
                $sql = "SELECT st.student_id,u.username from student_teachers as st, users as u WHERE u.id=st.student_id AND teacher_id = $teacher_id AND course_id=$subject_id ORDER BY student_id ASC ";  
                $stRecords = $connection->execute($sql)->fetchAll('assoc');

                if(count($stRecords) > 0){
                  foreach ($stRecords as $stRecord) {
                      $count_classStudents++;
                      $class_stud_id = $stRecord['student_id'];
                        

                      // get students quiz result for subskills
                      $sql1 = "SELECT max( (score*100)/exam_marks) as student_result FROM user_quizes as uq
                                    WHERE course_id=$subskill_id AND uq.user_id=$class_stud_id AND quiz_type_id IN (2,4,5,6) GROUP BY course_id ";
                      
                      $stQuizRecords = $connection->execute($sql1)->fetchAll('assoc');

                      if(count($stQuizRecords) > 0){
                        foreach ($stQuizRecords as $stQuizRecord) {
                            //$data['attention_records'][] = $stQuizRecord;
                            /*$exam_marks = $stQuizRecord['exam_marks'];
                            $student_score = $stQuizRecord['score'];*/                          
                            
                            $student_result_precentage = $stQuizRecord['student_result'];
                            if($student_result_precentage <= REMEDIAL){
                                $count_remedial++;
                            }
                            elseif($student_result_precentage >REMEDIAL && $student_result_precentage <=STRUGGLING){
                                $count_struggling++;
                            }
                            elseif($student_result_precentage >STRUGGLING && $student_result_precentage <=ON_TARGET){
                                $count_ontarget++;
                            }
                            elseif($student_result_precentage >ON_TARGET && $student_result_precentage <=OUTSTANDING){
                                $count_outstanding++;
                            }

                            elseif($student_result_precentage >OUTSTANDING && $student_result_precentage <=GIFTED){
                                $count_gifted++;
                            }else{
                                $count_escape++;
                            }
                        }     // end of foreach

                      }else{
                         $count_noattack = $count_noattack+1  ;
                      }              
                  }

                    //$row['percent_classStudents'] = 100;                
                    $row['percent_noattack'] = round( (($count_noattack*100)/$count_classStudents),2);
                    $row['percent_remedial'] = round( (($count_remedial*100)/$count_classStudents),2);
                    $row['percent_struggling']=round( (($count_struggling*100)/$count_classStudents),2);
                    $row['percent_ontarget'] = round( (($count_ontarget*100)/$count_classStudents),2);
                    $row['percent_outstanding'] =round( (($count_outstanding*100)/$count_classStudents),2);
                    $row['percent_gifted'] = round( (($count_gifted*100)/$count_classStudents),2);
                    

                    $data['student_result']=$row;
                    $data['status']=True;
                  

              
                }else{
                  $data['status']=False;
                  $data['message'] = "No students found.";
                }

        }else{
            $data['status'] = False;
            $data['message'] ="teacher_id, subject_id and subskill_id cannot null. please set the value.";
        }

        $this->set([
          'response' => $data, 
          '_serialize' => ['response']
        ]);

    }



  /**
   * setDefaultSettings().
   */
  private function _setDefaultSettings($user_settings = array()) {
    try {
      $default_settings = array(
        'student_chat_enabled' => TRUE,
        'parent_chat_enabled' => TRUE,
        'chat_status' => TRUE,
        'group_builder' => FALSE,
        'placement_test' => TRUE,
        'frequency_of_challenges_by' => 'all_class',
        'auto_progression' => FALSE,
        'auto_progression_by' => 'all_class'
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
   * This function is used for delete the image.
   * **/
  public function deleteImages($uid,$id) {
    try {
      $message = '';
      $image = '';
      $status = FALSE;
      $connection = ConnectionManager::get('default');
      $sql = "Select * from option_master where id = $id ";
      $result = $connection->execute($sql)->fetchAll('assoc');
      foreach ($result as $key => $value) {
        $image = $value['options'];
      }
      $del_sql = "Delete from option_master where id = $id";
      if (!$connection->execute($del_sql)) {
        $message = "unable to delete content";
        throw new Exception($message);
      } else {
        unlink($image);
        $message = "Content deleted successfully.";
        $status = TRUE;
      }
    } catch (Exception $ex) {
      $this->log('Error in getTeacherDetailsForContent function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['message', 'status']
    ]);
  }
  /**
   * This api is used for add new skill. 
   **/
  public function addNewScope() {
    try{
      $message = '';
      $status = FALSE;
      if($this->request->is('post')) {
        if(isset($this->request->data['uid']) && empty($this->request->data['uid'])) {
          $message = 'Please login.';
          throw new Exception('Please login.');
        }else if(isset($this->request->data['grade']) && empty($this->request->data['grade'])) {
          $message = 'Choose a grade.';
          throw new Exception('Choose a grade.');
        }else if(isset($this->request->data['course_id']) && empty($this->request->data['course_id'])) {
          $message = 'Choose a course.';
          throw new Exception('Choose a course.');
        }else if(isset($this->request->data['skill_name']) && empty($this->request->data['skill_name'])) {
          $message = 'Template name can not be empty.';
          throw new Exception('Template name can not be empty.');
        }else if(isset($this->request->data['start_date']) && empty($this->request->data['start_date'])) {
          $message = 'Choose start date.';
          throw new Exception('Choose start date.');
        }else if(isset($this->request->data['end_date']) && empty($this->request->data['end_date'])) {
          $message = 'Choose end date.';
          throw new Exception('Choose end date.');
        }else{
          $scope = TableRegistry::get('scope_and_sequence');
          $course = TableRegistry::get('courses');
          $course_detail = TableRegistry::get('course_details');
          $detail = $course->newEntity();
          $detail->level_id = $this->request->data['grade'];
          $detail->course_name = $this->request->data['skill_name'];
          $detail->created_by = $this->request->data['uid'];
          $detail->created = date('Y-m-d' ,time());
          $detail->start_date = $this->request->data['start_date'];
          $detail->end_date = $this->request->data['end_date'];
          $id = $course->save($detail)->id; 
          if(is_numeric($id)) {
            $result = $scope->find()->where(['created_by' => $this->request->data['uid'],'parent_id' => $this->request->data['course_id']])->toArray();
            foreach ($result as $key => $value) {
              $temp_scope = json_decode($value['scope']);
              $count = count($temp_scope);
              $temp_scope[$count]['course_id'] = $id;
              $temp_scope[$count]['name'] = $this->request->data['skill_name'];
              $temp_scope[$count]['start_date'] = $this->request->data['start_date'];
              $temp_scope[$count]['end_date'] = $this->request->data['end_date'];
              $temp_scope[$count]['parent_id'] = $this->request->data['course_id'];
              $temp_scope[$count]['visibility'] = '1';
              $query = $scope->query();
              $result = $query->update()->set([
                          'scope' => json_encode($temp_scope),
                      ])->where(['created_by' => $this->request->data['uid'],'parent_id' => $this->request->data['course_id'],'type' => $value['type']])->execute();
              $row_count = $result->rowCount();
            }
            $cors_detail = $course_detail->newEntity();
            $cors_detail->course_id = $id;
            $cors_detail->parent_id = $this->request->data['course_id'];
            $cors_detail->name = $this->request->data['skill_name'];
            $cors_detail->created_by = $this->request->data['uid'];
            $cors_detail->created = date('Y-m-d' ,time());
            $cors_detail->start_date = $this->request->data['start_date'];
            $cors_detail->end_date = $this->request->data['end_date'];
            if($course_detail->save($cors_detail)) {
             $status = TRUE;
             $message = 'Data saved successfully.';
            }else{
              $message = 'Some error occurred.';
            }
          }else{
              $message = 'Some error occurred.';
          }
        }
      } 
      
    }catch(Exception $e) {
      $this->log('Error in addNewScope function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['message','status']
    ]);
  }
  /**
   * This api is used for add new skill. 
   **/
  public function getUserCreatedScope($user_id,$parent_id,$type=null,$id=null) {
    try{
      $message='';
      $status = FALSE;
      $skills = '';
      $scopes = array();
      $by = 'course';
      $scope = TableRegistry::get('scope_and_sequence');
      if($user_id == NULL) {
        $message = 'Please login.';
        throw new Exception('Please Login');
      }else if($parent_id == NULL) {
        $message = 'Please select a subject.';
        throw new Exception('Please select a subject.');
      }else if($type == NULL) {
        $message = 'Please choose for which you want to get scopes.';
        throw new Exception('Please choose for which you want to get scopes.');
      }else{
        $scopCount = $scope->find()->where(['created_by'=> $user_id ,'parent_id' => $parent_id ,'type' => $type])->count();
        if($scopCount > 0) {
          $by = 'scope';
          if($type == 'people') {
            if($id == null) {
              $message = 'Select a student.';
              throw new Exception('Select a student.');
            }else{
              $scopes = $scope->find()->where(['created_by'=> $user_id , 
                 'type' => 'people','created_for' => $id ,'parent_id' => $parent_id]);
              $row_count = count($scopes);
              if($row_count == 1) {
               $status = TRUE; 
              }
            }
          }elseif ($type == 'group') {
            if($id == null) {
              $message = 'Select a group.';
              throw new Exception('Select a group.');
            }else{
              $scopes = $scope->find()->where(['created_by'=> $user_id , 
                 'type' => 'group','created_for' => $id ,'parent_id' => $parent_id]);
              $row_count = count($scopes);
              if($row_count == 1) {
               $status = TRUE; 
              }
            }  
          }elseif ($type == 'class') {
            $scopes = $scope->find()->where(['created_by'=> $user_id , 
                 'type' => 'class' ,'parent_id' => $parent_id])->toArray();
            $row_count = count($scopes);
              if($row_count == 1) {
               $status = TRUE; 
              }
          }
        } else {
          $course_detail = TableRegistry::get('course_details');
          $user_role = TableRegistry::get('user_roles');
          $role = $user_role->find('all', ['fields' => ['user_id']])->where(['role_id' => 1])->toArray();
          foreach ($role as $key => $value) {
            $rol[$key] = $value['user_id'];
          }
          $id = implode(',',$rol);
          $id = $id.','.$user_id;
          $connection = ConnectionManager::get('default');
          $sql = " SELECT * from course_details where created_by IN ($id) AND parent_id = $parent_id ";
          $skills = $connection->execute($sql)->fetchAll('assoc');
          if(count($skills) > 0) {
            foreach ($skills as $key => $value) {
            $skill['course_id'] = $value['course_id'];
            $skill['parent_id'] = $value['parent_id'];
            $skill['name'] = $value['name'];
            $skill['start_date'] = $value['start_date'];
            $skill['end_date'] = $value['end_date'];
            $skill['created_by'] = $value['created_by'];
            $skill['status'] = $value['status'];
            $skill['visibility'] = 1;
            $scopes[] = $skill;
            }
            $status = TRUE; 
          }
        } 
      }
    }catch(Exception $e) {
      $this->log('Error in getUserCreatedScope function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $scopes,
        'message' => $message,
        'status' => $status,
        'by' => $by,
        '_serialize' => ['response','message','status','by']
    ]);
  }
  /**
   * this api is used for save template.
   **/
  public function saveScopesAsTemplate() {
    try{
      $message = '';
      $status = FALSE;
      $scope = TableRegistry::get('scope_and_sequence_template');
      if($this->request->is('post')) {
        if(isset($this->request->data['created_by'])&& empty($this->request->data['created_by'])){
          $message = 'Please login';
          throw new Exception('Please login.');
        }else if(isset($this->request->data['name'])&& empty($this->request->data['name'])) {
          $message = 'Please give template a name.';
          throw new Exception('Please give template a name.');
        }else if(isset($this->request->data['grade'])&& empty($this->request->data['grade'])) {
          $message = 'Please choose grade.';
          throw new Exception('Please choose grade.');
        }else if(isset($this->request->data['parent_id'])&& empty($this->request->data['parent_id'])) {
          $message = 'Please choose subject.';
          throw new Exception('Please choose course/skill.');
        }else if(isset($this->request->data['created_for'])&& empty($this->request->data['created_for'])) {
          $message = 'Please Select for which you want to create the scope.';
          throw new Exception('Please Select for which you want to create the scope.');
        }else if(isset($this->request->data['type'])&& empty($this->request->data['type'])) {
          $message = 'Some error occurred';
          throw new Exception('Please defined type');
        }else if(isset($this->request->data['id'])&& empty($this->request->data['id']) && $this->request->data['created_for'] != 'class') {
          $message = 'Please select a student/group';
          throw new Exception('Please select a student/group.');
        }else{
          $detail = $scope->newEntity();
          $detail->grade_id = $this->request->data['grade'];
          $detail->name = $this->request->data['name'];
          $detail->category = $this->request->data['type'];
          $detail->type = $this->request->data['created_for'];
          $detail->created_for = $this->request->data['id'];
          $detail->parent_id = $this->request->data['parent_id'];
          $detail->created_by = $this->request->data['created_by'];
          $detail->created = date('Y-m-d' ,time());
          $detail-> template= json_encode($this->request->data['template']);
          if($scope->save($detail)) {
            $message = 'Tempalte Saved Successfully.';
            $status = TRUE;
          }  else {
            $message = 'Some error occurred.';
          } 
        }
      }
    }catch(Exception $e) {
      $this->log('Error in saveScopesAsTemplate function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['message','status']
    ]);
  }
  
  /**
   * this api is used for save template.
   **/
  public function saveScopesAsSequence() {
    try{
      $message = '';
      $status = FALSE;
      $scope = TableRegistry::get('scope_and_sequence');
      if($this->request->is('post')) {
        if(isset($this->request->data['created_by'])&& empty($this->request->data['created_by'])){
          $message = 'Please login';
          throw new Exception('Please login');
        }else if(isset($this->request->data['grade'])&& empty($this->request->data['grade'])) {
          $message = 'Please choose grade.';
          throw new Exception('Please choose grade.');
        }else if(isset($this->request->data['parent_id'])&& empty($this->request->data['parent_id'])) {
          $message = 'Please choose subject.';
          throw new Exception('Please choose subject.');
        }else if(isset($this->request->data['type'])&& empty($this->request->data['type'])) {
          $message = 'Please Select for which you want to create the scope.';
          throw new Exception('Please Select for which you want to create the scope.');
        }else if(isset($this->request->data['people']) && $this->request->data['type']== 'people' 
                && empty($this->request->data['people'])) {
          $message = 'Please select a student.';
          throw new Exception('Please select a student.');
        }else if(isset($this->request->data['group'])&& $this->request->data['type']== 'group'
                && empty($this->request->data['group'])) {
          $message = 'Please select a group.';
          throw new Exception('Please select a group.');
        }else if(isset($this->request->data['scope'])&& empty($this->request->data['scope'])) {
          $message = 'Some error occurred.';
          throw new Exception('Content can not be empty.');
        }else{
          $id = $this->request->data['created_by'];
          $grade = $this->request->data['grade'];
          $parent = $this->request->data['parent_id'];
          if($this->request->data['type'] == 'class') {
            $count = $scope->find()->where(['created_by' => $id,'type' => 'class' ,'grade_id'=> $grade,'parent_id'=>$parent])->count();
            if($count > 0) {
              $query = $scope->query();
              $result = $query->update()->set([
                          'scope' => json_encode($this->request->data['scope']),
                      ])->where(['created_by' => $id,'parent_id'=>$parent])->execute();
              $row_count = $result->rowCount();
              if($row_count > 0) {
                $message = 'Your scope and sequence saved.';
                $status = TRUE;
              }else{
                $message = 'Your scope and sequence not saved.';
              }
            }
          }elseif($this->request->data['type'] == 'group') {
            $count = $scope->find()->where(['created_by' => $id,
                'type' => 'group',
                'grade_id' => $grade,
                'parent_id' => $parent,
                'created_for' => $this->request->data['group']])->count();
            if($count > 0) {
              $query = $scope->query();
              $result = $query->update()->set([
                          'scope' => json_encode($this->request->data['scope']),
                      ])->where(['created_by' => $id,'parent_id'=>$parent])->execute();
              $row_count = $result->rowCount();
              if($row_count > 0) {
                $message = 'Your scope and sequence saved.';
                $status = TRUE;
              }else{
                $message = 'Your scope and sequence not saved.';
              }
            } 
          }else if($this->request->data['type'] == 'people') {
            $count = $scope->find()->where(['created_by' => $id,
                'type' => 'people' ,
                'grade_id' => $grade,
                'parent_id' => $parent,
                'created_for' => $this->request->data['people'],
                    ])->count();
            if($count > 0) {
              $query = $scope->query();
              $result = $query->update()->set([
                          'scope' => json_encode($this->request->data['scope']),
                      ])->where(['created_by' => $id,'parent_id'=>$parent])->execute();
              $row_count = $result->rowCount();
              if($row_count > 0) {
                $message = 'Your scope and sequence saved.';
                $status = TRUE;
              }else{
                $message = 'Your scope and sequence not saved.';
              }
            }
          }
        }
        if($message == '') {
          $detail = $scope->newEntity();
          $detail->grade_id = $grade;
          $detail->parent_id = $parent;
          $detail->created_by = $id;
          $detail->type = $this->request->data['type'];
          if($this->request->data['type'] == 'people') {
            $detail->created_for = $this->request->data['people'];
          }
          if($this->request->data['type'] == 'group') {
            $detail->created_for = $this->request->data['group'];
          }
          $detail->created = date('Y-m-d' ,time());
          $detail->scope = json_encode($this->request->data['scope']);
          if($scope->save($detail)) {
            $message = 'Your scope and sequence saved.';
            $status = TRUE;
          }else{
            $message = 'Some error occurred.';
          }
        }   
      }
    }catch(Exception $e) {
      $this->log('Error in saveScopesAsSequence function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'message' => $message,
        'status' => $status,
        '_serialize' => ['response','message','status','by']
    ]);
  }
  /**
   * This api is used for geting template detail 
   **/
  public function getScopeTemplate($user_id=null,$type=null) {
    try{
     $message = '';
     $status = FALSE;
     $scope = TableRegistry::get('scope_and_sequence_template');
     if($user_id == NULL) {
       $message = 'Please login';
       throw new Exception('Please login');
     }else if($type == NULL) {
       $message = 'Some Error Occurred.';
       throw new Exception('Please defined type.');
     }else{
      $template = $scope->find()->where(['created_by'=> $user_id ,'category' => $type] ); 
      $status = TRUE; 
     }
    } catch (Exception $e){
      $this->log('Error in getScopeTemplate function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $template,
        'message' => $message,
        'status' => $status,
        '_serialize' => ['response','message','status']
    ]);
  }
  /**
   * This api is used for get student report of teacher
   */
  public function getTeacherStudentReport($user_id=null,$grade=null,$course=null,$pnum=1) {
    try{
      $range = 10;
      $status = FALSE;
      $message = '';
      $student = array();
      $i = 0;
      $total_sub_skill =1;
      $sub_skill_array = array();
      $gap = array();
      if($user_id == null) {
        $message = 'Kindly Login';
        throw new Exception('Kindly Login');
      }else if($grade == null) {
        $message = 'Some error occurred.';
        throw new Exception('Please define Grade');
      }else if($course == null) {
        $message = 'Some error occurred.';
        throw new Exception('Please define Course.');
      }else{
        $current_page = 1;
        $common_query = " SELECT student_id from student_teachers where "
                . "teacher_id = $user_id AND grade_id = $grade AND course_id = $course";
        $subskill_query = " Select * from course_details where parent_id "
                . "IN(Select course_id from course_details where parent_id = $course) ORDER BY parent_id ASC";
        $skill_query = "Select * from course_details where parent_id = $course ORDER BY course_id ASC";
        if (!empty($pnum)) {
          $current_page = $pnum;
        }
        $connection = ConnectionManager::get('default');
        $sql = "select * from users where id IN ($common_query) ORDER BY id DESC ";
        $count = $connection->execute($sql)->count();
        $last_page = ceil($count / $range);
        if ($current_page < 1) {
          $current_page = 1;
        } elseif ($current_page > $last_page && $last_page > 0) {
          $current_page = $last_page;
        }
        $limit = 'limit ' . ($current_page - 1) * $range . ',' . $range;
        $connection = ConnectionManager::get('default');
        $sql = "select * from users where id IN ($common_query) ORDER BY id DESC $limit";
        $studentList = $connection->execute($sql)->fetchAll('assoc');
        $subskill =  $connection->execute($subskill_query)->fetchAll('assoc');
        $skill =  $connection->execute($skill_query)->fetchAll('assoc');
        foreach($subskill as $key=>$value) {
          $sub_skill_array[$i] = $value['course_id'];
          $i++;
        }
        $total_sub_skill = $i;
        foreach ($studentList as $key => $value) {
          $mastered = 0;
          $notStarted = 0;
          $started = 0;
          $temp = array();
          $j= 0 ;
          $master_temp = array();
          $lsql = "select * from user_quizes where user_id = ".$value['id']." AND grade_id = $grade"
                  . " AND course_id IN (". implode(',',$sub_skill_array).") AND quiz_type_id IN (2,5,6,7) ";
          $quizAttempt = $connection->execute($lsql)->fetchAll('assoc');
          if(!empty($quizAttempt)) {
            foreach($quizAttempt as $ki => $val) {
              if(in_array($val['course_id'], $sub_skill_array)) {
                if(!in_array($val['course_id'], $temp)){
                  $temp[$j] = $val['course_id'];
                }
                $marks = ($val['score']/$val['exam_marks'])*100;
                if($marks > QUIZ_PASS_SCORE) {
                  if(!in_array($val['course_id'], $master_temp)){
                    $master_temp[$j] = $val['course_id'];
                  }
                  $mastered++;
                }
                $started++;
              }
            }
            $gap_array = array();
            $student[$value['id']]['id'] = $value['id'];
            $student[$value['id']]['name'] = $value['first_name'].' '.$value['last_name'];
            $student[$value['id']]['mastered'] = round(($mastered*100)/$total_sub_skill,2);
            $student[$value['id']]['started'] = round((count($temp)*100)/$total_sub_skill,2);
            $student[$value['id']]['notstarted'] = round((($total_sub_skill-(count($temp)))*100)/$total_sub_skill,2);
            $student[$value['id']]['gap'] = $total_sub_skill - $mastered ;
//            $gap_array = array_diff($temp, $master_temp);
            $gap_array = array_diff($sub_skill_array, $master_temp);
//            pr($gap_array);die();
            $k = -1;
            foreach ($subskill as $ke => $val) {
              foreach($skill as $ki=> $vale){
                if($val['parent_id'] == $vale['course_id']) {
                  $k++;
                  if(in_array($val['course_id'],$gap_array) ) {
                    $gap[$value['id']][$k]['id'] = $value['id'];
                    $gap[$value['id']][$k]['name'] = $value['first_name'].' '.$value['last_name'];
                    $gap[$value['id']][$k]['skill_id'] = $vale['course_id'];
                    $gap[$value['id']][$k]['skill_name'] = $vale['name'];
                    $gap[$value['id']][$k]['sub_skill_id'] = $val['course_id'];
                    $gap[$value['id']][$k]['sub_skill_name'] = $val['name'];
                  } 
                  break;
                } 
              }
            }
            $mastered = 0;
            $notStarted = 0;
            $started = 0;
            $temp = array();
            $j= 0 ;
            $status = true;
          }else{
            $student[$value['id']]['id'] = $value['id'];
            $student[$value['id']]['name'] = $value['first_name'].' '.$value['last_name'];
            $student[$value['id']]['mastered'] = 0;
            $student[$value['id']]['started'] = 0;
            $student[$value['id']]['notstarted'] = 0;
            $student[$value['id']]['gap'] = $total_sub_skill - $mastered ;
            $status = true;
            $gap_array = array();
            $gap_array = $sub_skill_array;
            $k = -1;
            foreach ($subskill as $ke => $val) {
              foreach($skill as $ki=> $vale){
                if($val['parent_id'] == $vale['course_id']) {
                  $k++;
                  if(in_array($val['course_id'],$gap_array) ) {
                    $gap[$value['id']][$k]['id'] = $value['id'];
                    $gap[$value['id']][$k]['name'] = $value['first_name'].' '.$value['last_name'];
                    $gap[$value['id']][$k]['skill_id'] = $vale['course_id'];
                    $gap[$value['id']][$k]['skill_name'] = $vale['name'];
                    $gap[$value['id']][$k]['sub_skill_id'] = $val['course_id'];
                    $gap[$value['id']][$k]['sub_skill_name'] = $val['name'];
                  } 
                  break;
                } 
              }
            }
          }
        }        
      }
    }catch(Exception $e) {
       $this->log('Error in getTeacherStudentReport function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $student,
        'message' => $message,
        'gap' => $gap,
        'status' => $status,
        'lastPage' => $last_page,
        'start' => (($current_page - 1) * $range) + 1,
        'last' => (($current_page - 1) * $range) + $range,
        'total' => $count,
        '_serialize' => ['response','message','gap','status','lastPage', 'start', 'last', 'total']
    ]);
  }
  
  /**
   * This api is used for get student report of teacher
   */
  public function getTeacherStudentGap($user_id=null,$grade=null,$course=null,$pnum=1,$id=null,$type=null) {
    try{
      $range = 5;
      $status = FALSE;
      $message = '';
      $student = array();
      $studentDetail= array();
      $studentList = array();
      $lackingStudent = 0;
      $i = 0;
      $total_sub_skill =1;
      $sub_skill_array = array();
      $lacking = array();
      if($user_id == null) {
        $message = 'Kindly Login';
        throw new Exception('Kindly Login');
      }else if($grade == null) {
        $message = 'Some error occurred.';
        throw new Exception('Please define Grade');
      }else if($course == null) {
        $message = 'Some error occurred.';
        throw new Exception('Please define Course.');
      }else{
        $current_page = 1;
        $common_query = " SELECT student_id from student_teachers where "
                . "teacher_id = $user_id AND grade_id = $grade AND course_id = $course";
        if (!empty($pnum)) {
          $current_page = $pnum;
        }
        $connection = ConnectionManager::get('default');
        $sql = "select * from users where id IN ($common_query) ORDER BY id DESC ";
        $count = $connection->execute($sql)->count();
        $last_page = ceil($count / $range);
        if ($current_page < 1) {
          $current_page = 1;
        } elseif ($current_page > $last_page && $last_page > 0) {
          $current_page = $last_page;
        }
        $limit = 'limit ' . ($current_page - 1) * $range . ',' . $range;
        $m = 0;
        $connection = ConnectionManager::get('default');
        if($id == null && $type == null) {
          $sql = "select * from users where id IN ($common_query) ORDER BY id DESC";
          $studentDetail = $connection->execute($sql)->fetchAll('assoc');
          foreach ($studentDetail as $key => $value) {
            $studentList[$m] = $value['id'];
            $m++;
          }
        }else if ($id != null && $type == 'student') {
          $sql = "select * from users where id = $id ORDER BY id DESC";
          $studentDetail = $connection->execute($sql)->fetchAll('assoc');
          foreach ($studentDetail as $key => $value) {
            $studentList[$m] = $value['id'];
            $m++;
          }
        }else if($id != null && $type == 'group') {
          $sql = "select * from users where id IN(".explode(',',$id).") ORDER BY id DESC";
          $studentDetail = $connection->execute($sql)->fetchAll('assoc');
          foreach ($studentDetail as $key => $value) {
            $studentList[$m] = $value['id'];
            $m++;
          }
        }
        $skill_query = "Select * from course_details where parent_id = $course ORDER BY course_id ASC $limit";
        $skill =  $connection->execute($skill_query)->fetchAll('assoc');
        $s = 0;
        $skill_array = array();
        foreach ($skill as $key => $value) {
          $skill_array[$s] = $value['id'];
          $s++;
        }
        $subskill_query = " Select * from course_details where parent_id "
                . "IN(".  implode(',', $skill_array) .") ORDER BY parent_id ASC";
        $subskill =  $connection->execute($subskill_query)->fetchAll('assoc');
//        foreach($subskill as $key=>$value) {
//          $sub_skill_array[$i] = $value['course_id'];
//          $i++;
//        }
        $total_sub_skill = $i;
        $p = 0;
        $lack_subskill = array();
        foreach ($skill as $key => $value) {
          foreach($subskill as $ke=>$val) {
            if($val['parent_id'] == $value['course_id']) {
              $sub_skill_array[$i] = $val['course_id'];
              $lack_subskill[$i]['sub_skill_id'] = $val['course_id'];
              $lack_subskill[$i]['sub_skill_name'] = $val['name'];
              $i++;
            }
          }
          $count = 0;
          $temp = array();
          $j= 0 ;
          $master_temp = array();
          if(!empty($sub_skill_array)){
            $lsql = "select *  from user_quizes where user_id IN (".implode(',',$studentList) .") AND grade_id = $grade"
                  . " AND course_id IN (". implode(',',$sub_skill_array).") AND quiz_type_id IN (2,5,6,7) GROUP BY course_id ";
            $quizAttempt = $connection->execute($lsql)->fetchAll('assoc');
            foreach($studentList as $ky=>$valu){
              if(!empty($quizAttempt)) {
                foreach($quizAttempt as $ki => $val) {
                  if(in_array($val['course_id'], $sub_skill_array)) {
                    $marks = ($val['score']/$val['exam_marks'])*100;
                    if($marks < QUIZ_PASS_SCORE) {
                      if(!in_array($val['user_id'], $temp)){
                        $temp[$j] = $val['user_id'];
                        $j++;
                        $count++;
                      }
                    }
                  }
                }
              }
            }
            $lackingStudentList = array_diff($studentList,$temp);
            $lackingStudent = count($lackingStudentList);
          }
          $lacking[$p]['skill_id'] = $value['course_id'];
          $lacking[$p]['skill_name'] = $value['name'];
          $lacking[$p]['lack_student'] = $lackingStudent;
          $lacking[$p]['sub_skill'] = $lack_subskill;
          $p++;
          $sub_skill_array = array();
          $lack_subskill = array();
        }
      }
    }catch(Exception $e) {
       $this->log('Error in getTeacherStudentReport function in Teachers Controller.'
              . $e->getMessage() . '(' . __METHOD__ . ')');
    }
    $this->set([
        'response' => $lacking,
        'message' => $message,
        'status' => $status,
        'lastPage' => $last_page,
        'start' => (($current_page - 1) * $range) + 1,
        'last' => (($current_page - 1) * $range) + $range,
        'total' => $count,
        '_serialize' => ['response','message','gap','status','lastPage', 'start', 'last', 'total']
    ]);
  }
  // get default scope
  
  public function restoreScope(){
    try{
      $course_detail = TableRegistry::get('course_details');
      $user_role = TableRegistry::get('user_roles');
      $role = $user_role->find('all', ['fields' => ['user_id']])->where(['role_id' => 1])->toArray();
      foreach ($role as $key => $value) {
        $rol[$key] = $value['user_id'];
      }
      $id = implode(',',$rol);
      $id = $id.','.$user_id;
      $connection = ConnectionManager::get('default');
      $sql = " SELECT * from course_details where created_by IN ($id) AND parent_id = $parent_id ";
      $skills = $connection->execute($sql)->fetchAll('assoc');
      if(count($skills) > 0) {
        foreach ($skills as $key => $value) {
        $skill['course_id'] = $value['course_id'];
        $skill['parent_id'] = $value['parent_id'];
        $skill['name'] = $value['name'];
        $skill['start_date'] = $value['start_date'];
        $skill['end_date'] = $value['end_date'];
        $skill['created_by'] = $value['created_by'];
        $skill['status'] = $value['status'];
        $skill['visibility'] = 1;
        $scopes[] = $skill;
        }
        $status = TRUE; 
      }
    }catch(Exception $e){
      
    }
  }
}
