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
        
        $this->table('items');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

       $this->hasMany('Items', [
            'foreignKey' => 'item_id',
            'dependent' => true
        ]);


        $this->hasMany('ExamSections', [
            'foreignKey' => 'exam_section_id',
            'dependent' => true
        ]);



    }

  
}
