<?php

namespace ActionM\WebMoneyMerchant\Test\Dummy;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'WEBMONEY_orderSum',
        'WEBMONEY_orderStatus',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public static function SearchOrderFilterFails(Request $request, $order_id)
    {
        return false;
    }

    public static function SearchOrderFilterPaidforPayOrderFromGate(Request $request, $order_id, $orderStatus = 'paid', $orderSum = '1')
    {
        $order = new self([
            'WEBMONEY_orderSum' =>  $orderSum,
            'WEBMONEY_orderStatus' => $orderStatus,
        ]);

        return $order;
    }

    public static function SearchOrderFilterPaid(Request $request, $order_id, $orderStatus = 'paid', $orderSum = '12345')
    {
        $order = new self([
            'WEBMONEY_orderSum' =>  $orderSum,
            'WEBMONEY_orderStatus' => $orderStatus,
        ]);

        return $order;
    }

    public static function SearchOrderFilterNotPaid(Request $request, $order_id, $orderStatus = 'not_paid', $orderSum = '1')
    {
        $order = new self([
            'WEBMONEY_orderSum' =>  $orderSum,
            'WEBMONEY_orderStatus' => $orderStatus,
        ]);

        return $order;
    }

    public static function PaidOrderFilterFails(Request $request, $order)
    {
        return false;
    }

    public static function PaidOrderFilter(Request $request, $order)
    {
        return true;
    }
}
