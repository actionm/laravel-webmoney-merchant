<style type="text/css">
    form.webmoney-merchant-form .btn_order {
        background-color: #FFF;
        border: solid #CCC 1px;
        width: 450px;
        text-align: left;
        padding-left: 20px;
        margin-right: 20px;
        height: 74px;
    }
</style>

<div class="well">

    @if (config('webmoney-merchant.payment_forms')['cards'])
        <div class="form-group ">
            <form action="https://merchant.webmoney.ru/lmi/payment.asp?at=authtype_16&lang={{ $payment_fields['LOCALE'] }}" method="POST" class="form webmoney-merchant-form">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" value="{{ $payment_fields['LMI_PAYMENT_AMOUNT'] }}">
                <input type="hidden" name="LMI_PAYMENT_DESC_BASE64" value="{{ $payment_fields['LMI_PAYMENT_DESC_BASE64'] }}">
                <input type="hidden" name="LMI_PAYEE_PURSE" value="{{ $payment_fields['LMI_PAYEE_PURSE'] }}">
                <input type="hidden" name="LMI_PAYMENT_NO" value="{{ $payment_fields['LMI_PAYMENT_NO'] }}">
                <input type="hidden" name="LMI_PAYMENTFORM_SIGN" value="{{ $payment_fields['LMI_PAYMENTFORM_SIGN'] }}">
                <input type="hidden" name="LMI_SDP_TYPE" value="4">
                <button type="submit" class="btn btn_order">Оплатить картой российского банка</button>
            </form>
        </div>
    @endif

    @if (config('webmoney-merchant.payment_forms')['sberbank'])
        <div class="form-group pull-left">
            <form action="https://merchant.webmoney.ru/lmi/payment.asp?at=authtype_21&lang={{ $payment_fields['LOCALE'] }}" method="POST" class="form">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" value="{{ $payment_fields['LMI_PAYMENT_AMOUNT'] }}">
                <input type="hidden" name="LMI_PAYMENT_DESC_BASE64" value="{{ $payment_fields['LMI_PAYMENT_DESC_BASE64'] }}">
                <input type="hidden" name="LMI_PAYEE_PURSE" value="{{ $payment_fields['LMI_PAYEE_PURSE'] }}">
                <input type="hidden" name="LMI_PAYMENT_NO" value="{{ $payment_fields['LMI_PAYMENT_NO'] }}">
                <input type="hidden" name="LMI_PAYMENTFORM_SIGN" value="{{ $payment_fields['LMI_PAYMENTFORM_SIGN'] }}">
                <input type="hidden" name="LMI_SDP_TYPE" value="14">
                <button type="submit" class="btn btn_order">Сбербанк онлайн</button>
            </form>
        </div>
    @endif

    @if (config('webmoney-merchant.payment_forms')['online_banking'])
        <div class="form-group">
            <form action="https://merchant.webmoney.ru/lmi/payment.asp?at=authtype_18&lang={{ $payment_fields['LOCALE'] }}" method="POST" class="form">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" value="{{ $payment_fields['LMI_PAYMENT_AMOUNT'] }}">
                <input type="hidden" name="LMI_PAYMENT_DESC_BASE64" value="{{ $payment_fields['LMI_PAYMENT_DESC_BASE64'] }}">
                <input type="hidden" name="LMI_PAYEE_PURSE" value="{{ $payment_fields['LMI_PAYEE_PURSE'] }}">
                <input type="hidden" name="LMI_PAYMENT_NO" value="{{ $payment_fields['LMI_PAYMENT_NO'] }}">
                <input type="hidden" name="LMI_PAYMENTFORM_SIGN" value="{{ $payment_fields['LMI_PAYMENTFORM_SIGN'] }}">
                <button type="submit" class="btn btn_order">Интернет банкинги</button>
            </form>
        </div>
    @endif

    @if (config('webmoney-merchant.payment_forms')['mobile'])
        <div class="form-group">
            <form action="https://merchant.webmoney.ru/lmi/payment.asp?at=authtype_19&lang={{ $payment_fields['LOCALE'] }}" method="POST" class="form">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" value="{{ $payment_fields['LMI_PAYMENT_AMOUNT'] }}">
                <input type="hidden" name="LMI_PAYMENT_DESC_BASE64" value="{{ $payment_fields['LMI_PAYMENT_DESC_BASE64'] }}">
                <input type="hidden" name="LMI_PAYEE_PURSE" value="{{ $payment_fields['LMI_PAYEE_PURSE'] }}">
                <input type="hidden" name="LMI_PAYMENT_NO" value="{{ $payment_fields['LMI_PAYMENT_NO'] }}">
                <input type="hidden" name="LMI_PAYMENTFORM_SIGN" value="{{ $payment_fields['LMI_PAYMENTFORM_SIGN'] }}">
                <button type="submit" class="btn btn_order">Со счёта мобильного телефона</button>
            </form>
        </div>
    @endif

    @if (config('webmoney-merchant.payment_forms')['webmoney'])
        <div class="form-group">
            <form action="https://merchant.webmoney.ru/lmi/payment.asp?lang={{ $payment_fields['LOCALE'] }}" method="POST" class="form">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" value="{{ $payment_fields['LMI_PAYMENT_AMOUNT'] }}">
                <input type="hidden" name="LMI_PAYMENT_DESC_BASE64" value="{{ $payment_fields['LMI_PAYMENT_DESC_BASE64'] }}">
                <input type="hidden" name="LMI_PAYEE_PURSE" value="{{ $payment_fields['LMI_PAYEE_PURSE'] }}">
                <input type="hidden" name="LMI_PAYMENT_NO" value="{{ $payment_fields['LMI_PAYMENT_NO'] }}">
                <input type="hidden" name="LMI_PAYMENTFORM_SIGN" value="{{ $payment_fields['LMI_PAYMENTFORM_SIGN'] }}">
                <button type="submit" class="btn btn_order">Оплатить c помощью WebMoney</button>
            </form>
        </div>
    @endif

</div>
