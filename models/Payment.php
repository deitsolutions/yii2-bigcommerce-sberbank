<?php


namespace deitsolutions\bcsber\models;

use Bigcommerce\Api\Client as Bigcommerce;
use Voronkovich\SberbankAcquiring\Client;
use Voronkovich\SberbankAcquiring\Currency;
use yii\base\Model;
use yii\db\Expression;

/**
 * Class Payment
 * @package deitsolutions\bcsber\models
 */
class Payment extends Model
{
    /**
     * @var
     */
    public $storeId;

    /**
     * @var
     */
    public $orderId;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['storeId', 'orderId',], 'required'],
        ];
    }

    /**
     * @param $config
     * @return bool|mixed
     */
    public function getBigcommerceOrder($config)
    {
        $bcObject = false;
        Bigcommerce::configure($config);
        Bigcommerce::failOnError(true);
        $order = Bigcommerce::getOrder($this->orderId);
        if ($order) {
            $allFields = function () {
                return is_object($this->fields) ? clone $this->fields : $this->fields;
            };
            $getAllFields = $allFields->bindTo($order, $order);
            $object = $getAllFields();
            $bcObject = new \stdClass();
            foreach (get_object_vars($object) as $key => $value) {
                $bcObject->$key = $value;
            }
            $bcObject->products = [];

            $orderProducts = Bigcommerce::getOrderProducts($this->orderId);
            if ($orderProducts) {
                foreach ($orderProducts as $key => $orderProduct) {
                    $getAllFields = $allFields->bindTo($orderProduct, $orderProduct);
                    $bcObject->products[$key] = $getAllFields();
                }
            }
        }
        return $bcObject;
    }

    /**
     * success payment
     * @param $order
     */
    public static function processSuccessPayment($invoice)
    {
       /* $order = \your\models\Order::findOne($invoice->order_id);
        $client = $order->getClient();
        $client->sendEmail('Зачислена оплата по вашему заказу №' . $order->id);*/
    }

    /**
     * @param $order
     * @param $config
     */
    public static function createSberbankInvoice($order, $settings)
    {
        $cartItems = [];
        foreach ($order->products as $i => $product) {
            $cartItem = [
                'positionId' => $i + 1,
                'name' => substr($product->name, 0, 100),
                'quantity' => [
                    'value' => $product->quantity,
                    'measure' => 'штук',
                ],
                'itemAmount' => $product->total_inc_tax,
                'itemPrice' => $product->price_inc_tax,
                'itemCode' => $product->sku,
            ];
            if (isset($settings->paymentMethod) && $settings->paymentMethod) {
                $cartItem['itemAttributes']['paymentMethod'] = $settings->paymentMethod;
            }
            if (isset($settings->paymentObject) && $settings['paymentObject']) {
                $cartItem['itemAttributes']['paymentObject'] = $settings->paymentObject;
            }

            $cartItems[] = $cartItem;
        }
        $orderBundle = [
            'orderCreationDate' => date('Y-m-d', strtotime($order->date_created)) . 'T' . date('h:i:s', strtotime($order->date_created)),
            'customerDetails' => [
                'email' => $order->billing_address->email,
            ],
            'cartItems' => $cartItems,
        ];

        $params = [
            'orderBundle' => $orderBundle,
        ];
        if (isset($settings->taxSystem) && $settings->taxSystem) {
            $params['taxSystem'] = $settings->taxSystem;
        }

        $invoice = \pantera\yii2\pay\sberbank\models\Invoice::addSberbank($order->id, $order->total_inc_tax, null, $params);
        return $invoice;
    }
}