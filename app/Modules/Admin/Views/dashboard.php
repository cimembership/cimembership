
<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card primary h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_users']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card success h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['active_users']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card info h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Online Now</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['online_users']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-signal fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card warning h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['pending_users']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>Recent Users</span>
                <a href="/admin/users" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold"><?= esc($user['username']) ?></div>
                                                <div class="small text-muted"><?= esc($user['email']) ?></div>
                                            </div>
                                        </div>
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
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Recent Activity
            </div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted mb-0">No recent activity</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentActivity as $activity): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <?php if ($activity['success']): ?>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-2"></i>
                                        <?php endif; ?>
                                        <?= esc($activity['identifier']) ?>
                                        <span class="text-muted">
                                            <?= $activity['success'] ? 'logged in' : 'failed login' ?>
                                        </span>
                                    </div>
                                    <span class="text-muted small"><?= time_elapsed_string($activity['created_at']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Info -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-server me-2"></i>System Information
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>PHP Version</span>
                        <span class="badge bg-primary"><?= $systemInfo['php_version'] ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>CodeIgniter Version</span>
                        <span class="badge bg-primary"><?= $systemInfo['ci_version'] ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>Database Driver</span>
                        <span class="badge bg-primary"><?= $systemInfo['database_driver'] ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
