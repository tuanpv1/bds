<?php
namespace frontend\controllers;

use common\models\Email;
use common\models\IpAddressTable;
use common\models\TableAgency;
use Yii;
use yii\base\InvalidParamException;
use yii\data\Pagination;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\AffiliateCompany;
use common\models\Banner;
use common\models\LoginForm;
use common\models\News;
use frontend\models\ContactForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public $enableCsrfValidation = false;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
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

    /**
     * @inheritdoc
     */
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

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $ip = Yii::$app->request->getUserIP();
        $find_model_ip = IpAddressTable::findOne(['ip'=>$ip]); /** @var IpAddressTable $find_model_ip */
        if(isset($find_model_ip) && !empty($find_model_ip)){
            $old_number = $find_model_ip->number;

            $find_model_ip->number = $old_number+1;
            $find_model_ip->update();
        }else{
            $ip_table = new IpAddressTable();
            $ip_table->ip = $ip;
            $ip_table->number = 1;
            $ip_table->save();
        }
        $listBanner = Banner::findAll(['status' => Banner::STATUS_ACTIVE]);

        $listNews = News::find()->andWhere(['status' => News::STATUS_ACTIVE])
            ->andWhere(['type' => News::TYPE_COMMON])
            ->orderBy(['updated_at' => SORT_DESC])->all();

        $listDoiTac = AffiliateCompany::findAll(['type' => AffiliateCompany::TYPE_DOITAC, 'status' => AffiliateCompany::STATUS_ACTIVE]);

        $gioithieu = News::findOne(['id'=>News::ID_ABOUT]);

        $doiNNV = News::find()->andWhere(['status' => News::STATUS_ACTIVE])
        ->andWhere(['type' => News::TYPE_TIENDO])
        ->orderBy(['updated_at' => SORT_DESC])->one();

        $listArray = News::find()
            ->select('id')->andWhere(['status'=>News::STATUS_ACTIVE])
            ->andWhere(['type'=>News::TYPE_COMMON])
            ->orderBy(['updated_at'=>SORT_DESC])
            ->all();

        $duantop = News::find()->andWhere(['status' => News::STATUS_ACTIVE])
            ->andWhere(['type' => News::TYPE_PROJECT])
            ->andWhere(['position'=>News::POSITION_TOP])
            ->orderBy(['updated_at' => SORT_DESC])->one();
        $duankhac = News::find()->andWhere(['status' => News::STATUS_ACTIVE])
            ->andWhere(['type' => News::TYPE_PROJECT])
            ->andWhere(['position'=>News::POSITION_NOTTOP])
            ->orderBy(['updated_at' => SORT_DESC])->limit(3)->all();

        return $this->render('index', [
            'listBanner' => $listBanner,
            'listNews' => $listNews,
            'listDoiTac' => $listDoiTac,
            'gioithieu' => $gioithieu,
            'doiNNV'=>$doiNNV,
            'listArray'=>$listArray,
            'duantop'=>$duantop ,
            'duankhac'=>$duankhac
        ]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
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

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending email.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password was saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    public function actionRegisterEmail(){
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $model = new Email();
        $model->email = $email;
        $model->phone = $phone;
        $model->status = Email::STATUS_ACTIVE;
        if($model->save(false)){
            $content = "Khách hàng có địa chỉ email: ".$email.", số điện thoại: ".$phone." vừa đăng kí nhận tư vấn";
            $to = Yii::$app->params['emailSend'];
            $subject = "Vừa có khách hàng đăng kí nhận tư vấn";
            if($this->sendMail($to,$subject,$content)){
                $message = Yii::t('app','Đăng kí nhận tư vấn thành công.');
                return Json::encode(['success' => true, 'message' => $message]);
            }else{
                $message = Yii::t('app','khong gui dc mail.');
                return Json::encode(['success' => true, 'message' => $message]);
            }
        }else{
            $message = Yii::t('app','Đăng kí nhận tư vấn không thành công.');
            return Json::encode(['success' => false, 'message' => $message]);
        }
    }

    public function actionNews($type = News::TYPE_NEWS,$id = null)
    {
        $cat = null;
        $this->layout = 'main-page.php';
        $listNews = News::find()
            ->andWhere(['status' => News::STATUS_ACTIVE]);
        if($type == News::TYPE_NEWS){
            $listNews->andWhere(['type'=>News::TYPE_NEWS]);
        }elseif($type == News::TYPE_COMMON){
            $listNews->andWhere(['type'=>News::TYPE_COMMON]);
        }elseif($type == News::TYPE_PROJECT){
            $listNews->andWhere(['type'=>News::TYPE_PROJECT]);
        }elseif ($type == News::TYPE_CS){
            $listNews->andWhere(['type'=>News::TYPE_CS]);
        }elseif ($type == News::TYPE_TI){
            $listNews->andWhere(['type'=>News::TYPE_TI]);
        }elseif ($type == News::TYPE_NV){
            $listNews->andWhere(['type'=>News::TYPE_NV]);
        }elseif ($type == News::TYPE_TIENDO){
            $listNews->andWhere(['type'=>News::TYPE_TIENDO]);
        }
        if(isset($id)){
            $listNews->andWhere(['id_cat'=>$id]);
            $cat = News::findOne($id);
        }

        $listNews->orderBy(['created_at' => SORT_DESC]);
        $countQuery = clone $listNews;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pageSize = 6;
        $pages->setPageSize($pageSize);
        $models = $listNews->offset($pages->offset)
            ->limit(6)->all();
        return $this->render('index-news',[
            'listNews' => $models,
            'pages' => $pages,
            'type' => $type,
            'cat'=>$cat
        ]);

    }

    public function actionDetailNews($id)
    {
        $this->layout = 'main-detail.php';
        return $this->render('detail-news',[
            'model' => News::findOne(['id'=>$id])
        ]);
    }

    public function actionInvestment(){ // loi ich dau tu
        $this->layout = 'main-page.php';
        $listNews = News::find()
            ->andWhere(['status' => News::STATUS_ACTIVE])
            ->andWhere(['type'=>News::TYPE_COMMON])
            ->orderBy(['updated_at' => SORT_DESC]);
        $countQuery = clone $listNews;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pageSize = 6;
        $pages->setPageSize($pageSize);
        $models = $listNews->offset($pages->offset)
            ->limit(6)->all();
        $this->layout = 'main-page.php';
        return $this->render('investment',[
            'listNews' => $models,
            'pages' => $pages,
        ]);
    }

    public function actionDistribution(){ // he thong phan phoi
        $this->layout = 'main-page.php';
        $listDistribution = TableAgency::find()
            ->andWhere(['status' => TableAgency::STATUS_ACTIVE])
            ->orderBy(['updated_at' => SORT_DESC ])->all();
        return $this->render('distribution',[
            'model' => $listDistribution,
        ]);
    }

    public function actionGetInvestment(){

        $page = $this->getParameter('page');

        $listNews = News::find()
            ->andWhere(['status' => News::STATUS_ACTIVE])
            ->andWhere(['type'=>News::TYPE_COMMON])
            ->orderBy(['created_at' => SORT_DESC]);
        $models = $listNews->offset($page)
            ->limit(6)->all();
        return $this->renderPartial('_investment',[
            'listNews' => $models,
        ]);

    }

    public function actionGetNews(){

        $page = $this->getParameter('page');
        $type = $this->getParameter('type');

        $listNews = News::find()
            ->andWhere(['status' => News::STATUS_ACTIVE])
            ->andWhere(['type'=> $type])
            ->orderBy(['created_at' => SORT_DESC]);
        $models = $listNews->offset($page)
            ->limit(6)->all();
        return $this->renderPartial('_news',[
            'listNews' => $models,
        ]);

    }

    public function getParameter($param_name, $default = null) {
        return \Yii::$app->request->get($param_name, $default);
    }

    protected function sendMail($to, $subject, $content)
    {
        return Yii::$app->mailer->compose()
            ->setFrom(Yii::$app->params['adminEmail'])
            ->setTo($to)
            ->setSubject($subject)
            ->setTextBody($content)
            ->send();
    }
}
