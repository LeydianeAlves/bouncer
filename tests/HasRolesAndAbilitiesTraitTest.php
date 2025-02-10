<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Silber\Bouncer\Database\Models;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;
use Workbench\App\Models\UserWithSoftDeletes;

class HasRolesAndAbilitiesTraitTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function get_abilities_gets_all_allowed_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->allow('moderator')->to('ban-users');
        $bouncer->allow($user)->to('create-posts');

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('moderator')->to($user)->for(Account::create());

        $bouncer->forbid($user)->to('create-sites');
        $bouncer->allow('editor')->to('edit-posts');

        $this->assertEquals(
            ['create-posts', 'edit-site'],
            $user->getAbilities()->pluck('name')->sort()->values()->all()
        );
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function get_forbidden_abilities_gets_all_forbidden_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->forbid('admin')->to('edit-site');
        $bouncer->forbid($user)->to('create-posts');
        $bouncer->assign('admin')->to($user);

        $bouncer->allow($user)->to('create-sites');
        $bouncer->forbid('editor')->to('edit-posts');

        $this->assertEquals(
            ['create-posts', 'edit-site'],
            $user->getForbiddenAbilities()->pluck('name')->sort()->values()->all()
        );
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function get_abilities_for_restricted_model_gets_abilities_only_for_that_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->allow('viewer')->to('view', $account);
        $bouncer->allow($user)->to('create-accounts');

        $bouncer->assign(['admin', 'viewer'])->to($user)->for($account);

        $this->assertEquals(
            ['edit-site', 'view'],
            $user->getAbilitiesForRoleRestriction($account)->pluck('name')->sort()->values()->all()
        );

        $this->assertEquals(
            ['create-accounts'],
            $user->getAbilities()->pluck('name')->sort()->values()->all()
        );
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function get_forbidden_abilities_for_a_restricted_model_gets_all_forbidden_abilities_for_that_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $bouncer->forbid('admin')->to('edit-site');
        $bouncer->forbid($user)->to('create-posts');
        $bouncer->assign('admin')->to($user)->for($account);

        $bouncer->allow($user)->to('create-sites');

        $this->assertEquals(
            ['create-posts', 'edit-site'],
            $user->getForbiddenAbilities()->pluck('name')->sort()->values()->all()
        );

        $this->assertEquals(
            ['edit-site'],
            $user->getForbiddenAbilitiesForRoleRestriction($account)
                ->pluck('name')->sort()->values()->all()
        );
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_give_and_remove_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));

        $user->disallow('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_give_and_remove_model_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('delete', $user);

        $this->assertTrue($bouncer->cannot('delete'));
        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->can('delete', $user));

        $user->disallow('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_give_and_remove_ability_for_everything($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow()->everything();

        $this->assertTrue($bouncer->can('delete'));
        $this->assertTrue($bouncer->can('delete', '*'));
        $this->assertTrue($bouncer->can('*', '*'));

        $user->disallow()->everything();

        $this->assertTrue($bouncer->cannot('delete'));
        $this->assertTrue($bouncer->cannot('delete', '*'));
        $this->assertTrue($bouncer->cannot('*', '*'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_forbid_and_unforbid_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('edit-site');
        $user->forbid('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));

        $user->unforbid('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_forbid_and_unforbid_model_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('delete', $user);
        $user->forbid('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $user->unforbid('delete', $user);

        $this->assertTrue($bouncer->can('delete', $user));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_forbid_and_unforbid_everything($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('delete', $user);
        $user->forbid()->everything();

        $this->assertTrue($bouncer->cannot('delete', $user));

        $user->unforbid()->everything();

        $this->assertTrue($bouncer->can('delete', $user));
    }

    // user can with restriction
    // user can with model and restriction

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_assign_and_retract_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow('admin')->to('edit-site');
        $user->assign('admin');

        $this->assertEquals(['admin'], $user->getRoles()->all());
        $this->assertTrue($bouncer->can('edit-site'));

        $user->retract('admin');

        $this->assertEquals([], $user->getRoles()->all());
        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_assign_and_retract_roles_for_restricted_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->allow('admin')->to('view', Account::class);

        $user->assign('admin', $account);
   
        $this->assertEquals(['admin'], $user->getRolesForRoleRestriction($account)->all());

        $this->assertTrue($bouncer->can('edit-site', [null, $account]));
        $this->assertTrue($bouncer->cannot('edit-site'));

        $this->assertTrue($bouncer->can('view', [Account::class, $account]));
        $this->assertTrue($bouncer->cannot('view', [null, $account]));
        $this->assertTrue($bouncer->cannot('view'));

        $user->retract('admin', $account);

        $this->assertEquals([], $user->getRoles()->all());
        $this->assertTrue($bouncer->cannot('edit-site', [null, $account]));
        $this->assertTrue($bouncer->cannot('view', [Account::class, $account]));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_assign_and_retract_roles_for_multiple_restriction($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();
        $account2 = Account::create();

        $bouncer->allow('admin')->to('view', Account::class);

        $user->assign(['admin', 'viewer'], [$account, $account2]);
    
        $this->assertEquals(['viewer', 'admin'], $user->getRolesForRoleRestriction($account)->all());
        $this->assertEquals(['viewer', 'admin'], $user->getRolesForRoleRestriction($account2)->all());

        $this->assertTrue($bouncer->can('view', [Account::class, $account]));
        $this->assertTrue($bouncer->can('view', [Account::class, $account2]));

        $user->retract('admin', [$account, $account2]);

        $this->assertEquals(['viewer'], $user->getRolesForRoleRestriction($account)->all());
        $this->assertTrue($bouncer->cannot('view', [Account::class, $account]));
        $this->assertTrue($bouncer->cannot('view', [Account::class, $account2]));
    }


    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $this->assertTrue($user->isNotAn('admin'));
        $this->assertFalse($user->isAn('admin'));

        $this->assertTrue($user->isNotA('admin'));
        $this->assertFalse($user->isA('admin'));

        $user->assign('admin');

        $this->assertTrue($user->isAn('admin'));
        $this->assertFalse($user->isAn('editor'));
        $this->assertFalse($user->isNotAn('admin'));
        $this->assertTrue($user->isNotAn('editor'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_multiple_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $this->assertFalse($user->isAn('admin', 'editor'));

        $user->assign('moderator');
        $user->assign('editor');

        $this->assertTrue($user->isAn('admin', 'moderator'));
        $this->assertTrue($user->isAll('editor', 'moderator'));
        $this->assertFalse($user->isAll('moderator', 'admin'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_roles_for_restriction($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $this->assertFalse($user->isA('admin', 'editor'));

        $user->assign('moderator', $account);
        $user->assign('editor', $account);

        $this->assertFalse($user->isA('admin', 'moderator'));
        $this->assertTrue($user->isAFor(['admin', 'moderator'], $account));

        $this->assertFalse($user->isAll('editor', 'moderator'));
        $this->assertTrue($user->isAllFor(['editor', 'moderator'], $account));

        // $this->assertFalse($user->isAll('moderator', 'admin'));
        $this->assertFalse($user->isAllFor(['moderator', 'admin'], $account));
    }

    #[Test]
    public function deleting_a_model_deletes_the_permissions_pivot_table_records()
    {
        $bouncer = $this->bouncer();

        $user1 = User::create();
        $user2 = User::create();

        $bouncer->allow($user1)->everything();
        $bouncer->allow($user2)->everything();

        $this->assertEquals(2, $this->db()->table('permissions')->count());

        $user1->delete();

        $this->assertEquals(1, $this->db()->table('permissions')->count());
    }

    #[Test]
    public function soft_deleting_a_model_persists_the_permissions_pivot_table_records()
    {
        Models::setUsersModel(UserWithSoftDeletes::class);

        $bouncer = $this->bouncer();

        $user1 = UserWithSoftDeletes::create();
        $user2 = UserWithSoftDeletes::create();

        $bouncer->allow($user1)->everything();
        $bouncer->allow($user2)->everything();

        $this->assertEquals(2, $this->db()->table('permissions')->count());

        $user1->delete();

        $this->assertEquals(2, $this->db()->table('permissions')->count());
    }

    #[Test]
    public function deleting_a_model_deletes_the_assigned_roles_pivot_table_records()
    {
        $bouncer = $this->bouncer();

        $user1 = User::create();
        $user2 = User::create();

        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(2, $this->db()->table('assigned_roles')->count());

        $user1->delete();

        $this->assertEquals(1, $this->db()->table('assigned_roles')->count());
    }

    #[Test]
    public function soft_deleting_a_model_persists_the_assigned_roles_pivot_table_records()
    {
        Models::setUsersModel(UserWithSoftDeletes::class);

        $bouncer = $this->bouncer();

        $user1 = UserWithSoftDeletes::create();
        $user2 = UserWithSoftDeletes::create();

        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(2, $this->db()->table('assigned_roles')->count());

        $user1->delete();

        $this->assertEquals(2, $this->db()->table('assigned_roles')->count());
    }
}
