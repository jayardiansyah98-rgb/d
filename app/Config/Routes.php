<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);

$routes->get('/', 'Home::index', ['as' => 'home']);

$routes->group('', ['filter' => 'guest'], static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::login', ['as' => 'login']);
    $routes->post('login', 'AuthController::attemptLogin', ['as' => 'login.attempt']);
});

$routes->post('logout', 'AuthController::logout', [
    'as'     => 'logout',
    'filter' => 'auth',
]);

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::index', [
        'as'     => 'dashboard',
        'filter' => 'permission:dashboard.view',
    ]);

    $routes->get('profile', 'ProfileController::edit', ['as' => 'profile.edit']);
    $routes->post('profile', 'ProfileController::update', ['as' => 'profile.update']);
    $routes->post('profile/password', 'ProfileController::updatePassword', ['as' => 'profile.password']);

    $routes->group('donatur', ['filter' => 'permission:donors.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'DonaturController::index', ['as' => 'donatur.index']);
        $routes->get('new', 'DonaturController::create', [
            'as'     => 'donatur.create',
            'filter' => 'permission:donors.manage',
        ]);
        $routes->post('', 'DonaturController::store', [
            'as'     => 'donatur.store',
            'filter' => 'permission:donors.manage',
        ]);
        $routes->get('(:num)', 'DonaturController::show/$1', ['as' => 'donatur.show']);
        $routes->get('(:num)/edit', 'DonaturController::edit/$1', [
            'as'     => 'donatur.edit',
            'filter' => 'permission:donors.manage',
        ]);
        $routes->post('(:num)', 'DonaturController::update/$1', [
            'as'     => 'donatur.update',
            'filter' => 'permission:donors.manage',
        ]);
        $routes->post('(:num)/status', 'DonaturController::toggleStatus/$1', [
            'as'     => 'donatur.status',
            'filter' => 'permission:donors.manage',
        ]);
        $routes->post('(:num)/delete', 'DonaturController::archive/$1', [
            'as'     => 'donatur.delete',
            'filter' => 'permission:donors.manage',
        ]);
    });

    $routes->group('donasi', ['filter' => 'permission:donations.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'DonasiController::index', ['as' => 'donasi.index']);
        $routes->get('new', 'DonasiController::create', [
            'as'     => 'donasi.create',
            'filter' => 'permission:donations.manage',
        ]);
        $routes->post('', 'DonasiController::store', [
            'as'     => 'donasi.store',
            'filter' => 'permission:donations.manage',
        ]);
        $routes->get('(:num)', 'DonasiController::show/$1', ['as' => 'donasi.show']);
        $routes->get('(:num)/receipt', 'DonasiController::receipt/$1', ['as' => 'donasi.receipt']);
        $routes->get('(:num)/edit', 'DonasiController::edit/$1', [
            'as'     => 'donasi.edit',
            'filter' => 'permission:donations.manage',
        ]);
        $routes->post('(:num)', 'DonasiController::update/$1', [
            'as'     => 'donasi.update',
            'filter' => 'permission:donations.manage',
        ]);
        $routes->post('(:num)/verify', 'DonasiController::verify/$1', [
            'as'     => 'donasi.verify',
            'filter' => 'permission:donations.manage',
        ]);
        $routes->post('(:num)/cancel', 'DonasiController::cancel/$1', [
            'as'     => 'donasi.cancel',
            'filter' => 'permission:donations.manage',
        ]);
    });

    $routes->group('distribusi', ['filter' => 'permission:distributions.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'DistribusiController::index', ['as' => 'distribusi.index']);
        $routes->get('new', 'DistribusiController::create', [
            'as'     => 'distribusi.create',
            'filter' => 'permission:distributions.manage',
        ]);
        $routes->post('', 'DistribusiController::store', [
            'as'     => 'distribusi.store',
            'filter' => 'permission:distributions.manage',
        ]);
        $routes->get('(:num)', 'DistribusiController::show/$1', ['as' => 'distribusi.show']);
        $routes->get('(:num)/receipt', 'DistribusiController::receipt/$1', ['as' => 'distribusi.receipt']);
        $routes->get('(:num)/edit', 'DistribusiController::edit/$1', [
            'as'     => 'distribusi.edit',
            'filter' => 'permission:distributions.manage',
        ]);
        $routes->post('(:num)', 'DistribusiController::update/$1', [
            'as'     => 'distribusi.update',
            'filter' => 'permission:distributions.manage',
        ]);
        $routes->post('(:num)/approve', 'DistribusiController::approve/$1', [
            'as'     => 'distribusi.approve',
            'filter' => 'permission:distributions.manage',
        ]);
        $routes->post('(:num)/distribute', 'DistribusiController::distribute/$1', [
            'as'     => 'distribusi.distribute',
            'filter' => 'permission:distributions.manage',
        ]);
        $routes->post('(:num)/cancel', 'DistribusiController::cancel/$1', [
            'as'     => 'distribusi.cancel',
            'filter' => 'permission:distributions.manage',
        ]);
    });

    $routes->group('users', ['filter' => 'permission:users.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'UserController::index', ['as' => 'users.index']);
        $routes->get('new', 'UserController::create', [
            'as'     => 'users.create',
            'filter' => 'permission:users.create',
        ]);
        $routes->post('', 'UserController::store', [
            'as'     => 'users.store',
            'filter' => 'permission:users.create',
        ]);
        $routes->get('(:num)/edit', 'UserController::edit/$1', [
            'as'     => 'users.edit',
            'filter' => 'permission:users.update',
        ]);
        $routes->post('(:num)', 'UserController::update/$1', [
            'as'     => 'users.update',
            'filter' => 'permission:users.update',
        ]);
        $routes->post('(:num)/status', 'UserController::toggleStatus/$1', [
            'as'     => 'users.status',
            'filter' => 'permission:users.update',
        ]);
        $routes->post('(:num)/delete', 'UserController::archive/$1', [
            'as'     => 'users.delete',
            'filter' => 'permission:users.delete',
        ]);
    });

    $routes->group('roles', ['filter' => 'permission:roles.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'RoleController::index', ['as' => 'roles.index']);
        $routes->get('new', 'RoleController::create', [
            'as'     => 'roles.create',
            'filter' => 'permission:roles.manage',
        ]);
        $routes->post('', 'RoleController::store', [
            'as'     => 'roles.store',
            'filter' => 'permission:roles.manage',
        ]);
        $routes->get('(:num)/edit', 'RoleController::edit/$1', [
            'as'     => 'roles.edit',
            'filter' => 'permission:roles.manage',
        ]);
        $routes->post('(:num)', 'RoleController::update/$1', [
            'as'     => 'roles.update',
            'filter' => 'permission:roles.manage',
        ]);
        $routes->post('(:num)/delete', 'RoleController::delete/$1', [
            'as'     => 'roles.delete',
            'filter' => 'permission:roles.manage',
        ]);
    });

    $routes->group('audit-logs', ['filter' => 'permission:audit.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'AuditLogController::index', ['as' => 'audit.index']);
        $routes->get('(:num)', 'AuditLogController::show/$1', ['as' => 'audit.show']);
    });

    $routes->group('laporan', ['filter' => 'permission:reports.view'], static function (RouteCollection $routes): void {
        $routes->get('', 'LaporanController::rekapitulasi', ['as' => 'laporan.index']);
        $routes->get('rekapitulasi', 'LaporanController::rekapitulasi', ['as' => 'laporan.rekapitulasi']);
        $routes->get('rekapitulasi/print', 'LaporanController::printRekapitulasi', ['as' => 'laporan.rekapitulasi.print']);
        $routes->get('rekapitulasi/excel', 'LaporanController::excelRekapitulasi', ['as' => 'laporan.rekapitulasi.excel']);

        $routes->get('penerimaan', 'LaporanController::penerimaan', ['as' => 'laporan.penerimaan']);
        $routes->get('penerimaan/print', 'LaporanController::printPenerimaan', ['as' => 'laporan.penerimaan.print']);
        $routes->get('penerimaan/excel', 'LaporanController::excelPenerimaan', ['as' => 'laporan.penerimaan.excel']);

        $routes->get('distribusi', 'LaporanController::distribusi', ['as' => 'laporan.distribusi']);
        $routes->get('distribusi/print', 'LaporanController::printDistribusi', ['as' => 'laporan.distribusi.print']);
        $routes->get('distribusi/excel', 'LaporanController::excelDistribusi', ['as' => 'laporan.distribusi.excel']);
    });

    $routes->get('template/laporan', 'TemplateController::report', [
        'as'     => 'templates.report',
        'filter' => 'permission:reports.view',
    ]);
});
