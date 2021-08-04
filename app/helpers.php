<?php
/**
 * @author: junshenghuang
 * @datetime: 2021/8/4 5:41 下午
 */

function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}
