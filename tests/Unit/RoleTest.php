<?php

namespace Tests\Unit;

use Tests\User;
use Tests\TestCase;
use Actengage\Roles\Role;

class RoleTest extends TestCase
{

    public function testCreateModel()
    {
        $role = Role::create([
            'name' => 'test'
        ]);

        $this->assertThat($role->id, $this->equalTo(1));
        $this->assertThat($role->name, $this->equalTo('test'));
        $this->assertThat($role->description, $this->equalTo('test'));
    }

    public function testUserModel()
    {
        $user = User::create([
            'name' => 'John Smith',
            'email' => 'you@example.com',
            'password' => 'test'
        ]);

        $this->assertThat($user->id, $this->equalTo(1));
    }

    public function testAssigningRolesToUser()
    {
        $adminRole = Role::create([
            'name' => 'Administrator'
        ]);

        $userRole = Role::create([
            'name' => 'User'
        ]);

        $user = User::create([
            'name' => 'John Smith',
            'email' => 'you@example.com',
            'password' => 'test'
        ]);

        $user->grantRole($adminRole);

        $this->assertTrue($user->hasRole($adminRole));
        $this->assertFalse($user->hasRole($userRole));

        $user->grantRole($userRole);

        $this->assertTrue($user->hasRole($userRole));
    }

    public function testAssigningChildRoleToUser()
    {
        $adminRole = Role::create([
            'name' => 'Administrator'
        ]);

        $adminSubRole = Role::create([
            'name' => 'User',
            'parent_id' => $adminRole->id
        ]);

        $userRole = Role::create([
            'name' => 'User'
        ]);

        $user = User::create([
            'name' => 'John Smith',
            'email' => 'you@example.com',
            'password' => 'test'
        ]);

        $user->grantRole($adminSubRole);

        $this->assertTrue($user->hasRole($adminRole));
        $this->assertTrue($user->hasRole($adminSubRole));
        $this->assertFalse($user->hasRole($userRole));

        $user->revokeRole($adminRole);

        $this->assertFalse($user->hasRole($adminRole));
    }

}
