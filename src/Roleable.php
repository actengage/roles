<?php

namespace Actengage\Roles;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Roleable {

    public function getRoleClassName(): string
    {
        return Role::class;
    }

    public function getRoleablePivotClassName(): string
    {
        return Pivot::class;
    }

    public function getRolesInputName(): string
    {   
        return 'roles';
    }

    public function getRolesFromRequest(Request $request): Collection
    {   
        return collect($request->input($this->getRolesInputName()));
    }
    
    public function transformRolesFromRequest(Request $request): Collection
    {
        return collect($this->getRolesFromRequest($request))->mapWithKeys(function($value, $key) {
            if(is_array($value)) {
                dd($value, $key);

                return $value;
            }

            return [$this->transformRoleModel($value)->getKey() => $value];
        });
    }

    public function shouldSyncRolesOnSaved(Request $request): bool
    {
        return $request->has($this->getRolesInputName());
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains($this->superAdminRole());
    }

    public function superAdminRole(): Model
    {
        return $this->getRoleClassName()::findOrFail(config('roles.super_admin', 'super_admin'));
    }
    
    public function roles(): Relation
    {
        return $this->morphToMany($this->getRoleClassName(), 'roleable')->using($this->getRoleablePivotClassName());
    }

    public function hasRole($role): bool
    {
        if(!$role instanceof Role) {
            $role = $this->getRoleClassName()::find($role);
        }

        return $this->isSuperAdmin() || $this->roles->contains($role);
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

    public function syncRoles($roles, ?callable $fn = null): self
    {
        return $this->revokeAllRoles()->grantRoles($roles, $fn);
    }

    public function grantRole($role, ?callable $fn = null): self
    {
        if(!$role instanceof Role) {
            $role = $this->getRoleClassName()::findOrFail($role);
        }
          
        do {
            if(!$this->hasRole($role)) {
                $this->roles()->attach($role, $fn ? Closure::call($this, $role) : []);
            }
        } while($role = $role->parent);

        return $this->load('roles');
    }

    public function grantRoles($roles, ?callable $fn = null): self
    {
        foreach($roles as $role) {
            $this->grantRole($role, $fn);
        }

        return $this;
    }

    public function revokeRole(Role $role): self
    {
        $this->roles()->detach($role);

        return $this->load('roles');
    }

    public function revokeRoles($roles = null): self
    {
        if($roles && count($roles)) {
            foreach($roles as $role) {
                $this->revokeRole($role);
            }
        }
        else {
            $this->revokeAllRoles();
        }

        return $this;
    }

    public function revokeAllRoles(): self
    {
        $this->roles()->detach();

        return $this->load('roles');
    }

    public function transformRoleModel($value): Model
    {
        $class = $this->getRoleClassName();
        dd($class);

        if(is_numeric($value) || is_string($value)) {
            return $class::findOrFail($value);
        }

        if(is_array($value)) {
            return $class::make($value);
        }

        if(is_a($value, $class)) {
            return $value;
        }

        throw new InvalidArgumentException('"'.$value.'" is not an instance of '.$class.'.');
    }
    
    public static function bootRoleable()
    {
        static::saved(function($model) {
            if($model->shouldSyncRolesOnSaved(request())) {
                $model->roles()->sync($model->transformRolesFromRequest(request()));                
            }
        });
    }
}
