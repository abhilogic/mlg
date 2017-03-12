<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class ExamSectionsTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
                
        $this->table('exam_sections');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


       
       $this->belongsTo('Exams', [
            'foreignKey' => 'exam_id',
            'joinType'  =>  'INNER'
        ]); 

       $this->hasOne('QuizItems', [
          'foreignKey' => 'exam_section_id',
          'dependent' => true
        ]);
       




       


    }

  
}
