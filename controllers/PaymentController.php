<?php


namespace deitsolutions\bcsber\controllers;

use Bigcommerce\Api\Client as Bigcommerce;
use deitsolutions\bcsber\models\Payment;
use pantera\yii2\pay\sberbank\models\Invoice;
use yii\base\ErrorException;
use yii\filters\Cors;
use yii\web\Controller;

/**
 * Class PaymentController
 * @package deitsolutions\bcsber\controllers
 */
class PaymentController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {

        $storeId = \Yii::$app->getRequest()->getQueryParam('storeId', \Yii::$app->getRequest()->getBodyParam('storeId',''));
        $origin = $storeId ? $this->module->stores[$storeId]['settings']['acceptedDomains'] : null;
        return array_merge(parent::behaviors(), [
            // For cross-domain AJAX request
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    // restrict access to domains:
                    'Origin' => $origin,
                    'Access-Control-Request-Method' => ['POST', 'GET', 'PUT', 'DELETE'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 3600,
                ],
            ],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'create' => ['GET', 'POST'], //@TODO delete GET
        ];
    }

    /**
     * Register order
     * Get order from BigCommerce store via API and register one into Sberbank store
     */
    public function actionCreate()
    {
        $model = new Payment();
        $fields = \Yii::$app->getRequest()->getBodyParams();
        //$fields = \Yii::$app->getRequest()->getQueryParams(); //@TODO replace with BODY params
        if ($model->load($fields, '') && $model->validate()) {
            if (isset($this->module->stores[$model->storeId]['adapter']['auth'])) {
                $order = $model->getBigcommerceOrder($this->module->stores[$model->storeId]['adapter']['auth']);
                if ($order) {
                    $invoice = Payment::createSberbankInvoice($order, $this->module->stores[$model->storeId]['settings']);
                    $response = \Yii::$app->modules['sberbank']->sberbank->create($invoice);
                    if (isset($response['errorCode'])) {
                        throw new ErrorException($response['errorMessage']);
                    }
                    $orderId = $response['orderId'];
                    $formUrl = $response['formUrl'];
                    $invoice->orderId = $orderId;
                    $invoice->update();
                    return $formUrl;
                } else {
                    throw new ErrorException('Order not found', 400);
                }
            } else {
                throw new ErrorException('Server error: "auth" param not set', 400);
            }
        } else {
            throw new ErrorException('Order not found', 400);
        }
    }
}