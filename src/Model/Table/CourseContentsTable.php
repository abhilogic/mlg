<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class CourseContentsTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('course_contents');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


        $this->belongsTo('CoursesDetails', [
            'foreignKey' => 'id',
            'joinType'  =>  'INNER'
        ]);
    }

  
}
