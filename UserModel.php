<?php

class UserModel extends CommonModel {
    public $email;
    public $name;
    public $surname;
    public $sex = 0;
    public $city_id;
    public $pswd;
    public $pswd2;
    public $new_pswd;
    public $role;
    public $rectime;
    public $avatar;
    public $birthdate = 0;
    public $have_notification = 1;
    public $sponsor_notification = 1;
    public $is_active = true;
    public $ref_id;
    public $invite_date = 0;
    public $count_invited;
    public $openid = 0;
    public $whoInvited;

    public $last_visit;

    public $birthmonth = 0;
    public $birthday = 0;
    public $birthyear = 0;

    public $new_count;
    public $all_count;
    public $new_rectime;
    public $all_rectime;
    public $last_msg;
    public $invitations;
    
    public $inviteSelfCode = '';

    /**
     * Администратор/Аукционист - только сотрудник BigTicket, отвечающий за конкретный лот
     * @var int
     */
    const ROLE_ADMIN = 1;
    /**
     * Владелец – статус присваивается пользователю, выставляющему товар, на время проведения конкретного лота
     * @var int
     */
    const ROLE_OWNER = 2;
    /**
     * Пользователь, отправивший заявку на регистрацию, известна только его почта
     * @var unknown_type
     */
    const ROLE_CANDIDATE = 3;
    /**
     * Пользователь, которомц отправлено приглашение на регистрацию, но еще не зарегистрировавшийся
     * @var unknown_type
     */
    const ROLE_INVITED = 4;
    /**
     * Новичок – статус присваивается пользователю с баллами от 0 до 9
     * @var int
     */
    const ROLE_BEGINNER = 5;
    /**
     * Участник – статус присваивается пользователю с баллами от 10 до 24
     * @var int
     */
    const ROLE_MEMBER = 6;
    /**
     * Little star – статус присваивается пользователю с баллами от 25 до 49
     * @var int
     */
    const ROLE_LITTLE_STAR = 7;
    /**
     * Rising star – статус присваивается пользователю с баллами от 50 до 99
     * @var int
     */
    const ROLE_RISING_STAR = 8;
    /**
     * Big star – статус присваивается пользователю с баллами от 100 до 299
     * @var int
     */
    const ROLE_BIG_STAR = 9;
    /**
     * 
     * @var int
     */
    const ROLE_BLACK_STAR = 10;
    /**
     * Маленький аватар размером 39*39
     * @var int
     */
    const SMALL_AVATAR = 0;
    /**
     * Маленький аватар размером 71*71
     * @var int
     */
    const MEDIUM_AVATAR = 1;
    /**
     * Маленький аватар размером x*151
     * @var int
     */
    const BIG_AVATAR = 2;
    
    /**
     *
     * @param object $className
     * @return UserModel 
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }


    public function rules() {
        $obj = new CHtmlPurifier();
        $obj->options = array('Core.EscapeInvalidTags'=>true);
        return array(
        array('pswd', 'required', 'on'=>'first_register', 'message'=>'Необходимо ввести Ваш пароль.',),
        array('email', 'required', 'on'=>'first_register', 'message'=>'Необходимо ввести Ваш электронный адрес.',),
        array('email', 'match', 'not' => false, 'pattern' => '/^(([a-zA-Z0-9_\-.]+)@([a-zA-Z0-9\-]+)\.[a-zA-Z0-9\-.]+$)/', 'on'=>'first_register', 'message'=>'Введенный Вами электронный адрес не верен.',),
        array('email', 'match', 'not' => false, 'pattern' => '/^(([a-zA-Z0-9_\-.]+)@([a-zA-Z0-9\-]+)\.[a-zA-Z0-9\-.]+$)/', 'on'=>'register', 'message'=>'Введенный Вами электронный адрес не верен.',),
        array('birthdate', 'required', 'on'=>'register', 'message'=>'Необходимо ввести дату Вашего рождения.',),
        array('name', 'required', 'on'=>'register', 'message'=>'Необходимо ввести Ваше имя.',),
        array('surname', 'required', 'on'=>'register', 'message'=>'Необходимо ввести Вашу фамилию.',),
        array('birthday, birthmonth, birthyear', 'match', 'not' => true, 'pattern' => '/^0$/', 'on'=>'register'),
        array('city_id', 'required', 'on'=>'register', 'message'=>'Необходимо выбрать Ваш город.', ),
        array('email', 'required', 'on'=>'updatecandidate'),
        array('email,pswd2', 'length', 'max'=>100),
        array('pswd', 'length', 'min' => 6, 
            'tooShort'=>Yii::t("translation", "Пароль должен быть не менее 6 символов.")),
        //array('pswd','match','pattern'=>'/^([a-zA-Z0-9])+$/','message'=>Yii::t('lan','Только латинские символы и цифры.')),
        array('name,surname,ref_id', 'length', 'max'=>250),
        array('email','email', 'on'=>'register,append,loginza,updatecandidate'),
        array('role,city_id,sex,ref_id,birthdate,birthyear,birthmonth,birthday,invitations,mailout,page_id,enter_page_id','numerical', 'integerOnly'=>true),
        //array('pswd', 'compare', 'compareAttribute'=>'pswd2', 'on'=>'register'),
        array('pswd','unsafe', "on"=>"append"),
        array('avatar,name,surname,pswd,inviteSelfCode','filter','filter'=>array($obj,'purify')),
        array('email', 'required', 'on'=>'append'),
        array('avatar', 'file', 'types'=>'jpg, gif, png, bmp', 'on'=>'update', 'allowEmpty'=>true),
        array('have_notification,sponsor_notification,is_active','boolean'),
        array('vkontakte, facebook, twitter, odnoklassniki', 'safe'),
       // array('birthdate','date','format'=>DataUtils::SHORT_DATE_FORMAT),
        array('email','unique'),

        array('email, id,count_invited,ref_id,name,surname,rank,have_notification,sponsor_notification', 'safe', 'on'=>'search'),
        array('email, id,count_invited,ref_id,role,name,surname,have_notification,sponsor_notification', 'safe', 'on'=>'searchUnsubscribed'),
    );
    }

    public function attributeNames() {
        return array('id','name','surname','city_id','pswd',
        		'email','sex','role','rectime','avatar',
        		'have_notification','ref_id','invite_date','count_invited','openid',
        		'birthdate','sponsor_notification'
        );
    }

    public function tableName() {
        return 'users';
    }

    public function attributeLabels() {
        return array(
            'pswd'=>'Пароль',
            'pswd2'=>'Повторить пароль',
            'email'=>'E-mail',
        	'role'=>'Роль',
            'name'=>'Имя',
            'surname'=>'Фамилия',
            'sex'=>'Пол',
            'rectime'=>'Дата регистрации',
            'city_id'=>'Город',
            'avatar'=>'Фото',
            'have_notification'=>'Получать новости БигТикет',
            'sponsor_notification'=>'Получать акции спонсоров',
        	'ref_id'=>'Реферал',
            'count_invited'=>'Количество приглашенных',
            'invite_date'=>'Дата приглашения',
            'is_active'=>'Статус',
            'birthdate'=>'Дата рождения',
            'block_time'=>'Дата добавления в черный список',
            'ref_user'=>'Пригласивший',
            'rank' => 'Звание',
            'invitations' => 'Выслано приглашений',
            'mailout' => 'Рассылка',
            'page_id' => 'Стартовая',
            'enter_page_id' => 'Стартовая входа',
        );
    }

    public function relations() {
        return array(
            'city'=>array(self::BELONGS_TO, 'CityModel', 'city_id'),
            'socialNetworks'=>array(self::HAS_MANY, 'SocialNetworkModel', 'user_id'),
            'account'=>array(self::HAS_ONE, 'AccountModel', 'user_id'),
            'accountActivity'=>array(self::HAS_MANY, 'AccountActivityModel', 'user_id'),
            'taskUser'=>array(self::HAS_MANY, 'TaskUserModel', 'user_id'),
            'lastBlock'=>array(self::STAT, 'BlackListModel', 'user_id', 'select'=>'max(rectime)'),
            'bonus_account'=>array(self::STAT, 'BonusAccountsModel', 'user_id', 'select'=>'sum(balance-withdraw)', 'condition'=>'is_active=1 and date_end>EXTRACT(EPOCH FROM current_timestamp)'),
            'ref_user'=>array(self::BELONGS_TO, 'UserModel', 'ref_id'),
            'rank'=>array(self::HAS_ONE, 'UsersRanks', 'user_id'),
            'notification'=>array(self::HAS_ONE, 'UsersNotifications', 'user_id'),
        );
    }
    
    /**
     *
     * @param integer $id
     * @param boolean $activeOnly;
     * @return UserModel
     */
    public function getById($id, $activeOnly=false) {
        if (!$activeOnly) 
            return parent::getById($id); 
        else {
            return $this->findByPk($id, '(role!=:role_invited) and (role!=:role_candidate)', 
                array(':role_invited'=>self::ROLE_INVITED, ':role_candidate'=>self::ROLE_CANDIDATE));            
        }
    }
    

    /**
     * 
     * @param $email
     * @param $activeOnly
     * @return UserModel
     */
    public function getByEmail($email, $activeOnly=false) {
        $criteria = new CDbCriteria();
        $criteria->addCondition('lower(email)=lower(:email)');
        if ($activeOnly)
            $criteria->addNotInCondition('role', array(self::ROLE_CANDIDATE, self::ROLE_INVITED));
        $criteria->params[':email'] = $email;
        return $this->find($criteria);
    }
    
    public function getByLot($lotId, $limit=0, $offset=0) {
        $criteria = $this->createCriteria();
        if($limit) {
            $sql = 'select u.* from users u where u.id in
        	(select aa.user_id from account_activity aa where aa.lot_id=:lot_id 
        	and (aa.transaction_type=:transaction_type1))
        	order by u.rectime desc limit :limit offset :offset';
            $criteria->params['limit'] = $limit;
            $criteria->params['offset'] = $offset;
        } else $sql = 'select u.* from users u where u.id in
            (select aa.user_id from account_activity aa where aa.lot_id=:lot_id 
            and (aa.transaction_type=:transaction_type1))
            order by u.rectime desc';
        $criteria->params['lot_id'] = $lotId;
        $criteria->params['transaction_type1'] = AccountActivityModel::TRANSACTION_TYPE_WITHDRAW;
        return $this->findAllBySql($sql, $criteria->params);
    }

    public function setPswd() {
        $ret = $this->updateByPk($this->id, array('pswd'=>hash('sha256', $this->pswd)));
        return $ret;
    }

    public function beforeSave() {
        if ($this->getIsNewRecord()) {
            $this->rectime = time();
            //$this->is_active = true;
        } else {
            $model = $this->getById($this->id);
            if ($model->is_active&&(!$this->is_active)) {
                $blackListModel = new BlackListModel();
                $blackListModel->user_id = $this->id;
                $blackListModel->save(false);
            } else
                if ((!$model->is_active)&&($this->is_active)) {
                    $blackListModel = new BlackListModel();
                    $blackListModel = $blackListModel->getByUser($this->id, $this->lastBlock);
                    if ($blackListModel!=null)  {
                        $blackListModel->end_date = time();
                        $blackListModel->save(false);
                    }
                }
        }
        return true;
    }

    protected function afterSave() {
        if (!$this->account) {
            $accountModel = new AccountModel();
            $accountModel->makeAccount($this->id);
        }
        if (!$this->rank) { 
            $userRank = new UsersRanks();
            $userRank->makeUserRank($this->id);
        }
        if (!$this->notification) { 
            $userNotifications = new UsersNotifications();
            $userNotifications->makeUserNotifications($this->id);
        }
    }
    
    protected function beforeDelete() {
        $ret = false;
        if ($this->role==self::ROLE_BEGINNER) {
            $connection=Yii::app()->db; 
            $sql = 'delete from task_user where user_id='.$this->id;
            $command=$connection->createCommand($sql);
            $command->execute();
            $sql = 'delete from ranks_actions where user_id='.$this->id;
            $command=$connection->createCommand($sql);
            $command->execute();
            $sql = 'delete from account_activity where user_id='.$this->id;
            $command=$connection->createCommand($sql);
            $command->execute();
        }
        $account = AccountModel::model()->getByUserId($this->id);
        if ($account) {
            $ret = $account->delete();
        }
        $userRank = UsersRanks::model()->getByUserId($this->id);
        if ($userRank) {
            $ret = $userRank->delete();
        }
        $userNotifications = UsersNotifications::model()->getByUserId($this->id);
        if ($userNotifications) {
            $ret = $userNotifications->delete();
        }
        return $ret;
    }
    
    protected function afterDelete() {
        parent::afterDelete();
        $userLogModel = new UserLogModel();
        $userLogModel->refid = $this->id;
        $userLogModel->reftype = UserLogModel::TYPE_DELETE_USER;
        $userLogModel->refname = $this->getCaption();
        if ($this->role==self::ROLE_INVITED) $userLogModel->action = 'удаление приглашенного пользователя'; 
        if ($this->role==self::ROLE_CANDIDATE) $userLogModel->action = 'удаление кандидата'; 
        $q = $userLogModel->save(false);
    }

    /**
     * Возвращает URL аватара пользователя. Если аватар не задан, возвращается дефолтное изображение
     *
     * @uses getAvatarName()
     *
     * @param string $avatar
     * @param string $type self::SMALL_AVATAR(MEDIUM_AVATAR|BIG_AVATAR)
     * @return string возвращаемый URL
     */
    public function getAvatarUrl($type=self::SMALL_AVATAR) {
    	if ($this->avatar) {
            return Yii::app()->fileservice->url.'/'.Yii::app()->fileservice->number.'/'.$this->getAvatarName($type);
    	}
    	else {
    		switch ($type) {
    			case self::BIG_AVATAR:
    				return "/images/main/new_dummy_3.jpg";
    				break;
    			case self::MEDIUM_AVATAR:
    				return "/images/main/new_dummy_medium.jpg";
    				break;
    			case self::SMALL_AVATAR:	
    			default:
    				return "/images/main/dummy2.jpg";
    			break;
    		}
    	}
    }
    
    public function getAvatarName($type=self::SMALL_AVATAR) {
    	if ($type==self::BIG_AVATAR) {
   			$extNum = strrpos($this->avatar, ".");
   			$ext = substr($this->avatar, $extNum+1);
   			$name = substr($this->avatar, 0, $extNum)."_big.".$ext;
   			return $name;
   		} else if ($type==self::MEDIUM_AVATAR) {
   			$extNum = strrpos($this->avatar, ".");
   			$ext = substr($this->avatar, $extNum+1);
   			$name = substr($this->avatar, 0, $extNum)."_medium.".$ext;
   			return $name;
   		} else
   			return $this->avatar;
    }
    
    public function getContacts($userId, $criteria=null) {
        $criteria = $this->createCriteria();
        $attrs = $this->attributeNames();
        for ($i=0;$i<count($attrs);$i++) $attrs[$i] = 'u.'.$attrs[$i];
        $attrList = implode(',', $attrs);
        $sql = "select
    	            count(m.id) as all_count,
                    max(m.rectime) as all_rectime,
                    max(m.rectime*m.status) as new_rectime,
                    sum(m.status*(floor(-(abs(m.recipient_id-:user_id0)/(m.author_id+m.recipient_id+1.1)))+1)) as new_count,
                    (select m1.message from messages m1 where m1.id=max(m.id)) as last_msg,
                    ".$attrList."
                from messages m
                inner join users u on (m.author_id+m.recipient_id-:user_id2=u.id)
                where ((m.author_id=:user_id3 and m.ptype=:ptype1) or (m.recipient_id = :user_id4 and m.ptype=:ptype2))
                group by ".$attrList."
                order by all_rectime desc, new_rectime desc"; 
        $criteria->params['user_id0'] = $userId;
        $criteria->params['user_id2'] = $userId;
        $criteria->params['user_id3'] = $userId;
        $criteria->params['user_id4'] = $userId;
        $criteria->params['ptype1'] = MessageModel::OUT_MESSAGE;
        $criteria->params['ptype2'] = MessageModel::IN_MESSAGE;
        return $this->findAllBySql($sql, $criteria->params);
    }

    public function checkInviteSelf($inviteSelfId, $inviteSelfCode){
        $user = $this->getById($inviteSelfId);
        if ($user) {
            $md5 = $this->makeInviteSelfCode($user);
            if ($md5 == $inviteSelfCode) {
                return $user;
            }
        }
        $this->addError('ref_id', 'Эта ссылка для регистрации недействительна');
        return null;
    }

    public function makeInviteSelfCode($user){
        return md5('some data' . $user->id);
    }

    /**
     *
     * @param string $uid
     * @return UserModel 
     */
    public function getBySocialNetwork($uid, $activeOnly=false){
        $criteria = new CDbCriteria();
        $criteria->with = array(
            'socialNetworks'=>array(
                'joinType'=>'LEFT JOIN',
//                'condition'=>'"socialNetworks"."uid"=\''.$uid.'\'',
            )
        );
        $criteria->addCondition('"socialNetworks"."uid"=:uid');
        if ($activeOnly)
            $criteria->addNotInCondition('role', array(self::ROLE_CANDIDATE, self::ROLE_INVITED));
        $criteria->params[':uid'] = (string)$uid;
        return $this->find($criteria);
    }

    public function updateSelfVisit($id=0){
        try {
            $id = DataUtils::checkIntVal($id, 0);
            if ($id) {
                $t = time();
                $ret = $this->updateByPk($id, array('last_visit'=>$t));
            }
        } catch (Exception $e) {
        	Yii::log("update self visit by id=".$id." error:".$e->getMessage(), 'info', 'application');
        }
    }
    
    public function activateUserStatus(){
        $this->is_active = 1;
        $this->count_invited = 0;
        $this->save();
    }

    /**
     * Перевод пользоватея в статус приглашенного
     */
    public function invite() {
        $this->role = UserModel::ROLE_INVITED;
        $this->invite_date = time();
        $this->invitations = $this->invitations + 1;
        $this->save();
    }
    
    public function updateCountInvited($id=0, $c=0){
        if (!$id) {
            $this->count_invited++;
            $this->save(false, 'count_invited');
            return;
        }
        $count = $this->count_invited + $c;
        $this->updateByPk($id, array('count_invited'=>$count));
    }

    /**
     *
     * @param int $role
     * @param boolean $activeOnly
     * @return CDbCriteria
     */
    public function getCriteriaByRole($role, $isActive=1) {
        $criteria = parent::createCriteria();
        $criteria->addCondition('role=:role');
        $criteria->params[':role']=$role;
        if ($isActive>=0) {
            $criteria = $this->addActiveCriteria($criteria, $isActive);
        }
        return $criteria;
    }

    /**
     *
     * @param array $roles
     * @param boolean $isActive
     * @return CDbCriteria
     */
    public function getCriteriaByRoles(array $roles, $isActive=1) {
        $criteria = parent::createCriteria();
        $criteria->addInCondition('role', $roles);
        if ($isActive>=0) {
            $criteria = $this->addActiveCriteria($criteria, $isActive);
        }
        return $criteria;
    }

    /**
     *
     * @param $activeOnly
     * @return CDbCriteria
     */
    public function getRegisteredCriteria($isActive=1) {
        $roles = array(
            self::ROLE_OWNER, self::ROLE_BEGINNER,
            self::ROLE_MEMBER, self::ROLE_LITTLE_STAR, self::ROLE_RISING_STAR,
            self::ROLE_BIG_STAR, self::ROLE_BLACK_STAR,
        );
        return $this->getCriteriaByRoles($roles, $isActive);
    }

    /**
     *
     * @param CDbCriteria $criteria
     * @param boolean $is_active
     * @return CDbCriteria
     */
    public function addActiveCriteria(CDbCriteria $criteria, $isActive=true) {
        $criteria->addCondition('is_active=:is_active');
        $criteria->params[':is_active']=($isActive?1:0);
        return $criteria;
    }

    public function getCaption($onlyName = false) {
        if($this->name && $this->surname)
            return $this->name.' '.($this->name?DataUtils::cutOffStr($this->surname, 1):'').'.';
        elseif ($onlyName) {
            return false;
        } else return $this->email;
    }

    public function checkCaption() {
        return($this->name && $this->surname);
    }
    
    public static function getSaveRegCode($model){
        return md5($model->id.$model->rectime);
    }
    
    public function deleteAvatarImages() {
        if ($model->avatar) {
            $res1 = Yii::app()->fileservice->deleteFile($model->getAvatarName(UserModel::SMALL_AVATAR));
            $res2 = Yii::app()->fileservice->deleteFile($model->getAvatarName(UserModel::MEDIUM_AVATAR));
            $res3 = Yii::app()->fileservice->deleteFile($model->getAvatarName(UserModel::BIG_AVATAR));
        }
        return $res1&&$res2&&$res3;
    }
    
    /**
     * Возвращает список участников лота, которе либо не проходили задание, либо их 
     * результаты были удалены
     * @param $lotId идентификатор лота
     * @return список пользователей
     */
    public function getListByLotWithoutTask($lotId) {
        $criteria = $this->createCriteria();
        $sql = 'select u.* from users u
        		inner join account_activity aa on aa.user_id=u.id 
        		inner join lots l on l.id=aa.lot_id 
        		where u.id not in (
        			select tu.user_id from task_user tu
        			inner join task t on t.id=tu.task_id
        			where t.lot_id=l.id)
        		and l.id=:lot_id order by u.rectime';
        $criteria->params['lot_id'] = $lotId;
        return $this->findAllBySql($sql, $criteria->params);
    }
    
    public function getParticipantsSql() {
        $sql = "select 
        			u.id as user_id, 
        			u.email, 
        			u.name||' '||substring(u.surname from 1 for 1)||'.' as username, 
        			aa.transaction_date,
                    aa.payment_type,
        			tu.id, 
        			tu.count_correct,
        			tu.all_time,
                    tu.time_finished,
                    tu.time_start,
                    tu.times_paused,
                    tu.all_pause_time,
                    tu.hide_wrong_1,
                    tu.hide_wrong_2
        		from users u
				inner join account_activity aa on aa.user_id=u.id and aa.transaction_type = 4
				inner join lots l on l.id=aa.lot_id
				inner join task t on t.lot_id=l.id
				left join task_user tu on tu.user_id=u.id and tu.task_id=t.id
				where l.id=:lot_id";
        return $sql;
    }

    function getRef_idById ($id) {
        $criteria = new CDbCriteria();
        $criteria->addCondition('id=:id');
        $criteria->params[':id'] = $id;
        $criteria->select ='ref_id';
        return $this->find($criteria);
    }
    
    function getUserHref($publicOnly=false) {
    	$baseUrl = Yii::app()->urlManager->baseUrl;
    	$suffix = ((substr($baseUrl, -1)=='/')?'':'/');
		if ($publicOnly||(!Yii::app()->user)||(Yii::app()->user)&&(Yii::app()->user->getId()!=$this->id))
			return $baseUrl.$suffix.'u'.$this->id.'/';
		else 
			return $baseUrl.$suffix.'profile/';
    }

    public function getLastvisit ($userId) {
        $criteria = new CDbCriteria();
        $criteria->addCondition('id=:id');
        $criteria->params[':id'] = $userId;
        $criteria->select ='last_visit';
        return $this->find($criteria);
    }

    public function getByService ($service, $serviceId) {
        $criteria = new CDbCriteria();
        $criteria->addCondition($service.'=:serviceId');
        $criteria->params[':serviceId'] = $serviceId;
        $criteria->addNotInCondition('role', array(self::ROLE_CANDIDATE, self::ROLE_INVITED));
        if ($this->find($criteria))
            return $this->find($criteria);
        else return false;
    }

    public function search($role){
        $criteria=new CDbCriteria;
        $criteria->compare('t.id',$this->id);
        $criteria->compare('email',$this->email,true);
        $criteria->compare('name',$this->name,true);
        $criteria->compare('surname',$this->surname,true);
        $criteria->compare('count_invited',$this->count_invited);
        $criteria->compare('ref_id',$this->ref_id);
        $criteria->addCondition('role='.$role);
        $criteria->addCondition('is_active=1');
        if($role == 4) {
            $criteria->addCondition('mailout=1');
            $criteria->compare('invitations',$this->invitations);
        } elseif($role == 5) {
            $criteria->with=array('rank');
            $criteria->compare('rank.rank_id',$this->rank,true);
        }
        
        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'pagination'=>array("pageSize"=>100),
            'sort'=>array('attributes'=>array('id','email','name','surname','count_invited','ref_id','rectime','invitations','invite_date',
                'rank'=>array(
                'asc' => $expr='rank.rank_id',
                'desc' => $expr.' DESC',
            ),
        )),
        ));
    }

    public function makeUnsubscribeCode($user){
        return md5('data for unsubscribe' . $user->id);
    }

    public function checkUnsubscribeCode($userId, $unsubscribeCode){
        $user = $this->getById($userId);
        if ($user) {
            $md5 = $this->makeUnsubscribeCode($user);
            if ($md5 == $unsubscribeCode) {
                return $user;
            }
        }
        $this->addError('ref_id', 'Эта ссылка для отписки недействительна');
        return null;
    }

    public function searchUnsubscribed(){
        $criteria=new CDbCriteria;
        $criteria->compare('id',$this->id);
        $criteria->compare('email',$this->email,true);
        $criteria->compare('name',$this->name,true);
        $criteria->compare('surname',$this->surname,true);
        $criteria->compare('count_invited',$this->count_invited);
        $criteria->compare('ref_id',$this->ref_id);
        $criteria->compare('role',$this->role);
        $criteria->addCondition('mailout=0');
        
        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'pagination'=>array("pageSize"=>100),
        ));
    }

    public function defaultScope() {
        return array(
            'order'=>'rectime desc',
        );
    }


}