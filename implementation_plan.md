# Implementation Plan - Cleaning Hardcoded Secrets

This plan describes the proposed changes to clean hardcoded secrets (Gemini API key, Stripe API keys, and database credentials) from the workspace.

## Proposed Changes

### Configuration and Database Credentials

#### [MODIFY] [config.php](file:///c:/Users/bar45/Desktop/College/shareit_project/logic/config.php)
- Replace database connection parameters (`DB_USER`, `DB_PASS`, `DB_NAME`) with placeholders: `'YOUR_DB_USER_HERE'`, `'YOUR_DB_PASS_HERE'`, `'YOUR_DB_NAME_HERE'`.
- Replace the Gemini API key (`GEMINI_API_KEY`) with placeholder: `'YOUR_GEMINI_KEY_HERE'`.

#### [MODIFY] [db_config.php](file:///c:/Users/bar45/Desktop/College/shareit_project/logic/db_config.php)
- Replace variables `$username`, `$password`, `$dbname` with placeholders: `'YOUR_DB_USER_HERE'`, `'YOUR_DB_PASS_HERE'`, `'YOUR_DB_NAME_HERE'`.

---

### Stripe Integration Files

#### [MODIFY] [auth_register.php](file:///c:/Users/bar45/Desktop/College/shareit_project/api/auth_register.php)
- Replace the hardcoded test Stripe key inside `\Stripe\Stripe::setApiKey()` with placeholder: `'YOUR_STRIPE_KEY_HERE'`.

#### [MODIFY] [start_stripe_payment.php](file:///c:/Users/bar45/Desktop/College/shareit_project/api/start_stripe_payment.php)
- Replace the hardcoded `$apiKey` value with placeholder: `'YOUR_STRIPE_KEY_HERE'`.

---

## Verification Plan

### Manual Verification
- We will verify that code structure and syntax remain identical (valid PHP syntax) and only the string literals are replaced by placeholders.
- Verify that no logic or control flow was modified.
