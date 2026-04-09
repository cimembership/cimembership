
<h2 class="text-center mb-4">Set New Password</h2>
<p class="text-muted text-center mb-4">Enter your new password below.</p>

<form action="/auth/reset-password/<?= esc($token) ?>" method="post">
    <?= csrf_field() ?>

    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required minlength="8">
        <label for="password"><i class="fas fa-lock me-2"></i>New Password</label>
        <div class="form-text">At least 8 characters</div>
    </div>

    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm Password" required>
        <label for="password_confirm"><i class="fas fa-lock me-2"></i>Confirm Password</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
        <i class="fas fa-save me-2"></i>Reset Password
    </button>
</form>

<div class="text-center mt-4">
    <p class="mb-0"><a href="/auth/login" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Login</a></p>
</div>
