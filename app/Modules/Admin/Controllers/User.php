<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Auth\Authentication;
use App\Libraries\Auth\PasswordHasher;
use App\Models\UserModel;
use App\Models\UserGroupModel;
use App\Models\UserProfileModel;
use CodeIgniter\HTTP\ResponseInterface;

class User extends BaseController
{
    protected ?Authentication $auth = null;
    protected ?UserModel $userModel = null;
    protected ?UserGroupModel $groupModel = null;
    protected ?UserProfileModel $profileModel = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = new Authentication();
        $this->userModel = model('UserModel');
        $this->groupModel = model('UserGroupModel');
        $this->profileModel = model('UserProfileModel');
    }

    /**
     * Users list
     */
    public function index(): ResponseInterface|string
    {
        $filters = [
            'search'   => $this->request->getGet('search'),
            'status'   => $this->request->getGet('status'),
            'group_id' => $this->request->getGet('group_id'),
        ];

        $perPage = $this->request->getGet('per_page') ?? 20;
        $result = $this->userModel->getPaginated($filters, $perPage);

        $data = [
            'title'       => 'Users',
            'users'       => $result['items'],
            'pager'       => $result['pager'],
            'groups'      => $this->groupModel->getForDropdown(),
            'filters'     => $filters,
            'currentUser' => $this->auth->getCurrentUser(),
        ];

        return $this->render('Admin::users/index', $data);
    }

    /**
     * AJAX list for DataTables
     */
    public function list(): ResponseInterface
    {
        $filters = [
            'search'   => $this->request->getGet('search')['value'] ?? null,
            'status'   => $this->request->getGet('status'),
            'group_id' => $this->request->getGet('group_id'),
        ];

        $start = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 20);
        $order = $this->request->getGet('order')[0] ?? ['column' => 0, 'dir' => 'asc'];

        $builder = $this->userModel->builder();

        // Apply filters
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('users.username', $filters['search'], 'both')
                ->orLike('users.email', $filters['search'], 'both')
                ->groupEnd();
        }

        if (!empty($filters['status'])) {
            $builder->where('users.status', $filters['status']);
        }

        if (!empty($filters['group_id'])) {
            $builder->where('users.group_id', $filters['group_id']);
        }

        // Get total count
        $totalRecords = $this->userModel->where('deleted_at', null)->countAllResults();
        $filteredRecords = $builder->where('deleted_at', null)->countAllResults();

        // Apply ordering
        $columns = ['id', 'username', 'email', 'group_id', 'status', 'last_login_at', 'created_at'];
        $orderColumn = $columns[$order['column']] ?? 'id';
        $builder->orderBy($orderColumn, $order['dir']);

        // Get data
        $builder->limit($length, $start);
        $users = $builder->get()->getResultArray();

        // Format data
        $data = [];
        foreach ($users as $user) {
            $group = $this->groupModel->find($user['group_id']);
            $data[] = [
                'id'           => $user['id'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'group'        => $group['name'] ?? 'Unknown',
                'status'       => $this->formatStatus($user['status']),
                'last_login'   => $user['last_login_at'] ? date('Y-m-d H:i', strtotime($user['last_login_at'])) : 'Never',
                'created_at'   => date('Y-m-d', strtotime($user['created_at'])),
                'actions'      => $this->getUserActions($user),
            ];
        }

        return $this->json([
            'draw'            => (int) $this->request->getGet('draw'),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Create user form
     */
    public function create(): ResponseInterface|string
    {
        $data = [
            'title'  => 'Create User',
            'groups' => $this->groupModel->getForDropdown(),
            'user'   => [],
        ];

        return $this->render('Admin::users/form', $data);
    }

    /**
     * Store new user
     */
    public function store(): ResponseInterface
    {
        $rules = [
            'username'         => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'group_id'         => 'required|integer',
            'status'           => 'required|in_list[active,inactive,pending]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $userData = [
            'username'    => $this->request->getPost('username'),
            'email'       => $this->request->getPost('email'),
            'password'    => $this->request->getPost('password'),
            'group_id'    => $this->request->getPost('group_id'),
            'status'      => $this->request->getPost('status'),
            'first_name'  => $this->request->getPost('first_name'),
            'last_name'   => $this->request->getPost('last_name'),
        ];

        $userId = $this->userModel->insert($userData);

        if ($userId) {
            return redirect()->to('/admin/users')
                ->with('success', 'User created successfully.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to create user.');
    }

    /**
     * Edit user form
     */
    public function edit(int $id): ResponseInterface|string
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('/admin/users')
                ->with('error', 'User not found.');
        }

        $profile = $this->profileModel->getByUserId($id);

        $data = [
            'title'   => 'Edit User',
            'groups'  => $this->groupModel->getForDropdown(),
            'user'    => $user,
            'profile' => $profile,
        ];

        return $this->render('Admin::users/form', $data);
    }

    /**
     * Update user
     */
    public function update(int $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('/admin/users')
                ->with('error', 'User not found.');
        }

        $rules = [
            'username' => "required|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
            'email'    => "required|valid_email|is_unique[users.email,id,{$id}]",
            'password' => 'permit_empty|min_length[8]',
            'group_id' => 'required|integer',
            'status'   => 'required|in_list[active,inactive,banned,pending]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $userData = [
            'username'   => $this->request->getPost('username'),
            'email'      => $this->request->getPost('email'),
            'group_id'   => $this->request->getPost('group_id'),
            'status'     => $this->request->getPost('status'),
            'ban_reason' => $this->request->getPost('ban_reason'),
        ];

        // Only update password if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $userData['password'] = $password;
        }

        // Update user
        $this->userModel->update($id, $userData);

        // Update profile
        $profileData = [
            'first_name'  => $this->request->getPost('first_name'),
            'last_name'   => $this->request->getPost('last_name'),
            'phone'       => $this->request->getPost('phone'),
            'company'     => $this->request->getPost('company'),
            'address'     => $this->request->getPost('address'),
            'city'        => $this->request->getPost('city'),
            'country'     => $this->request->getPost('country'),
        ];

        $this->profileModel->updateByUserId($id, $profileData);

        return redirect()->to('/admin/users')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete user (soft delete)
     */
    public function delete(int $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Prevent deleting yourself
        $currentUser = $this->auth->getCurrentUser();
        if ($currentUser && $currentUser['id'] == $id) {
            return $this->json(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
        }

        // Prevent deleting super admin
        if ($user['group_id'] === 1) {
            return $this->json(['success' => false, 'message' => 'Cannot delete super administrator.'], 400);
        }

        if ($this->userModel->delete($id)) {
            return $this->json(['success' => true, 'message' => 'User deleted successfully.']);
        }

        return $this->json(['success' => false, 'message' => 'Failed to delete user.'], 500);
    }

    /**
     * Bulk action on users
     */
    public function bulkAction(): ResponseInterface
    {
        $action = $this->request->getPost('action');
        $ids = $this->request->getPost('ids');

        if (empty($ids) || !is_array($ids)) {
            return $this->json(['success' => false, 'message' => 'No users selected.'], 400);
        }

        $currentUser = $this->auth->getCurrentUser();

        switch ($action) {
            case 'activate':
                foreach ($ids as $id) {
                    if ($id != $currentUser['id']) {
                        $this->userModel->update($id, ['status' => 'active']);
                    }
                }
                return $this->json(['success' => true, 'message' => 'Users activated successfully.']);

            case 'deactivate':
                foreach ($ids as $id) {
                    if ($id != $currentUser['id']) {
                        $this->userModel->update($id, ['status' => 'inactive']);
                    }
                }
                return $this->json(['success' => true, 'message' => 'Users deactivated successfully.']);

            case 'delete':
                foreach ($ids as $id) {
                    $user = $this->userModel->find($id);
                    if ($user && $user['group_id'] !== 1 && $id != $currentUser['id']) {
                        $this->userModel->delete($id);
                    }
                }
                return $this->json(['success' => true, 'message' => 'Users deleted successfully.']);

            default:
                return $this->json(['success' => false, 'message' => 'Invalid action.'], 400);
        }
    }

    /**
     * Export users
     */
    public function export(): ResponseInterface
    {
        $users = $this->userModel->where('deleted_at', null)->findAll();

        $filename = 'users_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Username', 'Email', 'Group', 'Status', 'Last Login', 'Created']);

        foreach ($users as $user) {
            $group = $this->groupModel->find($user['group_id']);
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['email'],
                $group['name'] ?? 'Unknown',
                $user['status'],
                $user['last_login_at'] ?? 'Never',
                $user['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Format status for display
     */
    private function formatStatus(string $status): string
    {
        $badges = [
            'active'   => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
            'banned'   => '<span class="badge bg-danger">Banned</span>',
            'pending'  => '<span class="badge bg-warning">Pending</span>',
        ];

        return $badges[$status] ?? $status;
    }

    /**
     * Get action buttons for user
     */
    private function getUserActions(array $user): string
    {
        $actions = '<div class="btn-group btn-group-sm">';
        $actions .= '<a href="/admin/users/edit/' . $user['id'] . '" class="btn btn-primary">Edit</a>';
        $actions .= '<button class="btn btn-danger" onclick="deleteUser(' . $user['id'] . ')">Delete</button>';
        $actions .= '</div>';
        return $actions;
    }
}
