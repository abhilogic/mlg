<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class CoursesTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('courses');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


        $this->belongsTo('UserCourses', [
            'foreignKey' => 'course_id',
            'joinType'  =>  'INNER'
        ]);          
    }

  
}
