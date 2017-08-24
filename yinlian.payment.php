<?php
//引用sdk/acp_service.php 如不可以，采用绝对路径
include_once "./sdk/acp_service.php";

/*    银联支付插件
 *
 *    @author    hsvyang@gmail.com
	  @addtime  2017年8月24日
 */

class YinlianPayment extends BasePayment
{
    /* 银联前台请求地址 */
    var $_gateway   =   'https://gateway.95516.com/gateway/api/frontTransReq.do';
    var $_code      =   'yinlian';

    /**
     *    获取支付表单
     *
     *    @author    hsvyang@gmail.com
     *    @param     array $order_info  待支付的订单信息，必须包含总费用及唯一外部交易号
     */
    function get_payform($order_info)
    {
		$upop_evn		=2;		// 环境
		// 包含库接口文件
		$param=array();
		if (function_exists("date_default_timezone_set")) {
			date_default_timezone_set('Asia/Shanghai');
        }       
        // 基本信息，以下域必送
        $param['version']             = '5.1.0';  // 版本号
        $param['encoding']             = 'UTF-8';  // 编码方式

        // acpsdk.signCert.pwd   签名证书密码 默认000000，signCertPath  测试环境证书 路径，
        //  acpsdk.signCert.path=D:/certs/acp_test_sign.pfx
        // acpsdk.signCert.pwd=000000

        //证书ID，certId，填写签名私钥证书的 Serial Number，该值可通过银联提供的 SDK 获取 
        // $param['certId']             = 'UTF-8';  
        // 签名方法，signMethod，固定填写：01（表示采用 RSA 签名）
        $param['signMethod']             = '01';  
        // 签名，signature，填写对报文摘要的签名，可通过SDK生成签名
        $param['txnType']             = '01';  // 交易类型，固定填写
        $param['txnSubType']             = '01';  // 交易子类，固定填写
        $param['bizType']             = '000201';  // 产品类型，固定填写
        $param['channelType']             = '07';  // 渠道类型，互联网，固定填写
        // 商户信息
        $param['accessType']             = '0';  // 接入类型
        $param['frontUrl']           = $this->_create_return_url($order_info['order_id']);   // 前台回调URL
        $param['backUrl']            = $this->_create_notify_url($order_info['order_id']);    // 后台回调URL
        // 订单信息
        $param['orderId']           = $this->_get_trade_sn($order_info);  //商户订单号
        $param['currencyCode']         = 156;  //交易币种，CURRENCY_CNY=>人民币
        $param['txnAmt']           = (string)($order_info['order_amount'] * 100);  // 交易金额 
		$param['txnTime']        = date('YmdHis',$order_info['add_time']); //订单发送时间
	   	//机构信息 
        // 商户名称
        // $param['merName']       = $this->_config['yinlian_memname'];
        // 商户id
        $param['merId']     = $this->_config['yinlian_memid'];
        // 获取签名证书等相关
        AcpService::sign ( $param );
        $uri =   SDKConfig::getSDKConfig()->frontTransUrl;
        $html_form =  AcpService::createAutoFormHtml( $param, $uri );
        echo $html_form;  
    }

    /**
     *    返回通知结果
     *
     *    @author    hsvyang@gmail.com
     *    @param     array $order_info
     *    @param     bool  $strict
     *    @return    array
     */
    function verify_notify($order_info, $strict = false)
    {
		$logger = LogUtil::getLogger();
        $logger->LogInfo("receive front notify: " . createLinkString ( $_POST, false, true ));

        if (isset ( $_POST ['signature'] )) {
                // echo AcpService::validate ( $_POST ) ? '验签成功' : '验签失败';
                $orderId = $_POST ['orderId']; //其他字段也可用类似方式获取
                $respCode = $_POST ['respCode'];
            }


		// var_dump($_POST);exit;
		// print_r($_POST); 银联返回以post方式返回 一下几点验证可以新增

		if (($_POST['respCode']) != '00') {
			 $this->_error('支付失败');
			 return false;
		}
		$arr_ret = $_POST;
		$order_amount=$arr_ret['txnAmt'] / 100;
		$sp_billno=$arr_ret['orderId'];
        if ($order_info['order_amount'] != $order_amount)
        {
            /* 支付的金额与实际金额不一致 */
            $this->_error('price_inconsistent');

            return false;
        }
        if ($order_info['out_trade_sn'] != $sp_billno)
        {
            /* 通知中的订单与欲改变的订单不一致 */
            $this->_error('order_inconsistent');

            return false;
        }
		
		
		//检查商户号是否一致
		if ($_POST['merId'] != $this->_config['yinlian_memid'])
			{
				return false;
			}
		
        return array(
            'target'    => ORDER_ACCEPTED,
        );
        
    }

  

	
	static  function sign($params, $sign_method,$pp)
    {
        if (strtolower($sign_method) == "md5") {
            ksort($params);
			
            $sign_str = "";
			$sign_ignore_params = array("bank","signMethod");
            foreach ($params as $key => $val) {
               if (in_array($key, $sign_ignore_params)) {
                    continue;
                }
                $sign_str .= sprintf("%s=%s&", $key, $val);
				
            }
			
		
            return md5($sign_str . md5($pp));
        }
        /* TODO: elseif (strtolower($sign_method) == "rsa")  */
        else {
			
            throw new Exception("Unknown sign_method set in quickpay_conf");
        }
    }
	
	function verify_result($result) 
    {
        if ($result)
        {
            $url = $this->_create_return_url($_GET['order_id']);
            $back_url = $url . '&cmdno=' . $_GET['cmdno'] . '&pay_result=' . $_GET['pay_result'] . '&pay_info=' . $_GET['pay_info'].
                '&date=' . $_GET['date'] . '&bargainor_id=' . $_GET['bargainor_id'] .'&transaction_id=' . $_GET['transaction_id'].
                '&sp_billno=' . $_GET['sp_billno'] . '&total_fee=' . $_GET['total_fee'] . '&fee_type=' . $_GET['fee_type'] . '&attach=' . $_GET['attach'] . '&sign=' . $_GET['sign'];
            echo "<meta name='TENCENT_ONLINE_PAYMENT' content='China TENCENT'><html><script language=javascript>window.location.href='". $back_url ."';</script></html>";
        }
    }
}

?>