
<h2 class="text-center mb-4">Welcome Back</h2>

<form action="/auth/login" method="post">
    <?= csrf_field() ?>

    <div class="form-floating mb-3">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required
               value="<?= old('username') ?>">
        <label for="username"><i class="fas fa-user me-2"></i>Username or Email</label>
    </div>

    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
    </div>

    <?php if ($captcha ?? false): ?>
        <div class="mb-3 text-center">
            <img src="<?= $captchaImage ?>" alt="Captcha" class="img-fluid rounded mb-2">
            <input type="text" class="form-control" name="captcha" placeholder="Enter captcha code" required>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <a href="/auth/forgot-password" class="text-decoration-none">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
        <i class="fas fa-sign-in-alt me-2"></i>Sign In
    </button>
</form>

<?php if (!empty($oauth)): ?>
    <div class="divider">
        <span>OR</span>
    </div>

    <div class="d-grid gap-2">
        <?php foreach ($oauth as $key => $name): ?>
            <a href="/auth/oauth/<?= $key ?>" class="btn btn-outline-secondary oauth-btn">
                <i class="fab fa-<?= $key ?> me-2"></i>Continue with <?= $name ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="text-center mt-4">
    <p class="mb-0">Don't have an account? <a href="/auth/register" class="text-decoration-none">Sign up</a></p>
</div>
