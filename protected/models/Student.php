<?php

/**
 * This is the model class for table "student".
 *
 * The followings are the available columns in table 'student':
 * @property integer $id
 * @property string $password
 * @property string $email
 * @property string $time_registration
 * @property string $name
 * @property string $surname
 * @property integer $group
 * @property string $gender
 * @property string $avatar
 * @property integer $group_id
 *
 * The followings are the available model relations:
 * @property Group $group0
 * @property Test[] $tests
 */
class Student extends Users
{
    /**
     * @return string the associated database table name
     */
    public $password2;
    public $group;
    public $_type = 'student';
    
    public function defaultScope()
    {
        return array(
            'condition' => "type='{$this->_type}'"
        );
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $rules = parent::rules();
        
        array_push($rules, array(
            'group',
            'required',
            'on' => 'register, oauth',
            'message' => 'Поле не должно быть пустым'
        ),
        
        array(
            'group',
            'match',
            'pattern' => '/^[0-9А-Яа-яёЁ]+$/ui',
            'message' => 'Необходимо указать номер вашей группы в цифровом формате'
        ),
        array(
            'group',
            'isGroupExist'
        ));
        
        return $rules;
    }
    
    public function attributeLabels()
    {
    	$studentLabels = parent::attributeLabels();
    	$studentLabels['group'] = 'Группа';
    	
    	return $studentLabels;
    }
    
    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'student_group' => array(
                self::BELONGS_TO,
                'Group',
                'group_id'
            ),
            'tests' => array(
                self::MANY_MANY,
                'Test',
                'student_test(student_id, test_id)'
            )
        );
    }
    
    protected function afterSave()
    {
        parent::afterSave();
        $groupId = $this->studentGroupId($this->group);
        $this->updateByPk($this->id, array(
            'group_id' => $groupId
        ));
    }
    
    public function studentGroupId($number)
    {
        $criteria            = new CDbCriteria;
        $criteria->condition = 'number=:number';
        $criteria->params    = array(
            ':number' => $number
        );
        $group               = Group::model()->find($criteria);
        
        return $group->id;
    }
    
    public function isGroupExist()
    {
    	if(!empty($this->group)) {
    		$normalizeGroup = mb_strtolower($this->group);
    		$group = Group::model()->findByAttributes(array('number' => $normalizeGroup));
    		
    		if($group === null)
    		{
    			$this->addError('group', 'Данной группы нет в списке');
    		}
    	}
    }
}