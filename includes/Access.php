<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Access
{
    public static function canAccessFds(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();

        if (in_array('administrator', (array) $user->roles, true)) {
            return true;
        }

        if (!in_array(FEVDI_FDS_ROLE, (array) $user->roles, true)) {
            return false;
        }

        return get_user_meta($user->ID, '_fds_status', true) === 'approved';
    }
}