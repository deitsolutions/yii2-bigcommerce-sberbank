<?php


namespace deitsolutions\yii2bigcommercesberbank\controllers;

use yii\filters\Cors;

/**
 * Class DefaultController
 * @package deitsolutions\yii2bigcommercesberbank\controllers
 */
class PaymentController extends ActiveController
{

    /**
     * @var string
     */
    public $modelClass = 'deitsolutions\yii2bigcommercesberbank\models\Payment';

    /**
     * @SWG\Get(path="/payment",
     *     tags={"payment"},
     *     summary="get payment",
     *     description="Get payment data",
     *     produces={"application/json"},
     *     consumes={"application/json"},
     *     @SWG\Parameter(
     *        name="storeId",
     *        in="query",
     *        required=true,
     *        description = "identifier",
     *        required = true,
     *        type = "integer",
     *        format = "int11",
     *        ),
     *
     *     @SWG\Response(
     *         response = 200,
     *         description = "success",
     *     )
     * )
     *
     */

    /**
     * @inheritdoc
     */
    public function behaviors()
    {

        $storeId = \Yii::$app->getRequest()->getQueryParam('storeId', '');

        return array_merge(parent::behaviors(), [
            // For cross-domain AJAX request
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    // restrict access to domains:
                    'Origin' => ($storeId) ? $this->module->stores[$storeId]['apiSettings']['domains'] : null,
                    'Access-Control-Request-Method' => ['POST', 'GET', 'PUT', 'DELETE'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 3600,
                ],
            ],
        ]);
    }

    /**
     * Register order
     * Get order from BigCommerce store via API and register one into Sberbank store
     */
    public function actionRegisterOrder()
    {

    }
}