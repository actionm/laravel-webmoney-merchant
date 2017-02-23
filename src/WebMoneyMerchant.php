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
     * Allow access, if the ip address is in the whitelist.
     * @param $ip
     * @return bool
     */
    public function allowIP($ip)
    {
        // Allow local ip or any ip address
        if ($ip == '127.0.0.1' || in_array('*', config('webmoney-merchant.allowed_ips'))) {
            return true;
        }

        return in_array($ip, config('webmoney-merchant.allowed_ips'));
    }

    /**
     * Return 403 error code.
     * @param $message
     * @return mixed
     */
    public function responseError($message)
    {
        return abort(403, $message);
    }

    /**
     * Return YES success message.
     * @return mixed
     */
    public function responseOK()
    {
        return 'YES';
    }

    /**
     * Fill event details to pass the title and request params as array.
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
     * Calculate signature for the order form.
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
     * Return hash for params from WebMoneyMerchant gate.
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
     * Generate WebMoney order array with required array for order form.
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
     * Check required order params for order form and raise an exception if fails.
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

        // check if PAYMENT_NO is numeric
        if (! is_numeric($order['PAYMENT_NO'])) {
            throw InvalidConfiguration::generatePaymentFormOrderInvalidPaymentNo('PAYMENT_NO');
        }

        // check if PAYMENT_NO > 0 and < 2147483647
        if (intval($order['PAYMENT_NO']) < 1 || intval($order['PAYMENT_NO']) > 2147483647) {
            throw InvalidConfiguration::generatePaymentFormOrderInvalidPaymentNo($order['PAYMENT_NO']);
        }
    }

    /**
     * Generate html forms from view with payment buttons
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

        /* WM Merchant Accept windows-1251, use only latin characters for the product name*/
        $payment_fields['LMI_PAYMENT_DESC_BASE64'] = $order['ITEM_NAME'];

        $payment_fields['LMI_PAYEE_PURSE'] = config('webmoney-merchant.WM_LMI_PAYEE_PURSE');
        $payment_fields['LMI_PAYMENT_AMOUNT'] = $order['PAYMENT_AMOUNT'];
        $payment_fields['LMI_PAYMENT_NO'] = $order['PAYMENT_NO'];
        $payment_fields['LMI_PAYMENTFORM_SIGN'] = $this->getFormSignature($payment_fields['LMI_PAYMENT_AMOUNT'], $payment_fields['LMI_PAYMENT_NO']);

        $payment_fields['LOCALE'] = config('webmoney-merchant.locale');

        return view('webmoney-merchant::payment_form', compact('payment_fields'));
    }

    /**
     * Validate request params from WebMoneyMerchant gate.
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
     * Validate the payee purse from WebMoneyMerchant gate.
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
     * Validate request signature from WebMoneyMerchant gate.
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
     * Validate ip, request params and signature from WebMoneyMerchant gate.
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
     * Validate the required attributes of the found order.
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

        // check required found order attributes
        $attr = ['WEBMONEY_orderStatus', 'WEBMONEY_orderSum'];

        foreach ($attr as $k => $value) {
            if (! $order->getAttribute($value)) {
                $this->eventFillAndSend('webmoneymerchant.error', $value.'Invalid', $request);

                return false;
            }
        }

        // compare order attributes vs request params
        if ($order->getAttribute('WEBMONEY_orderSum') != $request->input('LMI_PAYMENT_AMOUNT')) {
            $this->eventFillAndSend('webmoneymerchant.error', $value.'Invalid', $request);

            return false;
        }

        return true;
    }

    /**
     * Call SearchOrderFilter and check return order params.
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
         *  Search order in the database and return order details
         *  Must return array with:
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
     * Call PaidOrderFilter if order not paid.
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

        // unset the custom order attributes for Eloquent support
        unset($order['WEBMONEY_orderSum'], $order['WEBMONEY_orderStatus']);

        // Run PaidOrderFilter callback
        return $callable($request, $order);
    }

    /**
     * Run WebMoneyMerchant::payOrderFromGate($request) when receive request from WebMoney Merchant gate.
     * @param Request $request
     * @return bool
     */
    public function payOrderFromGate(Request $request)
    {
        if (! $request->has('LMI_HASH')) {
            return $this->responseError('LMI_HASH not set');
        }

        if ($request->has('LMI_PREREQUEST')) {
            return 'YES';
        }

        // Validate request params from WebMoney Merchant server.
        if (! $this->validateOrderRequestFromGate($request)) {
            return $this->responseError('validateOrderRequestFromGate');
        }

        // Search and return order
        $order = $this->callFilterSearchOrder($request);

        if (! $order) {
            return $this->responseError('searchOrderFilter');
        }

        // If method pay and current order status is paid
        // return success response and notify info
        if (mb_strtolower($order->WEBMONEY_orderStatus) === 'paid') {
            $this->eventFillAndSend('webmoneymerchant.info', 'The order is already paid', $request);

            return $this->responseError('The order is already paid');
        }

        // Current order is paid in WebMoney Merchant and not paid in database

        $this->eventFillAndSend('webmoneymerchant.success', 'paid order', $request);

        // PaidOrderFilter - update order into DB as paid & other actions
        // if return false then error
        if (! $this->callFilterPaidOrder($request, $order)) {
            $this->eventFillAndSend('webmoneymerchant.error', 'callFilterPaidOrder', $request);

            return $this->responseError('callFilterPaidOrder');
        }

        // Order is paid in WebMoney Merchant and updated in database
        return $this->responseOK();
    }
}
