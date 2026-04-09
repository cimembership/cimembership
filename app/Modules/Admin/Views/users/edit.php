
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <?= isset($user) ? 'Edit User' : 'Create User' ?>
            </div>
            <div class="card-body">
                <form action="/admin/users/<?= isset($user) ? 'edit/' . $user['id'] : 'create' ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" value="<?= old('username', $user['username'] ?? '') ?>" required
                                   <?= isset($user) ? 'readonly' : '' ?>
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?= old('email', $user['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?= old('first_name', $profile['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?= old('last_name', $profile['last_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">User Group *</label>
                            <select class="form-select" name="group_id" required>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= $group['id'] ?>" <?= old('group_id', $user['group_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                                        <?= esc($group['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?= old('status', $user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="pending" <?= old('status', $user['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="banned" <?= old('status', $user['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
                                <option value="inactive" <?= old('status', $user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <?= isset($user) ? '(leave blank to keep current)' : '*' ?></label>
                        <input type="password" class="form-control" name="password" <?= isset($user) ? '' : 'required' ?> minlength="8">
                    </div>

                    <?php if (isset($user) && $user['status'] === 'banned'): ?>
                        <div class="mb-3">
                            <label class="form-label">Ban Reason</label>
                            <textarea class="form-control" name="ban_reason" rows="2"><?= old('ban_reason', $user['ban_reason'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between">
                        <a href="/admin/users" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($user)): ?>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">User Info</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>User ID:</span>
                            <span class="text-muted">#<?= $user['id'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Created:</span>
                            <span class="text-muted"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Last Login:</span>
                            <span class="text-muted"><?= $user['last_login_at'] ? date('M d, Y H:i', strtotime($user['last_login_at'])) : 'Never' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>IP Address:</span>
                            <span class="text-muted"><?= esc($user['last_login_ip'] ?? 'N/A') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
