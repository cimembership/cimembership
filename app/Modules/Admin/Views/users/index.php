
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users me-2"></i>Manage Users</span>
        <a href="/admin/users/create" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i>Add User
        </a>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="get" class="row mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?= esc($_GET['search'] ?? '') ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="banned" <?= ($_GET['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Group</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; font-size: 14px;">
                                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-weight-semibold"><?= esc($user['username']) ?></div>
                                        <div class="small text-muted"><?= esc($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= esc($user['group']['name'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'banned' => 'danger',
                                    'inactive' => 'secondary'
                                ][$user['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($user['status']) ?></span>
                            </td>
                            <td>
                                <?= $user['last_login_at'] ? date('M d, Y H:i', strtotime($user['last_login_at'])) : 'Never' ?>
                            </td>
                            <td>
                                <a href="/admin/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/admin/users/delete/<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pager)): ?>
            <div class="d-flex justify-content-end">
                <?= $pager->links() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
