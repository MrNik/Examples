<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "fs_budget_comment".
 *
 * @property integer $id
 * @property integer $budget_id
 * @property integer $user_id
 * @property string $comment
 *
 * @property Budget $budget
 * @property User $user
 */
class BudgetComment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%budget_comment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['budget_id', 'user_id', 'comment'], 'required'],
            [['budget_id', 'user_id'], 'integer'],
            [['comment'], 'string'],
            [['date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'budget_id' => 'Budget ID',
            'user_id' => 'Пользователь',
            'comment' => 'Комментарий',
            'date' => 'Дата',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBudget()
    {
        return $this->hasOne(Budget::className(), ['id' => 'budget_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
