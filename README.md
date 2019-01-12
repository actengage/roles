# Roles

A simple package for assigning many-to-many "roles" to Eloquent models. This
packages provides the migrations, a config file, a Role model, a HasRoles trait,
and an ability to sync the roles from a config to the database.

### Installation

    composer require actengage/roles

### Basic Example

    namespace App\User;

    use Actenage\Roles\Role;
    use Illuminate\Database\Eloquent\Model;

    class User extends Model {

        use HasRoles;

    }

    $role = Role::findByName('account_owner');

    $user = User::findOrFail(1);
    $user->grantRole($role);

    dd($user->hasRole($role)); // returns -> `true`

    $user->revokeRole($role);

    dd($user->hasRole($role)); // returns -> `false`

### Parent/Child Roles

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
