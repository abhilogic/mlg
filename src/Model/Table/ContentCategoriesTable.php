<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class ContentCategoriesTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('content_categories');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasOne('CourseDetails', [
          'foreignKey' => 'id',
          'dependent' => true
        ]);
    }

  
}
