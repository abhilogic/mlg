<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class HomeWorksTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('home_works');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

       
        $this->belongsTo('Courses', [
            'foreignKey' => 'course_id',
            'joinType'  =>  'INNER'
        ]);


        $this->hasMany('Users', [
            'foreignKey' => 'user_id',
            'dependent' => true
        ]); 

    }

  
}
