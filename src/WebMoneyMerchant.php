<?php

namespace ActionM\WebMoneyMerchant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ActionM\WebMoneyMerchant\Events\WebMoneyMerchantEvent;
use ActionM\WebMoneyMerchant\Exceptions\InvalidConfiguration;

class WebMoneyMerchant
{
    public function __construct()
    {
    }

    /**
     * Allow the access, if the ip address is in the whitelist.
     * @param $ip
     * @return bool
     */
    public function allowIP($ip)
    {
        // Allow the local ip or any other ip address
        if ($ip == '127.0.0.1' || in_array('*', config('webmoney-merchant.allowed_ips'))) {
            return true;
        }

        return in_array($ip, config('webmoney-merchant.allowed_ips'));
    }

    /**
     * Generates the '403' error code.
     * @param $message
     * @return mixed
     */
    public function responseError($message)
    {
        return abort(403, $message);
    }

    /**
     * Returns the 'YES' success message.
     * @return string
     */
    public function responseOK()
    {
        return 'YES';
    }

    /**
     * Fills in the event details to pass the title and request params as array.
     * @param $event_type
     * @param $event_title
     * @param Request $request
     */
    public function eventFillAndSend($event_type, $event_title, Request $request)
    {
        $event_details = [
            'title' => 'WebMoneyMerchant: '.$event_title,
            'ip' => $request->ip(),
            'request' => $request->all(),
        ];

        event(
            new WebMoneyMerchantEvent($event_type, $event_details)
        );
    }

    /**
     * Calculates the signature for the order form.
     * @param $LMI_PAYMENT_AMOUNT
     * @param $LMI_PAYMENT_NO
     * @return string
     */
    public function getFormSignature($LMI_PAYMENT_AMOUNT, $LMI_PAYMENT_NO)
    {
        $hashStr = config('webmoney-merchant.WM_LMI_PAYEE_PURSE').';'.$LMI_PAYMENT_AMOUNT.';'.$LMI_PAYMENT_NO.';'.config('webmoney-merchant.WM_LMI_SECRET_X20').';';

        return hash('sha256', $hashStr);
    }

    /**
     * Returns the hash for the params from WebMoneyMerchant.
     * @param Request $request
     * @return string
     */
    public function getSignature(Request $request)
    {
        $hashStr =
            $request->get('LMI_PAYEE_PURSE').
            $request->get('LMI_PAYMENT_AMOUNT').
            $request->get('LMI_PAYMENT_NO').
            $request->get('LMI_MODE').
            $request->get('LMI_SYS_INVS_NO').
            $request->get('LMI_SYS_TRANS_NO').
            $request->get('LMI_SYS_TRANS_DATE').
            config('webmoney-merchant.WM_LMI_SECRET_KEY').
            $request->get('LMI_PAYER_PURSE').
            $request->get('LMI_PAYER_WM');

        return hash('sha256', $hashStr);
    }

    /**
     * Generates the order array with required fields for the order form.
     * @param $payment_amount
     * @param $payment_no
     * @param $item_name
     * @return array
     */
    public function generateWebMoneyMerchantOrderWithRequiredFields($payment_amount, $payment_no, $item_name)
    {
        $order = [
            'PAYMENT_AMOUNT' => $payment_amount,
            'PAYMENT_NO' => $payment_no,
            'ITEM_NAME' => base64_encode($item_name),
        ];

        $this->requiredOrderParamsCheck($order);

        return $order;
    }

    /**
     * Checks required order params for the order form and raise an exception if it fails.
     * @param $order
     * @throws InvalidConfiguration
     */
    public function requiredOrderParamsCheck($order)
    {
        $required_fields = [
            'PAYMENT_AMOUNT',
            'PAYMENT_NO',
            'ITEM_NAME',
        ];

        foreach ($required_fields as $key => $value) {
            if (! array_key_exists($value, $order) || empty($order[$value])) {
                throw InvalidConfiguration::generatePaymentFormOrderParamsNotSet($value);
            }
        }

        // Checks if PAYMENT_NO is numeric.
        if (! is_numeric($order['PAYMENT_NO'])) {
            throw InvalidConfiguration::generatePaymentFormOrderInvalidPaymentNo('PAYMENT_NO');
        }

        // Checks if PAYMENT_NO > 0 and < 2147483647
        if (intval($order['PAYMENT_NO']) < 1 || intval($order['PAYMENT_NO']) > 2147483647) {
            throw InvalidConfiguration::generatePaymentFormOrderInvalidPaymentNo($order['PAYMENT_NO']);
        }
    }

    /**
     * Generates html forms from view with payment buttons
     * Note: you can customise the view via artisan:publish.
     * @param $payment_amount
     * @param $payment_no
     * @param $item_name
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function generatePaymentForm($payment_amount, $payment_no, $item_name)
    {
        $order = $this->generateWebMoneyMerchantOrderWithRequiredFields($payment_amount, $payment_no, $item_name);

        $this->requiredOrderParamsCheck($order);

        /* WM Merchant accepts windows-1251; use only latin characters for the product name*/
        $payment_fields = [];
        $payment_fields['LMI_PAYMENT_AMOUNT'] = $order['PAYMENT_AMOUNT'];
        $payment_fields['LMI_PAYMENT_NO'] = $order['PAYMENT_NO'];
        $payment_fields['LMI_PAYMENT_DESC_BASE64'] = $order['ITEM_NAME'];
        $payment_fields['LOCALE'] = config('webmoney-merchant.locale');
        $payment_fields['LMI_PAYEE_PURSE'] = config('webmoney-merchant.WM_LMI_PAYEE_PURSE');
        $payment_fields['LMI_PAYMENTFORM_SIGN'] = $this->getFormSignature($payment_fields['LMI_PAYMENT_AMOUNT'], $payment_fields['LMI_PAYMENT_NO']);

        return view('webmoney-merchant::payment_form', compact('payment_fields'));
    }

    /**
     * Validates the request params from WebMoneyMerchant.
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'LMI_PAYEE_PURSE' => 'required',
            'LMI_PAYMENT_AMOUNT' => 'required',
            'LMI_PAYMENT_NO' => 'required',
            'LMI_PAYER_IP' => 'required',
            'LMI_HASH' => 'required',
            'LMI_HASH2' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    /**
     * Validates the payee purse from WebMoneyMerchant.
     * @param Request $request
     * @return bool
     */
    public function validatePayeePurse(Request $request)
    {
        if ($request->get('LMI_PAYEE_PURSE') != config('webmoney-merchant.WM_LMI_PAYEE_PURSE')) {
            return false;
        }

        return true;
    }

    /**
     * Validates the request signature from WebMoneyMerchant.
     * @param Request $request
     * @return bool
     */
    public function validateSignature(Request $request)
    {
        $sign = $this->getSignature($request);

        if (mb_strtoupper($request->get('LMI_HASH')) != mb_strtoupper($sign)) {
            return false;
        }

        return true;
    }

    /**
     * Validates the allowed ip, request params and signature from WebMoneyMerchant.
     * @param Request $request
     * @return bool
     */
    public function validateOrderRequestFromGate(Request $request)
    {
        if (! $this->AllowIP($request->ip()) || ! $this->validate($request) || ! $this->validatePayeePurse($request) || ! $this->validateSignature($request)) {
            $this->eventFillAndSend('webmoneymerchant.error', 'validateOrderRequestFromGate', $request);

            return false;
        }

        return true;
    }

    /**
     * Validates the required attributes of the found order.
     * @param Request $request
     * @param $order
     * @return bool
     */
    public function validateSearchOrderRequiredAttributes(Request $request, $order)
    {
        if (! $order) {
            $this->eventFillAndSend('webmoneymerchant.error', 'orderNotFound', $request);

            return false;
        }

        // Checks required found order attributes.
        $attr = ['WEBMONEY_orderStatus', 'WEBMONEY_orderSum'];

        foreach ($attr as $k => $value) {
            if (! $order->getAttribute($value)) {
                $this->eventFillAndSend('webmoneymerchant.error', $value.'Invalid', $request);

                return false;
            }
        }

        // Compares order attributes with request params.
        if ($order->getAttribute('WEBMONEY_orderSum') != $request->input('LMI_PAYMENT_AMOUNT')) {
            $this->eventFillAndSend('webmoneymerchant.error', $value.'Invalid', $request);

            return false;
        }

        return true;
    }

    /**
     * Calls SearchOrderFilter and check return order params.
     * @param Request $request
     * @return bool
     * @throws InvalidConfiguration
     */
    public function callFilterSearchOrder(Request $request)
    {
        $callable = config('webmoney-merchant.searchOrderFilter');

        if (! is_callable($callable)) {
            throw InvalidConfiguration::searchOrderFilterInvalid();
        }

        /*
         *  SearchOrderFilter
         *  Searches the order in the database and return the order details.
         *  Must return the array with:
         *
         *  orderStatus
         *  orderSum
         */

        $order = $callable($request, $request->input('LMI_PAYMENT_NO'));

        if (! $this->validateSearchOrderRequiredAttributes($request, $order)) {
            return false;
        }

        return $order;
    }

    /**
     * Calls PaidOrderFilter if the order is not paid.
     * @param Request $request
     * @param $order
     * @return mixed
     * @throws InvalidConfiguration
     */
    public function callFilterPaidOrder(Request $request, $order)
    {
        $callable = config('webmoney-merchant.paidOrderFilter');

        if (! is_callable($callable)) {
            throw InvalidConfiguration::orderPaidFilterInvalid();
        }

        // Unset the custom order attributes; for Eloquent support.
        unset($order['WEBMONEY_orderSum'], $order['WEBMONEY_orderStatus']);

        // Runs the `PaidOrderFilter` callback.
        return $callable($request, $order);
    }

    /**
     * Runs WebMoneyMerchant::payOrderFromGate($request) when the request from WebMoney Merchant has been received.
     * @param Request $request
     * @return mixed
     */
    public function payOrderFromGate(Request $request)
    {
        if (! $request->has('LMI_HASH')) {
            return 'OK';
        }

        if ($request->has('LMI_PREREQUEST')) {
            return 'YES';
        }

        // Validates the request params from the WebMoney Merchant server.
        if (! $this->validateOrderRequestFromGate($request)) {
            $this->eventFillAndSend('webmoneymerchant.error', 'validateOrderRequestFromGate', $request);

            return $this->responseError('validateOrderRequestFromGate');
        }

        // Searches and returns the order
        $order = $this->callFilterSearchOrder($request);

        if (! $order) {
            $this->eventFillAndSend('webmoneymerchant.error', 'searchOrderFilter', $request);

            return $this->responseError('searchOrderFilter');
        }

        // If the current order status is `paid`.
        // Sends the notification and returns the success response.
        if (mb_strtolower($order->WEBMONEY_orderStatus) === 'paid') {
            $this->eventFillAndSend('webmoneymerchant.info', 'The order is already paid', $request);

            return $this->responseError('The order is already paid');
        }

        // The current order is paid on WebMoney Merchant and not paid in the database.

        $this->eventFillAndSend('webmoneymerchant.success', 'paid order', $request);

        // PaidOrderFilter - update the order into the DB as paid & other actions.
        // If it returns false, then some error has occurred.
        if (! $this->callFilterPaidOrder($request, $order)) {
            $this->eventFillAndSend('webmoneymerchant.error', 'callFilterPaidOrder', $request);

            return $this->responseError('callFilterPaidOrder');
        }

        // The order is paid on WebMoney Merchant and updated in the database.
        return $this->responseOK();
    }
}
