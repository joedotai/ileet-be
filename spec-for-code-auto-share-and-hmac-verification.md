# Specification: IP-Based Code Auto-Login And Handoff

## 1. Feature Overview

The goal is to let an examinee move from `exam-runner` into the top-level Laravel app with as little manual copying as possible.

After the examinee finishes or prepares code in `exam-runner`, they click a share or handoff button. The runner opens a new browser tab to a Laravel route. The Laravel app receives the examinee email and code payload, determines the examinee's IP address from the request, runs human verification, and prepares a submission screen where the decoded code is already filled into a textbox.

The examinee should only need to provide or confirm their email when needed, then submit the code.

## 2. Intended Flow

```txt
Examinee writes code in exam-runner
-> examinee clicks "share code" or "handoff code"
-> exam-runner base64-encodes the code
-> exam-runner base64-encodes the examinee email when available
-> exam-runner opens a new tab to the Laravel handoff page
-> Laravel reads the request IP using $request->ip()
-> Laravel runs verify-human bot protection
-> Laravel decodes the payload into a code textbox
-> Laravel finds or creates the user account by email
-> examinee confirms or enters email if missing
-> examinee submits the code into Laravel
```

## 3. Application Boundaries

```txt
exam-runner
- owns the coding interface
- owns the current in-browser code draft
- sends the code and optional email to Laravel during handoff
- does not own Laravel sessions

Laravel app
- owns user accounts
- owns human verification
- determines request IP
- receives and decodes the handoff payload
- creates or reuses the user account by email
- stores the final submitted code
- enforces submission visibility and review access
```

The two apps should communicate through explicit handoff requests. They should not rely on shared browser storage, shared cookies, or iframe behavior.

## 4. Handoff Entry Point

Laravel should expose a dedicated public route for the handoff.

Suggested route:

```txt
POST /exam-handoff
```

The route should accept:

```txt
code_b64      required string
email_b64     nullable string
exam_id       nullable string
runner_id     nullable string
handoff_token recommended string
human_token   required string
```

The preferred implementation is an automatic `POST` from the opened tab. If the runner first opens a new tab with a small HTML form, that form can immediately submit itself to Laravel using `POST`.

Avoid placing the base64 code payload in the URL query string. URLs are commonly stored in browser history, logs, analytics, and referrer headers.

## 5. IP Address Capture

Laravel determines the examinee IP address from the incoming request:

```php
$ip = $request->ip();
```

The handoff page may display this IP address in the top-right corner for visibility and debugging.

Display requirements:

- Show the detected IP address in the top-right of the handoff page.
- Make it clear that the IP is the server-observed request IP.
- Do not allow the client to provide or override the IP address.
- Store a hashed version of the IP for matching or auditing unless raw IP storage is required.

Important limitation:

IP address is not a stable identity. Multiple examinees may share one IP, and one examinee may change IPs. IP matching can support handoff context, but it must not be the only proof that the user owns an account or submission.

## 6. Base64 Payload Handling

The runner sends code and email as base64-encoded values.

Example payload fields:

```json
{
  "code_b64": "ZnVuY3Rpb24gc29sdmUoKSB7IH0=",
  "email_b64": "ZXhhbWluZWVAY29tcGFueS5jb20="
}
```

Laravel behavior:

- Decode `code_b64` server-side.
- Decode `email_b64` server-side when present.
- Validate decoded email format before using it.
- Put decoded code into the submission textbox.
- Escape code when rendering it back into HTML.
- Reject payloads that are too large.
- Reject invalid base64 payloads.

Base64 is only encoding. It is not encryption and does not protect the contents from anyone who can see the request.

## 7. User Account Creation

When a valid decoded email is available, Laravel should find or create the corresponding user account.

Required behavior:

- Email is the primary identity field.
- If the email exists, reuse the existing user.
- If the email does not exist, create a new user.
- The created account should be marked as originating from the handoff flow when useful for audit.
- The user should not gain administrator privileges from this flow.

Suggested behavior:

- If the user already has a valid Laravel session for the same email, continue directly to the prefilled submission page.
- If there is no valid session, require an email verification or OTP step before final submission.
- If the decoded email is missing or invalid, ask the examinee to enter their email manually.

## 8. Auto-Login Rule

The desired experience is automatic account availability after handoff. The Laravel app can automatically create or locate the account, but it should be careful about automatically authenticating the browser session.

Recommended rule:

```txt
IP match + base64 email + verify-human = account discovery and prefilled submission
IP match + base64 email + verify-human + valid OTP/session = authenticated submission
```

Do not treat the IP address and base64 email alone as strong authentication. A safer implementation can still feel automatic by creating the account immediately, preloading the code textbox, and then requiring only a short verification step before the final submit.

As an alternative to OTP, the handoff can use an HMAC-SHA signed payload similar in spirit to OAuth-style signed requests. `exam-runner` and Laravel would share a server-side secret, and the runner would include a timestamp, nonce, email, code hash, and HMAC signature with the handoff request. Laravel would recompute the HMAC before accepting the request, reject expired timestamps, and reject reused nonces. If the signature is valid and the human verification passes, Laravel may treat the handoff as authenticated for the specific examinee email and submission action.

## 9. Verify-Human Protection

The handoff endpoint must be protected by a verify-human bot check.

Requirements:

- Require a valid `human_token` on handoff POST.
- Verify the token server-side before decoding and storing sensitive payloads.
- Rate-limit failed handoff attempts by IP and email.
- Rate-limit successful handoff creation by IP to prevent spam.
- Log verification failures without storing submitted code.

Any provider can be used, but the verification decision must happen server-side in Laravel.

## 10. Temporary Handoff Storage

After the initial `POST`, Laravel may store the decoded handoff data temporarily while the examinee completes email confirmation or OTP.

Suggested cache shape:

```txt
Key: exam_code_handoff:{handoff_id}
TTL: 30 minutes

Fields:
- handoff_id
- ip_hash
- email
- exam_id
- runner_id
- code_text
- created_at
- human_verified_at
```

Storage requirements:

- Use a random `handoff_id`.
- Keep the TTL short.
- Do not expose sequential identifiers.
- Store raw code only as long as needed before final submission.
- Delete the temporary handoff after successful submission.

## 11. Submission Page

After the handoff is accepted, Laravel shows a submission page.

The page should include:

- Detected IP address in the top-right corner.
- Email field when email is missing or needs confirmation.
- Prefilled code textbox using the decoded code.
- Hidden handoff identifier.
- Human verification state or a new verification challenge if required.
- Submit button.

The examinee can review and edit the code before submitting.

## 12. Final Submission

Suggested route:

```txt
POST /exam-submissions
```

The final submission should store:

```txt
submissions
- id
- examinee_user_id
- examinee_email
- exam_id
- runner_id
- request_ip_hash
- code_text
- submitted_at
- created_at
- updated_at
```

Submission requirements:

- Store code as plain text.
- Escape code on display.
- Associate the submission with the examinee user.
- Record the decoded email snapshot.
- Record the IP hash used during handoff.
- Prevent duplicate submissions from the same handoff unless explicitly allowed.

## 13. Security Requirements

- Use `POST` for the code handoff.
- Do not put code payloads in query strings.
- Treat base64 as transport encoding only, not security.
- Validate decoded email before account lookup or creation.
- Limit maximum code payload size.
- Rate-limit handoff and submission routes.
- Require verify-human protection before accepting a handoff.
- Do not grant administrator access through this flow.
- Do not render submitted code as raw HTML.
- Do not execute submitted code in the Laravel app.
- Do not authenticate a user solely because they know an email and share an IP address.

## 14. Acceptance Criteria

- `exam-runner` can open a new tab that posts code to Laravel.
- Laravel determines the request IP using `$request->ip()`.
- The handoff page displays the detected IP address in the top-right corner.
- Laravel accepts a base64-encoded code payload.
- Laravel accepts a base64-encoded email payload when provided.
- Laravel decodes the code and preloads it into a textbox.
- Laravel finds or creates an examinee account from a valid decoded email.
- If email is missing, Laravel asks the examinee for email.
- Verify-human protection runs before accepting handoff data.
- The examinee can submit the prefilled code.
- The final submission is associated with the examinee account and request IP hash.
- Invalid base64, invalid email, oversized payloads, and bot-verification failures are rejected.
