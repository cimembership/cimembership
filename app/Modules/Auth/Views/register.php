
<h2 class="text-center mb-4">Create Account</h2>

<form action="/auth/register" method="post">
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-md-6">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name"
                       value="<?= old('first_name') ?>">
                <label for="first_name">First Name</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name"
                       value="<?= old('last_name') ?>">
                <label for="last_name">Last Name</label>
            </div>
        </div>
    </div>

    <div class="form-floating mb-3">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required
               value="<?= old('username') ?>">
        <label for="username"><i class="fas fa-user me-2"></i>Username</label>
    </div>

    <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
               value="<?= old('email') ?>">
        <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
    </div>

    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required
               minlength="8">
        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
        <div class="form-text">At least 8 characters</div>
    </div>

    <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm Password" required>
        <label for="password_confirm"><i class="fas fa-lock me-2"></i>Confirm Password</label>
    </div>

    <?php if ($captcha ?? false): ?>
        <div class="mb-3 text-center">
            <img src="<?= $captchaImage ?>" alt="Captcha" class="img-fluid rounded mb-2">
            <input type="text" class="form-control" name="captcha" placeholder="Enter captcha code" required>
        </div>
    <?php endif; ?>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
        <label class="form-check-label" for="terms">
            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
        </label>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
        <i class="fas fa-user-plus me-2"></i>Create Account
    </button>
</form>

<?php if (!empty($oauth)): ?>
    <div class="divider">
        <span>OR</span>
    </div>

    <div class="d-grid gap-2">
        <?php foreach ($oauth as $key => $name): ?>
            <a href="/auth/oauth/<?= $key ?>" class="btn btn-outline-secondary oauth-btn">
                <i class="fab fa-<?= $key ?> me-2"></i>Sign up with <?= $name ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="text-center mt-4">
    <p class="mb-0">Already have an account? <a href="/auth/login" class="text-decoration-none">Sign in</a></p>
</div>
