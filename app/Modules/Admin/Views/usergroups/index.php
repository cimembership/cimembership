
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-user-shield me-2"></i>User Groups</span>
        <a href="/admin/groups/create" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i>Add Group
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td>
                                <strong><?= esc($group['name']) ?></strong>
                                <?php if ($group['is_default']): ?>
                                    <span class="badge bg-info ms-1">Default</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($group['description'] ?? '-') ?></td>
                            <td>
                                <?php if ($group['status']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= number_format($group['user_count'] ?? 0) ?>
                            </td>
                            <td>
                                <a href="/admin/groups/permissions/<?= $group['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Permissions">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="/admin/groups/edit/<?= $group['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (!in_array($group['id'], [1, 2, 3, 4, 5])): ?>
                                    <a href="/admin/groups/delete/<?= $group['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this group?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
