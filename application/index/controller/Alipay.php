<?php


namespace app\index\controller;



use app\index\model\alipay\OrderInfo;
use buildermodel\AlipayTradePagePayContentBuilder;
use service\AlipayTradeService;
use think\Controller;
use think\Db;
use app\index\model\alipay\SkuCar as skuCarModel;
use app\index\model\alipay\SkuOrder;
use app\index\validate\alipay\Alipay as alipayValidate;
use think\facade\Request;
use think\facade\Session;

class Alipay extends Controller
{
    public function initialize()
    {
        if(!session('customer_name')){
            $this->redirect('/');
        }
    }

    public function config_str(){
            return array (
            //应用ID,您的APPID。
            'app_id' => "2016101900721182",

            //商户私钥
            'merchant_private_key' => "MIIEowIBAAKCAQEAiS/z1ATccHXqFw9roajpJGDnx5AQZxyf56Nd+W63r3Ur/V9GLrYSBrEeRzQjWkKqO+V9GAk0waSekD9bCmwYnQo59gQb7rqkwEmb/1Xt01+7R0+z9em8LhztVjLYaMe+HvsK5ksmpRaPfzzaIEmxSVP8cTFB9GsyKtyZttK5B/zNsW/ZRsDgjen4zirPbE86PDAfUOltBILb0dWbynmHo5wjIs6VY8gpJvXvw5G3DzUOWZBnDvnWVrmNjt35KeXZg/ieEKkNKaH0tiIdiSU0d39N138bRkc5ubI2W9b3hIbfpw40onAAoSovIXyKIDLN4U6hDymYINilIe4Be8MI/QIDAQABAoIBACmNNqL/He2KKW72orkCOitklo9hWTaB+wTj/HCyUjx4luxVUSKQzwDr4KncZuDN1FXz+mGvWCVWwRgbuG19tC7MjCWxtOwn6AK9yNwboL8m/chpoa5YL0EgTdqP5/BEn5cunmyGUpwqKyh0u/SPnX0CTTHTo5Bub3GAA6bWSGjcelIrdZtSGeaywIUNc1zeeJ37qHmL8bSTj6z88HtxbGmHFp9tutEacCp87wNwa8BCxCPMJP7x+nbKGnhXFUr2cmAczpr/tnBjFypKnsnYWDHa0YLelcRVclGDAm7ypMWVk+MCrLjBzYvpLWehfAPUvcK7OiMwXX8AX26jRBfywgECgYEA748d9Sr71drgrXwuU1xnVbi4opTYX3QOG45oucpwwK6MkxIJAnS1KpWWph8bvfJyM83aHZO2ryTiwshL2phlpiy+tVQw71bUqQavGpLRGtPvkOdtjDRNNXi3fpG+xhX1JF+J2vTXGXpo288lUd3wO1rWQtgojZ9cjkXCksXTmz0CgYEAkpo8iXXRgpO28iEH2qsbb0ArB5Cz0b4YGQL1pYJyewjtndu8Y2le3MQ5BEDT+ZFd3f6reCdRX3dRu7Th2gjZtl0/Isy9oxjPudtuC5jWo+o/jeua5pnxrUqvthZt4e79h3qA3yQItrBuaIoLB81ATsxoxw9TxxBobdkrKkPXAMECgYEAx5DuYAOC8FD6wwukfAWKgDr2dVqSNlK0PfiQ/dXLwHio2ww3PTiEhAlCCvn3XnHO+aEPh3w6wAV2ctXxexVh+OFlriGI8pnfZ0AON5D/ad4MwSZKeHZJq7X5BxPbXaGFKtv8N8+oMa1sFVGnwV+mdYvi2qTAg9qyfENZKHRtJ/ECgYAT7c2e7ho+AvCSt7TGoA4JsJJo493d/FZwR/u2tSX03cDXfcB9TxyrLC2IC3wFaCJ3hCAxJD8mmCTPPIabSiq2ZLSpeWWqHzxVyqOKBgvfmn9rPoT/Jhw5b3a1bRUg6okiep+8NbzNgOxxX5qiQ9+jFpyDuuyrmepoTGZWx4QZwQKBgEOB0TefJeJmmaE9AcOE7t/Csl0EQDLEkl5+Ex3SebS1QM9N5dt7WL348QNXQ75yDuofuF4Xp28oJ68sk8YEnw3sQTk9LoGdjDw2QnOfFEwvjValys9DY7hmb6ryzygY9P8FxG1XEgS8Cn1VUPwm4LTpcibbrk39e5Wqxn1PNXXf",

            //异步通知地址
            'notify_url' => "http://101.200.32.25/notify_url.php",

            //同步跳转
            'return_url' => "http://101.200.32.25/judgepay",

            //编码格式
            'charset' => "UTF-8",

            //签名方式
            'sign_type'=>"RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipaydev.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlJ4GNjJDDYR7mDOZwvruu1pq5H0+w/0jiti8+ONyKtHIy4JmT05k6tGsGlI6W5K171UsnREMoPsSwpVs+E4knkr6Tx50crPhIpaOKsTirMKCU/xKJZIaUDYrD2nMkRhd+gU8iBiRilPVrxEoAEFYGDiaVGJijpiMA9G/8hMI1L1hnciSYYmfmuFxrmxXKYb+1WBv4akFbrYFosyiqZDEYuCpOwvarsq2U99qBwISAhVy5bEdyb8G4tJTI7NAJ2Y7EGZuBh6p+JkgEVoRinUhF9HHYT8EUIa5uibcVpYVaMGQBvyItez11oA8qTCx8BKbfW5TFEY/HmS4hLjO6RxLMwIDAQAB",
//                                  MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApFuMQkmYp78lKkxoM8ZIBrf8ouuOQGfG5lYL0ZFwhxRmNFVqs4RTkLJgcNKvM/HwTpvzXWlz4l1MG4l/y06mJT7vcJW86tGNDdGVRQouJa5PpSktSVm07VzF+c3VgY7f6YcD1QuT+OJf6gezT42nd2fnfwBaCAM/zdfWfIlzlW139VAslRXkGCNDSseWZDqHiiVzhslh8NtaDpkTsdFLF4sajuDyIfRgA73t2SUbjKDiR0+7CvcHyDLVK+4MZQhKqSxZ3aTxt9ImFdlr1dTw1AqR/oth8PwY7R8cftrI8++SDuWthZlKGPhDFzfaBf6LE20DX7wT6Lv7oAQOLxqXGQIDAQAB
            );
    }
    public function alipay($order_id=null,$address_id=null){
        $config=Alipay::config_str();
        $validate=new alipayValidate();
        $data=['order_id'=>$order_id];
        if(!$validate->check($data)){
             $this->success('非法订单字符','/');
        }else{
            $orderInfo=new OrderInfo();
            if($order_id==null){
                $out_trade_no = trim($this->request->param('WIDout_trade_no'));
                $total_amount = trim($this->request->param('WIDtotal_amount'));
                $good_sku_id=($_POST['good_sku_id']);
                $sku_num=($_POST['sku_num']);
                $address_id=$_POST['address_id'];
                
                $order_info=[
                    'order_id'=>$out_trade_no,
                    'customer_name'=>session('customer_name'),
                    'address_id'=>$address_id,
                    'order_gmt_created'=>time(),
                    'order_status'=>0
                ];
                try {
                    $order=$orderInfo->addOrder($order_info);
                } catch (\Throwable $th) {
                    $orderInfo->deleteOrderId($out_trade_no);
                    $this->success('订单创建失败','/');
                }
                if($order){
                    $sku_order=array();
                    $skuOrder=new SkuOrder();
                    try {
                        $list=$skuOrder->addsku($sku_order,$out_trade_no,$good_sku_id,$sku_num);
                    } catch (\Throwable $th) {
                        $orderInfo->deleteOrderId($out_trade_no);
                        $this->success('订单创建失败','/');
                    }
                    Alipay::deleteCar($out_trade_no);//创建订单成功后，在购物车中删除订单商品
                }else{
                    $this->success('创建订单失败','/');
                }
            }else{
                if($address_id!=null){//更新地址
                    try {
                        $orderInfo->updateOrder($order_id,$address_id);
                    } catch (\Throwable $th) {
                       $this->success('错误地址编号');
                    } 
                }
                $out_trade_no=$order_id;
                $orderInfo=new OrderInfo();
                $order=$orderInfo->getMoney($out_trade_no);
                if($order==null){
                    $this->success('该订单号不存在或者该订单已经支付','/');
                    return ;
                }else{
                    $money=$order[0]['money'];
                    $total_amount = $money;
                }
            }
            $subject = 'test';
            $body = "123";
    
            $payRequestBuilder = new AlipayTradePagePayContentBuilder();
            $payRequestBuilder->setBody($body);
            $payRequestBuilder->setSubject($subject);
            $payRequestBuilder->setTotalAmount($total_amount);
            $payRequestBuilder->setOutTradeNo($out_trade_no);

            try {
                $aop = new AlipayTradeService($config);
            } catch (\Exception $e) {
                
            }

            /**
             * pagePay 电脑网站支付请求
             * @param $builder 业务参数，使用buildmodel中的对象生成。
             * @param $return_url 同步跳转地址，公网可以访问
             * @param $notify_url 异步通知地址，公网可以访问
             * @return $response 支付宝返回的信息
             */
            $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);
            //输出表单
            var_dump($response);   
        }
    }

    public function judgePay(){
        $config=Alipay::config_str();
        $arr=$_GET;
        $alipaySevice = new AlipayTradeService($config);
        $result = $alipaySevice->check($arr);

        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

            //商户订单号
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);

            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);
            $order_info=Db::name('order_info');
            $list=$order_info->where('order_id',$out_trade_no)->data(['order_status'=>'1',
                'alipay_id'=>$trade_no])->update();


            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $this->redirect('myorder');
        }
        else {
            //验证失败
            $this->success('支付失败,即将到首页','/');
        }

    }

    public function deleteCar($order_id){
        $customer_name=Session::get('customer_name');
        $order=Db::name('SkuOrder');
        $skuOrder=$order->where('order_id',$order_id)->select();
        $skuCar=Db::name('SkuCar');
        foreach ($skuOrder as $skuOrders){
            $where=[
                ['good_sku_id','=',$skuOrders['good_sku_id']],
                ['customer_name','=',$customer_name]
            ];
            $skuNum=$skuCar->removeOption('where')->removeOption('field')->where($where)->field('sku_num')->find();
            $num=$skuNum['sku_num'];
            if($num==$skuOrders['sku_order_num']){
                $skuCar->removeOption('where')->removeOption('field')->where($where)->delete();
            }else{
                $num=$num-$skuOrders['sku_order_num'];
                $data=['sku_num'=>$num];
                $skuCar->removeOption('where')->removeOption('field')->where($where)->data($data)->update();
            }
        }
    }

}