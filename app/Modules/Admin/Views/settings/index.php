
<ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
            <i class="fas fa-cog me-1"></i>General
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="auth-tab" data-bs-toggle="tab" data-bs-target="#auth" type="button" role="tab">
            <i class="fas fa-lock me-1"></i>Auth
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="oauth-tab" data-bs-toggle="tab" data-bs-target="#oauth" type="button" role="tab">
            <i class="fas fa-share-alt me-1"></i>OAuth
        </button>
    </li>
    <li class="nav-item" role="tab">
        <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
            <i class="fas fa-envelope me-1"></i>Email
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">
    <!-- General Settings -->
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="card">
            <div class="card-header">General Settings</div>
            <div class="card-body">
                <form action="/admin/settings/general" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="<?= esc($settings['site_name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Site Description</label>
                        <textarea class="form-control" name="site_description" rows="2"><?= esc($settings['site_description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Email</label>
                        <input type="email" class="form-control" name="admin_email" value="<?= esc($settings['admin_email'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Auth Settings -->
    <div class="tab-pane fade" id="auth" role="tabpanel">
        <div class="card">
            <div class="card-header">Authentication Settings</div>
            <div class="card-body">
                <form action="/admin/settings/auth" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="allow_registration" name="allow_registration" value="1"
                               <?= ($settings['allow_registration'] ?? true) ? 'checked' : '' ?>
                        <label class="form-check-label" for="allow_registration">Allow User Registration</label>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="require_activation" name="require_activation" value="1"
                               <?= ($settings['require_activation'] ?? true) ? 'checked' : '' ?>
                        <label class="form-check-label" for="require_activation">Require Email Activation</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Minimum Password Length</label>
                        <input type="number" class="form-control" name="min_password_length" value="<?= $settings['min_password_length'] ?? 8 ?>" min="6">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- OAuth Settings -->
    <div class="tab-pane fade" id="oauth" role="tabpanel">
        <div class="card">
            <div class="card-header">OAuth Providers</div>
            <div class="card-body">
                <form action="/admin/settings/oauth" method="post">
                    <?= csrf_field() ?>

                    <?php $providers = ['facebook', 'google', 'github', 'linkedin', 'microsoft']; ?

                    <ul class="nav nav-pills mb-3" id="oauthTab" role="tablist">
                        <?php foreach ($providers as $i => $provider): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#oauth-<?= $provider ?>" type="button">
                                    <?= ucfirst($provider) ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content" id="oauthTabContent">
                        <?php foreach ($providers as $i => $provider): ?>
                            <div class="tab-pane fade show <?= $i === 0 ? 'active' : '' ?>" id="oauth-<?= $provider ?>">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="oauth_<?= $provider ?>_enabled"
                                           name="oauth_<?= $provider ?>_enabled" value="1"
                                           <?= ($settings["oauth_{$provider}_enabled"] ?? false) ? 'checked' : '' ?>
                                    <label class="form-check-label" for="oauth_<?= $provider ?>_enabled">Enable <?= ucfirst($provider) ?> Login</label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" class="form-control" name="oauth_<?= $provider ?>_client_id"
                                           value="<?= esc($settings["oauth_{$provider}_client_id"] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Client Secret</label>
                                    <input type="password" class="form-control" name="oauth_<?= $provider ?>_client_secret"
                                           value="<?= esc($settings["oauth_{$provider}_client_secret"] ?? '') ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Settings -->
    <div class="tab-pane fade" id="email" role="tabpanel">
        <div class="card">
            <div class="card-header">Email Settings</div>
            <div class="card-body">
                <form action="/admin/settings/email" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Email Protocol</label>
                        <select class="form-select" name="email_protocol">
                            <option value="mail" <?= ($settings['email_protocol'] ?? '') === 'mail' ? 'selected' : '' ?>Mail</option>
                            <option value="sendmail" <?= ($settings['email_protocol'] ?? '') === 'sendmail' ? 'selected' : '' ?>Sendmail</option>
                            <option value="smtp" <?= ($settings['email_protocol'] ?? '') === 'smtp' ? 'selected' : '' ?>SMTP</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" value="<?= esc($settings['smtp_host'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" name="smtp_user" value="<?= esc($settings['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" name="smtp_pass" value="<?= esc($settings['smtp_pass'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" name="smtp_port" value="<?= $settings['smtp_port'] ?? 587 ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
