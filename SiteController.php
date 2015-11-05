<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Service;
use app\models\WorkTime;
use app\models\Timeslot;
use app\models\Params;
use app\models\Users;
use app\models\UserFields;
use app\models\Logs;
use \DateTime;
use app\models\helpers\TimeHelper;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex() {
        $services = Service::find()->all();
        return $this->render('index', ['services' => $services]);
    }

    public function actionService($id) {
        $params = Params::getParamsArray();
        $service = Service::find()->where(['id' => $id])->one();
        $model = new WorkTime();
        $work_days = WorkTime::find()->where(['service_id' => $id])->andWhere(['>=', 'day', date('Y-m-d', time())])->all();
        $free_days = $work_days;
        foreach ($work_days as $key => $work_day) {
            if(!WorkTime::checkIfDayAvailable($work_day, $id)) unset($free_days[$key]);
        }

        return $this->render('service', [
            'service' => $service, 
            'model' => $model, 
            'work_days' => $work_days,
            'free_days' => $free_days,
            'params' => $params,
        ]);
    }

    public function actionSelecttime($id, $date) {
        $params = Params::getParamsArray();
        $day = new DateTime($date);
        $day = date_format($day, 'Y-m-d');
        $workTime = WorkTime::find()->where(['service_id' => $id, 'day' => $day])->one();
        $model = new Timeslot();
        return $this->render('selecttime', [
            'model' => $model, 
            'workTime' => $workTime,
            'params' => $params,
            'date' => $date,
            'id' => $id,
            'day' => $day,
        ]);
    }

    public function actionSubscribe($id, $date, $time) {
        $t = explode('-', $time);
        $time = $t[0];
        $params = Params::getParamsArray();
        $day = new DateTime($date);
        $day = date_format($day, 'Y-m-d');
        $model = new Users();
        $fields = UserFields::find()->where(['is_active' => 1])->all();

        if(WorkTime::find()->where(['service_id' => $id, 'day' => $day])->one() 
            && WorkTime::checkIfTimeAvailable(TimeHelper::getSeconds($time), $day, $id)) {

            if(isset($_POST['Users'])) {
                $model->load(Yii::$app->request->post());
                foreach ($fields as $field) {
                    if($field->input == 'checkbox' && is_array($_POST['Users'][$field->name])) {
                        $name = $field->name;
                        $model->$name = serialize($_POST['Users'][$field->name]);
                    }
                }

                if($model->validate() && $model->save()) {
                    $timeslot = new Timeslot();
                    $timeslot->user_id = $model->id;
                    $timeslot->date = $day;
                    $timeslot->time_start = $time;
                    $timeslot->interval = $params['time_period'];
                    $timeslot->service_id = $id;
                    if($timeslot->save()) {
                        
                        $text = 'Вы записаны на '.$timeslot->date.' '.$timeslot->time_start;
                        if($model->email != '') {
                            if(Yii::$app->mailer->compose()
                                ->setFrom(Yii::$app->params['adminEmail'])
                                ->setTo($model->email)
                                ->setSubject('Запись на прием')
                                ->setTextBody($text)
                                ->send()
                            ) {
                                $log = new Logs();
                                $log->action = 'emailSubscribe';
                                $log->status = 1;
                                $log->user_id = $model->id;
                                $log->save();
                            }
                        }
                        if($model->phone != '') {
                            $status = $this->sendSms($model->phone, $text);
                            $log = new Logs();
                            $log->action = 'smsSubscribe';
                            $log->status = $status;
                            $log->user_id = $model->id;
                            $log->save();
                        }

                        return $this->render('success', [
                            'model' => $model,
                            'timeslot' => $timeslot,
                        ]);
                    }
                }
            }
            return $this->render('subscribe', [
                'model' => $model, 
                'fields' => $fields,
                'params' => $params,
                'id' => $id,
                'date' => $date,
            ]);
        } else {
            echo 'Время записи недоступно';
        }
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    public function actionAbout()
    {
        return $this->render('about');
    }

    private function sendSms($phone, $text) {
        $ch = curl_init("http://sms.ru/sms/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "api_id"        =>  Yii::$app->params['sms_app_id'],
            "to"            =>  $phone,
            "text"      =>  $text,
            "test"  => 1
        ));
        $body = curl_exec($ch);
        curl_close($ch);
        return $body;
    }

    public function actionTestfb() {
        $sql = 'select f.rdb$relation_name, f.rdb$field_name
            from rdb$relation_fields f
            join rdb$relations r on f.rdb$relation_name = r.rdb$relation_name
            and r.rdb$view_blr is null 
            and (r.rdb$system_flag is null or r.rdb$system_flag = 0)
            order by 1, f.rdb$field_position;';  
        //print_r(phpinfo());
        print_r(Yii::$app->db_firebird->createCommand($sql)->execute());
    }
}
