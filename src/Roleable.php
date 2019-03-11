<?php

namespace Actengage\Roles;

use Exception;
use Carbon\Carbon;
use Actengage\Roles;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Collection;

trait Roleable {

    public static function bootRoleable()
    {
        static::saved(function($model) {
            $ids = collect($model->getRolesFromRequest(request()))
                ->map(function($role) {
                    if(is_numeric($role) || is_string($role)) {
                        return $role;
                    }                   

                    return ((object) $role)->id;
                });
                
            if($ids->count()) {
                $model->grantRoles($ids->all());
            }
            else {
                $model->revokeRoles();
            }
        });
    }

    public function getRolesFromRequest($request)
    {   
        return $request->roles;
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'roleable');
    }

    public function superAdminRole()
    {
        return Role::findByName(config('roles.super_admin', 'account_owner'));
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->get()->contains($this->superAdminRole());
    }

    public function hasRole($role): bool
    {
        if(!$role instanceof Role) {
            $role = Role::findByName($role);
        }

        return $this->isSuperAdmin() || $this->roles()->get()->contains($role);
    }

    public function hasRoles($roles): bool
    {
        return $this->hasOneRole($roles);
    }

    public function hasAllRoles($roles): bool
    {
        foreach($roles as $role) {
            if(!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function hasOneRole($roles): bool
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
            $test = $role;

            if(!$role = Role::findByName($role)) {
                throw new InvalidArgumentException('"'.$role.'" is not a valid role');
            }
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

    public function revokeRoles($roles = null)
    {
        if($roles && count($roles)) {
            foreach($roles as $role) {
                $this->roles()->detach($role);
            }
        }
        else {
            $this->revokeAllRoles();
        }
    }

    public function revokeAllRoles()
    {
        $this->roles()->detach();
    }

}
