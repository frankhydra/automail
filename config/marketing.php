<?php

/*
|--------------------------------------------------------------------------
| Public website content (home, features, pricing, solutions)
|--------------------------------------------------------------------------
| All the words on the marketing site live here, so you can edit the copy without
| touching any template. Everything written here describes something AutoMail really
| does today - keep it that way, because people (and email providers) judge a
| product by whether it matches its promises.
|
| Plan names, prices and limits are NOT here: they come from config/plans.php and
| config/ai.php, so the pricing page can never disagree with what the app enforces.
*/

return [

    'tagline' => 'Email marketing and automation, with an AI co-writer.',

    // ---------------------------------------------------------------- capabilities

    'capabilities' => [

        'builder' => [
            'name' => 'Email Builder',
            'icon' => 'builder',
            'headline' => 'Design emails that look right everywhere',
            'summary' => 'Add blocks, preview on desktop and mobile, send yourself a test, and reuse your images from one library.',
            'points' => [
                'Click to add headers, text, images, buttons, columns, social links, dividers and spacers',
                'Desktop and mobile preview, plus a real test email to your inbox before you send',
                'Preview text, subject lines and merge tags like {{first_name}}',
                'A Content Studio image library shared by your whole team',
                'Prefer code? Switch to the raw HTML editor any time',
            ],
        ],

        'automations' => [
            'name' => 'Automations',
            'icon' => 'automations',
            'headline' => 'Journeys that nurture while you sleep',
            'summary' => 'Welcome new contacts, follow up after two days, and send a different email to people who opened or ignored the first one.',
            'points' => [
                'Start a journey when a contact is added, or when a tag is added to them',
                'Chain emails with waits of hours or days between them',
                'Split on a condition: did they open it, did they click it? Each answer gets its own path',
                'People who unsubscribe leave every journey automatically',
                'Pause a journey any time without losing anyone\'s place',
            ],
        ],

        'audience' => [
            'name' => 'Audience & Segments',
            'icon' => 'audience',
            'headline' => 'Know exactly who you are talking to',
            'summary' => 'Import a CSV, tag people, build segments, and keep your list clean without thinking about it.',
            'points' => [
                'CSV import that checks every row, catches duplicates and reports what it skipped',
                'Tags, search and filters across your whole audience, with a per-contact engagement bar',
                'Segments built from rules: status, tags, email domain or date added',
                'A suppression list, so anyone who unsubscribed or bounced is never emailed again',
                'Export your audience to CSV whenever you want it',
            ],
        ],

        'analytics' => [
            'name' => 'Analytics',
            'icon' => 'analytics',
            'headline' => 'See what actually works',
            'summary' => 'Delivery, opens, clicks and unsubscribes for every campaign, with the details to improve the next one.',
            'points' => [
                'A dashboard with your open rate, click rate and audience growth at a glance',
                'A report for every campaign: delivered, opened, clicked, bounced, complained',
                'Hourly engagement for the first 24 hours after you send',
                'Your top-clicked links, and an estimate of which mail apps open your emails',
                'Plain-language insights that point out what needs attention',
            ],
        ],

        'ai' => [
            'name' => 'AI Assistant',
            'icon' => 'sparkles',
            'headline' => 'An AI co-writer inside your editor',
            'summary' => 'Draft a whole campaign from one sentence, rewrite any block, or get five subject lines to choose from.',
            'points' => [
                'Describe the email you want and get a subject, preview text, headline, body and button',
                'Rewrite any text: shorter, more professional, friendlier, more persuasive, or translated',
                'Five subject-line ideas based on what your email actually says',
                'It never sends anything: you read, edit and decide every time',
                'It is told not to invent prices, dates or discounts you did not give it',
            ],
        ],

        'integrations' => [
            'name' => 'Integrations & API',
            'icon' => 'integrations',
            'headline' => 'Plugs into the tools you already use',
            'summary' => 'Add contacts from any form or automation tool, tag your links for Google Analytics, and read your data through an API.',
            'points' => [
                'A private intake URL that adds contacts from website forms, Zapier, Make or your own code',
                'Automatic Google Analytics (UTM) tags on the links in your emails',
                'A read-only REST API for contacts, lists, campaigns, templates and analytics',
                'Delivery through established email providers: Brevo, Resend or your own SMTP server',
                'Provider webhooks keep delivery, bounce and complaint data up to date',
            ],
        ],
    ],

    // The six that appear in the "Features" menu.
    'menu_capabilities' => ['builder', 'automations', 'audience', 'analytics', 'ai', 'integrations'],

    // ---------------------------------------------------------------- also included

    'included' => [
        ['icon' => 'identities', 'title' => 'Your own sending domain', 'text' => 'Send from a personal address or your business domain. For your own domain, AutoMail shows the SPF, DKIM and DMARC records to add and checks them for you.'],
        ['icon' => 'lock', 'title' => 'Unsubscribe built in', 'text' => 'Every campaign carries an unsubscribe link, and the system enforces it: a person who opts out stops receiving email.'],
        ['icon' => 'audience', 'title' => 'Teams and roles', 'text' => 'Invite colleagues as Owner, Admin, Manager, Editor or Viewer, so everyone can do their job and nothing more.'],
        ['icon' => 'shield', 'title' => 'Separate workspaces', 'text' => 'Each business has its own isolated workspace. Its contacts, campaigns and files can never be reached from another.'],
        ['icon' => 'palette', 'title' => 'Make it yours', 'text' => 'Choose a colour theme and switch between light and dark mode. Your workspace, your look.'],
        ['icon' => 'bolt', 'title' => 'Sends in the background', 'text' => 'Big campaigns go through a queue, so you click send and carry on. Schedule one for later, too.'],
    ],

    // ---------------------------------------------------------------- how it works

    'steps' => [
        ['title' => 'Bring your audience', 'text' => 'Import a CSV, add contacts by hand, or send them in from your website with a private URL.'],
        ['title' => 'Write and design', 'text' => 'Build the email block by block, or describe it in a sentence and let the AI assistant draft it.'],
        ['title' => 'Send, automate, learn', 'text' => 'Launch a campaign or switch on a journey, then watch the results come in and improve the next one.'],
    ],

    // ---------------------------------------------------------------- solutions (audiences)

    'audiences' => [

        'marketers' => [
            'name' => 'For marketers',
            'menu' => 'Campaigns, journeys and results in one place.',
            'icon' => 'campaigns',
            'eyebrow' => 'For marketers',
            'title' => 'Run your whole email programme from one screen.',
            'intro' => 'Plan the campaign, write it with help, send it to the right people, and see the result, without stitching five tools together.',
            'benefits' => [
                ['icon' => 'sparkles', 'title' => 'Beat the blank page', 'text' => 'Describe the offer and get a full draft. Then tighten, warm up or translate any paragraph in one click.'],
                ['icon' => 'audience', 'title' => 'Send to the right people', 'text' => 'Segment by tag, status, email domain or sign-up date, so each campaign reaches the people it is meant for.'],
                ['icon' => 'automations', 'title' => 'Automate the follow-up', 'text' => 'Build a welcome series once. It runs for every new contact, including a different path for people who open and people who do not.'],
                ['icon' => 'analytics', 'title' => 'Learn from every send', 'text' => 'Open rate, click rate, top links and hourly engagement tell you what to repeat and what to drop.'],
            ],
            'flow' => [
                'Import your list and tag it by interest',
                'Design the email, or have the AI assistant draft it',
                'Send a test to yourself, then send it, or schedule it',
                'Open the campaign report and compare it with your last one',
            ],
            'capabilities' => ['builder', 'automations', 'ai', 'analytics'],
        ],

        'business' => [
            'name' => 'For small businesses',
            'menu' => 'Stay in touch with customers without a marketing team.',
            'icon' => 'dashboard',
            'eyebrow' => 'For small businesses',
            'title' => 'Keep customers coming back, without a marketing department.',
            'intro' => 'A simple monthly newsletter, a warm welcome for every new customer and a nudge for the ones who went quiet. Set it up once and let it run.',
            'benefits' => [
                ['icon' => 'mail', 'title' => 'A welcome that sends itself', 'text' => 'Every new contact gets your welcome email, then a follow-up two days later, with no one pressing a button.'],
                ['icon' => 'audience', 'title' => 'Tag your best customers', 'text' => 'Tag someone as VIP and a special journey starts for them automatically.'],
                ['icon' => 'identities', 'title' => 'Send from your own name', 'text' => 'Emails come from you@yourbusiness.com, not from a stranger\'s server, once your domain is verified.'],
                ['icon' => 'lock', 'title' => 'Stay on the right side of the rules', 'text' => 'Unsubscribe links, a suppression list and bounce handling are built in, so you are not guessing.'],
            ],
            'flow' => [
                'Add your customers, or let your website form add them for you',
                'Choose a template and fill in your news with the editor',
                'Switch on a welcome journey for every new contact',
                'Check the dashboard once a week to see what people open',
            ],
            'capabilities' => ['audience', 'builder', 'automations', 'integrations'],
        ],

        'teams' => [
            'name' => 'For teams',
            'menu' => 'Roles, shared assets and one source of truth.',
            'icon' => 'audience',
            'eyebrow' => 'For teams',
            'title' => 'Everyone on the same page, with the right access.',
            'intro' => 'Writers write, managers send, and the owner keeps the keys. Share one audience, one image library and one set of templates.',
            'benefits' => [
                ['icon' => 'audience', 'title' => 'Five roles', 'text' => 'Owner, Admin, Manager, Editor and Viewer. Editors can build, managers can send, viewers can only look at results.'],
                ['icon' => 'studio', 'title' => 'One shared asset library', 'text' => 'Logos and product photos live in the Content Studio, so nobody hunts for the right file.'],
                ['icon' => 'builder', 'title' => 'Reusable templates', 'text' => 'Build an email once and start any campaign from it. Update the template and live journeys follow.'],
                ['icon' => 'shield', 'title' => 'Protected by design', 'text' => 'Sensitive actions such as deleting data, exporting contacts and switching on a journey need the right role.'],
            ],
            'flow' => [
                'Invite teammates by email and choose each person\'s role',
                'Editors design and write, with AI help if they want it',
                'A manager reviews the test email and sends the campaign',
                'Everyone, including viewers, reads the same reports',
            ],
            'capabilities' => ['builder', 'audience', 'analytics', 'ai'],
        ],

        'developers' => [
            'name' => 'For developers',
            'menu' => 'A private intake URL, a REST API and provider webhooks.',
            'icon' => 'code',
            'eyebrow' => 'For developers',
            'title' => 'Connect AutoMail to anything with a web request.',
            'intro' => 'Push new contacts in from your app, read your data out with a token, and let your email provider report delivery events back. No SDK needed.',
            'benefits' => [
                ['icon' => 'bolt', 'title' => 'Add contacts with one POST', 'text' => 'Send JSON to a private URL and the contact is created, tagged, and entered into any matching journey. Retries are safe.'],
                ['icon' => 'key', 'title' => 'Token-based REST API', 'text' => 'Read contacts, lists, campaigns, templates, sending identities and analytics under /api/v1, scoped to your workspace.'],
                ['icon' => 'integrations', 'title' => 'Delivery events in', 'text' => 'Provider webhooks for deliveries, bounces and complaints keep your contact statuses accurate.'],
                ['icon' => 'lock', 'title' => 'Secrets handled carefully', 'text' => 'Intake URLs are stored only as a hash, shown once, and can be revoked with one click. Endpoints are rate-limited.'],
            ],
            'flow' => [
                'Create an intake URL under Integrations, and an API token under Settings',
                'POST new sign-ups to the intake URL from your site or backend',
                'Read contacts and campaign results back through the API',
                'Tag your links for Google Analytics with one switch',
            ],
            'capabilities' => ['integrations', 'audience', 'analytics'],
            'code' => [
                [
                    'title' => 'Add a contact',
                    'note' => 'Only email is required. Existing addresses answer "exists", so retrying is safe.',
                    'code' => "curl -X POST \"{APP_URL}/webhooks/contacts/YOUR_SECRET_TOKEN\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"email\":\"jane@example.com\",\"first_name\":\"Jane\",\"tags\":[\"website\",\"vip\"]}'",
                ],
                [
                    'title' => 'Read your contacts',
                    'note' => 'Create a token under Settings, then send it as a Bearer token.',
                    'code' => "curl \"{APP_URL}/api/v1/contacts\" \\\n  -H \"Authorization: Bearer YOUR_API_TOKEN\" \\\n  -H \"Accept: application/json\"",
                ],
            ],
            'endpoints' => [
                ['GET', '/api/v1/contacts', 'List contacts'],
                ['GET', '/api/v1/contacts/{id}', 'One contact'],
                ['GET', '/api/v1/lists', 'Contact lists'],
                ['GET', '/api/v1/campaigns', 'List campaigns'],
                ['GET', '/api/v1/campaigns/{id}', 'One campaign'],
                ['GET', '/api/v1/templates', 'List templates'],
                ['GET', '/api/v1/sending-identities', 'Sending identities'],
                ['GET', '/api/v1/analytics', 'Account analytics'],
            ],
            'api_note' => 'The API is read-only today. To create contacts, use the intake URL above. More write endpoints are planned.',
        ],
    ],

    // ---------------------------------------------------------------- FAQ

    'faqs' => [
        'general' => [
            ['q' => 'Do I need technical skills?', 'a' => 'No. Everything is point-and-click: you import contacts, build emails with blocks and switch on journeys from the dashboard. Developers who want more can use the intake URL and API, but nobody has to.'],
            ['q' => 'Can I send from my own domain?', 'a' => 'Yes. Add a sending identity with your business address and AutoMail shows the SPF, DKIM and DMARC records to add at your domain host, then checks them. You can also send from a personal address once you have confirmed you own it.'],
            ['q' => 'How does the AI assistant work, and is my content safe?', 'a' => 'You describe an email or select some text, and the assistant writes a suggestion for you to review. Nothing is sent or published automatically. The text you ask about is sent to Anthropic\'s Claude to produce the answer; AutoMail keeps only a count of requests, not your prompts or the answers.'],
            ['q' => 'Can I import my existing list?', 'a' => 'Yes. Upload a CSV and AutoMail checks every row, skips invalid addresses and duplicates, and tells you exactly what it skipped. Only import people who agreed to hear from you.'],
            ['q' => 'What happens if I reach a plan limit?', 'a' => 'Nothing is silently dropped. A CSV import stops at your contact limit, and a campaign that would go over your monthly sending allowance is blocked until next month or until you change plan.'],
            ['q' => 'Can my team use it?', 'a' => 'Yes. Invite teammates by email and give each a role, from Owner and Admin down to Editor and Viewer. The number of people included depends on your plan.'],
            ['q' => 'Can I take my data with me?', 'a' => 'Yes. Owners, admins and managers can export the audience to a CSV file at any time.'],
            ['q' => 'Is there an API?', 'a' => 'There is a read-only REST API for contacts, lists, campaigns, templates, sending identities and analytics, plus a private URL for adding contacts from other tools. More write endpoints are planned.'],
        ],
        'pricing' => [
            ['q' => 'Are any features locked to higher plans?', 'a' => 'No. Every plan includes every feature: the builder, automations, segments, analytics, the AI assistant, integrations and the API. Plans differ only in volume: contacts, monthly emails, team members, sending identities and AI requests.'],
            ['q' => 'What counts as a monthly email?', 'a' => 'One email delivered to one contact. A campaign to 1,000 people is 1,000 emails, and so is each email an automation sends. Your allowance resets every month.'],
            ['q' => 'What happens if I reach a limit?', 'a' => 'Nothing is silently dropped. A CSV import stops at your contact limit, and a campaign that would go over your monthly allowance is blocked until next month or until you move to a bigger plan.'],
            ['q' => 'How do I change plan?', 'a' => 'Owners can switch plans at any time from Settings, Billing. Start on Free, and move up when you need more room.'],
            ['q' => 'What is an AI request?', 'a' => 'Each time you ask the assistant to write a draft, rewrite a block or suggest subject lines. Failed requests are not counted, and the allowance resets every month.'],
            ['q' => 'What is a sending identity?', 'a' => 'The name and address your emails come from, for example "Tony at Tony Electronics <sales@tonyelectronics.com>". You can have several, and each is verified before it can send.'],
        ],
    ],
];
