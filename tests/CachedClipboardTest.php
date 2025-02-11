<?php

namespace Silber\Bouncer\Tests;

use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Silber\Bouncer\CachedClipboard;
use Silber\Bouncer\Contracts\Clipboard as ClipboardContract;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class CachedClipboardTest extends BaseTestCase
{
    /**
     * Make a new clipboard with the container.
     */
    protected static function makeClipboard(): ClipboardContract
    {
        return new CachedClipboard(new ArrayStore);
    }

    #[Test]
    public function it_caches_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('ban-users');

        $this->assertEquals(['ban-users'], $this->getAbilities($user));

        $bouncer->allow($user)->to('create-users');

        $this->assertEquals(['ban-users'], $this->getAbilities($user));
    }

    #[Test]
    public function it_caches_restricted_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());
        $account = Account::create();

        $bouncer->allow('admin')->to(['ban-users', 'delete-users']);
        $bouncer->allow($user)->to('view-users');

        $bouncer->assign('admin')->to($user)->for($account);
        $bouncer->assign('viewer')->to($user);
        
        $this->assertEquals(['ban-users', 'delete-users'], $this->getRestrictedAbilities($user, $account));
        $this->assertEquals(['ban-users', 'delete-users','view-users'], $this->getAbilities($user));

        $bouncer->allow('admin')->to('create-users');
        $this->assertEquals(['ban-users', 'delete-users'], $this->getRestrictedAbilities($user, $account));
    }

    #[Test]
    public function it_caches_empty_abilities()
    {
        $user = User::create();

        $this->assertInstanceOf(Collection::class, $this->clipboard()->getAbilities($user));
        $this->assertInstanceOf(Collection::class, $this->clipboard()->getAbilities($user));
    }

    #[Test]
    public function it_caches_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->an('editor'));

        $bouncer->assign('moderator')->to($user);

        $this->assertFalse($bouncer->is($user)->a('moderator'));
    }

    #[Test]
    public function it_caches_restricted_roles()
    {
        $bouncer = $this->bouncer($user = User::create());
        $account = Account::create();

        $bouncer->assign('editor')->to($user)->for($account);

        $this->assertTrue($bouncer->is($user)->aFor( 'editor', $account));
        $this->assertTrue($bouncer->is($user)->a('editor'));

        $bouncer->assign('moderator')->to($user)->for($account);

        $this->assertFalse($bouncer->is($user)->aFor('moderator', $account));
    }

    #[Test]
    public function it_always_checks_roles_in_the_cache()
    {
        $bouncer = $this->bouncer($user = User::create());
        $admin = $bouncer->role()->create(['name' => 'admin']);

        $bouncer->assign($admin)->to($user);

        $this->assertTrue($bouncer->is($user)->an('admin'));

        $this->db()->connection()->enableQueryLog();

        $this->assertTrue($bouncer->is($user)->an($admin));
        $this->assertTrue($bouncer->is($user)->an('admin'));
        $this->assertTrue($bouncer->is($user)->an($admin->id));

        $this->assertEmpty($this->db()->connection()->getQueryLog());

        $this->db()->connection()->disableQueryLog();
    }

    #[Test]
    public function it_can_refresh_the_cache()
    {
        $cache = new ArrayStore;

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create-posts');
        $bouncer->assign('editor')->to($user);
        $bouncer->allow('editor')->to('delete-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbilities($user));

        $bouncer->disallow('editor')->to('delete-posts');
        $bouncer->allow('editor')->to('edit-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbilities($user));

        $bouncer->refresh();

        $this->assertEquals(['create-posts', 'edit-posts'], $this->getAbilities($user));
    }

    #[Test]
    public function it_can_refresh_the_cache_only_for_one_user()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(['ban-users'], $this->getAbilities($user1));
        $this->assertEquals(['ban-users'], $this->getAbilities($user2));

        $bouncer->disallow('admin')->to('ban-users');
        $bouncer->refreshFor($user1);

        $this->assertEquals([], $this->getAbilities($user1));
        $this->assertEquals(['ban-users'], $this->getAbilities($user2));

        $bouncer->refreshFor($user1);

        $bouncer->retract('admin')->from($user1);
        $this->assertFalse($user1->isAn('admin'));
        $this->assertCount(0, $user1->getRoles());
        $this->assertCount(1, $user2->getRoles());
    }

    /**
     * Get the name of all of the user's abilities.
     *
     * @return array
     */
    protected function getAbilities(Model $user)
    {
        return $user->getAbilities($user)->pluck('name')->sort()->values()->all();
    }

    /**
     * Get the name of all of the user's abilities.
     *
     * @return array
     */
    protected function getRestrictedAbilities(Model $user, $restrictedModel)
    {
        return $user->getAbilitiesForRoleRestriction($restrictedModel)
            ->pluck('name')->sort()->values()->all();
    }
}
