<?php

final class RY_WEI_Cron
{
    public static function add_action()
    {
        add_action(RY_WEI::OPTION_PREFIX . 'check_expire', [__CLASS__, 'check_expire']);
    }

    public static function check_expire()
    {
        RY_WEI_License::instance()->check_expire();
    }
}
