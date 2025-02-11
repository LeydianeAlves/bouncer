<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class RoleRestrictionsTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_create_an_assigned_role_for_restricted_model_class($provider)
    {
        [$bouncer, $user] = $provider();
        $account = new Account;

        $bouncer->assign(['admin', 'moderator'])->to($user)->for($account);

        $this->AssertTrue($user->roles()->count() === 2);
        $record = $user->roles()->first();

        $this->assertEquals($account->getMorphClass(), $record->pivot->restricted_to_type);
        $this->assertNull($record->pivot->restricted_to_id);
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_create_an_assigned_role_for_restricted_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $bouncer->assign(['admin', 'moderator'])->to($user)->for($account);
        
        $record = $user->roles()->first();
        $this->assertEquals(Account::class, $record->pivot->restricted_to_type);
        $this->assertEquals($account->getKey(), $record->pivot->restricted_to_id);
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_assign_a_role_to_the_user_for_a_restricted_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $user->assign('admin', $account);
        $bouncer->assign('moderator')->to($user)->for($account);

        $this->assertTrue($user->isAn('admin', 'moderator'));
        $this->assertTrue($user->isAnFor('admin', $account));
        $this->assertTrue($bouncer->is($user)->aFor('moderator', $account));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_retract_a_role_from_the_user_for_a_restricted_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account1 = Account::create();
        $account2 = Account::create();

        $user->assign('admin', [$account1, $account2]);
        $user->assign('editor', [$account1, $account2]);

        $this->assertTrue($user->isAllFor(['admin', 'editor'], $account1));
        $this->assertTrue($user->isAllFor(['admin', 'editor'], $account2));

        $bouncer->retract('admin')->from($user)->for([$account1, $account2]);

        $this->assertTrue($user->isNotAnFor('admin', $account1));
        $this->assertTrue($user->isNotAnFor('admin', $account2));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_user_is_a_restricted_role($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();

        $user->assign(['admin', 'viewer'], $account);

        $this->assertTrue($bouncer->is($user)->anFor('admin', $account));
        $this->assertTrue($bouncer->is($user)->aFor('viewer', $account));
        $this->assertTrue($bouncer->is($user)->allFor(['admin', 'viewer'], $account));

        $this->assertTrue($bouncer->is($user)->notAnFor('moderator', $account));
        $this->assertTrue($bouncer->is($user)->notAFor('superadmin', $account));

    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_user_has_an_ability_for_a_restricted_model($provider)
    {
        [$bouncer, $user] = $provider();
        $account = Account::create();
        $bouncer->assign('admin')->to($user)->for($account);

        $bouncer->allow('admin')->to('edit', Account::class);
        $bouncer->allow('admin')->to('view', Account::class);
        $bouncer->allow('admin')->to('view', User::class);
        $bouncer->allow('admin')->to('edit-site');
        
        $this->assertFalse($bouncer->can('edit', [null, $account]));
        $this->assertTrue($bouncer->can('view', [Account::class, $account]));
        $this->assertTrue($bouncer->canAny(['edit', 'view'], [Account::class, $account]));
        $this->assertTrue($bouncer->cannot('delete', [null, $account]));
        $this->assertTrue($bouncer->cannot('delete', [Account::class, $account]));
    }
}