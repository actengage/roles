<?php

namespace Actengage\Roles;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;

class RoleQueryBuilder extends Builder
{
    public function whereKey($value)
    {
        $where = function($value, $query) {
            if(is_numeric($value)) {
                $query->where($this->model->getQualifiedKeyName(), '=', $value);
            }
            else {
                $query
                    ->orWhere('name', $value)
                    ->orWhere('slug', $this->model->slugify($value));
            }
        };

        if(is_array($value) || $value instanceof Arrayable) {
            $this->where(function($query) use ($value, $where) {
                foreach($value as $val) {
                    $query->orWhere(function($query) use ($val, $where) {
                        $where($val, $query);
                    });
                }
            });
            
            return $this;
        }

        return $this->where(function($query) use ($value, $where) {
            $where($value, $query);
        });
    }

}