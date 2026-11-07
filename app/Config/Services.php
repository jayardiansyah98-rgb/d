<?php

namespace Config;

use App\Libraries\AuditLogger;
use App\Libraries\AuthService;
use App\Libraries\DistribusiService;
use App\Libraries\DonasiService;
use App\Libraries\DonaturService;
use App\Libraries\RoleService;
use App\Libraries\UserService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 */
class Services extends BaseService
{
    public static function auth($getShared = true): AuthService
    {
        if ($getShared) {
            return static::getSharedInstance('auth');
        }

        return new AuthService();
    }

    public static function auditLogger($getShared = true): AuditLogger
    {
        if ($getShared) {
            return static::getSharedInstance('auditLogger');
        }

        return new AuditLogger();
    }

    public static function userService($getShared = true): UserService
    {
        if ($getShared) {
            return static::getSharedInstance('userService');
        }

        return new UserService();
    }

    public static function roleService($getShared = true): RoleService
    {
        if ($getShared) {
            return static::getSharedInstance('roleService');
        }

        return new RoleService();
    }

    public static function donaturService($getShared = true): DonaturService
    {
        if ($getShared) {
            return static::getSharedInstance('donaturService');
        }

        return new DonaturService();
    }

    public static function donasiService($getShared = true): DonasiService
    {
        if ($getShared) {
            return static::getSharedInstance('donasiService');
        }

        return new DonasiService();
    }

    public static function distribusiService($getShared = true): DistribusiService
    {
        if ($getShared) {
            return static::getSharedInstance('distribusiService');
        }

        return new DistribusiService();
    }

    public static function laporanService($getShared = true): \App\Libraries\LaporanService
    {
        if ($getShared) {
            return static::getSharedInstance('laporanService');
        }

        return new \App\Libraries\LaporanService();
    }
}
