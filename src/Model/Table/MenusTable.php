<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class MenusTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('menus');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');        

        $this->belongsTo('UserMenus', [
            'foreignKey' => 'menu_id',
            'joinType'  =>  'INNER'
        ]);



    }

  
}
