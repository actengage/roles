<?php

namespace Actengage\Roles;

use Exception;
use Carbon\Carbon;
use Actengage\Roles;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

trait HasRoles {

    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }

    public function isAccountOwner() {
        return $this->roles()->get()->contains(Role::findByName('account_owner'));
    }

    public function hasRole($role)
    {
        if(!$role instanceof Role) {
            $role = Role::findByName($role);
        }

        return $this->roles()->get()->contains($role) || $this->isAccountOwner();
    }

    public function hasRoles($roles)
    {
        foreach($roles as $role) {
            if(!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function grantRole(Role $role, $expires = null)
    {
        do {
            if(!$this->hasRole($role)) {
                $this->roles()->attach($role, [
                    //'expires_at' => $expires
                ]);
            }
        } while($role = $role->parent);
    }

    public function grantRoles($roles, $expires = null)
    {
        foreach($roles as $role) {
            $this->grantRole($role, $expires);
        }
    }

    public function revokeRole(Role $role)
    {
        $this->roles()->detach($role);
    }

    public function revokeRoles($roles)
    {
        foreach($roles as $role) {
            $this->roles()->detach($role);
        }
    }

    public function revokeAllRoles()
    {
        $this->roles()->detach();
    }

}
