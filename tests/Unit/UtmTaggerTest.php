<?php

namespace Tests\Unit;

use App\Support\UtmTagger;
use PHPUnit\Framework\TestCase;

class UtmTaggerTest extends TestCase
{
    public function test_it_adds_parameters_to_a_plain_link(): void
    {
        $this->assertSame(
            'https://shop.com/sale?utm_source=automail&utm_medium=email',
            UtmTagger::apply('https://shop.com/sale', ['utm_source' => 'automail', 'utm_medium' => 'email'])
        );
    }

    public function test_it_keeps_existing_query_and_fragment(): void
    {
        $this->assertSame(
            'https://shop.com/p?id=7&utm_source=automail#reviews',
            UtmTagger::apply('https://shop.com/p?id=7#reviews', ['utm_source' => 'automail'])
        );
    }

    public function test_it_never_overwrites_a_utm_the_sender_set(): void
    {
        $this->assertSame(
            'https://shop.com/?utm_source=partner&utm_medium=email',
            UtmTagger::apply('https://shop.com/?utm_source=partner', ['utm_source' => 'automail', 'utm_medium' => 'email'])
        );
    }

    public function test_it_leaves_a_fully_tagged_link_untouched(): void
    {
        $url = 'https://shop.com/?utm_source=a&utm_medium=b';

        $this->assertSame($url, UtmTagger::apply($url, ['utm_source' => 'x', 'utm_medium' => 'y']));
    }

    public function test_domain_filter_matches_hosts_and_subdomains_only(): void
    {
        $this->assertTrue(UtmTagger::hostAllowed('https://shop.com/a', []));
        $this->assertTrue(UtmTagger::hostAllowed('https://shop.com/a', ['shop.com']));
        $this->assertTrue(UtmTagger::hostAllowed('https://blog.shop.com/a', ['shop.com']));
        $this->assertFalse(UtmTagger::hostAllowed('https://evilshop.com/a', ['shop.com']));
        $this->assertFalse(UtmTagger::hostAllowed('https://twitter.com/a', ['shop.com']));
    }
}
