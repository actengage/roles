# Roles

A simple package for assigning many-to-many "roles" to Eloquent models. This
packages provides the migrations, a config file, a Role model, a HasRoles trait,
and an ability to sync the roles from a config to the database.

### Installation

    composer require actengage/roles

### Implementation

To implement Roles, you just need to assign the `HasRoles` trait to the model
receiving the roles.

    namespace App\User;

    use Actenage\Roles\HasRoles;
    use Illuminate\Database\Eloquent\Model;

    class User extends Model {

        use HasRoles;

    }

### Gates & Policies

Roles are meant to be used directly within Laravel Gates and Policies.
```
Gate::define('sudo', function ($user, $model) {
    return $user->hasRole(Role::findByName('account_owner'));
});
```
```
<?php

namespace App\Policies;

use App\User;
use App\Post;

class PostPolicy
{
    /**
     * If the user an account owner, the policy should always pass.
     *
     * @param  \App\User  $user
     * @param  \App\Post  $ability
     * @return bool
     */
    public function before($user, $ability)
    {
        // isSuperAdmin() is a helper function provided by the HasRoles trait.
        // Which is a shortcut to: $user->hasRole(Role::findByName('account_owner'));
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Determine if the given post can be updated by the user.
     *
     * @param  \App\User  $user
     * @param  \App\Post  $post
     * @return bool
     */
    public function update(User $user, Post $post)
    {
        return $user->id === $post->user_id;
    }
}
```
### Basic Example
```
$role = Role::findByName('account_owner');

$user = User::findOrFail(1);
$user->grantRole($role);

dd($user->hasRole($role)); // returns -> `true`

$user->revokeRole($role);

dd($user->hasRole($role)); // returns -> `false`
```

### Parent/Child Roles
```
$role = Role::findByName('account_owner');

$childRole = Role::create([
    'name' => 'Child Role',
    'parent_id' => $role->id
]);

$user = User::findOrFail(1);
$user->grantRole($childRole);

dd($user->hasRole($role)); // returns -> `true`
dd($user->hasRole($childRole)); // returns -> `true`

$user->revokeRole($childRole);

dd($user->hasRole($role)); // returns -> `true`
dd($user->hasRole($childRole)); // returns -> `false`
```
