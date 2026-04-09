<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Auth\Authentication;
use App\Models\UserModel;
use App\Models\UserGroupModel;
use App\Models\LoginAttemptModel;
use App\Models\OptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class Dashboard extends BaseController
{
    protected ?Authentication $auth = null;
    protected ?UserModel $userModel = null;
    protected ?UserGroupModel $groupModel = null;
    protected ?LoginAttemptModel $loginAttemptModel = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = new Authentication();
        $this->userModel = model('UserModel');
        $this->groupModel = model('UserGroupModel');
        $this->loginAttemptModel = model('LoginAttemptModel');
    }

    /**
     * Admin dashboard
     */
    public function index(): ResponseInterface|string
    {
        $stats = [
            'total_users'       => $this->userModel->where('deleted_at', null)->countAllResults(),
            'active_users'      => $this->userModel->where('status', 'active')->where('deleted_at', null)->countAllResults(),
            'pending_users'     => $this->userModel->where('status', 'pending')->where('deleted_at', null)->countAllResults(),
            'banned_users'      => $this->userModel->where('status', 'banned')->where('deleted_at', null)->countAllResults(),
            'total_groups'      => $this->groupModel->where('deleted_at', null)->countAllResults(),
            'online_users'      => $this->getOnlineUsersCount(),
        ];

        // Recent users
        $recentUsers = $this->userModel->where('deleted_at', null)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        // Recent login activity
        $recentActivity = $this->loginAttemptModel->getRecentActivity(10);

        // Get PHP version and server info
        $systemInfo = [
            'php_version'       => phpversion(),
            'ci_version'        => \CodeIgniter\CodeIgniter::CI_VERSION,
            'database_driver'   => \Config\Database::connect()->DBDriver,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        ];

        $data = [
            'title'          => 'Dashboard',
            'stats'          => $stats,
            'recentUsers'    => $recentUsers,
            'recentActivity' => $recentActivity,
            'systemInfo'     => $systemInfo,
        ];

        return $this->render('Admin::dashboard', $data);
    }

    /**
     * Get online users count (active in last 15 minutes)
     */
    private function getOnlineUsersCount(): int
    {
        $fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        return $this->userModel->where('last_active_at >=', $fifteenMinutesAgo)
            ->where('deleted_at', null)
            ->countAllResults();
    }
}
