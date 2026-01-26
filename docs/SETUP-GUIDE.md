# Step-by-Step Setup Guide (For Non-Developers)

This guide walks you through completing the manual setup tasks. Follow each step exactly.

---

## Prerequisites

Before starting, make sure you have:
- [ ] Your computer with the Uffizi-Ticket-App folder
- [ ] Internet connection
- [ ] Access to GitHub (https://github.com/DhaNu1204/uffizi-tickets-app)
- [ ] SSH access to production server (Hostinger)
- [ ] Sentry account (we'll create one if you don't have it)

---

## TASK 1: Install Sentry SDK (5 minutes)

### What is this?
Sentry is a tool that catches errors in your app and notifies you. We need to install it.

### Steps:

**Step 1.1:** Open Command Prompt or Terminal
- On Windows: Press `Win + R`, type `cmd`, press Enter
- Or: Search for "Command Prompt" in Start menu

**Step 1.2:** Navigate to the backend folder
```
cd D:\Uffizi-Ticket-App\backend
```

**Step 1.3:** Run the install command
```
composer require sentry/sentry-laravel
```

**Step 1.4:** Wait for it to finish
- You'll see text scrolling
- Wait until you see your command prompt again (the blinking cursor)
- This takes about 1-2 minutes

### What success looks like:
```
Using version ^4.x for sentry/sentry-laravel
./composer.json has been updated
Running composer update sentry/sentry-laravel
...
Package manifest generated successfully.
```

### If you see an error:
- If it says "composer is not recognized", you need to install Composer first
- Ask me and I'll help you fix it

---

## TASK 2: Create Sentry Account & Get DSN (10 minutes)

### What is DSN?
DSN is like an address where Sentry sends error reports. We need two: one for frontend, one for backend.

### Steps:

**Step 2.1:** Go to Sentry website
- Open your browser
- Go to: https://sentry.io/signup/

**Step 2.2:** Create account (if you don't have one)
- Click "Create your account"
- Use your email or sign up with GitHub
- Verify your email if asked

**Step 2.3:** Create a new project for BACKEND (Laravel)
- After login, click "Create Project" (or it may ask you automatically)
- Select platform: Search for "Laravel" and click it
- Project name: `uffizi-backend`
- Click "Create Project"

**ALREADY DONE!** You already have a Sentry project configured. Your DSN is:
```
https://a5fbe3a90488b796d6f2936a60d966bc@o4510711031201792.ingest.de.sentry.io/4510761996058704
```

This same DSN is used for both frontend (React) and backend (Laravel).

---

## TASK 3: Configure GitHub Secrets (10 minutes)

### What are GitHub Secrets?
These are secure passwords stored on GitHub that the deployment system uses.

### Steps:

**Step 3.1:** Go to your GitHub repository
- Open browser: https://github.com/DhaNu1204/uffizi-tickets-app

**Step 3.2:** Go to Settings
- Click "Settings" tab (near the top, next to "Insights")
- If you don't see it, you may not have admin access

**Step 3.3:** Go to Secrets
- In the left sidebar, click "Secrets and variables"
- Then click "Actions"

**Step 3.4:** Add each secret
For each secret below, click "New repository secret" and fill in:

| Secret Name | Value to Enter |
|------------|----------------|
| `SSH_HOST` | `82.25.82.111` |
| `SSH_PORT` | `65002` |
| `SSH_USER` | `u803853690` |
| `DEPLOY_PATH` | `/home/u803853690/domains/deetech.cc/public_html/uffizi` |
| `SENTRY_DSN_BACKEND` | `https://a5fbe3a90488b796d6f2936a60d966bc@o4510711031201792.ingest.de.sentry.io/4510761996058704` |
| `SENTRY_DSN_FRONTEND` | `https://a5fbe3a90488b796d6f2936a60d966bc@o4510711031201792.ingest.de.sentry.io/4510761996058704` |

**Step 3.5:** Add SSH Private Key (This is the tricky one)

You need to generate an SSH key pair. Here's how:

**On Windows:**
1. Open Command Prompt
2. Run:
   ```
   ssh-keygen -t ed25519 -C "github-deploy@uffizi"
   ```
3. When asked "Enter file in which to save the key", press Enter for default
4. When asked for passphrase, press Enter twice (no passphrase)
5. Now you have two files:
   - `C:\Users\YOUR_USERNAME\.ssh\id_ed25519` (private key)
   - `C:\Users\YOUR_USERNAME\.ssh\id_ed25519.pub` (public key)

6. Open the PRIVATE key file:
   ```
   notepad C:\Users\YOUR_USERNAME\.ssh\id_ed25519
   ```
7. Copy ALL the text (including the BEGIN and END lines)
8. Go back to GitHub Secrets
9. Create new secret: Name = `SSH_PRIVATE_KEY`, Value = paste the private key

**Step 3.6:** Add public key to Hostinger
1. Open the PUBLIC key file:
   ```
   notepad C:\Users\YOUR_USERNAME\.ssh\id_ed25519.pub
   ```
2. Copy the entire line
3. Connect to your Hostinger server:
   ```
   ssh -p 65002 u803853690@82.25.82.111
   ```
4. Run these commands:
   ```
   mkdir -p ~/.ssh
   nano ~/.ssh/authorized_keys
   ```
5. Paste the public key, then press Ctrl+X, then Y, then Enter
6. Type `exit` to disconnect

---

## TASK 4: Update Production Server (10 minutes)

### What is this?
We need to add the Sentry DSN to the production server's configuration.

### Steps:

**Step 4.1:** Connect to production server
Open Command Prompt and run:
```
ssh -p 65002 u803853690@82.25.82.111
```
Enter your password when asked.

**Step 4.2:** Go to the backend folder
```
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend
```

**Step 4.3:** Edit the .env file
```
nano .env
```

**Step 4.4:** Add these lines at the end of the file
Use arrow keys to scroll to the bottom, then add:
```
# Sentry Error Tracking
SENTRY_LARAVEL_DSN=https://a5fbe3a90488b796d6f2936a60d966bc@o4510711031201792.ingest.de.sentry.io/4510761996058704

# Logging Configuration
LOG_STACK=single,sentry
LOG_REQUESTS=false
```

**Step 4.5:** Save and exit
- Press `Ctrl + X`
- Press `Y` (for yes)
- Press `Enter`

**Step 4.6:** Clear the cache
```
/opt/alt/php82/usr/bin/php artisan config:clear
/opt/alt/php82/usr/bin/php artisan cache:clear
```

**Step 4.7:** Disconnect
```
exit
```

---

## TASK 5: Create Local Settings File (2 minutes)

### What is this?
This creates a file on your computer to store your local passwords (not uploaded to GitHub).

### Steps:

**Step 5.1:** Open Command Prompt
```
cd D:\Uffizi-Ticket-App
```

**Step 5.2:** Copy the example file
```
copy CLAUDE.local.md.example CLAUDE.local.md
```

**Step 5.3:** Edit the file
- Open the file in Notepad:
  ```
  notepad CLAUDE.local.md
  ```
- Replace `YOUR_LOCAL_PASSWORD_HERE` with your actual MySQL password
- Replace `PRODUCTION_PASSWORD_HERE` with your Hostinger database password
- Save and close (Ctrl+S, then close window)

---

## TASK 6: Run Tests (5 minutes)

### What is this?
This checks that everything is working correctly.

### Steps:

**Step 6.1:** Open Command Prompt
```
cd D:\Uffizi-Ticket-App\backend
```

**Step 6.2:** Run the tests
```
php artisan test
```

**Step 6.3:** Check the results
You should see something like:
```
   PASS  Tests\Unit\BokunServiceTest
   ✓ it returns uffizi product ids from config
   ✓ verify webhook signature returns false when hmac header missing
   ...

  Tests:    55 passed
  Duration: 5.23s
```

### If tests fail:
- Some failures are OK if they need real API credentials
- If many tests fail, let me know the error messages

---

## TASK 7: Verify Everything Works (5 minutes)

### Step 7.1: Test the website
1. Open browser: https://uffizi.deetech.cc
2. Login with your credentials
3. Make sure you can see bookings

### Step 7.2: Test Sentry
1. Go to https://sentry.io
2. Click on your `uffizi-backend` project
3. You should see the dashboard (may be empty if no errors yet)

### Step 7.3: Trigger a test error (optional)
Connect to production:
```
ssh -p 65002 u803853690@82.25.82.111
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend
/opt/alt/php82/usr/bin/php artisan tinker
```
Then type:
```php
Sentry\captureMessage('Test from Uffizi app setup');
exit
```
Check Sentry dashboard - you should see the test message!

---

## Summary Checklist

- [ ] Task 1: Sentry SDK installed (ran composer require)
- [ ] Task 2: Sentry account created, have both DSNs
- [ ] Task 3: GitHub secrets configured (7 secrets)
- [ ] Task 4: Production .env updated with Sentry DSN
- [ ] Task 5: CLAUDE.local.md created with your passwords
- [ ] Task 6: Tests pass (or mostly pass)
- [ ] Task 7: Website still works, Sentry dashboard accessible

---

## Need Help?

If you get stuck on any step:
1. Copy the exact error message you see
2. Tell me which step you're on
3. I'll help you fix it!

Common issues:
- "composer not recognized" → Need to install Composer
- "ssh: connect refused" → Check your internet/VPN
- "permission denied" → Password might be wrong
- Tests failing → Some failures are OK, show me the output
