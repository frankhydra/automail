# AutoMail — Master Project Specification & Progressive Roadmap

**Project:** AutoMail  
**Purpose:** AI-assisted SaaS email marketing and automation platform  
**Primary coding partner:** Claude AI  
**Recommended stack:** Laravel + PHP + MySQL + Redis + Nginx + Ubuntu + Git/GitHub  
**Development philosophy:** Build a small, clean foundation first, then progressively add professional and advanced capabilities without rewriting the core application.

---

# 1. What AutoMail Is

AutoMail is a SaaS platform that allows businesses to manage contacts and send marketing emails.

The long-term vision is:

```text
Business
   |
   v
AutoMail
   |
   +-- Contacts
   +-- Lists / Segments
   +-- Email Campaigns
   +-- Templates
   +-- Sending Identities
   +-- Custom Domains
   +-- Scheduling
   +-- Analytics
   +-- Automation
   +-- AI
   +-- Billing
   +-- Future SMS / WhatsApp
```

AutoMail is **not initially an email-hosting platform**. It is primarily an email marketing and automation platform.

The project is intentionally divided into three phases:

1. **Phase 1 — Basic / Easy MVP**
2. **Phase 2 — Medium / Professional SaaS**
3. **Phase 3 — Hard / Advanced Marketing Automation Platform**

The most important architectural goal is:

> **Phase 1 must be designed so Phase 2 and Phase 3 can be added without rebuilding the entire system.**

---

# 2. Core Product Concept

AutoMail separates three things:

```text
USER ACCOUNT
     |
     | login
     v
tony@gmail.com
```

from:

```text
SENDING IDENTITY
     |
     +-- tony@gmail.com
     |
     +-- sales@tonyelectronics.com
```

and from:

```text
EMAIL DELIVERY INFRASTRUCTURE
     |
     +-- Brevo
     +-- Resend
     +-- Amazon SES
     +-- Other providers
     +-- Future self-hosted SMTP
```

This separation is critical.

A user may log in using Gmail while sending campaigns from a verified business domain.

---

# 3. The Two Initial Sending Models

## Model 1 — Personal/ordinary email

A user signs up with:

```text
tony@gmail.com
```

The user verifies ownership of that address and can use it as a sending identity, subject to the rules and limitations of the chosen email provider.

This is the easiest onboarding path.

---

## Model 3 — Customer-owned business domain

The user may own:

```text
tonyelectronics.com
```

and want to send from:

```text
sales@tonyelectronics.com
```

The user adds DNS records supplied by AutoMail.

AutoMail verifies:

```text
SPF
DKIM
DMARC
Domain ownership
```

Then the sending identity becomes available for campaigns.

---

# 4. Important Architecture Rule

Do not hard-code Model 1 and Model 3 throughout the application.

Create a generic concept:

```text
SendingIdentity
```

Example fields:

```text
id
organization_id
type
from_name
from_email
reply_to
verification_status
authentication_status
provider_id
created_at
updated_at
```

Possible types:

```text
personal
custom_domain
provider_managed
```

This allows more sending methods to be added later.

---

# 5. Recommended Technology Stack

## Application

- PHP
- Laravel
- MySQL
- Blade initially
- JavaScript
- React later when complex interactive interfaces require it

## Infrastructure

- Ubuntu Linux
- Nginx
- PHP-FPM
- MySQL
- Redis
- Laravel Queue
- Git
- GitHub
- HTTPS

## Email

Initially use an external email delivery provider such as:

- Amazon SES
- Brevo
- Resend
- Mailgun
- Another suitable transactional email provider

Do **not** build a complete SMTP infrastructure in Phase 1.

The application should communicate through an internal abstraction such as:

```text
EmailDeliveryService
```

so the provider can be changed later.

---

# 6. Multi-Tenant Architecture

AutoMail is a SaaS product, so it must be designed for multiple businesses.

Do not assume:

```text
one user = one business
```

Instead:

```text
User
 |
 +--> Organization
        |
        +--> Members
        +--> Contacts
        +--> Lists
        +--> Campaigns
        +--> Templates
        +--> Sending Identities
        +--> Domains
        +--> Automations
        +--> Analytics
```

Example:

```text
Tony
 |
 +--> Tony Electronics
 |
 +--> AutoMind Labs
```

This decision should be made in Phase 1 because changing from a single-user architecture to a multi-tenant architecture later can require a major rewrite.

---

# ============================================================
# PHASE 1 — BASIC / EASY AUTOMAIL MVP
# ============================================================

## Phase 1 Goal

Build the smallest real version of AutoMail.

A user should be able to:

```text
Register
   |
Create organization
   |
Add contacts
   |
Create sending identity
   |
Create campaign
   |
Send campaign
   |
See basic result
```

Do not build advanced automation, AI, billing, SMS, WhatsApp, or self-hosted SMTP yet.

---

# Phase 1.1 — Development Environment

## Learn

Claude must explain:

- PHP
- Composer
- Laravel
- MVC
- `.env`
- MySQL
- Git
- HTTP basics
- Local vs production environments

## Build

Create:

```text
Laravel application
MySQL database
Git repository
Environment configuration
```

Recommended structure:

```text
automail/
├── app/
├── database/
├── routes/
├── resources/
├── tests/
├── storage/
├── public/
├── .env
└── README.md
```

---

# Phase 1.2 — Authentication

Implement:

- Registration
- Login
- Logout
- Password hashing
- Password reset
- Email verification
- Profile

Main table:

```text
users
```

Use Laravel's established authentication mechanisms where appropriate instead of inventing authentication from scratch.

---

# Phase 1.3 — Organizations

Create:

```text
organizations
organization_user
```

A user creates a business/workspace.

Example:

```text
Tony
 |
 +--> Tony Electronics
```

Later the organization can have:

```text
Tony
Sarah
David
```

with different permissions.

---

# Phase 1.4 — Contacts

Create:

```text
contacts
contact_lists
contact_list_members
```

Basic contact fields:

```text
id
organization_id
first_name
last_name
email
status
created_at
updated_at
```

Possible statuses:

```text
subscribed
unsubscribed
bounced
```

Do not store contacts directly inside campaigns.

---

# Phase 1.5 — CSV Import

Allow users to import:

```text
name,email
John,john@gmail.com
Mary,mary@yahoo.com
```

The system must:

1. Validate the uploaded file.
2. Validate email addresses.
3. Detect duplicates.
4. Import valid contacts.
5. Report invalid rows.
6. Preserve organization ownership.

---

# Phase 1.6 — Sending Identities

Create:

```text
sending_identities
```

Initially support:

```text
personal
```

Example:

```text
Tony <tony@gmail.com>
```

The user must verify ownership of the address.

Design the database so custom domains can be added in Phase 2.

---

# Phase 1.7 — Campaigns

Create:

```text
campaigns
campaign_recipients
```

Campaign fields can include:

```text
id
organization_id
sending_identity_id
name
subject
body
status
scheduled_at
created_at
updated_at
```

Statuses:

```text
draft
queued
sending
sent
failed
cancelled
```

Campaign creation should include:

```text
Campaign name
Subject
Email body
Sending identity
Recipient list
```

---

# Phase 1.8 — Templates

Initially support simple HTML email templates.

Support variables such as:

```text
{{first_name}}
{{last_name}}
{{email}}
```

Example:

```text
Hello {{first_name}},

Welcome to our newsletter.
```

Claude must explain template rendering before implementing it.

---

# Phase 1.9 — Email Delivery

Do not initially build your own SMTP server.

Create:

```text
EmailDeliveryService
```

Conceptually:

```text
Campaign
   |
   v
EmailDeliveryService
   |
   v
Email Provider
```

The rest of AutoMail should not depend directly on provider-specific code.

---

# Phase 1.10 — Queues

Do not send large campaigns directly during a browser request.

Use:

```text
Redis
Laravel Queue
Jobs
Workers
```

Architecture:

```text
User clicks SEND
       |
       v
Campaign created
       |
       v
Queue
       |
       v
Worker
       |
       v
Email provider
```

This decision is important because Phase 2 and Phase 3 will depend heavily on background processing.

---

# Phase 1.11 — Basic Dashboard

Show:

```text
Contacts
Campaigns
Emails sent
Successful deliveries
Failed deliveries
```

Do not build complex analytics yet.

---

# Phase 1.12 — Basic Security

Implement:

- CSRF protection
- Input validation
- Authorization
- Password hashing
- Rate limiting
- Secure sessions
- Secure cookies
- File-upload validation
- Environment variables for secrets
- Organization-level access control

Critical test:

```text
Organization A must never access
Organization B's contacts or campaigns.
```

---

# PHASE 1 EXIT CRITERIA

Phase 1 is complete when this works:

```text
User registers
   |
Verifies account
   |
Creates organization
   |
Imports contacts
   |
Creates sending identity
   |
Creates campaign
   |
Queues campaign
   |
Worker sends emails
   |
Recipient receives email
   |
Dashboard records basic result
```

At this point AutoMail is a working MVP.

---

# ============================================================
# PHASE 2 — MEDIUM / MODERATE AUTOMAIL
# ============================================================

## Phase 2 Goal

Turn the MVP into a professional SaaS product that real businesses can use.

Add:

```text
Custom domains
DNS authentication
Scheduling
Tracking
Analytics
Unsubscribe
Bounce handling
Segmentation
Better templates
Team accounts
API
Billing foundation
```

---

# Phase 2.1 — Custom Domains

Create:

```text
domains
domain_verifications
```

Flow:

```text
User enters:
tonyelectronics.com
       |
       v
AutoMail generates DNS records
       |
       v
User adds records
       |
       v
AutoMail checks DNS
       |
       v
Domain verified
       |
       v
Sending identity enabled
```

Support the appropriate:

- SPF
- DKIM
- DMARC
- Domain ownership verification

The exact records should follow the selected email provider's current requirements.

---

# Phase 2.2 — Multiple Email Providers

Create:

```text
EmailProviderInterface
```

Implement adapters such as:

```text
EmailProviderInterface
       |
       +--> SESProvider
       +--> BrevoProvider
       +--> ResendProvider
       +--> FutureSMTPProvider
```

Campaign code should not care which provider is being used.

This allows:

- Provider replacement.
- Different providers for different environments.
- Provider failover later.
- Provider-specific configuration.

---

# Phase 2.3 — Scheduling

Allow:

```text
Send now
Send later
Schedule date/time
```

Use:

```text
Laravel Scheduler
Laravel Queue
Redis
Workers
```

Handle:

- User time zones
- Cancelled campaigns
- Duplicate execution
- Failed jobs
- Retries

---

# Phase 2.4 — Email Tracking

Track events such as:

```text
sent
delivered
opened
clicked
bounced
complained
unsubscribed
```

Use provider webhooks where possible.

For click tracking, generate unique tracking links.

For open tracking, use an appropriate tracking mechanism while respecting privacy and email-client limitations.

Do not treat "opened" as perfectly accurate because modern privacy features can affect open tracking.

---

# Phase 2.5 — Unsubscribe System

Create:

```text
unsubscribe_tokens
suppression_list
```

Every marketing campaign must provide an unsubscribe mechanism.

When a user unsubscribes:

```text
Contact
   |
   v
Suppressed
   |
   v
Future campaigns skip contact
```

The backend must enforce suppression.

---

# Phase 2.6 — Bounce and Complaint Handling

Receive provider webhook events.

Examples:

```text
delivered
bounced
complained
opened
clicked
unsubscribed
```

Update contact/campaign status.

Protect the platform from repeatedly sending to permanently invalid addresses.

---

# Phase 2.7 — Segmentation

Allow:

```text
All customers
VIP customers
New subscribers
Customers who clicked
Customers who purchased
```

Eventually support rules:

```text
country = Cameroon
AND
status = subscribed
```

or:

```text
last_purchase < 30 days
```

Keep segmentation as a reusable service instead of embedding the logic inside campaigns.

---

# Phase 2.8 — Advanced Template Builder

Move from raw HTML to a visual builder.

Potential blocks:

```text
Text
Image
Button
Columns
Divider
Header
Footer
Social links
```

React may be introduced here if the builder becomes sufficiently interactive.

Keep the generated email compatible with common email clients.

---

# Phase 2.9 — Team Accounts

Support roles such as:

```text
Owner
Admin
Manager
Editor
Viewer
```

Use Laravel policies/authorization.

Example:

```text
Owner -> everything
Admin -> administration
Manager -> contacts + campaigns
Editor -> campaign editing
Viewer -> analytics
```

---

# Phase 2.10 — API

Create a versioned API:

```text
/api/v1/
```

Potential resources:

```text
contacts
lists
campaigns
templates
sending-identities
analytics
```

Do not expose database internals directly.

---

# Phase 2.11 — Billing

Introduce plans only after the core product works.

Example structure:

```text
Free
Starter
Business
Pro
```

Plans may control:

```text
contacts
emails
team members
domains
automation
AI usage
```

Keep billing logic in a dedicated module.

---

# PHASE 2 EXIT CRITERIA

A professional AutoMail customer can:

```text
Create account
   |
Create business
   |
Invite team
   |
Add contacts
   |
Authenticate custom domain
   |
Create campaign
   |
Schedule campaign
   |
Send campaign
   |
Track results
   |
Manage unsubscribes
   |
View analytics
```

At this point AutoMail is a serious email marketing SaaS.

---

# ============================================================
# PHASE 3 — HARD / COMPLEX AUTOMAIL
# ============================================================

## Phase 3 Goal

Turn AutoMail into an advanced marketing automation platform.

---

# Phase 3.1 — Automation Engine

Core concept:

```text
Trigger
   |
Condition
   |
Delay
   |
Action
```

Example:

```text
User subscribes
       |
       v
Send welcome email
       |
       v
Wait 2 days
       |
       v
Did user click?
     /        YES      NO
    |        |
    v        v
Offer A    Offer B
```

Potential tables:

```text
automations
automation_nodes
automation_edges
automation_runs
automation_events
```

The automation engine should be independent from campaign controllers.

---

# Phase 3.2 — Visual Automation Builder

Create a visual workflow:

```text
[Trigger]
    |
[Send Email]
    |
[Wait]
    |
[Condition]
   /  Yes No
```

React becomes appropriate if the editor requires drag-and-drop nodes, connections, zooming, and real-time state management.

---

# Phase 3.3 — Advanced Analytics

Track:

- Delivery rate
- Bounce rate
- Open rate
- Click-through rate
- Unsubscribe rate
- Complaint rate
- Campaign comparison
- Contact growth
- Segment performance
- Automation performance

Eventually use event-based analytics rather than recalculating every metric from raw campaign records.

---

# Phase 3.4 — A/B Testing

Allow:

```text
Campaign A
Subject: 20% OFF TODAY

Campaign B
Subject: Your 20% Discount Is Waiting
```

Split recipients and compare results.

Allow configurable winning criteria.

---

# Phase 3.5 — Advanced Personalization

Support:

```text
{{first_name}}
{{company}}
{{city}}
{{custom_field}}
```

Eventually:

```text
{{recommended_product}}
{{last_purchase}}
{{customer_segment}}
```

All variables must be validated and safely rendered.

---

# Phase 3.6 — AI Layer

AI should sit above the existing application rather than control the whole application.

Potential features:

## AI campaign generation

User:

```text
Create a campaign for a 30% weekend discount.
```

AI generates:

- Subject
- Preview text
- Body
- CTA
- Suggested audience

## AI rewriting

```text
Make shorter
Make professional
Make friendly
Make persuasive
Translate
Improve CTA
```

## AI analytics

Example:

```text
Your campaign had a 28% open rate,
which was higher than your previous campaigns.
```

## AI segmentation

AI may suggest:

```text
Customers who have not purchased
in the last 90 days.
```

Important:

AI suggestions should be converted into validated application operations.

Never allow AI to execute arbitrary database queries or arbitrary server commands.

---

# Phase 3.7 — Omnichannel Expansion

Possible future channels:

```text
Email
SMS
WhatsApp
Push notifications
```

Architecture:

```text
Marketing Campaign
       |
       +--> Email
       +--> SMS
       +--> WhatsApp
       +--> Push
```

Only add this after the email system is mature.

---

# Phase 3.8 — Advanced Infrastructure

When actual traffic requires scaling:

```text
Load Balancer
      |
  +---+---+
  |       |
App 1   App 2
  |       |
  +---+---+
      |
    Redis
      |
    Queue
      |
 +----+----+
 |    |    |
W1   W2   W3
      |
      v
Email Providers
```

Potential additions:

- Multiple application servers
- Dedicated queue workers
- Managed database
- Redis infrastructure
- Object storage
- Monitoring
- Error tracking
- Centralized logging
- Backups
- Provider failover

Do not implement this before real usage requires it.

---

# Phase 3.9 — Optional Self-Hosted Email Infrastructure

Only consider this after the SaaS is proven.

A self-hosted mail infrastructure could involve:

```text
Postfix
Dovecot
Rspamd
ClamAV
DKIM
SPF
DMARC
Reverse DNS
TLS
IP reputation
Bounce processing
Abuse prevention
```

This is a separate major engineering project.

AutoMail should remain provider-based until there is a clear business reason to own email delivery infrastructure.

---

# 7. Database Evolution

## Phase 1

```text
users
organizations
organization_user
contacts
contact_lists
contact_list_members
sending_identities
campaigns
campaign_recipients
templates
```

## Phase 2

Add:

```text
domains
domain_verifications
email_events
suppression_list
webhook_events
segments
segment_rules
team_roles
subscriptions
plans
```

## Phase 3

Add:

```text
automations
automation_nodes
automation_edges
automation_runs
automation_events
ab_tests
message_variants
ai_generations
provider_accounts
provider_events
```

Do not create every table at the beginning.

---

# 8. Recommended Application Architecture

Avoid putting all business logic inside controllers.

Prefer:

```text
Controller
    |
    v
Application Service
    |
    v
Business Logic
    |
    v
Models / Persistence
    |
    v
Database
```

Email:

```text
CampaignController
       |
       v
CampaignService
       |
       v
EmailDeliveryService
       |
       v
EmailProviderInterface
       |
       +--> SES
       +--> Brevo
       +--> Resend
       +--> Future SMTP
```

This architecture makes Phase 2 and Phase 3 much easier.

---

# 9. Claude AI Development Rules

Claude is the primary coding assistant for AutoMail.

Claude must follow these rules.

## Rule 1 — Explain before implementing

Before significant code changes, explain:

1. What is being built.
2. Why it is needed.
3. How it works.
4. What files will change.
5. What database changes are required.
6. What risks exist.
7. How the feature will be tested.

---

## Rule 2 — Work in small milestones

Never ask Claude to build the entire project in one response.

Use:

```text
Define
  |
Explain
  |
Implement
  |
Test
  |
Review
  |
Document
  |
Commit
  |
Next task
```

---

## Rule 3 — Teach while building

Whenever Claude introduces a new concept, it must explain it.

Examples:

```text
Redis
Queues
Jobs
Workers
Webhooks
DNS
SPF
DKIM
DMARC
Multi-tenancy
Dependency injection
Events
Policies
API authentication
```

Claude should explain:

- What it is.
- Why AutoMail needs it.
- How it works.
- Where it fits in the architecture.
- How to test it.

---

## Rule 4 — Do not silently change architecture

If a proposed implementation requires changing an existing architectural decision, Claude must explain the change before implementing it.

---

## Rule 5 — Preserve existing functionality

Before changing an existing module, Claude should inspect how other modules use it.

Avoid breaking previously completed phases.

---

## Rule 6 — No secrets in source code

Never hard-code:

- API keys
- Passwords
- SMTP credentials
- Database passwords
- AI keys

Use environment variables.

---

## Rule 7 — Tests are part of development

Important modules should have automated tests.

Examples:

```text
Authentication
Authorization
Contacts
Campaigns
Sending identities
Queues
Unsubscribe
Domain verification
Automation
Billing
```

---

## Rule 8 — Do not over-engineer Phase 1

Do not implement Phase 3 architecture unnecessarily.

Instead:

> Build the simplest correct version while preserving clean boundaries for future expansion.

---

# 10. Claude Task Prompt Pattern

For every new feature, use this workflow with Claude:

```text
We are building AutoMail.

Before writing code:

1. Read the AutoMail project specification.
2. Identify the current phase.
3. Explain the feature in beginner-friendly terms.
4. Explain how it fits into the existing architecture.
5. Identify database changes.
6. Identify files that will change.
7. Identify dependencies.
8. Identify security considerations.
9. Explain how we will test it.
10. Propose the smallest implementation.
11. Wait for approval before making large architectural changes.

When implementing:
- Keep changes focused.
- Follow existing architecture.
- Write tests.
- Explain unfamiliar concepts.
- Do not expose secrets.
- Do not rewrite unrelated code.

After implementation:
- Explain what changed.
- Show how to test it.
- Report any known limitations.
- Update the project documentation.
- Suggest the next logical milestone.
```

---

# 11. Git Development Workflow

Recommended branches:

```text
main
develop
feature/*
```

Examples:

```text
feature/authentication
feature/contacts
feature/campaigns
feature/email-sending
feature/custom-domains
feature/tracking
feature/automation
```

Workflow:

```text
Create branch
     |
Build feature
     |
Run tests
     |
Manual test
     |
Commit
     |
Merge
```

Every major milestone should produce a working Git commit.

---

# 12. Testing Strategy

## Unit tests

Test individual business rules.

Example:

```text
Does an unsubscribed contact get excluded?
```

## Feature tests

Test complete workflows.

Example:

```text
User creates campaign
        |
Campaign queued
        |
Job processed
        |
Provider called
```

## Manual tests

Test the actual UI and real email delivery in a controlled environment.

## Security tests

Try:

```text
Organization A accessing Organization B
User accessing another user's campaign
Invalid IDs
Unauthorized API requests
Malicious uploads
```

---

# 13. Deployment Strategy

## Development

```text
Local PC
 |
Laravel
 |
MySQL
 |
Redis
```

## Staging

```text
VPS
 |
Nginx
 |
Laravel
 |
MySQL
 |
Redis
 |
Test email provider
```

## Production

```text
Domain
 |
HTTPS
 |
Nginx
 |
Laravel
 |
Database
 |
Redis
 |
Workers
 |
Email provider
```

Do not use production as the experimental development environment.

---

# 14. Security Requirements

AutoMail handles customer information and email addresses.

Security is a core feature.

Implement:

- HTTPS
- Password hashing
- CSRF protection
- Authorization policies
- Input validation
- Output escaping
- Rate limiting
- Secure sessions
- Secure cookies
- API authentication
- File upload restrictions
- Secrets in environment variables
- Database backups
- Audit logs
- Organization isolation
- Email verification
- Domain verification
- Abuse prevention
- Unsubscribe enforcement

Critical rule:

```text
Organization A
       X
Organization B's data
```

must never be accessible through manipulated URLs, API requests, or database IDs.

---

# 15. What NOT to Build Initially

Do not start with:

- Your own SMTP infrastructure
- Millions of emails per day
- Complex AI
- WhatsApp
- SMS
- Mobile apps
- Complex visual automation
- Microservices
- Kubernetes
- Multiple VPS servers
- Enterprise SSO
- Advanced billing

First prove:

```text
REGISTER
   |
CREATE BUSINESS
   |
ADD CONTACTS
   |
CREATE CAMPAIGN
   |
SEND EMAIL
   |
TRACK RESULT
```

---

# 16. Learning Roadmap While Building

Do not try to learn the entire stack before starting.

Learn just in time.

## Before Phase 1

Review:

- PHP OOP
- Composer
- Laravel basics
- MVC
- MySQL relationships
- Git
- HTTP
- REST APIs

## During Phase 1

Learn:

- Laravel authentication
- Eloquent
- Validation
- Authorization
- Queues
- Redis
- Email APIs

## During Phase 2

Learn:

- DNS
- SPF
- DKIM
- DMARC
- Webhooks
- Email deliverability
- Multi-tenancy
- SaaS billing
- Advanced queues

## During Phase 3

Learn:

- Workflow engines
- Advanced React
- AI APIs
- Data analytics
- Event-driven architecture
- Distributed systems
- Scaling
- Observability

---

# 17. Definition of Success

## Phase 1

> "I can send an email campaign."

## Phase 2

> "A real business can use AutoMail professionally."

## Phase 3

> "AutoMail is a marketing automation platform."

The progression is:

```text
LEVEL 1
I can send an email.
        |
        v
LEVEL 2
I can send campaigns to contacts.
        |
        v
LEVEL 3
Businesses can use the platform.
        |
        v
LEVEL 4
Businesses can authenticate their own domains.
        |
        v
LEVEL 5
Businesses can automate marketing.
        |
        v
LEVEL 6
AI can assist with marketing.
        |
        v
LEVEL 7
The platform can operate at scale.
```

---

# 18. Recommended Final Architecture

The long-term system should evolve toward:

```text
                         AUT0MAIL
                            |
              +-------------+-------------+
              |             |             |
              v             v             v
          Accounts       Contacts      Campaigns
              |             |             |
              +-------------+-------------+
                            |
                            v
                   Sending Identities
                     /                                 /                            Personal Email     Custom Domain
                    \               /
                     \             /
                      v           v
                    Email Delivery
                          |
               +----------+----------+
               |          |          |
              SES      Brevo      Resend
               |          |          |
               +----------+----------+
                          |
                          v
                 Gmail / Outlook / Yahoo
```

Then Phase 3 adds:

```text
                    AutoMail
                       |
          +------------+------------+
          |            |            |
       Campaigns    Automation      AI
          |            |            |
          +------------+------------+
                       |
                 Marketing Engine
                       |
          +------------+------------+
          |            |            |
        Email         SMS        WhatsApp
```

---

# 19. Final Project Roadmap

```text
PHASE 1 — BASIC
│
├── Laravel foundation
├── Authentication
├── Organizations
├── Contacts
├── Lists
├── CSV import
├── Sending identities
├── Campaigns
├── Templates
├── Email provider
├── Queues
├── Basic dashboard
└── Security
        |
        v
PHASE 2 — PROFESSIONAL SaaS
│
├── Custom domains
├── SPF/DKIM/DMARC
├── Multiple providers
├── Scheduling
├── Tracking
├── Unsubscribe
├── Bounce handling
├── Segmentation
├── Template builder
├── Team accounts
├── API
├── Billing
└── Advanced analytics
        |
        v
PHASE 3 — ADVANCED PLATFORM
│
├── Automation engine
├── Visual workflow builder
├── A/B testing
├── Advanced personalization
├── AI campaign generation
├── AI analytics
├── AI segmentation
├── SMS/WhatsApp
├── Advanced infrastructure
├── Provider failover
└── Optional self-hosted email infrastructure
```

---

# 20. First Instruction to Claude

Do **not** start by asking Claude to build AutoMail.

Give Claude this specification first.

Then instruct Claude to:

1. Read the complete specification.
2. Summarize its understanding.
3. Identify contradictions or missing decisions.
4. Propose the Phase 1 architecture.
5. Propose the initial database schema.
6. Explain the development environment.
7. Identify what needs to be learned before implementation.
8. Break Phase 1 into small milestones.
9. Recommend the first milestone.
10. Do not build the whole application.
11. Wait for approval before major implementation.

The first implementation milestone should be:

```text
Laravel project
+
Git repository
+
MySQL connection
+
Environment configuration
+
Initial architecture
+
Documentation
```

Then proceed one milestone at a time.

---

# 21. The AutoMail Golden Rule

Do not measure progress by the amount of code generated.

Measure progress by working capabilities.

The objective is not:

> "Claude generated 50,000 lines of code."

The objective is:

> "AutoMail works, I understand the architecture, and the next phase can be added without destroying the previous phase."

Build slowly.

Test continuously.

Commit frequently.

Document decisions.

Learn while building.

Let Claude accelerate implementation, but make sure you understand the important architecture and technology decisions.

---

# END OF AUTOMAIL MASTER PROJECT SPECIFICATION
