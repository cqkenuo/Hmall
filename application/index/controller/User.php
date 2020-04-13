<?php


namespace app\index\controller;


use sina\SaeTClientV2;
use sina\SaeTOAuthV2;
use think\Controller;
use think\facade\Cookie;
use think\Db;
use think\facade\Session;
use app\index\model\user\Customer as userModel;
use app\index\model\user\Address as addressModel;
use app\index\validate\user\User as userValidate;

use function Sodium\add;

class User extends Controller
{
    public function login()//登陆页面
    {
        $customer_name=Cookie::get('customer_name');
        $customer_psw=Cookie::get('customer_psw');
        if($customer_name&&$customer_psw)
        {
            $customer=[
                ['customer_name','=',$customer_name],
                ['customer_psw','=',$customer_psw]
            ];
            $list= Db::name('customer')->where($customer)->find();
            if($list){
                Session::set('customer_name',$customer_name);
                $this->success('保存密码登陆成功，3秒后进入主页','/');
            }
        }
        $o=new SaeTOAuthV2(WB_AKEY,WB_SKEY);
        $code_url=$o->getAuthorizeURL(WB_CALLBACK_URL);
        $this->assign('code_url',$code_url);
        return $this->fetch('login');
    }

    public function verify()//登陆身份验证
    {
        $list=Db::name('customer');
        $username=$list->where('customer_name',$_POST['username'])->find();
        if($username){
            if($username['customer_status']==0||$username['customer_status']==-1){
                return -1;
            }else{
                $psw=$list->where('customer_psw',md5($_POST['password']))->find();
                if($psw){
                    if(isset($_POST['checked'])){
                        Cookie::set('customer_name',$psw['customer_name'],60*60*24*7);
                        Cookie::set('customer_psw',$psw['customer_psw'],60*60*24*7);
                    }
                    Session::set('customer_name',$psw['customer_name']);
                    get_real_ip($psw['customer_name'],'前台');
                    return 2;
                }else{
                    return 1;
                }
            }

        }else{
            return 0;
        }
    }

    public function callback()//微博oauth
    {
        $o=new SaeTOAuthV2(WB_AKEY,WB_SKEY);
        $keys=array();
        $param=$this->request->param();
        if(isset($param['code'])){
            $keys['code']=$param['code'];
            $keys['redirect_uri']=WB_CALLBACK_URL;
            try {
                $token=$o->getAccessToken('code',$keys);
            }catch (\Exception $e){
                return redirect('/login');
            }
            if($token){
                \session('token',$token);
                $info = new SaeTClientV2(WB_AKEY, WB_SKEY, $token['access_token']);
                $get_uid=$info->get_uid();
                $uid=$get_uid['uid'];
                return $uid;
                $uname=$info->show_user_by_id($uid)['name'];
                $oauth=Db::name('oauth')->where([['third_uid','=',$uid],['third_id','=','1']])->find();
                if($oauth){
                    \session('customer_name',$oauth['customer_name']);
                    $this->success('微博用户 '.$uname.'  授权登陆成功,3秒后进入主页','/');
                }else{
                    $this->success('授权成功，绑定用户名,3秒后进入','/bindname');
                }
            }else{
                $this->success('授权失败，3秒后进入登陆页面','/login');
            }
        }else{
            $this->redirect('/login');
        }
    }

    public function  register()//注册页面
    {
        return $this->fetch('register');
    }
    public function registerAct(){//注册
        $username=trim($_POST['username']);
        $password=$_POST['password'];
        $data=[
            'username'=>$username,
            'password'=>$password
        ];
        $validate=new userValidate();
        if($validate->check($data)){
            return 0;
            $customerlist=Db::name('customer');
            $customer_name=$customerlist->where('customer_name',$username)->find();
            if($customer_name){
                return -1;
            }else{
                $customer=[
                    'customer_name'=> $username,
                    'customer_phone'=>$_POST['phone'],
                    'customer_sex'=>$_POST['sex'],
                    'customer_psw'=>md5($password),
                    'customer_gmt_created'=>time(),
                    'customer_status'=>1,
                    'customer_pic'=>'uploads/index/user/default.jpg'
                ];
                $list=$customerlist->insert($customer);
                if($list){
                    Session::set('customer_name',$username);
                    return 1;
                }else{
                    return 0;
                }
            }
        }else{
            return -2;
        }
        
    }
    public function usernameCheck(){//用户名检查
        $username=$_POST['username'];
        $data=[
            'username'=>$username
        ];
        $validate=new userValidate();
        if($validate->check($data)){
            $customerlist=Db::name('customer');
            $customer_name=$customerlist->where('customer_name',$username)->find();
            if($customer_name){
                return -1;
            }else{
                return 0;
            }
        }else{
            return -2;
        }
    }

    public function bindname()//oauth后绑定用户名
    {
        return $this->fetch('bindname');
    }

    public function  quit()//退出
    {
        Session::delete('customer_name');
        Cookie::delete('customer_name');
        Cookie::delete('customer_psw');
        return redirect('/');
    }

    public function userInfo(){//用户中心页面
        $customer_name=Session::get('customer_name');
        $list=userModel::where('customer_name',$customer_name)->select();
        $address=Db::name('address')->where([['customer_name','=',$customer_name],['is_default','<','2']])->select();

        $this->assign([
            'customer_name'=>$customer_name,
            'customer_sex'=>$list[0]['customer_sex'],
            'customer_phone'=>$list[0]['customer_phone'],
            'customer_email'=>$list[0]['customer_email'],
            'customer_pic'=>$list[0]['customer_pic'],
            'customer_gmt_created'=>$list[0]['customer_gmt_created'],
            'address'=>$address
        ]);


        return $this->fetch('userInfo');

    }
    public function saveAddress(){
        $address=new addressModel();
        $customer_name=Session::get('customer_name');
        if(isset($_POST['is_default'])){
            $is_default=1;
        }else{
            $is_default=0;
        }
        $address->saveAddress($customer_name,$_POST['address_name'],$_POST['address_phone'],$_POST['province'],$_POST['city'],$_POST['area'],$_POST['address_detail'],$is_default,null);
    }

    public function updateAddress(){//更新用户地址
        $address_id=$_POST['address_id'];
        $address=Db::name('address');
        $customer_name=Session::get('customer_name');
        $address->where([['customer_name','=',$customer_name],['is_default','=','1']])->data('is_default',0)->update();
        return $address->removeOption('where')->where([['address_id','=',$address_id],['customer_name','=',$customer_name]])->data('is_default',1)->update();
    }

    public function deleteAddress(){//删除用户地址
        $address_id=$_POST['address_id'];
        $customer_name=Session::get('customer_name');
        $address= Db::name('address');
        $list=Db::name('order_info')->where([['customer_name','=',$customer_name],['address_id','=',$address_id]])->find();
        if($list){
            return  $address->where([['address_id','=',$address_id],['customer_name','=',$customer_name]])->data('is_default',2)->update();
        }else{
            return $address->where([['address_id','=',$address_id],['customer_name','=',$customer_name]])->delete();
        }
    }

    public function province()
    {
        return province();
    }

    public function city($provinceid)
    {
        return city($provinceid);
    }

    public function area($cityid)
    {
        return area($cityid);
    }
}