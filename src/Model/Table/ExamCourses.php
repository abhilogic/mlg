<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class ExamCoursesTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
                
        $this->table('exam_courses');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


       
       $this->belongsTo('CourseDetails', [
            'foreignKey' => 'course_detail_id',
            'joinType'  =>  'INNER'
        ]); 

        $this->belongsTo('ExamSections', [
            'bindingKey' => ['exam_id'],
            'foreignKey' => ['exam_id'],
            'joinType'  =>  'INNER'
]);




       


    }

  
}
