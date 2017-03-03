<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class UserDetailsTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('course_details');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


        $this->belongsTo('Courses', [
            'foreignKey' => 'course_id',
            'joinType'  =>  'INNER'
        ]);          
    }

  
}
