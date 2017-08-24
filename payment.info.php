<?php

return array(
    // 对应中文可能保存在   language/sc-tuf-8/payment/yinlian.lang.php中
    'code'      => 'yinlian',
    'name'      => '银联支付实验',
    'desc'      => '银联便民支付网上平台旨在利用互联网科技所具有的便利性，为广大用户提供自助方式的网上公用事业缴费、通讯类预付费缴费、通讯类后付费缴费及信用卡还款等综合服务。',
    'is_online' => '1',
    'author'    => 'Xu Yang hsvyang@gmail.com ',
    'website'   => 'https://github.com/YHMSword',
    'version'   => '1.0',
    'currency'  => Lang::get('yinlian_currency'),
    'config'    => array(
        'yinlian_memname'   => array(        //账号
            'text'  => '商户名称',
            'desc'  => '输入商户名称',
            'type'  => 'text',
        ),
        'yinlian_memid'       => array(        //商户id
            'text'  => '商户id',
            'desc'  => '输入商户的id',
            'type'  => 'text',
        ),
        'yinlian_memkey'   => array(        //证书
            'text'  => '商户秘钥证书',
            'type'  => 'file',
        ),
    ),
	
);

?>