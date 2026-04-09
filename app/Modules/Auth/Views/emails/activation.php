
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4e73df; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .button { display: inline-block; background: #4e73df; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Activate Your Account</h1>
        </div>

        <div class="content">
            <p>Hello <?= esc($username) ?>,</p>

            <p>Thank you for registering! To complete your registration, please activate your account by clicking the button below:</p>

            <p style="text-align: center;">
                <a href="<?= site_url("/auth/activate/{$token}") ?>" class="button">Activate Account</a>
            </p>

            <p>Or copy and paste this link into your browser:</p>
            <p><code><?= site_url("/auth/activate/{$token}") ?></code></p>

            <p>This link will expire in 24 hours for security reasons.</p>

            <p>If you did not create this account, please ignore this email.</p>
        </div>

        <div class="footer">
            <p><?= config('App')->appName ?? 'CIMembership' ?></p>
        </div>
    </div>
</body>
</html>
