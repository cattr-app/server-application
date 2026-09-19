# Security Policy

Thank you for helping keep Cattr and its users secure.

## Supported Versions

Cattr provides security support for the two most recent stable minor release lines.

A minor release line means a `MAJOR.MINOR.x` series. For example, if the latest stable releases are `4.2.x` and `4.1.x`, both lines are supported. Once a newer stable minor line is released, the oldest supported line becomes unsupported.

When only one stable minor release line exists, only that line is supported.

| Version                                                           | Supported          |
|-------------------------------------------------------------------|--------------------|
| Latest stable minor release line                                  | :white_check_mark: |
| Previous stable minor release line, if one exists                 | :white_check_mark: |
| Older stable releases                                             | :x:                |
| Development branches, including `main`                            | :x:                |
| Alpha, beta, RC, preview, nightly, and other pre-release versions | :x:*               |

\* Vulnerability reports affecting a current pre-release version may still be accepted if the issue is relevant to an upcoming stable release. Pre-release builds are provided without any guarantee of stability or security support.

Security fixes are not guaranteed to be backported to unsupported versions.

Users are strongly encouraged to run the latest stable release.

## Reporting a Vulnerability

Do **not** report undisclosed security vulnerabilities through public GitHub Issues, Discussions, pull requests, or other public channels.

Please use GitHub's **Private Vulnerability Reporting** feature:

1. Open the repository's **Security** page.
2. Select **Report a vulnerability**.
3. Submit the report using the private vulnerability reporting form.

Please include enough information for the maintainers to understand and reproduce the issue whenever possible, including:

- the affected Cattr version;
- the affected component or endpoint;
- prerequisites required to exploit the vulnerability;
- reproduction steps or a proof of concept;
- the expected and actual behavior;
- the potential security impact;
- any known mitigations or workarounds.

Reports based only on automated scanner output, without enough information to reproduce or demonstrate a security impact, may be declined.

## Response and Handling

Cattr is a community-maintained open-source project and does not provide a guaranteed security response or remediation SLA.

Maintainer availability may vary significantly.

We aim to make an initial determination on a vulnerability report within **90 days**, but this is a target rather than a guaranteed response deadline.

A report may be:

- **accepted**, if the issue is considered a security vulnerability affecting Cattr;
- **declined**, if it is not considered a vulnerability, cannot be reproduced, is outside the scope of this policy, or affects only unsupported versions;
- kept under investigation if additional information or validation is required.

If a vulnerability is accepted, maintainers may use a GitHub Security Advisory to coordinate the fix and disclosure.

Where appropriate, maintainers may also request a CVE identifier through GitHub.

## Coordinated Disclosure

Please keep vulnerability information private while the report is being evaluated.

Do not publicly disclose the vulnerability, proof of concept, exploit details, or information that would allow others to reproduce the issue during the initial review period.

The 90-day review target is **not** an automatic disclosure deadline.

If a report is accepted, the vulnerability must remain private until:

- the maintainers publish the corresponding security advisory; or
- a maintainer explicitly authorizes disclosure.

Publication timing may depend on the availability of a fix, affected release preparation, and maintainer availability.

If a report is declined, this coordinated disclosure restriction no longer applies, although reporters remain responsible for avoiding disclosure of private user data, credentials, third-party confidential information, or information obtained without authorization.

## Testing Guidelines

Security testing must be performed only against systems and Cattr installations that you own or for which you have explicit permission to test.

Do not perform security testing against:

- installations operated by other Cattr users;
- infrastructure that you do not own or control;
- Cattr project infrastructure without explicit authorization;
- `demo.cattr.app`.

The public Cattr demo is **not a security testing environment**. It may run modified code and is not guaranteed to correspond to the current source code or any supported Cattr release.

Do not perform denial-of-service, resource-exhaustion, destructive, or disruptive testing against public or third-party infrastructure.

## Scope

A report is generally considered in scope when it demonstrates a security impact in a supported stable version of `cattr-app/server-application`.

Reports affecting a current alpha, beta, RC, or other pre-release version may also be considered when the issue is likely to affect a future stable release.

Examples of potentially valid security issues include, but are not limited to:

- authentication or authorization bypasses;
- privilege escalation;
- access to another user's data;
- remote code execution;
- SQL injection or other injection vulnerabilities;
- server-side request forgery;
- path traversal or arbitrary file access;
- unsafe file upload or file processing;
- cross-site scripting affecting other users;
- cross-site request forgery with meaningful security impact;
- exposure of sensitive credentials, secrets, or user data;
- vulnerabilities in dependencies that are demonstrably exploitable through Cattr;
- security boundary violations between Cattr users, roles, or components.

## Out of Scope

The following are generally not considered valid security vulnerabilities under this policy:

- vulnerabilities that affect only unsupported stable versions;
- issues that affect only alpha, beta, RC, preview, nightly, or other pre-release versions and are not relevant to an upcoming stable release;
- dependency CVEs without evidence that the vulnerable behavior is reachable or exploitable through Cattr;
- automated scanner findings without a reproducible security impact;
- social engineering, phishing, or attacks requiring deception of project maintainers or users;
- attacks requiring physical access to the host machine;
- attacks requiring pre-existing privileged access to the host, container runtime, container, database, or underlying infrastructure;
- denial-of-service, resource-exhaustion, stress-testing, or load-testing results;
- self-XSS where an attacker cannot affect another user;
- missing HTTP security headers without a demonstrated exploitable impact;
- missing or insufficient rate limiting without a demonstrated security impact;
- issues caused exclusively by insecure reverse proxy, database, operating system, container runtime, network, or deployment configuration outside Cattr;
- vulnerabilities that depend on intentionally disabling or bypassing documented security controls;
- reports concerning credentials, tokens, secrets, or keys that are demonstrably expired, revoked, non-functional, or intended only for testing;
- software version disclosure, banner disclosure, stack traces, or other informational findings without meaningful security impact;
- speculative vulnerabilities without a reproducible attack path.

An issue being out of scope does not necessarily mean it is unimportant. Non-security bugs can be reported through the project's normal issue tracker or discussion channels where appropriate.

## Security Advisories and CVEs

Accepted vulnerabilities may be documented using GitHub Security Advisories.

When appropriate, the project may request a CVE identifier for the vulnerability.

Security advisories may include:

- affected versions;
- patched versions;
- severity and impact;
- mitigation or upgrade instructions;
- acknowledgements for the reporter.

Reporter credit will be given when appropriate and when the reporter agrees to be credited.

## Bug Bounty

Cattr does not currently operate a paid bug bounty program.

Submitting a vulnerability report does not create any entitlement to financial compensation, rewards, or other payment.

We nevertheless appreciate responsible reports that help improve the security of Cattr.
