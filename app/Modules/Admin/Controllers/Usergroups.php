<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Models\UserGroupModel;
use CodeIgniter\HTTP\ResponseInterface;

class Usergroups extends BaseController
{
    protected ?UserGroupModel $groupModel = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->groupModel = model('UserGroupModel');
    }

    /**
     * Groups list
     */
    public function index(): ResponseInterface|string
    {
        $groups = $this->groupModel->findAll();

        $data = [
            'title'  => 'User Groups',
            'groups' => $groups,
        ];

        return $this->render('Admin::usergroups/index', $data);
    }

    /**
     * AJAX list
     */
    public function list(): ResponseInterface
    {
        $groups = $this->groupModel->findAll();
        return $this->json(['data' => $groups]);
    }

    /**
     * Create group form
     */
    public function create(): ResponseInterface|string
    {
        $data = [
            'title'       => 'Create Group',
            'group'       => [],
            'permissions' => $this->groupModel->getAvailablePermissions(),
        ];

        return $this->render('Admin::usergroups/form', $data);
    }

    /**
     * Store new group
     */
    public function store(): ResponseInterface
    {
        $rules = [
            'name'        => 'required|min_length[2]|max_length[50]',
            'description' => 'permit_empty|max_length[255]',
            'status'      => 'required|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $groupData = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'status'      => $this->request->getPost('status'),
            'permissions' => $this->request->getPost('permissions') ?: [],
        ];

        $groupId = $this->groupModel->insert($groupData);

        if ($groupId) {
            return redirect()->to('/admin/groups')
                ->with('success', 'Group created successfully.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to create group.');
    }

    /**
     * Edit group form
     */
    public function edit(int $id): ResponseInterface|string
    {
        $group = $this->groupModel->find($id);

        if (!$group) {
            return redirect()->to('/admin/groups')
                ->with('error', 'Group not found.');
        }

        $data = [
            'title'       => 'Edit Group',
            'group'       => $group,
            'permissions' => $this->groupModel->getAvailablePermissions(),
        ];

        return $this->render('Admin::usergroups/form', $data);
    }

    /**
     * Update group
     */
    public function update(int $id): ResponseInterface
    {
        $group = $this->groupModel->find($id);

        if (!$group) {
            return redirect()->to('/admin/groups')
                ->with('error', 'Group not found.');
        }

        // Prevent editing system groups (IDs 1-5)
        if ($id <= 5) {
            return redirect()->to('/admin/groups')
                ->with('error', 'Cannot modify system groups.');
        }

        $rules = [
            'name'        => "required|min_length[2]|max_length[50]|is_unique[users_groups.name,id,{$id}]",
            'description' => 'permit_empty|max_length[255]',
            'status'      => 'required|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $groupData = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'status'      => $this->request->getPost('status'),
            'permissions' => $this->request->getPost('permissions') ?: [],
        ];

        $this->groupModel->update($id, $groupData);

        return redirect()->to('/admin/groups')
            ->with('success', 'Group updated successfully.');
    }

    /**
     * Delete group
     */
    public function delete(int $id): ResponseInterface
    {
        $group = $this->groupModel->find($id);

        if (!$group) {
            return $this->json(['success' => false, 'message' => 'Group not found.'], 404);
        }

        // Prevent deleting system groups
        if ($id <= 5) {
            return $this->json(['success' => false, 'message' => 'Cannot delete system groups.'], 400);
        }

        // Check if group has users
        if (!$this->groupModel->canDelete($id)) {
            return $this->json(['success' => false, 'message' => 'Cannot delete group with users.'], 400);
        }

        if ($this->groupModel->delete($id)) {
            return $this->json(['success' => true, 'message' => 'Group deleted successfully.']);
        }

        return $this->json(['success' => false, 'message' => 'Failed to delete group.'], 500);
    }

    /**
     * Edit permissions
     */
    public function permissions(int $id): ResponseInterface|string
    {
        $group = $this->groupModel->find($id);

        if (!$group) {
            return redirect()->to('/admin/groups')
                ->with('error', 'Group not found.');
        }

        $data = [
            'title'       => 'Edit Permissions',
            'group'       => $group,
            'permissions' => $this->groupModel->getAvailablePermissions(),
        ];

        return $this->render('Admin::usergroups/permissions', $data);
    }

    /**
     * Update permissions
     */
    public function updatePermissions(int $id): ResponseInterface
    {
        $group = $this->groupModel->find($id);

        if (!$group) {
            return redirect()->to('/admin/groups')
                ->with('error', 'Group not found.');
        }

        $permissions = $this->request->getPost('permissions') ?: [];

        // Convert to proper format
        $permsArray = [];
        foreach ($permissions as $key => $value) {
            $permsArray[$key] = '1';
        }

        $this->groupModel->update($id, ['permissions' => $permsArray]);

        return redirect()->to('/admin/groups')
            ->with('success', 'Permissions updated successfully.');
    }
}
