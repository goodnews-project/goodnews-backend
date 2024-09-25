<?php
// storage/languages/en/messages.php

return [
    'follow_error.cant_follow_self' => '自己不能关注自己',
    'follow_error.blocked' => '你已被屏蔽，不能关注此用户',
    'follow_notify_error.follow_first' => '请先关注用户',

    'subscribe_error.closed_subscribe' => '用户该用户未开启订阅',
    'subscribe_error.no_subscribe_plan' => '该用户未开启长期订阅',
    'subscribe_error.pay_failed' => '订阅失败，请查看交易进度',
    'subscribe_error.pay_timeout' => '订阅超时',
    'subscribe_error.unknown_plan' => '未知的订阅计划',
    'subscribe_error.amount_not_match' => '付费金额不一致',
    'subscribe_error.pay_address_not_match' => '付费目标钱包地址不是作者钱包地址',

    'unsubscribe_error.not_found_record' => '未找到订阅记录',

    'status.post_frequency_limit' => '发推频率过快，请稍后再试',

    'dm.input_keyword' => 'Please enter keywords',
    'dm.cant_dm_self' => 'Cannot message yourself',
    'dm.cant_send_to_self' => 'Cannot message yourself',
    'dm.delete_owner_dm_only' => 'You can only delete your own messages',
    'filter.title_cant_empty' => 'The filter title cannot be empty',
    'status.already_voted' => 'Already voted.',
    'record_not_found' => 'Record not found',
    'admin.CSV_file_is_empty' => 'CSV file is empty',
    'admin.import_type_not_match' => 'Select the import type that matches the file',
    'admin.invalid_filename' => 'Invalid filename',
    'admin.ids_miss' => 'Select the ip address you want to delete',
    'admin.ip_format_error' => 'Ip format error',
    'admin.ip_segment_exists' => 'The ip segment already exists',
    'admin.category_not_settings' => 'Category not settings',
    'admin.btn_not_exist' => 'Action button that does not exist',
    'admin.rule_desc_require' => 'The rule states that parameters must be passed',
    'account.login_is_confirmed_email' => 'Please activate your account first',
];
