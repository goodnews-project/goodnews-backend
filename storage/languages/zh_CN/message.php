<?php
// storage/languages/en/messages.php

return [
    'account' => [
        'follow_error.cant_follow_self' => '自己不能关注自己',
        'follow_error.blocked' => '你已被屏蔽，不能关注此用户',
        'follow_notify_error.follow_first' => '请先关注用户',

        'subscribe_error.no_subscribe' => '用户该用户未开启订阅',
        'subscribe_error.no_subscribe_plan' => '该用户未开启长期订阅',
        'subscribe_error.pay_failed' => '订阅失败，请查看交易进度',
        'subscribe_error.pay_timeout' => '订阅超时',
        'subscribe_error.unknown_plan' => '未知的订阅计划',
        'subscribe_error.plan_id_not_match' => '付费计划ID不一致',
        'subscribe_error.pay_amount_not_match' => '付费金额不一致',
        'subscribe_error.pay_address_not_match' => '付费目标钱包地址不是作者钱包地址',

        'unsubscribe_error.not_found_record' => '未找到订阅记录',


        'change_password_error.old_password_error'=>'旧密码错误'
    ],
    'dm.input_keyword' => '请输入关键词',
    'dm.cant_dm_self' => '不能私信自己',
    'dm.cant_send_to_self' => '不能私信自己',
    'dm.delete_owner_dm_only' => '只能删自己的消息',
    'filter.title_cant_empty' => '过滤器标题不能为空',
    'status.already_voted' => '已投票',
    'record_not_found' => '记录未找到',
    'pay_log.author_is_not_exists' => '打赏作者不存在',
    'pay_log.status_is_not_exists' => '打赏推文不存在',
    'pay_log.plan_id_is_empty' => '计划ID为空',
    'pay_log.reward_type_miss' => 'reward type 缺失',

    'admin.CSV_file_is_empty' => 'CSV 文件为空',
    'admin.import_type_not_match' => '请选择与文件匹配的导入类型',
    'admin.invalid_filename' => '无效的文件名',
    'admin.ids_miss' => '请选择要删除的ip',
    'admin.ip_format_error' => 'ip格式错误',
    'admin.ip_segment_exists' => 'ip段已经存在',
    'admin.category_not_settings' => '请选择分类设置',
    'admin.status_ids_miss' => '请选择要删除的推文',
    'admin.btn_not_exist' => '不存在的操作按钮',
    'admin.rule_desc_require' => '规则说明必须',
    'account.login_is_confirmed_email' => '请先激活账户',

];
