<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class UserRolesTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
        
        $this->table('user_roles');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');


        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'  =>  'INNER'
        ]);


        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType'  =>  'INNER'
        ]);


    }

  
}
