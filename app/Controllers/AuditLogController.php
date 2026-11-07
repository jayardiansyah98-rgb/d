<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AuditLogController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $search = mb_substr($search, 0, 100);

        $action = trim((string) $this->request->getGet('action'));
        $action = mb_substr($action, 0, 80);

        $model = model(AuditLogModel::class);
        $model->select('audit_logs.*, users.full_name AS actor_name, users.username AS actor_username')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.id', 'DESC');

        if ($search !== '') {
            $model->groupStart()
                ->like('audit_logs.description', $search)
                ->orLike('audit_logs.ip_address', $search)
                ->orLike('users.full_name', $search)
                ->orLike('users.username', $search)
                ->groupEnd();
        }

        if ($action !== '') {
            $model->where('audit_logs.action', $action);
        }

        $logs = $model->paginate(20, 'audit');

        return view('audit/index', [
            'title'   => 'Audit Log',
            'logs'    => $logs,
            'pager'   => $model->pager,
            'actions' => (new AuditLogModel())->getActionOptions(),
            'search'  => $search,
            'action'  => $action,
        ]);
    }

    public function show($id)
    {
        $log = model(AuditLogModel::class)->find((int) $id);

        if ($log === null) {
            throw PageNotFoundException::forPageNotFound('Audit log tidak ditemukan.');
        }

        $actor = null;

        if ($log->user_id !== null) {
            $actor = db_connect()->table('users')
                ->select('full_name, username')
                ->where('id', $log->user_id)
                ->get()
                ->getRow();
        }

        return view('audit/show', [
            'title' => 'Detail Audit Log',
            'log'   => $log,
            'actor' => $actor,
        ]);
    }
}
