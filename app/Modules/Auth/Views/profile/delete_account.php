
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Delete Account</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-circle me-2"></i>Warning: This action cannot be undone!</h5>
                        <p class="mb-0">Deleting your account will:</p>
                        <ul class="mb-0">
                            <li>Remove all your personal data</li>
                            <li>Delete your profile information</li>
                            <li>Cancel any active sessions</li>
                            <li>This action is irreversible</li>
                        </ul>
                    </div>

                    <form action="/auth/profile/delete-account" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Enter your password to confirm</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type DELETE to confirm</label>
                            <input type="text" class="form-control" name="confirm_delete" required
                                   placeholder="Type DELETE here" pattern="DELETE">
                            <div class="form-text text-danger">This will permanently delete your account.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-2"></i>Permanently Delete My Account
                            </button>
                            <a href="/auth/profile" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
