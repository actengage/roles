<?php

namespace Actengage\Roles;

use Actengage\Sluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model {

	use Sluggable;
	
	protected $fillable = [
		'parent_id',
        'name',
        'description',
		'order'
	];

	public function getSlugQualifierAttributeName(): string
	{
		return 'name';
	}

	public function parent()
	{
		return $this->belongsTo(Role::class);
	}

	public function children()
	{
		return $this->hasMany(Role::class);
	}

    public function scopeRoleable($query, Model $model)
    {
		$query->whereRoleableType(get_class($model));
		$query->whereRoleableId($model->getKey());
	}
	
	public function reorder()
	{
		$this->reorderBranch($this->parent_id);

		if($this->parent_id != $this->getOriginal('parent_id')) {
			$this->reorderBranch($this->getOriginal('parent_id'), true);
		}
	}

	public function reorderBranch($parent_id, $debug = false)
	{
		$order = 1;

		foreach($branch = static::branch($this, $parent_id) as $model) {
			if($order == $this->order && $model->parent_id == $this->parent_id) {
				$order++;
			}

			if($model->id == $this->id) {
				continue;
			}

			if($model->id != $this->id) {
				$model->order = $order++;
				$model->save();
			}
		}

		return $branch;
	}

	public static function branch(Role $node, $parent_id)
	{
		$query = static::orderBy('order', 'asc');

		if($parent_id) {
			$query->whereParentId($parent_id);
		}
		else {
			$query->whereNull('parent_id');
		}

		return $query->get();
	}

    /**
     * Get the slug delimiting string.
     *
     * @return string
     */
    public function getSlugDelimiter(): string
    {
        return '_';
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new RoleQueryBuilder($query);
	}
	
	public static function superAdmin()
	{
		return static::findOrFail(config('roles.super_admin'));
	}
	
	public static function admin()
	{
		return static::findOrFail(config('roles.admin'));
	}

	public static function boot()
	{
		parent::boot();

	    /**
	     * Listen to the Role saving event.
	     *
	     * @param  Role $source
	     * @return void
	     */
	    static::saving(function(Role $role)
	    {
			if(!$role->description) {
				$role->description = $role->name;
			}

	        if(!$role->order) {
	            $role->reorder();
	        }
	    });
	}

}
