<?php

namespace Payeer;

use Exception;

class PayeerTradeApi
{
    
    private $arError = [];
    private string $key;
    private string $apiId;

    public function __construct(string $apiId, string $key)
    {
        $this->apiId = $apiId;
        $this->key = $key;
    }

    private function request($req = [])
    {
        $msec = round(microtime(true) * 1000);
        $req['post']['ts'] = $msec;

        $post = json_encode($req['post']);

        $sign = hash_hmac('sha256', $req['method'] . $post, $this->key);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payeer.com/api/trade/" . $req['method']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "API-ID: " . $this->apiId,
                "API-SIGN: " . $sign
            ]
        );

        $response = curl_exec($ch);
        curl_close($ch);

        $arResponse = json_decode($response, true);

        if ($arResponse['success'] !== true) {
            $this->arError = $arResponse['error'];
            throw new Exception($arResponse['error']['code']);
        }

        return $arResponse;
    }

    public function getError()
    {
        return $this->arError;
    }

    /**
     * Get limits and available pairs
     * 
     * @param string $pair 
     * @return mixed 
     * @throws Exception 
     */
    public function info(string $pair = null)
    {
        $request = [
            'method' => 'info',
        ];
        if (!empty($pair)) {
            $request['post'] = [
                'pair' => $pair,
            ];
        }
        $res = $this->request($request);

        return $res;
    }

    /**
     * Price statistics for latest 24 hours
     * 
     * @param string $pair 
     * @return mixed 
     * @throws Exception 
     */
    public function ticker(string $pair = null)
    {
        $request = [
            'method' => 'ticker',
        ];
        if (!empty($pair)) {
            $request['post'] = [
                'pair' => $pair,
            ];
        }
        $res = $this->request($request);

        return $res['pairs'];
    }


    /**
     * Get available orders for selected pair(s)
     * 
     * @param string $pair 
     * @return mixed 
     * @throws Exception 
     */
    public function orders(string $pair)
    {
        $res = $this->request([
            'method' => 'orders',
            'post' => [
                'pair' => $pair,
            ],
        ]);

        return $res['pairs'];
    }

    /**
     * Get all trades for current pair
     * 
     * @param string $pair 
     * @return mixed 
     * @throws Exception 
     */
    public function trades(string $pair)
    {
        $res = $this->request([
            'method' => 'trades',
            'post' => [
                'pair' => $pair,
            ],
        ]);

        return $res['pairs'];
    }


    /**
     * Get balance of wallets
     * 
     * @return mixed 
     * @throws Exception 
     */
    public function account()
    {
        $res = $this->request([
            'method' => 'account',
        ]);

        return $res['balances'];
    }


    /**
     * Order request
     * 
     * @param array $req 
     * @return mixed 
     * @throws Exception 
     */
    private function orderCreate($req = [])
    {
        $res = $this->request([
            'method' => 'order_create',
            'post' => $req,
        ]);

        return $res;
    }


    /**
     * Get order status by id
     * 
     * @param int $id 
     * @return mixed 
     * @throws Exception 
     */
    public function orderStatus(int $id)
    {
        $res = $this->request([
            'method' => 'order_status',
            'post' => [
                'order_id' => $id
            ],
        ]);

        return $res['order'];
    }

    /**
     * Get current time in milliseconds timestamp
     * 
     * @return int 
     * @throws Exception 
     */
    public function time()
    {
        $res = $this->request([
            'method' => 'time',
        ]);
        return $res['time'];
    }

    /**
     * Make limit order
     * 
     * @param string $pair 
     * @param string $action 
     * @param float $amount 
     * @param float $price 
     * @return array 
     * @throws Exception 
     */
    public function limitOrder(string $pair, string $action, float $amount, float $price)
    {
        if (!in_array($action, ['sell', 'buy'])) {
            throw new Exception('Action may be only sell or buy');
        }
        if ($amount <= 0) {
            throw new Exception('Amount cannot be less or equal zero');
        }
        if ($price <= 0) {
            throw new Exception('Price cannot be less or equal zero');
        }
        return $this->orderCreate([
            'type' => 'limit',
            'pair' => $pair,
            'action' => $action,
            'amount' => $amount,
            'price' => $price,
        ]);
    }

    /**
     * Make market order
     * 
     * @param string $pair 
     * @param string $action 
     * @param float $amount 
     * @param float $value 
     * @return array 
     * @throws Exception 
     */
    public function marketOrder(string $pair, string $action, float $amount = 0, float $value = 0)
    {
        if (!in_array($action, ['sell', 'buy'])) {
            throw new Exception('Action may be only sell or buy');
        }
        if ($amount <= 0 && $value <= 0) {
            throw new Exception('Amount and value cannot be less or equal zero simultaneously');
        }
        if ($amount > 0 && $value > 0) {
            throw new Exception('Please use only one of: Amount or Value. Another must be equal zero');
        }
        $req = [
            'type' => 'market',
            'pair' => $pair,
            'action' => $action,
        ];
        if ($value > 0) {
            $req['value'] = $value;
        }
        if ($amount > 0) {
            $req['amount'] = $amount;
        }
        return $this->orderCreate($req);
    }

    /**
     * Make stop limit order
     * 
     * @param string $pair 
     * @param string $action 
     * @param float $amount 
     * @param float $price 
     * @param float $stopPrice 
     * @return array 
     * @throws Exception 
     */
    public function stopLimitOrder(string $pair, string $action, float $amount, float $price, float $stopPrice)
    {
        if (!in_array($action, ['sell', 'buy'])) {
            throw new Exception('Action may be only sell or buy');
        }
        if ($amount <= 0) {
            throw new Exception('Amount cannot be less or equal zero');
        }
        if ($price <= 0) {
            throw new Exception('Price cannot be less or equal zero');
        }
        if ($stopPrice <= 0) {
            throw new Exception('Stop price cannot be less or equal zero');
        }
        return $this->orderCreate([
            'type' => 'stop_limit',
            'pair' => $pair,
            'action' => $action,
            'amount' => $amount,
            'price' => $price,
            'stop_price' => $stopPrice,
        ]);
    }

    /**
     * Cancel order by id
     * 
     * @param int $id 
     * @return mixed 
     * @throws Exception 
     */
    public function cancelOrder(int $id)
    {
        $res = $this->request([
            'method' => 'order_cancel',
            'post' => [
                'order_id' => $id
            ],
        ]);

        return $res['success'];
    }

    /**
     * Cancel multiple orders
     * 
     * @param string $pair 
     * @param string $action 
     * @return mixed 
     * @throws Exception 
     */
    public function cancelOrders(string $pair = null, string $action = null)
    {
        if (!empty($action) && !in_array($action, ['sell', 'buy'])) {
            throw new Exception('Action may be only sell or buy');
        }
        $req = [];
        if (!empty($pair)) $req['pair'] = $pair;
        if (!empty($action)) $req['action'] = $action;
        $res = $this->request([
            'method' => 'orders_cancel',
            'post' => $req,
        ]);

        return $res['items'];
    }

    /**
     * Get my orders
     * 
     * @param string $pair 
     * @param string $action 
     * @return mixed 
     * @throws Exception 
     */
    public function myOrders(string $pair = null, string $action = null)
    {
        if (!empty($action) && !in_array($action, ['sell', 'buy'])) {
            throw new Exception('Action may be only sell or buy');
        }
        $req = [];
        if (!empty($pair)) $req['pair'] = $pair;
        if (!empty($action)) $req['action'] = $action;
        $res = $this->request([
            'method' => 'my_orders',
            'post' => $req,
        ]);

        return $res['items'];
    }

}
