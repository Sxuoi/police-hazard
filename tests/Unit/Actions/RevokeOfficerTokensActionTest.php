<?php

namespace Tests\Unit\Actions;

use App\Actions\RevokeOfficerTokensAction;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class RevokeOfficerTokensActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deletes_all_user_tokens(): void
    {
        $tokensRelation = Mockery::mock();
        $tokensRelation->shouldReceive('delete')->once();

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('tokens')->andReturn($tokensRelation);

        $action = new RevokeOfficerTokensAction;

        ($action)($user);

        // Assertion is implicit via Mockery's ->once() expectation
        $this->assertTrue(true);
    }
}
