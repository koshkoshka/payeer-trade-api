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

    public function info($pair = null)
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

    public function ticker($pair = null)
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

        return $res;
    }


    public function orders($pair = 'BTC_USDT')
    {
        $res = $this->request([
            'method' => 'orders',
            'post' => [
                'pair' => $pair,
            ],
        ]);

        return $res['pairs'];
    }

    public function trades($pair = 'BTC_USDT')
    {
        $res = $this->request([
            'method' => 'orders',
            'post' => [
                'pair' => $pair,
            ],
        ]);

        return $res['pairs'];
    }


    public function account()
    {
        $res = $this->request([
            'method' => 'account',
        ]);

        return $res['balances'];
    }


    private function orderCreate($req = [])
    {
        $res = $this->request([
            'method' => 'order_create',
            'post' => $req,
        ]);

        return $res;
    }


    public function orderStatus($req = [])
    {
        $res = $this->request([
            'method' => 'order_status',
            'post' => $req,
        ]);

        return $res['order'];
    }


    public function myOrders($req = [])
    {
        $res = $this->request([
            'method' => 'my_orders',
            'post' => $req,
        ]);

        return $res['items'];
    }

    public function time()
    {
        $res = $this->request([
            'method' => 'time',
        ]);
        return $res['time'];
    }

    /**
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

}
