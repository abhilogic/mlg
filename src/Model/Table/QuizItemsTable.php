<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class QuizItemsTable extends Table{
    /**
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
       $this->table('quiz_items');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Items', [
            'foreignKey' => 'item_id',
            'joinType'  =>  'INNER'
        ]);

       $this->belongsTo('ExamSections', [
            'foreignKey' => 'exam_section_id',
            'joinType'  =>  'INNER'
        ]);



    }

  
}
