<?php

namespace Actengage\Roles;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
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

    public function syncRoles($roles, $pivotData = [], bool $detach = true): self
    {
        if(is_bool($pivotData)) {
            $detach = $pivotData;
            
            $pivotData = [];
        }
        else if($pivotData instanceof Arrayable) {
            $pivotData = $pivotData->toArray();
        }

        $roles = collect($roles)
            ->mapWithKeys(function($role) use ($pivotData) {
                if(!$role instanceof Role) {
                    $role = $this->getRoleClassName()::findOrFail($role);
                }

                $pivotData = is_callable($pivotData) ? call_user_func($pivotData, $role) : (array) $pivotData;

                return ["$role->id" => $pivotData];
            });

        $this->roles()->sync($roles, $detach);

        return $this;
    }

    public function syncRolesWithoutDetaching($roles, $pivotData = []): self
    {
        return $this->syncRoles($roles, $pivotData, false);
    }

    public function grantRole($role, ?callable $fn = null): self
    {
        if(!$role instanceof Role) {
            $role = $this->getRoleClassName()::findOrFail($role);
        }
          
        do {
            if(!$this->hasRole($role)) {
                $this->roles()->attach($role, $fn ? call_user_func($fn, $role) : []);
            }
        } while($role = $role->parent);

        return $this;
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

        return $this;
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

        return $this;
    }

    public function transformRoleModel($value): Model
    {
        $class = $this->getRoleClassName();

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
                $model->syncRoles($model->transformRolesFromRequest(request()));   
            }
        });
    }
}
