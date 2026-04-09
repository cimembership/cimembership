# Upgrade Guide: v3 to v4

This guide helps you migrate from CIMembership v3 (CodeIgniter 3) to v4 (CodeIgniter 4).

## Important Notes

⚠️ **Before You Begin:**

1. **Backup everything** - Database, files, and configurations
2. Test the upgrade on a staging environment first
3. Plan for downtime during the migration
4. Keep v3 running until v4 is fully tested

## What's Different?

| Feature | v3 (CI3) | v4 (CI4) |
|---------|----------|----------|
| PHP Version | 7.0+ | 8.1+ |
| Framework | CodeIgniter 3 | CodeIgniter 4 |
| Namespacing | No | Yes (PSR-4) |
| Password Hashing | phpass | bcrypt |
| Database | CI DB class | Query Builder |
| Modules | HMVC | Namespaced Controllers |
| Installation | Web-based | CLI-based |

## Pre-Upgrade Checklist

- [ ] Backup your current database
- [ ] Backup all application files
- [ ] Export user passwords (optional, for verification)
- [ ] Note current configuration values
- [ ] Document custom modifications
- [ ] Verify PHP 8.1+ is available

## Migration Steps

### Step 1: Backup Current Installation

```bash
# Backup database
mysqldump -u root -p old_cimembership_db > cimembership_v3_backup.sql

# Backup files
tar -czvf cimembership_v3_files.tar.gz /var/www/cimembership_v3/
```

### Step 2: Install CIMembership v4

Follow the [Installation Guide](INSTALL.md) to install v4 in a new directory:

```bash
# Install v4 in new directory
cd /var/www
composer create-project cimembership/cimembership cimembership_v4
cd cimembership_v4
```

### Step 3: Create v4 Database

```sql
-- Create new database for v4
CREATE DATABASE cimembership_v4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 4: Run v4 Migrations

```bash
cd /var/www/cimembership_v4
php spark migrate
```

### Step 5: Run Upgrade Command

```bash
php spark upgrade:fromv3 \
    --source-db=old_cimembership_db \
    --source-host=localhost \
    --source-user=root \
    --source-pass=your_password \
    --source-prefix=ci_
```

The upgrade command will migrate:
- User groups (custom groups only, system groups recreated)
- Users (with migration flag on passwords)
- User profiles
- OAuth connections
- Options/settings

### Step 6: Review and Test

1. **Verify user counts match**
   ```sql
   -- In old database
   SELECT COUNT(*) FROM old_cimembership_db.ci_users;

   -- In new database
   SELECT COUNT(*) FROM cimembership_v4.ci_users;
   ```

2. **Test login with existing accounts**
3. **Verify permissions are correct**
4. **Check OAuth connections work**
5. **Test admin functions**

### Step 7: Configure v4

1. Copy custom configurations to v4 `.env`
2. Set up email configuration
3. Re-configure OAuth providers (update redirect URLs)
4. Update any custom settings

### Step 8: Switch Over

Once testing is complete:

```bash
# Rename directories
mv /var/www/cimembership /var/www/cimembership_v3_archive
mv /var/www/cimembership_v4 /var/www/cimembership

# Update web server config to point to new location
# Restart web server
sudo systemctl restart apache2
```

## Password Migration

### How It Works

Since CI3 used phpass and CI4 uses bcrypt:

1. During upgrade, passwords are marked with `legacy:` prefix
2. When user logs in, the password is verified
3. If valid, the password is rehashed with bcrypt
4. User continues with new hash

### Password Reset Option

Alternatively, you can force all users to reset passwords:

```sql
-- After upgrade, send password reset to all users
-- This ensures all passwords use new hashing
```

## What Gets Migrated

### ✅ Migrated

- User accounts (usernames, emails, statuses)
- User groups (custom groups only)
- User profiles
- OAuth connections (converted to new format)
- Options/settings (compatible settings)

### ⚠️ Requires Manual Configuration

- OAuth app credentials (redirect URLs changed)
- Email settings (format changed)
- Custom themes (must be rebuilt for CI4)
- Custom modules (must be rewritten)
- Captcha keys

### ❌ Not Migrated

- CI Sessions (will be recreated)
- Login attempt history (logged for security)
- Theme customizations
- Custom code modifications

## Post-Upgrade Tasks

### 1. Update OAuth Redirect URLs

In each OAuth app console, update redirect URLs:

```
Old: http://example.com/auth/oauth/facebook
New: https://example.com/auth/oauth/facebook/callback
```

### 2. Rebuild Custom Themes

CI4 uses different templating:

```php
// CI3
$this->load->view('my_view', $data);

// CI4
return view('MyView', $data);
```

### 3. Migrate Custom Code

Custom controllers/models must be rewritten for CI4:

- Use namespaces
- Extend CI4 base classes
- Update database queries
- Follow CI4 conventions

### 4. Update Cron Jobs

```bash
# Old (CI3)
* * * * * php /var/www/cimembership/index.php cron run

# New (CI4)
* * * * * cd /var/www/cimembership && php spark cron:run
```

## Troubleshooting

### Users Can't Log In

**Cause:** Legacy password hash not working

**Solution:**
1. Check user has `legacy:` prefix in password_hash
2. Verify phpass compatibility layer is working
3. Consider sending password resets to all users

### OAuth Connections Not Working

**Cause:** Redirect URL mismatch or token format change

**Solution:**
1. Update redirect URLs in OAuth app settings
2. Re-link OAuth accounts if needed
3. Check tokens are properly migrated

### Missing Data

**Cause:** Migration incomplete or errors

**Solution:**
1. Check migration logs: `writable/logs/`
2. Re-run upgrade with `--dry-run` first
3. Manually import missing data if needed

### Custom Modules Broken

**Cause:** CI3 modules incompatible with CI4

**Solution:**
1. Rewrite modules using CI4 structure
2. Use namespaced controllers
3. Update database queries

## Rollback Plan

If upgrade fails:

```bash
# Restore v3
cd /var/www
mv cimembership cimembership_v4_failed
mv cimembership_v3_archive cimembership

# Restore database
mysql -u root -p old_cimembership_db < cimembership_v3_backup.sql

# Restart web server
sudo systemctl restart apache2
```

## Getting Help

- [Upgrade Discussion Forum](https://github.com/cimembership/cimembership/discussions/categories/upgrades)
- [Common Issues](https://docs.cimembership.com/upgrade-issues)
- [Professional Support](https://cimembership.com/support)

## FAQ

**Q: Can I upgrade directly from v1.x or v2.x?**

A: No. First upgrade to v3, then to v4.

**Q: Will my users need to reset passwords?**

A: Not necessarily. The upgrade includes legacy password support. However, password resets ensure all users are using the new secure hashing.

**Q: Can I run v3 and v4 side by side?**

A: Yes, temporarily. Use different databases and different subdirectories. However, don't run them on the same domain simultaneously.

**Q: How long does the upgrade take?**

A: Depends on your database size:
- < 1000 users: ~5 minutes
- 1000-10000 users: ~15 minutes
- > 10000 users: ~30+ minutes

**Q: What happens to banned users?**

A: Banned users are migrated with status 'banned'. Ban reasons are preserved.

---

Need more help? Visit our [documentation](https://docs.cimembership.com) or [community forum](https://github.com/cimembership/cimembership/discussions).
