<?php

namespace Actengage\Roles;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

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
        return collect($this->getRolesFromRequest($request))
            ->mapWithKeys(function($value, $key) {
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
        return $this->morphToMany($this->getRoleClassName(), 'roleable')
            ->using($this->getRoleablePivotClassName())
            ->withPivot('team_id');
    }

    public function hasRole($role, ?callable $fn = null): bool
    {
        if(!$role instanceof Role) {
            $role = $this->getRoleClassName()::find($role);
        }

        if($this->isSuperAdmin()) {
            return true;
        }

        $query = $this->roles();

        if(is_callable($fn)) {
            $fn($query);
        }

        return $query->get()->contains($role);
    }

    public function hasRoles($roles, ?callable $fn = null): bool
    {
        return $this->hasOneRole($roles, $fn);
    }

    public function hasAllRoles($roles, ?callable $fn = null): bool
    {
        foreach($roles as $role) {
            if(!$this->hasRole($role, $fn)) {
                return false;
            }
        }

        return true;
    }

    public function hasOneRole($roles, ?callable $fn = null): bool
    {
        foreach($roles as $role) {
            if($this->hasRole($role, $fn)) {
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
            ->mapWithKeys(function($role, $key) use ($pivotData) {
                if(!$role instanceof Role) {
                    $role = $this->transformRoleModel($role);
                }

                $pivotData = is_callable($pivotData) ? call_user_func($pivotData, $role) : (array) $pivotData;

                return ["{$role->getKey()}" => $pivotData];
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
            return $class::query()->firstOrFail($value);
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
