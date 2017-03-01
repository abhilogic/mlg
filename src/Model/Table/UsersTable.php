<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config){
       // parent::initialize($config);

        $this->table('users');
        $this->displayField('username');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');



        $this->hasOne('UserDetails', [
          'foreignKey' => 'user_id',
          'dependent' => true
        ]);  
              

        /*$this->belongsTo('UserDetails', [
            'foreignKey' => 'user_id'
        ]);*/
        
    $this->hasMany('UserRoles', [
            'foreignKey' => 'user_id',
            'dependent' => true
        ]);
<<<<<<< HEAD

    $this->hasMany('UserMenus', [
            'foreignKey' => 'user_id',
            'dependent' => true
        ]); 

    $this->hasMany('UserOrders', [
            'foreignKey' => 'user_id',
            'dependent' => true
        ]);

    $this->hasMany('UserCourses', [
            'foreignKey' => 'user_id',
            'dependent' => true
        ]);
    
=======
>>>>>>> eac9e503b68e09d892be18a093354debfc443f5b
}

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->allowEmpty('username');

        $validator
            ->allowEmpty('password');

        $validator
            ->allowEmpty('mobile');

        $validator
            ->allowEmpty('email');

        $validator
            ->allowEmpty('role');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['username']));
        $rules->add($rules->isUnique(['email']));

        return $rules;
    }
}
