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

    public function isSuperAdmin() {
        return $this->roles()->get()->contains(Role::findByName(config('roles.super_admin', 'account_owner')));
    }

    public function hasRole($role)
    {
        if(!$role instanceof Role) {
            $role = Role::findByName($role);
        }

        return $this->roles()->get()->contains($role) || $this->isSuperAdmin();
    }

    public function hasRoles($roles)
    {
        return $this->hasOneRole($roles);
    }

    public function hasAllRoles($roles)
    {
        foreach($roles as $role) {
            if(!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function hasOneRole($roles)
    {
        foreach($roles as $role) {
            if($this->hasRole($role)) {
                return true;
            }
        }

        return true;
    }

    public function syncRoles($roles)
    {
        $this->revokeAllRoles();
        $this->grantRoles($roles);
    }

    public function grantRole($role)
    {
        if(!$role instanceof Role) {
            $role = Role::findByName($role);
        }

        do {
            if(!$this->hasRole($role)) {
                $this->roles()->attach($role);
            }
        } while($role = $role->parent);
    }

    public function grantRoles($roles)
    {
        foreach($roles as $role) {
            $this->grantRole($role);
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
