<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $me = current_user();

        // 1. KPI Operasional Donasi & Distribusi
        $laporanService = service('laporanService');
        $kpi            = $laporanService->getKpiSummary();
        $chartData      = $laporanService->getMonthlyChartData((int) date('Y'));

        // 2. Transaksi Penerimaan Donasi Terbaru (5 transaksi)
        $recentDonations = [];
        if (can('donations.view')) {
            $recentDonations = $db->table('donasi')
                ->select('donasi.*, donatur.name AS donor_name, donatur.code AS donor_code')
                ->join('donatur', 'donatur.id = donasi.donor_id', 'left')
                ->where('donasi.deleted_at', null)
                ->orderBy('donasi.donation_date', 'DESC')
                ->orderBy('donasi.id', 'DESC')
                ->limit(5)
                ->get()
                ->getResultObject();
        }

        // 3. Transaksi Distribusi Terbaru (5 transaksi)
        $recentDistributions = [];
        if (can('distributions.view')) {
            $recentDistributions = $db->table('distribusi')
                ->select('distribusi.*, users.full_name AS approver_name')
                ->join('users', 'users.id = distribusi.approved_by', 'left')
                ->where('distribusi.deleted_at', null)
                ->orderBy('distribusi.distribution_date', 'DESC')
                ->orderBy('distribusi.id', 'DESC')
                ->limit(5)
                ->get()
                ->getResultObject();
        }

        // 4. Data Pengguna & Administrasi (Untuk Admin / Pimpinan)
        $totalUsers = $db->table('users')
            ->where('deleted_at', null)
            ->countAllResults();

        $activeUsers = $db->table('users')
            ->where('deleted_at', null)
            ->where('status', 'active')
            ->countAllResults();

        $inactiveUsers = $totalUsers - $activeUsers;

        $roleDistribution = $db->table('roles')
            ->select('roles.name, roles.slug, COUNT(users.id) AS user_count')
            ->join('users', 'users.role_id = roles.id AND users.deleted_at IS NULL', 'left')
            ->groupBy('roles.id')
            ->orderBy('user_count', 'DESC')
            ->orderBy('roles.name', 'ASC')
            ->get()
            ->getResultObject();

        $todayLogins = $db->table('audit_logs')
            ->where('action', 'auth.login')
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->countAllResults();

        $recentActivities = [];
        if (can('audit.view')) {
            $recentActivities = $db->table('audit_logs')
                ->select('audit_logs.*, users.full_name AS actor_name')
                ->join('users', 'users.id = audit_logs.user_id', 'left')
                ->orderBy('audit_logs.id', 'DESC')
                ->limit(6)
                ->get()
                ->getResultObject();
        }

        return view('dashboard/index', [
            'title'               => 'Dashboard',
            'kpi'                 => $kpi,
            'chartData'           => $chartData,
            'recentDonations'     => $recentDonations,
            'recentDistributions' => $recentDistributions,
            'totalUsers'          => $totalUsers,
            'activeUsers'         => $activeUsers,
            'inactiveUsers'       => $inactiveUsers,
            'totalRoles'          => $db->table('roles')->countAllResults(),
            'totalPermissions'    => $db->table('permissions')->countAllResults(),
            'roleDistribution'    => $roleDistribution,
            'todayLogins'         => $todayLogins,
            'recentActivities'    => $recentActivities,
            'canViewAudit'        => can('audit.view'),
            'canViewDonations'    => can('donations.view'),
            'canViewDistributions'=> can('distributions.view'),
            'canViewReports'      => can('reports.view'),
            'me'                  => $me,
        ]);
    }
}
