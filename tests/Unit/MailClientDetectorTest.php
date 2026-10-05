<?php

namespace Tests\Unit;

use App\Support\MailClientDetector;
use PHPUnit\Framework\TestCase;

class MailClientDetectorTest extends TestCase
{
    public function test_it_recognises_common_mail_apps(): void
    {
        $this->assertSame('Gmail', MailClientDetector::detect('Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 Chrome/42 Safari/537.36 Google-Image-Proxy GoogleImageProxy'));
        $this->assertSame('Outlook', MailClientDetector::detect('Microsoft Office/16.0 (Windows NT 10.0; Microsoft Outlook 16.0)'));
        $this->assertSame('Yahoo Mail', MailClientDetector::detect('YahooMailProxy; https://help.yahoo.com/kb/yahoo-mail-proxy-SLN28749.html'));
        $this->assertSame('Apple Mail', MailClientDetector::detect('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148'));
    }

    public function test_a_normal_browser_or_empty_agent_is_other(): void
    {
        $this->assertSame('Other', MailClientDetector::detect('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'));
        $this->assertSame('Other', MailClientDetector::detect(null));
        $this->assertSame('Other', MailClientDetector::detect(''));
    }
}
