<?php

namespace app\models;

use Yii;
use app\models\Users;

/**
 * This is the model class for table "user_fields".
 *
 * @property integer $id
 * @property string $name
 * @property integer $is_active
 * @property integer $is_required
 * @property integer $type
 *
 * @property UserData[] $userDatas
 */
class UserFields extends \yii\db\ActiveRecord
{
    public $subscribeDate;

    public static function tableName()
    {
        return 'user_fields';
    }

    public function rules()
    {
        return [
            [['name', 'title', 'input'], 'required'],
            ['name', 'match', 'pattern' => '/[a-zA-Z0-9_-]+/'],
            [['is_active', 'is_required'], 'integer'],
            [['name', 'type', 'title', 'input'], 'string', 'max' => 100],
            [['name'], 'unique'],
            [['value'], 'safe']
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Имя',
            'is_active' => 'Активное',
            'is_required' => 'Обязательное',
            'type' => 'Тип поля БД',
            'title' => 'Заголовок',
            'input' => 'Тип поля',
            'value' => 'Значение',
            'subscribeDate' => 'Время записи'
        ];
    }

    public function beforeSave($insert) {
        if($this->value != '') {
            $vals = [];
            foreach (explode(';', $this->value) as $value) {
                $vals[$value] = $value;
            }
            $this->value = serialize($vals);
            //$this->value = serialize(explode(';', $this->value));
        }
        $connection = \Yii::$app->db;
        if($this->isNewRecord) {
            $sql = "ALTER TABLE users ADD COLUMN ".$this->name." ".$this->type. ($this->is_required == 1 ? " NOT NULL" : "");
            $connection->createCommand($sql)->execute();
        }
        else {
            $old_data = self::find()->where(['id' => $this->id])->one();
            $sql = "ALTER TABLE users CHANGE ".$old_data->name." ".$this->name." ".$this->type. ($this->is_required == 1 ? " NOT NULL" : "");
            $connection->createCommand($sql)->execute();
        }

        return parent::beforeSave($insert);
    }

    public function beforeDelete() {
        $connection = \Yii::$app->db;
        $sql = "ALTER TABLE users DROP COLUMN ".$this->name;
        $connection->createCommand($sql)->execute();
        return parent::beforeDelete();
    }

    public function getTypesArray() {
        return ['integer'=>'integer', 'varchar(100)'=>'varchar(100)', 'text'=>'text'];
    }

    public function getInputTypes() {
        return ['text'=>'text', 'checkbox'=>'checkbox', 'radio'=>'radio'];
    }
}
