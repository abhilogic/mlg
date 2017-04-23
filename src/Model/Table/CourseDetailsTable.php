<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class CourseDetailsTable extends Table{

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


        $this->belongsTo('ContentCategories', [
          'foreignKey' => 'source_id',
          'joinType'  =>  'LEFT'
        ]);


        $this->hasOne('CourseContents', [
          'foreignKey' => 'course_detail_id',
          'dependent' => true
        ]);
    }

  
}
