<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class UserCoursesTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('user_courses');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'  =>  'INNER'
        ]); 

        $this->belongsTo('Courses', [
            'foreignKey' => 'course_id',
            'joinType'  =>  'INNER'
        ]);

    }

  
}
