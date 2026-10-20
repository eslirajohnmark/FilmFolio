<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TabBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_a_sign_in_tab_in_place_of_profile(): void
    {
        $response = $this->get(route('home'));

        $tabs = $this->tabBar($response);
        $this->assertSame(['Home', 'Films', 'Search', 'Lists', 'Sign in'], array_column($tabs, 'label'));
        $this->assertSame(route('login'), $tabs[4]['href']);
        $this->assertSame(['Home'], $this->currentTabs($tabs));
    }

    public function test_profile_tab_links_to_the_members_own_profile_and_is_current_there(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get(route('profile.show', $member));

        $tabs = $this->tabBar($response);
        $this->assertSame('Profile', $tabs[4]['label']);
        $this->assertSame(route('profile.show', $member), $tabs[4]['href']);
        $this->assertSame(['Profile'], $this->currentTabs($tabs));
    }

    public function test_profile_tab_is_not_current_on_another_members_profile(): void
    {
        [$member, $otherMember] = User::factory(2)->create();

        $response = $this->actingAs($member)->get(route('profile.show', $otherMember));

        $this->assertSame([], $this->currentTabs($this->tabBar($response)));
    }

    public function test_admin_pages_show_the_admin_tabs_with_add_film_in_the_centre(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.dashboard'));

        $tabs = $this->tabBar($response);
        $this->assertSame(['Dashboard', 'Films', 'Add film', 'Lists', 'Accounts'], array_column($tabs, 'label'));
        $this->assertSame(route('admin.films.create'), $tabs[2]['href']);
        $this->assertSame(['Dashboard'], $this->currentTabs($tabs));
    }

    /**
     * The links in the page's tab bar, in order.
     *
     * @return list<array{label: string, href: string, current: bool}>
     */
    private function tabBar(TestResponse $response): array
    {
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());

        $links = (new DOMXPath($document))->query('//nav[contains(concat(" ", @class, " "), " tab-bar ")]//a');
        $this->assertGreaterThan(0, $links->length, 'The page has no tab bar.');

        return array_map(fn (DOMElement $link): array => [
            'label' => trim($link->textContent),
            'href' => $link->getAttribute('href'),
            'current' => $link->getAttribute('aria-current') === 'page',
        ], iterator_to_array($links));
    }

    /**
     * @param  list<array{label: string, href: string, current: bool}>  $tabs
     * @return list<string>
     */
    private function currentTabs(array $tabs): array
    {
        return array_values(array_column(array_filter($tabs, fn (array $tab): bool => $tab['current']), 'label'));
    }
}
