<?php

namespace Actengage\Roles;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {

	protected $fillable = [
		'parent_id',
        'name',
        'description',
		'order'
    ];

	public function parent()
	{
		return $this->belongsTo(Role::class);
	}

	public function children()
	{
		return $this->hasMany(Role::class);
	}

	public function getSlug()
	{
		$role = $this;
		$chain = [];

		do {
			$chain[] = snake_case($role->name);
		} while($role = $role->parent);

		return implode('.', array_reverse($chain));
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

    public static function findByName($name)
    {
		return Role::where(function($query) use ($name) {
			$query->whereName($name);
			$query->orWhere('slug', '=', $name);
		})->first();
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
	        $role->slug = $role->getSlug();

			if(!$role->description) {
				$role->description = $role->name;
			}

	        if(!$role->order) {
	            $role->reorder();
	        }
	    });
	}

}
