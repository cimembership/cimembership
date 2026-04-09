
<h2 class="text-center mb-4">Reset Password</h2>
<p class="text-muted text-center mb-4">Enter your email address and we'll send you instructions to reset your password.</p>

<form action="/auth/forgot-password" method="post">
    <?= csrf_field() ?>

    <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
               value="<?= old('email') ?>">
        <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
    </div>

    <?php if ($captcha ?? false): ?>
        <div class="mb-3 text-center">
            <img src="<?= $captchaImage ?>" alt="Captcha" class="img-fluid rounded mb-2">
            <input type="text" class="form-control" name="captcha" placeholder="Enter captcha code" required>
        </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
    </button>
</form>

<div class="text-center mt-4">
    <p class="mb-0"><a href="/auth/login" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Login</a></p>
</div>
