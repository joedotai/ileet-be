# Laravel App Specification: Passwordless Exam Submission Handoff

## 1. Feature Overview

The Laravel app is responsible for identity, authorization, submission storage, and administrator review. The separate `exam-runner` app remains responsible for running the coding exercise and collecting the examinee's code.

The intended flow is:

```txt
Examinee uses exam-runner
-> exam-runner records a short-lived IP handoff in Redis
-> examinee opens Laravel app in a separate tab
-> Laravel auto-detects handoff by IP when possible
-> otherwise Laravel asks examinee to log in by email OTP
-> examinee paste-submits plain text code into Laravel
-> Laravel stores the submission for the intended administrator only
```

The Laravel app must support creating and authenticating a user by email without a password. Authentication is performed through a passwordless one-time password mechanism.

## 2. Application Boundaries

Keep the apps separate:

```txt
exam-runner
- runs coding exercise
- may keep draft code in its own browser storage
- may place temporary handoff data in Redis
- does not own Laravel session state

Laravel app
- owns users, OTP login, sessions, submissions, and review access
- stores submitted plain text code
- stores intended administrator email per submission
- enforces that only the examinee and intended administrator can view a submission
```

The Laravel app must not rely on shared `localStorage` or iframe cookies. The user should open Laravel in a separate tab when a login or submission step is required.

## 3. Passwordless User Creation And Login

### A. User Creation By Email

When a person enters an email address, Laravel must find or create a user record for that email.

Required behavior:

- No password is required.
- Email is the primary identity field.
- If the email does not exist, create a new user.
- If the email already exists, reuse the existing user.
- User login must not be completed until the OTP is verified.

### B. OTP Login

Laravel must send a one-time password to the user's email address.

Required behavior:

- OTPs must be short-lived.
- OTPs must be single-use.
- OTP values must be stored hashed, not in plain text.
- OTP requests must be rate-limited by email and IP.
- Successful OTP verification logs the user into the Laravel app.
- Failed OTP attempts must be limited to prevent guessing.

Suggested fields for an OTP table:

```txt
login_otps
- id
- email
- otp_hash
- expires_at
- consumed_at
- attempt_count
- request_ip
- created_at
- updated_at
```

## 4. IP-Based Handoff From Exam Runner

The first app, `exam-runner`, may write a temporary handoff record to Redis when it knows that an examinee should be allowed through the Laravel submission flow.

Suggested Redis shape:

```txt
Hash key: exam_handoff:{ip_hash}
TTL: 24 hours

Fields:
- email
- exam_id
- admin_email
- runner_session_id
- created_at
```

The IP address should be normalized and hashed before being used as a Redis key. Do not store raw IP addresses unless needed for audit or abuse prevention.

Laravel behavior:

- When the user opens the Laravel app, derive the same IP hash from the request IP.
- Check Redis for `exam_handoff:{ip_hash}`.
- If a valid handoff exists and identifies the expected email, Laravel may start or continue the passwordless login flow for that email.
- If the user is already authenticated as the same email, Laravel may take the user directly to the paste submission page.
- If the user is unauthenticated, Laravel may either auto-start OTP delivery or show the email-confirmation page before sending OTP.
- If no valid handoff exists, ask the person to log in through the normal passwordless OTP flow in a separate tab.

Important security rule:

IP handoff alone must not be treated as full authentication. It may be used to locate the intended exam context and reduce friction, but the Laravel session should still be established through OTP unless the user already has a valid Laravel session.

## 5. Paste Submission Flow

After login, Laravel must show a plain-text submission page. The examinee copies code from `exam-runner` and pastes it into this Laravel page.

The page must collect or infer:

- Examinee user identity.
- Examinee email.
- Intended administrator email.
- Exam or runner session identifier when available.
- Plain text code.

Submission requirements:

- Store the pasted code as plain text for administrator perusal.
- Escape code when displaying it. Never render submitted code as HTML.
- Treat submitted code as confidential data.
- Allow the examinee to view their own submission.
- Allow only the intended administrator to view the submission after authenticating.
- Do not expose the submitted code to other authenticated users.

## 6. Submission Access Control

Submitted code must be secretly available only to both parties:

```txt
allowed viewers:
- the examinee who submitted it
- the administrator whose email is attached to that submission

not allowed:
- unrelated logged-in users
- administrators with a different email
- anyone holding only a generic Laravel login
```

Authorization should compare the authenticated user's email or user id against the submission ownership fields.

Recommended checks:

- Examinee access: `submission.examinee_user_id === auth()->id()`.
- Administrator access: `submission.admin_email === auth()->user()->email`.
- If administrator accounts have explicit roles, require both matching email and appropriate role.

## 7. Database Migration Requirements

Update the submission migration so each saved submission records both the intended administrator and the examinee.

Suggested `submissions` fields:

```txt
submissions
- id
- examinee_user_id
- examinee_email
- admin_email
- exam_id
- runner_session_id
- code_text
- submitted_at
- created_at
- updated_at
```

Field notes:

- `examinee_user_id` should reference `users.id`.
- `examinee_email` should snapshot the examinee email at submission time.
- `admin_email` is required and identifies who may review the code.
- `code_text` stores the pasted plain text.
- `exam_id` and `runner_session_id` are nullable unless the runner always supplies them.

Indexes:

```txt
- index(examinee_user_id)
- index(examinee_email)
- index(admin_email)
- index(exam_id)
- index(runner_session_id)
```

## 8. Review Flow

The administrator must log in through the same passwordless OTP mechanism before reviewing submissions.

Administrator behavior:

- Admin enters their email.
- Laravel sends OTP.
- After OTP verification, Laravel shows only submissions where `submissions.admin_email` matches the authenticated email.
- Opening a submission displays a read-only view of the pasted code and metadata.

The review page should show:

- Examinee email.
- Administrator email.
- Exam identifier when present.
- Runner session identifier when present.
- Submission timestamp.
- Plain text code in a safe escaped block.

## 9. Security Requirements

Required protections:

- Never use browser `eval`, `Function`, or direct DOM execution for submitted code in Laravel.
- Never render submitted code with raw HTML output.
- OTPs must be hashed, expiring, single-use, and rate-limited.
- IP handoff records must expire after 24 hours.
- Redis handoff data must not grant review access by itself.
- Submission authorization must be enforced server-side on every read route.
- A logged-in user must not be able to enumerate or access submissions outside their examinee/admin relationship.

## 10. Acceptance Criteria

- A new user can be created with only an email address.
- A user can log in with an emailed OTP and no password.
- Laravel checks Redis for a 24-hour IP handoff from `exam-runner`.
- If a matching handoff and active Laravel session exist, the user is sent to the paste submission page.
- If no active session exists, the user is asked to complete OTP login in a separate tab.
- A logged-in examinee can paste code into Laravel and submit it.
- Each submission records the examinee and the intended administrator email.
- The examinee can view their submitted code.
- The intended administrator can log in and view that submission.
- Other authenticated users cannot view the submitted code.
