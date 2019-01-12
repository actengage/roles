<?php

namespace Actengage\Roles;

use Actengage\Roles\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the roles config file with the database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach($list = new Collection(config('roles.list')) as $item) {
            if(!$role = Role::find($item[0])) {
                $role = new Role;
                $role->id = $item[0];
            }

            $role->parent_id = $item[1];
            $role->name = $item[2];
            $role->description = isset($item[3]) ? $item[3] : null;

            $order[$role->parent_id][] = $role;

            $role->order = count($order[$role->parent_id]);
            $role->save();
        }

        Role::whereNotIn('id', $list->pluck(0))->delete();
    }
}
