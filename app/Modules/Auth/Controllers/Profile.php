<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Auth\Authentication;
use App\Libraries\Auth\PasswordHasher;
use App\Models\UserModel;
use App\Models\UserProfileModel;
use App\Models\UserOauthModel;
use CodeIgniter\HTTP\ResponseInterface;

class Profile extends BaseController
{
    protected ?Authentication $auth = null;
    protected ?UserModel $userModel = null;
    protected ?UserProfileModel $profileModel = null;
    protected ?UserOauthModel $oauthModel = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = new Authentication();
        $this->userModel = model('UserModel');
        $this->profileModel = model('UserProfileModel');
        $this->oauthModel = model('UserOauthModel');

        // Ensure user is logged in
        if (!$this->auth->isLoggedIn()) {
            $this->session->set('redirect_url', current_url());
            return redirect()->to('/auth/login')
                ->with('error', 'Please login to access this page.');
        }
    }

    /**
     * Profile page
     */
    public function index(): ResponseInterface|string
    {
        $user = $this->auth->getCurrentUser();
        $userData = $this->userModel->find($user['id']);
        $profile = $this->profileModel->getByUserId($user['id']);
        $oauthConnections = $this->oauthModel->getByUserId($user['id']);

        $data = [
            'title'        => 'My Profile',
            'user'         => $userData,
            'profile'      => $profile,
            'oauth'        => $oauthConnections,
            'avatar_url'   => $this->profileModel->getAvatarUrl($user['id']),
        ];

        return $this->render('Auth::profile/index', $data);
    }

    /**
     * Update profile
     */
    public function update(): ResponseInterface
    {
        $user = $this->auth->getCurrentUser();

        $rules = [
            'first_name'  => 'permit_empty|max_length[100]',
            'last_name'   => 'permit_empty|max_length[100]',
            'display_name' => 'permit_empty|max_length[100]',
            'phone'       => 'permit_empty|max_length[50]',
            'company'     => 'permit_empty|max_length[250]',
            'website'     => 'permit_empty|valid_url|max_length[255]',
            'address'     => 'permit_empty',
            'city'        => 'permit_empty|max_length[100]',
            'state'       => 'permit_empty|max_length[100]',
            'country'     => 'permit_empty|exact_length[2]',
            'postal_code' => 'permit_empty|max_length[20]',
            'timezone'    => 'permit_empty|max_length[50]',
            'bio'         => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $profileData = [
            'first_name'   => $this->request->getPost('first_name'),
            'last_name'    => $this->request->getPost('last_name'),
            'display_name' => $this->request->getPost('display_name'),
            'phone'        => $this->request->getPost('phone'),
            'company'      => $this->request->getPost('company'),
            'website'      => $this->request->getPost('website'),
            'address'      => $this->request->getPost('address'),
            'city'         => $this->request->getPost('city'),
            'state'        => $this->request->getPost('state'),
            'country'      => $this->request->getPost('country'),
            'postal_code'  => $this->request->getPost('postal_code'),
            'timezone'     => $this->request->getPost('timezone') ?: 'UTC',
            'bio'          => $this->request->getPost('bio'),
        ];

        // Handle avatar upload
        $avatar = $this->request->getFile('avatar');
        if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
            $validationRule = [
                'avatar' => [
                    'label' => 'Avatar',
                    'rules' => 'uploaded[avatar]'
                        . '|is_image[avatar]'
                        . '|mime_in[avatar,image/jpg,image/jpeg,image/png,image/webp]'
                        . '|max_size[avatar,2048]'
                        . '|max_dims[avatar,1024,1024]',
                ],
            ];

            if (!$this->validate($validationRule)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $this->validator->getErrors());
            }

            $newName = $avatar->getRandomName();
            $avatar->move(WRITEPATH . 'uploads/avatars/', $newName);

            // Resize image if needed
            $this->resizeAvatar(WRITEPATH . 'uploads/avatars/' . $newName);

            $profileData['avatar'] = $newName;
        }

        if ($this->profileModel->updateByUserId($user['id'], $profileData)) {
            return redirect()->back()
                ->with('success', 'Profile updated successfully.');
        }

        return redirect()->back()
            ->with('error', 'Failed to update profile.');
    }

    /**
     * Change password
     */
    public function changePassword(): ResponseInterface
    {
        $user = $this->auth->getCurrentUser();
        $userData = $this->userModel->find($user['id']);

        $rules = [
            'current_password'     => 'required',
            'new_password'         => 'required|min_length[8]',
            'new_password_confirm' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', $this->validator->getErrors());
        }

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');

        // Verify current password
        $hasher = new PasswordHasher();
        if (!$hasher->verify($currentPassword, $userData['password_hash'])) {
            return redirect()->back()
                ->with('error', 'Current password is incorrect.');
        }

        // Update password
        $this->userModel->update($user['id'], [
            'password_hash' => $hasher->hash($newPassword),
        ]);

        return redirect()->back()
            ->with('success', 'Password changed successfully.');
    }

    /**
     * Delete account page
     */
    public function deleteAccount(): ResponseInterface|string
    {
        $user = $this->auth->getCurrentUser();

        if ($this->request->getMethod() === 'POST') {
            $password = $this->request->getPost('password');
            $confirm = $this->request->getPost('confirm_delete');

            if ($confirm !== 'DELETE') {
                return redirect()->back()
                    ->with('error', 'Please type DELETE to confirm account deletion.');
            }

            $userData = $this->userModel->find($user['id']);
            $hasher = new PasswordHasher();

            if (!$hasher->verify($password, $userData['password_hash'])) {
                return redirect()->back()
                    ->with('error', 'Password is incorrect.');
            }

            // Soft delete the user
            $this->userModel->delete($user['id']);
            $this->auth->logout();

            return redirect()->to('/')
                ->with('success', 'Your account has been deleted. We\'re sorry to see you go!');
        }

        return $this->render('Auth::profile/delete_account', [
            'title' => 'Delete Account',
            'user'  => $user,
        ]);
    }

    /**
     * Resize avatar image
     */
    private function resizeAvatar(string $path): void
    {
        $image = service('image');
        $image->withFile($path)
            ->fit(256, 256, 'center')
            ->save($path);
    }
}
